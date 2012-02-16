var asstPath= "";
var id= "";
var upload_url= "";
var upload_path= "";
var menuActive= false;
var hoverTimer= 0;
var tempStorage= {};

window.onload= function()
{
	// asstPath= document.getElementById("asstPath").value;
	id= ( document.getElementById("id") != null ) ? document.getElementById("id").value : "";
	upload_url= document.getElementById("uploadUrl").value;
	upload_path= document.getElementById("uploadPath").value;
}

jQuery( function($)
{	
	if ( getVar('mode') == "edit" )
	{
		fileSortable();
		fileGroupSortable();
		textBlockUpdate();
		scrollWaitingRoom();
		
		// Valums File Uploader
		var uploader = new qq.FileUploader({
		    element: document.getElementById('file-uploader'),
		    action: "system/modules/projects/assets/valumsupload.php",
			allowedExtensions: ['jpg', 'jpeg', 'gif', 'png', 'mov', 'mpg', 'mpeg', 'wmv', 'avi', 'm4v', 'mp4', 'flv', 'swf', 'mp3', 'm4a', 'wav'],
			template: '<div class="qq-uploader">' + '<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>' + '<div class="qq-upload-button">' + $("#file-uploader").html() + '</div>' + '<ul class="qq-upload-list"></ul>' + '</div>',
			params: {
				action: "upload",
				system: SYSTEM,
				id: $("#id").val()
			},
			onComplete: function(id, fileName, responseJSON)
			{
				html= responseJSON.html;
				
				var totalGroups= $('.fileGroup:not(#waitingRoom,.textBlock)').size();
				
				if ( totalGroups == 1 )
				{
					$(html).appendTo('.fileGroup:not(#waitingRoom,.textBlock):first');
				}
				else
				{
					$("#waitingRoom").show();
					$(html).appendTo("#waitingRoom");
				}
				
				$(".qq-upload-success").fadeOut();
				
				fileSortable();
			}
		});
	}
	else
	{
		// Tabs Control
		sectionSortable();
	}
	
	// Initialize text editors on textblocks
	$(".textBlock").each(
		function(i)
		{
			textEditor( jQuery(this).children("textarea.textblock").attr("id") );
		}
	);
	
	// Intercept form submission
	var target = null;
	$('form#input :button').mouseover(function()
	{
		target = $(this).val();
	});
	
	$('form#input').submit(function()
	{
        if ( target == "delete" )
		{
			var text= '<h1>Are you sure you want to delete this project?</h1> <p>Be careful, this cannot be undone.</p>';
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
									window.location = "?cubicle=projects-manage&mode=delete&id=" + document.getElementById("id").value;
								}
								
								return value;
						  }
			});
		}
		else
			return true;
		
		return false;
    });
	
	$("#thumbnailForm").wrap('<form action="system/modules/projects/assets/thumbnailupload.php" method="post" enctype="multipart/form-data" id="thumbnailUpload"></form>');
	
	var thumbnailOptions=
	{
		data: {
			system_path: SYSTEM.path,
			system_url: SYSTEM.url
		},
		beforeSubmit: function(formData, form, options )
		{
			jQuery.noticeAdd({ text: "Uploading...", stay: true, type: "heavy" });
		},
		success: function(data, status)
		{	
			$("#thumbnailForm #theThumb img").remove();
			$("#thumbnailForm #theThumb").prepend(data);
			$("#thumbnailForm .delete").show();
							
			jQuery.noticeRemove(jQuery(".heavy"), 2000);
		}
	};
	
	$("#thumbnailUpload").ajaxForm(thumbnailOptions);
});

var scrollWaitingRoom= function()
{
	jQuery("#waitingRoom").width(jQuery("#waitingRoom").outerWidth()).scrollFixed({container: '#groupedFiles', appearAfterDiv: '#uploadForm', spacer: true});
}

