<?php
	error_reporting(0);
	
	define( "AJAX", true );
	require_once $_POST['system']['path'] . "assistants/launch.php";
	
	function newPage()
	{
		global $clerk;
		
		$name 	= 	$_POST['name'];
		$slug	= 	$clerk->simple_name( $name );
		$pos	=	$clerk->query_countRows( "pages" ) + 1;
		
		$newPage= $clerk->query_insert( "pages", "name, slug, pos", "'$name','$slug', '$pos'" );
		
		if ( $newPage )
		{
			echo $clerk->lastID();
		}
		else
		{
			echo "false";
		}
	}
	
	function orderPages()
	{
		global $clerk;
		
		parse_str( str_replace("&amp;", "&", $_POST['order']) );
		
		$count= 0;
		foreach( $page as $p )
		{
			$ok= $clerk->query_edit( "pages", "pos= '$count'", "WHERE id= '$p'" );
			$count++;
		}
		
		echo $ok;
	}
?>