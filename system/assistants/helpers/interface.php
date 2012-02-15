<?php
	class Toolbar
	{
		public $html;
		
		function __construct( $settings= "" )
		{
			if ( empty( $settings['id'] ) == false )
			{
				$settings['id']= ' id="' . $settings['id'] . '"';
			}
			
			$tools= "";
			foreach ( $settings['tools'] as $tool )
			{
				$tools.= '<li>' . $tool . '</li>';
			}
			
			$this->html= '<div class="inlineToolBar' . $settings['class'] . '"' . $settings['id'] . '><ul>' . $tools . '</ul></div>';
		}
	}
	
?>