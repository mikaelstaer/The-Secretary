<?php
	hook( "menu", "homeMenu" );
	hook( "big_message", "cacheWritable" );
	
	function cacheWritable()
	{
		global $manager;
		
		$path= $manager->clerk->getSetting( "cache_path", 1 );
		
		if ( is_writable( $path ) == false )
			message( "warning", "Oh no! The cache folder is out of order! Files cannot be uploaded because the folder is not writable.<br />The current path set to: <em>$path</em><br /><br />Double check that both the path and permissions are correct. You can update the path <a href=\"?cubicle=settings-general\">here</a>." );
	}
	
	if ( $manager->office->cubicle("REQUEST") == "home" )
	{
		define_anchor( "dashboard" );
		hook( "before_form", "home");
	}
	
	function homeMenu( $menu )
	{
		$menu['home']= array(
				'sys_name'	=>	'home',
				'dis_name'	=>	'Dashboard',
				'order'		=>	0,
				'url'		=>	'',
				'type'		=>	'',
				'hidden'	=>	'',
				'children'	=>	array( 
					array(
							'sys_name'	=>	'back',
							'dis_name'	=>	'Back to Dashboard',
							'url'		=>	'?cubicle=home'
					),
					array(
							'sys_name'	=>	'about',
							'dis_name'	=>	'About',
							'hidden'	=>	1
					)
					
				)
		);
		
		return $menu;
	}
	
	function home()
	{
		global $manager;
		
		echo '<div id="dashboard">';
		call_anchor( "dashboard" );
		echo '</div>';
	}
?>