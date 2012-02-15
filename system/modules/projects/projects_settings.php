<?php
	// Define anchors
	define_anchor( "projectSettingsAfterThumbnails" );
	define_anchor( "projectSettingsOtherSettings" );
	define_anchor( "projectSettingsAfter");
		
	// Define hooks
	hook( "form_main", "projectSettingsForm" );
	hook( "form_submit_primary", "submitButtons" );
	hook( "form_process", "processProjectSettingsForm" );
	
	// Functions
	function submitButtons()
	{
		global $manager;
		
		$manager->form->add_input( 'submit', 'submit', 'Save', 'save' );
	}
	
	function processProjectSettingsForm()
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
		
		$manager->clerk->updateSetting( "projects_path", array( $upload_path, $upload_url, "" ) );	
		
		$projThumbWidth		=	( empty( $_POST['projThumbWidth'] ) ) ? 0 : $_POST['projThumbWidth'];
		$projThumbHeight	= 	( empty( $_POST['projThumbHeight'] ) ) ? 0 : $_POST['projThumbHeight'];
		$fileThumbWidth		=	( empty( $_POST['fileThumbWidth'] ) ) ? 0 : $_POST['fileThumbWidth'];
		$fileThumbHeight	= 	( empty( $_POST['fileThumbHeight'] ) ) ? 0 : $_POST['fileThumbHeight'];
		$intelliScaling		=	( empty( $_POST['image_intelligentScaling'] ) ) ? 0 : $_POST['image_intelligentScaling'];
		$hideSections		= 	(int)$_POST['hideSections'];
		$hideFileInfo		= 	(int)$_POST['hideFileInfo'];
		$resizeProjThumb	=	(int)$_POST['resizeProjThumb'];
		
		$manager->clerk->updateSetting( "projects_thumbnailIntelliScaling", array( $_POST['projects_thumbnailIntelliScaling'] ) );
		$manager->clerk->updateSetting( "projects_fullsizeimg", array( $_POST['fullsizeimg_width'] . "x" . $_POST['fullsizeimg_height'], $_POST['fullsizeimg_intelli'], $_POST['fullsizeimg_do_scale'] ) );
		
		$nav_opts= serialize(
			array(
				'prev'		=>	$_POST['prev'],
				'divider'	=>	$_POST['divider'],
				'next'		=> 	$_POST['next'],
				'of'		=>	$_POST['of'],
				'nav_pos'	=>	$_POST['slideshow_nav_pos'],
				'fx'		=>	$_POST['slideshow_fx']
			)
		);
		
		$manager->clerk->updateSetting( "slideshow_opts", array( $nav_opts ) );
		
		$updates= array(
			'projThumb'	=>	array(
				"data1= '$projThumbWidth', data2= '$projThumbHeight'",
				"WHERE name= 'projects_thumbnail'"
			),
			'fileThumb'	=>	array(
				"data1= '$fileThumbWidth', data2= '$fileThumbHeight'",
				"WHERE name= 'projects_filethumbnail'"
			),
			'projects_intelliscaling'	=>	array(
				"data1= '$intelliScaling'",
				"WHERE name= 'projects_intelliscaling'"
			),
			'hideSections'	=>	array(
				"data1= '$hideSections'",
				"WHERE name= 'projects_hideSections'"
			),
			'hideFileInfo'	=>	array(
				"data1= '$hideFileInfo'",
				"WHERE name= 'projects_hideFileInfo'"
			),
			'resizeProjThumb'=>	array(
				"data1= '$resizeProjThumb'",
				"WHERE name= 'resizeProjThumb'"
			)
		);
		
		$ok= true;		
		foreach ( $updates as $update )
		{
			if ( !$manager->clerk->query_edit( "global_settings", $update[0], $update[1] ) )
				$ok= false;
		}
		
		if ( $ok )
		{
			$manager->message( 1, false, "Settings updated!" );
		}
		else
		{
			$manager->message( 0, true, "Could not update all settings!" );
		}
	}
	
	function projectSettingsForm()
	{
		global $manager;
		
		$paths			=	$manager->clerk->getSetting( "projects_path" );
		$upload_path	=	$paths['data1'];
		$upload_url		=	$paths['data2'];
		
		$projThumb		=	$manager->clerk->getSetting( "projects_thumbnail" );
		$fileThumb		=	$manager->clerk->getSetting( "projects_filethumbnail" );
		$intelliScaling	=	$manager->clerk->getSetting( "projects_intelliscaling", 1 );
		$hideSections	=	$manager->clerk->getSetting( "projects_hideSections", 1 );
		$hideFileInfo	=	$manager->clerk->getSetting( "projects_hideFileInfo", 1 );
		$resizeProjThumb=	$manager->clerk->getSetting( "resizeProjThumb", 1 );
		$projects_thumbnailIntelliScaling= $manager->clerk->getSetting( "projects_thumbnailIntelliScaling", 1 );
		$fullsizeimg	=	$manager->clerk->getSetting( "projects_fullsizeimg" );
		$fullsizeimg_wh =	explode( "x", $fullsizeimg['data1'] );
		$slideshow_opts	=	unserialize( $manager->clerk->getSetting( "slideshow_opts", 1 ) );
		
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
		$manager->form->add_fieldset( "Project Thumbnails", "projectThumbnails" );
		$manager->form->add_input( "text", "projThumbWidth", "Width", $projThumb['data1'] );
		$manager->form->add_input( "text", "projThumbHeight", "Height", $projThumb['data2'] );
		$manager->form->set_template( "row_start", "row_start_blank", true );
		$manager->form->add_input( "checkbox", "resizeProjThumb", " ", $resizeProjThumb, array( "Resize project thumbnails" => 1 ), "", "" );
		$manager->form->add_input( "checkbox", "projects_thumbnailIntelliScaling", " ", $projects_thumbnailIntelliScaling, array( "Intelligent Scaling" => 1 ), "", "");
		
		call_anchor( "projectThumbnailSettings_Checkboxes" );
		
		$manager->form->reset_template( "row_start" );
		
		$manager->form->close_fieldset();
		
		$manager->form->add_fieldset( "File/Image Thumbnails", "fileThumbnails" );
		
		$manager->form->add_input( "text", "fileThumbWidth", "Width", $fileThumb['data1'] );
		$manager->form->add_input( "text", "fileThumbHeight", "Height", $fileThumb['data2'] );
		$manager->form->add_input( "checkbox", "image_intelligentScaling", " ", $intelliScaling, array( "Intelligent Scaling " => 1 ), "", "" );
		
		$manager->form->close_fieldset();
		
		$manager->form->add_fieldset( "Full-size Images", "fullsize_images" );

		$manager->form->add_input( "text", "fullsizeimg_width", "Width", $fullsizeimg_wh[0] );
		$manager->form->add_input( "text", "fullsizeimg_height", "Height", $fullsizeimg_wh[1] );
		$manager->form->add_input( "checkbox", "fullsizeimg_do_scale", " ", $fullsizeimg['data3'], array( "Resize Images  " => 1 ), "", "" );
		$manager->form->add_input( "checkbox", "fullsizeimg_intelli", " ", $fullsizeimg['data2'], array( "Intelligent Scaling  " => 1 ), "", "" );
		$manager->form->close_fieldset();
		
		$manager->form->add_to_form( '</div><div class="col-2 last">' );
		
		$manager->form->add_fieldset( "Other Settings", "otherSettings" );
		$manager->form->set_template( "row_start", "row_start_blank", true );
		$manager->form->set_template( "row_end", '<span class="caption">(depends on theme)</span></div>' );
		$manager->form->add_input( "checkbox", "hideSections", " ", $hideSections, array( 'Hide Section Titles' => 1 ) );
		$manager->form->reset_template( "row_end" );
		$manager->form->add_input( "checkbox", "hideFileInfo", " ", $hideFileInfo, array( "Hide File Captions & Titles" => 1 ) );
		$manager->form->reset_template( "row_start" );
		
		call_anchor( "projectSettingsOtherSettings" );
		
		$manager->form->close_fieldset();
		
		$manager->form->add_fieldset( "Slideshow Options", "slideshow_opts" );

		$manager->form->add_input( "radio", "slideshow_nav_pos", "Navigation Position", $slideshow_opts['nav_pos'], array( "Top" => "top", "Bottom" => "bottom" ) );
		
		$manager->form->add_to_form( '<div class="field"><label>Navigation</label><br />' );
		$manager->form->set_template( "row_start", "" );
		$manager->form->set_template( "row_end", "" );
		$manager->form->set_template( "text_template", "text_short_html5", true );
		
		$manager->form->add_input( "text", "prev", "Previous", $slideshow_opts['prev'], "" );
		$manager->form->add_input( "text", "divider", "Divider", $slideshow_opts['divider'], "" );
		$manager->form->add_input( "text", "next", "Next ", $slideshow_opts['next'], "" );
		$manager->form->add_input( "text", "of", "(# of total)", $slideshow_opts['of'], "" );
		$manager->form->add_to_form( '</div>' );
		
		$manager->form->reset_template( "text_template" );
		$manager->form->reset_template( "row_start" );
		$manager->form->reset_template( "row_end" );
		
		$transitions= array(
				'Fade'				=>	'fade',
				'Blind Horizontal'	=>	'blindX',
				'Blind Vertical'	=>	'blindY',
				'Scroll Up'			=>	'scrollUp',
				'Scroll Down'		=>	'scrollDown',
				'Scroll Left'		=>	'scrollLeft',
				'Scroll Right'		=>	'scrollRight',
				'Uncover'			=>	'uncover',
				'None'				=>	'none',
		);
		$manager->form->add_select( "slideshow_fx", "Transition", $transitions, $slideshow_opts['fx'] );
		
		$manager->form->close_fieldset();
		
		$manager->form->add_to_form( '</div>' );
		
		call_anchor( "projectSettingsAfter" );
	}
?>