var newProject= function()
{
	if ( jQuery(".section").size() == 0 )
	{
		alert("You must create a section before you can add projects!");
		newSection();
		return false;
	}
	
	var sections;
	var selected;
	jQuery(".section").each(function(i)
	{
		sectionId= jQuery(this).attr("id");
		sectionId= Number( sectionId.substring( sectionId.search('_') + 1 ) );
		name= jQuery("#section_" + sectionId + " a.name").text();
		if ( jQuery(this).hasClass("ui-tabs-selected") )
			selected= 'selected= "selected"';
		else
			selected= "";
		sections+= '<option value="' + sectionId + '"' + selected + '>' + name + '</option>';
	});
	
	var form= '<label for="title">Title</label><input type="text" name="title" id="title" /><label for="section">Section</label><select name="section">' + sections + '</select>';
	jQuery.prompt( '<h1>New Project</h1>' + form,
	{
		buttons: {
			Save	: true,
			Cancel	: false
		},
		submit: function(value, msg, form)
		{
			if ( form.title.length < 1 && value == true )
				return false;
			
			return true;
		},
		callback: function(value, msg, form)
		{
			if ( value == true )
			{
				jQuery.noticeAdd({ text: "Creating project...", type: "heavy newProject", stay: true });
				jQuery.post("system/modules/projects/assets/update.php",
							{
									action: 'newProject',
									system: SYSTEM,
									title: form.title,
									section: form.section
							},
							function(data)
							{
									jQuery.noticeRemove(jQuery(".newProject"));
									if ( data == "false")
									{
										jQuery.prompt( '<h1>Fumbled!</h1> <p>Your new project could not be created because of a system error.</p>',
										{
											buttons: {
												Ok	: true 
											}
										});
									}
									else
									{
										var location= String(window.location);
										window.location= location.replace("#", "") + "&mode=edit&id=" + data;
									}
							});
			}
		}
	});
};

var sectionSortable= function()
{	
	var projectsSortableOpts= {
		items: '.project',
		opacity: 0.3,
		scroll: true,
		tolerance: 'pointer',
		placeHolder: 'placeholder',
		start: function(event, ui)
		{
			jQuery("#tabHolder").unbind("mousemove");
			
			var div= jQuery("#tabHolder"), ul = jQuery('#sectionsTabs'), ulPadding= 0;
			var divWidth= div.width();
			var divHeight= div.height();
			
			div.css({overflow: 'hidden'});
			
			var divPos= div.offset();
			
			jQuery(document).mousemove(function(e)
			{
				if ( ( e.pageX >= divPos.left && e.pageX <= (divPos.left + divWidth) ) && ( e.pageY >= divPos.top && e.pageY <= (divPos.top + divHeight) ) )
				{
					var lastLi = ul.find('li:last-child');
					var ulWidth = lastLi[0].offsetLeft + lastLi.outerWidth() + ulPadding;

					var left = (e.pageX - div.offset().left) * (ulWidth-divWidth) / divWidth;
					div.scrollLeft(left);
				}
			});
		},
		update: function(e, ui)
		{
			var group= jQuery(this).parent().attr("id");
			var groupNum= Number( group.substring( group.search('-') + 1 ) ) || 0;
			var fileOrder= jQuery(this).sortable("serialize");
			
			jQuery("#" + group + " .project.last").removeClass("last");
			
			jQuery.post("system/modules/projects/assets/update.php",
			{
				action: 'orderProjects',
				system: SYSTEM,
				section: groupNum,
				fileOrder: fileOrder
			},
			function(data)
			{	
				return data;
			});
		}
	};
	
	var droppableTabsOpts= {
		accept: ".project",
		tolerance: "pointer",
		hoverClass: "droppableHover",
		drop: function(ev, ui)
		{
			var $item = $(this);
			var $list = $($item.find('a').attr('href') + " ul.projects");
			
			ui.draggable.fadeOut('fast', function()
			{
				$(ui.draggable).appendTo($list).css("opacity", 1).show();
					
				var group= $item.find('a').attr('href').replace("#", "");
				var groupNum= Number( group.substring( group.search('-') + 1 ) ) || 0;
				var fileOrder= jQuery($list).sortable("serialize");
					
				jQuery.post("system/modules/projects/assets/update.php",
				{
					action: 'orderProjects',
					system: SYSTEM,
					section: groupNum,
					fileOrder: fileOrder
				},
				function(data)
				{	
					return data;
				});
			});
		}
	};
	
	// Enable Flowing Tabs.
	var div = jQuery("#tabHolder"), ul = jQuery('#sectionsTabs'), ulPadding= 0;
	var divWidth = div.width();
	div.css({overflow: 'hidden'});
	
	jQuery("#tabHolder").mousemove(function(e)
	{
	 	var lastLi = ul.find('li:last-child');
		var ulWidth = lastLi[0].offsetLeft + lastLi.outerWidth() + ulPadding;
		
		var left = (e.pageX - div.offset().left) * (ulWidth-divWidth) / divWidth;
		div.scrollLeft(left);
	});
	
	jQuery("#tabHolder").tabs(
		{
			idPrefix: "section-",
			tabTemplate: '<li id="#{href}" class="section"><a class="name" href="#{href}">#{label}</a></li>',
			panelTemplate: '<div class="sectionProjects"><ul class="projects"></ul></div>',
			add: function(event, ui)
			{
				jQuery("#tabHolder #sectionsTabs li:last-child").attr("id", jQuery("#tabHolder #sectionsTabs li:last-child").attr("id").replace("-", "_").replace("#", ""));
				jQuery("#tabHolder .sectionProjects").prepend(tempStorage.controls).appendTo("#overview");
								
				jQuery(".ui-tabs-nav").sortable("refresh");
				jQuery(".sectionProjects ul").sortable(projectsSortableOpts);
				jQuery("#tabHolder #sectionsTabs .section:last-child").droppable(droppableTabsOpts);
			}
		}).find(".ui-tabs-nav").sortable(
		{
			axis: 'x',
			opacity: 0.5,
			scroll: true,
			tolerance: 'pointer',
			update: function(e, ui)
			{
				var fileOrder= jQuery(this).sortable("serialize");
		
				jQuery.post("system/modules/projects/assets/update.php",
				{
					action: 'orderSections',
					system: SYSTEM,
					fileOrder: fileOrder
				},
				function(data)
				{
					return data;
				});
			},
			sort: function(event, ui)
			{
				lastLi = ul.find('li:last-child');
			},
			stop: function(event, ui)
			{
				lastLi = ul.find('li:last-child');
			}
		}
	);
	
	jQuery(".section", jQuery("#overview")).droppable(droppableTabsOpts);
	jQuery(".sectionProjects ul").sortable(projectsSortableOpts);
	
};

