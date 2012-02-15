<?php
	// Load required helpers
	$manager->load_helper( "interface" );
	$manager->load_helper( "file_uploader.inc" );
	$manager->load_helper( "ThumbLib.inc" );
	
	// Anchors
	define_anchor( "modifyPostThumb" );
	define_anchor( "modifyPostThumbAfterSave" );
	define_anchor( "blogOverviewToolbar" );
	
	// Define hooks
	if ( $_GET['mode']== "edit" )
	{
		hook( "form_process", "processEditBlogForm", array( $blogFileTypes ) );	
		
		$id= $_GET['id'];
		$post= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "secretary_blog", "", "WHERE id= '$id' LIMIT 1") );
			
		if ( $_POST['submit'] != "delete" )
		{
			hook( "breadcrumbActive", "postTitle", array( $post ) );
			hook( "form_submit_primary", "submitButtons", array(0) );
			hook( "form_submit_secondary", "submitButtons", array( 1, $post ) );		
			hook( "form_main", "editBlogForm", array( $blogFileTypes ) );
		}
	}
	elseif ( $_GET['mode'] == "delete" && !empty( $_GET['id'] ) )
	{
		hook( "big_message", "postDelete" );
	}
	
	if ( $_GET['action'] == "deleteImage" && !empty( $_GET['id'] ) )
	{
		hook( "big_message", "postDelete", array( true ) );
	}
	
	if ( ( $_GET['mode'] == "edit" && $_POST['submit'] == "delete" ) || $_GET['mode'] != "edit" )
	{	
		hook( "action_bar", "blogOverviewToolbar", "", 1);
		hook( "form_main", "posts", "", 2 );
	}
	
	hook( "javascript", "blogJs" );
	hook( "form_main", "hiddenFields" );
	
	// Functions
	function postTitle( $post )
	{
		echo $post['title'];
	}
	
	function posts()
	{
		global $manager;
		
		echo '<div id="overview">';
		
		$get= $manager->clerk->query_select( "secretary_blog", "", "ORDER BY date DESC" );
		
		if ( $manager->clerk->query_numRows( $get ) == 0 )
		{
			echo 'You have no blog posts! Click the <strong>New Post</strong> button above to get started.';
			return;
		}
		
		while ( $data= $manager->clerk->query_fetchArray( $get ) )
		{
			$edit_url= $manager->office->URIquery( "id", "mode=edit&id=" . $data['id'] );
			$delete_url= $manager->office->URIquery( "id", "mode=delete&id=" . $data['id'] );
			$html.= '
					<div class="post"><div class="controls inline mini"><ul><li class="edit"><a href="' . $edit_url . '">Edit</a><li class="delete"><a href="' . $delete_url . '">Delete</a></li></ul></div><div class="left"><a href="' . $edit_url . '"><span class="title">' . $data['title'] . '</span></a><span class="date">' . date( "d. F Y", $data['date'] ) . '</span></div>
					</div>';
		}
		
		echo $html;
		echo '</div>';
	}
	
	function blogOverviewToolbar()
	{
		global $manager;
		
		$tools= array(
						'<a href="#" onclick="newPost(); return false;" class="button-new">New Post</a>',
		);
		
		$tools= call_anchor( "blogOverviewToolbar", $tools );
		
		$toolbar= new Toolbar(
			array(
				"tools"	=>	$tools
			)
		);
		
		
		echo $toolbar->html;
	}

	function blogJs()
	{
		global $manager;

		echo $manager->load_jshelper( 'quicktags' );
	}
	
	function postDelete( $imageOnly= false )
	{
		global $manager;

		$id= $_GET['id'];
		
		$post= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "secretary_blog", "", "WHERE id= '$id' LIMIT 1") );
		
		$currentImage= $post['image'];
		
		$paths= $manager->getSetting( "blog_path" );
		$paths= array(
						'path' =>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$postFolder= $paths['path'] . $post['slug'];
		
		rmdirr( $postFolder );

		if ( $imageOnly == true )
		{
			$manager->clerk->query_edit( "secretary_blog", "image= ''", "WHERE id='$id'");
			$manager->message( 1, false, "Image deleted!" );
		}
		elseif ( $imageOnly == false )
		{
			if ( $manager->clerk->query_delete( "secretary_blog", "WHERE id= '$id'" ) )
			{
				$manager->message( 1, false, "Post <em>$title</em> deleted!" );
			}
			else
			{
				$manager->message( 0, true, "Could not delete post!" );
			}
		}
	}
	
	function processEditBlogForm( $blogFileTypes )
	{
		global $manager;
		
		$id			=	$_POST['id'];
		$title 		=	$_POST['title'];
		$slug 		= 	( empty( $_POST['slug'] ) ) ? $manager->clerk->simple_name( $title ) : $_POST['slug'];
		$oldSlug	=	$_POST['oldslug'];
		$now		= 	getdate( $_POST['date_timestamp'] );
		$date		= 	mktime( $now["hours"], $now["minutes"], $now["seconds"], $_POST["date_month"], $_POST["date_day"], $_POST["date_year"] );
		$post 		=	$_POST['post'];
		$currentImage= 	$_POST['currentImage'];
		$status		=	$_POST['status'];
		
		$paths= $manager->getSetting( "blog_path" );
		$paths= array(
						'path' =>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$postFolder= $paths['path'] . $oldSlug . '/';
				
		if ( $_POST['submit'] == "save" )
		{
			if ( $manager->clerk->query_countRows( "secretary_blog", "WHERE slug= '$slug' AND id != '$id'" ) >= 1 )
			{
				$manager->message( 0, false, 'A post with the simple name/slug <em>"' . $slug . '"</em> already exists! Please choose a new one.' );
				return false;
			}
			
			// Handle image
			if ( !is_dir( $postFolder ) )
			{
				mkdir( $postFolder, 0777 );
			}
			
			$thumbWidth		= 	$manager->clerk->getSetting( "blog_thumbnail", 1 );
			$thumbHeight	= 	$manager->clerk->getSetting( "blog_thumbnail", 2 );
			$intelliscaling	=	(boolean) $manager->clerk->getSetting( "blog_intelliscaling", 1 );
			$forceAdaptive	= 	( $thumbWidth == 0 || $thumbHeight == 0 ) ? true : false;
			
			$newImage= true;
			foreach ( $_FILES['image']['name'] as $key => $val )
			{
				if ( empty( $val ) )
				{
					$newImage= false;
					continue;
				}
								
				$file_extension= substr( basename( $val ), strrpos( basename( $val ), '.' ) );
				$image= $manager->clerk->simple_name( str_replace( $blogFileTypes, "", basename( $val ) ) ) . $file_extension;
				
				if ( $image == $currentImage )
					$newImage= false;
				
				$upload			=	upload( 'image', $key, $postFolder, implode( ",", $blogFileTypes ) );
				$upload_file	= 	$upload[0];
				$upload_error	=	$upload[1];
				
				rename( $postFolder . $upload_file, $postFolder . $image );
				
				if ( empty( $upload_error ) )
				{
					$thumb 			=	PhpThumbFactory::create( $postFolder . $image );
					$thumbnail_name	=	str_replace( $blogFileTypes, "", $image ) . ".thumb" . substr( $image, strrpos( $image, '.' ) );
					
					if ( $intelliscaling == true && $forceAdaptive == false )
					{
						$thumb->adaptiveResize( $thumbWidth, $thumbHeight );
					}
					else
					{
						$thumb->resize( $thumbWidth, $thumbHeight );
					}
					
					call_anchor( "modifyPostThumb", $thumb );
					$thumb->save( $postFolder . $thumbnail_name );
					call_anchor( "modifyPostThumbAfterSave", array( $thumb, ( $postFolder . $thumbnail_name ) ) );
				}
			}
			
			if ( $newImage == false )
				$image= $currentImage;
				
			if ( $newImage && file_exists( $postFolder . $currentImage ) && is_file( $postFolder . $currentImage ) )
			{
				$currentImageThumb=	str_replace( $blogFileTypes, "", $currentImage ) . ".thumb" . substr( $currentImage, strrpos( $currentImage, '.' ) );
				unlink( $postFolder . $currentImage );
				unlink( $postFolder . $currentImageThumb );
			}
			
			if ( empty( $upload['error'] ) && $manager->clerk->query_edit( "secretary_blog", "title= '$title', slug= '$slug', date= '$date', post= '$post', image= '$image', status='$status'", "WHERE id= '$id'" ) )
			{
				$manager->message( 1, false, "Post <em>$title</em> saved!" );
				
				// Handle renaming
				if ( $slug != $oldSlug && is_dir( $paths['path'] . $oldSlug ) )
				{
					rename( $paths['path'] . $oldSlug, $paths['path'] . $slug );
				}
				
				if ( $newImage )
				{
					$pic= PhpThumbFactory::create( $paths['path'] . $slug . '/' . $image );
					$pic->resize( 400, 0 );
					$pic->save( $paths['path'] . $slug . '/' . $image );
				}
			}
			else
			{
				$manager->message( 0, true, "This post could not be saved!" );
			}
		}
		elseif ( $_POST['submit'] == "delete" )
		{
			rmdirr( $paths['path'] . $oldSlug );
			
			if ( file_exists( $postFolder . '/' . $currentImage ) && is_file( $postFolder . '/' . $currentImage ) )
				unlink( $postFolder . '/' . $currentImage );
			
			if ( $manager->clerk->query_delete( "secretary_blog", "WHERE id= '$id'" ) )
			{
				$manager->message( 1, false, "Post <em>$title</em> deleted!" );
			}
			else
			{
				$manager->message( 0, true, "Could not delete post!" );
			}
		}
	}
	
	function submitButtons( $loc, $post )
	{
		global $manager;
		
		if ( $loc == 0 )
			$manager->form->add_input( 'submit', 'submit', 'Save Changes', 'save' );

		if ( $loc == 1 )
		{
			$manager->form->add_input( 'submit', 'submit', 'Delete', 'delete' );

			if ( !empty( $post['image'] ) )
				$manager->form->add_input( 'submit', 'submit', 'Delete Image', 'deleteImage' );
		}
	}

	function editBlogForm( $blogFileTypes )
	{
		global $manager;
		
		// Define required variables
		$id= $_GET['id'];
		$post= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "secretary_blog", "", "WHERE id= '$id' LIMIT 1" ) );
				
		$paths= $manager->getSetting( "blog_path" );
		$paths= array(
						'path' =>	$paths['data1'],
						'url'	=>	$paths['data2']
		);
		
		$currentImage= ( empty( $post['image'] ) ) ? "" : '<img src="' . $paths['url'] . $post['slug'] . '/' . $post['image'] . '" alt="" />';
		$deleteLink= ( empty( $post['image'] ) ) ? " hide" : "";
			
		// Rules
		$manager->form->add_rule( "title" );
		
		// Begin Form				
		$manager->form->add_fieldset( "Post Details", "postDetails" );
		
		$manager->form->add_input( "hidden", "id", NULL, $id );
		$manager->form->add_input( "hidden", "oldslug", NULL, $post['slug'] );
		
		$manager->form->add_input( "text", "title", "Title", $post['title'] );
		
		$manager->form->add_input( "text", "slug", "Simple Name / Slug", $post['slug'] );
		
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

		$manager->form->add_select( "date_month", NULL, $months, date( "F", $post['date'] ) );

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
		$manager->form->add_select( "date_day", NULL, $days, date( "j", $post['date'] ) );

		$current_year= date( "Y" );
		for ( $i= 2000; $i <= $current_year + 5 ; $i++ ) {
			$year= $i." ";
			$years[$year]= $i;
		}

		$manager->form->add_input( "text", "date_year", NULL, date( "Y", $post['date'] ) );
		$manager->form->add_input( "hidden", "date_timestamp", "", $post['date'] );

		// 
		$manager->form->add_to_form( '</div>' );
		
		$manager->form->reset_template( "text_template" );
		$manager->form->reset_template( "option_template" );
		$manager->form->reset_template( "select_template" );

		$manager->form->reset_template( "row_end" );
		
		$manager->form->set_template( "textarea_template", "textareaWithEditor", true );
		$manager->form->add_textarea( "post", "Post", "15", "125", $post['post'] );
		$manager->form->reset_template( "textarea_template" );
		
		$manager->form->add_input( "checkbox", "status", " ", $post['status'], array( "Publish" => 1 ) );
		
		$manager->form->close_fieldset();
		
		//
		 
		$manager->form->add_fieldset( "Post Image", "postImage" );
		$manager->form->add_input( "file", "image", "Select an image to attach to this post (" . implode( $blogFileTypes, ", " ) . " / <strong>Max " . str_replace( "M", "mb", ini_get( "upload_max_filesize" ) ) . "</strong>)" );

		if ( empty( $post['image'] ) == false )
		{
			$manager->form->add_to_form( '<img src="' . $paths['url'] . $post['slug'] . '/' . $post['image'] . '" alt="" />' );
		}
		
		$manager->form->add_input( "hidden", "currentImage", "", $post['image'] );
		$manager->form->close_fieldset();
	}
?>