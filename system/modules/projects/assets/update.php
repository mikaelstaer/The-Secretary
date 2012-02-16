<?php
	error_reporting( 0 );
	
	define( "AJAX", true );
	require_once $_POST['system']['path'] . "assistants/launch.php";
	
	define_anchor( "displayersList" );
	
	$paths= $clerk->getSetting( "projects_path" );
	$paths= array( 	'path' 	=>	$paths['data1'],
					'url'	=>	$paths['data2']
	);
	
	function update()
	{
		global $clerk;
		
		$file_id	=	$_POST['file_id'];
		$title 		= 	$_POST['title'];
		$caption	=	$_POST['caption'];
		
		echo $clerk->query_edit( "project_files", "title= '$title', caption= '$caption'", "WHERE id= '$file_id'" );
	}
	
	function delete()
	{
		global $clerk;
		
		$paths= $clerk->getSetting( "projects_path" );
		$paths= array( 	'path' 	=>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$projectId= $_POST['project_id'];
		$file_id= $_POST['file_id'];
		
		$project= $clerk->query_fetchArray( $clerk->query_select( "projects" , "", "WHERE id= '$projectId'" ) );
		$file= $clerk->query_fetchArray( $clerk->query_select( "project_files" , "", "WHERE id= '$file_id'" ) );
		
		$big= $file['file'];
		$thumbnail= $file['thumbnail'];
		
		$ok= $clerk->query_delete( "project_files", "WHERE id= '$file_id'" );
		
		echo $bigFile= $paths['path'] . $project['slug'] . "/" . $big;
		// $thumbFile= $paths['path'] . $project['slug'] . "/" . $thumbnail;
		
		if ( file_exists( $bigFile ) )
		{
			echo "yes";
			if ( unlink( $bigFile ) )
			{
				$ok= 1;
			}
			else
			{
				$ok= 0;
			}
		}
		
		echo $ok;
	}
	
	function orderFiles()
	{
		global $clerk;
		
		if ( !empty( $_POST['file_order'] ) )
		{
			$project_id	=	$_POST['project_id'];
			$group		=	$_POST['group'];
			$count		=	1;
			
			parse_str( str_replace("&amp;", "&", $_POST['file_order']) );

			foreach( $file as $f )
			{
				$ok= $clerk->query_edit( "project_files", "filegroup= '$group', project_id= '$project_id', pos= '$count'", "WHERE id= '$f'" );
				$count++;
			}
			
			echo $ok;
		}
	}
	
	function getDetails()
	{
		global $clerk;
		
		$file_id= $_POST['file_id'];
		
		$file= $clerk->query_fetchArray( $clerk->query_select( "project_files", "*", "WHERE id= '$file_id'" ) );
		
		echo json_encode( $file );
	}
	
	function flow()
	{
		global $clerk;
		
		$project_id	=	$_POST['project_id'];
		$flow		=	$_POST['flow'];
		
		echo $clerk->query_edit( "projects", "flow= '$flow'", "WHERE id= '$project_id'" );
	}
	
	function newProject()
	{
		global $clerk;
		
		$paths= $clerk->getSetting( "projects_path" );
		$paths= array( 	'path' 	=>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$title 		= 	$_POST['title'];
		$slug		= 	$clerk->simple_name( $title );
		$now		= 	getdate();
		$date		= 	mktime( $now["hours"], $now["minutes"], $now["seconds"], $now["mon"], $now["mday"], $now["year"] );
		$section	=	$_POST['section'];
		
		$nextFileID	= 	$clerk->nextID("project_files");
		$pos		=	$clerk->query_countRows( "projects", "WHERE section= '$section'" ) + 1;
		
		$project= $clerk->query_insert( "projects", "title, slug, date, section, flow, publish, pos", "'$title', '$slug', '$date', '$section', 'group1:one-by-one,textblock$nextFileID', '1', '$pos'" );
		$projectID= $clerk->lastID();
		
		$firstTextBlock= $clerk->query_insert( "project_files", "caption, project_id, type, filegroup", "'Some text about this project...','$projectID','text','0'" );

		$makeDir= mkdir( $paths['path'] . $slug );
		
		if ( $project && $firstTextBlock && $makeDir )
		{
			echo $projectID;
		}
		else
		{
			echo "false";
		}
	}
	
	function newTextBlock()
	{
		global $clerk;
		
		$title 				= 	$_POST['title'];
		$data				= 	$_POST['caption'];
		$project_id			= 	$_POST['project_id'];
		$type				= 	"text";
		$pos				=	$clerk->query_countRows( "project_files", "WHERE project_id= '$project_id'" ) + 1;
		
		if ( $clerk->query_insert( "project_files", "title, caption, project_id, pos, type", "'$title', '$data', '$project_id', '$pos', '$type'" ) )
		{
			$id= $clerk->lastID();
			$html= <<<HTML
				<div id="file_group-{$id}" class="fileGroup textBlock" data-type="textblock">
					<div class="controls">
						<ul>
							<li class="handle">
								Drag
							</li>
							<li class="textblockToolbar">
								<div id="toolbar-textBlock_{$id}"></div>
							</li>
							<li class="delete">
								<a href="#" onclick="deleteGroup({$id}); return false;">Delete</a>
							</li>
						</ul>
					</div>
					<textarea id="textBlock_{$id}" class="textblock" rows="7" cols="50">Some text...</textarea>
				</div>
HTML;

			echo $html;
		}
		else
		{
			echo json_encode(
				array(
					"success"	=>	false,
					"error"		=>	$clerk->error()
				)
			);
		}
	}
	
	function newGroup()
	{
		global $clerk, $anchors;
		
		$groupNum= $_POST['groupNum'];
		$displayers= call_anchor( "displayersList", array() );
		$count= 0;
		
		foreach ( $displayers as $d )
		{
			$count++;
			
			if ( $count == 1 )
			{
				$sel= ' selected= "selected"';
			}
			else
			{
				$sel= "";
			}
			
			$displayersList.= '<option value="' . $d . '"' . $sel . '>' . array_search( $d, $displayers ) . '</option>';
		}
				
		$html= <<<HTML
			<ul id="file_group-{$groupNum}" class="fileGroup" data-type="group">
				<li>
					<div class="controls">
						<ul>
							<li class="handle">
								Drag
							</li>
							<li class="title">
								Group {$groupNum}
							</li>
							<li class="displayer">
								<select class="displayType" onchange="setGroupDisplayer({$groupNum}, this)">
									{$displayersList}
								</select>
							</li>
							<li class="delete">
								<a href="#" onclick="deleteGroup({$groupNum}); return false;">Delete</a>
							</li>
						</ul>
					</div>
				</li>
			</ul>
HTML;

		echo $html;
	}
	
	function deleteGroup()
	{
		global $clerk, $paths;
		
		$paths= $clerk->getSetting( "projects_path" );
		$paths= array( 	'path' 	=>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$projectId		=	$_POST['projectId'];
		$groupId		=	$_POST['groupId'];
		$isTextBlock	=	$_POST['isTextBlock'];
		
		$project= $clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '$projectId'" ) );
		
		if ( $isTextBlock == "true" )
		{
			$projectFlow	=	preg_replace( '/(' . "textblock" . $groupId . ')(,?)/', '', $project['flow'] );
			$deleteBlock	=	$clerk->query_delete( "project_files", "WHERE id= '$groupId' AND project_id= '$projectId'" );
			$updateFlow		=	$clerk->query_edit( "projects", "flow= '$projectFlow'", "WHERE id= '$projectId'" );
			
			if ( $deleteBlock && $updateFlow )
			{
				echo "true";
			}
			else
			{
				echo "false";
			}
		}
		else
		{
			$projectFlow	=	preg_replace( '/(' . "group" . $groupId . ')(:*)([a-zA-Z0-9]+)?(,?)/', '', $project['flow'] );
			
			// Get all files, delete
			$getFiles= $clerk->query_select( "project_files", "", "WHERE project_id= '$projectId' AND filegroup= '$groupId'" );
			while ( $file= $clerk->query_fetchArray( $getFiles ) )
			{
				if ( file_exists( $paths['path'] . $project['slug'] . "/" . $file['file'] ) )
					unlink( $paths['path'] . $project['slug'] . "/" . $file['file'] );
			}
			
			$deleteFiles	=	$clerk->query_delete( "project_files", "WHERE filegroup= '$groupId' AND project_id= '$projectId'" );
			$updateFlow		=	$clerk->query_edit( "projects", "flow= '$projectFlow'", "WHERE id= '$projectId'" );
			
			if ( $deleteFiles && $updateFlow )
			{
				echo "true";
			}
			else
			{
				echo "false";
			}
		}
	}
	
	function newSection()
	{
		global $clerk;
		
		$name	=	$_POST['name'];
		$slug	=	$clerk->simple_name( $name );
		$pos	=	$clerk->query_countRows( "project_sections" ) + 1;
		
		if ( $clerk->query_insert( "project_sections", "name, slug, pos", "'$name', '$slug', '$pos'" ) )
		{
			$id= $clerk->lastID();
			$html.= '
						<div class="controls">
							<ul>
								<li class="edit">
									<a href="#" onclick="editSection(\'' . $id . '\', \'' . $name . '\', \'' . $slug . '\'); return false;">Edit Section</a>
								</li>
								<li class="delete">
									<a href="#" onclick="deleteSection(' . $id . '); return false;">Delete Section</a>
								</li>
							</ul>
						</div>
			';
			
			// section controls anchor
		
			echo json_encode(
				array(
					"id"	=>	$id,
					"name"	=>	$name,
					"slug"	=>	$slug,
					"pos"	=>	$pos,
					"controls"	=> $html
				)
			);
		}
	}
	
	function editSection()
	{
		global $clerk;
		
		$id		=	$_POST['id'];
		$name	=	$_POST['name'];
		$slug	=	( empty( $_POST['slug'] ) == false ) ? $clerk->simple_name( $_POST['slug'] ) : $clerk->simple_name( $name );
		
		$action	= 	$clerk->query_edit( "project_sections", "name= '$name', slug= '$slug'", "WHERE id= '$id'" );
		
		echo json_encode( array( "success" => $action, "name" => $name, "slug" => $slug ) );
	}
	
	function orderProjects()
	{
		global $clerk;
		
		$section	= 	$_POST['section'];
		parse_str( str_replace( "&amp;", "&", $_POST['fileOrder'] ) );
		
		$count= 0;
		$total= count( $project );
		foreach ( $project as $p )
		{
			$count++;
			$ok= $clerk->query_edit( "projects", "section= '$section', pos= '$count'", "WHERE id= '$p'" );
		}
		
		echo $ok;
	}
	
	function orderSections()
	{
		global $clerk;
		
		parse_str( str_replace( "&amp;", "&", $_POST['fileOrder'] ) );
		
		$count= 0;
		$total= count( $section );
		foreach ( $section as $s )
		{
			$count++;
			$ok= $clerk->query_edit( "project_sections", "pos= '$count'", "WHERE id= '$s'" );
		}
		
		echo $ok;
	}
	
	function deleteSection()
	{
		global $clerk, $paths;
		
		$paths= $clerk->getSetting( "projects_path" );
		$paths= array( 	'path' 	=>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$section=	$_POST['section'];
		
		$projects= array();
		$filesToDelete= array();
		
		$getProjects= $clerk->query_select( "projects", "", "WHERE section= '$section'" );
		while ( $project= $clerk->query_fetchArray( $getProjects ) )
		{
			$projects[$project['id']]= $project['slug'];
		}
		
		
		$totalProjects= count( $projects );
		$count=	0;
		$countFiles= 0;
		foreach ( $projects as $projId => $projSlug )
		{
			$count++;
			
			$getFiles= $clerk->query_select( "project_files", "", "WHERE project_id= '$projId'" );
			while ( $file= $clerk->query_fetchArray( $getFiles ) )
			{
				$countFiles++;
				
				$filesToDelete[]= $file['id'];
				$id= $file['id'];
				$filesWhere.= ( $countFiles == 1 ) ? "id= '$id'" : " OR id= '$id'";
			}
			
			// Delete project folder and files
			rmdirr( $paths['path'] . $projSlug );
		}
		
		// Remove from database
		if ( !empty( $filesWhere ) )
			$ok= $clerk->query_delete( "project_files", "WHERE $filesWhere" );
		$ok= $clerk->query_delete( "projects", "WHERE section= '$section'");
		$ok= $clerk->query_delete( "project_sections", "WHERE id= '$section'" );
		
		return $ok;
	}
	
	function setGroupDisplayer()
	{
		global $clerk;
		
		$projectId= $_POST['projectId'];
		
		$project= $clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '$projectId'" ) );
		$string= "group" . $_POST['groupId'] . ":" . trim( $_POST['displayer'] );
		$flow= preg_replace( '/group' . $_POST['groupId'] . '(:)*([a-zA-Z0-9-]+)?/', $string, $project['flow'] );
		
		if ( $clerk->query_edit( "projects", "flow= '$flow'", "WHERE id= '$projectId'" ) )
		{
			echo "true";
		}
		else
		{
			echo "false";
		}
	}
	
	function deleteProjThumbnail()
	{
		global $clerk;
		
		$paths= $clerk->getSetting( "projects_path" );
		$paths= array( 	'path' 	=>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$projectId= $_POST['project_id'];
		
		$project= $clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '$projectId'" ) );
		$thumbFile= $paths['path'] . $project['slug'] . "/" . $project['thumbnail'];
		
		if ( file_exists( $thumbFile ) && is_dir( $thumbFile ) == false )
		{
			unlink( $thumbFile );
		}
		
		$clerk->query_edit( "projects", "thumbnail= ''", "WHERE id= '$projectId'" );
	}
?>