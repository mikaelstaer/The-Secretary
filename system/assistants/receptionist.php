<?php	
	/*
	 * The Receptionist / The Secretary
	 * by Mikael Stær (www.secretarycms.com, www.mikaelstaer.com)
	 *
	 * A good, very capable, flexible form generator and validator.
	 * In-flow template switching, custom Regex-based rules, custom error messages,
	 * custom templates, this thing is great. Cut development time by at least 50%.
	 * Develop instead of write HTML.
	 *
	 * Todo: proper documentation/comments, code cleanup.
	 */
	
	include_once "receptionist_templates.inc.php";
	
	class Receptionist
	{
		private $name;
		private $method;
		private $action;
		private $enctype;
	
		private $form_vars;
		private $callback;
		private $submitted= false;
		private $save_state= false;
		private $rules= array();
		private $rulesTemp= array();
		private $file_rules= array();
		private $file_rulesTemp= array();
		private $errors= array();
		private $trigger= array();
	
		private $templates= array();
		private $default_templates= array();
	
		private $form_lines= array();
	
		private $stop= false;
	
		public	$e_message= array(
									'required_label'	=>	'{label} is required!',
									'required_name'		=>	'{name} is required!',
									'required'			=>	'This field is required!',
									'oops'				=>	'Oops! There seems to be an error!',
									'oops_mistake'		=>	'Oops! Looks like you made a mistake!',
									'oops_custom'		=>	'Oops! ',
									'this_field'		=>	'This field must ',
									'email'				=>	'Please supply a valid e-mail address.'
							);
							
	function __construct ( $name, $method, $action, $trigger, $enctype= "application/x-www-form-urlencoded", $callback= "" )
	{
		global $receptionist_templates, $_POST, $_GET;
		
		if ( !empty( $receptionist_templates ) )
		{
			$this->name= $name;
			$this->method= $method;
		
		// Set the $form_vars variable so the form can function as either GET or POST.
		
		if ( $method == "post" )
			$this->form_vars= $_POST;
			
		elseif ( $method == "get" )
			$this->form_vars= $_GET;
		
		else
			$this->form_vars= $_POST;
		
		$this->action= ( empty( $action ) ) ? $_SERVER['PHP_SELF'] : $action;		
		$this->enctype= ( empty( $enctype ) ) ? "application/x-www-form-urlencoded" : $enctype;
		$this->callback= ( empty( $callback ) ) ? "process" : $callback;
		$this->trigger= $trigger;

		// Set the template values, $default_templates as "back up" to restore templates through reset_template() function.
		
		$this->templates= $receptionist_templates;
		$this->default_templates= $receptionist_templates;
		
		// If the form as been submitted, set object $submitted variable to true and run validation.
		if ( !empty( $this->trigger ) && !empty( $this->form_vars[$this->trigger] ) ) {
			if (!get_magic_quotes_gpc()) {
				$this->rulesTemp= unserialize(str_replace("';", "\";", str_replace(":'", ":\"", $this->form_vars['rules'])));
				$this->file_rulesTemp= unserialize(str_replace("';", "\";", str_replace(":'", ":\"", $this->form_vars['fileRules'])));
			}else {
				$this->rulesTemp= unserialize(str_replace("';", "\";", str_replace(":'", ":\"", stripslashes($this->form_vars['rules']))));
				$this->file_rulesTemp= unserialize(str_replace("';", "\";", str_replace(":'", ":\"", stripslashes($this->form_vars['fileRules']))));
			}
			$this->submitted= true;
			$this->validate();
		}
		// Template variables and values to send to parser.
		
		$vars= array(
			"$this->method"		=> "{method}",
			"$this->action" 	=> "{action}",
			"$this->name" 		=> "{name}",
			"$this->enctype"	=> "{enctype}"
		);
		// Parse and add to form.
		$this->add_to_form( $this->parse( $this->templates["form_start"], $vars ) );
		
		}else
			echo '<br /><b>The Receptionist</b><br /><i>OOPS!</i> Your form cannot be created because the templates cannot be found (make sure to include the <i>templates.inc.php</i> file).<br /><br />';
	}
	
	/*
	 * $snippet - A string containing HTML code and template variables.
	 * $templ_vars - An array of template variables and corresponding values to replace with.
	 * $error_name - String/optional containing the name of the form element, to double check against
	 *				 $errors array.
	 * $force_error - Boolean/optional, forces method to parse {error} commands even if an error does
	 *				  not exist for the given element. Useful for generating content that 
	 *				  needs to be displayed whenever the form is submitted.
	 *	Parses a string given a code snippet (HTML) and array of matching template variables +
	 *	values. Also will double check the form element against the $errors array to invoke
	 *	{error} commands if there any within the template. Supplying $force_error with a true
	 *	value forces the method to parse {error} commands if the form has been submitted.
	 *	[NOTE: ..will only force {error} commands if $errors array contains at least 1 error]
	 */
	private function parse ($snippet, $templ_vars, $error_name="", $force_error= false) {
		if (!empty($templ_vars)) {
			if ( array_key_exists( $error_name, $this->errors ) ) {
				foreach ( $this->errors[$error_name] as $error ) {
					if ( $error['type'] == "regular" )
						$vars= array( $this->rulesTemp[$error_name][$error['index']-1]['message'] => "{message}");
					elseif ( $error['type'] == "file" )
						$vars= array( $this->file_rulesTemp[$error_name][$error['index']-1]['message'] => "{message}");
						
					$templ.=$this->parse( $this->templates['error_message'], $vars );
				}
				$templ_vars[$templ] = "{error_message}";
			}
			
			$defaultValue= array_search( "{defaultValue}", $templ_vars );			
			$snippet= str_replace( "{defaultValue}", "[defaultValue]", $snippet );
			
			foreach ($templ_vars as $key => $val):
				$snippet= str_replace($val, $key, $snippet);
			endforeach;
			
			$snippet= preg_replace("/{erase.*?}/", "", $snippet);
		
			if ($this->submitted==true && (array_key_exists($error_name, $this->errors) || ($force_error== true && count($this->errors)>= 1))) {
				$snippet= preg_replace("/({success)(.*?)(})/s", "", $snippet);
				$snippet= preg_replace("/({error)(.*?)(})/s", "$2", $snippet);
			}else {
				$snippet= preg_replace("/{error.*?}/s", "", $snippet);
				$snippet= preg_replace("/({success)(.*?)(})/s", "$2", $snippet);
			}
			
			// Do some clean up
			$snippet= preg_replace("/({)(.*?)(})/", "", $snippet);
			$snippet= str_replace( "}", "", $snippet );
			$snippet= str_replace( "[defaultValue]", $defaultValue, $snippet );
		}
				
		$snippet.= "\n";
		return $snippet;
	}
	
	/*
	 * $type - String representing <input> type.
	 * $name - String, a unique name that is used by the rules, errors, parse methods as well
	 *		   as within the template for [name] and [id] attributes.
	 * $label - String/optional, label to place next to element, can be used in <label> tags.
	 * $defaultValue - String/optional, default value for element, used in [value] attribute.
	 * $values - Array/optional of values (keys and vals) for use in checkboxes and radio btns.
	 * $caption - String/optional, any random text for added functionality/information, can be
	 *			  used to denote required fields.
	 * 	$tooltip - String/optional, a message to be placed in a tooltip.
	 *
	 *	Generates form element based on $type by sending appropriate templates and template
	 *		  variable arrays to parse(), then adds parsed element to $form_lines array for output.
	 */	
	public function add_input ($type, $name, $label, $defaultValue= "", $values= "", $caption="{erase}", $tooltip= "{erase}") {
		// Here we need to do a little trickery because we have denoted, in the $type"_template", WHERE to place the tooltip by writing
		// {tooltip}, BUT we also have the actual "tooltip_template" which gives the tooltip its structure (which must also be parsed).
		// We solve this problem by passing the "tooltip_template" as a template variable to the parser, which gets parsed first because
		// of its position in the array, leaving the {tooltip_message} indicator to be replaced with the value of $tooltip.
		// [January 23 2008] Wow, I must have been high or something, that was intricately confusing...

 		if ($tooltip== "{erase}" || empty($tooltip))
			$tooltip_templ= "{erase}";
		else
			$tooltip_templ= $this->templates['tooltip_template'];
		
		if ($caption== "{erase}" || empty($caption))
			$caption_templ= "{erase}";
		else
			$caption_templ= $this->templates['caption_template'];
		
		$vars= array(
			"$tooltip_templ" 	=> 	"{tooltip}",
			"$tooltip" 			=> 	"{tooltip_message}",
			"$type"				=>	"{type}",
			"$name" 			=> 	"{name}",
			"$label" 			=> 	"{label}",
			"$caption_templ"	=>	"{caption}",
			"$caption" 			=> 	"{caption_message}"
		);
		
		// Do this so that elements without labels are automatically given a differen structure, aka "naked elements", 
		// without having to use add_anon_field().
		if ($label != NULL && $type != "hidden")
		{
			$template= $this->templates["row_start"];
		}
		
		switch ($type) {
		case "text":
			$vars[$this->saved($name, $defaultValue)]= "{defaultValue}";
			$template.= $this->templates["text_template"];
			break;
		case "radio":			
			$valvars= $vars;
			$vals= "";
			
			foreach ($values as $key=>$val) {
				$valvars[$key]= "{key}";
				$valvars[$val]= "{val}";
				
				if ( is_array( $this->form_vars[$name] ) && ( $val== $defaultValue || in_array( $val, $this->saved($name, $defaultValue) ) ) ) {
					$valvars['checked="checked"']= "{defaultValue}";
				}elseif (!$this->submitted && ($key== $defaultValue || $val== $defaultValue) ) {
					$valvars['checked="checked"']= "{defaultValue}";
				}elseif ( $val == $defaultValue ) {
					$valvars['checked="checked"']= "{defaultValue}";
				}
				
				$vals.= $this->parse($this->templates["radio_template"], $valvars, $name);
				$valvars= $vars;
			}
			$template.= $vals;
			break;
		case "checkbox":
			$valvars= $vars;
			$vals= "";

			foreach ($values as $key=>$val) {
				$valvars[$key]= "{key}";
				$valvars[$val]= "{val}";

				if ( is_array( $this->form_vars[$name] ) && ( $val== $defaultValue || in_array( $val, $this->saved($name, $defaultValue) ) ) ) {
					$valvars['checked="checked"']= "{defaultValue}";
				}elseif (!$this->submitted && ($key== $defaultValue || $val== $defaultValue) ) {
					$valvars['checked="checked"']= "{defaultValue}";
				}elseif ( $val == $defaultValue ) {
					$valvars['checked="checked"']= "{defaultValue}";
				}
				
				$vals.= $this->parse($this->templates["checkbox_template"], $valvars, $name);
				$valvars= $vars;
			}
			$template.= $vals;
			break;
		case "hidden":
			$vars[$defaultValue]= "{defaultValue}";
			$template.= $this->parse($this->templates["hidden_template"], $vars);
			break;
		case "password":
			$vars[$defaultValue]= "{defaultValue}";
			$template.= $this->templates["password_template"];
			break;
		case "submit":
			$vars[$defaultValue]= "{defaultValue}";
			$template.= $this->templates["submit_template"];
			break;
		case "reset":
			$vars[$defaultValue]= "{defaultValue}";
			$template.= $this->templates["reset_template"];
			break;
		case "file":
			$template.= $this->templates["file_template"];
			break;
		}
		
		if ($label != NULL && $type != "hidden")
			$template.= $this->templates["row_end"];
		
		// Parse and add to form.
		$this->add_to_form($this->parse($template, $vars, $name));
	}
	
	public function add_textarea ($name, $label, $rows= "5", $cols= "100", $defaultValue= "{erase}", $caption= "{erase}", $tooltip= "{erase}") {
		if ($tooltip== "{erase}" || empty($tooltip))
			$tooltip_templ= "{erase}";
		else
			$tooltip_templ= $this->templates['tooltip_template'];
		
		if ($caption== "{erase}" || empty($caption))
			$caption_templ= "{erase}";
		else
			$caption_templ= $this->templates['caption_template'];
					
		if (empty($rows))
			$rows= "5";
		if (empty($cols))
			$cols= "50";
			
		$vars= array(
			"$tooltip_templ" 	=> 	"{tooltip}",
			"$tooltip" 			=> 	"{tooltip_message}",
			"$type"				=>	"{type}",
			"$name" 			=> 	"{name}",
			"$label" 			=> 	"{label}",
			"$caption_templ"	=>	"{caption}",
			"$caption" 			=> 	"{caption_message}",
			"$rows" 			=> "{rows}",
			"$cols" 			=> "{cols}"
		);
		
		$vars[$this->saved($name, $defaultValue)]= "{defaultValue}";
		
		if ($label != NULL)
			$template= $this->templates["row_start"];
			
		$template.= $this->templates["textarea_template"];
			
		if ($label != NULL)
			$template.= $this->templates["row_end"];
		
		// Parse and add to form.
		$this->add_to_form($this->parse($template, $vars, $name));
	}

	public function add_select ($name, $label, $options, $defaultValue= "{erase defv}", $caption= "{erase}", $tooltip= "{erase}") {
		if ($tooltip== "{erase}" || empty($tooltip))
			$tooltip_templ= "{erase}";
		else
			$tooltip_templ= $this->templates['tooltip_template'];
		
		if ($caption== "{erase}" || empty($caption))
			$caption_templ= "{erase}";
		else
			$caption_templ= $this->templates['caption_template'];
			
		$vars= array(
			"$tooltip_templ" 	=> 	"{tooltip}",
			"$tooltip" 			=> 	"{tooltip_message}",
			"$type"				=>	"{type}",
			"$name" 			=> 	"{name}",
			"$label" 			=> 	"{label}",
			"$caption_templ"	=>	"{caption}",
			"$caption" 			=> 	"{caption_message}"
		);
	
		if ($label != NULL)
			$template= $this->templates["row_start"];

		$template.= $this->templates["select_template"];
		
		$defaultValueArray= explode( ",", $defaultValue );
		
		// $optvars, populate with template variables of $vars and expand by looping through $options, adding each as more variables
		// $opts will become a long string containing parsed "opt(ion/group)_templates"
		$optvars= $vars;
		$opts= "";
		
		if ( !empty( $options ) )
		foreach ($options as $key=>$val) {
			$optvars[$key]= "{key}";
			$optvars[$val]= "{val}";
			
			// Set the selected field by replacing {defaultValue} with [selected] attribute.
			
			if ( ( $this->saved($name, $defaultValue)== $val || $this->saved($name, $defaultValue)== $key) || ( is_array( $this->form_vars[$name] ) && ( ( $val == $defaultValue || $key == $defaultValue ) || in_array( $val, $this->saved($name, $defaultValue) ) ) ) ) {
				$optvars['selected="selected"']= "{defaultValue}";
				// is_array( $this->form_vars[$name] )
			}elseif ( ( !$this->submitted && ($key== $defaultValue || $val== $defaultValue) ) || ( !$this->submitted && ( in_array( $val, $defaultValueArray ) || in_array( $key, $defaultValueArray ) ) ) )
				$optvars['selected="selected"']= "{defaultValue}";
			
			// Define an <optgroup> here - use "optgroup_template" instead of "option_template".
			if ($val== "OPTG")
				$opts.= $this->parse($this->templates["optgroup_template"], $optvars, $name);
			else
				$opts.= $this->parse($this->templates["option_template"], $optvars, $name);

			$optvars= $vars;
		}
		
		// Because our "select_template" (and structure of <select> tags) requires a template variable of {options} to place <option> tags
		// we perform the same trick as we did with the tooltip and replace {options} with $opts.
		$vars[$opts]= "{options}";
		
		if ($label != NULL)
			$template.= $this->templates["row_end"];
		
		// Parse and add to form.
		$this->add_to_form($this->parse($template, $vars, $name));
	}
	
	public function add_fieldset( $text= "", $id= "", $extra= "" ) {
		$vars= array(
			"$text" 	=> "{legend}",
			"$id" 		=> "{id}",
			"$extra"	=> "{extra}"
		);
		
		$template= $this->templates['fieldset'];
		$this->add_to_form( $this->parse( $template, $vars ) );
	}
	
	public function close_fieldset() {
		$template= $this->templates['fieldset_close'];
		$this->add_to_form( $this->parse( $template, $vars ) );
	}
	
	/*
	 *	This allows the creation of entirely custom elements/fields/rows/anything within the flow of the
	 *	form. Simply pass a template and a set (array) of template variables to parse. Rules can
	 *	also be set for anonymous fields, so the functionality of $error_name and $force_error
	 *	is preserved. [NOTE: Make sure to keep names consistent otherwise conflicts will occur
	 *	between rules, errors and elements!]
	 */
	public function add_anon_field ($snippet, $templ_vars, $error_name= "", $force_error= false) {
		$this->add_to_form($this->parse($snippet, $templ_vars, $error_name, $force_error));
	}
	
	/*
	 * $field_name - String, the unique name of the form element to attach the rule to.
	 * $match - String|Array/optional, sets how to validate this rule.
	 * $display_name - String/optional, an alias for the form element, if rules are being
	 * 				   displayed and the element name is not exactly pretty.
	 *				   (ie. <input ... name="email">, we can 'mask' this name, which is suitable 
	 *				   for scripts but not text output, with something like 'E-mail')
	 *
	 *	Attaches a rule to an element. A $match value of 'required' tells the validator that
	 *	the field must not be left empty; passing a single-dimensional array will tell the
	 *	validator that user input must match at least one value within that array; passing any
	 *	other string tells the validator to treat $match as a Regex expression.
	 *	[NOTE: By incorporating Regex functionality, essentially any kind of match can be made!]
	 */
	public function add_rule ($field_name, $match= "required", $display_name= "", $message= "") {
		if (empty($match))
			$match= "required";

		$message= ( empty( $message ) ) ? $this->e_message['required'] : $message;
		
		$this->rules[$field_name][]= 
			array(
				"field_name" 	=> $field_name,
				"match" 		=> $match,
				"display_name" 	=> $display_name,
				"message" 		=> $message
			);
	}
	
	public function add_file_rule ($field_name, $match= "required", $match_what= "name", $display_name= "", $message= "") {
		if (empty($match))
			$match= "required";
		if (empty($match_what))
	 		$match_what= "name";

		$message= ( empty( $message ) ) ? $this->e_message['required'] : $message;
		
		$this->file_rules[$field_name][]=
			array(
				"field_name"	=> $field_name,
				"match" 		=> $match,
				"match_what" 	=> $match_what,
				"display_name" 	=> $display_name,
				"message"		=> $message
			);
	}
	
	/*
	 *	Validates the form by checking user input against the $rules. If a rule is not met, an
	 *	an error is registered to $errors (which is in turn used by parse() to invoke {error}
	 *	and {success} template commands! Wow, it's one big circle... ).
	 */
	private function validate() {		
		// Our rules array is actually being preserved through a hidden field generated by the script,
		// so we must unserialize() it (str_replace() is used to reverse the single-quotes to double-quotes
		// change made by dump_rules() ).
				
		if ( count($this->rulesTemp) >= 1 && is_array( $this->rulesTemp ))
		foreach ($this->rulesTemp as $field_name => $rule) {
			$count= 0;
			foreach ( $rule as $data ) {
				$count++;
				$fieldName= $data['field_name'];
				$displayName= ( empty( $data['display_name'] ) ) ? $data['field_name'] : $data['display_name'];
				
				switch ($data['match']) {
					case "required":
						if (empty($this->form_vars[$fieldName]))
							$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'regular');
					break;
					case gettype($data['match'])== "array":
						if (!in_array($this->form_vars[$fieldName], $data['match']))
							$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'regular');
					break;
					case gettype($data['match'])== "string" && $data['match']!= "required":
						if (!preg_match($data['match'], $this->form_vars[$fieldName], $array))
							$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'regular');
					break;
				}
			}
		}
		
		if ( count($this->file_rulesTemp) >= 1 && is_array( $this->file_rulesTemp ))
		foreach ($this->file_rulesTemp as $field_name => $rule) {
			$count= 0;
			foreach ( $rule as $data ) {
				$count++;
				$fieldName= $data['field_name'];
				$displayName= ( empty( $data['display_name'] ) ) ? $data['field_name'] : $data['display_name'];
				
				switch ($data['match']) {
					case "required":
						$countFiles= count($_FILES[$fieldName]["name"]);
						for ($i= 0; $i< $countFiles; $i++) {
							if (empty($_FILES[$fieldName]["name"][$i]))
								$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'file');
						}
						
						if ( $countFiles == 0 )
						{
							$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'file');
						}	
					break;
					case gettype($data['match'])== "array":
						if ($data['match_what']== "name" || $data['match_what']== "type") {
							$countFiles= count($_FILES[$fieldName][$data['match_what']]);
							for ($i= 0; $i< $countFiles; $i++) {
								if (!in_array($_FILES[$fieldName][$data['match_what']][$i], $data['match']))
									$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'file');
							}
						}elseif ($data['match_what']== "size") {
							$min= $match[0];
							$max= $match[1];
							$countFiles= count($_FILES[$fieldName]["size"]);
							for ($i= 0; $i< $countFiles; $i++) {
								if (!($_FILES[$fieldName]["size"][$i]>= $min && $_FILES[$fieldName]["size"][$i]<= $max))
									$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'file');
							}					
						}
					break;
					case $data['match']!= "required":
						$count= count($_FILES[$fieldName][$data['match_what']]);
						for ($i= 0; $i< $count; $i++) {
							if (!preg_match($data['match'], $_FILES[$fieldName][$data['match_what']][$i], $array))
								$this->errors[$fieldName][]= array( 'field' => $fieldName, 'index' => $count, 'type' => 'file');
						}
					break;
				}
			}
		}
		
		// If there are no errors, call the user-defined function 'process' to handle data
		// submission/transmission to the database or otherwise.
		if (count($this->errors) == 0) {
			if (function_exists($this->callback))
				call_user_func($this->callback);
			else
				echo '<br /><b>The Receptionist</b><br /><i>OOPS!</i> The form cannot be processed because the callback function <i>'.$this->callback.'()</i> does not exist.<br /><br />' 	;
		}
	}
	
	public function close()
	{
		$rules= str_replace( "\"","'",serialize( $this->rules ) );
		$file_rules= str_replace( "\"","'", serialize( $this->file_rules ) );
		
		if ( count($this->rules) > 0 || count($this->file_rules) >  0 )
			$this->add_to_form( '<div>' );
			
		if ( count( $this->rules ) > 0 )
			$this->add_to_form( '<input type="hidden" name="rules"  id="rules" value="'.$rules.'"/>');
			
		if ( count( $this->file_rules ) > 0 )
			$this->add_to_form('<input type="hidden" name="fileRules"  id="fileRules" value="'.$file_rules.'"/>');
			
		if ( count($this->rules) > 0 || count($this->file_rules) >  0 )
			$this->add_to_form( '</div>' );
			
		$this->add_to_form($this->parse($this->templates["form_end"], "", "", true));
		$this->draw();
	}
	
	/*
	 *	$name - String, the name of the template to set, ie. "text_template" or "option_template"
	 *	$val - String, a replacement template, either an external variable or name of a currently
	 *		   loaded template.
	 *	$name_switch - Boolean/optional, lets the function know if $val is an external template
	 *				   or if it should switch templates based on names of loaded templates.
	 *	Sets the template for a specific form element, essentially replacing the value of $name 
	 *		  in $templates for $val.
	 */
	public function set_template ($name, $val, $name_switch= false) {
		if ( !$name_switch )
			$this->templates["$name"]= $val;
		else
			$this->templates["$name"]= $this->templates["$val"];
	}
	
	/*
	 *	$name - String, the name of the template to be reset, ie. "radio_template" or "form_start"
	 *	Returns the given template to its original state. Really just swaps values from
	 *	$default_templates into $templates.
	 */
	public function reset_template ($name) {
		$this->templates["$name"]= $this->default_templates["$name"];
	}
	
	/*
	 * $line - String, anything to add to the form, raw HTML etc.
	 */
	public function add_to_form ($line) {
		$this->form_lines[]= $line."\n";
	}
	
	/*
	 * Loops through the $form_lines array and outputs each value.
	 */
	public function draw () {
		if ( $this->stop == false ){ 
		if ( count( $this->form_lines ) >= 1 ):
			foreach ($this->form_lines as $line)
				echo $line;
			
		endif;
		$this->form_lines= array();
		}
	}
	
	public function save_state ( $state= true ) {
		$this->save_state= $state;
	}
	
	public function state_saved() {
		if ( $this->save_state == true )
			return true;
		else
			return false;
	}
	
	private function saved ($name, $defaultValue) {
		if ($this->submitted== true && $this->save_state== true && count($this->errors) == 0)
			return stripslashes( htmlspecialchars($this->form_vars[$name], ENT_QUOTES, 'UTF-8' ) );
		elseif ($this->submitted== true && $this->save_state== true && in_array($name, $this->errors)== false)
			return stripslashes( htmlspecialchars($this->form_vars[$name], ENT_QUOTES, 'UTF-8' ) );
		elseif (empty($defaultValue))
			return stripslashes( htmlspecialchars($this->form_vars[$name], ENT_QUOTES, 'UTF-8' ) );
		else
			return $defaultValue;
	}

	public function get_errors () {
		return $this->errors;
	}
	
	public function count_errors() {
		return count( $this->errors );
	}
	
	public function get_rules () {
		return $this->rules;
	}
	
	public function get_file_rules () {
		return $this->file_rules;
	}

	public function submitted() {
		if ($this->submitted== true)
			return true;
		else
			return false;
	}

	public function show_errors () {
		echo "Errors:<br />";
		// print_r($this->errors);
		foreach ($this->errors as $e):
			echo $this->rules[$e]['display_name'];
		endforeach;
		echo "<br />";
	}
	
	public function show_rules () {
		echo "Rules:<br />";
		foreach ($this->rules as $val) {
			foreach ($val as $key=>$final) {
				echo "$key: $final<br />";
			}
			echo "<br />";
		}
		
		echo "File Rules:<br />";
		foreach ($this->file_rules as $val) {
			foreach ($val as $key=>$final) {
				echo "$key: $final<br />";
			}
			echo "<br />";
		}
	}
	
	public function size() {
		return count( $this->form_lines );
	}
	
	public function message( $message ) {
		$vars= array(
			$message	=>	'{message}'
		);
		$this->add_to_form( $this->parse( $this->templates['message'], $vars ) );
	}
	
	public function stop() {
		$this->stop= true;
	}
	
	public function start() {
		$this->stop= false;
	}
	
	public function errorBox( $message= "" ) {
		$message= ( empty($message) ) ? "The following errors occured:<br />" : $message;
		if ( $this->submitted() && count($this->errors) >= 1 ) {
			$box= '<div id="errorBox"><span class="errorBoxMessage">'.$message.'</span><ul>';
			foreach ( $this->errors as $e ) {
				foreach ( $e as $data ) {
					if ( $data['type'] == "regular" )
						$box.= '<li>'.$this->rulesTemp[$data['field']][$data['index']-1]['message'].'</li>';
					if ( $data['type'] == "file" )
						$box.= '<li>'.$this->file_rulesTemp[$data['field']][$data['index']-1]['message'].'</li>';
						
				}
			}
			$box.= '</ul></div>';
		}
		return $box;
	}
	
	public function getTemplate( $template ) {
		return $this->templates[$template];
	}
}
?>