var newSection= function()
{
	var form= '<label for="title">Title</label><input type="text" name="name" id="name" />';
	jQuery.prompt( '<h1>New Section</h1>' + form,
	{
		buttons: {
			Save	: true,
			Cancel	: false
		},
		submit: function(value, msg, form)
		{
			if ( form.name.length < 1 && value == true )
				return false;
				
			return true;
		},
		callback: function(value, msg, form)
		{
			if ( value == true )
			{
				jQuery.post("system/modules/projects/assets/update.php",
				{
						action: 'newSection',
						system: SYSTEM,
						name: form.name
				},
				function(data)
				{
					tempStorage= data;
					jQuery("#tabHolder").tabs("option", "tabTemplate", '<li id="section_' + data.id + '" class="section hide"><a class="name" href="#{href}">#{label}</a></li>');
					jQuery("#tabHolder").tabs("add", "#section-" + data.id, form.name);
					jQuery("#tabHolder .section#section_" + data.id).show("drop", { direction: "down" }, 300).removeClass("hide");
					
					return data;
				}, "json");
			}
			else
			{
				return false;
			}
		}
	});
};

var editSection= function(id, name, slug)
{
	var form= '<label for="name">Name</label><input type="text" name="name" value="' + name + '"/><label for="name">Slug</label><input type="text" name="slug" value="' + slug + '"/>';
	jQuery.prompt( '<h1>Edit Section</h1>' + form, {
		buttons: {
			Save	: true,
			Cancel	: false
		},
		submit: function(value, msg, form)
		{
			if ( form.name.length < 1 && value == true )
				return false;
			
			return true;
		},
		callback: function(value, msg, form)
				  {
				  	if ( value == true )
					{
						jQuery.noticeAdd({ text: "Saving...", type: "heavy saveSection", stay: true });
						jQuery.post(
							"system/modules/projects/assets/update.php",
							{
								action: 'editSection',
								system: SYSTEM,
								id: id,
								name: form.name,
								slug: form.slug
							},
							function(data)
							{
								jQuery.noticeRemove(jQuery(".saveSection"));
								jQuery("#section_" + id + " a").text(form.name);
								jQuery("#section-" + id + " .controls .edit a").attr("onClick", "").unbind("click").click(function() { editSection(id, data.name, data.slug); return false; });
							},
							"json"
						);
					}
				  }
	});
};

