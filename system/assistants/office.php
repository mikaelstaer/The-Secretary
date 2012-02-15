<?php
	/*
	 * The Office / The Secretary
	 * by Mikael Stær (www.secretarycms.com, www.mikaelstaer.com)
	 *
	 * Generates the menu and takes care of other general GUI things.
	 *
	 */
	
	class Office
	{
		private $cubicle;
		private $menu;
		private $menu_html;
		
		public function init()
		{
			global $manager;

			$namespace= ( empty( $_GET['cubicle'] ) ) ? "home" : $_GET['cubicle'];
			$namespace_array= explode( "-", $namespace );

			$this->cubicle['REQUEST']	= $namespace;
			$this->cubicle['BRANCH']	= $namespace_array[0];
			$this->cubicle['CUBICLE'] 	= $namespace_array[count( $namespace_array )-1];
		}
		
		public function cubicle( $var )
		{
			return $this->cubicle[$var];
		}
		
		public function generateMenu()
		{
			if ( MINI )
			{
				$menu= call_anchor( "minimenu", array() );
				$menu= call_anchor( "minimenu_modify", $menu );
				$this->menu= array( $menu );
			}
			else
			{
				$menu= call_anchor( "menu", array() );
				$menu= call_anchor( "menu_modify", $menu );
				$this->menu= array( $menu );
			}
			
			uasort( $this->menu[0], array("Office", "menu_SortOrder") );
			
			$this->menu_html= $this->menu_MakeHTML( $this->menu[0] );
		}
		
		public function pageTitle()
		{
			global $manager;
			
			$site_name	=	$manager->clerk->getSetting( 'site', 1 );
			$cms_name	=	"The Secretary";
			
			$parts		= 	array_reverse( explode( "-", $this->cubicle( 'REQUEST' ) ) );
			$request 	= 	$this->cubicle( 'REQUEST' );
			$total		=	count( $parts );
			$count		= 	$total;
			
			if ( MINI )
			{
				$parent= $this->menu_ExtractBranch( $parts[1], $type= "branch" );
				$page= $this->menu[0][$request];
				$location= $parent['dis_name'] . " - " . $page['dis_name'] . $location;
			}
			else
			{
				foreach ( $parts as $p )
				{
					$count--;
					$page= $this->menu_ExtractBranch( $request, $type= "branch" );
					$location= ( $count > 0 ) ?  " - " . $page['dis_name'] . $location : $page['dis_name'] . $location;
					$request= substr( $request, 0, strpos( $request, $p )-1 );
				}
			}
			
			echo $location;
			echo ( !empty( $cms_name ) ) ? " / " : "";
			echo $cms_name;
			echo ( !empty( $site_name ) ) ? " / " : "";
			echo $site_name;	
		}
		
		public function make_URIquery( $vars, $start= true ) {
			if ( gettype($vars) == "array" )
			{
				$q= htmlentities( http_build_query($vars) );
			}
			elseif ( gettype($vars) == "string" )
			{
				$q= htmlentities($vars);
			}
			
			return ( $start ) ? "?" . $q : "&" . $q;
		}
		
	   /*
		* $key : Array/String/NULL
		* $append: Array/String/NULL
		*
		* Various options here, pretty cool!
		* 1. Returns the GET string as is
		* 2. If $key is supplied, will strip those values out of the GET string
		* 3. If $append is supplied, will call make_URIquery() and append that string
		* 
		* Results in a totally custom, validation-safe query string! 
		*/
		public function URIquery( $key= "", $append= "" )
		{
			$query= parse_str( $_SERVER['QUERY_STRING'], $vars );
			
			if ( gettype($key) == "array" )
			{
				foreach ( $key as $k )
				{
					unset( $vars[$k] );
				}
				
				$q= "?" . htmlentities( http_build_query($vars) );
			}
			elseif ( gettype($key) == "string" )
			{
				unset( $vars[$key] );
				$q= "?" . htmlentities( http_build_query($vars) );
			
			}
			else
				$q= "?" . htmlentities( $_SERVER['QUERY_STRING'] );
			
			return ( !empty($append) ) ? $q . $this->make_URIquery( $append, false ) : $q;
		}

		public function make_breadcrumb()
		{
			$request= explode( "-", $this->cubicle('REQUEST') );
				
			$breadCrumb= "";
			$count= 0;

			foreach ( $request as $part )
			{
				$chain.= ( $count == 0 ) ? $part : "-" . $part;
				$link=	( $count >= 1 ) ? "?cubicle=" . $chain : "#";
				$module= $this->menu_ExtractBranch( $chain, "branch", $this->menu );
				$breadCrumb.= ( $count >= 0 ) ? '<a href="' . $link . '">' . $module['dis_name'] . '</a>': "";
				$breadCrumb.= ( $count >= 0 && $count < ( count( $request ) - 1 )  ) ? " / " : "";
					
				$count++;
			}
			
			$breadCrumb.= ( countHooks( "breadcrumbActive" ) > 0 ) ? " / " : "";
				
			return $breadCrumb;
		}
		
		public function head_tags()
		{	
			global $manager;
			
			$skin= $manager->clerk->config('SKIN');
			if ( empty( $skin ) )
				$skin= "starling";
			
			// Grab common and SKIN CSS
			$css= scanFolder( SYSTEM . "gui/common_css", 1 );
			sort( $css );
			foreach ( $css as $file )
			{
				if ( strstr( $file, ".css" ) )
					echo $this->style( SYSTEM_URL . "gui/common_css" . basename( $file ) );
			}
			
			$css= scanFolder( SYSTEM . "gui/" . $skin, 1 );
			sort( $css );
			foreach ( $css as $file )
			{
				if ( strstr( $file, ".css" ) )
					echo $this->style( SYSTEM_URL . "gui/" . $skin . "/" . basename( $file ) );
			}
			
			$js= scanFolder( SYSTEM . "gui/common_js", 1 );
			sort( $js );
			foreach ( $js as $file )
			{
				if ( strstr( $file, ".js" ) )
					echo $this->jsfile( SYSTEM_URL . "gui/common_js/" . basename( $file ) );
			}

			$js= scanFolder( SYSTEM . "gui/" . $skin . "/js", 1 );
			sort( $js );
			foreach ( $js as $file )
			{
				if ( strstr( $file, ".js" ) )
					echo $this->jsfile( SYSTEM_URL . "gui/" . $skin . "/js/" . basename( $file ) );
			}
			
			// Include module JS and CSS assets (actions.js and styles.css)
			$styles= "modules/" . $this->cubicle['BRANCH'] . "/assets/styles.css";
			$actions= "modules/" . $this->cubicle['BRANCH'] . "/assets/actions.js";
			
			if ( file_exists( SYSTEM . $styles ) ) echo $this->style( SYSTEM_URL . $styles );
			if ( file_exists( SYSTEM . $actions ) ) echo $this->jsfile( SYSTEM_URL . $actions );
		}
		
		public function echo_r( $array )
		{
			echo '<pre>';
			print_r($array);
			echo '</pre>';
		}
		
		static function menu_SortOrder( $a, $b )
		{
			if ($a['order'] == $b['order'])
			{
			    return 0;
			}

			return ($a['order'] < $b['order']) ? -1 : 1;
		}
		
		private function menu_MakeHTML( $array, $parent= "", $start= true )
		{
			global $manager;

			static $list;

			$count= 0;
			foreach ( $array as $m )
			{
				$requestString= ( empty( $parent ) ) ? $m['sys_name'] : $parent . "-" . $m['sys_name'];

				if ( ( $m['off'] == 0 || $m['default'] == 1 ) && $m['hidden'] == 0 )
				{
					if ($start)
					{
						$aClass		=	"top";
						$active		=	( $this->cubicle( "BRANCH" ) == $m['sys_name'] ) ? " active" : "";
						$url		= 	( empty( $m['url'] ) ) ? '?cubicle=' . $requestString : $m['url'];
						
						// Mini
						if ( MINI )
						{
							$active		=	( $this->cubicle( "REQUEST" ) == $m['sys_name'] ) ? " active" : "";
							$url		=	$this->URIquery( "cubicle", "cubicle=" . $requestString );
						}
						
					}
					elseif ( !$start && count( $m['children'] ) > 0 )
					{
						$url	=	( empty( $m['url'] ) ) ? "#" : $m['url'];
					}
					else
					{
						$url	=	( empty( $m['url'] ) ) ? '?cubicle=' . $requestString : $m['url'];
					}

					$list.= '<li id="' . $m['sys_name'] . '" class="' . $aClass . $active .'"><a href="' . $url . '" class="' . $aClass . '">' . $m['dis_name'] . '</a>' . "\n";

					if ( is_array( $m['children'] ) && count( $m['children'] ) > 0 )
					{
						$class= "";
						$list.= '<ul>'."\n";
						$this->menu_MakeHTML( $m['children'], $requestString, false );
						$list.= "</ul>"."\n";
					}
					
					$list.= "</li>"."\n";
					$count++;	
				}
			}
			
			return '<ul id="navActual">' . $list . '</ul>';
		}
		
		public function printMenu()
		{	
			echo $this->menu_html;
		}
		
		public function getMenu()
		{
			return $this->menu[0];
		}
		
		private function menu_GrabChild( $request, $menu, $last )
		{
			if ( !empty ( $menu ) )
			foreach ( $menu as $m )
			{
				if ( $m['sys_name'] == $request && is_array($m['children']) && !$last )
				{
					return $m['children'];
				}
				elseif( $m['sys_name'] == $request && $last )
				{
					return $m;
				}
			}
		}
		
		/*
		 * $request : String
		 * $type: String
		 * $menu: Array
		 *
		 * Well this was quite the bitch to figure out...Extracts the requested menu piece from the full
		 * menu array. Can return either a "parent" array, ie. 'Admin', or return the array based on a
		 * cubicle request string, in the form of 'admin-users-add', which would return the array for
		 * 'add'. Useful if data about a specific module or branch is required, as needed in
		 * Guard::user_has_access() for example.
		 *
		 * Don't think that the two types are needed actually. Pretty sure full functionality is in
		 * the 'branch' type...really really don't feel like testing this right now, waaaay to late.
		 *
		 * 2012: Ummm...wtf? Don't need this mess anymore...
		 */
		public function menu_ExtractBranch( $request, $type= "module", $menu= "" )
		{
			if ( $type == 'module')
			{
				$menu= ( empty($menu) ) ? $this->menu : $menu;
				$count= 0;
				// $this->echo_r( $this->menu[0] );
				if ( count( $menu ) > 0 )
				foreach ( $this->menu[0] as $item )
				{
					if ( $item['sys_name'] == $request )
						return $menu[0][$count];
					$count++;
				}
			}
			elseif ( $type == 'branch' )
			{
				$req= explode("-", $request);
				$menu= $this->menu[0][$req[0]];
				
				if ( count($req) == 1 )
				{
					return $menu;
				}
				else
				{
					$children= $menu['children'];
					$req= array_slice( $req, 1 );
					$count= 0;
					foreach ( $req as $r )
					{
						$count++;
						$last= ( count($req) == $count ) ? true : false;
						$children= $this->menu_GrabChild( $r, $children, $last );
					}
					return ( is_array($children) ) ? $children : false;
				}
			}
		
		}
					
		public function js( $code )
		{
			$html	 = 	'<script type="text/javascript" charset="utf-8">'."\n";
			$html	.= 	'	'.$code."\n";	
			$html	.=	'</script>'."\n";
			
			return $html;
		}
		
		public function jsfile( $file )
		{
			return '<script type="text/javascript" src="' . $file . '"></script>'."\n";
		}
		
		public function jquery( $code )
		{
			$html	 = 	'<script type="text/javascript" charset="utf-8">'."\n";
			$html	.=	'	jQuery(function($) {'."\n";
			$html	.= 	'		'.$code."\n";	
			$html	.= 	'	});'."\n";
			$html	.=	'</script>'."\n";
			
			return $html;
		}
		
		public function style( $file )
		{
			return '<link rel="stylesheet" href="' . $file . '" type="text/css" media="screen" />' . "\n";
		}
		
		public function ajax( $url, $type, $data, $callback= "", $settings= "" )
		{
			$data= "{".$data."}";
			
			$html= <<<CODE
					$.ajax({ 	type: '$type',
								url: '$url',
								data: $data,
								success: function(data,status) {
									$callback
								},
								$settings
								});
CODE;
			
			return $html;
		}
	}
?>