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

foreach ($registered_pages as $name => $page) {
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
}
//reorder where skills page is
if(isset($eligible_pages["skills"])) {
    $page = $eligible_pages["skills"];
    unset($eligible_pages[$page->GetName()]);
    $eligible_pages = [$page->GetName() => $page] + $eligible_pages;
    if (isset($enabled_pages[$page->GetName()])) {
        unset($enabled_pages[$page->GetName()]);
        $enabled_pages = [$page->GetName() => $page] + $enabled_pages;
    }
}
if (count($enabled_pages) > 1) {
	$eligible_pages["onepage"] = "onepage";
	$enabled_pages["onepage"] = "onepage";
}
	
 ?>