var deleteSection= function(id)
{
	jQuery.prompt( '<h1>Are you sure you want to delete this section?</h1> <p>All projects in this section will be deleted.</p>', {
		buttons: {
			Cancel	: false,
			OK		: true
		},
		callback: function(value, msg, form)
				  {
				  	if ( value == true )
					{
						jQuery("#tabHolder .section#section_" + id).animate( {opacity: 0, top: '+=35'}, 300, "easeOutQuad" , function() { jQuery("#tabHolder").tabs("remove", jQuery(".section#section_" + id).index()); }  );
						
						jQuery.post(
							"system/modules/projects/assets/update.php",
							{
								action: 'deleteSection',
								system: SYSTEM,
								section: id
							},
							function(data)
							{
								return data;
							}
						);
					}
				  }
	});
};

var fileGroupSortable= function()
{
	jQuery("#groupedFiles").sortable(
	{
		items: '.fileGroup:not(#waitingRoom)',
		opacity: 0.7,
		handle: ".handle",
		scroll: true,
		update: function(e, ui)
		{
			updateFlow(true);
		}
	});
};

var updateFlow= function(send)
{
	send= (typeof send == 'undefined') ? true : send;
	
	var order= new Array( jQuery(".fileGroup:not(#waitingRoom)").size() );
	var displayType= "";
	var group= "";
	
	jQuery(".fileGroup:not(#waitingRoom)").each(function(i)
	{
		groupId= jQuery(this).attr("id");
		groupId= Number( groupId.substring( groupId.search('-') + 1 ) );
					
		// Is a file group
		if ( jQuery(this).attr("data-type") == "group" )
		{
			displayType= jQuery("#file_group-" + groupId + " .displayType").val();
			order[i]= "group" + groupId + ":" + displayType;
		}
		else
		{
			order[i]= jQuery(this).attr("data-type") + groupId;
		}
	});
	
	if ( send == false )
	{
		return order;
	}
	else
	{
		jQuery.post("system/modules/projects/assets/update.php",
		{
			action: 'flow',
			system: SYSTEM,
			project_id: document.getElementById("id").value,
			flow: order.join()
		},
		function(data)
		{
			return data;
		});
	}
};

var fileSortable= function()
{		
	jQuery(".fileGroup").sortable(
	{
		items: '.filebox',
		opacity: 	0.5,
		scroll: true,
		connectWith: '.fileGroup:not(.textBlock)',
		update: function(e, ui)
		{
			var group= jQuery(this).attr("id");
			var groupNum= Number( group.substring( group.search('-') + 1 ) ) || 0;
			var file_order= jQuery(this).sortable("serialize");

			jQuery.post("system/modules/projects/assets/update.php",
			{
				action: 'orderFiles',
				system: SYSTEM,
				project_id: document.getElementById("id").value,
				group: groupNum,
				file_order: file_order
			},
			function(data)
			{
				if ( jQuery("#waitingRoom .filebox").size() == 0 )
				{
					jQuery("#waitingRoom").fadeOut("fast");
				}
				
				return data;
			});
		}
	});
};

function toolbar_show(id)
{
	clearTimeout(hoverTimer);
	menuActive= true;
	jQuery(".filebox[id!=file_" + id + "] .toolbar .edit").removeClass("active").siblings(".options").hide();
	jQuery("#file_" + id + " .toolbar .options").show();
	jQuery("#file_" + id + " .toolbar .edit").addClass("active");
}

function toolbar_hide(id)
{
	if ( menuActive == true )
	{
		hoverTimer= setTimeout("toolbar_hide_forReal(" + id + ")", 200 );
	}
}

function toolbar_hide_forReal(id)
{
	jQuery("#file_" + id + " .toolbar .options").hide();
	jQuery("#file_" + id + " .toolbar .edit").removeClass("active");
	menuActive= false;
}

