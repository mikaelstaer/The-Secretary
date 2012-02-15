jQuery( function($)
{
	if ( getVar("mode") == "edit" )
	{
		textEditor("post");
	}
	
	$("#overview .post").click( function()
	{
		window.location = $(this).contents().find(".edit a").attr("href");
	});
	
	var target = null;
	$('form#input :button').mouseover(function()
	{
		target = $(this).val();
	});
	
	$('form#input').submit(function()
	{
        if ( target == "delete" )
		{
			var text= '<h1>Are you sure you want to delete this post?</h1> <p>Be careful, this cannot be undone.</p>';
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
									window.location = "?cubicle=blog-manage&mode=delete&id=" + document.getElementById("id").value;
								}
								
								return value;
						  }
			});
		}
		else if ( target == "deleteImage" )
		{
			var text= '<h1>Are you sure you want to delete this post\'s image?</h1> <p>Be careful, this cannot be undone.</p>';
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
									window.location = "?cubicle=blog-manage&mode=edit&action=deleteImage&id=" + document.getElementById("id").value;
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

var newPost= function()
{
	var form= '<label for="name">Title</label><input type="text" name="name" id="name" />';
	jQuery.prompt( '<h1>New Post</h1>' + form, {
		buttons: {
			Save	: true,
			Cancel	: false
		},
		callback: function(value, msg, form)
				  {
				  	if ( value == true )
					{
						jQuery.noticeAdd({ text: "Creating post...", type: "heavy new", stay: true });
						jQuery.post(
							"system/modules/blog/assets/ajax.php",
							{
								action: 'newPost',
								system: SYSTEM,
								name: form.name
							},
							function(data)
							{
								jQuery.noticeRemove($(".new"));
								if ( data == "false" )
								{							
									jQuery.prompt( '<h1>Fumbled!</h1> Your new blog post could not be created because of a system error.',
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