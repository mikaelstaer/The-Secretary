jQuery( function($)
{
	var activeMenu= ( getVar('cubicle') != false ) ? getVar('cubicle').split('-')[0] : "home";
	
	$("#navActual li.top").hover(
		function()
		{
			$(this).children("a.top").addClass("hover");
			$(this).children("ul:first").show();
		},
		function()
		{
			$(this).children("a.top").removeClass("hover");
			$(this).children("ul:first").hide();
		}
	);
});