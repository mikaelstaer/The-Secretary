<?php
/*
 * jQuery File Upload Plugin PHP Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

error_reporting(E_ALL | E_STRICT);

define( "AJAX", true );

$_POST['system']['path'] = $_POST['syspath'];
$_POST['system']['url'] = $_POST['sysurl'];

require_once $_POST['syspath'] . "assistants/launch.php";
require('UploadHandler.php');

$paths= $clerk->getSetting( "projects_path" );
$paths= array(
                'path' =>	$paths['data1'],
                'url'	=>	$paths['data2']
);

// Get the project
$project		= 	$clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '" . $_POST['id'] . "' LIMIT 1" ) );
$id				=	$project['id'];
$slug			= 	$project['slug'];
$dest_dir       =	$paths['path'] . $slug . "/";
$dest_url       =   $paths['url'] . $slug . "/";

$image_types		=	array( 'jpg', 'jpeg', 'gif', 'png', 'JPG', 'JPEG' );
$video_types		=	array( 'mov', 'mpg', 'mpeg', 'wmv', 'avi', 'm4v', 'mp4', 'flv', 'swf' );
$audio_types		=	array( 'mp3', 'm4a', 'wav' );
$allowedExtensions 	= 	array_merge( $image_types, $video_types, $audio_types );

$upload_handler = new UploadHandler(array(
    'accept_file_types' => '/\.(gif|jpe?g|png|mp4|mov|mpg|mpeg|wmv|avi|mp3|m4a|wav)$/i',
    'upload_dir' => $dest_dir,
    'upload_url' => $dest_url,
    'print_response' => false
));

if ($upload_handler) {
    // $upload_handler->generate_response($upload_handler->get_response());

    // Files here
    // $upload_handler->get_response();
    $uploaded = $upload_handler->get_response();

    // File details
    $file			=	$uploaded['files'][0]->name;
    $file_details	=	pathinfo( $file );
    $file_ext		= 	$file_details['extension'];
    $thumbnail_name	=	str_replace( $file_ext, "thumb." . $file_ext, $file );

    // Project details
    $numGroups		=	substr_count( $project['flow'], "group" );
    $fileThumb		=	$clerk->getSetting( "projects_filethumbnail" );
    $fileThumbWidth	= 	$fileThumb['data1'];
    $fileThumbHeight= 	$fileThumb['data2'];
    $intelliscaling	=	(boolean) $clerk->getSetting( "projects_intelliscaling", 1 );
    $forceAdaptive	= 	( $fileThumbWidth == 0 || $fileThumbHeight == 0 ) ? true : false;

    // File is meant to be a custom thumbnail (for videos or audio, for example)
    // file_name.jpg, file_name.thumb.jpg
    if ( strstr( $file, ".thumb" ) || strstr( $file, ".thumbnail" ) )
    {
        // Break out
        // echo htmlspecialchars( json_encode( $result ), ENT_NOQUOTES );
        echo json_encode( $result );
        exit();
    }

    // File is an image
    if ( in_array( $file_ext, $image_types ) )
    {
        $type= "image";
        list($width, $height) = getimagesize( $paths['path'] . $slug . "/" . $file );
        $width = $width ?? 0;
        $height = $height ?? 0;
    }
    elseif ( in_array( $file_ext, $video_types ) )
    {
        $type= "video";
    }
    elseif ( in_array( $file_ext, $audio_types ) )
    {
        $type= "audio";
    }

    $pos	=	$clerk->query_countRows( "project_files", "WHERE project_id= '$id'" ) + 1;
    // Need to fix this - if only one group, group # can still be greater than 1.
    // flow: group2:one-by-one
    // this says to put file in group1, which doesn't exist
    $group	=	( $numGroups == 1 ) ? 1 : 0;

    // Add to database
    $clerk->query_insert( "project_files", "title, caption, file, thumbnail, width, height, project_id, pos, type, filegroup", "'', '', '$file', '$thumbnail_name', '$width', '$height', '$id', '$pos', '$type', '$group'" );
    $fileId= mysqli_insert_id($clerk->link);

    $thumb_path		=	$paths['path'] . $slug . "/" . $thumbnail_name;
    $thumb_url		= 	$paths['url'] . $slug . "/" . $thumbnail_name;
    $big_path		=	$paths['path'] . $slug . "/" . $file;

    if ( $type == "image" )
    {
        $thumb_path= $paths['path'] . $slug . '/' . $file;

        $file_extension= substr( $file	, strrpos( $file, '.' ) );
        $cache_file_name= str_replace( $file_extension, "", $file ) . "." . 100 . "x" . 100 . "_1.jpg";

        $dynamic_thumb= ( file_exists( $clerk->getSetting( "cache_path", 1 ) . $cache_file_name ) ) ? $clerk->getSetting( "cache_path", 2 ) . $cache_file_name : $clerk->getSetting( "site", 2 ) . "?dynamic_thumbnail&file=" . $thumb_path . '&amp;width=' . 100 . '&amp;height=' . 100 . '&adaptive=1';

        $thumb= '<img src="' . $dynamic_thumb . '" alt="" />';
    }
    else
    {
        $thumb=	'<span class="media">' . $file . '</span>';
    }

    $file_data= array(
        'id' 		=>	$fileId,
        'file'		=>	$file,
        'thumbnail'	=>	$thumbnail_name,
        'width'		=>	$width,
        'height'	=>	$height,
        'project_id'=>	$id,
        'pos'		=>	$pos,
        'type'		=>	$type,
        'group'		=>	$group
    );

    $file_toolbar= call_anchor( "project_file_toolbar", array( 'html' => "", 'file' => $file_data ) );

    $html= '
                    <li class="filebox" id="file_' . $fileId .'">
                        <div class="thumbnail">
                            ' . $thumb . '
                        </div>

                        <ul class="toolbar">
                            <li onmouseover="toolbar_show(' . $fileId . ')" onmouseout="toolbar_hide(' . $fileId . ')">
                                <a href="#" class="edit">Edit</a>
                                <ul class="options">
                                    <li><a href="#" onclick="toolbar_delete(' . $fileId . '); return false;">Delete</a></li>
                                    <li><a href="#" onclick="toolbar_details(' . $fileId . '); return false;">Edit details</a></li>
                                    ' . $file_toolbar['html'] . '
                                </ul>
                            </li>
                        </ul>
                    </li>
                ';

    $result['html']= $html;
    // echo htmlspecialchars( json_encode( $result ), ENT_NOQUOTES );
    echo json_encode( $result );
}