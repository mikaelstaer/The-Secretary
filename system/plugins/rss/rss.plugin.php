<?php
	hook( "uri_router", "rss_routes", "", 1000 );
	hook( "site_begin", "rss" );
	
	function rss_routes( $routes )
	{
		$rss= getRemappedVar( 'rss' );
		
		$routes[ $rss . '/([a-zA-Z0-9\-_]+)']= $rss . '=$1';
		
		return $routes;
	}
	
	function rss()
	{
		global $clerk;
		
		$type= getRemappedVar( $_GET['rss'], true );
		
		if ( isset( $_GET['rss'] ) == false || empty( $type ) )
			return;
		
		include_once "FeedWriter.php";
		
		if ( is_callable( $type . '_rss' ) )
		{
			call_user_func( $type . '_rss' );
		}
		else
		{
			echo "Oops! A feed cannot be generated for that content type.";
		}
		
		exit;
	}
		
?>