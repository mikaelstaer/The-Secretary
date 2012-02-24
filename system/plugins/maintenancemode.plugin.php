<?php
	hook( "site_init", "maintenanceMode" );
	hook( "settings-general", "maintenanceDelegates" );
	
	
	function maintenanceDelegates()
	{
		global $manager;
		
		hook( "prefsSiteSettings", "maintenanceForm" );
		hook( "form_process", "processMaintenance" );
	}
	
	function processMaintenance()
	{
		global $manager;
		
		$mMode= $_POST['maintenanceMode'];
		
		$manager->clerk->updateSetting( "maintenanceMode", array( $mMode, "", "" ) );
	}
	
	function maintenanceForm()
	{
		global $manager;
		
		$mMode= ( $manager->form->submitted() ) ? $_POST['maintenanceMode'] : $manager->clerk->getSetting( "maintenanceMode", 1 );
		$manager->form->add_input( "checkbox", "maintenanceMode", " ", $mMode, array( "Turn on Maintenance Mode" => 1 ) );
	}
	
	function maintenanceMode()
	{
		global $clerk, $layout;
		
		$username= $_COOKIE["secretary_username"];
		$password= $_COOKIE["secretary_password"];

		$user= $clerk->query_select( "users", "", "WHERE username= '$username' AND password= '$password'" );
		
		if ( $clerk->getSetting( "maintenanceMode", 1 ) == 1  && $clerk->query_numRows( $user ) == 0 )
		{
			// User is not logged in - show maintenance page
			$layout= "maintenance";
		}
	}
?>