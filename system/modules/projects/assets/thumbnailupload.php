<?php	
	error_reporting( 0 );
	
	array_push( $_POST, 'system' );
	$_POST['system']= array( 'path' => $_POST['system_path'], 'url' => $_POST['system_url'] );
	
	define( "AJAX", true );
	require_once $_POST['system']['path'] . "assistants/launch.php";
	require_once $_POST['system']['path'] . "assistants/helpers/file_uploader.inc.php";
	require_once $_POST['system']['path'] . "assistants/helpers/ThumbLib.inc.php";
	
	define_anchor( "modifyFileThumbAfterSave" );
	define_anchor( "modifyFileThumb" );
	
	$paths= $clerk->getSetting( "projects_path" );
	$paths= array(
					'path' =>	$paths['data1'],
					'url'	=>	$paths['data2']
	);
	
	if ( $_POST['action'] == "uploadThumbnail" )
	{
		$project		= 	$clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '" . $_POST['id'] . "' LIMIT 1" ) );
		$id				=	$_POST['id'];
		$slug			= 	$project['slug'];
		$destination	=	$_POST['uploadPath'] . $slug . "/";
		$thumbnails		=	$clerk->getSetting( "projects_thumbnail" );
		$thumbWidth		= 	$thumbnails['data1'];
		$thumbHeight	= 	$thumbnails['data2'];
		$resizeProjThumb=	$clerk->getSetting( "resizeProjThumb", 1 );
		$intelliscaling	=	(boolean) $clerk->getSetting( "projects_intelliscaling", 1 );
		$forceAdaptive	= 	( $thumbWidth == 0 || $thumbHeight == 0 ) ? true : false;
		
		// Create set folder if it doesn't already exist
		if ( !is_dir( $destination ) )
		{
			mkdir( substr( $destination, 0, -1 ), 0755 );
		}
		
		$allowed_file_types	=	array( '.jpg', '.jpeg', '.gif', '.png' );
		
		foreach ( $_FILES['Thumbnail']['name'] as $key => $val )
		{
			$upload				= 	upload( 'Thumbnail', $key, $destination, implode( ",", $allowed_file_types ), true );
			$upload_file		= 	$upload[0];
			$upload_error		=	$upload[1];
		}
		
		if ( empty( $upload_error ) )
		{	
			$currentThumb	= 	$_POST['uploadPath'] . $slug . "/" . $project['thumbnail'];
			$deleteCurrent	= 	( file_exists( $currentThumb ) && is_file( $currentThumb ) ) ? unlink( $currentThumb ) : true;
			$file_extension =	substr( $upload_file, strrpos( $upload_file, '.' ) );
			$thumbnail_name	=	$clerk->simple_name( str_replace( array_merge( $allowed_file_types, array( ".thumbnail", ".thumb" ) ), "", $upload_file ) ) . ".project" . $file_extension;
			
			rename( $destination . $upload_file, $destination . $thumbnail_name );
			call_anchor( "modifyProjectThumbAfterSave", array( $thumb, ( $destination . $thumbnail_name ) ) );
			
			if ( $clerk->query_edit( "projects", "thumbnail= '$thumbnail_name'", "WHERE id= '$id'" ) && $deleteCurrent )
			{
				echo '<img src="' . $_POST['uploadUrl'] . $slug . '/' . $thumbnail_name . '" alt="" />';
			}
		}
		else
		{
			echo $upload_error;
		}
	}
?>