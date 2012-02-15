<?php
	header('Content-type: text/html; charset=utf-8');
	
	if ( !isset($_GET['debug']) )
		error_reporting(0);
	
	if ( !defined("HQ") || !defined("HQ") )
	{
		echo "No sneaky!";
		exit;
	}
	
	define( "SYSTEM", HQ . "system/" );
	define( "SYSTEM_URL", HQ_URL . "system/" );
	define( "FRONTEND", "true" );
		
	// Required files
	require_once SYSTEM . "assistants/utf8.php";
	require_once SYSTEM . "assistants/config.inc.php";
	require_once SYSTEM . "assistants/clerk.php";
	require_once "display_functions.php";
	
	// Initialize
	$clerk= new Clerk();
	$clerk->dbConnect();
	$clerk->loadSettings();
	
	// Load plugins
	loadPlugins();
	
	// Include all modules
	$defaultModules= array( "global", "design", "pages" );
	$modules= scanFolder( SYSTEM . "modules", 1 );
	
	foreach ( $defaultModules as $key )
	{
		$viewFile= SYSTEM . "modules/" . $key . "/view.php";
		if ( file_exists( $viewFile ) )
		{
			include_once $viewFile;
		}
	}
	
	foreach ( $modules as $key => $val )
	{
		$viewFile= SYSTEM . "modules/" . $key . "/view.php";
		if ( file_exists( $viewFile ) )
		{
			include_once $viewFile;
		}
	}
	
	// Clean URL switching, if requested
	// for debugging purposes
	if ( isset( $_GET['clean_urls'] ) )
	{
		if ( $_GET['clean_urls'] == 0 )
		{
			$clerk->updateSetting( "clean_urls", array( 0 ) );
		}
		elseif ( $_GET['clean_urls'] == 1 )
		{
			$clerk->updateSetting( "clean_urls", array( 1 ) );
		}
	}
	
	// URI Router
	if ( $clerk->getSetting( "clean_urls", 1 ) == 1 && empty( $_GET['request'] ) == false )
	{
		$_GET['request']= utf8_strtolower( $_GET['request'] );
		
		$uri_routes= call_anchor( "uri_router", array() );
		
		foreach ( $uri_routes as $pattern => $result )
		{
			if ( preg_match( "#$pattern#", $_GET['request'] ) )
			{	
				$remainder= preg_replace( "#$pattern#", "", $_GET['request'], 1 );
				
				if ( !empty( $remainder ) ) continue;
				
				$match= preg_replace( "#$pattern#", $result, $_GET['request'], 1 );
				parse_str( $match, $map );
			}
		}
		
		$_GET= $map;
	}
	
	foreach ( $modules as $key => $val )
	{
		$module= getRemappedVar( $key );
		
		if ( in_array( $key, $defaultModules ) )
		{
			if ( $module == getRemappedVar("pages") && !empty( $_GET['id'] ) )
			{	
				$pageDetails= pageInfo( $_GET[getRemappedVar("pages")] );
				$pageType= $pageDetails['content_type'];

				$layout= $pageType;
				$activeModule= $pageType;
			}
			
			continue;
		}
		
		if ( array_key_exists( $module, $_GET ) )
		{
			$layout= $key;
			$activeModule= $key;
			
			break;
		}
	}
	
	if ( empty( $layout ) || $layout == "pages" ) $layout= "default";
	
	$index_page= pageInfo( $clerk->getSetting( "index_page", 1 ) );
	$index_page= $index_page['slug'];
	
	$selectedPage= ( empty( $_GET[getRemappedVar("pages")] ) ) ? $index_page : $_GET[getRemappedVar("pages")];
	foreach ( $modules as $key => $val )
	{
		$module= getRemappedVar( $key );
		
		if ( !empty( $_GET[getRemappedVar($key)] ) && $module == getRemappedVar("pages") )
		{
			$selectedPage= $_GET[getRemappedVar($key)];
			break;
		}
		
		// Not viewing a page (domain.com/?projects=id)
		elseif ( !empty( $_GET[getRemappedVar($key)] ) && $module != getRemappedVar("pages") )
		{
			$selectedPage= getRemappedVar($key);
			break;
		}
	}
		
	// Constants
	call_anchor( "site_init" );
	
	define( "THEME", $clerk->getSetting( "site_theme", 1 ) );
	define( "THEME_URL", HQ_URL . "site/themes/" . THEME . "/" );
	define( "LAYOUT", "layout_" . $layout . ".php" );
	define( "ACTIVE_MODULE", $activeModule );
	define( "PAGE", $selectedPage );
	
	// Check if layout exists.
	if ( file_exists( HQ . "site/themes/" . THEME . "/" . LAYOUT ) == false )
	{
		echo "Oops! Looks like your theme is missing the layout file, <em>" . LAYOUT . "</em>.<br /><br />Create this file and upload it to the root of your theme's folder. Don't forget to fill it with template tags and your custom HTML!";
		
		exit;
	}
	
	call_anchor( "site_begin" );

	require_once "themes/" . THEME . "/" . LAYOUT;
?>