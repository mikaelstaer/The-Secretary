<?php
	// Define anchors
	define_anchor( "blogSettingsAfterThumbnails" );
	define_anchor( "blogSettingsOtherSettings" );
	define_anchor( "blogSettingsAfter");
		
	// Define hooks
	hook( "form_main", "blogSettingsForm" );
	hook( "form_submit_primary", "submitButtons" );
	hook( "form_process", "processBlogSettingsForm" );
		
	// Functions
	function submitButtons()
	{
		global $manager;
		
		$manager->form->add_input( 'submit', 'submit', 'Save', 'save' );
	}
	
	function processBlogSettingsForm()
	{
		global $manager;
		
		$upload_path	=	$_POST['upload_path'];
		$upload_url		=	"http://".str_replace( "http://", "", $_POST['upload_url'] );
		$old_uploadpath	=	$_POST['old_uploadpath'];
		
		// Prepare path and url...
		if ( strrchr( $upload_path, "/" ) != "/" )
		{
			$upload_path.= "/";
		}
		
		if ( strrchr( $upload_url, "/" ) != "/" )
		{
			$upload_url.= "/";
		}
		
		if ( $upload_path != $old_uploadpath )
		{
			// Need to move files then...
			dircopy( $old_uploadpath, $upload_path );
			rmdirr( $old_uploadpath );
		}
		
		$manager->clerk->updateSetting( "blog_path", array( $upload_path, $upload_url, "" ) );
		
		$thumbWidth		=	( empty( $_POST['thumbWidth'] ) ) ? 0 : $_POST['thumbWidth'];
		$thumbHeight	= 	( empty( $_POST['thumbHeight'] ) ) ? 0 : $_POST['thumbHeight'];
		$intelliScaling	=	$_POST['intelligentScaling'];
		
		$manager->clerk->updateSetting( "blog_thumbnail", array( $thumbWidth, $thumbHeight, "" ) );
		$manager->clerk->updateSetting( "blog_intelliscaling", array( $intelliScaling, "", "" ) );
		
		$manager->message( 1, false, "Settings updated!" );
	}
	
	function blogSettingsForm()
	{
		global $manager;
		
		$paths			=	$manager->clerk->getSetting( "blog_path" );
		$upload_path	=	$paths['data1'];
		$upload_url		=	$paths['data2'];
		
		$thumbnail		=	$manager->clerk->getSetting( "blog_thumbnail" );
		$intelliScaling	=	$manager->clerk->getSetting( "blog_intelliscaling", 1 );
		
		// Rules
		$manager->form->add_rule( "upload_path" );
		$manager->form->add_rule( "upload_url" );
		
		// Begin form
		$manager->form->add_to_form( '<div class="col-4 last">' );
		$manager->form->add_fieldset( "File Storage", "fileStorage" );

		$manager->form->add_input( "text", "upload_path", "Upload Path", $upload_path, "", "", "This is the <strong>absolute path</strong> to the folder where images should be uploaded to. It should look something like this: <em>/home/user/mydomain.com/files/</em>" );
		$manager->form->add_input( "text", "upload_url", "Upload URL", $upload_url, "", "", "This is the <strong>URL</strong> to the folder where images should be uploaded to. It should look something like this: <em>http://www.mydomain.com/files/</em>." );
		
		$manager->form->add_input( "hidden", "old_uploadpath", "", $upload_path );
		
		$manager->form->close_fieldset();		
		
		$manager->form->add_to_form( '</div><div class="col-2">' );
		$manager->form->add_fieldset( "Post Thumbnails", "postThumbnails" );
		$manager->form->add_input( "text", "thumbWidth", "Width", $thumbnail['data1'] );
		$manager->form->add_input( "text", "thumbHeight", "Height", $thumbnail['data2'] );
		$manager->form->add_input( "checkbox", "intelligentScaling", " ", $intelliScaling, array( "Intelligent Scaling" => 1 ), "", "" );
		
		call_anchor( "blogSettingsAfterThumbnails" );
		
		$manager->form->close_fieldset();
	
		call_anchor( "blogSettingsOtherSettings" );
			
		$manager->form->add_to_form( '</div>' );
		
		call_anchor( "blogSettingsAfter" );
	}
?>