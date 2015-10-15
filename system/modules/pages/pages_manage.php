<?php
	// Define anchors
	define_anchor( "pages_manage_after_text" );
	define_anchor( "pages_manage_after_type" );
	define_anchor( "pageContentTypes" );
	
	// Load required helpers
	$manager->load_helper( "interface" );
	
	// Define hooks
	if ( $_GET['mode'] == "edit" )
	{
		$id= $_GET['id'];
		$data= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "pages", "", "WHERE id= '$id' LIMIT 1") );
		
		hook( "form_process", "processForm" );
		
		if ( $_POST['submit'] != "delete" )
		{
			hook( "breadcrumbActive", "breadCrumbActive", array( $data ) );
			hook( "form_submit_primary", "submitButtons", array(0) );
			hook( "form_submit_secondary", "submitButtons", array(1) );
			hook( "form_main", "editForm" );
		}
	}
	elseif ( $_GET['mode'] == "delete" && !empty( $_GET['id'] ) )
	{
		hook( "big_message", "pageDelete" );
	}
	
	if ( ( $_GET['mode'] == "edit" && $_POST['submit'] == "delete" ) || $_GET['mode'] != "edit" )
	{
		hook( "action_bar", "page_overview_toolbar", "", 1);
		hook( "form_main", "listPages", "", 2 );
	}
	
	hook( "javascript", "formJavascript" );
	hook( "head_tags", "formCss" );
	hook( "form_main", "hiddenFields" );
		
	// Functions
	function hiddenFields()
	{
		global $manager;
		
		echo '<input type="hidden" name="asstPath" id="asstPath" value="' . $manager->clerk->config('ASSISTANTS_PATH') . '" />';
	}
	
	function breadCrumbActive( $data )
	{
		echo $data['name'];
	}
	
	function listPages()
	{
		global $manager;
		
		$get= $manager->clerk->query_select( "pages", "", "ORDER BY pos ASC" );
		
		echo '<ul id="pageList">';
		while ( $data= $manager->clerk->query_fetchArray( $get ) )
		{
			$edit_url= $manager->office->URIquery( "id", "mode=edit&id=" . $data['id'] );
			$delete_url= $manager->office->URIquery( "id", "mode=delete&id=" . $data['id'] );
			echo '<li class="item" id="page_' . $data['id'] . '"><div class="controls inline mini"><ul><li class="handle">Drag</li><li class="edit"><a href="' . $edit_url . '">Edit</a></li><li class="delete"><a href="' . $delete_url . '">Delete</a></li></ul></div><a href="' . $edit_url . '" class="title">' . $data['name'] . '</a></li>';
		}
		echo '</div>';
	}
	
	function page_overview_toolbar()
	{
		global $manager;
		
		$toolbar= new Toolbar(
			array(
				"tools"	=>	array('<a onclick="newPage();" class="button-new">New Page</a>')
			)
		);
		
		echo $toolbar->html;
	}
	
	function formCss()
	{
		echo '<link rel="stylesheet" href="' . SYSTEM_URL . 'modules/pages/assets/styles.css" type="text/css" media="screen" charset="utf-8">'."\n";
	}

	function formJavascript()
	{
		global $manager;
		
		echo $manager->load_jshelper( 'quicktags' );
	}
	
	function pageDelete()
	{
		global $manager;
		
		$id= $_GET['id'];
		$data= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "pages", "", "WHERE id= '$id' LIMIT 1") );
		
		if ( $manager->clerk->query_delete( "pages", "WHERE id= '$id'" ) )
		{
			$manager->message( 1, false, "Page <em>{$data['name']}</em> deleted!" );
		}
		else
		{
			$manager->message( 0, true, "Could not delete page!" );
		}
	}
	
	function processForm()
	{
		global $manager;
				
		$id					=	$_POST['id'];
		$name 				=	$_POST['name'];
		$slug 				= 	$_POST['slug'];
		$newSlug			= 	( empty( $slug ) ) ? $manager->clerk->simple_name( $name ) : $slug;
		$url				=	$_POST['url'];
		$text				=	$_POST['text'];
		$content_options	=	$_POST['content_options'];
		$content_type		=	$_POST['content_type'];
		$hidden				=	$_POST['hidden'];
		
		if ( $_POST['submit'] == "save" )
		{
			if ( $manager->clerk->query_countRows( "pages", "WHERE name= '$name' AND slug= '$slug' AND id != '$id'" ) >= 1 )
			{
				$manager->message( 0, false, "A page with the name <em>$name</em> already exists! Please choose a different name." );
				return false;
			}
			
			$updatePage= $manager->clerk->query_edit( "pages", "name= '$name', slug= '$newSlug', url= '$url', text= '$text', content_type= '$content_type', content_options= '$content_options', hidden= '$hidden'", "WHERE id= '$id'" );
			
			if ( $updatePage )
			{
				
				$manager->message( 1, false, "Page <em>$name</em> saved!" );
			}
			else
			{
				$manager->message( 0, true, "This page could not be saved!" );
			}
		}
		elseif ( $_POST['submit'] == "delete" )
		{						
			if ( $manager->clerk->query_delete( "pages", "WHERE id= '$id'" ) )
			{
				$manager->message( 1, false, "Page <em>$name</em> deleted!" );
			}
			else
			{
				$manager->message( 0, true, "Could not delete page!" );
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
	
	function editForm()
	{
		global $manager;
		
		// Define required variables		
		$id= $_GET['id'];
		$data= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "pages", "", "WHERE id= '$id' LIMIT 1") );
		
		// Rules
		$manager->form->add_rule( "name" );
		
		// Begin form
		$manager->form->add_to_form( '<div class="col-2">' );
		$manager->form->add_fieldset( "Page Details", "pageDetails" );
		
		$manager->form->add_input( "hidden", "id", NULL, $id );
		
		$manager->form->add_input( "text", "name", "Name", $data['name'] );
		$manager->form->add_input( "text", "slug", "Simple Name / Slug", $data['slug'] );
		$manager->form->add_input( "text", "url", "Link", $data['url'], "", "", "Use this option to override this page's link in your menu. This is useful if you want to link to an external blog, forum or page from within your menu." );
		
		$manager->form->set_template( "textarea_template", "textareaWithEditor", true );
		$manager->form->add_textarea( "text", "Text", "15", "", $data['text'] );
		$manager->form->reset_template( "textarea_template");
		
		call_anchor( "pages_manage_after_text", $data );
		
		$manager->form->add_input( "checkbox", "hidden", " ", $data['hidden'], array( "Hide from menu" => 1 ) );
		
		$manager->form->close_fieldset();

		$manager->form->add_to_form( '</div><div class="col-2 last">' );
		
		$manager->form->add_fieldset( "Page Type", "pageType" );
		
		$manager->form->set_template( "text_template", "textSuperLong", true );
		$manager->form->set_template( "select_template", "selectLong", true );
		
		$contentTypes= array(
								"None" => "none"
		);
		
		foreach ( $manager->office->getMenu() as $key => $module )
		{
			if ( $module['type'] == "content" )
			{
				$contentTypes[$module['dis_name']]= $module['sys_name'];
			}
		}
		
		$contentTypes= call_anchor( "pageContentTypes", $contentTypes );
		$selectedType= ( empty( $data['content_type'] ) ) ? "None" : $data['content_type'];
		
		$manager->form->add_select( "content_type", "Type", $contentTypes, $selectedType, "Choose the type of content you would like to attach to this page." );
		
		$manager->form->add_input( "text", "content_options", "Options", $data['content_options'], "", "Enter a comma separated list of options" );
		
		$manager->form->reset_template( "text_template" );
		$manager->form->reset_template( "select_template" );
		
		$manager->form->message( 'Need help with this? Read the <a href="http://help.thesecretary.org/kb/faq/what-is-the-page-type-and-how-do-i-use-it" class="external">how to</a> in the User Guide.' );
		
		call_anchor( "pages_manage_after_type", $data );
		
		$manager->form->close_fieldset();
		$manager->form->add_to_form( '</div>' );
	}
?>
