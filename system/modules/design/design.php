<?php
	hook( "menu", "designMenu" );
	hook( "dashboard", "designDashboard", "", 4 );
	hook( "big_message", "themesWritable" );
	
	function themesWritable()
	{
		global $manager;

		$path= BASE_PATH . "site/themes/";
		if ( is_writable( $path ) == false )
			message( "warning", "Oh no! Your themes can't be edited properly: the theme folder is not writable.<br />The current path set to: <em>$path</em><br /><br />Double check that the permissions are correct." );
	}
	
	function designMenu( $menu )
	{
		$menu['design']= array(
				'sys_name'	=>	'design',
				'dis_name'	=>	'Design',
				'order'		=>	4,
				'url'		=>	'?cubicle=design-edit',
				'type'		=>	'',
				'hidden'	=>	'',
				'children'	=>	array( 
					array(
							'sys_name'	=>	'edit',
							'dis_name'	=>	'Themes'
							
					),
					array(
							'sys_name'	=>	'settings',
							'dis_name'	=>	'Theme Settings',
							'hidden'	=>	1
					)
				)
		);
		
		return $menu;
	}
	
	function designDashboard()
	{
		global $manager;
		
		$siteTheme= $manager->clerk->getSetting( "site_theme", 1 );
		
		$total= 0;
		$scan= scanFolder( BASE_PATH . "site/themes", 1 );
		foreach ( $scan as $themeFolder )
		{
			$total++;
			
			$folderName= str_replace( BASE_PATH . "site/themes/", "", $themeFolder );
			if ( $folderName == $siteTheme )
			{
				include_once $themeFolder . '/info.php';
				$currentTheme= $info['name'];
			}
		}
		
		$plural= ( $total != 1 ) ? "s" : "";
		
		$html= <<<HTML
			<div class="col-1">
				<h1>Design</h1>
				<div class="menu">
					<a href="?cubicle=design-edit">Manage Themes</a>
				</div>
				<ul class="stats">
					<li>{$total} beautiful theme{$plural} installed</li>
					<li>Your site is currently using <strong>{$currentTheme}</strong></li>
				</ul>
			</div>
HTML;
		
		echo $html;
	}
?>