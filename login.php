<?php
	header('Content-type: text/html; charset=utf-8');
	
	if ( !isset($_GET['debug']) )
		error_reporting(0);
	//start session - security
	session_start();
	//check in session rather than cookie
	if ( isset($_SESSION['secretary_username']) )
		header( "Location: index.php" );
		
	ob_start();
	
	define( "BASE_PATH", dirname( $_SERVER["SCRIPT_FILENAME"] ) . "/" );
	define( "BASE_URL", str_replace( basename(__FILE__), "", $_SERVER['SCRIPT_URI'] ) );
	define( "SYSTEM" , BASE_PATH  . "system/" );
	define( "SYSTEM_URL", BASE_URL  . "system/" );
	
	require_once "system/assistants/config.inc.php";
	require_once "system/assistants/utf8.php";
	require_once "system/assistants/clerk.php";
	require_once "system/assistants/guard.php";
	require_once "system/assistants/receptionist.php";
	require_once "system/assistants/office.php";
	require_once "system/assistants/manager.php";
	
	$manager= new Manager();
	$manager->clerk->dbConnect();
	$manager->clerk->loadSettings();
	
	function process()
	{
		global $manager;
		
		// $_POST= $manager->clerk->clean( $_POST );

		$username= $_POST["username"];
		//avoiding sql injection - security
		$username = mysql_real_escape_string($username);
		$row= $manager->clerk->query_select( "users", "", "WHERE username='$username'");
	    	$num= $manager->clerk->query_numRows($row);
		
		$password_encrypted= mysql_result($row,0,'password');
		//verify password - for security reasons
		if ( $num == 1 && password_verify($_POST["password"], $password_encrypted))
		{
			//set values in session not cookies - for added security
			$_SESSION['secretary_username']="$username";
			$_SESSION['secretary_password']= "$password_encrypted";
			header( "Location: index.php" );
			
		}else
		{
			//for upgrading users - security reasons
			$password_encrypted= sha1($_POST["password"]);
		
			$row= $manager->clerk->query_select( "users", "", "WHERE username='$username' AND password='$password_encrypted'" );
		    	$num= $manager->clerk->query_numRows($row);
	
			if ( $num == 1 )
			{	
				//set more secure hash
				$pass_new = password_hash( $_POST["password"], PASSWORD_DEFAULT);
				$_SESSION['secretary_username']="$username";
				$_SESSION['secretary_password']= "$pass_new";
							
				$manager->clerk->query_edit("users","password='$pass_new'",  "WHERE username='$username'");
				header( "Location: index.php" );
			}
			else
				$manager->message( 0, false, "Sorry, that login information does not exist! Please try again." );
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-ype" content="text/html; charset=utf-8" />
		<title><?php echo $manager->clerk->getSetting( 'site', 1 ); ?> / The Secretary / Login</title>
		<?php
			$manager->office->head_tags();
		?>
	</head>
	<body>
		<div id="layout">
			<div id="header">
				<div id="navHolder">
					<div id="nav" class="center">
						
					</div>
				</div>
				<div id="titleHolder">
					<div id="title" class="center">
						<h1><?php echo $manager->clerk->getSetting( 'site', 1 ); ?> / The Secretary / <span class="active">Login</span></h1>
						<div id="appTitle">
							<a href="http://www.secretarycms.com">The Secretary</a>
						</div>
					</div>
				</div>
			</div>
			<div id="formMessage">
				<?php
					$manager->form= new Receptionist( "input", "post", $manager->office->URIquery(), "submit", "", "process" );
					$manager->form->save_state();
				?>
			</div>
			
			<div id="app" class="center">
				<?php
					$manager->form->add_input( "text", "username", "Username" );
					$manager->form->add_input( "password", "password", "Password" );

					$manager->form->add_rule( "username" );
					$manager->form->add_rule( "password" );

					$manager->form->draw();
				?>
				<div class="formButtons">
					<div class="primary">
						<?php
							$manager->form->set_template( "row_start", "" );
							$manager->form->set_template( "row_end", "" );
							
							$manager->form->add_input( "submit", "submit", "Submit", "login" );
							$manager->form->draw();
							
							$manager->form->reset_template( "row_start" );
							$manager->form->reset_template( "row_end" );
						?>
					</div>
				</div>
				<?php
					$manager->form->close();
				?>
			</div>
		</div>
	</body>
</html>
<?php
	ob_end_flush();
?>
