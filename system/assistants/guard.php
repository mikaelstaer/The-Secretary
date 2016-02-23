<?php
	/*
	 * The Guard / The Secretary
	 * by Mikael Stær (www.secretarycms.com, www.mikaelstaer.com)
	 *
	 * Takes care of validating users.
	 */
	
	class Guard
	{
		private $user_attrs= array();
		private $clerk;
		
		function init()
		{
			global $manager;
			
			//Get username and password from session - for security reasons - replaced use of cookie
			$username	=	$_SESSION["secretary_username"];
			$password	= 	$_SESSION["secretary_password"];

			$user		= 	$manager->clerk->query_fetchArray( $manager->clerk->query_select( "users", "", "WHERE username= '$username' AND password= '$password'" ) );
			
			$this->user_attrs["USERNAME"]					= $user['username'];
			$this->user_attrs["PASSWORD"]					= $user['password'];
			$this->user_attrs["USER_ID"]					= $user['id'];
		}

		public function user( $var )
		{
			return $this->user_attrs[$var];
		}
		
		public function userAttribute( $var )
		{
			return $this->user( $var );
		}
		
		public function user_attr( $var )
		{
			return $this->user_attrs[$var];
		}

		public function data_escape( $string )
		{
			if ( get_magic_quotes_gpc() )
				return mysql_real_escape_string( stripslashes( $string ) );
			else
				return mysql_real_escape_string( $string );
		}

		public function this_path()
		{
			return dirname( $_SERVER["SCRIPT_FILENAME"] );
		}

		public function validate_user()
		{
			global $manager;
			
			//Get username and password from session - for security reasons - replaced use of cookie
			$username	= 	$_SESSION["secretary_username"];
			$password	= 	$_SESSION["secretary_password"];
			
			if ( empty( $username ) || empty( $password ) )
			{
				header("Location: login.php");
			}
			else
			{
				if ( $manager->clerk->query_numRows( $manager->clerk->query_select( "users", "", "WHERE username= '$username' AND password= '$password'" ) ) == 0 )
				{
					header("Location: logout.php");
				}
			}
		}
		
		public function validate_user_extern( $clerk, $username, $password )
		{	
			if ( $clerk->query_numRows( $clerk->query_select( "users", "", "WHERE username= '$username' AND password= '$password'" ) ) == 0 )
			{
				return false;
			}
			
			return true;
		}
		
		public function page_is_active()
		{
			global $manager;

			$page= $manager->office->menu_ExtractBranch( $manager->office->cubicle('REQUEST'), 'branch' );
			$code= 0;
			
			if ( $page == false )
				$code= 1;
				
			elseif ( $page['off'] == true )
				$code= 2;
			
			return $code;
		}
	}
?>
