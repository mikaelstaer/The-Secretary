<?php
	# The Secretary 2.3 Upgrader
	if ( isset( $manager ) ):
	
		$manager->clerk->addSetting( "app", array( 0, 0, 0 ) );

		$upgradeVersion= "2.3";
		$appVersion= $manager->clerk->getSetting( "app", 1 );

		if ( $appVersion < $upgradeVersion ):
			$manager->clerk->updateSetting( "app", array( $upgradeVersion ) );
			
			$manager->clerk->addSetting( "projects_fullsizeimg", array( "", 1, 0 ) );
			$manager->clerk->addSetting( "slideshow_opts", array( 'a:4:{s:4:"prev";s:4:"Prev";s:7:"divider";s:1:"/";s:4:"next";s:4:"Next";s:2:"of";s:12:"(# of total)";}', 0, 0 ) );
		else:
			$manager->clerk->updateSetting( "slideshow_opts", array( 'a:4:{s:4:"prev";s:4:"Prev";s:7:"divider";s:1:"/";s:4:"next";s:4:"Next";s:2:"of";s:12:"(# of total)";}', 0, 0 ) );
		endif;
		
		unlink( SYSTEM . "plugins/upgrade.plugin.php" );
	
	endif;
?>
