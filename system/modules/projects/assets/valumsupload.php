<?php
	error_reporting( 0 );
	$_POST= $_GET;
	
	define( "AJAX", true );
	require_once $_POST['system']['path'] . "assistants/launch.php";
	
	$paths= $clerk->getSetting( "projects_path" );
	$paths= array(
					'path' =>	$paths['data1'],
					'url'	=>	$paths['data2']
	);
	
	// Get the project
	$project		= 	$clerk->query_fetchArray( $clerk->query_select( "projects", "", "WHERE id= '" . $_GET['id'] . "' LIMIT 1" ) );
	$id				=	$project['id'];
	$slug			= 	$project['slug'];
	$destination	=	$paths['path'] . $slug . "/";
	
	// Set up Valums File Uploader
	$image_types		=	array( 'jpg', 'jpeg', 'gif', 'png', 'JPG', 'JPEG' );
	$video_types		=	array( 'mov', 'mpg', 'mpeg', 'wmv', 'avi', 'm4v', 'mp4', 'flv', 'swf' );
	$audio_types		=	array( 'mp3', 'm4a', 'wav' );
	$allowedExtensions 	= 	array_merge( $image_types, $video_types, $audio_types );
	
	$sizeLimit			= 	trim( ini_get( "upload_max_filesize" ) ) * 1024 * 1024;
	
	$overwrite			=	( file_exists( $destination . $_GET['qqfile'] ) );
	
	$uploader= new qqFileUploader( $allowedExtensions, $sizeLimit );
	$result= $uploader->handleUpload( $destination, TRUE );
	
	// Succesful upload, save in database
	if ( $result['success'] == true ):
		// File details
		$file			=	$_GET['qqfile'];
		$file_details	=	pathinfo( $file );
		$file_ext		= $file_details['extension'];
		$thumbnail_name	=	str_replace( $file_ext, "thumb." . $file_ext, $file );
		
		// Project details
		$numGroups		=	substr_count( $project['flow'], "group" );
		$fileThumb		=	$clerk->getSetting( "projects_filethumbnail" );
		$fileThumbWidth	= 	$fileThumb['data1'];
		$fileThumbHeight= 	$fileThumb['data2'];
		$intelliscaling	=	(boolean) $clerk->getSetting( "projects_intelliscaling", 1 );
		$forceAdaptive	= 	( $fileThumbWidth == 0 || $fileThumbHeight == 0 ) ? true : false;

		// Create project folder if it doesn't already exist
		if ( !is_dir( $destination ) )
		{
			mkdir( substr( $destination, 0, -1 ), 0755 );
		}
		
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
		}
		elseif ( in_array( $file_ext, $video_types ) )
		{
			$type= "video";
		}
		elseif ( in_array( $file_ext, $audio_types ) )
		{
			$type= "audio";
		}
		
		if ( $overwrite )
		{
			// echo htmlspecialchars( json_encode( $result ), ENT_NOQUOTES );
			echo json_encode( $result );
			exit();
		}
		
		$pos	=	$clerk->query_countRows( "project_files", "WHERE project_id= '$id'" ) + 1;
		// Need to fix this - if only one group, group # can still be greater than 1.
		// flow: group2:one-by-one
		// this says to put file in group1, which doesn't exist
		$group	=	( $numGroups == 1 ) ? 1 : 0;
		
		// Add to database
		$clerk->query_insert( "project_files", "file, thumbnail, width, height, project_id, pos, type, filegroup", "'$file', '$thumbnail_name', '$width', '$height', '$id', '$pos', '$type', '$group'" );
		$fileId= mysql_insert_id();

		$thumb_path		=	$paths['path'] . $slug . "/" . $thumbnail_sysname;
		$thumb_url		= 	$paths['url'] . $slug . "/" . $thumbnail_sysname;
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
		
	else:
		// echo htmlspecialchars( json_encode( $result ), ENT_NOQUOTES );
		echo json_encode( $result );
	endif;


/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }
        
        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
            return array('success'=>true);
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        
    }    
}
?>