var newGroup= function(location)
{
	location= location || "top";
	var groupNum= ( jQuery(".fileGroup:not(#waitingRoom, .textBlock)").size() + 1 );
	
	jQuery.post("system/modules/projects/assets/update.php",
			{
				action: 'newGroup',
				system: SYSTEM,
				groupNum: groupNum
			}, 
			function(data) {
				if (location == "top")
					jQuery(data).insertAfter("#groupedFiles #waitingRoom");
				else if (location == "bottom")
					jQuery("#groupedFiles").append(data);
				
				updateFlow(true);
				
				jQuery("#groupedFiles").sortable("refresh");
				
				if ( jQuery("#groupedFiles .fileGroup").size() >= 3 )
				{
					jQuery("#bottom").show();
				}
				
				fileSortable();

				scrollto("#file_group-" + groupNum);
			}
	);
};

var addTextBlock= function(location)
{
	location= location || "top";
	jQuery.post("system/modules/projects/assets/update.php",
			{
				action: 'newTextBlock',
				system: SYSTEM,
				project_id: document.getElementById("id").value
			}, 
			function(data)
			{	
				if (location == "top")
					jQuery(data).insertAfter("#groupedFiles #waitingRoom");
				else if (location == "bottom")
					jQuery("#groupedFiles").append(data);
				
				var blockId= jQuery(data).attr("id").replace("file_group-", "");
				
				if ( jQuery("#groupedFiles .fileGroup").size() >= 3 )
				{
					jQuery("#bottom").show();
				}
				
				textBlockUpdate();
				textEditor("textBlock_" + blockId);
				
				scrollto("#file_group-" + blockId);
				
				updateFlow();
			}
	);
};

var textBlockUpdate= function()
{
	var options = {
	    callback: function(txt, el)
		{
			var id= jQuery(el).attr("id");
			var text= txt;

			jQuery.post("system/modules/projects/assets/update.php",
			{
				action : 'update',
				system: SYSTEM,
				project_id: document.getElementById("id").value,
				file_id: Number( id.substring( id.search('_') + 1 ) ),
				caption: txt
			}, 
			function(data) {
				return data;
			});
		},
	    wait: 500,
	    highlight: false,
	    captureLength: 0
	}
	
	jQuery(".textblock").typeWatch( options );
	
	jQuery(".textblock").change( function()
	{
		var id= jQuery(this).attr("id");
		var text= jQuery(this).val();

		jQuery.post("system/modules/projects/assets/update.php",
		{
			action : 'update',
			system: SYSTEM,
			project_id: document.getElementById("id").value,
			file_id: Number( id.substring( id.search('_') + 1 ) ),
			caption: text
		}, 
		function(data) {
			return data;
		});
	});
};

var updateTextBlocks= function()
{
	var response;
	
	jQuery(".textblock").each( function()
	{
		var id= jQuery(this).attr("id");
		var text= jQuery(this).val();
		
		response= jQuery.post("system/modules/projects/assets/update.php",
				{
					action : 'update',
					system: SYSTEM,
					project_id: document.getElementById("id").value,
					file_id: Number( id.substring( id.search('_') + 1 ) ),
					caption: text
				}, 
				function(data) {
					return data;
				}
		);
	});
	
	return Boolean( response );
}

var deleteGroup= function(groupId)
{
	var isTextBlock= jQuery( "#file_group-" + groupId ).hasClass("textBlock");
	var text= '<h1>Are you sure you want to delete this group?</h1> <p>All files in this group will be deleted.</p>';
	if ( isTextBlock )
		text= '<h1>Are you sure you want to delete this text block?</h1>';
	
	jQuery.prompt( text, {
		buttons: {
			Cancel	: false,
			OK		: true
		},
		callback: function(value, msg, form)
				  {
				  	if ( value == true )
					{	
						jQuery.post("system/modules/projects/assets/update.php",
								{
									action : 'deleteGroup',
									system: SYSTEM,
									projectId: id,
									groupId: groupId,
									isTextBlock: isTextBlock
								}, 
								function(data)
								{
									jQuery( "#file_group-" + groupId ).fadeOut("normal", function() { jQuery(this).remove(); });
									return data;
								}
						);
					}
					
					return true;
				  }
	});
};

