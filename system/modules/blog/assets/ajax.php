<?php
	error_reporting(0);
	
	define( "AJAX", true );
	require_once $_POST['system']['path'] . "assistants/launch.php";
		
	function newPost()
	{
		global $clerk;
		
		$title 	= 	$_POST['name'];
		$slug	= 	$clerk->simple_name( $title );
		$now	= 	getdate();
		$date	= 	mktime( $now["hours"], $now["minutes"], $now["seconds"], $now["mon"], $now["mday"], $now["year"] );
		
		$new= $clerk->query_insert( "secretary_blog", "title, slug, date, status", "'$title','$slug', '$date', '1'" );
		
		if ( $new )
		{
			echo $clerk->lastID();
		}
		else
		{
			echo "false";
		}
	}
?>