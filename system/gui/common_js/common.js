var tooltip_active= false;
var ajax_message= "Loading...";
var ajax_message_default= "Loading...";
var ajax_message_type= "normal";

jQuery(function($)
{
	setTimeout("$('.message_success').fadeOut('slow')", 3000);
	
	$(".tooltipLink").mouseover(
		function()
		{
			tooltip_active= true;
			$(".tooltip .tooltipMessage").hide();
			$(".tooltip .tooltipLink").removeClass("tooltip-active");
			$(".tooltip#" + this.rel + " .tooltipMessage").show();
			$(this).addClass("tooltip-active");
		}
	);
	
	$("body").click(
		function()
		{
			tooltip_active= false;
			$(".tooltip .tooltipMessage").hide();
			$(".tooltip .tooltipLink").removeClass("tooltip-active");
			$(".tooltip .tooltipMessage").hide();
		}	
	);
	
	jQuery.SetImpromptuDefaults(
	{
		overlayspeed: "fast",
		useiframe: true,
		persistent: false,
		opacity: 0.3
	});
});

function scrollto( location )
{
        var target = jQuery(location).offset().top;
        jQuery("html,body").animate( { scrollTop: target }, 500 );
}

// Borrowed from http://snook.ca/archives/javascript/testing_for_a_v/#c23230
Array.prototype.has=function(v,i){
for (var j=0;j<this.length;j++){
if (this[j]==v) return (!i ? true : j);
}
return false;
}

// Borrowed from http://johankanngard.net/2005/11/14/remove-an-element-in-a-javascript-array/
Array.prototype.remove=function(s){
  for(i=0;i<this .length;i++){
    if(s==this[i]) this.splice(i, 1);
  }
}

function hideTooltip() {
	if (tooltip_active) {
		jQuery("div.tooltip div").hide();
		jQuery("a.tooltip-active").removeClass("tooltip-active");
	}
}

// Borrowed from http://www.zrinity.com/developers/code_samples/code.cfm/CodeID/59/JavaScript/Get_Query_String_variables_in_JavaScript
function getVar(variable)
{
	var query = window.location.search.substring(1);
	var vars = query.split('&');

	vars.reverse();
	
	for (var i=0;i<vars.length;i++)
	{
		var pair = vars[i].split("=");
		
    	if (pair[0] == variable)
		{
      		return pair[1];
    	}
  	}

	return false;
}

function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}

function change(whichMenu) {
	if (!(whichMenu.options[whichMenu.selectedIndex].value== "#" || whichMenu.options[whichMenu.selectedIndex].value== ""))
		window.location= whichMenu.options[whichMenu.selectedIndex].value;
}