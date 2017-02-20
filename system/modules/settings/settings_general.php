<?php
	define_anchor( "prefsSiteSettings" );
	define_anchor( "prefsPersonalSettings" );
	define_anchor( "prefsCol1" );
	define_anchor( "prefsCol2" );
	define_anchor( "prefsMisc" );
	
	hook( "form_main", "prefsForm" );
	hook( "form_process", "processPrefsForm" );
	hook( "form_submit_primary", "submitButtons" );
	hook( "big_message", "emptyCache" );
	
	// Functions
	function emptyCache()
	{
		global $manager;
		
		if ( (bool) $_GET['emptycache'] == false )
			return;
		
		emptyDir( $manager->clerk->getSetting( "cache_path", 1 ) );
		
		$manager->message( 1, false, "Cache folder emptied!" );
	}
	
	function processPrefsForm()
	{
		global $manager;
		
		$username		=	$_POST['username'];
		$name			=	$_POST['display_name'];
		$password		=	$_POST['password'];
		$passwordConf	=	$_POST['password_conf'];
		$email			=	$_POST['email'];
		$siteName		=	$_POST['site_name'];
		$siteUrl		=	$_POST['site_url'];
		$cleanUrls		=	$_POST['clean_urls'];
		$cache_path		=	$_POST['cache_path'];
		$cache_url		=	$_POST['cache_url'];
		$old_cache_path	=	$_POST['old_cache_path'];
		
		// User wants to change their password
		if ( !empty($_POST['password']) && !empty($_POST['password_conf']) )
		{
			if ( $_POST['password'] != $_POST['password_conf'] )
			{
				$manager->message( 0, false, "Password do not match! Please re-confirm." );
			}
			elseif ( strlen($_POST['password']) < 6 || strlen($_POST['password_conf']) < 6 )
			{
				$manager->message( 0, false, "Password must be at least 6 characters long!" );
			}
			else 
			{
				//hash password securely
				if ( $manager->clerk->query_edit( "users", "password= '" . password_hash( $password, PASSWORD_DEFAULT) . "'", "WHERE id= '" . $manager->guard->user('USER_ID') . "'" ) )
					$manager->message( 1, false, "Password changed!" );
				else
					$manager->message( 0, true, "Could not save your Settings!" );
			}
		
		}

		$personal	=	$manager->clerk->query_edit( "users", "username= '$username', display_name= '$name', email= '$email'", "WHERE id= '".$manager->guard->user('USER_ID')."'" );
		
		$manager->clerk->updateSetting( "site", array( $siteName, $siteUrl ) );
		
		if ( strrchr( $cache_path, "/" ) != "/" )
			$cache_path.= "/";
		
		if ( strrchr( $cache_url, "/" ) != "/" )
			$cache_url.= "/";
		
		if ( $cache_path != $old_cache_path )
		{
			// Need to move files then...
			dircopy( $old_cache_path, $cache_path );
			rmdirr( $old_cache_path );
		}
		
		$manager->clerk->updateSetting( "clean_urls", array( $cleanUrls ) );
		$manager->clerk->updateSetting( "cache_path", array( $cache_path, $cache_url ) );
		
		if ( $personal )
			$manager->message( 1, false, "Settings saved!" );
		else
			$manager->message( 0, true, "Could not save your Settings!" );
	}
	
	function prefsForm()
	{
		global $manager;
		
		// Variables
		$self= $manager->clerk->query_fetchArray( $manager->clerk->query_select( "users", "", "WHERE username= '".$manager->guard->user('USERNAME')."' AND password= '".$manager->guard->user('PASSWORD')."'") );
		$site= $manager->clerk->getSetting( "site" );
		$site= array(
			'name'	=>	$site['data1'],
			'url'	=>	$site['data2']
		);
		$cleanUrls= ( $manager->form->submitted() ) ? $_POST['clean_urls'] : $manager->clerk->getSetting( "clean_urls", 1 );
		$cache_path= $manager->clerk->getSetting( "cache_path", 1 );
		$cache_url= $manager->clerk->getSetting( "cache_path", 2 );
		
		// Rules
		$manager->form->add_rule( "email", "/^[A-Z0-9._%\-]+@[A-Z0-9._%\-]+\.[A-Z]{2,4}$/i", "", $manager->form->e_message['email'] );
		$manager->form->add_rule( "site_name" );
		$manager->form->add_rule( "site_url" );
		$manager->form->add_rule( "cache_path" );
		$manager->form->add_rule( "cache_url" );
		
		// Begin form
		// $manager->form->add_to_form( '<div class="col-2">' );
		
		$manager->form->add_fieldset( "Personal Settings", "personalSettings" );
		$manager->form->add_input( "text", "username", "Username", $self['username'] );
		$manager->form->add_input( "text", "display_name", "Name", $self['display_name'] );
		$manager->form->add_input( "password", "password", "New Password" );
		$manager->form->add_input( "password", "password_conf", "Confirm Password" );
		$manager->form->add_input( "text", "email",  "E-mail*", $self['email'] );
		
		call_anchor( "prefsPersonalSettings" );
		
		$manager->form->close_fieldset();
		
		call_anchor( "prefsCol1" );
		
		// $manager->form->add_to_form( '</div><div class="col-2 last>"' );
		
		$manager->form->add_fieldset( "Site Settings", "siteSettings" );
		$manager->form->add_input( "text", "site_name", "Site Name", $site['name'] );
		$manager->form->add_input( "text", "site_url", "Site URL", $site['url'] );
		$manager->form->add_input( "checkbox", "clean_urls", " ", $cleanUrls, array( "Clean URLs" => 1 ) );
		
		call_anchor( "prefsSiteSettings" );
		
		$manager->form->close_fieldset();
		
		$manager->form->add_fieldset( "Cache", "cache" );
		$manager->form->add_input( "text", "cache_path", "Cache Path", $cache_path, "", "", "This is the <strong>absolute path</strong> to the folder where cached files should be saved to. It should look something like this: <em>/home/user/mydomain.com/files/cache/</em>" );
		$manager->form->add_input( "text", "cache_url", "Cache URL", $cache_url, "", "", "This is the <strong>URL</strong> to the folder where cached files should be saved to. It should look something like this: <em>http://www.mydomain.com/files/cache/</em>." );
		$manager->form->add_input( "hidden", "old_cache_path", "", $cache_path );
		
		$manager->form->add_to_form( 'Empty Cache Folders: <span class="delete singleButton"><a href="' . $manager->office->URIquery( "", "emptycache=true") . '">EMPTY</a></span>' );
		$manager->form->close_fieldset();
		
		call_anchor( "prefsCol2" );
		
		// $manager->form->add_to_form( '</div>' );
		
		call_anchor( "prefsMisc" );
		
	}
	
	function submitButtons()
	{
		global $manager;
		
		$manager->form->add_input( "submit", "submit", "Save", "save" );
	}
?>
