window.onload= function()
{
	asstPath= document.getElementById("asstPath").value;
}

jQuery( function($)
{
	$("#pageList .item").click( function()
	{
		window.location = $(this).contents().find(".edit a").attr("href");
	});
	
	if ( getVar("mode") == "edit" )
	{
		textEditor("text");
	}
	else
	{
		pagesSortable();
	}
	
	var target = null;
	$('form#input :button').mouseover(function()
	{
		target = $(this).val();
	});
	
	$('form#input').submit(function()
	{
        if ( target == "delete" )
		{
			var text= '<h1>Are you sure you want to delete this page?</h1> <p>Be careful, this cannot be undone.</p>';
			
			var res;
			
			jQuery.prompt( text,
			{
				buttons: {
					Cancel	: false,
					OK		: true
				},
				callback: function(value, msg, form)
						  {
						  		if ( value == true )
								{
									window.location = "?cubicle=pages-manage&mode=delete&id=" + document.getElementById("id").value;
								}
								
								return value;
						  }
			});
		}
		else
			return true;
		
		return false;
    });
	
});

var newPage= function()
{
	var form= '<label for="name">Name</label><input type="text" name="name" id="name" />';
	jQuery.prompt( '<h1>New Page</h1>' + form, {
		buttons: {
			Save	: true,
			Cancel	: false
		},
		callback: function(value, msg, form)
				  {
				  	if ( value == true )
					{
						jQuery.noticeAdd({ text: "Creating page...", type: "heavy new", stay: true });
						jQuery.post(
							"system/modules/pages/assets/ajax.php",
							{
								action: 'newPage',
								system: SYSTEM,
								name: form.name
							},
							function(data)
							{
								jQuery.noticeRemove($(".new"));
								if ( data == "false" )
								{							
									jQuery.prompt( '<h1>Fumbled!</h1> Your new page could not be created because of a system error.',
									{
										buttons: {
											Ok	: false, 
										}
									});
								}
								else
								{
									window.location= window.location + "&mode=edit&id=" + data;
								}
							}
						);
					}

					return true;
				  }
	});
}

var pagesSortable= function()
{
	jQuery("#pageList").sortable(
		{
			items: '.item',
			opacity: 0.5,
			fit: false,
			handle: '.handle',
			update: function(e, ui)
					{
						jQuery.post("system/modules/pages/assets/ajax.php",
								{
									action: 'orderPages',
									system: SYSTEM,
									order: jQuery(this).sortable("serialize")
								}, 
								function(data)
								{
									jQuery.noticeRemove(jQuery(".heavy"));
									return data;
								}
						);
					}
		}
	);
}