var setGroupDisplayer= function(groupId, select)
{
	jQuery.post("system/modules/projects/assets/update.php",
	{
		action : 'setGroupDisplayer',
		system: SYSTEM,
		projectId: id,
		groupId: groupId,
		displayer: select.options[select.selectedIndex].value
	}, 
	function(data)
	{
		return data;
	});
};

var toolbar_details= function(id)
{
	jQuery.noticeAdd({ text: "Loading...", stay: true, type: "heavy" });
	jQuery.post(
				"system/modules/projects/assets/update.php",
				{
					action: 'getDetails',
					system: SYSTEM,
					file_id: id
				},
				function(data)
				{
					jQuery.noticeRemove(jQuery(".heavy"));
					
					var form= '<label for="title">Title</label><input type="text" name="title" value="' + data.title +'" id="title" /><label for="caption">Caption</label><input type="text" name="caption" value="' + data.caption + '" id="caption" />';
					jQuery.prompt( '<h1>Edit File Details</h1>' + form, {
						buttons: {
							Save	: true,
							Cancel	: false
						},
						callback: function(value, msg, form)
								  {
								  	if ( value == true )
									{
										jQuery.noticeAdd({ text: "Saving...", type: "heavy", stay: true });
										jQuery.post(
											"system/modules/projects/assets/update.php",
											{
												action: 'update',
												system: SYSTEM,
												file_id: id,
												title: form.title,
												caption: form.caption
											},
											function(data)
											{
												jQuery.noticeRemove(jQuery(".heavy"));
											}
										);
									}

									return true;
								  }
					});
				},
				"json"
		
	);
	
	// return false;
};

var toolbar_delete= function ( id )
{
	jQuery.prompt( '<h1>Are you sure you want to delete this file?</h1>', {
		buttons: {
			Cancel	: false,
			OK		: true
		},
		callback: function(value, msg, form)
				  {
				  	if ( value == true )
					{
						if ( jQuery("#file_" + id).parent().attr("id") == "waitingRoom" )
						{
							jQuery("#waitingRoom").fadeOut();
						}
						
						jQuery("#file_" + id).fadeOut("normal", function() { jQuery(this).remove(); });
						jQuery.post(
							"system/modules/projects/assets/update.php",
							{
								action: 'delete',
								system: SYSTEM,
								project_id: document.getElementById("id").value,
								file_id: id
							}
						);
					}
					
					return true;
				  }
	});
};

var deleteProjThumbnail= function()
{
	jQuery.post(
			"system/modules/projects/assets/update.php",
			{
				action: 'deleteProjThumbnail',
				system: SYSTEM,
				project_id: id
			},
			function(data)
			{
				jQuery("#thumbnailForm .delete").fadeOut("normal");
				jQuery("#theThumb img").fadeOut("normal", function() { jQuery(this).remove(); });
			}
	);
};

/*
 * Fixed Scroll
 * http://www.webdeveloperjuice.com/2011/08/07/how-to-fix-element-position-after-some-scroll-using-jquery/
 *
 */
(function($)
{
	$.fn.scrollFixed = function(params)
	{
		params = $.extend( {appearAfterDiv: 0, hideBeforeDiv: 0, container: 0, spacer: false}, params);
		var element = $(this);

		if( params.appearAfterDiv )
			var distanceTop = element.offset().top + $(params.appearAfterDiv).outerHeight(true) + element.outerHeight(true);
		else
			var distanceTop = element.offset().top;

		if ( params.hideBeforeDiv )
			var bottom = $(params.hideBeforeDiv).offset().top - element.outerHeight(true) - 10;
		else
			var bottom= $(document).height() - element.outerHeight(true);
			// var bottom = 200000;

		if ( params.spacer )
			$(params.container).prepend('<div class="spacer"></div>');

		$(window).scroll(function()
		{
			if ( element.is(":visible") ) {
				if ( $(window).scrollTop() > distanceTop && $(window).scrollTop() < bottom ) {
					if ( params.spacer ) $(params.container + " .spacer").height( element.outerHeight(true) );
					element.css({'position':'fixed'}).addClass("scrollFixed");
				}else {
					if ( params.spacer ) $(params.container + " .spacer").height(0);
					element.css({'position':'relative'}).removeClass("scrollFixed");
				}
			}else {
				if ( params.spacer ) $(params.container + " .spacer").height(0);
			}
		});			  
	};
})(jQuery);