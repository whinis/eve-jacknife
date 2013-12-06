<?php 
$registered_pages = array();
$enabled_pages = array();
$eligible_pages = array();

// pages default in order = first eligible page becomes the default

include("pages/auditorPage.base.php");
include("pages/auditorAboutPage.class.php");
include("pages/auditorSkillsPage.class.php");
include("pages/auditorKillsPage.class.php");
include("pages/auditorMailPage.class.php");
include("pages/auditorMembersPage.class.php");
include("pages/auditorNotificationsPage.class.php");
include("pages/auditorAssetsPage.class.php");
include("pages/auditorJournalPage.class.php");
include("pages/auditorTransactionsPage.class.php");
include("pages/auditorOrdersPage.class.php");
include("pages/auditorContractsPage.class.php");

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