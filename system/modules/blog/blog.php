<?php
	hook( "menu", "blogMenu" );
	hook( "settings_menu", "blogSettingsMenu" );
	hook( "dashboard", "blogDashboard", "", 2 );
	hook( "start", "blogInstall" );
	hook( "big_message", "blogWritable" );
	
	$blogFileTypes	=	array( '.jpg', '.jpeg', '.gif', '.png' );
	
	function blogWritable()
	{
		global $manager;
		
		$path= $manager->clerk->getSetting( "blog_path", 1 );
		
		if ( is_writable( $path ) == false )
			message( "warning", "Oh no! The blog is slightly out of order: files cannot be uploaded because the folder is not writable.<br />The current path set to: <em>$path</em><br /><br />Double check that both the path and permissions are correct. You can update the path <a href=\"?cubicle=blog-settings\">here</a>." );
	}
	
	function blogMenu( $menu )
	{
		$menu['blog']= array(
				'sys_name'	=>	'blog',
				'dis_name'	=>	'Blog',
				'order'		=>	2,
				'url'		=>	'?cubicle=blog-manage',
				'type'		=>	'content',
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
	
	function blogSettingsMenu( $menu )
	{
		$menu[]= array(
				'sys_name'	=>	'blog',
				'dis_name'	=>	'Blog'
		);
		
		return $menu;
	}
	
	function blogDashboard()
	{
		global $manager;
		
		$total= $manager->clerk->query_countRows( "secretary_blog" );
		
		$plural= ( $total > 1 || $total == 0 ) ? "s" : "";
		
		$html= <<<HTML
			<div class="col-1">
				<h1>Blog</h1>
				<div class="menu">
					<a href="?cubicle=blog-manage">Manage</a> / <a href="?cubicle=blog-settings">Settings</a>
				</div>
				<ul class="stats">
					<li>{$total} post{$plural}</li>
				</ul>
			</div>
HTML;
		
		echo $html;
	}
?>