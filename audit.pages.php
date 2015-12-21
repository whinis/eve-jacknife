<?php 
$registered_pages = array();
$enabled_pages = array();
$eligible_pages = array();

// pages default in order = first eligible page becomes the default

//Auto load the eveApi pages
$files=scandir("./pages/");
$includes=array();
foreach ($files as $file){
	if(pathinfo($file, PATHINFO_EXTENSION)=="php"){
		include_once("./pages/".pathinfo($file, PATHINFO_BASENAME));
	}
}

foreach ($registered_pages as $name => $page) 
	if ((CORP_MODE && $page->GetAccMode() != ACC_CHAR_ONLY) || (!CORP_MODE && $page->GetAccMode() != ACC_CORP_ONLY)) {
		$eligible_pages[$page->GetName()] = $page;		
		$mask = $page->GetAccMask(CORP_MODE);
		if (is_array($mask)) {
			foreach ($mask as $submask) 
				if (canAccess($submask)) {
					$enabled_pages[$page->GetName()] = $page;
					break;
				}
		} else if (canAccess($mask)) 
			$enabled_pages[$page->GetName()] = $page;
	}

if (count($enabled_pages) > 1) {
	$eligible_pages["onepage"] = "onepage";
	$enabled_pages["onepage"] = "onepage";
}
	
 ?>