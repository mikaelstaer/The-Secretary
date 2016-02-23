<?php
	if ( defined( "AJAX" ) && AJAX == true ):
		session_start();
		define( "BASE_PATH", $_POST['system']['path'] . "assistants/" );
		define( "SYSTEM" , $_POST['system']['path']  );
		define( "SYSTEM_URL" , $_POST['system']['url']  );

		require_once BASE_PATH . "utf8.php";
		require_once BASE_PATH . "config.inc.php";
		require_once BASE_PATH . "clerk.php";
		require_once BASE_PATH . "guard.php";
		require_once BASE_PATH . "office.php";
		require_once BASE_PATH . "manager.php";

		$clerk= new Clerk( true );
		$guard=	new Guard();
		$manager= new Manager();
		
		//get passwd and username from session not cookie - for security reasons
		if ( !$guard->validate_user_extern( $clerk, $_SESSION["secretary_username"], $_SESSION["secretary_password"] ) )
		{
			die( "Back off!");
		}

		loadPlugins();

		$_POST= $clerk->clean( $_POST );

		$actions= explode( ",", $_POST['action']);
		foreach ( $actions as $func )
		{
			if ( function_exists( $func ) )
				$func();
		}
	else:
		// Include assistants
		require_once SYSTEM . "assistants/utf8.php";
		require_once SYSTEM . "assistants/config.inc.php";
		require_once SYSTEM . "assistants/clerk.php";
		require_once SYSTEM . "assistants/guard.php";
		require_once SYSTEM . "assistants/receptionist.php";
		require_once SYSTEM . "assistants/office.php";
		require_once SYSTEM . "assistants/manager.php";

		// Initialise
		$manager= new Manager();
		$manager->clerk->dbConnect();
		$manager->clerk->loadSettings();
		$manager->guard->init();
		$manager->office->init();
		$manager->guard->validate_user();

		// Default anchors
		$anchors= array( 	
					"start"						=>	array(),
					"head_tags"					=>	array(),
					"css"						=>	array(),
					"javascript"				=>	array(),
					"menu"						=>	array(),
					"after_menu"				=>	array(),
					"breadcrumbActive"			=>	array(),
					"search_bar"				=>	array(),
					"search_results"			=>	array(),
					"big_message"				=>	array(),
					"before_form"				=>	array(),
					"form_process"				=>	array(),
					"form_submit_primary"		=>	array(),
					"form_submit_secondary"		=>	array(),
					"form_main"					=>	array(),
					"after_form"				=>	array(),
					"end"						=>	array()
		);
	endif;
?>
