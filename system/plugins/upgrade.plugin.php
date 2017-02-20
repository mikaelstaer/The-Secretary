<?php
	# The Secretary 2.4 Upgrader
	if ( isset( $manager ) ):

		$upgradeVersion= "2.4";
		$manager->clerk->updateSetting( "app", array( $upgradeVersion ) );

		unlink( SYSTEM . "plugins/upgrade.plugin.php" );

	endif;
?>
