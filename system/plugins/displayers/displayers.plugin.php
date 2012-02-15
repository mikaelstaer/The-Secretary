<?php
	hook( "displayersList", "displayFormats" );
	
	function displayFormats( $displayers )
	{
		
		$defaults= array(
			'One by One'	=>	"one-by-one",
			'Slideshow'		=>	"slideshow",
			'Pop'			=>	"pop"
		);
		
		$final= array_merge( $defaults, $displayers );
		
		return $final;
	}
?>