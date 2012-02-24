<?php
$receptionist_templates= array();

$receptionist_templates["form_start"]= <<<HTML
<form id="{name}" method="{method}" action="{action}" enctype="{enctype}">
HTML;

$receptionist_templates["form_end"]= <<<HTML
</form>
HTML;

$receptionist_templates["row_start"]= <<<HTML
<div class="field{error field_with_error}">
<label for="{name}">{label}</label> {tooltip}
{success {caption}}
{error {error_message}}
HTML;

$receptionist_templates["error_message"]= <<<HTML
<span class="error_message">{message}</span><br />
HTML;

$receptionist_templates["row_end"]= <<<HTML
</div>
HTML;

$receptionist_templates["text_template"]= <<<HTML
<input type="text" name="{name}" id="{name}" value="{defaultValue}" class="textfield long" />
HTML;

$receptionist_templates["textSuperLong"]= <<<HTML
<input type="text" name="{name}" id="{name}" value="{defaultValue}" class="textfield superLong" />
HTML;

$receptionist_templates["caption_template"]= <<<HTML
<span class="caption">{caption_message}</span>
HTML;

$receptionist_templates["tooltip_template"]= <<<HTML
<div class="tooltip" id="{name}_tooltip">
	<a class="tooltipLink" rel="{name}_tooltip">?</a>
	<div class="tooltipMessage">
		<em class="arrow">-</em>
		{tooltip_message}
	</div>
</div>
HTML;

$receptionist_templates["radio_template"]= <<<HTML
<input type="radio" name="{name}" id="{key}" value="{val}" {defaultValue}/><label for="{key}">{key}</label>
HTML;

$receptionist_templates["checkbox_template"]= <<<HTML
<input type="checkbox" name="{name}" id="{key}" value="{val}" {defaultValue}/><label for="{key}">{key}</label>
HTML;

$receptionist_templates["hidden_template"]= <<<HTML
<input type="hidden" name="{name}"  id="{name}" class="hidden" value="{defaultValue}" />
HTML;

$receptionist_templates["password_template"]= <<<HTML
<input type="password" name="{name}" id="{name}" class="textfield long" />
HTML;

$receptionist_templates["submit_template"]= <<<HTML
<button type="submit" name="submit" id="{name}" value="{defaultValue}">{label}</button>
HTML;

$receptionist_templates["reset_template"]= <<<HTML
<input type="reset" name="{name}" id="{name}" value="{defaultValue}" class="input_submit" />
HTML;

$receptionist_templates["file_template"]= <<<HTML
<input type="file" name="{name}[]" id="{name}" class="text long file" />
HTML;

$receptionist_templates["fileMultiple_template"]= <<<HTML
<input type="file" name="{name}[]" id="{name}" class="text long" />
HTML;

$receptionist_templates["textarea_template"]= <<<HTML
<textarea name="{name}" id="{name}" rows="{rows}" cols="{cols}">{defaultValue}</textarea>
HTML;

$receptionist_templates["textareaWithEditor"]= <<<HTML
<div id="toolbar-{name}" class="textblockToolbar"></div>
<textarea name="{name}" id="{name}" rows="{rows}" cols="{cols}">{defaultValue}</textarea>
HTML;

$receptionist_templates["select_template"]= <<<HTML
<select name="{name}" id="{name}">
	{options}
</select>
HTML;

$receptionist_templates["selectLong"]= <<<HTML
<select name="{name}" id="{name}" class="long">
	{options}
</select>
HTML;

$select_multiple= <<<HTML
<br /><select multiple="multiple" size="6" name="{name}[]" id="{name}" class="textfield input_width-200">
	{options}
</select>
HTML;

$receptionist_templates["option_template"]= <<<HTML
<option value="{val}" {defaultValue}>{key}</option>
HTML;

$receptionist_templates["optgroup_template"]= <<<HTML
<optgroup label="{key}"></optgroup>
HTML;

$receptionist_templates['fieldset']= <<<HTML
<fieldset id="{id}" class="{extra}">
	<legend>{legend}</legend>
	<div class="fieldsetContent">
HTML;

$receptionist_templates['fieldset_close']= <<<HTML
	</div>
</fieldset>
HTML;

$receptionist_templates['fieldset_search']= <<<HTML
<div class="fieldset{extra} fieldset_search" id="{id}">
	<div class="tab_container">
		<div class="tab_outline">
			<!-- <div class="center"> -->
				<ul class="tabs">
					<li><a href="#" class="active"><span>{legend}</span></a></li>
				</ul>
			<!-- </div> -->
		</div>
	</div>
	<div class="fieldset_content">
		<table>
			<tr>
				<td>
HTML;

$receptionist_templates['fieldset_close_search']= <<<HTML
				</td>
				<td>
					<input type="submit" name="search" value="perform search" class="submit" />
				</td>
			</tr>
		</table>
	</div>
</div>
HTML;

$receptionist_templates['message']= <<<HTML
	<div class="formMessage">
		<span class="icon">?</span>
		{message}
	</div>
HTML;

$receptionist_templates['select_noblank']= <<<HTML
<select name="{name}" id="{name}" class="select long">
	{options}
</select>
HTML;

$receptionist_templates['select_supershort']= <<<HTML
<select name="{name}" id="{name}" class="select supershort">
	{options}
</select>
HTML;

$receptionist_templates['options_supershort']= <<<HTML
<option value="{val}" class="supershort" {defaultValue}>{key}</option>
HTML;

$receptionist_templates['text_supershort']= <<<HTML
<input type="text" name="{name}" id="{name}" value="{defaultValue}" class="text supershort" maxlength="4" />
HTML;

$receptionist_templates['text_short']= <<<HTML
<input type="text" name="{name}" id="{name}" value="{defaultValue}" class="text supershort" />
HTML;

$receptionist_templates['text_short_html5']= <<<HTML
<input type="text" name="{name}" id="{name}" value="{defaultValue}" class="text supershort" placeholder="{label}" />
HTML;

$receptionist_templates['select_jschange']= <<<HTML
<select name="{name}" id="{name}" onchange="change(this)">
	<option value="#"></option>
	{options}
</select>
HTML;

$receptionist_templates["row_start_blank"]= <<<HTML
<div class="field{error field_with_error}">
HTML;
?>