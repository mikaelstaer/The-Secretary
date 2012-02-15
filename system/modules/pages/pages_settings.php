<?php
	// Define anchors
	define_anchor( "pagesSettings" );
	define_anchor( "pageSettingsForm");
	define_anchor( "processPageSettingsForm" );
		
	// Define hooks
	hook( "form_main", "pagesSettingsForm" );
	hook( "form_process", "processPagesSettingsForm" );
	hook( "form_submit_primary", "submitButtons" );
	
	// Functions
	function submitButtons()
	{
		global $manager;
		
		$manager->form->add_input( 'submit', 'submit', 'Save', 'save' );
	}
	
	function processPagesSettingsForm()
	{
		global $manager;
		
		$index_page= $_POST['index_page'];
		
		if ( $manager->clerk->updateSetting( "index_page", array( $index_page ) ) )
		{
			$manager->message( 1, false, "Settings updated!" );
		}
		else
		{
			$manager->message( 0, true, "Settings could not be updated!" );
		}
		
		call_anchor( "processPageSettingsForm" );
	}
	
	function pagesSettingsForm()
	{
		global $manager;
		
		// Variables
		$currentIndex= $manager->clerk->getSetting( "index_page", 1 );
		$pages= array( "None" => "0" );
		
		$getPages= $manager->clerk->query_select( "pages", "", "ORDER BY pos ASC" );
		while ( $page= $manager->clerk->query_fetchArray( $getPages ) )
		{
			$pages[$page['name']]= $page['id'];
		}
		
		// Begin form
		$manager->form->add_fieldset( "General Settings", "generalSettings" );
		$manager->form->add_select( "index_page", "Site Index/Home Page", $pages, $currentIndex );
		$manager->form->close_fieldset();
		
		call_anchor( "pageSettingsForm" );
	}
?>