<?php
	define_anchor( "theme_css" );
	define_anchor( "theme_css_ie" );
	
	hook( "css_frontend", "themeCss", "", 0 );
	hook( "css_frontend", "themeIECss", "", 1 );
	
	function themeCss()
	{
		global $clerk;
		
		$file= HQ_URL . 'site/themes/' . THEME . '/css/main.css';
		
		echo '<link rel="stylesheet" href="' . $file . '" type="text/css" media="screen" />' . "\n";
		
		call_anchor( "theme_css" );
	}
	
	/*
	 * Automatically include IE specific stylesheets in conditional comments.
	 * Supports IE all, 6, 7 and 8.
	 * Super simple and straightforward, could be modified to dynamically
	 * recognise files by version number.
	 
	 * IE is bad news!
	*/
	function themeIECss()
	{
		global $clerk;
		
		if ( file_exists( HQ . 'site/themes/' . THEME . '/css/ie/ie.css' ) )
		{
			$file= HQ_URL . 'site/themes/' . THEME . '/css/ie/ie.css';
			echo '<!--[if IE]><link rel="stylesheet" href="' . $file . '" type="text/css" media="screen"><![endif]-->' . "\n";
		}
		
		if ( file_exists( HQ . 'site/themes/' . THEME . '/css/ie/ie6.css' ) )
		{
			$file= HQ_URL . 'site/themes/' . THEME . '/css/ie/ie6.css';
			echo '<!--[if IE 6]><link rel="stylesheet" href="' . $file . '" type="text/css" media="screen"><![endif]-->' . "\n";
		}
		
		if ( file_exists( HQ . 'site/themes/' . THEME . '/css/ie/ie7.css' ) )
		{
			$file= HQ_URL . 'site/themes/' . THEME . '/css/ie/ie7.css';
			echo '<!--[if IE 7]><link rel="stylesheet" href="' . $file . '" type="text/css" media="screen"><![endif]-->' . "\n";
		}
		
		if ( file_exists( HQ . 'site/themes/' . THEME . '/css/ie/ie8.css' ) )
		{
			$file= HQ_URL . 'site/themes/' . THEME . '/css/ie/ie8.css';
			echo '<!--[if IE 8]><link rel="stylesheet" href="' . $file . '" type="text/css" media="screen"><![endif]-->' . "\n";
		}
		
		call_anchor( "theme_css_ie" );
	}
?>