<?php
	ob_start();
	
	// Include assistants
	include_once "system/assistants/config.inc.php";
	include_once "system/assistants/clerk.php";
	include_once "system/assistants/guard.php";
	include_once "system/assistants/receptionist.php";
	include_once "system/assistants/office.php";
	include_once "system/assistants/manager.php";

	$manager= new Manager();
	$manager->office->init();
	
	setcookie("secretary_username", "$username", time()-($manager->clerk->config("COOKIE_TIME")), $manager->clerk->config("COOKIE_PATH") );
 	setcookie("secretary_password", "$password_encrypted", time()-($manager->clerk->config("COOKIE_TIME")), $manager->clerk->config("COOKIE_PATH"));

	header( "Location: login.php" );
	
	ob_end_flush();
?>