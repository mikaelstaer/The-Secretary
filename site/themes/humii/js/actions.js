if( typeof(jQuery) !== 'undefined' ) 
	jQuery(document).ready(
		function($)
		{
			$("#content .front-slideshow").cycle(
				{
	    			fx: 'fade',
	 				slideExpr: '.front_slideshow_image',
		    		speed: 300
				}
			);
		}
	);