<?php
	header('Content-type: text/html; charset=utf-8');
	
	if ( !isset($_GET['debug']) )
		error_reporting(0);
	
	ob_start();
	
	define( 'BASE_PATH', dirname( $_SERVER["SCRIPT_FILENAME"] ) . "/" );
	define( 'BASE_URL', "http://" . $_SERVER['SERVER_NAME'] . dirname( $_SERVER['REQUEST_URI'] ) . "/" );
	define( "SYSTEM" , BASE_PATH  . "system/" );
	define( "SYSTEM_URL", BASE_URL  . "system/" );
	
	require_once BASE_PATH . "system/assistants/utf8.php";
	require_once BASE_PATH . "system/assistants/config.inc.php";
	require_once BASE_PATH . "system/assistants/clerk.php";
	require_once BASE_PATH . "system/assistants/guard.php";
	require_once BASE_PATH . "system/assistants/receptionist.php";
	require_once BASE_PATH . "system/assistants/office.php";
	require_once BASE_PATH . "system/assistants/manager.php";
	
	$manager= new Manager();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<?php
			$manager->office->head_tags();
		?>
		<title>The Secretary / Install</title>
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
						<h1>The Secretary / Install</h1>
						<div id="appTitle">
							<a href="http://www.secretarycms.com">The Secretary</a>
						</div>
					</div>
				</div>
			</div>
			<div id="formMessage">
				<?php
					if ( $manager->clerk->getSetting( "site" ) != "" )
					{
						echo $manager->message( 0, false, "You've already installed me! For security reasons you should delete this file." );
						exit;
					}
					
					if ( phpversion() < 5 )
					{
						echo $manager->message( 0, false, "Oh brutal! The Secretary requires PHP 5 or greater! You are running version ".phpversion()."." );
						exit;
					}
					
					$manager->form= new Receptionist( "input", "post", $manager->office->URIquery(), "submit", "multipart/form-data", "process" );
					$manager->form->save_state();
				?>
			</div>
			<div id="app" class="center">
				<?php					
					function process()
					{
						global $manager;

						# Connect
						$db= array(
								'DB_SERVER'		=>	$_POST['db_server'],
								'DB_NAME'		=>	$_POST['db_name'],
								'DB_USERNAME'	=>	$_POST['db_username'],
								'DB_PASSWORD'	=>	$_POST['db_password']
						);
						
						if ( !$manager->clerk->dbConnect( $db ) )
						{
							echo $manager->message( 0, false, "Uh-oh! A test connection to your database could not be made. Double check your info." );
							return;
						}
						
						$manager->clerk->preserve_vars( "db_password, password" );
						$_POST= $manager->clerk->clean( $_POST );

						# Write config file
						$error		=	false;
						$config		= 	fopen( SYSTEM . "assistants/config.inc.php", "w+");
						$write		= 	array(
											'<?php',
											'$settings[\'DB_SERVER\']= "' . $_POST['db_server'] . '";',
											'$settings[\'DB_NAME\']= "' . $_POST['db_name'] . '";',
											'$settings[\'DB_USERNAME\']= "' . $_POST['db_username'] . '";',
											'$settings[\'DB_PASSWORD\']= "' . $_POST['db_password'] . '";',
											"\n",
											'$settings[\'COOKIE_TIME\']= "604800";',
											'$settings[\'COOKIE_PATH\']= "";',
											'$settings[\'COOKIE_DOMAIN\']= ".";',
											"\n",
											'$settings[\'SKIN\']= "starling";',
											"?>",
						);

						foreach ( $write as $w )
						{
							if ( !fwrite( $config, $w . "\n" ) )
								$error= true;
						}

						fclose( $config );
						
						# Write site file
						$error				=	false;
						$site_file			= 	fopen( BASE_PATH . "site/site.php", "w+" );
						$site_file_contents	= 	array(
													'<?php',
													'define( "HQ", "' . BASE_PATH . '" );',
													'define( "HQ_URL", "' . BASE_URL . '" );',
													'include_once HQ . "site/index.php";',
													"?>",
						);

						require_once SYSTEM . "assistants/config.inc.php";

						foreach ( $site_file_contents as $line )
						{
							if ( !fwrite( $site_file, $line . "\n" ) )
								$error= true;
						}

						fclose( $site_file );

						# Open SQL file
						$dump	=	fopen( "system/install_assets/setup.sql", "r" ); 
						$file 	= 	fread( $dump, 80000 ); 
						fclose( $dump ); 

						# Split into separate queries 
						$lines 	= explode( ';',  $file );
						$count 	= count( $lines );
						$queries= array();
						foreach ( $lines as $line )
						{
							$queries[]= trim( $line );
						}

						# Execute the queries
						$count= 0;
						foreach ( $queries as $q )
						{
							$count++;
							if ( empty( $q ) ) continue;

							if ( !$manager->clerk->query( $q ) )
							{
								echo "Error! Line $count<br />$q<br />";
							}
						}
						
						# Re-initialize
						$manager->clerk->disconnect();
						$manager->clerk->dbConnect( $db );
						$manager->clerk->loadSettings();
						
						# Setup user
						$username	=	$_POST['username'];
						$password	=	$_POST['password'];
						$email		=	$_POST['email'];
						
						$siteUrl	=	"http://" . str_replace( "http://", "", $_POST['site_url'] );
						$siteUrl	=	( strrchr( $siteUrl, "/" ) == "/" ) ? substr( $siteUrl, 0, strrchr( $siteUrl, "/" ) - 1 ) : $siteUrl;
						
						# Create user if it doesn't exist already
						# Sometimes an install error means the installer has to be run again
						# resulting in duplicates.
						
						//fix for security reasons
						if ( $manager->clerk->query_countRows( "users", "WHERE username= '$username'" ) == 0 )
							$manager->clerk->query_insert( "users", "username, password, email", "'$username',  password_hash( $password, PASSWORD_DEFAULT), '$email'" );
						
						# Update settings
						$manager->clerk->updateSettings(
							array(
								'app'						=>	array( "2.3", "", "" ),
								'site'						=>	array( $_POST['site_name'], $siteUrl ),
								'projects_path'				=>	array( BASE_PATH . 'files/projects/', BASE_URL . 'files/projects/' ),
								'cache_path'				=>	array( BASE_PATH . "files/cache/", BASE_URL . "files/cache/", 0 ),
								'mediamanager_path'			=>	array( BASE_PATH . "files/media/", BASE_URL . "files/media/" ),
								'mediamanager_thumbnail' 	=> 	array( "100", "100" ),
								'blog_path'					=>	array( BASE_PATH . "files/blog/", BASE_URL . "files/blog/", "" ),
								'blog_thumbnail'			=>	array( "100", "0", "" ),
								'blog_intelliscaling'		=>	array( "1", "", "" )
							)
						);
						
						# Send password confirmation email
						$subject = 'The Secretary Username & Password';
						$message = 'Hello! You have just installed The Secretary. So you don\'t forget, your username is "' . $username . '" and your password is "' . $_POST['password'] . '".';
						$headers = 'From: secretarybot' . "\r\n" .
						    'Reply-To: code@nivr.net' . "\r\n" .

						mail( $email, $subject, $message, $headers );
						
						if ( file_exists( BASE_PATH . "site/site.php" ) == false )
						{
							$code= '<pre>';
							foreach ( $site_file_contents as $line )
							{
								$code.= $line . "\n";
							}
							
							$code.= '</pre>';
							
							echo message( "warning", 'The Secretary was <em>almost</em> successfully installed! You need to do one little thing before everything works as it should: create a file called index.php and paste the following into it:<br/>' . $code . '<br /><br />Upload this file to: ' . $siteUrl . '<br /><br />For security reasons, you should delete this file (install.php). After you have done that, you may <a href="login.php">login</a>.' );
						}
						else
						{
							echo $manager->message( 1, false, 'Congratulations, The Secretary was successfully installed!<br /><br />Your website file has been created and can be found at <em>' . BASE_URL . 'site/site.php</em>. In order for your Secretary-powered website to work, please move it to <em>' . $siteUrl . '</em> and rename it <em>index.php</em>. <br /><br />For security reasons, you should delete this file (install.php). After you have done that, you may <a href="login.php">login</a>.' );
						}
						// unlink( __FILE__ );
					}
					
					$manager->form->message(
						'<br /><br />Before you begin installing The Secretary, double-check that your host meets the <a href="http://www.secretarycms.com/guide/setup/application-requirements" class="external">requirements</a>. You must also have the connection information to your MySQL database (contact your webhost for this information if you do not have it). You will need the following information:
						<br /><br />
						- database host<br />	
						- database name<br />
						- database username<br />
						- database password<br /><br />
						Make sure that you have changed the permissions (chmoded) on your Secretary folder and its contents (<em>' . str_replace( '/', '', dirname( $_SERVER['REQUEST_URI'] ) ) . '</em>) to 755. See the <a href="http://www.secretarycms.com/guide/setup/installation-instructions" class="external">installation instructions</a> for more details.
						'
					);
					
					$manager->form->draw();
					
					$manager->form->set_template( "row_start", "" );
					$manager->form->set_template( "row_end", "" );
				?>
				
				<div class="formButtons">
					<div class="primary">
						<?php
							$manager->form->add_input( 'submit', 'submit', 'Submit', 'save' );
							$manager->form->draw();
						?>
					</div>
					<div class="secondary">
						<?php
							$manager->form->draw();
							
							$manager->form->reset_template( "row_start" );
							$manager->form->reset_template( "row_end" );
						?>
					</div>
				</div>
				
				<?php
					$manager->form->add_fieldset( "Database Information", "dbInfo" );
					$manager->form->message( 'This information is very important and must be correct. If you are unsure what to enter into each field, contact your webhost and ask for your <strong>MySQL database information</strong>.' );
					$manager->form->add_input( "text", "db_server", "MySQL Database Server / Host" );
					$manager->form->add_input( "text", "db_name", "Database Name" );
					$manager->form->add_input( "text", "db_username", "Database Username" );
					$manager->form->add_input( "password", "db_password", "Database Password" );
					$manager->form->close_fieldset();
					
					$manager->form->add_fieldset( "Website Information", "websiteInfo" );
					$manager->form->message( 'Enter the name and address of your website here. The address of your website should NOT be ' . BASE_URL . '. If you are installing Secretary in www.yourdomain.com/cms, then your website URL will most likely be www.yourdomain.com.' );
					$manager->form->add_input( "text", "site_name", "Website Name" );
					$manager->form->add_input( "text", "site_url", "Website URL / Address" );
					$manager->form->close_fieldset();
					
					$manager->form->add_fieldset( "User Information", "userInfo" );
					$manager->form->message( 'Enter your desired username and password to login to The Secretary.' );
					$manager->form->add_input( "text", "username", "Username" );
					$manager->form->add_input( "password", "password", "Password" );
					$manager->form->add_input( "text", "email", "Your E-mail" );
					$manager->form->close_fieldset();
					
					$manager->form->draw();
					
					$manager->form->add_rule( "db_server" );
					$manager->form->add_rule( "db_name" );
					$manager->form->add_rule( "db_username" );
					$manager->form->add_rule( "db_password" );
					$manager->form->add_rule( "site_name" );
					$manager->form->add_rule( "site_url" );
					$manager->form->add_rule( "username" );
					$manager->form->add_rule( "password" );
					$manager->form->add_rule( "email" );
				?>
				
				<div class="formButtons">
					<div class="primary">
						<?php
							$manager->form->set_template( "row_start", "" );
							$manager->form->set_template( "row_end", "" );
							
							$manager->form->add_input( 'submit', 'submit', 'Submit', 'save' );
							
							$manager->form->draw();
						?>
					</div>
					<div class="secondary">
						<?php
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
	ob_flush();
?>
