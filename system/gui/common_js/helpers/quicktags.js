// Based on JS QuickTags by Alex King
//
// Copyright (c) 2002-2008 Alex King
// http://alexking.org/projects/js-quicktags
//
// Modified by Mikael Stær, 2010
// - addition of 'func' parameter to edButton(), to allow for custom functions/callbacks
// 		- modified edShowButton() to handle 'func' option
// - removed 'open' parameter in edButton()
// - added jQuery change() trigger, to edInsertTag()

var edButtons = new Array();
var edLinks = new Array();
var edOpenTags = new Array();

function edButton(id, display, tagStart, tagEnd, access, func) {
	this.id = id;
	this.display = display;
	this.tagStart = tagStart;
	this.tagEnd = tagEnd;
	this.access = access;
	this.func = func;
}

edButtons.push(
	new edButton(
		'ed_bold'
		,'B'
		,'<strong>'
		,'</strong>'
		,'b'
	)
);

edButtons.push(
	new edButton(
		'ed_italic'
		,'I'
		,'<em>'
		,'</em>'
		,'i'
	)
);

edButtons.push(
	new edButton(
		'ed_underline'
		,'U'
		,'<u>'
		,'</u>'
		,'u'
	)
);

edButtons.push(
	new edButton(
		'ed_link'
		,'Link'
		,''
		,'</a>'
		,'a'
	)
);

var extendedStart = edButtons.length;

function edLink(display, URL, newWin) {
	this.display = display;
	this.URL = URL;
	if (!newWin) {
		newWin = 0;
	}
	this.newWin = newWin;
}

function edShowButton(which, button, i) {
	if (button.access) {
		var accesskey = ' accesskey = "' + button.access + '"'
	}
	else {
		var accesskey = '';
	}
	switch (button.id) {
		case 'ed_link':
			return '<a href="#" ' + accesskey + ' class="ed_button ' + button.id + '" onclick="edInsertLink(\'' + which + '\', ' + i + '); return false;">' + button.display + '</a>'
			break;
		default:
			if ( button.func != null && button.func != "" && typeof(button.func) != "undefined" )
				var onclick= button.func + '(\'' +  which + '\');';
			else
				var onclick= 'edInsertTag(\'' + which + '\', ' + i + ');';
				
			return '<a href="#" ' + accesskey + ' class="ed_button ' + button.id + '" onclick="' + onclick + ' return false;">' + button.display + '</a>';
			break;
	}
}

function edCheckOpenTags(which, button) {
	var tag = 0;
	for (i = 0; i < edOpenTags[which].length; i++) {
		if (edOpenTags[which][i] == button) {
			tag++;
		}
	}
	if (tag > 0) {
		return true;
	}
	else {
		return false;
	}
}

function edCloseAllTags(which) {
	var count = edOpenTags[which].length;
	for (o = 0; o < count; o++) {
		edInsertTag(which, edOpenTags[which][edOpenTags[which].length - 1]);
	}
}

function textEditor(which) {
	var toolbar= "";

	for (i = 0; i < extendedStart; i++) {
		toolbar+= edShowButton(which, edButtons[i], i);
	}
	
	for (i = extendedStart; i < edButtons.length; i++) {
		toolbar+= edShowButton(which, edButtons[i], i);
	}
	
	var target= String("toolbar-" + which);
	document.getElementById(target).innerHTML= toolbar;
		
    edOpenTags[which] = new Array();
}

function edInsertTag(which, i) {
    myField = document.getElementById(which);
	//IE support
	if (document.selection) {
		myField.focus();
	    sel = document.selection.createRange();
		if (sel.text.length > 0) {
			sel.text = edButtons[i].tagStart + sel.text + edButtons[i].tagEnd;
		}
		else {
			sel.text = edButtons[i].tagStart + sel.text + edButtons[i].tagEnd;
		}
		myField.focus();
		jQuery("#" + myField.id).change();
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var cursorPos = endPos;
		var scrollTop = myField.scrollTop;
		if (startPos != endPos) {
			myField.value = myField.value.substring(0, startPos)
			              + edButtons[i].tagStart
			              + myField.value.substring(startPos, endPos) 
			              + edButtons[i].tagEnd
			              + myField.value.substring(endPos, myField.value.length);
			cursorPos += edButtons[i].tagStart.length + edButtons[i].tagEnd.length;
		}
		else {
			myField.value = myField.value.substring(0, startPos)
			              + edButtons[i].tagStart
			              + myField.value.substring(startPos, endPos) 
			              + edButtons[i].tagEnd
			              + myField.value.substring(endPos, myField.value.length);
			cursorPos += edButtons[i].tagStart.length + edButtons[i].tagEnd.length;
		}
		myField.focus();
		jQuery("#" + myField.id).change();
		myField.selectionStart = cursorPos;
		myField.selectionEnd = cursorPos;
		myField.scrollTop = scrollTop;
	}
	else {
		if (!edCheckOpenTags(which, i) || edButtons[i].tagEnd == '') {
			myField.value += edButtons[i].tagStart;
			edAddTag(which, i);
		}
		else {
			myField.value += edButtons[i].tagEnd;
			edRemoveTag(which, i);
		}
		myField.focus();
		jQuery("#" + myField.id).change();
	}
}

function edInsertContent(which, myValue) {
    myField = document.getElementById(which);
	//IE support
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
		myField.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var scrollTop = myField.scrollTop;
		myField.value = myField.value.substring(0, startPos)
		              + myValue 
                      + myField.value.substring(endPos, myField.value.length);
		myField.focus();
		myField.selectionStart = startPos + myValue.length;
		myField.selectionEnd = startPos + myValue.length;
		myField.scrollTop = scrollTop;
	} else {
		myField.value += myValue;
		myField.focus();
	}
}

function edInsertLink(which, i, defaultValue) {
    myField = document.getElementById(which);
	if (!defaultValue) {
		defaultValue = 'http://';
	}
	if (!edCheckOpenTags(which, i)) {
		var URL = prompt('Enter the URL' ,defaultValue);
		if (URL) {
			edButtons[i].tagStart = '<a href="' + URL + '">';
			edInsertTag(which, i);
		}
	}
	else {
		edInsertTag(which, i);
	}
}