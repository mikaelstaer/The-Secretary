<?php	
	hook( "menu", "settingsMenu" );
	hook( "settings", "getsetting" );
	
	function getsetting()
	{
		global $manager;
		
		$module= $manager->office->cubicle( "BRANCH" );
		
		$request= explode( "-", $manager->office->cubicle( "REQUEST" ) );
		$page= SYSTEM . "modules/" . $request[1] . "/" . $request[1] . "_settings.php";
		
		if ( file_exists( $page ) )
			include_once $page;
	}
	
	function settingsMenu( $menu )
	{	
		global $totalModules;
		
		$children= array(
			array(
				'sys_name'	=>	'general',
				'dis_name'	=>	'General Settings',
				'url'		=>	'',
				'type'		=>	'',
				'hidden'	=>	''
			),
		);
		
		$children= call_anchor( "settings_menu", $children );
		
		$menu['settings']= array(
			'sys_name'	=>	'settings',
			'dis_name'	=>	'Settings',
			'order'		=>	$totalModules,
			'url'		=>	'?cubicle=settings-general',
			'type'		=>	'',
			'hidden'	=>	'',
			'children'	=>	$children
		);
		
		return $menu;
	}
	
	function settingsDashboard()
	{
		global $manager;

		$site		=	$manager->clerk->getSetting( "site" );
		$siteName	= 	$site['data1'];
		$siteUrl	= 	str_replace( 'http://', '', $site['data2'] );

		$html= <<<HTML
			<div class="col-1">
				<h1>Settings</h1>
				<div class="menu">
					<a href="?cubicle=settings-general">Site Settings &amp; Preferences</a>
				</div>
				<ul class="stats">
					<li>Site Title: <strong>{$siteName}</strong></li>
					<li>URL: <strong><a href="{$site['data2']}">{$siteUrl}</a></strong></li>
				</ul>
			</div>
HTML;

		echo $html;
	}
?>