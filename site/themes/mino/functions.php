<?php
	function install_mino()
	{
		global $manager;
		
		$manager->clerk->updateSettings(
			array(
				"index_page"	=>	array( 0 )
			)
		);
	}
	
	function uninstall_mino()
	{
		global $manager;
		
		$getPages= $manager->clerk->query_select( "pages" );
		while ( $page= $manager->clerk->query_fetchArray( $getPages ) )
		{
			$manager->clerk->updateSetting( "index_page", array( $page['id'] ) );
			break;
		}
	}
?>