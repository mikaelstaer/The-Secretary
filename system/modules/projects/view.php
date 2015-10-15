<?php
	// Variables
	$project= "";
	$paths= $clerk->getSetting( "projects_path" );
	define( "PROJECTS_PATH", $paths['data1'] );
	define( "PROJECTS_URL", $paths['data2'] );
	
	// Anchors
	define_anchor( "textblockModify" );
	define_anchor( "projectsRssDescription" );
	
	// Hooks
	hook( "uri_router", "projects_routes" );
	hook( "site_init", "catch_selected_project" );
	
	function projects_routes( $routes )
	{
		$projects= getRemappedVar( 'projects' );
		
		$routes[$projects . '/tags/([a-zA-Z0-9\-_]+)']= 'project_tags=$1';	// domain.com/projects/tags/tag
		$routes[$projects . '/([a-zA-Z0-9\-_]+)']= $projects . '=$1';		// domain.com/projects/project-slug
		
		return $routes;
	}
	
	function catch_selected_project()
	{
		if ( projectSelected() == true )
		{
			hook( "site_begin", "projectDisplayers" );
		}
		else
			hook( "site_begin", "dirtiness");
	}
	
	function projects( $options= "" )
	{
		global $clerk, $project;
		
		if ( !is_array( $options ) )
		{
			$options= prepare_settings( $options );
		}
		
		// Defaults
		$defaults	= array(
				'id'			=>	'',
				'template'		=>	'',
				'show'			=>	'small',
				'tags'			=>	'',
				'section'		=>	'',
				'sticky'		=>	'true',
				'order'			=>	'pos',
				'orderHow'		=>	'asc',
				'limit'			=>	''
		);
		
		$options= merge_settings( $options, $defaults );
		$project_count= 0;
		
		if ( !empty( $options['func'] ) )
		{
			if ( is_callable( $options['func'] ) )
			{
				ob_start();
				call_user_func( $options['func'], $options );
				$contents= ob_get_contents();
				ob_end_clean();

				return $contents;
			}
		}
		
		if ( !empty( $_GET[getRemappedVar("projects")] ) && $options['sticky'] == "false" )
		{
			return;
		}
		
		$options['orderHow']= strtoupper( $options['orderHow'] );

		// Handle tags option
		if ( !empty( $_GET['project_tags'] ) ) $options['tags']= $_GET['project_tags'];
		
		if ( !empty( $options['tags'] ) )
		{
			$options['show']= "small";
			
			$tags= explode( ",", str_replace( ", ", ",", $options['tags'] ) );
			
			// First, collect the category IDs...
			$total= count( $tags );
			$count= 0;
			
			foreach ( $tags as $t )
			{
				$tag= $clerk->complex_name( $t );
				$where.= ( $count == 0 ) ? "tag= '$tag'" : " OR tag= '$tag'";
				$count++;
			}
			
			$where= ( $count > 0 ) ? "WHERE " . $where : "";
			
			$getTags= $clerk->query_select( "projects_to_tags", "", $where );

			// And then construct the join...
			$count= 0;
			$where= "";

			while ( $tag= $clerk->query_fetchArray( $getTags ) )
			{
				$where.= ( $count == 0 ) ? "tag= '" . $tag['tag'] . "'" : " OR tag= '" . $tag['tag'] . "'";
				$count++;
			}
			
			$from= "projects_to_tags join projects on projects_to_tags.projectid= projects.id";
			
			if ( empty( $where ) ) $from= "projects";
		}
		else
		{
			$from= "projects";
			
			if ( !empty( $options['id'] ) )
			{
				$id= $options['id'];
				$where= "id= '$id' OR slug= '$id' OR title= '$id'";
			}
		}
		
		if ( empty( $options['template'] ) )
		{
			if ( $options['show'] == "big" )
			{
				$options['template']= "projects_view.html";
			}
			else
			{
				$options['template']= "projects_list.html";
			}
			
			if ( $options['sticky'] == "true" )
			{
				$options['template']= ( $options['show'] == "small" ) ? "projects_list.html" : "projects_view.html";
			}
		}
		
		if ( file_exists( HQ . "site/themes/" . THEME . "/templates/" . $options['template'] ) == false )
		{
			return "Oops! Looks like your theme is missing the template file, <em>" . $options['template'] . "</em><br /><br />Create this file and upload it to the \"templates\" folder in your theme's folder. Don't forget to fill it with template tags and your custom HTML!";
		}
		
		// Handle section
		if ( !empty( $options['section'] ) && empty( $_GET['project_tags'] ) )
		{
			if ( !empty( $options['tags'] ) )
			{
				$where.= " AND ";
			}
			
			$sec= $options['section'];
			$section= $clerk->query_fetchArray( $clerk->query_select( "project_sections", "", "WHERE name= '$sec' OR slug= '$sec' OR id='$sec'" ) );

			$where.= "section= '" . $section['id'] . "'";
		}
		
		// Get sections, foreach section get projects where section = $section, loop out like normal
		// (to preserve section AND project ordering)
		
	 	$order= ( $options['order'] == "random" ) ? "ORDER BY rand()" : "ORDER BY section, " . $options['order'] . " " . $options['orderHow'];
		$limit= ( empty( $options['limit'] ) ) ? "" : "LIMIT " . $options['limit'];
		
		// Pagination
		if ( $options['pagination'] == "true" )
		{
			$page= ( !isset( $_GET['p'] ) || !is_numeric( $_GET['p']) ) ? 1 : (int) $_GET['p'];
			$offset= ( $page - 1 ) * $options['limit'];
			$limit= 'LIMIT ' . $offset . ', ' . $options['limit'];
		}
		
		// Spit it out!		
		ob_start();
		
		if ( !empty( $_GET['project_tags'] ) )
		{
			echo '<div class="tags">Searching Tags: ' . currentProjectTags() . '</div>';
		}
		
		if ( empty( $options['id'] ) && projectSelected() )
		{
			$where= ( empty( $where ) ) ? "WHERE publish= 1" : "WHERE (" . $where . ") AND publish= 1";
			$projects= array();
			
			$getProjects= $clerk->query_select( "$from", "", "$where $order $limit" );
			while ( $p= $clerk->query_fetchArray( $getProjects ) )
			{
				$projects[$p['id']]= $p;
			}
			
			$getSections= $clerk->query_select( "project_sections", "", "ORDER BY pos ASC" );
			while ( $section= $clerk->query_fetchArray( $getSections ) )
			{
				foreach ( $projects as $id => $data )
				{
					if ( $data['section'] == $section['id'] )
					{
						$project= $projects[$id];
						
						include HQ . "site/themes/" . THEME . "/templates/" . $options['template'];
						unset( $projects[$id] );
					}
				}
			}
		}
		elseif ( !empty( $options['id'] ) && projectSelected() )
		{
			$where= ( empty( $where ) ) ? "" : "WHERE (" . $where . ")";
			
			$options['template']= "projects_view.html";
			
			$get= $clerk->query_select( "$from", "", "$where $order $limit" );
			while ( $project= $clerk->query_fetchArray( $get ) )
			{
				include HQ . "site/themes/" . THEME . "/templates/" . $options['template'];
			}
		}
		else
		{
			$where= ( empty( $where ) ) ? "WHERE publish= 1" : "WHERE " . $where . " AND publish= 1";
			
			$projects= array();
			
			$getProjects= $clerk->query_select( "$from", "", "$where $order $limit" );
			while ( $p= $clerk->query_fetchArray( $getProjects ) )
			{
				$projects[$p['id']]= $p;
			}
			
			$getSections= $clerk->query_select( "project_sections", "", "ORDER BY pos ASC" );
			while ( $section= $clerk->query_fetchArray( $getSections ) )
			{
				foreach ( $projects as $id => $data )
				{
					if ( $data['section'] == $section['id'] )
					{
						$project= $projects[$id];
						if ( pageSelected() == true )
						{
							projectDisplayers( $id );
						}
						
						include HQ . "site/themes/" . THEME . "/templates/" . $options['template'];
						unset( $projects[$id] );
					}
				}
			}
		}
		
		$contents= ob_get_contents();
		ob_end_clean();
		
		return $contents;
	}
	
	function dirtiness()
	{
		$pageInfo= pageInfo( PAGE );
		$settings= prepare_settings( $pageInfo['content_options'] );
		
		if ( $pageInfo['content_type'] == "projects" && $settings['show'] == "big" )
		{
			projects( $settings );
		}
	}
	
	function determineProjectId( $selected= "" )
	{
		global $project;
		
		if ( !empty( $_GET['id'] ) && empty( $selected ) )
		{
			$id= $_GET['id'];
		}
		elseif ( !empty( $selected ) )
		{
			$id= $selected;
		}
		elseif ( empty( $_GET[getRemappedVar("projects")] ) )
		{
			$id= $project['id'];
		}
		else
		{
			$id= $_GET[getRemappedVar("projects")];
		}
		
		return $id;
	}
	
	function getGroupInfo( $string )
	{
		$groupNum	=	preg_replace( '/(group)([0-9]+)(:)*([a-zA-Z0-9-_]+)?/', '$2', $string );
		$groupType	=	preg_replace( '/(group)([0-9]+)(:)*([a-zA-Z0-9-_]+)?/', '$4', $string );
		
		return array(
					"num"	=>	$groupNum,
					"type"	=>	$groupType
				);
	}
	
	function displayersIncludes( $files )
	{
		foreach ( $files as $file => $data )
		{
			switch ( $data['type'] )
			{
				case "image":
						// Do nothing
						break;
				case "video":
						include_once HQ . "site/media/media-player/mediaplayer.php";
						break;
				case "audio":
						include_once HQ . "site/media/audio-player/audioplayer.php";
						break;
				default:
						// Do nothing
						break;
			}
		}
	}
	
	function projectDisplayers( $id= "" )
	{
		global $clerk, $project;
		
		$id= determineProjectId($id);
		
		if ( empty( $id ) ) return;
		
		$project	=	$clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '$id' OR slug= '$id'" ) );
		$projectId	= 	$project['id'];
		$flow		= 	explode( ",", $project['flow'] );
		
		$files= $clerk->query_select( "project_files", "", "WHERE project_id= '$projectId' ORDER BY pos ASC" );
		while ( $file= $clerk->query_fetchArray( $files ) )
		{
			$thefiles[$file['id']]= $file;
		}
		
		foreach ( $flow as $part )
		{	
			if ( !strstr( $part, "group" ) )
			{
				continue;
			}
			else
			{
				displayersIncludes( $thefiles );
				$group= getGroupInfo( $part );
				$file= SYSTEM . "plugins/displayers/" . $group['type'] . "/" . str_replace( '-', '_', $group['type'] ) . ".plugin.php";
				
				if ( file_exists( $file ) )
					include_once $file;
			}
		}
	}
	
	function projectContent( $proj= "" )
	{
		global $clerk, $project;
		
		$id= determineProjectId( $proj );
		if ( empty( $id ) ) return;
		
		$project= $clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '$id' OR slug= '$id'" ) );
		$flow= explode( ",", $project['flow'] );
		
		$id= $project['id'];
		$files= $clerk->query_select( "project_files", "", "WHERE project_id= '$id' ORDER BY pos ASC" );
		while ( $file= $clerk->query_fetchArray( $files ) )
		{
			$thefiles[$file['id']]= $file;
		}
		
		foreach ( $flow as $part )
		{
			if ( strstr( $part, "textblock") )
			{
				$textBlockID= str_replace( "textblock", "", $part );
				$textblock= call_anchor( "textblockModify", array(
											'original'	=>	$thefiles[$textBlockID]['caption'],
											'modified'	=>	textOutput( $thefiles[$textBlockID]['caption'] )
				) );
				
				$print_block= '<div class="textblock">' . $textblock['modified'] . '</div>';
				$print_block= call_anchor( "textblock_modify_html", $print_block );
				echo $print_block;
			}
			elseif ( strstr( $part, "group" ) )
			{
				$group		=	getGroupInfo( $part );
				$typeFunc	= 	str_replace( "-", "_", $group['type'] );
				
				echo '<div class="fileGroup ' . $group['type'] . '" id="' . $project['slug'] . '-' . $group['num'] . '">';
				
				if ( is_callable( $typeFunc ) )
					echo call_user_func_array( $typeFunc, array( $project, $thefiles, $group ) );
				
				echo '</div>';
			}
			else
			{
				call_anchor( "project_content_flow", array( "part" => $part ) );
			}
		}
	}
	
	function projectView()
	{
		if ( projectSelected() == false )
		{
			return;
		}
		else
		{
			$settings= prepare_settings( "show= big, id= ". selectedProject() );
			echo projects( $settings );
		}
	}
	
	function projectTitle()
	{
		global $project, $clerk;
		
		return $project['title'];
	}
	
	function projectSlug()
	{
		global $project;
		
		return $project['slug'];
	}
	
	function projectId()
	{
		global $project;
		
		return $project['id'];
	}
	
	function selectedProject()
	{
		if ( !empty( $_GET['id'] ) )
			return $_GET['id'];
			
		if ( !empty( $_GET[getRemappedVar("projects")] ) )
			return $_GET[getRemappedVar("projects")];
	}
	
	function projectSelected()
	{
		return ( !empty( $_GET[getRemappedVar("projects")] ) || !empty( $_GET['id'] ) );
	}
	
	function projectLink( $project_id= "" )
	{
		return linkToProject( $project_id );
	}
	
	function linkToProject( $projectId= "" )
	{
		global $clerk, $project;
		
		$cleanUrls= (bool) setting( "clean_urls", 1 );
		
		if ( !empty( $projectId ) )
		{
			$projectInfo= projectInfo( $projectId );
			$slug= $projectInfo['slug'];
		}
		else
		{
			$slug= projectSlug();
		}
		
		$page_info= pageInfo( PAGE );
		
		if ( $page_info['content_type'] != "projects" )
		{
			return ( $cleanUrls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . getRemappedVar( "projects" ) . '/' . $slug : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "projects" ) . '=' . $slug;
		}
		elseif ( pageSelected() )
		{
			return ( $cleanUrls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . PAGE . '/' . $slug : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "pages" ) . '=' . PAGE . '&amp;' . getRemappedVar( "id" ) . '=' . $slug;
		}
		else
		{
			return ( $cleanUrls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . getRemappedVar( "projects" ) . '/' . $slug : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "pages" ) . '=' . PAGE . '&amp;' . getRemappedVar( "projects" ) . '=' . $slug;
		}
	}
	
	function projectInfo( $projectId= "" )
	{
		global $clerk, $project;
		
		return ( empty( $projectId ) ) ? $project : $clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '$projectId' OR slug= '$projectId'" ) );
	}
	
	function projectList( $template= "" )
	{
		global $clerk, $project;
		
		$hideSections= (boolean) $clerk->getSetting( "projects_hideSections", 1 );
		
		$order= "pos, section";
		$orderHow= "ASC";
		
		$getProjects= $clerk->query_select( "projects", "", "WHERE publish= 1 ORDER BY $order $orderHow" );
		
		ob_start();
		
		while ( $proj= $clerk->query_fetchArray( $getProjects ) )
		{
			$projects[$proj['id']]= $proj;
		}
		
		if ( $hideSections == true )
		{
			echo '<ul id="projects">';
		}
		else
		{
			echo '<ul id="projects"><li>';
		}
		
		$getSections= $clerk->query_select( "project_sections", "", "ORDER BY pos ASC" );
		while ( $section= $clerk->query_fetchArray( $getSections ) )
		{
			
			if ( $hideSections == false )
			{
				echo '
					<ul id="' . $section['slug'] . '">
						<li class="sectionTitle">
							' . $section['name'] . '
						</li>
				';
			}
			
			foreach ( $projects as $id => $data )
			{
				if ( $data['section'] == $section['id'] )
				{
					$project= $projects[$id];
					$activeClass= ( selectedProject() == projectSlug() ) ? ' active"' : '';
					$link= ( $clerk->getSetting( "clean_urls", 1 ) == true ) ? linkToSite() . "/" . getRemappedVar( "projects" ) . "/" . $project['slug'] : "?" . getRemappedVar( "projects" ) . "=" . $project['slug'];
					
					if ( empty( $template ) )
					{
						echo '
							<li class="project' . $activeClass . '">
								<a href="' . $link . '">' . projectTitle() . '</a>
							</li>';
					}
					else
					{
							include HQ . 'site/themes/' . THEME . '/templates/' . $template;
					}
						
					// unset( $projects[$id] );
				}
			}
			
			if ( $hideSections == false )
			{
				echo '
					</ul>
				';
			}
		}
		
		if ( $hideSections == true )
		{
			echo '</ul>';
		}
		else
		{
			echo '</li></ul>';
		}
		
		$contents = ob_get_contents();
        ob_end_clean();
        
		return $contents;
	}
	
	function projectThumbnail( $project_id= "" )
	{
		global $clerk, $project;
		
		$proj= ( empty( $project_id ) == false ) ? projectInfo( $project_id ) : $project;

		if ( empty( $proj['thumbnail'] ) ) {
			return '';

		} else {
			if ( $clerk->getSetting( "resizeProjThumb", 1 ) == 0 )
			{
				list( $width, $height )= getimagesize( PROJECTS_PATH . $proj['slug'] . "/" . $proj['thumbnail'] );
				return '<img src="' . PROJECTS_URL . $proj['slug'] . "/" . $proj['thumbnail'] . '" width="' . $width . '" height="' . $height . '" alt="" />';
			}
			else
			{
				$thumbnail= $proj['thumbnail'];
				$width= $clerk->getSetting( "projects_thumbnail", 1 );
				$height= $clerk->getSetting( "projects_thumbnail", 2 );
				$intelliScaling= $clerk->getSetting( "projects_thumbnailIntelliScaling", 1 );
				$location= PROJECTS_PATH . $proj['slug'] . "/";
				
				return dynamicThumbnail( $thumbnail, $location, $width, $height, $intelliScaling );
			}
		}
	}
	
	function getProjectFiles( $id= "" )
	{
		global $clerk;
		
		$projectId= determineProjectId( $id );
		$theFiles= array();
		$orderedFiles= array();
		
		$project= $clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '$projectId' OR slug= '$projectId'" ) );
		$flow= explode( ",", $project['flow'] );
		
		$files= $clerk->query_select( "project_files", "", "WHERE project_id= '$projectId' ORDER BY pos ASC" );
		while ( $file= $clerk->query_fetchArray( $files ) )
		{
			$theFiles[$file['id']]= $file;
		}
		
		foreach ( $flow as $part )
		{
			if ( strstr( $part, "textblock") )
			{
				$textBlockID= str_replace( "textblock", "", $part );
				foreach ( $theFiles as $file => $data )
				{
					if ( $data['id'] == $textBlockID )
					{
						$orderedFiles[$data['id']]= $data;
					}
				}
			}
			
			if ( strstr( $part, "group" ) )
			{
				$group=	getGroupInfo( $part );
				foreach ( $theFiles as $file => $data )
				{
					if ( $data['filegroup'] == $group['num'] )
					{
						$orderedFiles[$data['id']]= $data;
					}
				}
			}
		}
		
		return $orderedFiles;
	}
	
	function tagSlug( $tag )
	{
		$quick_search	= 	array( " " );
		$quick_replace	=	array( "-" );

		return str_replace( $quick_search, $quick_replace, $tag );
	}
	
	function projectTagLink( $tag )
	{
		return linkToProjectTag( $tag );
	}
	
	function linkToProjectTag( $tag )
	{
		global $clerk;
		
		$tag= tagSlug( $tag );
		
		return ( $clerk->getSetting( "clean_urls", 1 ) == true ) ? $clerk->getSetting( "site", 2 ) . '/' . getRemappedVar( "projects" ) . '/tags/' . $tag : '?project_tags=' . $tag;
	}
	
	function projectHasTags()
	{
		global $clerk, $project;
		
		$projectid	=	$project['id'];
		
		$getTags= $clerk->query_select( "projects_to_tags", "DISTINCT tag", "WHERE projectid= '$projectid'" );
		
		return ( $clerk->query_numRows( $getTags ) > 0 );
	}
	
	// Displays the tags for the selected project
	// ie. www.domain.com/projects/secretary displays tags for project "Secretary"
	function projectTags()
	{
		global $clerk, $project;
		
		$projectid	=	$project['id'];
		$tags		= 	array();
		$cleanUrls	=	(bool) $clerk->getSetting( "clean_urls", 1 );
		
		$getTags= $clerk->query_select( "projects_to_tags", "DISTINCT tag", "WHERE projectid= '$projectid' ORDER BY id ASC" );
		while ( $tag= $clerk->query_fetchArray( $getTags ) )
		{
			$tags[]= '<a href="' . linkToProjectTag( $tag['tag'] ) . '">' . $tag['tag'] . '</a>';
		}
		
		return implode( ', ', $tags );
	}
	
	function projectTagsArray()
	{
		global $clerk, $project;
		
		$projectid	=	$project['id'];
		$tags		= 	array();
		$cleanUrls	=	(bool) $clerk->getSetting( "clean_urls", 1 );
		
		$getTags= $clerk->query_select( "projects_to_tags", "DISTINCT tag", "WHERE projectid= '$projectid' ORDER BY id ASC" );
		while ( $tag= $clerk->query_fetchArray( $getTags ) )
		{
			$tags[]= $clerk->simple_name( $tag['tag'] );
		}
		
		return $tags;
	}
	
	// Displays the tags currently selected
	// ie. www.domain.com/projects/tags/interface-design,web-design
	function currentProjectTags()
	{
		global $clerk;

		$selectedTags	= 	explode( ",", $_GET['project_tags'] );
		$tags			=	array();
		
		foreach ( $selectedTags as $tag )
		{
			$tags[]= '<a href="' . linkToProjectTag( $tag ) . '">' . $clerk->complex_name( $tag ) . '</a>';
		}
		
		return implode( ', ', $tags );
	}
	
	function currentProjectTagsArray()
	{
		global $clerk;
		
		$selectedTags	= 	explode( ",", $_GET['project_tags'] );
		$tags			=	array();
		
		foreach ( $selectedTags as $tag )
		{
			$tags[]= $tag;
		}
		
		return $tags;
	}
	
	function viewingProjectTags()
	{
		return ( empty( $_GET['project_tags'] ) ) ? false : true;
	}
	
	function allProjectTags()
	{
		global $clerk;
		
		$tags= array();
		
		$getTags= $clerk->query_select( "projects_to_tags", "DISTINCT tag", "ORDER BY id ASC" );
		while ( $tag= $clerk->query_fetchArray( $getTags ) )
		{
			$tags[]= '<a href="' . linkToProjectTag( $tag['tag'] ) . '">' . $tag['tag'] . '</a>';
		}
		
		return implode( ', ', $tags );
	}
	
	function allProjectTagsArray()
	{
		global $clerk;
		
		$tags= array();
		
		$getTags= $clerk->query_select( "projects_to_tags", "DISTINCT tag",  "ORDER BY id ASC" );
		while ( $tag= $clerk->query_fetchArray( $getTags ) )
		{
			$tags[]= $tag['tag'];
		}
		
		return $tags;
	}
	
	function nextProject( $returnData= false )
	{
		global $clerk, $project;
		
		$projectsList= array();
		$orderedProjects= array();
		$currentProject= projectInfo( selectedProject() );
		$currentIndex= 0;
		$count= 0;

		$page= pageInfo( currentPage() );
		$options= prepare_settings( $page['content_options'] );
		
		$section= $clerk->query_fetchArray( $clerk->query_select( "project_sections", "", "WHERE name= '{$options['section']}' OR slug= '{$options['section']}' OR id='{$options['section']}'" ) );
		$section= $section['id'];
		
		if ( !empty( $section ) )
		{
			$projects= $clerk->query_select( "projects", "", "WHERE publish= 1 AND section= '$section' ORDER BY pos ASC" );
		}
		else
		{
			$projects= $clerk->query_select( "projects", "", "WHERE publish= 1 ORDER BY section, pos ASC" );
		}
		
		while ( $project= $clerk->query_fetchArray( $projects ) )
		{
			$projectsList[]= $project;
		}
		
		$getSections= $clerk->query_select( "project_sections", "", "ORDER BY pos ASC" );
		while ( $section= $clerk->query_fetchArray( $getSections ) )
		{
			foreach ( $projectsList as $p )
			{
				if ( $p['section'] == $section['id'] )
				{
					$orderedProjects[]= $p;
					if ( $p['id'] == $currentProject['id'] )
					{
						$currentIndex= $count;
					}
					$count++;
				}
			}
		}
		
		$nextProject= $orderedProjects[$currentIndex + 1];

		if ( $returnData )
		{
			$nextProject['link']= linkToProject( $nextProject['id'] );
			return $nextProject;
		}
		
		return ( $currentIndex != count( $orderedProjects ) - 1 ) ? linkToProject( $nextProject['id'] ) : linkToProject( $orderedProjects[0]['id'] );
	}
	
	function prevProject( $returnData= false )
	{
		global $clerk;
		
		$projectsList= array();
		$orderedProjects= array();
		$currentProject= projectInfo( selectedProject() );
		$currentIndex= 0;
		$count= 0;
		
		$page= pageInfo( currentPage() );
		$options= prepare_settings( $page['content_options'] );
		$section= $clerk->query_fetchArray( $clerk->query_select( "project_sections", "", "WHERE name= '{$options['section']}' OR slug= '{$options['section']}' OR id='{$options['section']}'" ) );
		$section= $section['id'];
		
		if ( !empty( $section ) )
		{
			$projects= $clerk->query_select( "projects", "", "WHERE section= '$section' ORDER BY pos ASC" );
		}
		else
		{
			$projects= $clerk->query_select( "projects", "", "ORDER BY section, pos ASC" );
		}
		
		while ( $project= $clerk->query_fetchArray( $projects ) )
		{
			$projectsList[]= $project;
		}
		
		$getSections= $clerk->query_select( "project_sections", "", "ORDER BY pos ASC" );
		while ( $section= $clerk->query_fetchArray( $getSections ) )
		{
			foreach ( $projectsList as $p )
			{
				if ( $p['section'] == $section['id'] )
				{
					$orderedProjects[]= $p;
					if ( $p['id'] == $currentProject['id'] )
					{
						$currentIndex= $count;
					}
					$count++;
				}
			}
		}
		
		if ( $currentIndex == 0 ) {
			$prevProject= $orderedProjects[count( $orderedProjects ) - 1];
		} else {
			$prevProject= $orderedProjects[$currentIndex - 1];
		}
		
		if ( $returnData )
		{
			$prevProject['link']= linkToProject( $prevProject['id'] );
			return $prevProject;
		}
		
		return linkToProject( $prevProject['id'] );
	}
	
	function projectDate( $format= "d. F Y" )
	{
		global $project;
		
		return date( $format, $project['date'] );
	}
	
	function projects_rss()
	{
		global $clerk, $project;
		
		$feed= new FeedWriter(RSS2);
			
		$title= $clerk->getSetting( "site", 1 );
		$feed->setTitle( $title . ' / Projects Feed' );
		$feed->setLink( linkToSite() );
		$feed->setDescription('Live feed of projects on ' . $title );

		$feed->setChannelElement('pubDate', date(DATE_RSS, time()));
		
		$projects= $clerk->query_select( "projects", "", "WHERE publish= 1 ORDER BY id DESC" );
		while ( $project= $clerk->query_fetchArray( $projects ) )
		{
			$newItem= $feed->createNewItem();

			$newItem->setTitle( $project['title'] );
			$newItem->setLink( html_entity_decode( linkToProject( $project['id'] ) ) );
			$newItem->setDate( $project['date'] );

			$desc= projectThumbnail();
			$desc= call_anchor( "projectsRssDescription", $desc );
			
			$newItem->setDescription('' . $desc . '');
			$newItem->addElement( 'guid', linkToProject( $project['id'] ), array('isPermaLink'=>'true') );

			$feed->addItem($newItem);

			$count= 0;
			$desc= "";
		}

		$feed->genarateFeed();
	}
	
	
?>
