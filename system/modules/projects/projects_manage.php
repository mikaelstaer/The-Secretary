<?php
	// Define anchors
	define_anchor( "displayersList" );
	define_anchor( "projectsOverviewToolbar" );
	define_anchor( "projectFilesToolbar" );
	define_anchor( "projectFilesToolbarBottom" );
	define_anchor( "projectFormAfterDetails" );
	define_anchor( "projectFormAfterThumbnail" );
	define_anchor( "projectFormBeforeFiles" );
	define_anchor( "projectFormAfterFiles" );
	
	// Load required helpers
	$manager->load_helper( "interface" );
	
	// Define hooks
	if ( $_GET['mode']== "edit" )
	{
		hook( "form_process", "processEditProjectForm" );	
		
		$id= $_GET['id'];
		$project= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "projects", "", "WHERE id= '$id' LIMIT 1") );
			
		if ( $_POST['submit'] != "delete" )
		{
			hook( "breadcrumbActive", "projectTitle", array( $project ) );
			hook( "form_submit_primary", "submitButtons", array(0) );
			hook( "form_submit_secondary", "submitButtons", array(1) );		
			hook( "form_main", "editProjectForm", array( $allowed_file_types, $project ) );
		}
	}
	elseif ( $_GET['mode'] == "delete" && !empty( $_GET['id'] ) )
	{
		hook( "big_message", "projectDelete" );
	}

	if ( ( $_GET['mode'] == "edit" && $_POST['submit'] == "delete" ) || $_GET['mode'] != "edit" )
	{	
		hook( "action_bar", "projectsOverviewToolbar", "", 1);
		hook( "form_main", "projects", "", 2 );
	}
	
	hook( "projects-manage", "projectsDelegate" );
	
	// Functions
	function projectsDelegate()
	{
		hook( "javascript", "projectsFormJs", "", 0 );
		hook( "form_main", "hiddenFields" );
	}
	
	function hiddenFields()
	{
		global $manager;
		
		$paths= $manager->getSetting( "projects_path" );
		$paths= array(
						'path' =>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		echo '
		<input type="hidden" name="asstPath" id="asstPath" value="' . $manager->clerk->config('ASSISTANTS_PATH') . '" />
		<input type="hidden" name="uploadPath" id="uploadPath" value="' . $paths['data1'] . '" />
		<input type="hidden" name="uploadUrl" id="uploadUrl" value="' . $paths['data2'] . '" />
		';
	}
	
	function projectTitle( $project )
	{
		echo $project['title'];
	}
	
	function projectsOld()
	{
		global $manager;
		
		echo '<div id="overview">';
		$getProjects= $manager->clerk->query_select( "projects", "", "ORDER BY pos ASC" );		
		while ( $project= $manager->clerk->query_fetchArray( $getProjects ) )
		{
			$projects[$project['id']]= $project;
		}
		
		$getSections= $manager->clerk->query_select( "project_sections", "", "ORDER BY pos ASC" );
		while ( $section= $manager->clerk->query_fetchArray( $getSections ) )
		{
			$html.= '
				<ul id="section_' . $section['id'] . '" class="section">
					<li>
						<div class="controls">
							<ul>
								<li class="handle">
									Drag
								</li>
								<li class="name">
									' . $section['name'] . '
								</li>
								<li class="edit">
									<a href="#" onclick="editSection(\'' . $section['id'] . '\', \'' . $section['name'] . '\', \'' . $section['slug'] . '\'); return false;">Edit</a>
								</li>
								<li class="delete">
									<a href="#" onclick="deleteSection(' . $section['id'] . '); return false;">Delete</a>
								</li>
							</ul>
						</div>
					</li>
			';
			
			foreach ( $projects as $id => $data )
			{
				if ( $data['section'] == $section['id'] )
				{
					$html.= '
						<li class="project" id="project_' . $id . '">
							<span class="handle">Drag</span> <a href="' . $manager->office->URIquery( "id", "mode=edit&id=" . $data['id'] ) . '">' . $data['title'] . '</a>
						</li> 
					';
				}
			}
			
			$html.= '
				</ul>
			';
		}
		echo $html;
		echo '</div>';
	}
	
	function projects()
	{
		global $manager;
		
		echo <<<HTML
		<div id="overview">
			<div id="tabHolder">
				<ul id="sectionsTabs">
HTML;

		$tabs= "";
		$tabContent= "";
		$countProjects= 1;
		
		$getProjects= $manager->clerk->query_select( "projects", "", "ORDER BY pos ASC" );		
		while ( $project= $manager->clerk->query_fetchArray( $getProjects ) )
		{
			$projects[$project['id']]= $project;
		}
		
		$getSections= $manager->clerk->query_select( "project_sections", "", "ORDER BY pos ASC" );
		while ( $section= $manager->clerk->query_fetchArray( $getSections ) )
		{	
			$tabs.= '
					<li id="section_' . $section['id'] . '" class="section">
						<a class="name" href="#section-' . $section['id'] . '">' . $section['name'] . '</a>
					</li>
			';
			
			$tabContent.= '<div id="section-' . $section['id'] . '" class="sectionProjects">';
			$tabContent.= '
							<div class="controls">
								<ul>
									<li class="edit">
										<a href="#" onclick="editSection(\'' . $section['id'] . '\', \'' . $section['name'] . '\', \'' . $section['slug'] . '\'); return false;">Edit Section</a>
									</li>
									<li class="delete">
										<a href="#" onclick="deleteSection(' . $section['id'] . '); return false;">Delete Section</a>
									</li>
								</ul>
							</div>
						';
			$tabContent.= '<ul class="projects">';
			
			foreach ( $projects as $id => $data )
			{
				if ( $data['section'] == $section['id'] )
				{
					if ( empty( $data['thumbnail'] ) )
					{
						// No thumbnail? Get first image...
						$flow= explode( ",", preg_replace( '/(textblock)([0-9]+)/', "", $data['flow'] ) );
						$group_num= preg_replace( '/(group)([0-9])(:)*([a-zA-Z0-9-_]+)?/', '$2', $flow[0] );
						$first= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "project_files", "", "WHERE project_id= '$id' AND type= 'image' ORDER BY pos, filegroup ASC LIMIT 1" ) );
						$data['thumbnail']= $first['file'];
					}
					
					$thumb_path= $manager->clerk->getSetting( "projects_path", 1 ) . $data['slug'] . '/' . $data['thumbnail'];
					
					$file_extension= substr( $data['thumbnail']	, strrpos( $data['thumbnail'], '.' ) );
					$cache_file_name= str_replace( $file_extension, "", $data['thumbnail'] ) . "." . 175 . "x" . 110 . "_1.jpg";
					
					$dynamic_thumb= ( file_exists( $manager->clerk->getSetting( "cache_path", 1 ) . $cache_file_name ) ) ? $manager->clerk->getSetting( "cache_path", 2 ) . $cache_file_name : $manager->clerk->getSetting( "site", 2 ) . "?dynamic_thumbnail&file=" . $thumb_path . '&amp;width=' . 175 . '&amp;height=' . 110 . '&adaptive=1';
					
					$thumbnail= ( empty( $data['thumbnail'] ) == false ) ? '<img src="' . $dynamic_thumb . '" />' : "";
					$tabContent.= '
						<li class="project" id="project_' . $data['id'] . '">
							<a href="' . $manager->office->URIquery( "id", "mode=edit&id=" . $data['id'] ) . '"><span class="handle">' . $thumbnail . '</span><span class="title">' . $data['title'] . '</span></a>
						</li> 
					';
					
					$countProjects++;
				}
			}
			
			$tabContent.= '</ul></div>';
		}
		
		echo $tabs;

		echo <<<HTML
				</ul>
			</div>
			{$tabContent}
		</div>
