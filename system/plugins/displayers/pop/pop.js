var popped= new Array();

function popper( target, width, height, media )
{
	var targetImage= jQuery("#file" + target + " .popper img" ).attr("alt");
	var sourceImage= jQuery("#file" + target + " .popper img" ).attr("src");
	
	if ( media == "" || media == null ) media= false;
	
	if ( popped.has(target) )
	{
		popped.remove(target);
		
		if ( media )
		{
			jQuery("#file" + target + " .popcontent" ).hide();
		}
		else
		{
			jQuery("#file" + target + " .popper img" ).removeAttr("width").removeAttr("height");
		}
		
	}
	else
	{
		popped[popped.length]= target;
		
		if ( media )
		{
			jQuery("#file" + target + " .popcontent" ).show();
		}
		else
		{
			jQuery("#file" + target + " .popper img" ).attr("width", width).attr("height", height).attr("alt", sourceImage);
		}
	}
	
	jQuery("#file" + target + " .popper img" ).attr("alt", sourceImage).width(width).height(height);
	jQuery("#file" + target + " .info" ).toggle();
	
	if ( media == false )
	{
		var load= new Image();
		load.src= targetImage;
		
		jQuery("#file" + target + " .popper img" ).attr("src", load.src);
	}
	
	return false;
}

Array.prototype.has=function(v,i)
{
	for (var j=0;j<this.length;j++)
	{
		if (this[j]==v) return (!i ? true : j);
	}
	return false;
}

Array.prototype.remove=function(s)
{
	for(i=0;i<this .length;i++)
	{
    	if (s==this[i]) this.splice(i, 1);
  	}
}