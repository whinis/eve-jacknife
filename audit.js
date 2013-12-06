function toggle_visibility(id) {
 var e = document.getElementById(id);
 if(e.style.display == 'block')
  e.style.display = 'none';
 else
  e.style.display = 'block';
  
 return false;
}

function toggle_row_visibility(id) {
 var e = document.getElementById(id);
 if(e.style.display == 'table-row')
  e.style.display = 'none';
 else
  e.style.display = 'table-row';
  
 return false;
}
function show_div(name) {
	var e = document.getElementById(name+"Div");
	if (!e) return true;
	e.style.display = 'block';
	e = document.getElementById(name);
	if (!e) return true;
	e.style.display = 'block';
	
	return false;
}

function hide_div(name) {
	var e = document.getElementById(name+"Div");
	if (!e) return true;
	e.style.display = 'none';
	e = document.getElementById(name);
	if (!e) return true;
	e.style.display = 'none';
	
	return false;
}
function show_notes(id) {
	var e = document.getElementById("notesDiv");
	if (!e) return true;
	e.style.display = 'block';
	e = document.getElementById("notes");
	if (!e) return true;
	e.style.display = 'block';
	document.getElementById("noteText").innerHTML=document.getElementById("note"+id).innerHTML
	
	return false;
}

function hide_notes() {
	var e = document.getElementById("notesDiv");
	if (!e) return;
	e.style.display = 'none';
	e = document.getElementById("notes");
	if (!e) return;
	e.style.display = 'none';
}
function show_name(id) {
	var e = document.getElementById("editNameDiv");
	if (!e) return true;
	e.style.display = 'block';
	e = document.getElementById("editName");
	if (!e) return true;
	e.style.display = 'block';
	document.getElementById("noteText").innerHTML=document.getElementById("note"+id).innerHTML
	
	return false;
}

function hide_name() {
	var e = document.getElementById("editNameDiv");
	if (!e) return;
	e.style.display = 'none';
	e = document.getElementById("editName");
	if (!e) return;
	e.style.display = 'none';
}
function getScrollTop() {
 if (typeof window.pageYOffset !== 'undefined' ) 
	return window.pageYOffset;

 var d = document.documentElement;
 return (d.clientHeight) ? d.scrollTop : document.body.scrollTop;
}

function getWindowHeight() {
 if (typeof window.innerHeight !== 'undefined' ) 
	return window.innerHeight;

 return 0;
}
function watch_for_scroll() {
  window.onscroll = function() {
   var sTop = getScrollTop();
  	var e = document.getElementById("fade");
	if (e) 
		e.style.top = sTop + "px";
	e = document.getElementById("login");
	if (e) 
		e.style.top = (sTop + getWindowHeight() * .4) + "px";
  };
}
function update_skill_time(time){
	day="";
	hour="";
	min="";
	sec="";
	output="";
	D=new Date().getTime()/1000;
	trainingTime=time-D;
	days = Math.floor(trainingTime/(24*60*60));
	hours =Math.floor((trainingTime-(days*24*60*60))/(60*60));
	mins = Math.floor(((trainingTime-(hours*60*60))-(days*24*60*60))/60);
	secs = Math.floor(((trainingTime-(mins*60))-(hours*60*60))-(days*24*60*60));

	if (days ==1)  output += days +" day"; else
	if (days > 1)  output += days +" days";
	if (hours>0&&output!="") output +=", "
	if (hours ==1) output += hours +" hour"; else
	if (hours > 1) output += hours +" hours";
	if (mins>0&&output!="") output +=", "
	if (mins ==1)  output += mins +" minute"; else
	if (mins > 1)  output += mins +" minutes";
	if (secs>0&&output!="") output +=", "
	if (secs ==1)  output += secs +" second"; else
	if (secs > 1)  output += secs +" seconds";
	
	if(trainingTime<=0)
		document.getElementById("skilltime").innerHTML="Training Complete";

	document.getElementById("skilltime").innerHTML=output;
	var t=setTimeout("update_skill_time("+time+")",1000);
}
