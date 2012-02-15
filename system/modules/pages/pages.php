<?php
	hook( "menu", "pagesMenu" );
	hook( "settings_menu", "pagesSettingsMenu" );
	hook( "dashboard", "pagesDashboard", "", 3 );

	function pagesMenu( $menu )
	{
		$menu['pages']= array(
				'sys_name'	=>	'pages',
				'dis_name'	=>	'Pages',
				'order'		=>	3,
				'url'		=>	'?cubicle=pages-manage',
				'type'		=>	'',
				'hidden'	=>	'',
				'children'	=>	array( 
					array(
							'sys_name'	=>	'manage',
							'dis_name'	=>	'Manage'
							
					)
				)
		);
		
		return $menu;
	}
	
	function pagesSettingsMenu( $menu )
	{
		$menu[]= array(
				'sys_name'	=>	'pages',
				'dis_name'	=>	'Pages'
		);
		
		return $menu;
	}
	
	function pagesDashboard()
	{
		global $manager;
		
		$totalPages	=	$manager->clerk->query_countRows( "pages" );
		$indexPage	=	$manager->clerk->getSetting( "index_page", 1 );
		$getPage	=	$manager->clerk->query_fetchArray( $manager->clerk->query_select( "pages", "", "WHERE slug= '$indexPage' OR id= '$indexPage'" ) );
		$indexPage	=	$getPage['name'];
		$pageS		= 	( $totalPages > 1 ) ? "s" : "";
		
		$html= <<<HTML
			<div class="col-1">
				<h1>Pages</h1>
				<div class="menu">
					<a href="?cubicle=pages-manage">Manage</a> / <a href="?cubicle=settings-pages">Settings</a>
				</div>
				<ul class="stats">
					<li>{$totalPages} page{$pageS}</li>
					<li><strong>{$indexPage}</strong> is the home page</li>
				</ul>
			</div>
HTML;
		
		echo $html;
	}
?>