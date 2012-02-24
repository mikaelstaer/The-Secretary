<?php
	// Variables
	$db= (isset($clerk)) ? $clerk : $manager->clerk;
	define("MMURL", $db->getSetting("mediamanager_path", 2));
	define("MMPATH", $db->getSetting("mediamanager_path", 1));
	
	// Hooks
	$_GET= $db->clean($_GET);
	
	if (!empty($_GET['mmfile']))
		hook("start", "showImage");
	
	if ($_GET['action'] == "delete" && !empty($_GET['id']))
		hook("start", "mmDelete");	
	
	hook("start", "mmInstall"); // Make sure it is installed!
	hook("big_message", "mmWritable");
	
	// Text modifiers
	hook("pageTextModify", "mmText");
	hook("blogPostModify", "mmText");
	hook("textblockModify", "mmText");
	
	// GUI
	hook("javascript", "mmJs");
	
	// Actions
	hook("settings-filecabinet", "mmSettingsDelegate");
	
	hook("minimenu", "mmMenu");
	hook("settings_menu", "mmMenuBig");

	hook("mediamanager-insert", "mmInsertDelegate");
	hook("mediamanager-upload", "mmUploadDelegate");
	
	// Anchors
	define_anchor("mmPatterns");
	define_anchor("mmReplacements");
	
	$mmUpload= array(
					'success'	=>	false,
					'file'		=>	''
	);
							
	// Functions
	function mmWritable()
	{
		global $manager;

		$path= MMPATH;
		
		if (is_writable($path) == false)
		{
			message("warning", "Oh no! The File Cabinet is out of order: files cannot be uploaded because the folder is not writable.<br />The current path set to: <em>$path</em><br /><br />Double check that both the path and permissions are correct. You can update the path <a href=\"?cubicle=home-settings\">here</a>.");
		}
	}
	
	function mmDelete()
	{
		global $manager;
		
		if (unlink(MMPATH . $_GET['id']))
		{
			echo "true";
		}
		else
		{
			echo "false";
		}
		
		exit;
	}
	
	function showImage()
	{
		global $manager;
		
		$manager->load_helper("ThumbLib.inc");
		
		$thumb=	PhpThumbFactory::create($_GET['mmfile']);
		$thumb->resize(100, 0);
		$thumb->show();
	}
	
	function mmInstall()
	{
		global $manager;
		
		$manager->clerk->addSettings(
			array(
					"mediamanager_path" => array(BASE_PATH . "files/media/", BASE_URL . "files/media/"),
					"mediamanager_thumbnail" => array("100", "100")
				)
		);
		
		if (is_dir(BASE_PATH . "files/media") == false)
		{
			mkdir(BASE_PATH . "files/media", 0777);
		}
	}
	
	function mmText($text)
	{
		$chars= '.*?';
		
		$patterns= array(
			'/{(\s?)image:(' . $chars . ')(\s?)}/',
			'/{(\s?)thumb:(' . $chars . ')(\s?)}/',
			'/{file:(' . $chars . ')}/',
			'/{file:(' . $chars . '):(' . $chars . ')}/'
		);
		
		$patterns= call_anchor("mmPatterns", $patterns);
		
		$text['modified']= preg_replace_callback($patterns, "mmTextTransform", $text['modified']);
		
		return $text;
	}
	
	function mmTextTransform($matches)
	{
		global $clerk;
		
		$chars= '.*?';
		
		$patterns= array(
			'/{image:(' . $chars . ')(\s)}/',
			'/{image:(' . $chars . ')}/',
			'/{(\s)image:(' . $chars . ')(\s)}/',			
			'/{(\s)image:(' . $chars . ')}/',
			'/{thumb:(' . $chars . ')(\s)}/',
			'/{thumb:(' . $chars . ')}/',
			'/{(\s)thumb:(' . $chars . ')(\s)}/',			
			'/{(\s)thumb:(' . $chars . ')}/',
			'/{file:(' . $chars . '):(' . $chars . ')}/',
			'/{file:(' . $chars . ')}/',

		);
		
		$patterns= call_anchor("mmPatterns", $patterns);
		
		$file= $matches[2];
		$size= @getimagesize(MMPATH . $file);
		$size= $size[3];
		
		// $file is an image
		if ($size)
		{
			$thumbWidth= $clerk->getSetting("mediamanager_thumbnail", 1);
			$thumbHeight= $clerk->getSetting("mediamanager_thumbnail", 2);
		
			$thumb= dynamicThumbnail($file, MMPATH, $thumbWidth, $thumbHeight, 1, "short");
		
			$thumbWidth= ($thumbWidth == 0) ? "auto" : $thumbWidth;
			$thumbHeight= ($thumbHeight == 0) ? "auto" : $thumbHeight;
			$thumbSize= 'width="' . $thumbWidth . '" height="' . $thumbHeight . '"';
		}
		else
		{
			$parts= explode(":", $matches[1]);
			$text= (empty($parts[0])) ? $parts[1] : $parts[0];
			$file= (count($parts) == 1) ? $parts[0] : $parts[1];
		}
		
		$replacements= array(
			'<img src="' . MMURL . $file . '" alt="" class="align-left" ' . $size . ' />',
			'<img src="' . MMURL . $file . '" alt="" ' . $size . ' />',
			'<img src="' . MMURL . $file . '" alt="" class="align-center" ' . $size . ' />',
			'<img src="' . MMURL . $file . '" alt="" class="align-right" ' . $size . ' />',
			'<img src="' . $thumb . '" alt="" class="align-left" ' . $thumbSize . ' />',
			'<img src="' . $thumb . '" alt="" ' . $thumbSize . ' />',
			'<img src="' . $thumb . '" alt="" class="align-center" ' . $thumbSize . ' />',
			'<img src="' . $thumb . '" alt="" class="align-right" ' . $thumbSize . ' />',
			'<a href="' . MMURL . $file . '">' . $text . '</a>',
			'<a href="' . MMURL . $file . '">' . $file . '</a>',
		);
		
		$replacements= call_anchor("mmReplacements", $replacements);
		
		$text= preg_replace($patterns, $replacements, $matches[0]);
		
		return $text;
	}
	
	function mmJs()
	{
		global $manager;
		
		if (($manager->helperLoaded("quicktags", "js") || $manager->office->cubicle("BRANCH") == "mediamanager"))
		{
			echo $manager->office->jsfile(SYSTEM_URL . "plugins/media_manager/mediamanager.js");
		}
	}
	
	function mmCss()
	{
		global $manager;

		echo $manager->office->style(SYSTEM_URL . "plugins/media_manager/mmstyles.css");
	}
	
	function mmSettingsDelegate()
	{
		hook("form_main", "mmSettings");
		hook("form_process", "mmSettingsProcess");
		hook("form_submit_primary", "mmSubmitButtons");
	}
	
	function mmSubmitButtons()
	{
		global $manager;
		
		$manager->form->add_input("submit", "submit", "Save", "save");
	}
	
	function mmSettingsProcess()
	{
		global $manager;
		
		$upload_path= $_POST['mmPath'];
		$upload_url= $_POST['mmUrl'];
		
		$manager->clerk->updateSetting("mediamanager_path", array($upload_path, $upload_url, ""));
		$manager->clerk->updateSetting("mediamanager_thumbnail", array($_POST['mediamanager_width'], $_POST['mediamanager_height'], ""));
		
		$manager->message(1, false, "Settings saved!");
	}
	
	function mmSettings()
	{
		global $manager;
		
		$paths			=	$manager->clerk->getSetting("mediamanager_path");
		$upload_path	=	$paths['data1'];
		$upload_url		=	$paths['data2'];
		$size			=	$manager->clerk->getSetting("mediamanager_thumbnail");
		
		$manager->form->add_fieldset("File Cabinet Settings", "mmSettings");
		
		$manager->form->add_input("text", "mmPath", "Upload Path", $upload_path, "", "", "This is the <strong>absolute path</strong> to the folder where files should be uploaded to. It should look something like this: <em>/home/user/mydomain.com/files/media/</em>");
		$manager->form->add_input("text", "mmUrl", "Upload URL", $upload_url, "", "", "This is the <strong>URL</strong> to the folder where files should be uploaded to. It should look something like this: <em>http://www.mydomain.com/files/media/</em>.");
		
		$manager->form->add_input("hidden", "mmPathOld", "", $upload_path);
		
		$manager->form->add_input("text", "mediamanager_width", "Thumbnail Width", $size['data1']);
		$manager->form->add_input("text", "mediamanager_height", "Thumbnail Height", $size['data2']);
		
		$manager->form->add_rule("mmPath");
		$manager->form->add_rule("mmUrl");
		
		$manager->form->close_fieldset();
	}
	
	function mmMenu($menu)
	{
		global $manager;
		
		$menu['mediamanager']= array(
				'sys_name'	=>	'mediamanager',
				'dis_name'	=>	'File Cabinet',
				'url'		=>	'',
				'type'		=>	'',
				'hidden'	=>	true,
				'children'	=>	array(
						array(
							'sys_name'	=>	'upload',
							'dis_name'	=>	'Upload',
							'url'		=>	'',
							'type'		=>	'',
							'hidden'	=>	true
						),
						array(
							'sys_name'	=>	'insert',
							'dis_name'	=>	'Insert',
							'url'		=>	'',
							'type'		=>	'',
							'hidden'	=>	true
						)	
				)
		);
		
		$menu['mediamanager-upload']= array(
				'sys_name'	=>	'mediamanager-upload',
				'dis_name'	=>	'Upload',
				'url'		=>	'',
				'type'		=>	'',
				'hidden'	=>	false,
		);
		
		$menu['mediamanager-insert']= array(
				'sys_name'	=>	'mediamanager-insert',
				'dis_name'	=>	'Insert',
				'url'		=>	'',
				'type'		=>	'',
				'hidden'	=>	false,
		);
		
		return $menu;
	}
	
	function mmMenuBig($menu)
	{
		global $manager;
		
		$menus['mediamanager']= array(
				'sys_name'	=>	'mediamanager',
				'dis_name'	=>	'File Cabinet',
				'url'		=>	'',
				'type'		=>	'',
				'hidden'	=>	'',
				'children'	=>	array(
						array(
							'sys_name'	=>	'upload',
							'dis_name'	=>	'Upload',
							'url'		=>	'',
							'type'		=>	'',
							'hidden'	=>	''
						),
						array(
							'sys_name'	=>	'insert',
							'dis_name'	=>	'Insert',
							'url'		=>	'',
							'type'		=>	'',
							'hidden'	=>	''
						)	
				)
		);
		
		$menu[]= array(
			'sys_name'	=>	'filecabinet',
			'dis_name'	=>	'File Cabinet'
		);
		
		return $menu;
	}
	
	function mmInsertDelegate()
	{
		global $manager;
		
		hook("css", "mmCss");
		hook("form_main", "mmInsert");
	}
	
	function mmInsert()
	{
		global $manager;
		
		$files = scanFolder(MMPATH, 1);
		sort($files);
		
		if (count($files) == 0) {
			echo $manager->form->message("There are no files in your File Cabinet!");
		}
		
		$BASE_URL 	= BASE_URL;
		$target 	= $_GET['target'];
		
		// List images...
		
		$html 	= '<table>';
		$count 	= 0;
		
		foreach ($files as $file)
		{
			$isImage = @getimagesize($file);
			
			if ($isImage == false) {
				continue;
			}
			
			$count++;
			
			$file 		= str_replace("//", "/", $file);
			$fileName 	= basename($file);
			$thumbnail 	= '<img src="' . BASE_URL . 'index.php?cubicle=mediamanager&amp;mmfile='. $file .'" alt= ""/>';
			$html 		.= <<<HTML
				<tr id="file{$count}">
					<td class="thumbnail">
						{$thumbnail}
					</td>
					<td>
						<span class="fileName">{$fileName}</span>
						<div class="controls floating">
							<ul>
								<li class="misc"><a href="#" onclick="insertFile('image:{$fileName}', '{$target}'); return false;">Insert</a></li>
								<li class="misc"><a href="#" onclick="insertFile('thumb:{$fileName}', '{$target}'); return false;">Insert Thumbnail</a></li>
								<li class="delete"><a href="#" onclick="deleteFile('{$fileName}', 'file{$count}'); return false;">Delete</a></li>
							</ul>
						</div>
					</td>
				</tr>
HTML;
		}
		
		$html .= '</table>';

		if ($count > 0)
		{
			$manager->form->add_fieldset("Images", "images");
			$manager->form->add_to_form($html);
			$manager->form->close_fieldset();
		}
		
		// Now files

		$count = 0;		
		$html  = '<table>';

		foreach ($files as $file)
		{			
			$isImage = @getimagesize($file);
			
			if ($isImage == true) {
				continue;
			}
			
			$count++;
			
			$file 		= str_replace("//", "/", $file);
			$fileName 	= basename($file);
			$html 		.= <<<HTML
				<tr id="file{$count}">
					<td>
						<span class="fileName">{$fileName}</span>
						<div class="controls floating">
							<ul>
								<li class="misc"><a href="#" onclick="insertFile('file:{$fileName}', '{$target}'); return false;">Insert</a></li>
								<li class="misc"><a href="#" onclick="insertFile('file:YourText:{$fileName}', '{$target}'); return false;">Insert with custom text</a></li>
								<li class="delete"><a href="#" onclick="deleteFile('{$fileName}', 'file{$count}'); return false;">Delete</a></li>
							</ul>
						</div>
					</td>
				</tr>
HTML;
		}
		
		$html .= '</table>';
		
		if ($count > 0) {
			$manager->form->add_fieldset("Files", "files");
			$manager->form->add_to_form($html);
			$manager->form->close_fieldset();
		}
	}
	
	function mmUploadDelegate()
	{
		hook("form_submit_primary", "mmUploadSubmit");
		hook("form_main", "mmUpload");
		hook("form_process", "mmUploadProcess");
	}
	
	function mmUpload()
	{
		global $manager, $mmUpload;
		
		$fileTypes= array('.jpg', '.gif', '.png', '.mp3', '.mov', '.mpeg', '.pdf', '.txt', '.html');
		
		$manager->form->add_fieldset("Select image", "selectImage");
		$manager->form->add_input("file", "image", "(" . implode($fileTypes, ", ") . " / <strong>Max " . str_replace("M", "mb", ini_get("upload_max_filesize")) . "</strong>)");
		$manager->form->close_fieldset();
		
		$manager->form->add_file_rule("image");
	}
	
	function mmUploadSubmit()
	{
		global $manager;
		
		$manager->form->add_input('submit', 'submit', 'Upload', 'upload');
	}
	
	function mmUploadProcess()
	{
		global $manager;
		
		$manager->load_helper("file_uploader.inc");
		
		// $fileTypes= array('.jpg', '.jpeg', '.gif', '.png');
		$fileTypes= array();
		
		foreach ($_FILES['image']['name'] as $key => $val)
		{
			if (empty($val))
			{
				continue;
			}
				
			$image= basename($val);
				
			$upload			=	upload('image', $key, MMPATH, implode(",", $fileTypes));
			$upload_file	= 	$upload[0];
			$upload_error	=	$upload[1];
		}
		
		if (empty($upload_error))
		{
			$manager->message(1, false, "File uploaded!");
			hook("before_form", "mmInsertNew", array($image));
		}
		else
		{
			$manager->message(0, false, "Oops! Couldn't upload that file: " . $upload_error);
		}
	}
	
	function mmInsertNew($fileName)
	{
		global $manager;
		
		$fileTypes= array('.jpg', '.gif', '.png', '.mp3', '.mov', '.mpeg', '.pdf', '.txt', '.html');
		$target= $_GET['target'];
		
		$BASE_URL= BASE_URL;
		
		$file= MMPATH . $fileName;
		
		$isImage= @getimagesize($file);
		if ($isImage == true)
		{
			$html= <<<HTML
				<img src="{$BASE_URL}index.php?cubicle=mediamanager&amp;mmfile={$file}" alt= ""/>
				<div class="controls inline">
					<ul>
						<li class="misc"><a href="#" onclick="insertFile('image:{$fileName}', '{$target}'); return false;">Insert</a></li>
						<li class="misc"><a href="#" onclick="insertFile('thumb:{$fileName}', '{$target}'); return false;">Insert Thumbnail</a></li>
					</ul>
				</div>
HTML;
		}
		else
		{
			$html= <<<HTML
				<strong>{$fileName}</strong>
				<div class="controls inline">
					<ul>
						<li class="misc"><a href="#" onclick="insertFile('file:{$fileName}', '{$target}'); return false;">Insert</a></li>
						<li class="misc"><a href="#" onclick="insertFile('file:YourText:{$fileName}', '{$target}'); return false;">Insert with custom text</a></li>
					</ul>
				</div>
HTML;
		}
		
		echo $html;
	}
?>