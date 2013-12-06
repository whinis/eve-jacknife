<?php 
define("ACC_CORP_ONLY", 1);
define("ACC_CHAR_ONLY", 2);
define("ACC_BOTH", 0);

function getPageTimes($Db,$time_api,$time_exec) {
	global $allApiCalls;
	
	$output = "<span style=\"font-size:80%;\">";
	
   if ($Db->queries != 0)
		$output .= "\n".$Db->queries." queries<br>";
		
   $output .=  "api time: ".number_format($time_api,6)."s<br>";
   $output .=  "exec time: ".number_format($time_exec-$time_api,6)."s<br>";
   $output .=  "</span>";
	
	if (isset($Db->allQueries)) {
		 $output .= count($Db->allQueries) . " queries<br><h5>";
		 foreach ($Db->allQueries as $query => $count) {
			  if ($count > 1) // optimization required
					$output .= " MULTIPLE QUERIES ### ";
			  $output .= $query . " ## " . $count . "<br>\n";
		 }
		 $output .= "</h5>";
	}
	
	if (isset($allApiCalls)) {
		 $output .="<br>";
		 $output .= count($allApiCalls) . " apicalls<br><h5>";
		 foreach ($allApiCalls as $query) {
			  $output .= $query[2] . ": " . API_BASE_URL . $query[0] ."?" . http_build_query($query[1]) . "<br>\n";
		 }
		 $output .= "</h5>";
	}
		
	return $output;
}

abstract class auditorPage {
 public $Output = "";	// main body
 public $Title = "";		// page title
 public $Header = "";	// page header (after menu bar)
 public $Updated = "";	// update time (end of menu bar, if present)
 public $Times = "";		// execution times, sql info

 abstract public function GetName();				// return string name
 abstract public function GetAccMode();			// return ACC_BOTH, ACC_CHAR_ONLY, or ACC_CORP_ONLY
 abstract public function GetAccMask($corp); 	// return come combination of the values in eveAccessMasks
 abstract public function GetOutput($Db);  		// true if success, false if error
 public function SetHeaders() { }					// set any headers
}

 ?>