<?php
	# The Secretary 2.5 Upgrader
	if ( isset( $manager ) ):

		$upgradeVersion= "2.5";
		$manager->clerk->updateSetting( "app", array( $upgradeVersion ) );

		unlink( SYSTEM . "plugins/upgrade.plugin.php" );

	endif;
?>
