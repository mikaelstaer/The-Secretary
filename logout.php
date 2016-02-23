<?php
	ob_start();
	//added session - for security
	session_start();
	// Include assistants
	include_once "system/assistants/config.inc.php";
	include_once "system/assistants/clerk.php";
	include_once "system/assistants/guard.php";
	include_once "system/assistants/receptionist.php";
	include_once "system/assistants/office.php";
	include_once "system/assistants/manager.php";
	//destroy session
	session_destroy();
	$manager= new Manager();
	$manager->office->init();
	
	/*removed cookies*/

	header( "Location: login.php" );
	
	ob_end_flush();
?>
