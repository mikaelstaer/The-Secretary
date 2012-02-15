<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title><?php echo siteTitle(); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<?php
			echo requireJs( "jquery.js", true );
			echo requireJs( THEME_URL . "js/actions.js" );
			call_anchor("css_frontend");
			call_anchor("js_frontend");
		?>
	</head>
	<body>
		<div id="site">
			<div id="header">
				<h1 id="title">
					<a href="<?php echo linkToSite(); ?>"><?php echo siteTitle(); ?></a>
				</h1>
				<div id="menu">
					<?php echo pageList(); ?>
				</div>
			</div>
			<div id="main">
				<div id="content">