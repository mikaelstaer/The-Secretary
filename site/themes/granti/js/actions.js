jQuery(function($) {
	$(".project").hover(
		function()
		{
			$(this).children(".thumbnail").fadeTo( "fast", 0.6 );
		},
		function()
		{
			$(this).children(".thumbnail").fadeTo( "fast", 1.0 );
		}
	);
});