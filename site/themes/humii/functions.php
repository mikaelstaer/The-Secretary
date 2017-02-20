<?php
	function install_humii()
	{
		global $manager;
		
		$manager->clerk->updateSettings(
			array(
				"index_page"				=>	array( 0 ),
				"projects_thumbnail"		=>	array( 230, 110 ),
				"projects_filethumbnail"	=>	array( 230, 110 ),
				"blog_thumbnail"			=>	array( 230, 110 ),
				"mediamanager_thumbnail"	=>	array( 230, 110)
			)
		);
	}
	
	function uninstall_humii()
	{
		global $manager;
		
		$getPages= $manager->clerk->query_select( "pages" );
		while ( $page= $manager->clerk->query_fetchArray( $getPages ) )
		{
			$manager->clerk->updateSetting( "index_page", array( $page['id'] ) ) || die(mysql_error());
			break;
		}
		
	}
?>