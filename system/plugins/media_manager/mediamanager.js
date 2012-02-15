// Add Media button
if ( typeof( edButtons ) != "undefined" )
{
	edButtons.push(
		new edButton(
			'mm_add special'
			,'File Cabinet'
			,''
			,''
			,'m'
			, 'mediaManager'
		)
	);
}

function mediaManager(target)
{	
	var popW= 990;
	var popH= 500;
	
	var left= (screen.availWidth - popW)/2;
	var top= (screen.availHeight - popH)/2;
	
	window.open('index.php?cubicle=mediamanager-insert&mini=true&target=' + target,'mediaManager','width=' + popW + ',height=' + popH + ',scrollbars=yes,resizable=yes,top=' + top + ',left=' + left);
}

var insertFile= function(file, target)
{
	jQuery.noticeAdd({ text: "Inserted!", stay: false, stayTime: 1000 });
	window.opener.edInsertContent(target, "{" + file + "}");
};

var deleteFile= function(file, id)
{
	jQuery.prompt( '<h1>Are you sure you want to delete this file?</h1> <p>This cannot be undone.</p>',
	{
		buttons: {
			Cancel	: false,
			OK		: true
		},
		callback: function(value, msg, form)
				  {
				  	if ( value == true )
					{
						jQuery.get(
							"index.php",
							{
								cubicle: "mediamanager",
								action: "delete",
								id: file
							},
							function(data)
							{
								if ( data == "true" )
								{
									jQuery("tr#" + id).fadeOut("normal", function() { jQuery(this).remove(); });
								}
								else
								{
									alert('The file "' + file + '" could not be deleted!');
								}
							}
						);
					}
				  }
	});
};