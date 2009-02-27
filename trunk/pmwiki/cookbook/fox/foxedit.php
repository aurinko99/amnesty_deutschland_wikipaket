<?php if (!defined('PmWiki')) exit();

/* foxedit.php, edit link markup to open page section or ptvs in edit form 
	an extension to PmWiki 2. Copyright Hans Bracker 2007.
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published
   by the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
   
   See Cookbook:FoxEdit for documentation and instructions.
   Include this script after including fox.php.
*/
$RecipeInfo['FoxEdit']['Version'] = '2008-07-14';

//default edit form pages
SDV($FoxEditPTVSectionForm, 'FoxTemplates.EditPTVForm');        
SDV($FoxEditPageSectionForm, 'FoxTemplates.EditSectionForm');

$FmtPV['$EditSource'] = '$GLOBALS["EditSource"]';
$FmtPV['$EditTarget'] = '$GLOBALS["EditTarget"]';
$FmtPV['$EditSection'] ='$GLOBALS["EditSection"]';

# make it possible to treat [[#section]] .... [[#sectionend]] as a PTV of name 'section'
$PageTextVarPatterns['[[#anchor]]'] =
  "/(\\[\\[#(\\w[-\\w]*)\\]\\](?: *\n)?)(.*?)(\\[\\[#\\2end\\]\\])/s";

//Handle section edit by setting page vars and loading edit form
$HandleActions['foxedit'] = 'FoxHandleEdit';

function FoxHandleEdit($pagename) {
	global $FoxAuth, $EditSource, $EditTarget, $EditSection, $InputValues, $FmtV, $FoxMsgFmt,
			$FoxEditPTVSectionForm, $FoxEditPageSectionForm,  $PageStartFmt, $PageEndFmt, $FoxPageEditFmt,
			$EnableFoxEditFormCheck, $QualifyPatterns, $FoxCheckError;
	//check if function is called by Fox  preview
	if ($_POST['preview'] || $FoxCheckError==1) 
		$args = $_SESSION['foxedit'][$pagename]; //fetch arguments from url of first foxedit call
	else {
		$args = RequestArgs(); //fetch GET or POST arguments
		FoxURLArgs($pagename); //set url arguments for use with preview
	}
	#echo "<pre>\$args "; print_r($args); echo "</pre>"; //DEBUG

	$EditSource = (isset($args['source'])) ? $args['source'] : $pagename;
	$target = (isset($args['target'])) ? $args['target'] : $EditSource;
	$prefix = (isset($args['prefix']) ? $args['prefix'] : '');
	$section = (isset($args['section']) ? urldecode($args['section']) : '');

	//open targetpage, get section, set InputValues for 'text' control
	$fulltarget = $target.$section;
	$EditTarget = MakePagename($pagename, $target);
	$page = RetrieveAuthPage($EditTarget, $FoxAuth, true);
	if (!$page) Abort( "?cannot read $page");
	$text = $page['text'];
	//check if we got fullpage edit
	if ($section=='') {
		$mode = 'section';
		if (PageExists($FoxEditPageSectionForm)) 
			$formpage = $FoxEditPageSectionForm; //default can open whole page for editing
	}		
	//check if we got anchored section, else section is ptv
	else {
		if (strstr($section,'#')) {
			$mode = 'section';
			$fts = FoxTextSection($page['text'], $fulltarget); 
			if ($fts['pos']==0) FoxAbort($pagename,"$[Error: cannot find section] '$EditSection'");
			$text = $fts['text'];
			$formpage = $FoxEditPageSectionForm;
		} else {
			$mode = 'ptv';
			$QualifyPatterns = NULL; //prevent substitutions by Qualify function 
			$text = PageTextVar($EditSource, $section);
			$formpage = $FoxEditPTVSectionForm;
		}
	}
	//check if we should use a specified form 
	if (isset($args['form'])) {
		$formpage = $args['form'];
		if ($section=='') $mode = ''; //no $mode since it could be whole page edit or ptv edit
	}
	//get text for insertion in editform textarea
	if ($InputValues['text']) 
		$EditText = $InputValues['text'];
	else 
		$EditText = $InputValues['text'] = str_replace('$','&#036;',htmlspecialchars($text,ENT_NOQUOTES));	

	//set global $EditSection
	$EditSection = $prefix.$section;

	//retrieve edit form from page or page section
	if ($formpage) {
		$formname = MakePagename($pagename,$formpage);
		if (PageExists($formname)) {
			$epage = RetrieveAuthPage($formname, 'read', true);
			if (!$epage) Abort( "?cannot read $formname");
			$eform = $epage['text']; 
			if ($eform=='') FoxAbort($pagename,"$[Error: cannot find edit form] $formpage");
			if (strstr($formpage,'#')) {
				$sect = FoxTextSection($epage['text'], $formpage);
				if ($sect['pos']==0) FoxAbort($pagename,"$[Error: cannot find edit template] $formpage");
				$eform = $sect['text'];
			}
			if (IsEnabled($EnableFoxEditFormCheck, 1)) {
				//check if edit form is the right type 
				if (preg_match("/\\(:input\\s+([^\\)]+)\\s*name\\=\\$:/",$eform)) { 
	   			if ($mode=='section') 
	    				Abort( "Error: wrong PTV name in foxedit link or edit form not suitable for PTV editing!");
	   				#FoxAbort($pagename, "Error: wrong PTV name in foxedit link or edit form not suitable for PTV editing!");
	   		}
	   		else if ($mode=='ptv') 
	   			Abort("Error: wrong section name in foxedit link or edit form not suitable for section editing!");
	   			#FoxAbort($pagename, "Error: wrong section name in foxedit link or edit form not suitable for section editing!");
			}
		}
	} 
	//we got no form page, so use hardcoded basic form, either for ptv or section edit
	else {
		if ($mode=='ptv') $eform = "(:fox eform ptvtarget=$target template=0 redirect=1:)";
		else $eform = "(:fox eform foxaction=replace put=overwrite target=$fulltarget :)"
				."\n(:foxtemplate \"{\$\$text}\":)";
		$eform .= "\n(:input hidden csum 'Section edited':)"
				."\n'''Editing $target section $section'''"
				."(:input defaults source={$EditSource}:)";
		if ($mode=='ptv') $eform .= Keep("<br /><textarea id='text' name='ptv_{$EditSection}'  class='inputtext' rows='12' cols='80'>$EditText</textarea><br />");
		else $eform .=	Keep("<br /><textarea id='text' name='text'  class='inputtext' rows='12' cols='80'>$EditText</textarea><br />");
		$eform .= "\n(:input submit post '$[Save]':) &nbsp; (:input submit cancel '$[Cancel]':)"
				."\n(:foxend eform:)";
	}
	$FmtV['$FoxEditForm'] = MarkupToHTML($pagename, $eform);
	$FoxPageEditFmt = '$FoxEditForm';
	$HandleEditFmt = array(&$PageStartFmt, &$FoxPageEditFmt, &$PageEndFmt);
	PrintFmt($pagename, $HandleEditFmt);
	exit;	
} //}}}	


