<?php
   	/*
	 * The Clerk / The Secretary
	 * by Mikael Stær (www.secretarycms.com, www.mikaelstaer.com)
	 * 
	 * Database interface. Handles settings as well.
	 */
   
	class Clerk
	{
		private $config			= 	array();
		private $preserved_vars	=	array();
		private $globalSettings	=	array();
		
		public 	$queries		= 	0;
		public 	$link;
		
		function __construct( $auto= true )
		{
			global $settings;
		
			$this->config= $settings;
			
			$this->config['ASSISTANTS_PATH']	= SYSTEM . "assistants/";
			$this->config['HELPERS_PATH']		= SYSTEM . "assistants/helpers/";
			$this->config['CUBICLES_PATH']		= SYSTEM . "cubicles/";
			$this->config['GUI_PATH']			= SYSTEM . "gui/";
			$this->config['SKIN_PATH']			= SYSTEM . "gui/" . $settings['SKIN'] . "/";
			$this->config['SKIN_URL']			= SYSTEM_URL . "gui/" . $settings['SKIN'] . "/";
			
			if ( $auto )
			{
				$this->dbConnect();
				$this->loadSettings();
			}
		}
		
		public function config( $var )
		{
			return $this->config[$var];
		}
	
		public function loadSettings()
		{
			$settings= array();
				
			$getSettings= $this->query_select( "global_settings" );
			while ( $setting= $this->query_fetchArray( $getSettings ) )
			{
				// if ( empty( $setting['data1'] ) && empty( $setting['data2'] ) && empty( $setting['data3'] ) ) continue;
					
				$settings[$setting['name']]= array(
					'data1'	=>	$setting['data1'],
					'data2'	=>	$setting['data2'],
					'data3'	=>	$setting['data3']
				);
			}
			
			$this->globalSettings= $settings;
			
			return $this->globalSettings;
		}
		
		public function getSetting( $name, $which= "" )
		{
			if ( empty( $this->globalSettings[$name] ) ) return false;
			return ( !empty($which) && is_int($which) ) ? $this->globalSettings[$name]['data'.$which] : $this->globalSettings[$name];
		}
		
		public function updateSetting( $name, $vals )
		{
			$val1= $vals[0];
			$val2= $vals[1];
			$val3= $vals[2];
			
			if ( $this->settingExists( $name ) == false )
			{
				$this->addSetting( $name, $vals );
				return true;
			}
			
			if ( $this->query_edit( "global_settings", "data1= '$val1', data2= '$val2', data3= '$val3'", "WHERE name= '$name'" ) )
			{
				$this->loadSettings();
				return true;
			}
				
			return false;
		}
		
		public function updateSettings( $settings )
		{
			$success= true;
			
			foreach ( $settings as $name => $vals )
			{
				if ( $this->settingExists( $name ) == false )
				{
					$this->addSetting( $name, $vals );
					continue;
				}
				
				$val1= $vals[0];
				$val2= $vals[1];
				$val3= $vals[2];
				
				if ( $this->query_edit( "global_settings", "data1= '$val1', data2= '$val2', data3= '$val3'", "WHERE name= '$name'" ) == false )
				{
					$success= false;
				}
			}
			
			$this->loadSettings();
			
			return $success;
		}
		
		public function addSetting( $name, $vals= "" )
		{
			if ( $this->settingExists( $name ) ) return true;
			
			$val1= $vals[0];
			$val2= $vals[1];
			$val3= $vals[2];
			
			$cols= ( empty( $vals ) ) ? "name" : "name, data1, data2, data3";
			$values= ( empty( $vals ) ) ? "'$name'" : "'$name','$val1', '$val2', '$val3'";
			
			if ( $this->query_insert( "global_settings", "$cols", "$values" ) )
			{
				$this->loadSettings();
				return true;
			}
				
			return false;
		}
		
		public function addSettings( $settings )
		{
			$success= true;
			
			foreach ( $settings as $name => $vals )
			{
				if ( $this->settingExists( $name ) ) continue;

				$val1= $vals[0];
				$val2= $vals[1];
				$val3= $vals[2];

				$cols= ( empty( $vals ) ) ? "name" : "name, data1, data2, data3";
				$values= ( empty( $vals ) ) ? "'$name'" : "'$name','$val1', '$val2', '$val3'";

				if ( $this->query_insert( "global_settings", "$cols", "$values" ) == false )
				{
					$success= false;
				}
			}
			
			$this->loadSettings();
			
			return $success;
		}
		
		public function deleteSetting( $name )
		{
			if ( $this->query_delete( "global_settings", "WHERE name= '$name'" ) )
			{
				$this->loadSettings();
				return true;
			}
			
			return false;
		}
		
		public function settingExists( $name )
		{
			
			//return ( $this->query_numRows( "global_settings", "WHERE name= '$name'" ) == 1 );
			return array_key_exists( $name, $this->globalSettings );
		}
	
		public function dbConnect( $info= "" )
		{
			$info= ( empty( $info ) ) ? $this->config : $info;
			
			$this->link= mysql_connect($info["DB_SERVER"], $info["DB_USERNAME"], $info["DB_PASSWORD"], false, MYSQL_CLIENT_INTERACTIVE );
			mysql_query( "SET NAMES 'utf8'", $this->link );
			mysql_query( "SET CHARACTER SET utf8", $this->link );
			
			return mysql_select_db( $info["DB_NAME"] );
		}
		
		public function disconnect()
		{
			$this->link= "";
			mysql_free_result();
			mysql_close();
		}
				
		public function query_insert( $table, $fields, $values, $multiple= false )
		{
			$this->queries++;
			$values= ( $multiple ) ? $values : "($values)";
			
			if ( mysql_query("INSERT INTO $table ($fields) VALUES $values") )
				return true;
			else
				die( $this->message( 0, true, 'Uh-oh...') );
		}
	
		public function query_edit($table, $values, $where= "")
		{
			$this->queries++;

			if ( mysql_query("UPDATE $table SET $values $where") )
				return true;
			else
				die( $this->message( 0, true, 'Uh-oh...') );
		}

		public function query_delete($table, $where)
		{
			$this->queries++;
		
			if (mysql_query("DELETE FROM $table $where"))
				return true;
			else
				die( $this->message( 0, true, 'Uh-oh...') );
		}
	
		public function query_select( $table, $fields= "", $wol= "", $order= "" )
		{
			$this->queries++;
		
			$query= "SELECT";
		
			if (!empty($fields))
				$query.= " ".$fields;
			else
				$query.= " *";
			
			$query.= " FROM $table";

			if (!empty($wol))
				$query.= " ".$wol;
			
			return mysql_query($query);
		}
		
		public function get_fields( $table )
		{
			return mysql_query( "SHOW COLUMNS FROM $table" );
		}
	
		public function num_fields( $query )
		{
			return mysql_num_fields( $query );
		}
	
		public function query_fetchArray( $get, $how= "assoc" )
		{
			switch ( $how )
			{
				case "num":
					$how= MYSQL_NUM;
					break;
				case "assoc":
					$how= MYSQL_ASSOC;
					break;
				case "both":
					$how= MYSQL_BOTH;
					break;
				default:
					$how= MYSQL_BOTH;
					break;
			}
			
			return mysql_fetch_array( $get, $how );
		}
	
		public function query_fetchRow( $get )
		{
			return mysql_fetch_row( $get );
		}
	
		public function query_numRows( $get )
		{
			return mysql_num_rows( $get );
		}
	
		public function query_countRows ( $table, $wol= "" )
		{
			$row= $this->query_fetchArray( $this->query_select( $table, "COUNT(*) as numGrabbed", $wol ) );
			return $row['numGrabbed'];
		}
	
		public function query_empty( $table )
		{
			$this->queries++;

			if ( mysql_query( "TRUNCATE TABLE $table" ) )
				return true;
			else
				die( $this->message( 0, true, 'Uh-oh...') );
		}
		
		public function query( $query )
		{
			return mysql_query( $query );
		}
		
		public function addColumn( $table, $column, $details )
		{
			return mysql_query( "ALTER TABLE " . $table . " ADD " . $column . " " . $details . ";" );
		}
		
		public function alterTable( $table, $action, $details )
		{
			return mysql_query( "ALTER TABLE " . $table . " " . $action . " " . $details );
		}
		
		public function tableExists( $table )
		{
			return ( mysql_num_rows( mysql_query( "SHOW TABLES LIKE '" . $table . "'" ) ) == 1 );
		}
		
		public function lastID()
		{
			return mysql_insert_id();
		}
		
		public function nextID( $table )
		{
			$query = mysql_query("SHOW TABLE STATUS LIKE '$table' ");
			$row = mysql_fetch_array($query);
			mysql_free_result($query);
			
			return $row['Auto_increment'];
		}
		
		public function error()
		{
			return mysql_error();
		}
		
		public function clean( $vars ) 
		{
			$new= array();
		
			foreach( $vars as $key=>$val )
			{
				if ( gettype( $val ) == "array" )
				{
					$new[$key]= $this->clean( $val );
				}
				else
				{
					$string= $val;
					if ( get_magic_quotes_gpc() )
					{
						$string= stripslashes( $string );
					}
					
					if ( !in_array( $key, $this->preserved_vars ) )
					{
						$string= mysql_real_escape_string( htmlspecialchars( $string, ENT_COMPAT, 'UTF-8') );
					}
					
					$new[$key]= $string;
				}
			}
			
			return $new;
		}
		
		public function clean_string( $var )
		{
			$string= $var;
			
			if ( get_magic_quotes_gpc() )
			{
				$string= stripslashes( $var );
			}
			
			if ( !in_array( $var, $this->preserved_vars ) )
			{
				$string= mysql_real_escape_string( htmlspecialchars( $string, ENT_COMPAT, 'UTF-8') );
			}	
			
			return $string;
		}
		
		public function preserve_vars( $vars )
		{
			$vars= explode( ",", str_replace(" ", "" , $vars ) );
			$this->preserved_vars= array_merge( $this->preserved_vars, $vars );
			
			return $this->preserved_vars;
		}
		
		public function simple_name( $string )
		{
			$quick_search= 	array( "-", " ", "--" );
			$quick_replace=	array( "-", "-", "-" );
			$string= preg_replace( "/[^a-zA-Z0-9-_]/u", "", str_replace( $quick_search, $quick_replace, utf8_strtolower( $string ) ) );
			return $string;
		}
		
		public function complex_name( $string )
		{
			$quick_search	= 	array( "-" );
			$quick_replace	=	array( " " );
			$string= str_replace( $quick_search, $quick_replace, $string );
			return $string;
		}
		
		private function message( $type= 0, $mysql_error= false, $text )
		{
			$type= ( $type == 1 ) ? "success" : "error";
		
			if ( $mysql_error )
				$text.= "<br /><i>MySQL says:</i> ".mysql_error();
			
			return '<div class="message_'.$type.'">'.$text.'</div>';
		}
	
}
?>