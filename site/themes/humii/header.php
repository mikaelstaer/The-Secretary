<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title><?php echo siteTitle()?></title>
		<?php
			call_anchor("css_frontend");
			
			// Include jQuery and jQuery Cycle if we are on the homepage
			if ( is_index() )
			{
				echo requireJs( "jquery.js", true );
				echo requireJs( SYSTEM_URL . "plugins/displayers/slideshow/jquery.cycle.js" );
			}
			
			echo requireJs( themeUrl() . "js/actions.js" );
			
			call_anchor("js_frontend");
		?>
	</head>
	<body>
		<div id="wrapper">
			<div id="header">
				<div id="title">
					<a href="<?php echo linkToSite(); ?>"><h1><?php echo siteTitle()?></h1></a>
				</div>
				<!--
				<div id="subtitle">
					An optional subtitle.
				</div>
				-->
				<div id="menu">
					<?php echo pageList(); ?>
				</div>
			</div>
			<div id="content">
