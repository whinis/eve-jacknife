<?php
// ****************************************************************************
//
// Mysql Library
// Copyright (C) 2015  Equto (whinis@whinis.com)
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// ****************************************************************************
class db
{
	
	protected $ref;
    protected $res;
    protected $prepare=false;
	protected $link;
	protected $prefix="";
    protected $lastQuery="";


	public $debugQuery=false;
	public $lastid;
    public $hold=false;
    public $queries = 0;

    private $params=array("");
	function __construct($host,$user,$pass,$database,$port=3306,$debug=false){
		$this->ref = new mysqli($host, $user, $pass, $database, $port);
		$this->destroy=false;
		if(mysqli_connect_errno($this->ref)){
			$this->destroy=true;
			$this->ref=null;
			trigger_error("(SQL) Mysql Connection Error ".(mysqli_connect_error()),E_USER_ERROR );
		}
		if(!$this->ref){
			$this->destroy=true;
			$this->ref=null;
			trigger_error("(SQL) Unknown connection error",E_USER_ERROR);
		}
		$this->link=$this->ref;
		mysqli_set_charset($this->ref,"utf8");
		$this->debug=$debug;
		if(defined("DB_PREFIX")){
			$this->prefix=DB_PREFIX;
		}
	}
	function __destruct(){
		if($this->ref)
			$this->close();
	}
    public function prepare(){
        $allowed=[
            'select',
            'selectWhere',
            'insert',
            'update',
            'delete'
        ];
        $this->prepare=true;
        $args=func_get_args();
        $function=array_shift($args);
        if(!in_array($function,$allowed)){
            return false;
        }else{
            call_user_func_array(array($this,$function),$args);
        }
        $this->prepare=false;
    }
	protected function _prepare($query){
        //clean the last statement if it exist
        if(is_object($this->res)) {
            $this->res->close();
        }
        if(strtolower(substr($query,0,6))=="select"){
            $this->type="select";
        }elseif(strtolower(substr($query,0,6))=="insert"){
            $this->type="insert";
        }else{
            $this->type="";
        }
        $this->lastQuery=$query;
		if($this->debugQuery==true)
			$this->printQuery();
		$this->debugQuery=false;
		#run query
		$this->res=$this->ref->prepare($query);
		if($this->res===false){
			if($this->debug) {
				echo $this->ref->error."<br>";
			}
			return false;
		}
        if(count($this->params)>1) {
            #bind parameters to insure safety
            $ref = new ReflectionClass('mysqli_stmt');
            $method = $ref->getMethod("bind_param");
            $params = $this->params;
            $method->invokeArgs($this->res, $params);
            $this->params = array("");
        }
	}
    //runs the last prepared query with new parameters
    public function execute($params){
        if(count($params)>1) {
            $types = "";
            $newParam = array();
            foreach ($params as $i => $param) { //making things references because otherwise you can't do the reflection.
                $types .= "s";
                $newParam[] =& $params[$i];
            }
            array_unshift($newParam, $types);
            #bind parameters to insure safety
            $ref = new ReflectionClass('mysqli_stmt');
            $method = $ref->getMethod("bind_param");
            $method->invokeArgs($this->res, $newParam);
            $this->params = array("");
        }
        $this->query();
    }
    private function printQuery($query=""){
        if($query){
            echo $query."<br>";
        }else{
            $string=str_split($this->lastQuery);
            $int=1;
            foreach($string as &$char){
                if($char=="?"){
                    $char=$this->params[$int];
                    $int++;
                }
            }
            $string=implode("",$string);
            echo $string."<br>";
        }
    }
	/************************************************************************/
	/*Description:protected function which runs passed queries and returns	*/
	/* 				formated results										*/
	/*Paramenters:                                           		     	*/
	/*     	$query=> prepared query which can be ran						*/
	/*Returns:																*/
	/*		$results=> results from mysql query returns null if error		*/
	/************************************************************************/
	public function query($query=""){
        if ($this->debug) {
            $q=$query;
            if($query==""){
                $q=$this->lastQuery;
            }
            if (!isset($this->allQueries[$q])) {
                $this->allQueries[$q] = 0;
            }

            $this->allQueries[$q]++;
        }
		// select prepared statement or raw query;
		if($query==""&&$this->res) {
			$result = $this->res->execute();
            $this->queries++;
			$success=!($this->res->error||$result===false);
			if(!$success){
				#output to a log file
				trigger_error("(SQL)".($this->res->error)." query: ".$query,E_USER_NOTICE );
				$this->error=true;
				$return=null;
			}
		}elseif($query!=""){
			if($this->debugQuery==true)
				$this->printQuery();
			$this->debugQuery=false;

			#run query
			$result=$this->ref->query($query);
            $this->queries++;

			if($this->ref->error) {
				#output to a log file
				trigger_error("(SQL)" . ($this->ref->error) . " query: " . $query, E_USER_NOTICE);
				$this->error = true;
				#close results.
				if (is_object($result))
					$result->close();
				return null;
			}
			$success=true;
		}else{
			return false;
		}

		$mysqlnd = function_exists('mysqli_fetch_all');//check if we have mysqlnd
        #check if select statement, store in array if so
        if($this->type=="select"){
            $return = new stdClass;
            if($mysqlnd===true) {
                if($query==""&&is_object($this->res)) {
                    $result = $this->res->get_result();
                }
                $return->results = $result->fetch_all(MYSQLI_ASSOC);
                $return->rows=$result->num_rows;
            }else{//backup for lack of mysqlnd

				$ref = new ReflectionClass('mysqli_stmt');
                $method = $ref->getMethod("bind_result");

                $variables = array();
                $data = array();
                $meta = $this->res->result_metadata();

                while($field = $meta->fetch_field()) {
                    $variables[] = &$data[$field->name]; // pass by reference
                }

                $method->invokeArgs($this->res,$variables);

                $i=0;
                while($this->res->fetch())
                {
                    $array[$i] = array();
                    foreach($data as $k=>$v)
                        $array[$i][$k] = $v;
                    $i++;

                    // don't know why, but when I tried $array[] = $data, I got the same one result in all rows
                }
                if(isset($array)) { //check if we got results back
                    $result = $array;
                    $return->results=$array;
                    $return->rows=count($array);//FIXME: look into other methods of checking rows
                }else{
                    $return->results=array();
                    $return->rows=0;
                }
            }

        }else{
            #check if insert, store last id if so
            if($this->type=="insert")
                $this->lastid=$this->ref->insert_id;
            $return=$result;

        }
        #close results.
        if(isset($result)&&is_object($result))
            $result->close();
		return $return;
	}
	/************************************************************************/
	/*Description:public functions which build a update sql statement based	*/
	/*						on information given defaults to select all		*/
	/*Paramenters:                                           		     	*/
	/*     	$table=> string value of table being queries					*/
	/*		$where=> argument containing array to be passed to where 		*/
	/*				function												*/
	/*OPTIONAL																*/
	/*		$columns=> array with columns to be selected					*/
	/*Returns:																*/
	/*		$results=> results from mysql query								*/
	/************************************************************************/
	public function select($table, $columns=null,$limit=null,$sort=null,$group=null,$have=null,$join=null){
		return $this->selectWhere($table,null,$columns,$limit,$sort,$group,$have,$join);
	}
	public function selectWhere($table,$where,$columns=null,$limit=null,$sort=null,$group=null,$have=null,$join=null){
        $this->params = array("");
		$statement="SELECT ";
		if($columns){
			foreach($columns as $key=>$column){
                if(!is_numeric($key)){
                    $statement .= $this->escape($column, "`", true) . " AS ".$this->escape($key, "`", true).",";
                }else {
                    $statement .= $this->escape($column, "`", true) . ",";
                }
			}
			$statement=substr($statement,0,-1);
		}else{
			$statement.="*";
		}
		$statement.=" FROM `".$this->prefix.$table."`";


		if(isset($join)){
			if(is_array($join[0])) {
				foreach($join as $jTable) {
					if(count($jTable)==3){
						array_unshift($jTable,$table); //shift the table to the beginning
					}
					$statement .= " INNER JOIN " . $this->escape($this->prefix.$jTable[0], "`", true) . " ON " . $this->escape($this->prefix.$jTable[0] . "." . $jTable[1], "`", true) . "=" . $this->escape($this->prefix.$jTable[2] . "." . $jTable[3], "`",true);
				}
			}else{
				if(count($join)==3){
					array_unshift($join,$table); //shift the table to the beginning
				}
				$statement .= " INNER JOIN " . $this->escape($this->prefix.$join[0], "`", true) . " ON " . $this->escape($this->prefix.$join[0] . "." . $join[1], "`", true) . "=" . $this->escape($this->prefix.$join[2] . "." . $join[3], "`",true);
			}
		}


		if(isset($where)){
			$statement.=$this->where($where);
		}

		if(isset($group)){
			$statement.=" GROUP BY ".$this->escape($sort,"`",true);
		}

		if(isset($have)){
			$statement.=" HAVING ".substr($this->where($have),6);
		}

		if(isset($sort)){
			if(!is_array($sort))
				$statement.=" ORDER BY ".$this->escape($sort,"`",true);
			else{
				//prevent mysql injection at sort
				if($sort[1]=="ASC")
					$sort[1]="ASC";
				else
					$sort[1]="DESC";
				$statement.=" ORDER BY ".$this->escape($sort[0],"`",true)." ".$sort[1];
			}
		}
		if(isset($limit)){
			if(is_int($limit))
				$statement.=" LIMIT ".$this->escape(array($limit,"int"),"",true);
			else
				$statement.=" LIMIT ".$this->escape(array($limit[0],"int"),"",true).",".$this->escape(array($limit[1],"int"),"",true);
		}
        $this->_prepare($statement.";");
        if($this->prepare==false) {
            return $this->query();
        }
	}
	/************************************************************************/
	/*Description:public function which build a insert sql statement based	*/
	/*						on information given							*/
	/*Paramenters:                                           		     	*/
	/*     	$table=> string value of table being queried					*/
	/*		$value=> array with columns as keys and values to be set, values*/
	/*				can either be arrays with types or straight varriables	*/
	/* OPTIONAL																*/
	/*		$columns=> array of columns being updated						*/
	/*Returns:																*/
	/*		$results=> results from mysql query								*/
	/************************************************************************/
	public function insert($table,$values){
        $this->params = array("");
		$columns=array_keys($values);
		$statement="INSERT INTO `".$this->prefix.$table."` ";
		if($columns){
			$test=false; //are there actual colums or just values
			$col="";
			foreach($columns as $column){
				if(is_numeric($column))
					continue;
				$col.=$this->escape($column,"`",true).",";
				$test=true;
			}
			if($test)
				$statement.="(".substr($col,0,-1).") ";
		}
		$statement.="VALUES ";
		$statement.="(";
		foreach($values as $value){
			$statement.=" ".$this->escape($value,"'").",";
		}
		$statement=substr($statement,0,-1).") ";
        $this->_prepare($statement.";");
        if($this->prepare==false) {
            return $this->query();
        }
	}
	/************************************************************************/
	/*Description:public functions which build a update sql statement based	*/
	/*						on information given							*/
	/*Paramenters:                                           		     	*/
	/*     	$table=> string value of table being queried					*/
	/*		$where=> argument containing array to be passed to where 		*/
	/*				function												*/
	/*		$value=> array with columns as keys and values to be set, values*/
	/*				can either be arrays with types or straight varriables	*/
	/*Returns:																*/
	/*		$results=> results from mysql query								*/
	/************************************************************************/
	public function update($table,$where,$values,$join=null){
        $this->params = array("");
		$statement="UPDATE `".$this->prefix.$table."` SET ";
		foreach($values as $column=>$value){
			if(is_array($value)){
				switch($value[0]){
					case "increment":
						$value=1;
						if(isset($value[1]))
							$amount=$value[1];
						$statement.=$this->escape($column,"`",true)."=".$this->escape($column,"`",true)."+".$this->escape($value,"'").",";
						break;
					case "decrement":
						$value=1;
						if(isset($value[1]))
							$amount=$value[1];
						$statement.=$this->escape($column,"`",true)."=".$this->escape($column,"`",true)."-".$this->escape($value,"'").",";
						break;

				}
			}else{
				$statement.=$this->escape($column,"`",true)."=".$this->escape($value,"'").",";
			}
		}
		if(isset($join)){
			$statement.=" INNER JOIN ".$this->escape($join[0],"`",true)." ON ".$this->escape($table.".".$join[1],"`",true)."=".$this->escape($join[0].".".$join[2],"`");
		}
		$statement=substr($statement,0,-1)." ";
		if(isset($where)){
			$statement.=$this->where($where);
		}
        $this->_prepare($statement.";");
        if($this->prepare==false) {
            return $this->query();
        }
	}
	
	
	public function delete($table,$where){
        $this->params = array("");
		$statement="DELETE FROM `".$this->prefix.$table."` ";
		if(isset($where)){
			$statement.=$this->where($where);
		}else
			return false;
        $this->_prepare($statement.";");
        if($this->prepare==false) {
            return $this->query();
        }
	}
	/************************************************************************/
	/*Description:private function that generates where statements         	*/
	/*Paramenters:                                           		     	*/
	/*     $where=> Array that contains columns as key and either an array	*/
	/*              containing operators and value or a value which defaults*/
    /*              equals													*/
	/*Returns:																*/
	/*		$statement=> a compiled where statement ready for including at	*/
	/*		end of sql statement											*/
	/************************************************************************/
	private function where($where){
		$statement=" WHERE ";
		$remove=(-4);
		foreach($where as $column=>$value){
			# assumed to be a where statement encased in parathenses
			if(is_numeric($column)){
				if(is_array($value)&&isset($value['or'])){
					unset($value['or']);
					$statement.="(".substr($this->where($value),6).") OR";
					$remove=(-3);
				}else{
					$statement.="(".substr($this->where($value),6).") AND";
					$remove=(-4);
				}


			}else{
				#end in OR or AND
				if(is_array($value)&&isset($value['or'])){
					$end= " OR ";
					$remove=(-3);
				}else{
					$end= " AND ";
					$remove=(-4);
				}
				if(count($value)==2&&isset($value['or']))
					$value=$value[0];

				#if the column has information on its type, escape it
				if(is_array($value)){
					switch($value[0]){
						case "IN":
							$operator=" IN ";
							$statement.=$this->escape($column,"`",true)." ".$operator."(";
								foreach($value[1] as $v){
									$statement.=$this->escape($v,'`').",";
								}
								$statement=substr($statement,0,-1).")";
							$value[2]=null;
							$bypass=true;
						break;
						case "not equal":
						case "!=":
							$operator="<>";
						break;
						case "greaterthan":
						case "greater than":
						case ">":
							$operator=">";
						break;
						case "lessthan":
						case "less than":
						case "<":
							$operator="<";
						break;
						case "greaterthanequalto":
						case "greater than equal to":
						case ">=":
							$operator=">=";
						break;
						case "lessthanequalto":
						case "less than equal to":
						case "<=":
							$operator="<=";
						break;
						case "IS NOT":
							$operator="IS NOT ";
						break;
						case "LIKE":
							$operator="LIKE ";
						break;
						case "BETWEEN":
							$operator="BETWEEN ";
							$statement.=$this->escape($column,"`",true)." ".$operator.$this->escape($value[1],"'")." AND ".$this->escape($value[2],"'");
							$value[2]=null;
							$bypass=true;
						break;
						default:
							$operator="=";
						break;
					
					
					}
					#don't bypass normal statement generation (not a special case)
					if(!isset($bypass))
						$statement.=$this->escape($column,"`",true)." ".$operator.$this->escape($value[1],"'");
					else
						$bypass=null;
				}else{
					if(is_null($value))
						$statement.=$this->escape($column,"`",true)." IS NULL ";
					else
						$statement.=$this->escape($column,"`",true)."=".$this->escape($value,"'");
				}
				$statement.=" ".$end;
			}
		}
		$statement=substr($statement,0,$remove)." ";
		$statement=preg_replace("|[ ]{2,}|"," ", $statement);
		return $statement;
	}
	/************************************************************************/
	/*Description:private function that escapes values given with sql safe 	*/
	/*				version         										*/
	/*Paramenters:                                           		     	*/
	/*     $input=> Array  contains value and type to be escaped or a value */
	/*				which defaults to simple real escapes					*/
	/*Returns:																*/
	/*		$value=> A escaped value										*/
	/************************************************************************/
	private function escape($input,$escape,$column=false){
		if(!$this->ref)
			trigger_error("(SQL) No Server Connection",E_USER_ERROR );
		$type="";

		if(is_array($input)){
			$type=@$input[1];
			$input=$input[0];
		}
		if($input===NULL)
			return "NULL";
		//check for mysql functions
		if(preg_match("|\((.+)\)|",$input)){
            $append="";
			switch($input){
	 			case (preg_match("|count\((.+)\)|i",$input,$matches)? true : false):
	 				$wrapper="count";
	 				$input=$matches[1];
	 			break;
	 			case (preg_match("|avg\((.+)\)|i",$input,$matches)? true : false):
	 				$wrapper="avg";
	 				$input=$matches[1];
	 			break;
	 			case (preg_match("|sum\((.+)\)|i",$input,$matches)? true : false):
	 				$wrapper="sum";
	 				$input=$matches[1];
	 			break;
	 			case (preg_match("|max\((.+)\)|i",$input,$matches)? true : false):
	 				$wrapper="max";
	 				$input=$matches[1];
	 			break;
	 			case (preg_match("|min\((.+)\)|i",$input,$matches)? true : false):
	 				$wrapper="min";
	 				$input=$matches[1];
	 			break;
                case (preg_match("|substring\((.+),(\d+),(\d+)\)|i",$input,$matches)? true : false):
                    $wrapper="substring";
                    $input=$matches[1];
                    $append=",".$matches[2].",".$matches[3];
                    break;
                case (preg_match("|length\((.+)\)|i",$input,$matches)? true : false):
                    $wrapper="length";
                    $input=$matches[1];
                    break;
	 			default:
	 				$wrapper=null;
	 			break;
				
			}
		}

        if(strpos($input,".")!==false&&$column){
            $parts=explode(".",$input);
            $return="";
            foreach($parts as $i=>$part){
                if($i>0){
                    $return.="."; //added the . back
                }
                $return.=$this->escape($part,$escape,$column);
            }
            if(isset($wrapper))
                $return=$wrapper."(".$return.$append.")";
            return $return;
        }

		//typecase inside
		switch($type){
			case "int":
				$input=preg_replace("![^0-9]!","",$input);
				if(!$column) {
					$this->params[0] .= "i";
				}
			break;
			default:
				if(!$column) {
					$this->params[0] .= "s";
				}
			break;
		}
		$return="";
		if(!$column) {
			$this->params[] =& $input;
			$return="?";
		}else{
            $return = $escape.preg_replace('![^0-9,a-z,A-Z$_]!',"",$input).$escape;
			//$return=$escape.$this->ref->real_escape_string($input).$escape;
		}
		if(isset($wrapper))
			$return=$wrapper."(".$return.$append.")";
		return $return;
	}
    public function clean(){
        //clean the last statement if it exist
        if(is_object($this->res)) {
            $this->res->close();
        }
    }
	#closes database
	public function close() {
        //clean the last statement if it exist
        if(is_object($this->res)) {
            $this->res->close();
        }

        if(isset($this->ref)&&$this->ref&&is_resource($this->ref))
			$this->ref->close();
	}
}
?>
