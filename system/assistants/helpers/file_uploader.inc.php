<?php
/**
 * A function for easily uploading files. This function will automatically generate a new 
 *        file name so that files are not overwritten.
 * Taken From: http://www.bin-co.com/php/scripts/upload_function/
 * Arguments:    $file_id- The name of the input field contianing the file.
 *                $folder    - The folder to which the file should be uploaded to - it must be writable. OPTIONAL
 *                $types    - A list of comma(,) seperated extensions that can be uploaded. If it is empty, anything goes OPTIONAL
 * Returns  : This is somewhat complicated - this function returns an array with two values...
 *                The first element is randomly generated filename to which the file was uploaded to.
 *                The second element is the status - if the upload failed, it will be 'Error : Cannot upload the file 'name.txt'.' or something like that
 */
function upload($file_id, $num, $folder="", $types="", $unique= false) {
    if(!$_FILES[$file_id]['name'][$num]) return array('','No file specified');
	
    $file_title = $_FILES[$file_id]['name'][$num];
    //Get file extension
    $ext_arr = split("\.",basename($file_title));
    $ext = "." . strtolower($ext_arr[count($ext_arr)-1]); //Get the last extension
	
	if ( $unique == true )
	{
    	//Not really uniqe - but for all practical reasons, it is
    	$uniqer = substr(md5(uniqid(rand(),1)),0,5);
    	$file_name = ( file_exists( "$folder/$file_title" ) ) ? str_replace( $ext, "", $file_title ) . '_' . $uniqer . $ext : $file_title;
	}
	else
	{
		$file_name = $file_title;
	}

    $all_types = explode(",",strtolower($types));
    if($types) {
        if(in_array($ext,$all_types));
        else {
            $result = "'".$_FILES[$file_id]['name'][$num]."' is not a valid file."; //Show error if any.
            return array('',$result);
        }
    }

    //Where the file must be uploaded to
    // if($folder) $folder .= '/';//Add a '/' at the end of the folder
    $uploadfile = $folder . $file_name;

    $result = '';
    //Move the file from the stored location to the new location
    if (!move_uploaded_file($_FILES[$file_id]['tmp_name'][$num], $uploadfile)) {
        $result = "Cannot upload the file '".$_FILES[$file_id]['name'][$num]."'"; //Show error if any.
        if(!file_exists($folder)) {
            $result .= " : Folder don't exist.";
        } elseif(!is_writable($folder)) {
            $result .= " : Folder not writable.";
        } elseif(!is_writable($uploadfile)) {
            $result .= " : File not writable.";
        }
        $file_name = '';
        
    } else {
        if(!$_FILES[$file_id]['size'][$num]) { //Check if the file is made
            @unlink($uploadfile);//Delete the Empty file
            $file_name = '';
            $result = "Empty file found - please use a valid file."; //Show the error message
        } else {
            chmod($uploadfile,0777);//Make it universally writable.
        }
    }

    return array($file_name,$result);
}