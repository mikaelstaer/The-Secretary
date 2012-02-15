<?php
	// Hooks
	hook( "start", "themeSettings" );
	
	function themeSettings()
	{
		global $manager;
		
		if ( countHooks( "design-settings" ) == 0 )
		{
			hook( "big_message", "noDesignSettings" );
			return;
		}		
	}
	
	function noDesignSettings()
	{
		global $manager;
		
		$manager->message( 0, false, "There are no settings or options for the current theme!" ); 
	}
?>