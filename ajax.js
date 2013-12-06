	var totalIsk
	var callback = {
				addKey: function(response){
					if(response[0]>0){
						alert(response[1])
						link=document.getElementById('keyAction')
						link.innerHTML='remove'
						link.onclick=function(){removeKey(response[0]); return false;}
					}else{
					
					
					
					}
				},
				addFormKey: function(response){
				},
				removeKey: function(response){
					if(response[0]>0){
						alert(response[1])
						if(document.getElementById('keyAction')){
							link=document.getElementById('keyAction')
							link.innerHTML='save'
							link.onclick=function(){show_div('api'); return false;}
						}else{
							removeRow("keysTable","row"+response[0]);
						}
					}else{
					
					}
				},
				checkKey: function(response){
					if(response[0]!=false && response!='logout'){
						var key=Number(response[0]);
						callback.apiKey=key;
						callback.keyName=response[1];
					}else{
						callback.changeApiKeyButtons(-1);
						callback.apiKey="";
						callback.keyName="";
					}
				},
				addFormKey: function(response){
				},
				saveNotes: function(response){
					if(response[0]>0){
						string=document.getElementById('noteText').value;
						if(string.length>140)
							string=string.substring(0,130)+" . . . ";
						document.getElementById('note'+callback.id).innerHTML=string
					}
				},
				editName: function(response){
					if(response[0]>=0){
						document.getElementById('name'+callback.id).innerHTML=document.getElementById('nameEdit'+callback.id).value
					}
				},
				Character: function(response){
					tIsk=document.getElementById('tIsk')
					tSp=document.getElementById('tSp')
					if(response[0]>0){
						document.getElementById('isk'+response[0]).innerHTML=response[1]
						document.getElementById('sp'+response[0]).innerHTML=response[2]
						document.getElementById('bday'+response[0]).innerHTML=response[3]
						if(response[1]!="ERROR")
							if(tIsk.innerHTML){
								totalIsk=Number(tIsk.innerHTML.replace(",","").replace(",","").replace(",","").replace(",","").replace(",","").replace(",",""))
								addIsk=Number(response[1].replace(",","").replace(",","").replace(",","").replace(",","").replace(",","").replace(",",""))
								totalIsk +=addIsk
								tIsk.innerHTML=addCommas(totalIsk.toFixed(2))
							}else
								tIsk.innerHTML=response[1]
						if(response[2]!="ERROR")
							if(tSp.innerHTML){
								totalSp=Number(tSp.innerHTML.replace(",","").replace(",","").replace(",","").replace(",",""))
								addSp=Number(response[2].replace(",","").replace(",","").replace(",","").replace(",",""))
								totalSp=totalSp+addSp
								tSp.innerHTML=addCommas(totalSp)
							}else
								tSp.innerHTML=response[2]
					}
				},
				apiKey:"",
				keyName:"",
				id:0,
				
			}
		function loadXMLDoc(method,variables,callbacks){
			var xmlhttp;
			if (window.XMLHttpRequest)
			  {// code for IE7+, Firefox, Chrome, Opera, Safari
			  xmlhttp=new XMLHttpRequest();
			  }
			else
			  {// code for IE6, IE5
			  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			  }
			xmlhttp.onreadystatechange=function()
			  {
			  if (xmlhttp.readyState==4 && xmlhttp.status==200)
				{
					response=xmlhttp.responseText.split('<321>');
					callbacks(response)
				}
			  }
			
			if(method=='post'){
				xmlhttp.open(method,'ajax.php?t='+new Date().getTime(),true);
				xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
				xmlhttp.send(variables);
			}else{
				xmlhttp.open(method,'ajax.php?'+variables+'&t='+new Date().getTime(),true);
				xmlhttp.send();
			}
		}
		function checkKey(keyID,vCode){
			variables='checkKey=1&keyID='+keyID+'&vCode='+vCode;
			loadXMLDoc('post',variables,callback.checkKey);
		
		}
		function addKey(keyID,vCode){
			name=document.getElementById('keyName').value
			notes=document.getElementById('notes').value
			callback.id=keyID;
			variables='Save=1&keyID='+keyID+'&vCode='+vCode+"&name="+name+"&notes="+notes;
			loadXMLDoc('post',variables,callback.addKey);
		}
		function saveNotes(keyID){
			notes=document.getElementById('noteText').value
			callback.id=keyID;
			variables='Edit=1&keyID='+keyID+"&notes="+notes;
			loadXMLDoc('post',variables,callback.saveNotes);
		}
		function editName(keyID){
			name=document.getElementById('nameEdit'+keyID).value
			callback.id=keyID;
			variables='Edit=1&keyID='+keyID+"&name="+name;
			loadXMLDoc('post',variables,callback.editName);
		}
		function addFormKey(keyID,vCode){
			variables='Save=1&keyID='+keyID+'&vCode='+vCode+"&name="+name+"&notes="+notes;
			loadXMLDoc('post',variables,callback.addFormKey);
		}
		function removeKey(id){
			variables='Remove=1&keyID='+id;
			callback.id=id;
			loadXMLDoc('post',variables,callback.removeKey);
		}
		function hideShow(formId){
			if(document.getElementById(formId).style.display=='none'){
				document.getElementById(formId).style.display='block';
			}else{
				document.getElementById(formId).style.display='none';
			}
		}
		function removeRow(table,row){
			document.getElementById(table).deleteRow(document.getElementById(row).rowIndex);
			if(document.getElementById(table).rows.length==1){
				var row=document.getElementById(table).insertRow(-1)
				row.id="id0";
				var notes=row.insertCell(0);
				notes.colSpan=6;
				notes.innerHTML="No Keys Found";
			}
		}
		function loadingBar(){
			document.getElementById('info').style.display='inline';
			document.getElementById('info').innerHTML="Saving Key";
			clearInterval(window.interval);
			window.interval=setInterval(
			function(){
				if(document.getElementById('info').innerHTML==""||document.getElementById('info').innerHTML=="Saving Key..........")
					document.getElementById('info').innerHTML="Saving Key";
				else
					document.getElementById('info').innerHTML=document.getElementById('info').innerHTML+".";
			}		
			,750)
		}
		function getCharacterInfo(cID,uID,vCode){
			variables='Character=1&cID='+cID+'&uID='+uID+'&vCode='+vCode;
			loadXMLDoc('post',variables,callback.Character);
		}
		function urlEncode(inputString)                   
		{                   
		  var encodedInputString=escape(inputString);
		  encodedInputString=encodedInputString.replace("+", "%2B");
		  encodedInputString=encodedInputString.replace("/", "%2F");
		  return encodedInputString;
		}
		function limitText(limitField, limitNum) {
			if (limitField.value.length > limitNum) {
				limitField.value = limitField.value.substring(0, limitNum);
			}
		}
		function hide(id){
			document.getElementById(id).style.display='none';
		
		}
		function show(id){
			document.getElementById(id).style.display='block';
		}
		function addCommas(nStr)
		{
			nStr += '';
			x = nStr.split('.');
			x1 = x[0];
			x2 = x.length > 1 ? '.' + x[1] : '';
			var rgx = /(\d+)(\d{3})/;
			while (rgx.test(x1)) {
				x1 = x1.replace(rgx, '$1' + ',' + '$2');
			}
			return x1 + x2;
		}