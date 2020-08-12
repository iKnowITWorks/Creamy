<?php
/**
	The MIT License (MIT)
	
	Copyright (c) 2015 Ignacio Nieto Carvajal
	
	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
*/

namespace creamy;

// dependencies
require_once('CRMDefaults.php');
require_once('LanguageHandler.php');
require_once('CRMUtils.php');
require_once('ModuleHandler.php');

// constants
define ('CRM_UI_DEFAULT_RESULT_MESSAGE_TAG', "resultmessage");
error_reporting(E_ERROR | E_PARSE);

/**
 *  UIHandler.
 *  This class is in charge of generating the dynamic HTML code for the basic functionality of the system. 
 *  Every time a page view has to generate dynamic contact, it should do so by calling some of this class methods.
 *  UIHandler uses the Singleton pattern, thus gets instanciated by the UIHandler::getInstante().
 *  This class is supposed to work as a ViewController, stablishing the link between the view (PHP/HTML view pages) and the Controller (DbHandler).
 */
 class UIHandler {
	
	// language handler
	private $lh;
	// Database handler
	private $db;
	
	/** Creation and class lifetime management */

	/**
     * Returns the singleton instance of UIHandler.
     * @staticvar UIHandler $instance The UIHandler instance of this class.
     * @return UIHandler The singleton instance.
     */
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

	
    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        require_once dirname(__FILE__) . '/DbHandler.php';
        // opening db connection
        $this->db = new \creamy\DbHandler();
        $this->lh = \creamy\LanguageHandler::getInstance();
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
    
    /** Generic HTML structure */

	public function fullRowWithContent($content, $colStyle = "lg") {
		return '<div class="row"><div class="col-'.$colStyle.'-12">'.$content.'</div></div>';
	}
	
	public function rowWithVariableContents($spans, $columns, $columnsStyle = "lg") {
		// safety checks
		if ((!is_array($spans)) || (!is_array($columns))) { return ""; }
		if (count($spans) != count($columns)) { return ""; }
		// build the structure
		$result = '<div class="row">';
		for ($i = 0; $i < count($spans); $i++) {
			$result .= '<div class="col-'.$columnsStyle.'-'.$spans[$i].'">'.$columns[$i].'</div>';
		}
		$result .= '</div>';
		return $result;
	}

    public function boxWithContent($header_title, $body_content, $footer_content = NULL, $icon = NULL, $style = CRM_UI_STYLE_DEFAULT, $body_id = NULL, $additional_body_classes = "") {
	    // if icon is present, generate an icon item.
	    $iconItem = (empty($icon)) ? "" : '<i class="fa fa-'.$icon.'"></i>';
	    $bodyIdCode = (empty($body_id)) ? "" : 'id="'.$body_id.'"';
	    $boxStyleCode = empty($style) ? "" : "box-$style";
	    $footerDiv = empty($footer_content) ? "" : '<div class="box-footer">'.$footer_content.'</div>';
	    
	    return '<div class="box '.$boxStyleCode.'">
					<div class="box-header">'.$iconItem.'
				        <h3 class="box-title">'.$header_title.'</h3>
				    </div>
					<div class="box-body '.$additional_body_classes.'" '.$bodyIdCode.'>'.$body_content.'</div>
					'.$footerDiv.'
				</div>';
    }
    
    public function collapsableBoxWithContent($header_title, $body_content, $footer_content = NULL, $icon = NULL, $style = CRM_UI_STYLE_DEFAULT, $body_id = NULL, $initiallyCollapsed = true) {
	   	// if icon is present, generate an icon item.
	    $iconItem = (empty($icon)) ? "" : '<i class="fa fa-'.$icon.'"></i>';
	    $bodyIdCode = (empty($body_id)) ? "" : 'id="'.$body_id.'"';
	    $boxStyleCode = empty($style) ? "" : " box-$style";
	    $collapsedCode = $initiallyCollapsed ? " collapsed-box" : "";
	    $footerDiv = empty($footer_content) ? "" : '<div class="box-footer">'.$footer_content.'</div>';
	    
	    return '<div class="box'.$boxStyleCode.$collapsedCode.'">
					<div class="box-header">'.$iconItem.'
                        <div class="box-tools pull-right">
                            <button class="btn btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
                            <button class="btn btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                        </div>
				        <h3 class="box-title">'.$header_title.'</h3>
				    </div>
					<div class="box-body" '.$bodyIdCode.'>'.$body_content.'</div>
					'.$footerDiv.'
				</div>'; 
    }
        
    public function responsibleTableBox($header_title, $table_content, $icon = NULL, $style = CRM_UI_STYLE_DEFAULT, $body_id = NULL) {
	    return $this->boxWithContent($header_title, $table_content, NULL, $icon, $style, $body_id, "table-responsive");
    }
    
    public function boxWithMessage($header_title, $message, $icon = NULL, $style = CRM_UI_STYLE_DEFAULT) {
	    $body_content = '<div class="callout callout-'.$style.'"><p>'.$message.'</p></div>';
	    return $this->boxWithContent($header_title, $body_content, NULL, $icon, $style);
    }
    
    public function boxWithForm($id, $header_title, $content, $submit_text = null, $style = CRM_UI_STYLE_DEFAULT, $messagetag = CRM_UI_DEFAULT_RESULT_MESSAGE_TAG) {
	    if (empty($submit_text)) { $submit_text = $this->lh->translationFor("accept"); }
	    return '<div class="box box-default"><div class="box-header"><h3 class="box-title">'.$header_title.'</h3></div>
	    	   '.$this->formWithContent($id, $content, $submit_text, $style, $messagetag).'</div>';
    }
    
    public function boxWithQuote($title, $quote, $author, $icon = "quote-left", $style = CRM_UI_STYLE_DEFAULT, $body_id = null, $additional_body_classes = "") {
	    $body_content = '<blockquote><p>'.$quote.'</p>'.(empty($author) ? "" : '<small>'.$author.'</small>').'</blockquote>';
	    return $this->boxWithContent($title, $body_content, null, $icon, $style, $body_id, $additional_body_classes);
    }
    
    public function infoBox($title, $subtitle, $url, $icon, $color, $colsize = 3) {
	    return '<div class="col-md-'.$colsize.'"><div class="info-box"><a href="'.$url.'"><span class="info-box-icon bg-'.$color.'"><i class="fa fa-'.$icon.'"></i></span></a>
	    	<div class="info-box-content"><span class="info-box-text">'.$title.'</span>
			<span class="info-box-number">'.$subtitle.'</span></div></div></div>';
    }
    
    public function boxWithSpinner($header_title, $body_content, $footer_content = NULL, $icon = NULL, $overlayId = "loading-overlay") {
	    $footerDiv = empty($footer_content) ? "" : '<div class="box-footer">'.$footer_content.'</div>';
	    $iconItem = (empty($icon)) ? "" : '<i class="fa fa-'.$icon.'"></i>';
	    return '<div class="box box-primary">
			<div class="box-header">'.$iconItem.'
		        <h3 class="box-title">'.$header_title.'</h3>
		    </div>
			<div class="box-body">'.$body_content.'</div>
			'.$footerDiv.'
			'.$this->spinnerOverlay($overlayId).'
		</div>';
    }

    
    public function spinnerOverlay($overlayId = "loading-overlay") { 
	    return '<div id="'.$overlayId.'" class="overlay"><i class="fa fa-spinner fa-spin"></i></div>'; 
	}
    
    /** Tables */

    public function generateTableHeaderWithItems($items, $id, $styles = "", $needsTranslation = true, $hideHeading = false, $hideOnMedium = array(), $hideOnLow = array()) {
	    $theadStyle = $hideHeading ? 'style="display: none!important;"' : '';
	    $table = "<table id=\"$id\" class=\"table $styles\"><thead $theadStyle><tr>";
	    if (is_array($items)) {
		    foreach ($items as $item) {
			    // class modifiers for hiding classes in medium or low resolutions.
			    $classModifiers = "class=\"";
			    if (in_array($item, $hideOnMedium)) { $classModifiers .= " hide-on-medium "; }
			    if (in_array($item, $hideOnLow)) { $classModifiers .= " hide-on-low "; }
			    $classModifiers .= "\"";
			    // build header item
			    $table .= "<th $classModifiers>".($needsTranslation ? $this->lh->translationFor($item) : $item)."</th>";
		    }
	    }
		$table .= "</tr></thead><tbody>";
		return $table;
    }
    
    public function generateTableFooterWithItems($items, $needsTranslation = true, $hideHeading = false, $hideOnMedium = array(), $hideOnLow = array()) {
	    $theadStyle = $hideHeading ? 'style="display: none!important;"' : '';
	    $table = "</tbody><tfoot $theadStyle><tr>";
	    if (is_array($items)) {
		    foreach ($items as $item) {
			    // class modifiers for hiding classes in medium or low resolutions.
			    $classModifiers = "class=\"";
			    if (in_array($item, $hideOnMedium)) { $classModifiers .= " hide-on-medium "; }
			    if (in_array($item, $hideOnLow)) { $classModifiers .= " hide-on-low "; }
			    $classModifiers .= "\"";
				// build footer item
			    $table .= "<th $classModifiers>".($needsTranslation ? $this->lh->translationFor($item) : $item)."</th>";
		    }
	    }
		$table .= "</tr></tfoot></table>";
		return $table;
	}
    
    /** Style and color */
    
    /**
	 * Returns the array of creamy colors as an associative arrays.
	 * Keys are creamy tags (which can be used for css text-<color>)
	 * Values are their #rrggbb representation.
	 */
    public function creamyColors() {
	    return array(
		    "aqua" => "#00c0ef",
		    "blue" => "#0073b7",
		    "light-blue" => "#3c8dbc",
		    "teal" => "#39cccc",
		    "yellow" => "#f39c12",
		    "orange" => "#ff851b",
		    "green" => "#00a65a",
		    "lime" => "#01ff70",
		    "red" => "#dd4b39",
		    "purple" => "#605ca8",
		    "fuchsia" => "#f012be",
		    "navy" => "#001f3f",
		    "muted" => "#777"
	    );
    }
    
    /**
	 * Returns the rgb hex value (including #) string for the given creamy color.
	 * If $color is not found, returns CRM_UI_COLOR_DEFAULT_HEX.
	 */
    public function hexValueForCreamyColor($color) {
		$colors = $this->creamyColors();
		if (array_key_exists($color, $colors)) { return $colors[$color]; }
		else return CRM_UI_COLOR_DEFAULT_HEX;
    }
    
    /**
	 * Returns the creamy color for an hex value, 
	 * or CRM_UI_COLOR_DEFAULT_NAME if the hex code doesn't translate 
	 */
    public function creamyColorForHexValue($color) {
		$colors = $this->creamyColors();
		foreach ($colors as $creamy => $hex) { if ($hex == $color) return $creamy; }
		return CRM_UI_COLOR_DEFAULT_NAME;
    }
    
	/**
	 * Returns a random UI style to use for a notification, button, background element or such.
	 */
	public function getRandomUIStyle() {
		$number = rand(1,5);
		if ($number == 1) return CRM_UI_STYLE_INFO;
		else if ($number == 2) return CRM_UI_STYLE_DANGER;
		else if ($number == 3) return CRM_UI_STYLE_WARNING;
		else if ($number == 4) return CRM_UI_STYLE_SUCCESS;
		else return CRM_UI_STYLE_DEFAULT;
	}

    /** Messages */
    
    public function dismissableAlertWithMessage($message, $success, $includeResultData = false) {
	    $icon = $success ? "check" : "ban";
	    $color = $success ? "success" : "danger";
	    $title = $success ? $this->lh->translationFor("success") : $this->lh->translationFor("error");
	    $plusData = $includeResultData ? "'+ data+'" : "";
	    return '<div class="alert alert-dismissable alert-'.$color.'"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><h4><i class="fa fa-'.$icon.'"></i> '.$title.'</h4><p>'.$message.' '.$plusData.'</p></div>';
    }
    
    public function emptyMessageDivWithTag($tagname) {
	    return '<div  id="'.$tagname.'" name="'.$tagname.'" style="display:none"></div>';
    } 
    
    /**
	 * Generates a generic callout message with the given title, message and style.
	 * @param title String the title of the callout message.
	 * @param message String the message to show.
	 * @param style String a string containing the style (danger, success, primary...) or NULL if no style.
	 */
	public function calloutMessageWithTitle($title, $message, $style = NULL) {
		$styleCode = empty($style) ? "" : "callout-$style";
		return "<div class=\"callout $styleCode\"><h4>$title</h4><p>$message</p></div>";	
	}
    
	/**
	 * Generates a generic message HTML box, with the given message.
	 * @param message String the message to show.
	 */
	public function calloutInfoMessage($message) { 
		return $this->calloutMessageWithTitle($this->lh->translationFor("message"), $message, "info"); 
	}

	/**
	 * Generates a warning message HTML box, with the given message.
	 * @param message String the message to show.
	 */
	public function calloutWarningMessage($message) { 
		return $this->calloutMessageWithTitle($this->lh->translationFor("warning"), $message, "warning");
	}

	/**
	 * Generates a error message HTML box, with the given message.
	 * @param message String the message to show.
	 */
	public function calloutErrorMessage($message) {
		return $this->calloutMessageWithTitle($this->lh->translationFor("error"), $message, "danger");
	}
	
	/**
	 * Generates a error modal message HTML dialog, with the given message.
	 * @param message String the message to show.
	 */
	public function modalErrorMessage($message, $header) {
		$result = '<div class="modal-dialog"><div class="modal-content"><div class="modal-header">
		                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		                <h4 class="modal-title"><i class="fa fa-envelope-o"></i> '.$header.'</h4>
		            </div><div class="modal-body">';
		$result = $result.$this->calloutErrorMessage($message);
		$result = $result.'</div><div class="modal-footer clearfix"><button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> '.
		$this->lh->translationFor("exit").'</button></div></div></div>';
		return $result;
	}
	
	/** Forms */
	
	public function formWithContent($id, $content, $submit_text = null, $submitStyle = CRM_UI_STYLE_DEFAULT, $messagetag = CRM_UI_DEFAULT_RESULT_MESSAGE_TAG, $action = "") {
		if (empty($submit_text)) { $submit_text = $this->lh->translationFor("send"); }
		$button = '<button type="submit" class="btn btn-'.$submitStyle.'">'.$submit_text.'</button>';
		return $this->formWithCustomFooterButtons($id, $content, $button, $messagetag, $action);
	}
	
	public function formForCustomHook($id, $modulename, $hookname, $content, $submit_text = null, $messagetag = CRM_UI_DEFAULT_RESULT_MESSAGE_TAG, $action = "") {
		$hiddenFields = $this->hiddenFormField("module_name", $modulename).$this->hiddenFormField("hook_name", $hookname);
		return $this->formWithContent($id, $hiddenFields.$content, $submit_text, CRM_UI_STYLE_DEFAULT, $messagetag, $action);
	}
	
	public function modalFormStructure($modalid, $formid, $title, $subtitle, $body, $footer, $icon = null, $messagetag = CRM_UI_DEFAULT_RESULT_MESSAGE_TAG) {
		$iconCode = empty($icon) ? '' : '<i class="fa fa-'.$icon.'"></i> ';
		$subtitleCode = empty($subtitle) ? '' : '<p>'.$subtitle.'</p>';
		
		return '<div class="modal fade" id="'.$modalid.'" name="'.$modalid.'" tabindex="-1" role="dialog" aria-hidden="true">
	        	<div class="modal-dialog"><div class="modal-content">
	                <div class="modal-header">
	                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	                    <h4 class="modal-title">'.$iconCode.$title.'</h4>
	                    '.$subtitleCode.'
	                </div>
	                <form action="" method="post" name="'.$formid.'" id="'.$formid.'">
	                    <div class="modal-body">
	                        '.$body.'
	                        <div id="'.$messagetag.'" style="display: none;"></div>
	                    </div>
	                    <div class="modal-footer clearfix">
							'.$footer.'
	                    </div>
	                </form>
				</div></div>
				</div>';
	}
	
	public function formWithCustomFooterButtons($id, $content, $footer, $messagetag = CRM_UI_DEFAULT_RESULT_MESSAGE_TAG, $action = "") {
		return '<form role="form" id="'.$id.'" name="'.$id.'" method="post" action="'.$action.'" enctype="multipart/form-data">
            <div class="box-body">
            	'.$content.'
            </div>
            <div class="box-footer" id="form-footer">
            	'.$this->emptyMessageDivWithTag($messagetag).'
				'.$footer.'
            </div>
        </form>';
	}
	
    public function checkboxInputWithLabel($label, $id, $name, $enabled) {
	    return '<div class="checkbox"><label for="'.$id.'">
	    <input type="checkbox" id="'.$id.'" name="'.$name.'"'.($enabled ? "checked": "").'/> '.$label.'</label></div>';
    }
    
    public function radioButtonInputGroup($name, $values, $labels, $ids = null, $checkedIndex = 0) {
	    $result = '<div class="form-group">';
	    $i = 0;
	    foreach ($values as $value) {
		    $idCode = ((isset($ids)) && (isset($ids[$i]))) ? 'id="'.$ids[$i].'"' : '';
		    $checkedCode = ($checkedIndex == $i) ? "checked" : "";
		    $result .= '<div class="radio"><label><input type="radio" name="'.$name.'" '.$idCode.' value="'.$value.'" '.$checkedCode.'>'.$labels[$i].'</label></div>';
			$i++;
	    }
	    $result .= '</div>';
	    return $result;
    }
    
    public function singleFormGroupWithSelect($label, $id, $name, $options, $selectedOption, $needsTranslation = false) {
	    $labelCode = empty($label) ? "" : '<label>'.$label.'</label>';
	    $selectCode = '<div class="form-group">'.$labelCode.'<select id="'.$id.'" name="'.$name.'" class="form-control">';
	    foreach ($options as $key => $value) {
		    $isSelected = ($selectedOption == $key) ? " selected" : "";
		    $selectCode .= '<option value="'.$key.'" '.$isSelected.'>'.($needsTranslation ? $this->lh->translationFor($value) : $value).'</option>';
	    }
		$selectCode .= '</select></div>';
		return $selectCode;
    }
    
    public function singleFormInputElement($id, $name, $type, $placeholder = "", $value = null, $icon = null, $required = false, $disabled = false) {
	    $iconCode = empty($icon) ? '' : '<span class="input-group-addon"><i class="fa fa-'.$icon.'"></i></span>';
	    $valueCode = empty($value) ? '' : ' value="'.$value.'"';
	    $requiredCode = $required ? "required" : "";
	    $disabledCode = $disabled ? "disabled" : "";
	    return $iconCode.'<input name="'.$name.'" id="'.$id.'" type="'.$type.'" class="form-control '.$requiredCode.'" placeholder="'.$placeholder.'"'.$valueCode.' '.$disabledCode.'>';
    }
    
    public function singleFormTextareaElement($id, $name, $placeholder = "", $text = "", $icon = null) {
	    $iconCode = empty($icon) ? '' : '<span class="input-group-addon"><i class="fa fa-'.$icon.'"></i></span>';
	    return $iconCode.'<textarea id="'.$id.'" name="'.$name.'" placeholder="'.$placeholder.'" class="form-control">'.$text.'</textarea>';
    }
    
    public function singleFormGroupWithFileUpload($id, $name, $currentFilePreview, $label, $bottomText) {
	    $labelCode = isset($label) ? '<label for="'.$id.'">'.$label.'</label>' : '';
	    return '<div class="form-group">'.$labelCode.'<br>'.$currentFilePreview.'<br><input type="file" id="'.$id.'" name="'.$id.'">
	                <p class="help-block">'.$bottomText.'</p></div>';
    }

	public function maskedDateInputElement($id, $name, $dateFormat = "dd/mm/yyyy", $value = null, $icon = null, $includeJS = false) {
		// date value
		$dateAsDMY = "";
        if (isset($value)) { 
            $time = strtotime($value);
            $phpFormat = str_replace("dd", "d", $dateFormat);
            $phpFormat = str_replace("mm", "m", $phpFormat);
            $phpFormat = str_replace("yyyy", "Y", $phpFormat);
            $dateAsDMY = date($phpFormat, $time); 
        }
        // icon and label
		$iconCode = empty($icon) ? '' : '<span class="input-group-addon"><i class="fa fa-'.$icon.'"></i></span>';

		// bild html code
		$htmlCode = '<input name="'.$name.'" id="'.$id.'" type="text" class="form-control" data-inputmask="\'alias\': \''.$dateFormat.'\'" data-mask value="'.$dateAsDMY.'" placeholder="'.$dateFormat.'"/>';
		// build JS code to turn an input text into a dateformat.
		$jsCode = "";
		if ($includeJS === true) {
			$jsCode = '<script src="js/plugins/input-mask/jquery.inputmask.js" type="text/javascript"></script>
		    <script src="js/plugins/input-mask/jquery.inputmask.date.extensions.js" type="text/javascript"></script>
		    <script src="js/plugins/input-mask/jquery.inputmask.extensions.js" type="text/javascript"></script>';
		} 
		$jsCode .= $this->wrapOnDocumentReadyJS('$("#'.$id.'").inputmask("'.$dateFormat.'", {"placeholder": "'.$dateFormat.'"});');
				
		return $iconCode.$htmlCode."\n".$jsCode;
	}

	public function hiddenFormField($id, $value = "") {
		return '<input type="hidden" id="'.$id.'" name="'.$id.'" value="'.$value.'">';
	}

	public function singleInputGroupWithContent($content) {
		return '<div class="input-group">'.$content.'</div>';
	}
	
	public function singleFormGroupWrapper($content, $label = null) {
		$labelCode = isset($label) ? '<label>'.$label.'</label>' : '';
		return '<div class="form-group">'.$labelCode.$content.'</div>';
	}

    public function singleFormGroupWithInputGroup($inputGroup, $label = null) {
	    $labelCode = isset($label) ? "<label>$label</label>" : "";
	    return '<div class="form-group">'.$labelCode.'<div class="input-group">'.$inputGroup.'</div></div>';
    }
    
    public function modalDismissButton($id, $message = null, $position = "right", $dismiss = true) {
	    if (empty($message)) { $message = $this->lh->translationFor("cancel"); }
	    $dismissCode = $dismiss ? 'data-dismiss="modal"' : '';
	    return '<button type="button" class="btn btn-danger pull-'.$position.'" '.$dismissCode.' id="'.$id.'">
	    		<i class="fa fa-times"></i> '.$message.'</button>';
    }
    
    public function modalSubmitButton($id, $message = null, $position = "left", $dismiss = false) {
	    if (empty($message)) { $message = $this->lh->translationFor("accept"); }
	    $dismissCode = $dismiss ? 'data-dismiss="modal"' : '';
	    return '<button type="submit" class="btn btn-primary pull-'.$position.'" '.$dismissCode.' id="'.$id.'"><i class="fa fa-check-circle"></i> '.$message.'</button>';
    }
    
    /** Global buttons */
    
    public function buttonWithLink($id, $link, $title, $type = "button", $icon = null, $style = CRM_UI_STYLE_DEFAULT, $additionalClasses = null) {
	    $iconCode = isset($icon) ? '<i class="fa fa-'.$icon.'"></i>' : '';
	    return '<button type="'.$type.'" class="btn btn-'.$style.' '.$additionalClasses.'" id="'.$id.'" href="'.$link.'">'.$iconCode.' '.$title.'</button>';
    }
    
    /** Task list buttons */
    
    /**
	 * Creates a hover action button to be put in a a task list, todo-list or similar.
	 * If modaltarget is specified, the button will open a custom dialog with the given id.
	 * @param String $classname name for the class.
	 * @param Array $parameters An associative array of parameters to include (i.e: "user_id" => "1231").
	 * @param String icon the font-awesome identifier for the icon.
	 * @param String modaltarget if specified, id of the destination modal dialog to open.
	 * @param String $linkClasses Additional classes for the HTML a link
	 * @param String $iconClasses Additional classes for the font awesome icon i.
	 */
    public function hoverActionButton($classname, $icon, $hrefValue = "", $modaltarget = null, $linkClasses = "", $iconClasses = "", $otherParameters = null) {
	    // build parameters and additional code
	    $paramCode = "";
	    if (isset($otherParameters)) foreach ($otherParameters as $key => $value) { $paramCode = "$key=\"$value\" "; }
	    $modalCode = isset($modaltarget) ? "data-toggle=\"modal\" data-target=\"#$modaltarget\"" : "";
	    // return the button action link
	    return '<a class="'.$classname.' '.$linkClasses.'" href="'.$hrefValue.'" '.$paramCode.' '.$modalCode.'>
	    		<i class="fa fa-'.$icon.' '.$iconClasses.'"></i></a>';
    }
    
    /** Pop-Up Action buttons */
    
    public function popupActionButton($title, $options, $style = CRM_UI_STYLE_DEFAULT) {
	    // style code
	    if (is_string($style)) { $styleCode = "btn btn-$style"; }
	    else if (is_array($style)) {
		    $styleCode = "btn";
		    foreach ($style as $class) { $styleCode .= " btn-$class"; }
	    } else { $styleCode = "btn btn-default"; }
	    // popup prefix code
	    $popup = '<div class="btn-group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.$title.' 
	                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height: 34px;">
						<span class="caret"></span>
						<span class="sr-only">Toggle Dropdown</span>
	                </button><ul class="dropdown-menu" role="menu">';
	    // options
	    foreach ($options as $option) { $popup .= $option; }
	    // popup suffix code.
	    $popup .= '</ul></div>';
	    return $popup;
    }
    
    public function actionForPopupButtonWithClass($class, $text, $parameter_value, $parameter_name = "href") {
	    return '<li><a class="'.$class.'" '.$parameter_name.'="'.$parameter_value.'">'.$text.'</a></li>';
    }
    
    public function actionForPopupButtonWithLink($url, $text, $class = null, $parameter_value = null, $parameter_name = null) {
	    // do we need to specify a class?
	    if (isset($class)) { $classCode = 'class="'.$class.'"'; } else { $classCode = ""; }
	    // do we have an optional parameter?
	    if (isset($parameter_value) && isset($parameter_name)) { $parameterCode = $parameter_name.'="'.$parameter_value.'"'; }
	    else { $parameterCode = ""; }
	    return '<li><a '.$classCode.' href="'.$url.'" '.$parameterCode.'>'.$text.'</a></li>';
    }
    
    public function actionForPopupButtonWithOnClickCode($text, $jsFunction, $parameters = null, $class = null) {
	    // do we need to specify a class?
	    if (isset($class)) { $classCode = 'class="'.$class.'"'; } else { $classCode = ""; }
	    // do we have an parameters?
	    if (isset($parameters) && is_array($parameters)) { 
		    $parameterCode = "";
		    foreach ($parameters as $parameter) { $parameterCode .= "'$parameter',"; }
		    $parameterCode = rtrim($parameterCode, ",");
		} else { $parameterCode = ""; }
	    return '<li><a href="#" '.$classCode.' onclick="'.$jsFunction.'('.$parameterCode.');">'.$text.'</a></li>';
    } 
    
    public function separatorForPopupButton() {
	    return '<li class="divider"></li>';
    }
    
    public function simpleLinkButton($id, $title, $url, $icon = null, $style = CRM_UI_STYLE_DEFAULT, $additionalClasses = null) {
	    // style code.
	    $styleCode = "";
	    if (!empty($style)) {
		    if (is_array($style)) { foreach ($style as $st) { $styleCode .= "btn-$style "; } }
		    else if (is_string($style)) { $styleCode = "btn-$style"; }
	    }
	    return '<a id="'.$id.'" class="btn '.$styleCode.'" href="'.$url.'">'.$title.'</a>';
    }
    
    /** Images */
    
    public function imageWithData($src, $class, $extraParams, $alt = "") {
	    $paramsCode = "";
	    if (is_array($extraParams) && count($extraParams) > 0) {
		    foreach ($extraParams as $key => $value) { $paramsCode .= " $key=\"$value\""; }
     }
	    return "<img src=\"$src\" class=\"$class\" $paramsCode alt=\"$alt\"/>";
    }
    
    /** Paragraphs */
    
    public function simpleParagraphWithText($text, $additionalClasses = "") {
	    return "<p class='$additionalClasses'>$text</p>";
    }
    
    /** Javascript HTML code generation */
    
    public function wrapOnDocumentReadyJS($content) {
	    return '<script type="text/javascript">$(document).ready(function() {
		    '.$content.'
		    });</script>';
    }
    
    public function formPostJS($formid, $phpfile, $successJS, $failureJS, $preambleJS = "", $successResult=CRM_DEFAULT_SUCCESS_RESPONSE) {
	    return $this->wrapOnDocumentReadyJS('$("#'.$formid.'").validate({
			submitHandler: function(e) {
				'.$preambleJS.'
				$.post("'.$phpfile.'", $("#'.$formid.'").serialize(), function(data) {
					if (data == "'.$successResult.'") {
						'.$successJS.'
					} else {
						'.$failureJS.'
					}
				}).fail(function(){ 
					'.$failureJS.'
  				});
			}
		 });');
    }
    
    public function fadingInMessageJS($message, $tagname = CRM_UI_DEFAULT_RESULT_MESSAGE_TAG) {
	    return '$("#'.$tagname.'").html(\''.$message.'\');
				$("#'.$tagname.'").fadeIn();';
    }
    
    public function fadingOutMessageJS($animated = false, $tagname = CRM_UI_DEFAULT_RESULT_MESSAGE_TAG) {
	    if ($animated) { return '$("#'.$tagname.'").fadeOut();'; }
	    else { return '$("#'.$tagname.'").hide();'; }
    }
    
    public function reloadLocationJS() { return 'location.reload();'; }
    
    public function newReloadLocationJS($url) { return 'window.location.href = window.location.href + "?'.$url.'";'; }
    
    public function newLocationJS($url) { return 'window.location.href = "'.$url.'";'; }
    
    public function showRetrievedErrorMessageAlertJS() { return 'alert(data);'; }
    
    public function showCustomErrorMessageAlertJS($msg) { return 'alert("'.$msg.'");'; }
    
    public function clickableClassActionJS($className, $parameter, $container, $phpfile, $successJS, $failureJS, $confirmation = false, $successResult = CRM_DEFAULT_SUCCESS_RESPONSE, $additionalParameters = null, $parentContainer = null) {
	    // build the confirmation code if needed.
	    $confirmPrefix = $confirmation ? 'var r = confirm("'.$this->lh->translationFor("are_you_sure").'"); if (r == true) {' : '';
	    $confirmSuffix = $confirmation ? '}' : '';
	    $paramCode = empty($parentContainer) ? 'var paramValue = $(this).attr("'.$container.'");' : 
	    			'var ele = $(this).parents("'.$parentContainer.'").first(); var paramValue = ele.attr("'.$container.'");';
	    // additional parameters
	    $additionalString = "";
	    if (is_array($additionalParameters) && count($additionalParameters) > 0) {
		    foreach ($additionalParameters as $apKey => $apValue) { $additionalString .= ", \"$apKey\": $apValue ";  }
	    }
	    
	    // return the JS code
	    return $this->wrapOnDocumentReadyJS(
	    '$(".'.$className.'").click(function(e) {
			e.preventDefault();
			'.$confirmPrefix.'
				'.$paramCode.'
				$.post("'.$phpfile.'", { "'.$parameter.'": paramValue '.$additionalString.'} ,function(data){
					if (data == "'.$successResult.'") { '.$successJS.' }
					else { '.$failureJS.' }
				}).fail(function(){ 
					'.$failureJS.'
  				});
			'.$confirmSuffix.'
		 });');
    }
    
    /**
	 * Creates a javascript javascript "reload with message" code, that will
	 * reload the current page passing a custom message tag. 
	 */
	public function reloadWithMessageFunctionJS($messageVarName = "message") {
		return 'function reloadWithMessage(message) {
		    var url = window.location.href;
			if (url.indexOf("?") > -1) { url += "&'.$messageVarName.'="+message;
			} else{ url += "?'.$messageVarName.'="+message; }
			window.location.href = url; 
		}'."\n";
	}
	
	/**
	 * Generates an javascript calling to ReloadWithMessage function, generated
	 * by reloadWithMessageFunctionJS() to reload the custom page sending a
	 * custom message parameter.
	 * Note: Message is not quoted inside call, you must do it yourself.
	 */
	public function reloadWithMessageCallJS($message) { return 'reloadWithMessage('.$message.');'; }
    
    /**
	 * This function generates the javascript for the messages mailbox actions.
	 * You must pass a class name for the button that triggers the action, a php
	 * url for the Ajax request, a completion resultJS code and, optionally, if
	 * you want to discern the failure from the success, a failureJS.
	 * The function does the following assumptions:
	 * - The result message div for showing the results has id messages-message-box
	 * - The parameters to send to any function invoked by the php ajax script are
	 *   messageids (containing a comma separated string array of message ids to act upon)
	 *   and folder (containing the current folder identifier).
	 * @param String $classname the name of the mailbox action class.
	 * @param String $url the URL for the PHP that will receive the Ajax POST request.
	 * @param String $resultJS The default javascript to execute (or the successful one if
	 *        failureJS is also specified).
	 * @param String $failureJS The failure javascript to execute, if left null, only the
	 *        resultJS will be applied, without taking into account the result data.
	 * @param Array $customParameters Associative array with custom parameters to add to the request.
	 * @param Bool $confirmation If true, confirmation will be asked before applying the action.
	 * @param Bool $checkSelectedMessages if true, no action will be taken if no messages are selected.
	 */ 
	public function mailboxAction($classname, $url, $resultJS, $failureJS = null, $customParameters = null, $confirmation = false, 
								  $checkSelectedMessages = true) {
		// check selected messages count?
		$checkSelectedMessagesCode = $checkSelectedMessages ? 'if (selectedMessages.length < 1) { return; }' : '';
		// needs confirmation?
		$confirmPrefix = $confirmation ? 'var r = confirm("'.$this->lh->translationFor("are_you_sure").'"); if (r == true) {' : '';
		$confirmSuffix = $confirmation ? '}' : '';
		// success+failure or just result ?
		if (empty($failureJS)) { $content = $resultJS; } 
		else { $content = 'if (data == "'.CRM_DEFAULT_SUCCESS_RESPONSE.'") { '.$resultJS.' } else { '.$failureJS.' }'; }
		// custom parameters
		$paramCode = "";
		if (is_array($customParameters) && count($customParameters)) {
			foreach ($customParameters as $key => $value) { $paramCode .= ", \"$key\": \"$value\" "; }
		}
		
		$result = '$(".'.$classname.'").click(function (e) {
				    '.$checkSelectedMessagesCode.'
					e.preventDefault();
					'.$confirmPrefix.'
					$("#messages-message-box").hide();
					$.post("'.$url.'", { "messageids": selectedMessages, "folder": folder '.$paramCode.'}, function(data) {
						'.$content.'
					});
					'.$confirmSuffix.'
			    });';
		return $result;
	}
    
    // Assignment to variables from one place to a form destination.
    
    private function javascriptVarFromName($name, $prefix = "var") {
	    $result = str_replace("-", "", $prefix.$name);
	    $result = str_replace("_", "", $result);
	    return trim($result);
    }
    
    public function selfValueAssignmentJS($attr, $destination) {
	    $varName = $this->javascriptVarFromName($destination);
	    return 'var '.$varName.' = $(this).attr("'.$attr.'"); 
	    		$("#'.$destination.'").val('.$varName.');';
    }
    
    public function directValueAssignmentJS($source, $attr, $destination) {
	    $varName = $this->javascriptVarFromName($destination);
	    return 'var '.$varName.' = $("#'.$source.'").attr("'.$attr.'"); 
	    		$("#'.$destination.'").val('.$varName.');';
    }
    
    public function classValueFromParentAssignmentJS($classname, $parentContainer, $destination) {
	    $elementName = $this->javascriptVarFromName($destination, "ele");
	    $varName = $this->javascriptVarFromName($destination);
	    return 'var '.$elementName.' = $(this).parents("'.$parentContainer.'").first();
				var '.$varName.' = $(".'.$classname.'", '.$elementName.');
				$("#'.$destination.'").val('.$varName.'.text().trim());';
    }
        
    public function clickableFillValuesActionJS($classname, $assignments) {
	    $js = '$(".'.$classname.'").click(function(e) {'."\n".'e.preventDefault();';
		foreach ($assignments as $assignment) { $js .= "\n".$assignment; }
		$js .= '});'."\n";
		return $this->wrapOnDocumentReadyJS($js);
    }
    
    /** Hooks */
    
    /**
	 * Returns the hooks for the dashboard.
	 */
    public function hooksForDashboard() {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_DASHBOARD, null, CRM_MODULE_MERGING_STRATEGY_APPEND);
    }
    
	/**
	 * Generates the footer for the customer list screen, by invoking the different modules
	 * CRM_MODULE_HOOK_CUSTOMER_LIST_FOOTER hook.
	 */
	public function getCustomerListFooter($customer_type) {
		$mh = \creamy\ModuleHandler::getInstance();
		$footer = $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_CUSTOMER_LIST_FOOTER, array(CRM_MODULE_HOOK_PARAMETER_CUSTOMER_LIST_TYPE => $customer_type), CRM_MODULE_MERGING_STRATEGY_APPEND);
		$js = $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_CUSTOMER_LIST_ACTION, array(CRM_MODULE_HOOK_PARAMETER_CUSTOMER_LIST_TYPE => $customer_type), CRM_MODULE_MERGING_STRATEGY_APPEND);
		return $footer.$js;
	}	 

	/**
	 * Generates the footer for the messages list screen, by invoking the different modules
	 * CRM_MODULE_HOOK_MESSAGE_LIST_FOOTER & CRM_MODULE_HOOK_MESSAGE_LIST_ACTION hooks.
	 */
	public function getMessagesListActionJS($folder) {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_MESSAGE_LIST_ACTION, array(CRM_MODULE_HOOK_PARAMETER_MESSAGES_FOLDER => $folder), CRM_MODULE_MERGING_STRATEGY_APPEND);
	}	 
	 
	public function getComposeMessageFooter() {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_MESSAGE_COMPOSE_FOOTER, null, CRM_MODULE_MERGING_STRATEGY_APPEND);
	}
	 
	public function getComposeMessageActionJS() {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_MESSAGE_COMPOSE_ACTION, null, CRM_MODULE_MERGING_STRATEGY_APPEND);
	}
	 
	public function getMessageDetailFooter($messageid, $folder) {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_MESSAGE_DETAIL_FOOTER, array(CRM_MODULE_HOOK_PARAMETER_MESSAGE_ID => $messageid, CRM_MODULE_HOOK_PARAMETER_MESSAGES_FOLDER => $folder), CRM_MODULE_MERGING_STRATEGY_APPEND);
	}
	 
	public function getMessageDetailActionJS($messageid, $folder) {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_MESSAGE_DETAIL_ACTION, array(CRM_MODULE_HOOK_PARAMETER_MESSAGE_ID => $messageid, CRM_MODULE_HOOK_PARAMETER_MESSAGES_FOLDER => $folder), CRM_MODULE_MERGING_STRATEGY_APPEND);
	}

    /**
	 * Returns the hooks for the customer detail/edition screen.
	 */
	public function customerDetailModuleHooks($customerid, $customerType) {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_CUSTOMER_DETAIL, array(CRM_MODULE_HOOK_PARAMETER_CUSTOMER_LIST_ID => $customerid, CRM_MODULE_HOOK_PARAMETER_CUSTOMER_LIST_TYPE => $customerType),
		 CRM_MODULE_MERGING_STRATEGY_APPEND);
	}
    
    /* Administration & user management */
    
    /** Returns the HTML form for modyfing the system settings */
    public function getGeneralSettingsForm() {
		// current settings values
	    $baseURL = $this->db->getSettingValueForKey(CRM_SETTING_CRM_BASE_URL);
	    $tz = $this->db->getSettingValueForKey(CRM_SETTING_TIMEZONE);
	    $lo = $this->db->getSettingValueForKey(CRM_SETTING_LOCALE);
	    $ct = $this->db->getSettingValueForKey(CRM_SETTING_THEME);
	    if (empty($ct)) { $ct = CRM_SETTING_DEFAULT_THEME; }
	    $ce = $this->db->getSettingValueForKeyAsBooleanValue(CRM_SETTING_CONFIRMATION_EMAIL);
	    $cv = $this->db->getSettingValueForKeyAsBooleanValue(CRM_SETTING_EVENTS_EMAIL);
	    $cn = $this->db->getSettingValueForKey(CRM_SETTING_COMPANY_NAME);
	    $cl = $this->db->getSettingValueForKey(CRM_SETTING_COMPANY_LOGO);
	    if (isset($cl)) { $cl = $this->imageWithData($cl, "", array("style" => "max-width: 350px;")); }
	    $tOpts = array("black" => "Black", "blue" => "Blue", "green" => "Green", "minimalist" => "Minimalist", "purple" => "Purple", "red" => "Red", "yellow" => "Yellow");
	    
	    // translation.
	    $em_text = $this->lh->translationFor("require_confirmation_email");
	    $ev_text = $this->lh->translationFor("send_event_email");
	    $es_text = $this->lh->translationFor("choose_theme");
	    $tz_text = $this->lh->translationFor("detected_timezone");
	    $lo_text = $this->lh->translationFor("choose_language");
	    $ok_text = $this->lh->translationFor("settings_successfully_changed");
	    $bu_text = $this->lh->translationFor("base_url");
	    $cn_text = $this->lh->translationFor("company_name");
	    $cl_text = $this->lh->translationFor("custom_company_logo");
	    
	    // form
	    $form = '<form role="form" id="adminsettings" name="adminsettings" class="form" enctype="multipart/form-data">
			  '.$this->singleFormGroupWithInputGroup($this->singleFormInputElement("base_url", "base_url", "text", $bu_text, $baseURL, "globe"), $bu_text).'
	    	  <label>'.$this->lh->translationFor("messages").'</label>
			  '.$this->checkboxInputWithLabel($em_text, "confirmationEmail", "confirmationEmail", $ce).'
			  '.$this->checkboxInputWithLabel($ev_text, "eventEmail", "eventEmail", $cv).'
			  '.$this->singleFormGroupWithInputGroup($this->singleFormInputElement("company_name", "company_name", "text", $cn_text, $cn, "building-o"), $cn_text).'
			  '.$this->singleFormGroupWithFileUpload("company_logo", "company_logo", $cl, $cl_text, null).'
			  '.$this->singleFormGroupWithSelect($es_text, "theme", "theme", $tOpts, $ct, false).'
			  '.$this->singleFormGroupWithSelect($tz_text, "timezone", "timezone", \creamy\CRMUtils::getTimezonesAsArray(), $tz).'
			  '.$this->singleFormGroupWithSelect($lo_text, "locale", "locale", \creamy\LanguageHandler::getAvailableLanguages(), $lo).'
			  <div class="box-footer">
			  '.$this->emptyMessageDivWithTag(CRM_UI_DEFAULT_RESULT_MESSAGE_TAG).'
			  <button type="submit" class="btn btn-primary">'.$this->lh->translationFor("modify").'</button></div></form>';
		
		return $form;
    }
    
    /** Returns the HTML code for the input field associated with a module setting data type */
    public function inputFieldForModuleSettingOfType($setting, $type, $currentValue) {
	    if (is_array($type)) { // select type
		    return $this->singleFormGroupWithSelect($this->lh->translationFor($setting), $setting, $setting, $type, $currentValue);
	    } else { // single input type: text, number, bool, date...
		    switch ($type) {
			    case CRM_SETTING_TYPE_STRING:
				    return $this->singleFormGroupWithInputGroup($this->singleFormInputElement($setting, $setting, "text", $this->lh->translationFor($setting), $currentValue), $this->lh->translationFor($setting));
					break;
				case CRM_SETTING_TYPE_INT:
				case CRM_SETTING_TYPE_FLOAT:
				    return $this->singleFormGroupWithInputGroup($this->singleFormInputElement($setting, $setting, "number", $this->lh->translationFor($setting), $currentValue), $this->lh->translationFor($setting));
					break;
				case CRM_SETTING_TYPE_BOOL:
					return $this->singleFormGroupWithInputGroup($this->checkboxInputWithLabel($this->lh->translationFor($setting), $setting, $setting, (bool) $currentValue));
					break;
				case CRM_SETTING_TYPE_DATE:
					$dateFormat = $this->lh->getDateFormatForCurrentLocale();
				    return $this->singleFormGroupWithInputGroup($this->maskedDateInputElement($setting, $setting, $dateFormat, $currentValue), $this->lh->translationFor($setting));
					break;
		    }
	    }
    }
    
    
    /**
	 * Generates the HTML code for a select with the human friendly descriptive names for the user roles.
	 * @return String the HTML code for a select with the human friendly descriptive names for the user roles.
	 */
	public function getUserRolesAsFormSelect($selectedOption = CRM_DEFAULTS_USER_ROLE_MANAGER) {
		$selectedAdmin = $selectedOption == CRM_DEFAULTS_USER_ROLE_ADMIN ? " selected" : "";
		$selectedManager = $selectedOption == CRM_DEFAULTS_USER_ROLE_MANAGER ? " selected" : "";
		$selectedWriter = $selectedOption == CRM_DEFAULTS_USER_ROLE_WRITER ? " selected" : "";
		$selectedReader = $selectedOption == CRM_DEFAULTS_USER_ROLE_READER ? " selected" : "";
		$selectedGuest = $selectedOption == CRM_DEFAULTS_USER_ROLE_GUEST ? " selected" : "";
		
		$adminName = $this->lh->translationFor($this->getRoleNameForRole(CRM_DEFAULTS_USER_ROLE_ADMIN));
		$managerName = $this->lh->translationFor($this->getRoleNameForRole(CRM_DEFAULTS_USER_ROLE_MANAGER));
		$writerName = $this->lh->translationFor($this->getRoleNameForRole(CRM_DEFAULTS_USER_ROLE_WRITER));
		$readerName = $this->lh->translationFor($this->getRoleNameForRole(CRM_DEFAULTS_USER_ROLE_READER));
		$guestName = $this->lh->translationFor($this->getRoleNameForRole(CRM_DEFAULTS_USER_ROLE_GUEST));
		
		return '<select id="role" name="role">
				   <option value="'.CRM_DEFAULTS_USER_ROLE_ADMIN.'"'.$selectedAdmin.'>'.$adminName.'</option>
				   <option value="'.CRM_DEFAULTS_USER_ROLE_MANAGER.'"'.$selectedManager.'>'.$managerName.'</option>
				   <option value="'.CRM_DEFAULTS_USER_ROLE_WRITER.'"'.$selectedWriter.'>'.$writerName.'</option>
				   <option value="'.CRM_DEFAULTS_USER_ROLE_READER.'"'.$selectedReader.'>'.$readerName.'</option>
				   <option value="'.CRM_DEFAULTS_USER_ROLE_GUEST.'"'.$selectedGuest.'>'.$guestName.'</option>				   
			    </select>';
	}

    /**
     * Returns a HTML representation of the action associated with a user in the admin panel.
     * @param $userid Int the id of the user
     * @param $username String the name of the user
     * @param $status Int the status of the user (enabled=1, disabled=0)
     * @return String a HTML representation of the action associated with a user in the admin panel.
     */
	private function getUserActionMenuForUser($userid, $username, $status) {
		$textForStatus = $status == 1 ? $this->lh->translationFor("disable") : $this->lh->translationFor("enable");
		$actionForStatus = $status == 1 ? "deactivate-user-action" : "activate-user-action";
		return '<div class="btn-group">
	                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.$this->lh->translationFor("choose_action").' 
	                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height: 34px;">
						<span class="caret"></span>
						<span class="sr-only">Toggle Dropdown</span>
	                </button>
	                <ul class="dropdown-menu" role="menu">
	                    <li><a class="edit-action" href="'.$userid.'">'.$this->lh->translationFor("edit_data").'</a></li>
	                    <li><a class="change-password-action" href="'.$userid.'">'.$this->lh->translationFor("change_password").'</a></li>
	                    <li><a class="'.$actionForStatus.'" href="'.$userid.'">'.$textForStatus.'</a></li>
	                    <li class="divider"></li>
	                    <li><a class="delete-action" href="'.$userid.'">'.$this->lh->translationFor("delete_user").'</a></li>
	                </ul>
	            </div>';
	}

    /**
     * Returns a HTML Table representation containing all the user's in the system (only relevant data).
     * @return String a HTML Table representation of the data of all users in the system.
     */
	public function getAllUsersAsTable() {
       $users = $this->db->getAllUsers();
       // is null?
       if (is_null($users)) { // error getting contacts
	       return $this->calloutErrorMessage($this->lh->translationFor("unable_get_user_list"));
       } else if (empty($users)) { // no contacts found
	       return $this->calloutWarningMessage($this->lh->translationFor("no_users_in_list"));
       } else { 
	       // we have some users, show a table
	       $columns = array("id", "name", "email", "creation_date", "role", "status", "action");
	       $hideOnMedium = array("email", "creation_date", "role");
	       $hideOnLow = array("email", "creation_date", "role", "status");
		   $result = $this->generateTableHeaderWithItems($columns, "users", "table-bordered table-striped", true, false, $hideOnMedium, $hideOnLow);
	       
	       // iterate through all contacts
	       foreach ($users as $userData) {
	       	   $status = $userData["status"] == 1 ? $this->lh->translationFor("enabled") : $this->lh->translationFor("disabled");
	       	   $userRole = $this->lh->translationFor($this->getRoleNameForRole($userData["role"]));	
	       	   $action = $this->getUserActionMenuForUser($userData["id"], $userData["name"], $userData["status"]);       
		       $result = $result."<tr>
	                    <td>".$userData["id"]."</td>
	                    <td><a class=\"edit-action\" href=\"".$userData["id"]."\">".$userData["name"]."</a></td>
	                    <td class='hide-on-medium hide-on-low'>".$userData["email"]."</td>
	                    <td class='hide-on-medium hide-on-low'>".$userData["creation_date"]."</td>
	                    <td class='hide-on-medium hide-on-low'>".$userRole."</td>
	                    <td class='hide-on-low'>".$status."</td>
	                    <td>".$action."</td>
	                </tr>";
	       }
	       
	       // print suffix
	       $result .= $this->generateTableFooterWithItems($columns, true, false, $hideOnMedium, $hideOnLow);
	       return $result; 
       }
	}

	/**
	 * Retrieves the human friendly descriptive name for a role given its identifier number.
	 * @param $roleNumber Int number/identifier of the role.
	 * @return Human friendly descriptive name for the role.
	 */
	private function getRoleNameForRole($roleNumber) {
		switch ($roleNumber) {
			case CRM_DEFAULTS_USER_ROLE_ADMIN:
				return "administrator";
				break;
			case CRM_DEFAULTS_USER_ROLE_MANAGER:
				return "manager";
				break;
			case CRM_DEFAULTS_USER_ROLE_WRITER:
				return "writer";
				break;
			case CRM_DEFAULTS_USER_ROLE_READER:
				return "reader";
				break;
			case CRM_DEFAULTS_USER_ROLE_GUEST:
				return "guest";		
				break;
		}
	}
	
	/**
	 * Returns a warning message in case the setting for email confirmation on new user accounts is activated.
	 */
	public function getUserActivationEmailWarning() {
		$confirmationEmailSetting = $this->db->getSettingValueForKey(CRM_SETTING_CONFIRMATION_EMAIL);
		if (filter_var($confirmationEmailSetting, FILTER_VALIDATE_BOOLEAN)) {
			return '<p>'.$this->lh->translationFor("confirmation_email_enabled").'</p>';
		} else { return '<p>'.$this->lh->translationFor("confirmation_email_disabled").'</p>'; }
	}
	
	/**
	 * Generates the HTML with a unauthorized access. It must be included inside a <section> section.
	 */
	public function getUnauthotizedAccessMessage() {
		return $this->boxWithMessage($this->lh->translationFor("access_denied"), $this->lh->translationFor("you_dont_have_permission"), "lock", "danger");
	}
	
	/** Modules */
	
	public function getModulesAsList() {
		// get all modules.
		$mh = \creamy\ModuleHandler::getInstance();
		$allModules = $mh->listOfAllModules();
		
		// generate a table with all elements.
		$items = array("name", "version", "enabled", "action");
		$table = $this->generateTableHeaderWithItems($items, "moduleslist", "table-striped", true, false, array(), array("version", "action"));
		// fill table
		foreach ($allModules as $moduleClass => $moduleDefinition) {
			// module data
			if ($mh->moduleIsEnabled($moduleClass)) { // module is enabled.
				$status = "<i class='fa fa-check-square-o'></i>";
				$enabled = true;
			} else { // module is disabled.
				$status = "<i class='fa fa-times-circle-o'></i>";
				$enabled = false;
			}
			$moduleName = $moduleDefinition->getModuleName();
			$moduleVersion = $moduleDefinition->getModuleVersion();
			$moduleDescription = $moduleDefinition->getModuleDescription();
			// module action
			$moduleShortName = $moduleDefinition->getModuleShortName();
			$action = $this->getActionButtonForModule($moduleShortName, $enabled);
			// add module row
			$table .= "<tr><td><b>$moduleName</b><br/><div class='small hide-on-low'>$moduleDescription</div></td><td class='small hide-on-low'>$moduleVersion</td><td class='small hide-on-low'>$status</td><td>$action</td></tr>";
		}

		// close table
		$table .= $this->generateTableFooterWithItems($items, true, false, array(), array("version", "action"));
		
		// add javascript code.
		$enableJS = $this->clickableClassActionJS("enable_module", "module_name", "href", "./php/ModifyModule.php", $this->reloadLocationJS(), $this->showRetrievedErrorMessageAlertJS(), false, CRM_DEFAULT_SUCCESS_RESPONSE, array("enabled"=>"1"), null);
		$disableJS = $this->clickableClassActionJS("disable_module", "module_name", "href", "./php/ModifyModule.php", $this->reloadLocationJS(), $this->showRetrievedErrorMessageAlertJS(), false, CRM_DEFAULT_SUCCESS_RESPONSE, array("enabled"=>"0"), null);
		$deleteJS = $this->clickableClassActionJS("uninstall_module", "module_name", "href", "./php/DeleteModule.php", $this->reloadLocationJS(), $this->showRetrievedErrorMessageAlertJS(), true);
		$table .= $enableJS.$disableJS.$deleteJS;
		
		return $table;
	}
	
	public function getModuleHandlerLog() {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->getModuleHandlerLog();
	}
	
	private function getActionButtonForModule($moduleShortName, $enabled) {
		// build the options.
		$ed_option = $enabled ? $this->actionForPopupButtonWithClass("disable_module", $this->lh->translationFor("disable"), $moduleShortName) : $this->actionForPopupButtonWithClass("enable_module", $this->lh->translationFor("enable"), $moduleShortName);
		//$up_option = $this->actionForPopupButtonWithClass("update_module", $this->lh->translationFor("update"), $moduleShortName);
		$un_option = $this->actionForPopupButtonWithClass("uninstall_module", $this->lh->translationFor("uninstall"), $moduleShortName);
		$options = array($ed_option, $un_option);
		// build and return the popup action button.
		return $this->popupActionButton($this->lh->translationFor("choose_action"), $options);
	}

	/** Header */
	
	/**
	 * Returns the default creamy header for all pages.
	 */
	public function creamyHeader($user) {
		// module topbar elements
		$mh = \creamy\ModuleHandler::getInstance();
		$moduleTopbarElements = $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_TOPBAR, null, CRM_MODULE_MERGING_STRATEGY_APPEND);
		// header elements
		$logo = $this->creamyHeaderLogo();
		$name = $this->creamyHeaderName();
		// return header
		return '<header class="main-header">
	            <a href="./index.php" class="logo"><img src="'.$logo.'" width="auto" height="32"> '.$name.'</a>
	            <nav class="navbar navbar-static-top" role="navigation">
	                <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
	                    <span class="sr-only">Toggle navigation</span>
	                    <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
	                </a>
	                <div class="navbar-custom-menu">
	                    <ul class="nav navbar-nav">
	                    		'.$moduleTopbarElements.'
	                    		'.$this->getTopbarMessagesMenu($user).'  
		                    	'.$this->getTopbarNotificationsMenu($user).'
		                    	'.$this->getTopbarTasksMenu($user).'
		                    	'.$this->getTopbarUserMenu($user).'
	                    </ul>
	                </div>
	            </nav>
	        </header>';
	}
	
	/**
	 * Returns the creamy company custom logo. If no custom logo is defined, returns 
	 * the default white creamy logo.
	 * @return String a string containing the relative URL for the header logo.
	 */
	public function creamyHeaderLogo() {
		$customLogo = $this->db->getSettingValueForKey(CRM_SETTING_COMPANY_LOGO);
		return (!empty($customLogo) ? $customLogo : CRM_DEFAULT_HEADER_LOGO);
	}
	
	/**
	 * Returns the creamy name for the header. If a custom company name is defined, it
	 * returns it, otherwise, it returns "Creamy".
	 * @return String a string containing the company custom name (if set) or "Creamy".
	 */
	public function creamyHeaderName() {
		$customName = $this->db->getSettingValueForKey(CRM_SETTING_COMPANY_NAME);
		return (!empty($customName) ? $customName : "Creamy");
	}
	
	/**
	 * Returns the creamy body for a page including the theme.
	 * If no theme setting is found, it defaults to CRM_SETTING_DEFAULT_THEME
	 */
	public function creamyBody() {
		$theme = $this->db->getSettingValueForKey(CRM_SETTING_THEME);
		if (empty($theme)) { $theme = CRM_SETTING_DEFAULT_THEME; }
		return '<body class="skin-'.$theme.'">';
	}
	
	/**
	 * Returns the proper css style for the selected theme.
	 * If theme is not found, it defaults to CRM_SETTING_DEFAULT_THEME
	 */
	public function creamyThemeCSS() {
		$theme = $this->db->getSettingValueForKey(CRM_SETTING_THEME);
		if (empty($theme)) { $theme = CRM_SETTING_DEFAULT_THEME; }
		return '<link href="css/skins/skin-'.$theme.'.min.css" rel="stylesheet" type="text/css" />';
	}
	
	/**
	 * Returns the default creamy footer for all pages.
	 */
	public function creamyFooter() {
		$version = $this->db->getSettingValueForKey(CRM_SETTING_CRM_VERSION);
		if (empty($version)) { $version = "unknown"; }
		return '<footer class="main-footer"><div class="pull-right hidden-xs"><b>Version</b> '.$version.'</div><strong>Copyright &copy; 2014 <a href="http://digitalleaves.com">Digital Leaves</a> - <a href="http://woloweb.com">Woloweb</a>.</strong> All rights reserved.</footer>';
	}
 
 /**
  * Returns the CSS link for Snackbar.
  */
 public function creamySnackbarCSS() {
  $snackbarHTML  = '<!-- SnackBar -->' . "\n";
  $snackbarHTML .= '<link href="css/snackbar/snackbar.css" rel="stylesheet" type="text/css" />' . "\n";
		return $snackbarHTML;
 }
 
 /**
  * Returns the JS link for Snackbar.
  */
 public function creamySnackbarJS() {
  $snackbarHTML  = '<!-- SnackBar -->' . "\n";
  $snackbarHTML .= '<script src="js/plugins/snackbar/snackbar.js" type="text/javascript"></script>' . "\n";
		return $snackbarHTML;
 }
	
	/** Topbar Menu elements */

	/**
	 * Generates the HTML for the message notifications of a user as a dropdown list element to include in the top bar.
	 * @param $userid the id of the user.
	 */
	protected function getTopbarMessagesMenu($user) {
		if (!$user->userHasBasicPermission()) return '';

        $list = $this->db->getMessagesOfType($user->getUserId(), MESSAGES_GET_UNREAD_MESSAGES);
		$numMessages = count($list);
		
		$headerText = $this->lh->translationFor("you_have").' '.$numMessages.' '.$this->lh->translationFor("unread_messages");
		$result = $this->getTopbarMenuHeader("envelope-o", $numMessages, CRM_UI_TOPBAR_MENU_STYLE_COMPLEX, $headerText, null, CRM_UI_STYLE_SUCCESS, false);
        
        foreach ($list as $message) {
			if (empty($message["remote_user"])) $remoteuser = $this->lh->translationFor("unknown");
	        if (empty($message["remote_avatar"])) $remoteavatar = CRM_DEFAULTS_USER_AVATAR;
	        else {
		        $remoteuser = $message["remote_user"];
		        $remoteavatar = $message["remote_avatar"];
	        }
	        $result .= $this->getTopbarComplexElement($remoteuser, $message["message"], $message["date"], $remoteavatar, "messages.php");
        }
        $result .= $this->getTopbarMenuFooter($this->lh->translationFor("see_all_messages"), "messages.php");
        return $result;
	}
	
	/**
	 * Generates the HTML for the main info boxes of the dashboard.
	 */
	public function dashboardInfoBoxes($userid) {
		$boxes = "";
		$firstCustomerType = $this->db->getFirstCustomerGroupTableName();
		$columnSize = isset($firstCustomerType) ? 3 : 4;

		// new contacts
		$contactsUrl = "./customerslist.php?customer_type=clients_1&customer_name=".urlencode($this->lh->translationFor("contacts"));
		$boxes .= $this->infoBox($this->lh->translationFor("new_contacts"), $this->db->getNumberOfNewContacts(), $contactsUrl, "user-plus", "aqua", $columnSize);
		// new customers
		if (isset($firstCustomerType)) {
			$customersURL = "./customerslist.php?customer_type=".$firstCustomerType["table_name"]."&customer_name=".urlencode($firstCustomerType["description"]);
			$boxes .= $this->infoBox($this->lh->translationFor("new_customers"), $this->db->getNumberOfNewCustomers(), $customersURL, "users", "green",  $columnSize);
		}
		// notifications
		$numNotifications = intval($this->db->getNumberOfTodayNotifications($userid));
		$numEvents = intval($this->db->getNumberOfTodayEvents($userid));
		$num = $numNotifications + $numEvents;
		$boxes .= $this->infoBox($this->lh->translationFor("notifications"),$num , "notifications.php", "clock-o", "yellow", $columnSize);
		// events today // TODO: Change
		$boxes .= $this->infoBox($this->lh->translationFor("unfinished_tasks"), $this->db->getUnfinishedTasksNumber($userid), "tasks.php", "calendar", "red", $columnSize);

		return $boxes;
	}

	/**
	 * Generates the HTML for the alert notifications of a user as a dropdown list element to include in the top bar.
	 * @param $userid the id of the user.
	 */
	protected function getTopbarNotificationsMenu($user) {
		if (!$user->userHasBasicPermission()) return '';
		
		// get notifications number
		$notifications = $this->db->getTodayNotifications($user->getUserId());
		if (empty($notifications)) $notificationNum = 0;
		else $notificationNum = count($notifications);
		$eventsForToday = $this->db->getEventsForToday($user->getUserId());
		if (!empty($eventsForToday)) $notificationNum += count($eventsForToday);
		// build header
		$headerText = $this->lh->translationFor("you_have").' '.$notificationNum.' '.strtolower($this->lh->translationFor("notifications"));
		$result = $this->getTopbarMenuHeader("calendar", $notificationNum, CRM_UI_TOPBAR_MENU_STYLE_SIMPLE, $headerText, null, CRM_UI_STYLE_WARNING, false);
		// build notifications
        foreach ($notifications as $notification) {
	        $result .= $this->getTopbarSimpleElement($notification["text"], $this->notificationIconForNotificationType($notification["type"]), "notifications.php", $this->getRandomUIStyle());
        }  
        // build events.
        foreach ($eventsForToday as $event) {
	        $url = "events.php?initial_date=".urlencode($event["start_date"]);
	        $tint = $this->creamyColorForHexValue($event["color"]);
	        $result .= $this->getTopbarSimpleElement($event["title"], "calendar-o", $url, $tint);
        }  
        
        // footer and result                                      
        $result .= $this->getTopbarMenuFooter($this->lh->translationFor("see_all_notifications"), "notifications.php");
        return $result;
	}
	
	protected function getTopbarTasksMenu($user) {
		if (!$user->userHasBasicPermission()) return '';

		$list = $this->db->getUnfinishedTasks($user->getUserId());
		$numTasks = count($list);
		
		$headerText = $this->lh->translationFor("you_have").' '.$numTasks.' '.$this->lh->translationFor("pending_tasks");
		$result = $this->getTopbarMenuHeader("tasks", $numTasks, CRM_UI_TOPBAR_MENU_STYLE_DATE, $headerText, null, CRM_UI_STYLE_DANGER, false);
                                    
        foreach ($list as $task) {
	        $result .= $this->getTopbarSimpleElementWithDate($task["description"], $task["creation_date"], "clock-o", "tasks.php", CRM_UI_STYLE_WARNING);
        }
                                    
        $result .= $this->getTopbarMenuFooter($this->lh->translationFor("see_all_tasks"), "tasks.php");
        return $result;
    }

	/**
	 * Generates the HTML for the user's topbar menu.
	 * @param $userid the id of the user.
	 */
	protected function getTopbarUserMenu($user) {
		// menu actions & change my data(only for users with permissions).
		$menuActions = '';
		$changeMyData = '';
		if ($user->userHasBasicPermission()) {
			$menuActions = '<li class="user-body">
				<div class="text-center"><a href="" data-toggle="modal" id="change-password-toggle" data-target="#change-password-dialog-modal">'.$this->lh->translationFor("change_password").'</a></div>
				<div class="text-center"><a href="./messages.php">'.$this->lh->translationFor("messages").'</a></div>
				<div class="text-center"><a href="./notificationes.php">'.$this->lh->translationFor("notifications").'</a></div>
				<div class="text-center"><a href="./tasks.php">'.$this->lh->translationFor("tasks").'</a></div>
			</li>';
			$changeMyData = '<div class="pull-left"><a href="./edituser.php" class="btn btn-default btn-flat">'.$this->lh->translationFor("my_profile").'</a></div>';
		} 
		
		return '<li class="dropdown user user-menu">
	                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
	                    <img src="'.$user->getUserAvatar().'" class="user-image" alt="User Image" />
	                    <span>'.$user->getUserName().' <i class="caret"></i></span>
	                </a>
	                <ul class="dropdown-menu">
	                    <li class="user-header bg-light-blue">
	                        <img src="'.$user->getUserAvatar().'" class="img-circle" alt="User Image" />
	                        <p>'.$user->getUserName().'<small>'.$this->lh->translationFor("nice_to_see_you_again").'</small></p>
	                    </li>'.$menuActions.'
	                    <li class="user-footer">'.$changeMyData.'
	                        <div class="pull-right"><a href="./logout.php" class="btn btn-default btn-flat">'.$this->lh->translationFor("exit").'</a></div>
	                    </li>
	                </ul>
	            </li>';
	}

	public function getTopbarMenuHeader($icon, $badge, $menuStyle, $headerText = null, $headerLink = null, $badgeStyle = CRM_UI_STYLE_DEFAULT, $hideForLowResolution = true) {
		// header text and link
		if (!empty($headerText)) {
			$linkPrefix = isset($headerLink) ? '<a href="'.$headerLink.'">' : '';
			$linkSuffix = isset($headerLink) ? '</a>' : '';
			$headerCode = '<li class="header">'.$linkPrefix.$headerText.$linkSuffix.'</li>';
		} else { $headerCode = ""; }
		$hideCode = $hideForLowResolution? "hide-on-low" : "";
		
		// return the topbar menu header
		return '<li class="dropdown '.$menuStyle.'-menu '.$hideCode.'"><a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-'.$icon.'"></i><span class="label label-'.$badgeStyle.'">'.$badge.'</span></a>
					<ul class="dropdown-menu">'.$headerCode.'<li><ul class="menu">';
	}

	public function getTopbarMenuFooter($footerText, $footerLink = null) {
		$linkPrefix = isset($footerLink) ? '<a href="'.$footerLink.'">' : '';
		$linkSuffix = isset($footerLink) ? '</a>' : '';
		return '</ul></li><li class="footer">'.$linkPrefix.$footerText.$linkSuffix.'</li></ul></li>';
	}
	
	public function getTopbarSimpleElement($text, $icon, $link, $tint = "aqua") {
		$shortText = $this->substringUpTo($text, 40);
		return '<li style="text-align: left; !important;"><a href="'.$link.'"><i class="fa fa-'.$icon.' text-'.$tint.'"></i> '.$shortText.'</a></li>';
	}
	
	public function getTopbarSimpleElementWithDate($text, $date, $icon, $link, $tint = CRM_UI_STYLE_DEFAULT) {
		$shortText = $this->substringUpTo($text, 30);
	    $relativeTime = $this->relativeTime($date, 1);
		return '<li><a href="'.$link.'"><h3><p class="pull-left">'.$shortText.'</p><small class="label label-'.$tint.' pull-right"><i class="fa fa-'.$icon.'"></i> '.$relativeTime.'</small></h3></a></li>';
	}
	
	public function getTopbarComplexElement($title, $text, $date, $image, $link) {
		$shortTitle = $this->substringUpTo($title, 20);
		$shortText = $this->substringUpTo($text, 40);
	    $relativeTime = $this->relativeTime($date, 1);
		return '<li><a href="'.$link.'">
                    <div class="pull-left">
                        <img src="'.$image.'" class="img-circle" alt="User Image"/>
                    </div>
                    <h4>'.$title.' 
                    <small class="label"><i class="fa fa-clock-o"></i> '.$relativeTime.'</small>
                    </h4>
                    <p>'.$shortText.'</p>
                </a>
            </li>';
	}

	public function getTopbarCustomMenu($header, $elements, $footer) { return $header.$elements.$footer; }

	/** Sidebar */
	
	/**
	 * Generates the HTML for the sidebar of a user, given its role.
	 * @param $userid the id of the user.
	 */
	public function getSidebar($userid, $username, $userrole, $avatar) {
		$numMessages = $this->db->getUnreadMessagesNumber($userid);
		$numTasks = $this->db->getUnfinishedTasksNumber($userid);
		$numNotifications = $this->db->getNumberOfTodayNotifications($userid) + $this->db->getNumberOfTodayEvents($userid);
		$mh = \creamy\ModuleHandler::getInstance();
		
		$adminArea = "";
		if ($userrole == CRM_DEFAULTS_USER_ROLE_ADMIN) {
			$modulesWithSettings = $mh->modulesWithSettings();
			$adminArea = '<li class="treeview"><a href="#"><i class="fa fa-dashboard"></i> <span>'.$this->lh->translationFor("administration").'</span><i class="fa fa-angle-left pull-right"></i></a><ul class="treeview-menu">';
			$adminArea .= $this->getSidebarItem("./adminsettings.php", "gears", $this->lh->translationFor("settings")); // admin settings
			$adminArea .= $this->getSidebarItem("./adminusers.php", "user", $this->lh->translationFor("users")); // admin settings
			$adminArea .= $this->getSidebarItem("./adminmodules.php", "archive", $this->lh->translationFor("modules")); // admin settings
			$adminArea .= $this->getSidebarItem("./admincustomers.php", "users", $this->lh->translationFor("customer_groups")); // admin settings	
			foreach ($modulesWithSettings as $k => $m) { $adminArea .= $this->getSidebarItem("./modulesettings.php?module_name=".urlencode($k), $m->mainPageViewIcon(), $m->mainPageViewTitle()); }
	        $adminArea .= '</ul></li>';
		}
		
		// get customer types
		$customerTypes = $this->db->getCustomerTypes();
		
		// prefix: structure and home link
		$result = '<aside class="main-sidebar" sidebar-offcanvas"><section class="sidebar">
	            <div class="user-panel">
	                <div class="pull-left image">
	                    <a href="edituser.php"><img src="'.$avatar.'" class="img-circle" alt="User Image" /></a>
	                </div>
	                <div class="pull-left info">
	                    <p>'.$this->lh->translationFor("hello").', '.$username.'</p>
	                    <a href="edituser.php"><i class="fa fa-circle text-success"></i> '.$this->lh->translationFor("online").'</a>
	                </div>
	            </div>
	            <ul class="sidebar-menu"><li class="header">'.strtoupper($this->lh->translationFor("menu")).'</li>';
	    // body: home and customer menus
        $result .= $this->getSidebarItem("./index.php", "bar-chart-o", $this->lh->translationFor("home"));
        // include a link for every customer type
        foreach ($customerTypes as $customerType) {
	        if (isset($customerType["table_name"]) && isset($customerType["description"])) {
		        $customerTableName = $customerType["table_name"];
		        $customerFriendlyName = $customerType["description"];
		        $url = 'customerslist.php?customer_type='.$customerTableName.'&customer_name='.$customerFriendlyName;
		        $result .= $this->getSidebarItem($url, "users", $customerFriendlyName);
	        }
        }

        // ending: messages, notifications, tasks, events.
        $result .= $this->getSidebarItem("events.php", "calendar-o", $this->lh->translationFor("events"));
        $result .= $this->getSidebarItem("messages.php", "envelope", $this->lh->translationFor("messages"), $numMessages);
        $result .= $this->getSidebarItem("notifications.php", "exclamation", $this->lh->translationFor("notifications"), $numNotifications, "orange");
        $result .= $this->getSidebarItem("tasks.php", "tasks", $this->lh->translationFor("tasks"), $numTasks, "red");
        
        // suffix: modules
        $activeModules = $mh->activeModulesInstances();
        foreach ($activeModules as $shortName => $module) {
        	$result .= $this->getSidebarItem($mh->pageLinkForModule($shortName, null), $module->mainPageViewIcon(), $module->mainPageViewTitle(), $module->sidebarBadgeNumber());
        } 
        
		$result .= $adminArea.'</ul></section></aside>';
		return $result;
	}

	/**
	 * Generates the HTML code for a sidebar link.
	 */
	protected function getSidebarItem($url, $icon, $title, $includeBadge = null, $badgeColor = "green") {
		$badge = (isset($includeBadge)) ? '<small class="badge pull-right bg-'.$badgeColor.'">'.$includeBadge.'</small>' : '';
		return '<li><a href="'.$url.'"><i class="fa fa-'.$icon.'"></i> <span>'.$title.'</span>'.$badge.'</a></li>';
	}

	/** Customers */
   	
   	/**
	 * Generates a HTML table with all customer types for the administration panel.
	 */
	public function getCustomerTypesAdminTable() {
		// generate table		
		$items = array("Id", $this->lh->translationFor("name"));
		$table = $this->generateTableHeaderWithItems($items, "customerTypes", "table-bordered table-striped", true);
		if ($customerTypes = $this->db->getCustomerTypes()) {
			foreach ($customerTypes as $customerType) {
				$table .= "<tr><td>".$customerType["id"]."</td><td><span class='text'>".$customerType["description"].'
				</span><div class="tools pull-right">
				<a class="edit-customer" href="'.$customerType["id"].'" data-toggle="modal" data-target="#edit-customer-modal">
				<i class="fa fa-edit task-item"></i></a>
				<a class="delete-customer" href="'.$customerType["id"].'"><i class="fa fa-trash-o"></i></a>
				</div></td></tr>';
			}
		}
		$table .= $this->generateTableFooterWithItems($items, true);
		
		// generate companion JS code.
		// delete customer type
		$ec_ok = $this->reloadLocationJS();
		$ec_ko = $this->showRetrievedErrorMessageAlertJS();
		$deletephp = "./php/DeleteCustomerType.php";
		$deleteCustomerJS = $this->clickableClassActionJS("delete-customer", "customertype", "href", $deletephp, $ec_ok, $ec_ko, true);
		// edit customer type
		$idAssignment = $this->selfValueAssignmentJS("href", "customer-type-id");
		$textAssignment = $this->classValueFromParentAssignmentJS("text", "td", "newname");
		$editCustomerJS = $this->clickableFillValuesActionJS("edit-customer", array($idAssignment, $textAssignment));	

		// edit customer modal form
		$modalTitle = $this->lh->translationFor("edit_customer_type");
		$modalSubtitle = $this->lh->translationFor("enter_new_name_customer_type");
		$name = $this->lh->translationFor("name");
		$newnameInput = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("newname", "newname", "text required", $name));
		$hiddenidinput = $this->hiddenFormField("customer-type-id");
		$bodyInputs = $newnameInput.$hiddenidinput;
		$msgDiv = $this->emptyMessageDivWithTag("editcustomermessage");
		$modalFooter = $this->modalDismissButton("edit-customer-cancel").$this->modalSubmitButton("edit-customer-accept").$msgDiv;
		$modalForm = $this->modalFormStructure("edit-customer-modal", "edit-customer-form", $modalTitle, $modalSubtitle, $bodyInputs, $modalFooter, "user");
		
		// validate form javascript
		$successJS = $this->reloadLocationJS();
		$em_text = $this->lh->translationFor("error_editing_customer_name");
		$failureJS = $this->fadingInMessageJS($this->dismissableAlertWithMessage($em_text, false, true), "editcustomermessage");
		$preambleJS = $this->fadingOutMessageJS(false, "editcustomermessage");
		$javascript = $this->formPostJS("edit-customer-form", "./php/ModifyCustomerType.php", $successJS, $failureJS, $preambleJS);

		return $table."\n".$editCustomerJS."\n".$deleteCustomerJS."\n".$modalForm."\n".$javascript;
	}
	
	public function newCustomerTypeAdminForm() {
		// form
		$cg_text = $this->lh->translationFor("customer_group");
		$hc_text = $this->lh->translationFor("new_customer_group");
		$cr_text = $this->lh->translationFor("create");
		$inputfield = $this->singleFormInputElement("newdesc", "newdesc", "text", $cg_text);
		$formbox = $this->boxWithForm("createcustomergroup", $hc_text, $inputfield, $cr_text, CRM_UI_STYLE_DEFAULT, "creationmessage");
		
		// javascript form submit.
		$successJS = $this->reloadLocationJS();
		$ua_text = $this->lh->translationFor("unable_add_customer_group");
		$failureJS = $this->fadingInMessageJS($this->dismissableAlertWithMessage($ua_text, false, true), "creationmessage");
		$preambleJS = $this->fadingOutMessageJS(false, "creationmessage");
		$javascript = $this->formPostJS("createcustomergroup", "./php/CreateCustomerGroup.php", $successJS, $failureJS, $preambleJS);
		
		return $formbox."\n".$javascript;
	}
	
	/**
	 * Generates the HTML with an empty table for a list of contacts or customers.
	 */
	public function getEmptyCustomersList($customerType) {
	   // print prefix
	   $columns = $this->db->getCustomerColumnsToBeShownInCustomerList($customerType);
	   $columns[] = $this->lh->translationFor("action");
	   $result = $this->generateTableHeaderWithItems($columns, "contacts", "table-bordered table-striped", true);

       // print suffix
       $result .= $this->generateTableFooterWithItems($columns, true);
       return $result;
	}

	/** Tasks */

	/**
	 * Generates the HTML for a given task as a table row
	 * @param $task Array associative array representing the task object.
	 * @return String the HTML representation of the task as a row.
	 */
	private function getTaskAsIndividualRow($task) {
		// define progress and bar color
		$completed = $task["completed"];
		if ($completed < 0) $completed = 0;
		else if ($completed > 100) $completed = 100;
		$creationdate = $this->relativeTime($task["creation_date"]);
		// values dependent on completion of the task.
		$doneOrNot = $completed == 100 ? 'class="done"' : '';
		$completeActionCheckbox = $completed == 100 ? '' : '<input type="checkbox" value="" name="">';
		// modules hovers.
		$mh = \creamy\ModuleHandler::getInstance();
		$moduleTaskHoverActions = $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_TASK_LIST_HOVER, array("taskid" => $task["id"]), CRM_MODULE_MERGING_STRATEGY_APPEND);
		
		return '<li id="'.$task["id"].'" '.$doneOrNot.'>'.$completeActionCheckbox.'<span class="text">'.$task["description"].'</span>
				  <small class="label label-warning pull-right"><i class="fa fa-clock-o"></i> '.$creationdate.'</small>
				  <div class="tools">'.$moduleTaskHoverActions.'
				  	'.$this->hoverActionButton("edit-task-action", "edit", $task["id"], "edit-task-dialog-modal", null, "task-item").'
				  	'.$this->hoverActionButton("delete-task-action", "trash-o", $task["id"]).'
				  </div>
			 </li>';
	}

	/**
	 * Generates the HTML for a all tasks of a given user as a table row
	 * @param $userid Int id of the user to retrieve the tasks from.
	 * @return String the HTML representation of the user's tasks as a table.
	 */
	public function getCompletedTasksAsTable($userid, $userrole) { 
		$tasks = $this->db->getCompletedTasks($userid);
		if (empty($tasks)) { return $this->calloutInfoMessage($this->lh->translationFor("you_dont_have_completed_tasks")); }
		else {
			$list = "<ul class=\"todo-list ui-sortable\">";
			foreach ($tasks as $task) {
				// generate row
				$taskHTML = $this->getTaskAsIndividualRow($task);
				$list = $list.$taskHTML;
			}
			
			$list = $list."</ul>";
	    	return $list;
		}
   	}

	/**
	 * Generates the HTML for a all tasks of a given user as a table row
	 * @param $userid Int id of the user to retrieve the tasks from.
	 * @return String the HTML representation of the user's tasks as a table.
	 */
	public function getUnfinishedTasksAsTable($userid) { 
		$tasks = $this->db->getUnfinishedTasks($userid);
		if (empty($tasks)) { return $this->calloutInfoMessage($this->lh->translationFor("you_dont_have_pending_tasks")); }
		else {
			$list = "<ul class=\"todo-list ui-sortable\">";
			foreach ($tasks as $task) {
				// generate row
				$taskHTML = $this->getTaskAsIndividualRow($task);
				$list = $list.$taskHTML;
			}
			
			$list = $list."</ul>";
	    	return $list;
		}
   	}
	
	/**
	 * Returns the tasks footer action hooks for modules.
	 */
	public function getTasksActionFooter() {
		$mh = \creamy\ModuleHandler::getInstance();
		return $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_TASK_LIST_ACTION, null, CRM_MODULE_MERGING_STRATEGY_APPEND);
	}
	
	/**
	 * Generates the inner form code for the fields of the customer creation/edition form (the form part is not included, 
	 * allowing it to be a modal or inline form). If $customerobj is specified, the values are loaded from it.
	 * @param Array $customerobj an associative array with the current customer values, or null.
	 * @return String a HTML generated code with the form fields without the form, ready to be wrapped in a form by using
	 * any of the form generation methods of this class.
	 */
	public function customerFieldsForForm($customerobj = null, $customerType = null, $customerid = null) {
		// name
		$ph = $this->lh->translationFor("name").' ('.$this->lh->translationFor("mandatory").')';
		$vl = isset($customerobj["name"]) ? $customerobj["name"] : null;
		$name_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("name", "name", "text", $ph, $vl, "user"));

		// type of customer service.
		$ph = $this->lh->translationFor("customer_or_service_type");
		$vl = isset($customerobj["type"]) ? $customerobj["type"] : null;
		$ptype_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("productType", "productType", "text", $ph, $vl, "puzzle-piece"));

		// Customer id number or id identifier (passport, NIF, DNI...).
		$ph = $this->lh->translationFor("id_number");
		$vl = isset($customerobj["id_number"]) ? $customerobj["id_number"] : null;
		$idnum_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("id_number", "id_number", "text", $ph, $vl, "credit-card"));

		// phone
		$ph = $this->lh->translationFor("home_phone");
		$vl = isset($customerobj["phone"]) ? $customerobj["phone"]: null;
		$phone_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("phone", "phone", "text", $ph, $vl, "phone"));

		// mobile phone
		$ph = $this->lh->translationFor("mobile_phone");
		$vl = isset($customerobj["mobile"]) ? $customerobj["mobile"] : null;
		$mobile_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("mobile", "mobile", "text", $ph, $vl, "mobile"));

		// email
		$ph = $this->lh->translationFor("email");
		$vl = isset($customerobj["email"]) ? $customerobj["email"] : null;
		$email_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("email", "email", "text", $ph, $vl, "envelope"));

		// address
		$ph = $this->lh->translationFor("address");
		$vl = isset($customerobj["address"]) ? $customerobj["address"] : null;
		$address_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("address", "address", "text", $ph, $vl, "map-marker"));

		// city & state
		$ph = $this->lh->translationFor("city");
		$vl = isset($customerobj["city"]) ? $customerobj["city"] : null;
		$city_f = $this->singleInputGroupWithContent($this->singleFormInputElement("city", "city", "text", $ph, $vl, "map-marker"));
		$ph = $this->lh->translationFor("state");
		$vl = isset($customerobj["state"]) ? $customerobj["state"] : null;
		$state_f = $this->singleInputGroupWithContent($this->singleFormInputElement("state", "state", "text", $ph, $vl, "map-marker"));
		$c_and_s_row = $this->rowWithVariableContents(array("6", "6"), array($city_f, $state_f));
		$c_and_s_field = $this->singleFormGroupWrapper($c_and_s_row);

		// zip code and country
		$ph = $this->lh->translationFor("zipcode");
		$vl = isset($customerobj["zip_code"]) ? $customerobj["zip_code"] : null;
		$zip_f = $this->singleInputGroupWithContent($this->singleFormInputElement("zipcode", "zipcode", "text", $ph, $vl, "map-marker"));
		$ph = $this->lh->translationFor("country");
		$vl = isset($customerobj["country"]) ? $customerobj["country"] : null;
		$country_f = $this->singleInputGroupWithContent($this->singleFormInputElement("country", "country", "text", $ph, $vl, "map-marker"));
		$c_and_z_row = $this->rowWithVariableContents(array("6", "6"), array($zip_f, $country_f));
		$c_and_z_field = $this->singleFormGroupWrapper($c_and_z_row);
		
		// website.
		$ws = $this->lh->translationFor("website");
		$vw = isset($customerobj["website"]) ? $customerobj["website"] : null;
		$ws_f = $this->singleFormGroupWithInputGroup($this->singleFormInputElement("website", "website", "text", $ws, $vw, "globe"));
		
		// textarea
		$ph = $this->lh->translationFor("notes");
		$vl = isset($customerobj["notes"]) ? $customerobj["notes"] : null;
		$notes_f = $this->singleFormGroupWithInputGroup($this->singleFormTextareaElement("notes", "notes", $ph, $vl, "file-text-o"));
		
		// marital status
        $currentMS = 0; $msOptions = "";
        $ms = array("choose_an_option","single","married","divorced","separated","widow");
        if (isset($customerobj["marital_status"])) {
            $currentMS = $customerobj["marital_status"];
            if ($currentMS < 1 || $currentMS > 5) $currentMS = 0;
        }
		$ms_f = $this->singleFormGroupWithSelect($this->lh->translationFor("marital_status"), "maritalstatus", "maritalstatus", $ms, $currentMS, true);
		
		// gender
        $currentGender = -1;
        if (isset($customerobj["gender"])) {
            $currentGender = $customerobj["gender"];
            if ($currentGender < 0 || $currentGender > 1) $currentGender = -1;
        }
        $genders = array("-1" => "choose_an_option", "0" => "female", "1" => "male");
		$gender_f = $this->singleFormGroupWithSelect($this->lh->translationFor("gender"), "gender", "gender", $genders, $currentGender, true);
        
		// birthdate
		$dateAsDMY = "";
        if (isset($customerobj["birthdate"])) { 
            $time = strtotime($customerobj["birthdate"]);
            $dateAsDMY = date('m/d/Y', $time); 
        }
		$md = $this->maskedDateInputElement("birthdate", "birthdate", "dd/mm/yyyy", $dateAsDMY, "calendar", true);
		$birth_f = $this->singleFormGroupWithInputGroup($md, $this->lh->translationFor("birthdate"));
								
		// do not send email
		$doNotSendEmail = isset($customerobj["do_not_send_email"]) ? filter_var($customerobj["do_not_send_email"], FILTER_VALIDATE_BOOLEAN) : null; 
		$dnsmc = $this->checkboxInputWithLabel($this->lh->translationFor("do_not_send_email"), "donotsendemail", "donotsendemail", $doNotSendEmail);
		$dnsm_f = $this->singleFormGroupWrapper($dnsmc);

		// hidden fields: customer type and id
		$hidden = "";
		$hidden .= $this->hiddenFormField("customer_type", $customerType);
		$hidden .= $this->hiddenFormField("customerid", $customerid);

		// join all fields
		$formcontent = $name_f.$ptype_f.$idnum_f.$email_f.$phone_f.$mobile_f.$address_f.$c_and_s_field.$c_and_z_field.$ws_f.$notes_f.$ms_f.$gender_f.$birth_f.$dnsm_f.$hidden;
		return $formcontent;
	}
	
	/** Messages */

	/**
	 * Generates the list of users $myuserid can send message to or assign a task to as a HTML form SELECT.
	 * @param Int $myuserid 		id of the user that wants to send messages, all other user's ids will be returned.
	 * @param Boolean $includeSelf 	if true, $myuserid will appear listed in the options. If false (default), $myuserid will not be included in the options. If this parameter is set to true, the default option will be the $myuserid
	 * @param String $customMessage The custom message to ask for a selection in the SELECT, default is "send this message to...".
	 * @param String $selectedUser	If defined, this user will appear as selected by default.
	 * @return the list of users $myuserid can send mail to (all valid users except $myuserid unless $includeSelf==true) as a HTML form SELECT.
	 */
	public function generateSendToUserSelect($myuserid, $includeSelf = false, $customMessage = NULL, $selectedUser = null) {
		// perform query of users.
		if (empty($customMessage)) $customMessage = $this->lh->translationFor("send_this_message_to");
		$usersarray = $this->db->getAllEnabledUsers();

		// iterate through all users and generate the select
		$response = '<select class="form-control required" id="touserid" name="touserid"><option value="0">'.$customMessage.'</option>';
		foreach ($usersarray as $userobj) {
			// don't include ourselves.
			if ($userobj["id"] != $myuserid) {
				$selectedUserCode = "";
				if (isset($selectedUser) && ($selectedUser == $userobj["id"])) { $selectedUserCode = 'selected="true"'; }
				$response = $response.'<option value="'.$userobj["id"].'" '.$selectedUserCode.' >'.$userobj["name"].'</option>';
			} else if ($includeSelf === true) { // assign to myself by default unless another $selectedUser has been specified.
				$selfSelectedCode = isset($selectedUser) ? "" : 'selected="true"';
				$response = $response.'<option value="'.$userobj["id"].'" '.$selfSelectedCode.'>'.$this->lh->translationFor("myself").'</option>';
			}	
		}
		$response = $response.'</select>';
		return $response;
	}
	
	/**
	 * Generates the HTML of the given messages as a HTML table, from a table array
	 * @param Array $messages the list of messages.
	 * @return the HTML code with the list of messages as a HTML table. 
	 */
	private function getMessageListAsTable($messages, $folder) {
		$columns = array("", "favorite", "name", "subject", "attachment", "date");
		$table = $this->generateTableHeaderWithItems($columns, "messagestable", "table-hover table-striped mailbox table-mailbox", true, true);
		foreach ($messages as $message) {
			if ($message["message_read"] == 0) $table .= '<tr class="unread">';
			else $table .= '<tr>';
						
			// variables and html text depending on the message
			$favouriteHTML = "-o"; if ($message["favorite"] == 1) $favouriteHTML = "";
			$messageLink = '<a href="readmail.php?folder='.$folder.'&message_id='.$message["id"].'">';

			$table .= '<td><input type="checkbox" class="message-selection-checkbox" value="'.$message["id"].'"/></td>';
			$table .= '<td class="mailbox-star"><i class="fa fa-star'.$favouriteHTML.'" id="'.$message["id"].'"></i></td>';
			$table .= '<td class="mailbox-name">'.$messageLink.(isset($message["remote_user"]) ? $message["remote_user"] : $this->lh->translationFor("unknown")).'</a></td>';
			$table .= '<td class="mailbox-subject">'.$message["subject"].'</td>';
			$table .= '<td class="mailbox-attachment"></td>'; //<i class="fa fa-paperclip"></i></td>';
			$table .= '<td class="mailbox-date pull-right">'.$this->relativeTime($message["date"]).'</td>';
			$table .= '</tr>';
		}		
		$table .= $this->generateTableFooterWithItems($columns, true, true);
		return $table;
	}	
	
	/**
	 * Generates the HTML for a mailbox button.
	 */
	public function generateMailBoxButton($buttonClass, $icon, $param, $value) {
		return '<button class="btn btn-default btn-sm '.$buttonClass.'" '.$param.'="'.$value.'"><i class="fa fa-'.$icon.'"></i></button>';
	}
	
	/**
	 * Generates the button group for the mailbox messages table
	 */
	public function getMailboxButtons($folder) {
		// send to trash or recover from trash ?
		if ($folder == MESSAGES_GET_DELETED_MESSAGES) {
			$trashOrRecover = '<button class="btn btn-default btn-sm messages-restore-message"><i class="fa fa-undo"></i></button>';
		} else { 
			$trashOrRecover = '<button class="btn btn-default btn-sm messages-send-to-junk"><i class="fa fa-trash-o"></i></button>'; 
		}
		
		// basic buttons
		$buttons = '<button class="btn btn-default btn-sm checkbox-toggle"><i class="fa fa-square-o"></i></button>                    
		<div class="btn-group">
		  <button class="btn btn-default btn-sm messages-mark-as-favorite"><i class="fa fa-star"></i></button>
		  <button class="btn btn-default btn-sm messages-mark-as-read"><i class="fa fa-eye"></i></button>
		  <button class="btn btn-default btn-sm messages-mark-as-unread"><i class="fa fa-eye-slash"></i></button>
		  '.$trashOrRecover.'
		  <button class="btn btn-default btn-sm messages-delete-permanently"><i class="fa fa-times"></i></button>';
		// module buttons
		$mh = \creamy\ModuleHandler::getInstance();
		$buttons .= $mh->applyHookOnActiveModules(CRM_MODULE_HOOK_MESSAGE_LIST_FOOTER, array("folder" => $folder), CRM_MODULE_MERGING_STRATEGY_APPEND);
		// chevrons
		$buttons .= '</div><div class="pull-right"><div class="btn-group">
			<button class="btn btn-default btn-sm mailbox-prev"><i class="fa fa-chevron-left"></i></button>
			<button class="btn btn-default btn-sm mailbox-next"><i class="fa fa-chevron-right"></i></button>
		</div></div>';
		
		return $buttons;
	}
	
	/**
	 * Generates a HTML table with all inbox messages of a user.
	 * @param Int $userid user to retrieve the messages from
	 */
	public function getInboxMessagesAsTable($userid) {
		$messages = $this->db->getMessagesOfType($userid, MESSAGES_GET_INBOX_MESSAGES);
		if ($messages == NULL) return $this->calloutInfoMessage($this->lh->translationFor("unable_get_messages"));
		else return $this->getMessageListAsTable($messages);
	}
	
	/**
	 * Generates a HTML table with the unread messages of the user.
	 * @param Int $userid user to retrieve the messages from
	 */
	public function getUnreadMessagesAsTable($userid) {
		$messages = $this->db->getMessagesOfType($userid, MESSAGES_GET_UNREAD_MESSAGES);
		if ($messages == NULL) return $this->calloutInfoMessage($this->lh->translationFor("no_messages_in_list"));
		else return $this->getMessageListAsTable($messages);
	}
		
	/**
	 * Generates a HTML table with with the junk messages of a user.
	 * @param Int $userid user to retrieve the messages from
	 */
	public function getJunkMessagesAsTable($userid) {
		$messages = $this->db->getMessagesOfType($userid, MESSAGES_GET_DELETED_MESSAGES);
		if ($messages == NULL) return $this->calloutInfoMessage($this->lh->translationFor("no_messages_in_list"));
		else return $this->getMessageListAsTable($messages);
	}
		
	/**
	 * Generates a HTML table with the sent messages of a user.
	 * @param Int $userid user to retrieve the messages from
	 */
	public function getSentMessagesAsTable($userid) {
		$messages = $this->db->getMessagesOfType($userid, MESSAGES_GET_SENT_MESSAGES);
		if ($messages == NULL) return $this->calloutInfoMessage($this->lh->translationFor("no_messages_in_list"));
		else return $this->getMessageListAsTable($messages);
	}
				
	/**
	 * Generates a HTML table with the favourite messages of a user.
	 * @param Int $userid user to retrieve the messages from
	 */
	public function getFavoriteMessagesAsTable($userid) {
		$messages = $this->db->getMessagesOfType($userid, MESSAGES_GET_FAVORITE_MESSAGES);
		if ($messages == NULL) return $this->calloutInfoMessage($this->lh->translationFor("no_messages_in_list"));
		else return $this->getMessageListAsTable($messages);
	}
		
	/**
	 * Generates a HTML table with the messages from given folder for a user.
	 * @param Int $userid user to retrieve the messages from
	 * @param Int $folder folder to retrieve the messages from
	 */
	public function getMessagesFromFolderAsTable($userid, $folder) {
		$messages = $this->db->getMessagesOfType($userid, $folder);
		if ($messages == NULL) return $this->calloutInfoMessage($this->lh->translationFor("no_messages_in_list"));
		else return $this->getMessageListAsTable($messages, $folder);
	}
	
	/**
	 * Generates the HTML with the list of message folders as <li> items.
	 * @param $activefolder String current active folder the user is in.
	 * @return String the HTML with the list of message folders as <li> items.
	 */
	public function getMessageFoldersAsList($activefolder) {
		require_once('Session.php');
		$user = \creamy\CreamyUser::currentUser();
		// info for active folder and unread messages
        $unreadMessages = $this->db->getUnreadMessagesNumber($user->getUserId());
        $aInbox = $activefolder == MESSAGES_GET_INBOX_MESSAGES ? 'class="active"' : '';
        $aSent = $activefolder == MESSAGES_GET_SENT_MESSAGES ? 'class="active"' : '';
        $aFav = $activefolder == MESSAGES_GET_FAVORITE_MESSAGES ? 'class="active"' : '';
        $aDel = $activefolder == MESSAGES_GET_DELETED_MESSAGES ? 'class="active"' : '';
        
        return '<ul class="nav nav-pills nav-stacked">
			<li '.$aInbox.'><a href="messages.php?folder=0">
				<i class="fa fa-inbox"></i> '.$this->lh->translationFor("inbox").' 
				<span class="label label-primary pull-right">'.$unreadMessages.'</span></a>
			</li>
			<li '.$aSent.'><a href="messages.php?folder=3"><i class="fa fa-envelope-o"></i> '.$this->lh->translationFor("sent").'</a></li>
			<li '.$aFav.'><a href="messages.php?folder=4"><i class="fa fa-star"></i> '.$this->lh->translationFor("favorites").'</a></li>
			<li '.$aDel.'><a href="messages.php?folder=2"><i class="fa fa-trash-o"></i> '.$this->lh->translationFor("trash").'</a></li>
		</ul>';
	}

	/**
	 * Generates the HTML code for showing the attachments of a given message.
	 * @param Int $messageid 	identifier for the message.
	 * @param Int $folderid 	identifier for the folder.
	 * @param Int $userid 		identifier for the user.
	 * @return String The HTML code containing the code for the attachments.
	 */
	public function attachmentsSectionForMessage($messageid, $folderid) {
		$attachments = $this->db->getMessageAttachments($messageid, $folderid);
		if (!isset($attachments) || count($attachments) < 1) { return ""; }
		
		$code = '<div class="box-footer non-printable"><ul class="mailbox-attachments clearfix">';
		foreach ($attachments as $attachment) {
			// icon/image
			$icon = $this->getFiletypeIconForFile($attachment["filepath"]);
			if ($icon != CRM_FILETYPE_IMAGE) {
				$hasImageCode = "";
				$iconCode = '<i class="fa fa-'.$icon.'"></i>';
				$attIcon = "paperclip";
			} else {
				$hasImageCode = "has-img";
				$iconCode = '<img src="'.$attachment["filepath"].'" alt="'.$this->lh->translationFor("attachment").'"/>';
				$attIcon = "camera";
			}
			// code
			$basename = basename($attachment["filepath"]);
			$code .= '<li><span class="mailbox-attachment-icon '.$hasImageCode.'">'.$iconCode.'</span>
                      <div class="mailbox-attachment-info">
                        <a href="'.$attachment["filepath"].'" target="_blank" class="mailbox-attachment-name">
                        <i class="fa fa-'.$attIcon.'"></i> '.$basename.'</a>
                        <span class="mailbox-attachment-size">
                          '.$attachment["filesize"].'
                          <a href="'.$attachment["filepath"].'" target="_blank" class="btn btn-default btn-xs pull-right"><i class="fa fa-cloud-download"></i></a>
                        </span>
                      </div>
                    </li>';			
		}
		$code .= '</ul></div>';
		return $code;
	}

	/** 
	 * returns the filetype icon for a given file. This filetype can be used added to fa-
	 * for the icon representation of a file.	
	 */
	public function getFiletypeIconForFile($filename) {
		$mimetype = mime_content_type($filename);
		if (\creamy\CRMUtils::startsWith($mimetype, "image/")) { return CRM_FILETYPE_IMAGE; }
		else if ($mimetype == "application/pdf") { return CRM_FILETYPE_PDF; }
		else if ($mimetype == "application/zip") { return CRM_FILETYPE_ZIP; }
		else if ($mimetype == "text/plain") { return CRM_FILETYPE_TXT; }
		else if ($mimetype == "text/html") { return CRM_FILETYPE_HTML; }
		else if (\creamy\CRMUtils::startsWith($mimetype, "video/")) { return CRM_FILETYPE_VIDEO; }
		else { return CRM_FILETYPE_UNKNOWN; }
	}

	/** Events */
	
	/**
	 * Returns a time selection for an event. It includes time periods of 15 min.
	 * Default value is "All day" with value 0. All other values contain the time
	 * string in HH:mm format.
	 */
	public function eventTimeSelect() {
		// build options
		$options = array("all_day" => $this->lh->translationFor("all_day"));
		for ($i = 0; $i < 24; $i++) {
			for ($j = 0; $j < 60; $j += 15) {
				$hour = sprintf("%02d", $i);
				$minute = sprintf("%02d", $j);
				$options["$hour:$minute"] = "$hour:$minute"; 
			}
		}
		
		// return select.
		return $this->singleFormGroupWithSelect(
		    null, 									// label
		    "time", 								// id
		    "time", 								// name
		    $options, 								// options
		    "all_day",								// selected option 
		    false);									// needs translation
	}
	
	/**
	 * Returns the list of unassigned events as list.
	 */
	public function getUnassignedEventsList($userid) {
		$result = "<div id='external-events'>";
		$events = $this->db->getUnassignedEventsForUser($userid);
		foreach ($events as $event) {
			$urlCode = empty($event["url"]) ? '' : ' event-url="'.$event["url"].'" ';
			$result .= "<div event-id='".$event["id"]."' class='external-event bg-".$this->creamyColorForHexValue($event["color"])."' $urlCode>".$event["title"]."</div>";
		}
		$result .= "</div>";
		return $result;
	}
	
	/**
	 * Returns the list of date-assigned events as javascript full calendar list.
	 */
	public function getAssignedEventsListForCalendar($userid) {
		$result = "events: [ ";
		$events = $this->db->getAssignedEventsForUser($userid);
		foreach ($events as $event) {
			// id
			$eventId = $event["id"];
			// title
			$title = str_replace("'", "\\'", $event["title"]);
			// end and start date.
			$startDate = strtotime($event["start_date"]);
			if (empty($event["end_date"])) { continue; } // no end date? no way!
			$endDate = strtotime($event["end_date"]);
			// start date components
			$comp = getdate($startDate);
			$y = $comp["year"]; $m = $comp["mon"]-1; $d = $comp["mday"]; $H = $comp["hours"]; $M = $comp["minutes"];
			$startCode = ", start: new Date($y, $m, $d, $H, $M)";
			$comp = getdate($endDate);
			$y = $comp["year"]; $m = $comp["mon"]-1; $d = $comp["mday"]; $H = $comp["hours"]; $M = $comp["minutes"];
			$endCode = ", end: new Date($y, $m, $d, $H, $M)";
			// all day?
			$allDayCode = ", allDay: ".(($event["all_day"]) ? "true" : "false");
			// url
			if (isset($event["url"])) { $urlCode = ", url: '".$event["url"]."'"; }
			else $urlCode = "";
			// color
			$color = $event["color"];
			$colorCode = ", backgroundColor: '$color', borderColor: '$color'";
			
			$result .= "{ id: $eventId, title: '$title' $startCode $endCode $allDayCode $urlCode $colorCode},";
		}
		$result = rtrim($result, ",");
		$result .= "]";
		return $result;
	}

	public function getTimezoneForCalendar() {
		$timezone = $this->db->getTimezoneSetting();
		return "timezone: '$timezone', timezoneParam: '$timezone'";
	}

	/** Notifications */

	/**
	 * Returns the HTML font-awesome icon for notifications of certain type.
	 * @param $type String the type of notification.
	 * @return String the string with the font-awesome icon for this notification type.
	 */
	public function notificationIconForNotificationType($type) {
		if ($type == "contact") return "user";
		else if ($type == "event") return "calendar-o";
		else if ($type == "message") return "envelope";
		else return "calendar-o";
	}
	
	/**
	 * Returns the HTML UI color for notifications of certain type.
	 * @param $type String the type of notification.
	 * @return String the string with the UI color for this notification type.
	 */
	public function notificationColorForNotificationType($type) {
		if ($type == "contact") return "aqua";
		else if ($type == "message") return "blue";
		else return "yellow";
	}
	
	/**
	 * Returns the HTML action button text for notifications of certain type.
	 * @param $type String the type of notification.
	 * @return String the string with the action button text for this notification type.
	 */
	public function actionButtonTextForNotificationType($type) {
		if ($type == "contact") return $this->lh->translationFor("see_customer");
		else if ($type == "message") return $this->lh->translationFor("read_message");
		else if ($type == "event") return $this->lh->translationFor("see_details");
		else return $this->lh->translationFor("see_more");
	}
	
	/**
	 * Returns the HTML header text for notifications of certain type associated to certain action.
	 * @param $type String the type of notification.
	 * @param $action String a URL with the action to perform for this notification.
	 * @return String the string with the header text for this notification type.
	 */
	public function headerTextForNotificationType($type, $action) {
		if ($type == "contact") 
		return empty($action) ? $this->lh->translationFor("you_have_a_new")." ".$this->lh->translationFor("contact") : $this->lh->translationFor("you_have_a_new")." <a href=".$action.">".$this->lh->translationFor("contact")."</a>";
		else if ($type == "message") 
			return empty($action) ? $this->lh->translationFor("you_have_a_new")." ".$this->lh->translationFor("message") : $this->lh->translationFor("you_have_a_new")." <a href=".$action.">".$this->lh->translationFor("message")."</a>";

		return empty($action) ? $this->lh->translationFor("you_have_a_new")." ".$this->lh->translationFor("event") : $this->lh->translationFor("you_have_a_new")." <a href=".$action.">".$this->lh->translationFor("event")."</a>";
	}
	
	/**
	 * Generates the HTML code for a timeline item action button.
	 * @param String $url 		the url to launch when pushing the button.
	 * @param String $title 	title for the button.
	 * @param String $style		Style for the button, one of CRM_UI_STYLE_*
	 * @return String			The HTML for the button to include in the timeline item.
	 */
	public function timelineItemActionButton($url, $title, $style = CRM_UI_STYLE_DEFAULT) {
		return '<div class="timeline-footer"><a class="btn btn-'.$style.' btn-xs" href="'.$url.'">'.$title.'</a></div>';
	}
	
	
	/**
	 * Generates the HTML code for a timeline item with the given data.
	 * @param String $title 		Title for the timeline item
	 * @param String $content		Main content (text) for the timeline item.
	 * @param String $date			Recognizable date for strtotime (see http://php.net/manual/es/datetime.formats.date.php).
	 * @param String $url			If set, an action for the notification, use 
	 * @param String $buttonTitle	Title for the button (if URL set).
	 * @param String $icon			Icon for the notification item (default calendar).
	 * @param String $buttonStyle	Style for the button, one of CRM_UI_STYLE_*
	 * @param String $badgeColor	Color for the badge notification bubble (default yellow).
	 * @return The HTML with the code of the timeline notification item to insert in the timeline list. 
	 */
	public function timelineItemWithData($title, $content, $date, $url = null, $buttonTitle, $icon = "calendar-o", $buttonStyle = CRM_UI_STYLE_DEFAULT, $badgeColor = "yellow") {
		// parameters
		$relativeTime = $this->relativeTime($date, 1);
		$actionHTML = isset($url) ? $this->timelineItemActionButton($url, $buttonTitle, $buttonStyle) : "";
		// return code.
		return '<li><i class="fa fa-'.$icon.' bg-'.$badgeColor.'"></i>
            <div class="timeline-item">
                <span class="time"><i class="fa fa-clock-o"></i> '.$relativeTime.'</span>
                <h3 class="timeline-header no-border">'.$title.'</h3>
				<div class="timeline-body">'.$content.'</div>
                '.$actionHTML.'
            </div></li>';
	}
	
	/**
	 * Generates the HTML for the beginning of the timeline.
	 */
	protected function timelineStart($message, $includeInitialTimelineStructure = true, $color = "green") {
		$tlCode = $includeInitialTimelineStructure ? '<ul class="timeline">' : '';
		return $tlCode.'<li class="time-label"><span class="bg-'.$color.'">'.$message.'</span></li>';
	}
	
	/**
	 * Generates the HTML for a intermediate label in the timeline (used to 
	 */
	public function timelineIntermediateLabel($message, $color = "purple") {
		return '<li class="time-label"><span class="bg-'.$color.'">'.$message.'</span></li>';
	}
	
	/**
	 * Generates the HTML for the timelabel ending section.
	 */
	public function timelineEnd($endingIcon = "clock-o") {
		return '<li><i class="fa fa-'.$endingIcon.'"></i></li></ul>';
	}
	
	/** 
	 * Generates the HTML for an simple timeline item without icon, just a message.
	 * @param String $message the message for the timeline item.	
	 */
	public function timelineItemWithMessage($title, $message, $style = CRM_UI_STYLE_INFO) {
		$content = $this->calloutMessageWithTitle($title, $message, $style);
		return '<li><div class="timeline-item">'.$content.'</div></li>';
	}
	
	/**
	 * Generates the HTML code for the given notification.
	 * @param $notification Array an associative array object containing the notification data.
	 * @return String a HTML representation of the notification.
	 */
	public function timelineItemForNotification($notification) {
		$type = $notification["type"];
		$action = isset($notification["action"]) ? $notification["action"]: NULL;
		$date = $notification["date"];
		$content = $notification["text"];
				
		$color = $this->notificationColorForNotificationType($type);
		$icon = $this->notificationIconForNotificationType($type);
		$title = $this->headerTextForNotificationType($type, $action);
		$buttonTitle = $this->actionButtonTextForNotificationType($type);

		return $this->timelineItemWithData($title, $content, $date, $action, $buttonTitle, $icon, CRM_UI_STYLE_SUCCESS, $color);
	}
	
	/**
	 * Generates the HTML code for the given event.
	 * @param event Array an associative array object containing the event data.
	 * @return String a HTML representation of the notification.
	 */
	public function timelineItemForEvent($event) {
		$type = "event";
		$action = isset($event["url"]) ? $event["url"]: "events.php?initial_date=".urlencode($event["start_date"]);
		$date = $event["start_date"];
		$content = $this->lh->translationFor("event_programmed_today").$event["title"];
				
		$color = $this->creamyColorForHexValue($event["color"]);
		$icon = $this->notificationIconForNotificationType($type);
		$title = $this->headerTextForNotificationType($type, $action);
		$buttonTitle = $this->actionButtonTextForNotificationType($type);
		return $this->timelineItemWithData($title, $content, $date, $action, $buttonTitle, $icon, CRM_UI_STYLE_DEFAULT, $color);
	}
	
	/**
	 * Generates the HTML code for the given notification.
	 * @param $notification Array an associative array object containing the notification data.
	 * @return String a HTML representation of the notification.
	 */
	public function getNotificationsAsTimeLine($userid) {
		$locale = $this->lh->getLanguageHandlerLocale();
		if (isset($locale)) { setlocale(LC_ALL, $locale); }
		$todayAsDate = strftime("%x");
		$todayAsText = $this->lh->translationFor(CRM_NOTIFICATION_PERIOD_TODAY)." ($todayAsDate)";
		
		// today
		$timeline = $this->timelineStart($todayAsText);
		// notifications for today
		$notifications = $this->db->getTodayNotifications($userid);
		// events for today
		$events = $this->db->getEventsForToday($userid);
		// module notifications for today
		$mh = \creamy\ModuleHandler::getInstance();
		$modNots = $mh->applyHookOnActiveModules(
			CRM_MODULE_HOOK_NOTIFICATIONS, 
			array(CRM_NOTIFICATION_PERIOD => CRM_NOTIFICATION_PERIOD_TODAY), 
			CRM_MODULE_MERGING_STRATEGY_APPEND);

		// generate timeline items for today.
		if (empty($notifications) && empty($events) && empty($modNots)) {
			$title = $this->lh->translationFor("message");
			$message = $this->lh->translationFor("no_notifications_today");
			$timeline .= $this->timelineItemWithMessage($title, $message);
		} else {
			// notifications
			foreach ($notifications as $notification) {
				$timeline .= $this->timelineItemForNotification($notification);
			}
			// events
			foreach ($events as $event) {
				$timeline .= $this->timelineItemForEvent($event);
			}
			if (isset($modNots)) { $timeline .= $modNots; }
		}
		
        // past week
        $pastWeek = $this->lh->translationFor(CRM_NOTIFICATION_PERIOD_PASTWEEK);
		$timeline .= $this->timelineIntermediateLabel($pastWeek);
		// notifications for past week.
        $notifications = $this->db->getNotificationsForPastWeek($userid);
		// module notifications for past week
		$modNots = $mh->applyHookOnActiveModules(
			CRM_MODULE_HOOK_NOTIFICATIONS, 
			array(CRM_NOTIFICATION_PERIOD => CRM_NOTIFICATION_PERIOD_PASTWEEK), 
			CRM_MODULE_MERGING_STRATEGY_APPEND);

		if (empty($notifications) && empty($modNots)) {
			$title = $this->lh->translationFor("message");
			$message = $this->lh->translationFor("no_notifications_past_week");
			$timeline .= $this->timelineItemWithMessage($title, $message);
		} else {
			foreach ($notifications as $notification) {
				$timeline .= $this->timelineItemForNotification($notification);
			}
			if (isset($modNots)) { $timeline .= $modNots; }
		}
		// end timeline
		$timeline .= $this->timelineEnd();
        
        return $timeline;
	}

	/** Statistics */

	protected function datasetWithLabel($label, $data, $color = null) {
		if (!isset($color)) $color = \creamy\CRMUtils::randomRGBAColor(false);
		return '{ label: "'.$label.'", 
			fillColor: "'.$this->rgbaColorFromComponents($color, "0.9").'",
	        strokeColor: "'.$this->rgbaColorFromComponents($color, "0.9").'",
	        pointColor: "'.$this->rgbaColorFromComponents($color, "1.0").'",
	        pointStrokeColor: "'.$this->rgbaColorFromComponents($color, "1.0").'",
	        pointHighlightFill: "#fff",
	        pointHighlightStroke: "'.$this->rgbaColorFromComponents($color, "1.0").'",
	        data: ['.implode(",", $data).'] },';
	}
		
	public function generateLineChartStatisticsData($colors = null) {
		// initialize values
		$labels = "labels: [";
		$datasets = "datasets: [";
		$data = array();
		$statsArray = $this->db->getLastCustomerStatistics();
		$customerTypes = $this->db->getCustomerTypes();

		// create the empty data fields.
		foreach ($customerTypes as $customerType) { $data[$customerType["table_name"]] = array(); }
		
		// iterate through all customers
		foreach ($statsArray as $obj) {
			// store labels
			$formattedDate = date("Y-m-d",strtotime($obj['timestamp']));
			$labels .= '"'.$formattedDate.'",';
				
			// store customer number
			foreach ($customerTypes as $customerType) { $data[$customerType["table_name"]][] = $obj[$customerType["table_name"]] or 0; }
		}
		// finish data
		$labels = rtrim($labels, ",")."],";
		$i = 0;
		foreach ($customerTypes as $customerType) { 
			$color = isset($colors[$i]) ? $colors[$i] : null;
			$datasets .= $this->datasetWithLabel($customerType["description"], $data[$customerType["table_name"]], $color); 
			$i++;
		}
		$datasets = rtrim($datasets, ",")."]";

		return $labels."\n".$datasets;
	}

	protected function pieDataWithLabelAndNumber($label, $number, $color = null) {
		if (!isset($color)) $color = \creamy\CRMUtils::randomRGBAColor(false);
		return '{ value: '.$number.', color: "'.$this->rgbaColorFromComponents($color, "1.0").'", highlight: "'.$this->rgbaColorFromComponents($color, "1.0").'", label: "'.$label.'" },';
	}
	
	public function generatePieChartStatisticsData($colors = null) {
		$result = "";
		$customerTypes = $this->db->getCustomerTypes();
		$i = 0;
		foreach ($customerTypes as $customerType) {
			$num = $this->db->getNumberOfClientsFromTable($customerType["table_name"]);
			$color = isset($colors[$i]) ? $colors[$i] : null;
			$result .= $this->pieDataWithLabelAndNumber($customerType["description"], $num, $color);
			$i++;
		}
		return $result;
	}

	public function generateStatisticsColors() {
		$num = $this->db->getNumberOfCustomerTypes();
		$result = array();
		for ($i = 0; $i < $num; $i++) { 
			$result[] = \creamy\CRMUtils::randomRGBAColor(false);
		}
		return $result;
	}

	public function rgbaColorFromComponents($components, $alpha = "1.0") {
		return "rgba(".$components["r"].", ".$components["g"].", ".$components["b"].", ".(isset($components["a"]) ? $components["a"] : $alpha).")";
	} 

	/** Utility functions */

	/**
	 * Generates a relative time string for a given date, relative to the current time.
	 * @param $mysqltime String a string containing the time extracted from MySQL.
	 * @param $maxdepth Int the max depth to dig when representing the time, 
	 *        i.e: 3 days, 4 hours, 1 minute and 20 seconds with $maxdepth=2 would be 3 days, 4 hours.
	 * @return String the string representation of the time relative to the current date.
	 */
	public function relativeTime($mysqltime, $maxdepth = 1) {
		$time = strtotime(str_replace('/','-', $mysqltime));
	    $d[0] = array(1,$this->lh->translationFor("second"));
	    $d[1] = array(60,$this->lh->translationFor("minute"));
	    $d[2] = array(3600,$this->lh->translationFor("hour"));
	    $d[3] = array(86400,$this->lh->translationFor("day"));
	    $d[4] = array(604800,$this->lh->translationFor("week"));
	    $d[5] = array(2592000,$this->lh->translationFor("month"));
	    $d[6] = array(31104000,$this->lh->translationFor("year"));
	
	    $w = array();
	
		$depth = 0;
	    $return = "";
	    $now = time();
	    $diff = ($now-$time);
	    $secondsLeft = $diff;
	
		if ($secondsLeft == 0) return "now";
	
	    for($i=6;$i>-1;$i--)
	    {
	         $w[$i] = intval($secondsLeft/$d[$i][0]);
	         $secondsLeft -= ($w[$i]*$d[$i][0]);
	         if($w[$i]!=0)
	         {
	            $return.= abs($w[$i]) . " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
	            $depth += 1;
	            if ($depth >= $maxdepth) break;
	         }
	
	    }
	
	    $verb = ($diff>0)?"":"in ";
	    $return = $verb.$return;
	    return $return;
	}
	
	private function substringUpTo($string, $maxCharacters) {
		if (empty($maxCharacters)) $maxCharacters = 4;
		else if ($maxCharacters < 1) $maxCharacters = 4;
		return (strlen($string) > $maxCharacters) ? substr($string, 0, $maxCharacters-3).'...' : $string;
	}

}	
	
?>