<?php
	/*
	 * The Manager / The Secretary
	 * by Mikael Stær (www.secretarycms.com, www.mikaelstaer.com)
	 *
	 * Initializes instances of Clerk, Guard, Receptionist and Office classes. File also includes
	 * global-scope functions to grab plug-ins, define and call hooks.
	 */
	
	if ( !isset( $anchors ) )
	{
		$anchors= array();
	}
	
	$helpers= array(
					'js'	=>	array()
	);
	
	$totalModules= 0;
	
	class Manager
	{
		public $clerk;
		public $guard;
		public $office;
		public $form;
		
		function __construct()
		{
			$this->clerk	= 	new Clerk( false );
			$this->guard	= 	new Guard();
			$this->office	= 	new Office();			
		}

		public function message( $type= 0, $mysql_error= false, $text, $return= false )
		{
			$type= ( $type == 1 ) ? "success" : "error";
			
			if ( $mysql_error )
				$text.= "<br /><i>MySQL says:</i> ".mysql_error();
			
			$text= stripslashes( $text );
			
			if ( $return )
				return '<div class="message '.$type.'"><div class="center">'.$text.'</div></div>';
			else
				echo '<div class="message '.$type.'"><div class="center">'.$text.'</div></div>';
		}
		
		public function load_helper( $file, $vars= "" )
		{
			$options= $vars;
			include_once SYSTEM . "assistants/helpers/$file.php";
		}
		
		public function load_jshelper( $file )
		{
			global $helpers;
			
			if  ( !in_array( $file, $helpers['js'] ) )
			{
				$helpers['js'][]= $file;
				
			
				return '<script type="text/javascript" src="' . SYSTEM_URL . 'gui/common_js/helpers/' . $file . '.js"></script>'."\n";
			}
			
			return;
		}
		
		public function helperLoaded( $helper, $type )
		{
			global $helpers;
			
			return ( in_array( $helper, $helpers[$type] ) );
		}
		
		public function getSetting( $name, $which= "" )
		{
			return $this->clerk->getSetting( $name, $which );
		}
		
		public function install( $file )
		{
			$error= true;
			
			// Read dump file
			$dump	=	fopen( $file, "r" ); 
			$file 	= 	fread( $dump, 80000 ); 
			fclose($dump); 

			// split the .sql into separate queries 
			$lines 	= explode( ';',  $file );
			$count 	= count( $lines );
			$queries= array();
			foreach ( $lines as $line )
			{
				$queries[]= trim( $line );
			}

			// Execute the queries
			$count= 0;
			foreach ( $queries as $q )
			{
				$count++;
				if ( empty( $q ) ) continue;

				if ( !$this->clerk->query( $q ) )
				{
					echo "Error! Line $count<br />$q<br />";
					$error= true;
				}
			}
			
			return $error;
		}
		
	}

	function define_anchor( $name )
	{
		global $anchors;
		
		if ( anchor_exists( $name ) == false )
			$anchors[$name]= array();
	}

	function call_anchor( $name, $val= "" )
	{
		global $anchors;
		
		if ( count( $anchors[$name] > 0 ) && is_array( $anchors[$name] ) )
		{
			foreach ( $anchors[$name] as $name => $data )
			{	
				if ( is_callable( $data[0] ) )
				{
					$data[1][]= $val;
				 	$val= call_user_func_array( $data[0], $data[1] );
				}
			}
			
			return $val;
		}
		
		return $val;
	}
	
	function anchor_exists( $name )
	{
		global $anchors;
		
		return array_key_exists( $name, $anchors );
	}
	
	function clearAnchor( $name )
	{
		global $anchors;
		
		$anchors[$name]= array();
	}
	
	function removeAnchor( $name, $function )
	{
		global $anchors;
		
		unset( $anchors[$name][$function] );
	}
	
	function hook( $anchor, $function, $params= "", $order= -1 )
	{
		global $anchors;
		
		if ( !isset( $anchors[$anchor] ) )
		{
			define_anchor( $anchor );
		}
		
		if ( $order == -1 )
		{
			$order= count( $anchors[$anchor] ) + 1;
		}
		
		$anchors[$anchor][$function]= array( $function, $params, $order );
		usort( $anchors[$anchor], 'sort_hooks' );
	}
	
	function sort_hooks( $a, $b )
	{
		if ( $a[2] == $b[2] ) {
			return 0;
		}
		
		return ( $a[2] < $b[2] ) ? -1 : 1;
	}
	
	function countHooks( $anchor )
	{
		global $anchors;
		
		return count( $anchors[$anchor] );
	}
	
	function loadModules()
	{
		global $manager, $totalModules;

		// Load global modules
		$dir= SYSTEM . 'modules/global';
	 	
		if ( is_dir( $dir ) )
		{
			foreach ( scanFolder( $dir, 1 , ".php" ) as $file )
			{
				include_once( $file );
			}
		}
		
		// Load defaults
		$dir= SYSTEM . 'modules';
		$modules= scanFolder( $dir, 1 );
		$totalModules= count( $modules );
		
		foreach ( $modules as $module => $loc )
		{
			if ( file_exists( $loc . '/' . $module . ".php" ) )
				include_once $loc . '/' . $module . ".php";
		}

		// Load current request
		$request= str_replace( "-", "_", $manager->office->cubicle('REQUEST') );
	 	$dir= SYSTEM . 'modules/' . $manager->office->cubicle('BRANCH') . '/';
		
		if ( file_exists( $dir . $request . ".php" ) )
			include_once $dir . $request . ".php";
	}
	
	function loadPlugins( $dir= "", $root= true, $maxLevel= 2, $level= 0 )
    {
		global $manager;
		
        static $tree;
        static $base_dir_length;			
		
		if ( $level == $maxLevel ) return;
		
		$base= ( AJAX ) ? SYSTEM : BASE_PATH;
		
        if ( $root )
        {
			$dir= $base . 'plugins/';
            $tree= array(); 
            $base_dir_length= strlen( $dir ) + 1; 
        }
		
        if ( is_file( $dir ) )
        {
			$filename	=	substr( $dir, $base_dir_length );
			$extension 	= 	substr( $filename, strpos( $filename, '.' ) + 1 );
			if ( $extension == "plugin.php" )
			{
				include $dir;
			}
        }
		elseif ( is_dir( $dir ) && $di = dir( $dir ) )
        {
			$level= ( !$root ) ? $level + 1 : $level;
            while ( ( $file = $di->read() ) !== false )
                if ( $file != "." && $file != ".." )
                    loadPlugins( "$dir/$file", false, $maxLevel, $level ); 
            $di->close();
        }

        if ($root)
		{
			$theme= ( isset( $manager ) ) ? $manager->clerk->getSetting( "site_theme", 1 ) : $clerk->getSetting( "site_theme", 1 );
			$themePlugin= $base . 'site/themes/' . $theme . '/functions.php';
			
			if ( file_exists( $themePlugin ) )
			{
				include_once $themePlugin;
			}
			
            return $tree;
		}
    }

	function scanFolder( $dir= "", $maxLevel= 0, $filter= "", $callback= "", $level= 0, $root= true )
    {
        static $tree;
        static $base_dir_length;
		
		$filters= explode( ",", $filter );
		
		if ( $level == $maxLevel && $maxLevel > 0 ) return;

        if ($root)
        {
            $tree = array(); 
            $base_dir_length = strlen($dir) + 1; 
        }
		
		$ext= substr( $dir, strrpos( $dir, '.' ) );
		
        if ( is_file( $dir ) )
        {
			if ( ( !empty( $filter ) && in_array( $ext, $filters ) ) || empty( $filter ) )
            	$tree[substr($dir, $base_dir_length)] = $dir;
        }
		elseif (is_dir($dir) && $di = dir($dir))
        {
			$level= ( !$root ) ? $level + 1 : $level;
			
			$folderName= basename( $dir );
			if ( !empty( $filter ) && in_array( $folderName, $filters ) )
				$tree[substr($dir, $base_dir_length)] = $dir;
			elseif (!$root && empty( $filter ) )
				$tree[substr($dir, $base_dir_length)] = $dir;
				
            while (($file = $di->read()) !== false)
                if ($file != "." && $file != "..")
                    scanFolder( $dir . "/" . $file, $maxLevel, $filter, $callback, $level, false ); 
            $di->close();
        }

        if ($root)
            return $tree;    
    }
	
	function return_bytes( $val )
	{
	    $val= trim( $val );
	   	$last= strtolower( $val{ strlen( $val )-1 } );

	    switch($last)
		{
	        case 'g':
	            $val *= 1024;
	        case 'm':
	            $val *= 1024;
	        case 'k':
	            $val *= 1024;
	    }

	    return $val;
	}
	
	// Borrowed from http://aidanlister.com/repos/v/function.rmdirr.php, thanks Aidan!
	function rmdirr( $dirname, $deleteMe= true )
	{
	    // Sanity check
	    if (!file_exists($dirname)) {
	        return false;
	    }

	    // Simple delete for a file
	    if (is_file($dirname) || is_link($dirname)) {
	        return unlink($dirname);
	    }

	    // Loop through the folder
	    $dir = dir($dirname);
	    while (false !== $entry = $dir->read()) {
	        // Skip pointers
	        if ($entry == '.' || $entry == '..') {
	            continue;
	        }

	        // Recurse
	        rmdirr($dirname . DIRECTORY_SEPARATOR . $entry, true);
	    }

	    // Clean up
	    $dir->close();
		if ( $deleteMe )
	    	return rmdir($dirname);
		else
			return true;
	}
	
	// From http://snippets.dzone.com/posts/show/5004
	function emptyDir( $dir, $DeleteMe )
	{
	    if ( !$dh = @opendir( $dir ) ) return;
	    while ( false !== ( $obj= readdir( $dh ) ) )
		{
	        if ( $obj == '.' || $obj == '..' ) continue;
	        if ( !unlink($dir.'/'.$obj ) ) SureRemoveDir( $dir . '/' . $obj, true );
	    }

	    closedir($dh);
	
	    if ( $DeleteMe )
		{
	        rmdir( $dir );
	    }
	}
	
	// From http://dk.php.net/manual/en/function.copy.php#78500, thanks mzheng at [s-p-a-m dot ]procuri dot com!
	function dircopy($src_dir, $dst_dir, $verbose = false, $use_cached_dir_trees = false)
	{   
	        static $cached_src_dir;
	        static $src_tree;
	        static $dst_tree;
	        $num = 0;

	        if (($slash = substr($src_dir, -1)) == "\\" || $slash == "/") $src_dir = substr($src_dir, 0, strlen($src_dir) - 1);
	        if (($slash = substr($dst_dir, -1)) == "\\" || $slash == "/") $dst_dir = substr($dst_dir, 0, strlen($dst_dir) - 1); 

	        if (!$use_cached_dir_trees || !isset($src_tree) || $cached_src_dir != $src_dir)
	        {
	            $src_tree = get_dir_tree($src_dir);
	            $cached_src_dir = $src_dir;
	            $src_changed = true; 
	        }
	        if (!$use_cached_dir_trees || !isset($dst_tree) || $src_changed)
	            $dst_tree = get_dir_tree($dst_dir);
	        if (!is_dir($dst_dir)) mkdir($dst_dir, 0777, true); 

	          foreach ($src_tree as $file => $src_mtime)
	        {
	            if (!isset($dst_tree[$file]) && $src_mtime === false) // dir
	                mkdir("$dst_dir/$file");
	            elseif (!isset($dst_tree[$file]) && $src_mtime || isset($dst_tree[$file]) && $src_mtime > $dst_tree[$file])  // file
	            {
	                if (copy("$src_dir/$file", "$dst_dir/$file"))
	                {
	                    if($verbose) echo "Copied '$src_dir/$file' to '$dst_dir/$file'<br>\r\n";
	                    touch("$dst_dir/$file", $src_mtime);
	                    $num++;
	                } else
	                    echo "<font color='red'>File '$src_dir/$file' could not be copied!</font><br>\r\n";
	            }       
	        }

	        return $num;
	}

	function get_dir_tree($dir, $root = true)
    {
        static $tree;
        static $base_dir_length;

        if ($root)
        {
            $tree = array(); 
            $base_dir_length = strlen($dir) + 1; 
        }

        if (is_file($dir))
        {
            //if (substr($dir, -8) != "/CVS/Tag" && substr($dir, -9) != "/CVS/Root"  && substr($dir, -12) != "/CVS/Entries")
            $tree[substr($dir, $base_dir_length)] = filemtime($dir);
        } elseif (is_dir($dir) && $di = dir($dir)) // add after is_dir condition to ignore CVS folders: && substr($dir, -4) != "/CVS"
        {
            if (!$root) $tree[substr($dir, $base_dir_length)] = false; 
            while (($file = $di->read()) !== false)
                if ($file != "." && $file != "..")
                    get_dir_tree("$dir/$file", false); 
            $di->close();
        }

        if ($root)
            return $tree;    
    }

	if (!function_exists("json_encode"))
	{
		function json_encode($a=false)
		{
			// Some basic debugging to ensure we have something returned
			if (is_null($a)) return 'null';
			if ($a === false) return 'false';
			if ($a === true) return 'true';
			if (is_scalar($a))
			{
				if (is_float($a))
				{
					// Always use "." for floats.
					return floatval(str_replace(",", ".", strval($a)));
				}

				if (is_string($a))
				{
					static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
					return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
				}
				else
					return $a;
			}
			$isList = true;
			for ($i = 0; reset($a); $i) {
				if (key($a) !== $i)
				{
					$isList = false;
					break;
				}
			}
			$result = array();
			if ($isList)
			{
				foreach ($a as $v) $result[] = json_encode($v);
				return '[' . join(',', $result) . ']';
			}
			else
			{
				foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
				return '{' . join(',', $result) . '}';
			}
		}
	}
	
	function message( $type, $text, $return= false )
	{		
		$text= stripslashes( $text );
		
		if ( $return )
			return '<div class="message ' . $type . '"><div class="center">' . $text . '</div></div>';
		else
			echo '<div class="message ' . $type . '"><div class="center">' . $text . '</div></div>';
	}
?>