Markup('foxeditlink','directives','/\\{\\[foxedit\\s*(.*?)\\s*\\]\\}/e',
        "FoxEditLinkMarkup(\$pagename, PSS('$1'))");
// make {[foxedit...]} link HTML
function FoxEditLinkMarkup ($pagename, $args) {
	$PageUrl = PageVar($pagename, '$PageUrl');
	$args = ParseArgs($args);
	$args[''] = (array)@$args[''];
	$section = (isset($args['section'])) ? $args['section'] : array_shift($args['']);
	$section = urlencode($section);
	$form = isset($args['form']) ? urlencode($args['form']) : '';
	$label = (isset($args['label'])) ? $args['label'] : array_shift($args['']);
	$target = (isset($args['target'])) ? MakePageName($pagename,$args['target']) : '';
	$source = (isset($args['source'])) ? MakePageName($pagename,$args['source']) : '';
	
	if(!$label) $label = XL('Edit');
	$url = "{$PageUrl}?action=foxedit".
			($source ? "&amp;source={$source}" : "").
			($target ? "&amp;target={$target}" : "").
			($form ? "&amp;form={$form}" : "").
			(isset($args['prefix']) ? "&amp;prefix={$args['prefix']}" : "").
			($section ? "&amp;section={$section}" : "");
	$out = "<a class='foxeditlink' href='{$url}'".
			(isset($args['title']) ? "title='{$args['title']}'" : "").
			(isset($args['tooltip']) ? "title='{$args['tooltip']}'" : "").
			" rel='nofollow'>{$label}</a>";
	return Keep($out);
} //}}}

//make argument array out of url parameters
function FoxURLArgs($pn) {
	@session_start();
	$_SESSION['foxedit'][$pn] = array();
	//get url
	if ($_SERVER["HTTPS"] == "on") $pageURL .= "https://";
	else $pageURL .= "http://";
	if ($_SERVER["SERVER_PORT"] != "80")
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	//use only argument string (form first ?), process it
	$args = strstr(strip_tags(urldecode($pageURL)),'?');
	$args = str_replace('?',' ',$args); 
	$args = str_replace('&',' ',$args);
	$args = htmlentities($args); //just to be safe...
	//make into array
	$args = ParseArgs($args);
	unset($args['#']);
	//save as SESSION var
	$_SESSION['foxedit'][$pn] = $args;
	return $args; 	
} //}}}
