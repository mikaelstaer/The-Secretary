<?php
	if ( !defined("HQ") || !defined("HQ") )
	{
		echo "No sneaky.";
		exit;
	}
	
	// Variables
	$anchors= array(
					'site_init'		=>	array(),
					'site_begin'	=>	array(),
					'css_frontend'	=>	array(),
					'js_frontend'	=>	array()
	);

	$requires= array(
					'js'	=>	array(),
					'css'	=>	array()
	);
	
	$remap= array();
	$stop_hooks= array();
	
	// Functions
	function themeURL()
	{
		return HQ_URL . 'site/themes/' . THEME . '/';
	}
	
	function siteTitle()
	{
		global $clerk;
		
		$title= $clerk->getSetting( "site", 1 );
		$title= call_anchor( "siteTitle", $title );
		
		return $title;
	}
	
	function site_link()
	{
		global $clerk;
		
		$cleanUrls= (bool) $clerk->getSetting( "clean_urls", 1 );
		
		return ( $cleanUrls == true ) ? $clerk->getSetting( "site", 2 ) : $clerk->getSetting( "site", 2 );
	}
	
	function linkToSite()
	{
		return site_link();
	}
	
	function prepare_settings( $list )
	{
		$list= str_replace( "= ", "=", str_replace( ", ", ",", $list ) );
		$vals= explode( ',', $list );

		$hold= array();
		$parent= 0;
		$count= 0;
		
		foreach ( $vals as $val )
		{
			if ( strstr( $val, "=" ) )
			{
				$parent= $count;
				$hold[]= $val;
				$count++;
			}
			else
			{
				$hold[$parent].= ",$val";
			}

		}
		
		$settings= array();
	
		foreach( $hold as $h )
		{
			$key= substr( $h, 0, strpos( $h, "=" ) );
			$val= substr( $h, strpos( $h, "=" )+1 );
			$settings[$key]= $val;
		}
		
		return $settings;
	}
	
	function merge_settings( $user, $default )
	{	
		foreach ( $default as $key => $val )
		{
			if ( empty( $user[$key] ) )
			{
				$user[$key]= $val;
			}
		}
		
		return $user;
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
	
	function loadPlugins( $dir= "", $root= true, $maxLevel= 2, $level= 0 )
    {
		global $clerk;
		
        static $tree;
        static $base_dir_length;			

		if ( $level == $maxLevel ) return;
		
        if ( $root )
        {
			$dir= HQ . 'system/plugins/';
            $tree= array(); 
            $base_dir_length= strlen( $dir ) + 1; 
        }

        if ( is_file( $dir ) )
        {
			$filename	=	substr( $dir, $base_dir_length );
			$extension 	= 	substr( $filename, strpos( $filename, '.' ) + 1 );
			if ( $extension == "plugin.php" )
			{
				include_once $dir;
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
			$theme= $clerk->getSetting( "site_theme", 1 );
			$themePlugin= HQ . 'site/themes/' . $theme . '/functions.php';
			if ( file_exists( $themePlugin ) )
			{
				include_once $themePlugin;
			}
			
			return $tree;
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
		global $anchors, $stop_hooks;
		
		if ( count( $anchors[$name] > 0 ) && is_array( $anchors[$name] ) )
		{
			foreach ( $anchors[$name] as $function => $data )
			{	
				if ( array_key_exists( $name, $stop_hooks ) )
				{
					break;
				}
					
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
	
	function stop_hook( $function, $stopper )
	{
		global $stop_hooks;
		
		$stop_hooks[$function]= $stopper;
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
	
	function setting( $name, $which= "" )
	{
		global $clerk;
		
		return $clerk->getSetting( $name, $which );
	}
	
	function requireJs( $file, $default= false )
	{
		global $requires;
		
		if  ( !in_array( $file, $requires['js'] ) )
		{
			$requires['js'][]= $file;
			
			if ( $default )
				$file= HQ_URL . 'site/js/' . $file;
			
			return '<script src="' . $file . '" type="text/javascript"></script>' . "\n";
		}	
	}
	
	function requireCss( $file, $default= false )
	{
		global $requires;
		
		if  ( !in_array( $file, $requires['css'] ) )
		{
			$requires['css'][]= $file;
			
			if ( $default )
				$file= HQ_URL . 'site/css/' . $file;
			
			return '<link rel="stylesheet" href="' . $file . '" type="text/css" media="screen">' . "\n";
		}
	}
	
	function remapModuleVar( $module, $getVar )
	{
		global $remap;
		
		$remap[$module]= $getVar;
	}
	
	function getRemappedVar( $module, $is_val= false )
	{
		global $remap;
		
		if ( $is_val == false )
			return ( empty( $remap[$module] ) ) ? $module : $remap[$module];
		else
		{
			$key= array_search( $module, $remap );
			return ( empty( $key ) ) ? $module : $key;
		}
	}
	
	function nl2p( $text )
	{
	    $text = '<p>' . $text . '</p>';
      	$text = str_replace("\n", "</p>\n<p" . $cssClass . '>', $text);

      	$text = str_replace(array('<p></p>', "\r"), '', $text);
		$text= str_replace( "<p></p>", "", $text );
      
		return $text;
	}
	
	function textOutput( $string )
	{
		return ( empty( $string ) ) ? "" : nl2p( html_entity_decode( $string ) );
	}
	
	function load_helper( $file )
	{
		include_once SYSTEM . "assistants/helpers/$file.php";
	}
	
	function getSetting( $setting, $which= "" )
	{
		global $clerk;
		
		return $clerk->getSetting( $setting, $which );
	}
?>