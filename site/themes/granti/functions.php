<?php
	function install_granti()
	{
		global $manager;
		
		$manager->clerk->updateSettings(
			array(
				"projects_thumbnail"		=>	array( 200, 130 ),
				"projects_filethumbnail"	=>	array( 140, 90 ),
				"blog_thumbnail"			=>	array( 200, 130 ),
				"mediamanager_thumbnail"	=>	array( 200, 130)
			)
		);
	}
?>