HTML;
	}
	
	function projectsOverviewToolbar()
	{
		global $manager;
		
		$tools= array(
						'<a href="#" onclick="newProject(); return false;" class="button-new">New Project</a>',
						'<a href="#" onclick="newSection(); return false;" class="button-new">New Section</a>'
		);
		
		$tools= call_anchor( "projectsOverviewToolbar", $tools );
		
		$toolbar= new Toolbar(
			array(
				"tools"	=>	$tools
			)
		);
		
		
		echo $toolbar->html;
	}


	function projectsFormJs()
	{
		global $manager;
		
		echo $manager->load_jshelper( "fileuploader" );
		echo $manager->load_jshelper( 'jquery.form' );
		echo $manager->load_jshelper( 'quicktags' );
		echo $manager->load_jshelper( 'jquery.typewatch' );
	}
	
	function projectDelete()
	{
		global $manager;
		
		$id= $_GET['id'];
		$project= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "projects", "", "WHERE id= '$id' LIMIT 1") );
		
		if ( empty( $project ) ) return;
		
		$paths= $manager->getSetting( "projects_path" );
		$paths= array(
						'path' =>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$project_folder= $paths['path'] . $project['slug'];
		if ( file_exists( $project_folder ) && is_dir( $project_folder ) )
		{
			rmdirr( $paths['path'] . $project['slug'] );
		}
		
		$manager->clerk->query_delete( "projects_to_tags", "WHERE projectid= '$id'" );
		
		if ( $manager->clerk->query_delete( "projects", "WHERE id= '$id'" ) && $manager->clerk->query_delete( "project_files", "WHERE project_id= '$id'" ) )
		{
			$manager->message( 1, false, "Project <em>" . $project['title'] . "</em> deleted!" );
		}
		else
		{
			$manager->message( 0, true, "Could not delete project!" );
		}
	}
	
	function processEditProjectForm()
	{
		global $manager;
				
		$id			=	$_POST['id'];
		$title 		=	$_POST['title'];
		$slug 		= 	( empty( $_POST['slug'] ) ) ? $manager->clerk->simple_name( $title ) : $_POST['slug'];
		$oldSlug	=	$_POST['oldslug'];
		$now		= 	getdate();
		$date		= 	mktime( $now["hours"], $now["minutes"], $now["seconds"], $_POST["date_month"], $_POST["date_day"], $_POST["date_year"] );
		$tags		=	$_POST['tags'];
		$publish	=	$_POST['publish'];
		
		$paths= $manager->getSetting( "projects_path" );
		$paths= array(
						'path' =>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		if ( $_POST['submit'] == "save" )
		{
			if ( $manager->clerk->query_countRows( "projects", "WHERE slug= '$slug' AND id != '$id'" ) >= 1 )
			{
				$manager->message( 0, false, 'A project with the simple name/slug <em>"' . $slug . '"</em> already exists! Please choose a new one.' );
				return false;
			}
			
			if ( $manager->clerk->query_edit( "projects", "title= '$title', slug= '$slug', date= '$date', publish= '$publish'", "WHERE id= '$id'" ) )
			{
				
				$manager->message( 1, false, "Project <em>$title</em> saved!" );
				
				// Handle tags...
				if ( !empty( $tags ) )
				{
					$tags	= explode( ",", $tags );
					$total	= count( $tags );
					$manager->clerk->query_delete( "projects_to_tags", "WHERE projectid= '$id'" );
					
					$count= 0;
					foreach ( $tags as $c )
					{
						$count++;
						$query.= "('" . utf8_trim( $c ) . "', '$id')";
						$query.= ( $count == $total ) ? "" : ", ";
					}
					
					$manager->clerk->query_insert( "projects_to_tags", "tag, projectid", $query, true );
				}
				else
				{
					$manager->clerk->query_delete( "projects_to_tags", "WHERE projectid= '$id'" );
				}
				
				// Handle renaming
				if ( $slug != $oldSlug )
				{
					rename( $paths['path'] . $oldSlug, $paths['path'] . $slug );
				}
			}
			else
			{
				$manager->message( 0, true, "This project could not be saved!" );
			}
		}
	}
	
	function submitButtons( $loc )
	{
		global $manager;
		
		if ( $loc == 0 )
			$manager->form->add_input( 'submit', 'submit', 'Save Changes', 'save' );
		if ( $loc == 1 )
			$manager->form->add_input( 'submit', 'submit', 'Delete', 'delete' );
	}

	function displayersOptList( $list, $selected )
	{
		$html= "";
		
		foreach ( $list as $d )
		{
			if ( $d == $selected )
			{
				$sel= ' selected= "selected"';
			}
			else
			{
				$sel= "";
			}
			
			$html.= '<option value="' . $d . '"' . $sel . '>' . array_search( $d, $list ) . '</option>';
		}
		
		return $html;
	}

	function editProjectForm( $allowed_file_types )
	{
		global $manager;
		
		// Define required variables
		$id= $_GET['id'];
		$project= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "projects", "", "WHERE id= '$id' LIMIT 1") );
		
		$paths= $manager->getSetting( "projects_path" );
		$paths= array(
						'path' =>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$displayersOptList= array();
		$displayers= call_anchor( "displayersList", array() );
		
		$currentThumb= ( empty( $project['thumbnail'] ) ) ? "" : '<img src="' . $paths['url'] . $project['slug'] . '/' . $project['thumbnail'] . '" alt="" />';
		$deleteLink= ( empty( $project['thumbnail'] ) ) ? " hide" : "";
			
		// Rules
		$manager->form->add_rule( "title" );
		
		// Begin Form				
		$manager->form->add_fieldset( "Project Details", "projectDetails" );
		
		$manager->form->add_input( "hidden", "id", NULL, $id );
		$manager->form->add_input( "hidden", "oldslug", NULL, $project['slug'] );
		
		$manager->form->add_to_form('<div class="col-2">');
		
		$manager->form->add_input( "text", "title", "Title", $project['title'] );
		
		$manager->form->add_input( "text", "slug", "Simple Name / Slug", $project['slug'] );
		
		$manager->form->set_template( "row_end", "" );
		$manager->form->set_template( "text_template", "text_supershort", true );

		$manager->form->add_to_form( '<div class="field date"><label>Date</label><br />' );

		$current_month= date( "F" );
		$months= array( "January"	=> 	"1",
						"February"	=> 	"2",
						"March"		=>	"3",
						"April"		=>	"4",
						"May"		=> 	"5",
						"June"		=>	"6",
						"July"		=>	"7",
						"August"	=>	"8",
						"September"	=>	"9",
						"October"	=>	"10",
						"November"	=>	"11",
						"December"	=>	"12"
				 );

		$manager->form->add_select( "date_month", NULL, $months, date( "F", $project['date'] ) );

		$current_day= date( "j" );
		for ( $i= 1; $i <= 31 ; $i++ ) {
			if ( $i <= 9 )
				$day= "0".$i;
			else
				$day= $i." ";

			$days[$day]= $i;
		}

		$manager->form->set_template( "select_template", "select_supershort", true );
		$manager->form->set_template( "option_template", "options_supershort", true );
		$manager->form->add_select( "date_day", NULL, $days, date( "j", $project['date'] ) );

		$current_year= date( "Y" );
		for ( $i= 2000; $i <= $current_year + 5 ; $i++ ) {
			$year= $i." ";
			$years[$year]= $i;
		}

		$manager->form->add_input( "text", "date_year", NULL, date( "Y", $project['date'] ) );
		
		// 
		$manager->form->add_to_form( '</div>' );
		
		$manager->form->reset_template( "text_template" );
		$manager->form->reset_template( "option_template" );
		$manager->form->reset_template( "select_template" );

		$manager->form->reset_template( "row_end" );

		$tags= array();
		$getTags= $manager->clerk->query_select( "projects_to_tags", "", "WHERE projectid= '$id' ORDER BY id ASC" );
		while ( $tag= $manager->clerk->query_fetchArray( $getTags ) )
		{
			$tags[]= $tag['tag'];
		}
		
		$manager->form->add_input( "text", "tags", "Tags", implode( ",", $tags ) );
		
		$manager->form->add_input( "checkbox", "publish", " ", $project['publish'], array( "Publish" => 1 ) );
		
		call_anchor( "projectFormAfterDetails" );
		
		$manager->form->add_to_form('</div>');
		
		$manager->form->add_to_form('<div class="col-2 last">');
		$manager->form->add_to_form( '<div id="thumbnailForm">' );
		$manager->form->add_input( "file", "Thumbnail", 'Project Thumbnail <span class="delete singleButton' . $deleteLink . '"><a href="#" onclick="deleteProjThumbnail(); return false;">Delete</a></span>' );
		$manager->form->add_to_form( '<button type="submit" id="uploadThumbnail" name="uploadThumbnail" value="upload">Upload</button>' );
		$manager->form->add_input( "hidden", "asstPath", "", $manager->clerk->config('ASSISTANTS_PATH') );
		$manager->form->add_input( "hidden", "uploadPath", "", $paths['path'] );
		$manager->form->add_input( "hidden", "uploadUrl", "", $paths['url'] );
		$manager->form->add_input( "hidden", "id", "", $id );
		$manager->form->add_input( "hidden", "action", "", "uploadThumbnail" );
		$manager->form->add_to_form( '<div id="theThumb">' . $currentThumb . '</div>' );
		$manager->form->add_to_form( '</div>' );
		
		call_anchor( "projectFormAfterThumbnail" );
		
		$manager->form->add_to_form( '</div>' );
		$manager->form->close_fieldset();
		
		//
		
		call_anchor( "projectFormBeforeFiles" );
		 
		$manager->form->add_fieldset( "Images, Video, Audio and Text", "files" );
		
		$tools= array(
					'<a href="#" onclick="newGroup(\'top\'); return false;" class="button-new">New Group</a>',
					'<a href="#" onclick="addTextBlock(\'top\'); return false;" class="button-new">Add text block</a>'
		);
		
		$tools= call_anchor( "projectFilesToolbar", $tools );
		$toolbar= new Toolbar(
			array(
				"id"	=>	"top",
				"tools"	=>	$tools
			)
		);
		
		
		$manager->form->add_to_form( '<div id="uploadForm">' );
		$manager->form->add_input( "hidden", "asstPath", "", $manager->clerk->config('ASSISTANTS_PATH') );
		$manager->form->add_input( "hidden", "uploadPath", "", $paths['path'] );
		$manager->form->add_input( "hidden", "uploadUrl", "", $paths['url'] );
		$manager->form->add_input( "hidden", "action", "", "upload" );
		$manager->form->add_input( "hidden", "id", "", $id );
		
		// Valums File Uploader
		$manager->form->add_to_form( '<div id="file-uploader">upload files <span class="explanation">(<strong>Max ' . str_replace( "M", "mb", ini_get( "upload_max_filesize" ) ) . '</strong> per file)</span></div>' );
		
		$manager->form->add_to_form( '</div>' );
		
		$manager->form->add_to_form( $toolbar->html );
		
		$flow= explode( ",", $project['flow'] );
		$projectFiles= array();

		$getFiles= $manager->clerk->query_select( "project_files", "", "WHERE project_id= '$id' ORDER BY pos ASC" );
		while ( $file= $manager->clerk->query_fetchArray( $getFiles ) )
		{
			$projectFiles[$file['id']]= $file;
		}
		
		foreach ( $flow as $part )
		{
			if ( strstr( $part, "textblock") )
			{
				$fileID= str_replace( "textblock", "", $part );
				
				$textblock_toolbar= array(
					'html'		=>	'<li class="delete"><a href="#" onclick="deleteGroup(' . $fileID . '); return false;">Delete</a></li>',
					'file_id'	=>	$fileID,
					'file'		=>	$projectFiles[$fileID]
				);
				
				$textblock_toolbar= call_anchor( "projects_textblock_toolbar", $textblock_toolbar );

				$grouped.= '
					<div id="file_group-' . $fileID . '" class="fileGroup textBlock" data-type="textblock">
						<div class="controls">
							<ul>
								<li class="handle">
									Drag
								</li>
								<li class="textblockToolbar">
									<div id="toolbar-textBlock_' . $fileID . '"></div>
								</li>
								' . $textblock_toolbar['html'] . '
							</ul>
						</div>
						<textarea id="textBlock_' . $fileID . '" class="textblock" rows="8" cols="50">' . html_entity_decode( $projectFiles[$fileID]['caption'] ) . '</textarea>
					</div>
				';
			}
			elseif ( strstr( $part, "group" ) )
			{
				$groupNum	=	preg_replace( '/(group)([0-9]+)(:)*([a-zA-Z0-9-]+)?/', '$2', $part );
				$groupType	=	preg_replace( '/(group)([0-9]+)(:)*([a-zA-Z0-9-]+)?/', '$4', $part );
				
				$list= displayersOptList( $displayers, $groupType );
				
				$group_toolbar= array(
					'html'		=>	'<li class="delete"><a href="#" onclick="deleteGroup(' . $groupNum . '); return false;">Delete</a></li>',
					'group_id'	=>	$groupNum
				);
				
				$group_toolbar= call_anchor( "projects_filegroup_toolbar", $group_toolbar );
				
				$grouped.= '
					<ul id="file_group-' . $groupNum . '" class="fileGroup" data-type="group">
						<li>
							<div class="controls">
								<ul>
									<li class="handle">
										Drag
									</li>
									<li class="title">
										Group ' . $groupNum . '
									</li>
									<li class="displayer">
										<select class="displayType" onchange="setGroupDisplayer(' . $groupNum . ', this)">
											' . $list . '
										</select>
									</li>
									' . $group_toolbar['html'] . '
								</ul>
							</div>
						</li>
				';
				
				foreach ( $projectFiles as $file => $data )
				{
					$fileID= $data['id'];
					
					if ( $data['filegroup'] == $groupNum )
					{
						if ( $data['type'] == "image" )
						{
							$thumb_path= $manager->clerk->getSetting( "projects_path", 1 ) . $project['slug'] . '/' . $data['file'];

							$file_extension= substr( $data['file']	, strrpos( $data['file'], '.' ) );
							$cache_file_name= str_replace( $file_extension, "", $data['file'] ) . "." . 100 . "x" . 100 . "_1.jpg";

							$dynamic_thumb= ( file_exists( $manager->clerk->getSetting( "cache_path", 1 ) . $cache_file_name ) ) ? $manager->clerk->getSetting( "cache_path", 2 ) . $cache_file_name : $manager->clerk->getSetting( "site", 2 ) . "?dynamic_thumbnail&file=" . $thumb_path . '&amp;width=' . 100 . '&amp;height=' . 100 . '&adaptive=1';

							$thumb= ( empty( $data['thumbnail'] ) == false ) ? '<img src="' . $dynamic_thumb . '" />' : "";
							
						}
						else
						{
							$thumb=	'<span class="media">' . $data['file'] . '</span>';
						}
						
						$file_toolbar= call_anchor( "project_file_toolbar", array( 'html' => "", 'file' => $data ) );
						
						$grouped.= '
							<li class="filebox" id="file_' . $fileID .'">
								<div class="thumbnail">
									' . $thumb . '
								</div>

								<ul class="toolbar">
									<li onmouseover="toolbar_show(' . $fileID . ');" onmouseout="toolbar_hide(' . $fileID . ');">
										<a href="#" class="edit">Edit</a>
										<ul class="options">
											<li><a href="#" onclick="toolbar_delete(' . $fileID . '); return false;">Delete</a></li>
											<li><a href="#" onclick="toolbar_details(' . $fileID . '); return false;">Edit details</a></li>
											' . $file_toolbar['html'] . '
										</ul>
									</li>
								</ul>
							</li>
						';
						
						unset( $projectFiles[$file] );
					}
				}
				
				$grouped.= '
					</ul>
				';
			}
			else
			{
				$others= call_anchor( "project_content_flow_backend", array( 'part' => $part, 'grouped' => "" ) );
				$grouped.= $others['grouped'];
			}
		}
		
		foreach ( $projectFiles as $file => $data )
		{
			$fileID= $data['id'];
			
			if ( $data['filegroup'] == 0 && $data['type'] != "text" )
			{
				if ( $data['type'] == "image" )
				{
					$thumb_path= $manager->clerk->getSetting( "projects_path", 1 ) . $project['slug'] . '/' . $data['file'];

					$file_extension= substr( $data['file']	, strrpos( $data['file'], '.' ) );
					$cache_file_name= str_replace( $file_extension, "", $data['file'] ) . "." . 100 . "x" . 100 . "_1.jpg";

					$dynamic_thumb= ( file_exists( $manager->clerk->getSetting( "cache_path", 1 ) . $cache_file_name ) ) ? $manager->clerk->getSetting( "cache_path", 2 ) . $cache_file_name : $manager->clerk->getSetting( "site", 2 ) . "?dynamic_thumbnail&file=" . $thumb_path . '&amp;width=' . 100 . '&amp;height=' . 100 . '&adaptive=1';

					$thumb= ( empty( $data['thumbnail'] ) == false ) ? '<img src="' . $dynamic_thumb . '" />' : "";
				}
				elseif ( $data['type'] == "video" || $data['type'] == "audio" )
				{
					$thumb=	'<span class="media">' . $data['file'] . '</span>';
				}
				else
				{
					continue;
				}
				
				$file_toolbar= call_anchor( "project_file_toolbar", array( 'html' => "", 'file' => $data ) );
				
				$waiting.= '
					<li class="filebox" id="file_' . $fileID .'">
						<div class="thumbnail">
							' . $thumb . '
						</div>

						<ul class="toolbar">
							<li onmouseover="toolbar_show(' . $fileID . ');" onmouseout="toolbar_hide(' . $fileID . ');">
								<a href="#" class="edit">Edit</a>
								<ul class="options">
									<li><a href="javascript:void(0)" onclick="toolbar_delete(' . $fileID . ')">Delete</a></li>
									<li><a href="javascript:void(0)" onclick="toolbar_details(' . $fileID . ')">Edit details</a></li>
									' . $file_toolbar['html'] . '
								</ul>
							</li>
						</ul>
					</li>
				';
			}
		}
			
		$hide= ( empty( $waiting ) ) ? " hide" : "";
		
		$manager->form->add_to_form( '
			<div id="groupedFiles">
				<ul id="waitingRoom" class="fileGroup' . $hide . '">
					<li>
						<div class="controls">
							<ul>
								<li class="formMessage">
									<strong>Ungrouped Files</strong> / Drag these files to a group
								</li>
							</ul>
						</div>
					</li>
					' . $waiting . '
			</ul>
		');	
		
		$manager->form->add_to_form( 
			$grouped . '
			</div>
		');
		
		if ( count( $flow ) < 3 )
		{
			$bottomToolbarClass= " hide";
		}
		
		$tools= array(
					'<a href="#" onclick="newGroup(\'bottom\'); return false;" class="button-new">New Group</a>',
					'<a href="#" onclick="addTextBlock(\'bottom\'); return false;" class="button-new">Add text block</a>'
		);

		$tools= call_anchor( "projectFilesToolbarBottom", $tools );
		$toolbar= new Toolbar(
			array(
				"id"	=>	"bottom",
				"class"	=>	$bottomToolbarClass,
				"tools"	=>	$tools
			)
		);

		$manager->form->add_to_form( $toolbar->html );
		
		$manager->form->close_fieldset();
		
		call_anchor( "projectFormAfterFiles" );
	}
?>