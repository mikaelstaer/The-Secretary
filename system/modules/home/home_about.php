<?php
	hook( "form_main", "about" );
	
	function about()
	{
		global $manager;
		
		$readonly= <<<HTML
		<input type="text" name="{name}" id="{name}" value="{defaultValue}" readonly="readonly" class="textfield long" />
HTML;
		$manager->form->set_template( "text_template", $readonly );

		$manager->form->add_fieldset( "System Information", "SysInfo" );

		$manager->form->add_input( "text", "version", "Version", VERSION . " / " . VERSION_DATE );
		$manager->form->add_input( "text", "abspath", "Absolute Path", BASE_PATH );
		$manager->form->add_input( "text", "url", "URL", BASE_URL );
		$manager->form->add_input( "text", "skin", "Skin", $manager->clerk->config( 'SKIN' ) );

		$manager->form->close_fieldset();
	}
?>