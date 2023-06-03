<?php
	ob_start();
	//added session - for security
	session_start();
	//destroy session
	session_destroy();

	header( "Location: login.php" );
	
	ob_end_flush();
?>
