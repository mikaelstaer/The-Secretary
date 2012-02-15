<?php
	// Define anchors
	define_anchor( "themeEdit" );
	define_anchor( "themeAfterSelectFiles" );
	define_anchor( "themeBeforeFileEditor" );
	define_anchor( "themeAfterFileEditor" );
	define_anchor( "themeEditProcess" );
	define_anchor( "themeFormButtons" );
	define_anchor( "themeEditJs" );
	
	// Define hooks
	hook( "start", "registerThemes" );
	
	if ( $_GET['action'] == "select" )
	{
		hook( "start", "selectTheme", array( $_GET['id'] ), 1 );
		hook( "big_message", "themeSelected", array( $_GET['id'] ), 1 );
		hook( "form_main", "themeList", "", 1 );
	}
	elseif ( $_GET['action'] == "edit" )
	{
		hook( "breadcrumbActive", "themeTitle", array( $_GET['id'] ) );
		hook( "form_main", "themeEdit", array( $_GET['id'] ) );
		hook( "javascript", "themeEditJs" );
		hook( "form_process", "themeEditProcess" );
		
		if ( !empty( $_GET['file'] ) )
		{
			hook( "form_submit_primary", "submitButtons" );
		}
	}
	else
	{
		hook( "form_main", "themeList" );
	}
	
	hook( "css", "themeEditCss" );
	
	// Define needed variables
	$themes= array();
	
	$manager->clerk->preserve_vars( "code" );
	
	// Functions
	function themeEditProcess()
	{
		global $manager;
		
		$code= $_POST['code'];
		$file= $_POST['file'];
				
		if ( file_put_contents( BASE_PATH . "site/themes/" . $_GET['id'] . "/" . $file, $code ) )
		{
			echo $manager->message( 1, false, "File saved!" );
		}
		else
		{
			echo $manager->message( 0, false, "Hmmm...the file couldn't be saved. Make sure the folders are <u>writeable</u>." );
		}
		
		call_anchor( "themeEditProcess" );
	}
	
	function submitButtons()
	{
		global $manager;
		
		$manager->form->add_input( 'submit', 'submit', 'Save', 'save' );
		call_anchor( "themeFormButtons" );
	}
	
	function themeEditJs()
	{
		global $manager;
		
		echo $manager->office->jsfile( SYSTEM_URL . "modules/design/assets/codemirror/codemirror.js" );
		echo $manager->office->jsfile( SYSTEM_URL . "modules/design/assets/codemirror/xml.js" );
		echo $manager->office->jsfile( SYSTEM_URL . "modules/design/assets/codemirror/javascript.js" );
		echo $manager->office->jsfile( SYSTEM_URL . "modules/design/assets/codemirror/css.js" );
		echo $manager->office->jsfile( SYSTEM_URL . "modules/design/assets/codemirror/clike.js" );
		echo $manager->office->jsfile( SYSTEM_URL . "modules/design/assets/codemirror/php.js" );

		echo $manager->office->jquery(
			'var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
		        lineNumbers: true,
		        matchBrackets: true,
		        indentUnit: 4,
		        indentWithTabs: true,
		        enterMode: "keep",
				theme: "monokai",
				mode: "application/x-httpd-php",
		        tabMode: "shift",
				lineWrapping: true,
				onCursorActivity: function() {
				    editor.setLineClass(hlLine, null);
				    hlLine = editor.setLineClass(editor.getCursor().line, "activeline");
				}
		     });
		
			 var hlLine = editor.setLineClass(0, "activeline");'
		);
		
		call_anchor( "themeEditJs" );
	}
	
	function themeEditCss()
	{
		global $manager;
		
		echo $manager->office->style( SYSTEM_URL . "modules/design/assets/codemirror/codemirror.css" );
		echo $manager->office->style( SYSTEM_URL . "modules/design/assets/codemirror/monokai.css" );
	}
	
	function themeEdit( $id )
	{
		global $manager, $themes;
		
		$manager->form->add_fieldset( "Theme Files", "edit" );
				
		$files= scanFolder( BASE_PATH . "site/themes/" . $id );

		$list= array();
		$other= array(
					"Other Files" => "OPTG"
		);
		
		foreach ( $files as $f => $path )
		{
			if ( is_dir( $path ) )
			{
				$list[$f]= "OPTG";
			}
			elseif ( is_file( $path ) )
			{
				if ( strstr( $path, ".jpg" ) || strstr( $path, ".jpeg" ) || strstr( $path, ".gif" ) || strstr( $path, ".png" ) )
					continue;
					
				$val= $manager->office->URIquery( "file", "file=" . $f );
				if ( strstr( $f, "/" ) )
				{
					$list[$f]= $val;
				}
				else
				{
					$other[$f]= $val;
				}
			}
		}
		
		$list= array_merge( $list, $other );
		
		$manager->form->set_template( "select_template", "select_jschange", true );
		$manager->form->add_select( "files", "Select a file to edit", $list );
		$manager->form->reset_template( "select_template" );
		
		call_anchor( "themeAfterSelectFiles" );
		
		$manager->form->close_fieldset();
		
		if ( !empty( $_GET['file'] ) )
		{
			call_anchor( "themeBeforeFileEditor" );
			
			$manager->form->add_fieldset( "File Editor: " . $_GET['file'], "fileEditor" );

			$manager->form->add_input( "hidden", "file", "", $_GET['file'] );

			$code= file_get_contents( BASE_PATH . "site/themes/" . $id . "/" . $_GET['file'] );
			$code= htmlspecialchars( $code );

			// Because Receptionist has some parsing issues...
			$manager->form->add_to_form( '<textarea id="code" name="code" cols="140" rows="40" class="code">' . $code . '</textarea>' );
			
			$manager->form->close_fieldset();
			
			call_anchor( "themeAfterFileEditor" );
		}
	}
	
	function themeTitle( $id )
	{
		global $themes;
		
		$text= $themes[$id]['name'];
		
		echo $text;
	}
	
	function themeSelected( $id )
	{
		global $manager, $themes;
		
		$title= $themes[$id]['name'];
		$manager->message( 1, false, "Your site is now using the theme <em>$title</em>!" );
	}
	
	function selectTheme( $id )
	{
		global $manager, $themes;
		
		$theme= $id;
		
		// Uninstall current theme
		$uninstall= "uninstall_" . $manager->clerk->getSetting( "site_theme", 1 );
		if ( is_callable( $uninstall ) )
			call_user_func( $uninstall );
		
		// Install selected theme
		if ( $manager->clerk->updateSetting( "site_theme", array( $theme ) ) )
		{
			$functionsfile= BASE_PATH . "site/themes/" . $theme . '/functions.php';
			
			if ( file_exists( $functionsfile ) )
				include_once $functionsfile;
			if ( is_callable( "install_" . $theme ) )
				call_user_func( "install_" . $theme );
		}
	}
	
	function registerThemes()
	{
		global $manager, $themes;
		
		$scan= scanFolder( BASE_PATH . "site/themes", 1 );
		foreach ( $scan as $themeFolder )
		{
			$name= str_replace( BASE_PATH . "site/themes/", "", $themeFolder );
			$theme= scanFolder( $themeFolder, 1 );
			foreach ( $theme as $file )
			{
				if ( strstr( $file, "info.php" ) )
				{
					include_once $file;

					$themes[$name]= $info;
				}
			}
			
		}
	}
	
	function themeList()
	{
		global $manager, $themes;
		
		$currentTheme= $manager->clerk->getSetting( "site_theme", 1 );
		
		foreach ( $themes as $theme => $info )
		{
			$themeFolder	=	BASE_URL . "site/themes/" . $theme . "/";
			$preview		= 	( empty( $info['preview'] ) ) ? "" : '<img src="' . $themeFolder . $info['preview'] . '" alt="' . $info['preview'] . '" />';
			$selectLink		=	( $theme == $currentTheme ) ? "#" : $manager->office->URIquery( array('id', 'action'), "action=select&id=" . $theme );
			$editLink		=	$manager->office->URIquery( "id", "action=edit&id=" . $theme );
			$selectText		=	( $theme == $currentTheme ) ? "Currently using this theme" : "Use this theme";
			$active			=	( $theme == $currentTheme ) ? " active" : "";
			$settings		=	( $theme == $currentTheme && countHooks( "design-settings" ) >= 1 ) ? '<a href="?cubicle=design-settings" class="editTheme">Settings</a>' : '';
			
			$html= <<<HTML
				<div class="theme col-4">
					<div class="col-2">
						<h1>{$info['name']}</h1>
						<div class="controls inline">
							<ul>
								<li class="misc{$active}">
									<a href="{$selectLink}">{$selectText}</a>
								</li>
								<li class="edit">
									<a href="{$editLink}">Edit</a></span>
								</li>
								<li class="misc">
									{$settings}
								</li>
							</ul>
						</div>
						<p>
							Designed by <a href="{$info['website']}">{$info['designer']}</a><br />
							{$info['date']}
						</p>
						<p>
							{$info['about']}
						</p>
					</div>
					<div class="col-2 last preview">
						{$preview}
					</div>
				</div>
				<div class="divider"></div>
HTML;
			echo $html;
		}
	}
	
	
?>