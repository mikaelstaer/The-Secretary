<?php
	hook( "menu", "projectsMenu" );
	hook( "settings_menu", "projectsSettingsMenu" );
	hook( "dashboard", "projectsDashboard", "", 1 );
	hook( "big_message", "projectsWritable" );
	
	$allowed_file_types	=	array( '.jpg', '.jpeg', '.gif', '.png', '.mov', '.mpg', '.mpeg', '.wmv', '.avi', '.m4v', '.mp4', '.flv', '.swf', '.mp3', '.m4a', '.txt' );
	
	function projectsWritable()
	{
		global $manager;

		$path= $manager->clerk->getSetting( "projects_path", 1 );
		
		if ( is_writable( $path ) == false )
			message( "warning", "Oh no! The projects module is out of order: files cannot be uploaded because the folder is not writable.<br />The current path set to: <em>$path</em><br /><br />Double check that both the path and permissions are correct. You can update the path <a href=\"?cubicle=projects-settings\">here</a>." );
	}
	
	function projectsMenu( $menu )
	{
		$menu['projects']= array(
				'sys_name'	=>	'projects',
				'dis_name'	=>	'Projects',
				'order'		=>	1,
				'url'		=>	'?cubicle=projects-manage',
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
	
	function projectsSettingsMenu( $menu )
	{
		$menu[]= array(
			'sys_name'	=>	'projects',
			'dis_name'	=>	'Projects'
		);
		
		return $menu;
	}
	
	function projectsDashboard()
	{
		global $manager;
		
		$totalProjects= $manager->clerk->query_countRows( "projects" );
		$totalImages= $manager->clerk->query_countRows( "project_files", "WHERE type= 'image'" );
		$totalVideos= $manager->clerk->query_countRows( "project_files", "WHERE type= 'video'" );
		$totalAudio= $manager->clerk->query_countRows( "project_files", "WHERE type= 'audio'" );
		$totalSections= $manager->clerk->query_countRows( "project_sections" );
		$totalTags= $manager->clerk->query_numRows( $manager->clerk->query_select( "projects_to_tags", "DISTINCT tag" ) );
		
		$projectS= ( $totalProjects > 1 ) ? "s" : "";
		$imageS= ( $totalImages > 1 ) ? "s" : "";
		$sectionS= ( $totalSections > 1 ) ? "s" : "";
		$tagS= ( $totalTags != 1 ) ? "s" : "";
		
		$html= <<<HTML
			<div class="col-1">
				<h1>Projects</h1>
				<div class="menu">
					<a href="?cubicle=projects-manage">Manage</a> / <a href="?cubicle=settings-projects">Settings</a>
				</div>
				<ul class="stats">
					<li>{$totalProjects} project{$projectS}</li>
					<li>{$totalImages} image{$imageS}, {$totalVideos} video and {$totalAudio} audio files</li>
					<li>{$totalSections} section{$sectionS}</li>
					<li>{$totalTags} tag{$tagS}</li>
				</ul>
			</div>
HTML;
		
		echo $html;
	}
?>