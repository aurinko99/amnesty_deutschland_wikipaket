<?php if (!defined('PmWiki')) exit();
/*
+----------------------------------------------------------------------+
| Copyright 2008 Hans Bracker
| This program is free software; you can redistribute it and/or modify
| it under the terms of the GNU General Public License, Version 2, as
| published by the Free Software Foundation.
| http://www.gnu.org/copyleft/gpl.html
| This program is distributed in the hope that it will be useful,
| but WITHOUT ANY WARRANTY; without even the implied warranty of
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
| GNU General Public License for more details.
+----------------------------------------------------------------------+
| fox.php for PmWiki
| A form processing script for PmWiki 2.2.0 (beta)
| Instructions on how to use it see page Cookbook.Fox on pmwiki.org
+----------------------------------------------------------------------+
| Other contributors:
| * Nils Knappmeier (nk@knappi.org): original script adddeleteline2.php
| * John Rankin: AccessCode code from commentbox.php
| * Petko Yotov (2006): original deletelink directive
| * Patrick R. Michaud: permission check, input validation and other ideas
| * Feral: FoxFilter hook and other improvements
| * Stirling Westrup (2007): code overhaul with a rewrite of TemplateEngine
| *        and associated functions and many function improvements
+----------------------------------------------------------------------+
*/

$RecipeInfo['Fox']['Version'] = '2008-11-26';

$FmtPV['$FoxVersion'] = "'{$RecipeInfo["Fox"]["Version"]}'";

// switch on echos for debugging
$FoxDebug = 0; //echo function process: 1 - basic; 2 - page names, 3 - target array, 4 - early array,
					// 5 - template & new text, 6 - full text

//in case page name is not resolved
$pagename = ResolvePageName($pagename);

// auth permission level for adding and deleting posts.
// 'edit': needs edit permission.
// 'read': needs read permission (can post to edit protected pages)
// 'ALWAYS':no permission needed, can also post to 'read' protected pages.
SDV($FoxAuth, 'edit');
SDV($FoxConfigPageFmt, "$SiteAdminGroup.FoxConfig"); //default config page for page permission patterns
SDV($FoxTemplatePageFmt, "$SiteGroup.FoxTemplates#null"); //default page + section for templates (null is dummy)
SDV($EnableFoxUrlInput, false); //set to true to allow input via url parameters (php $_GET array)
SDV($EnablePostDirectives, false); //set to true to allow posting of directives of form (: :)
SDV($EnableAccessCode, false);  //set to true to enable accesscode check (needs access code form fields as well)
SDV($EnableFoxDefaultMsg, true);
//to stop fatal php timeout errors set two seconds less than php.ini: max_execution_time 30sec default
SDV($FoxProcessTimeMax, 28); //processing time limit in seconds for UpdatePages process.
SDV($FoxClearPTVFmt, 'NULL'); //string used as input to clear a PTV.
SDV($EnableFoxPTVDelete, false); //set to true to allow PTVs to be deleted by changing value for a PTV to $FoxPTVDeleteKey
SDV($FoxPTVDeleteKey, 'ERASEPTV'); //string to use for ersaing (deleting) PTVs, if $EnableFoxPTVDelete = true;
SDV($FoxCheckErrorMsg, '$[Please enter valid input!]');

# Default posting permission patterns for allowed and prohibited pages to post to
# (use of wildcards * and $ is possible, use of - at start will prohibit posting to that page)
# A prohibiting pattern overrules an allowing pattern of the same match!
# If you want to post to a page in Site group, you need to unset the prohibiting pattern for $SiteGroup first.
SDVA($FoxPagePermissions, array(
	'$SiteGroup.DUMMY'  => 'all', //dummy entry needed so permissions will be checked!
	'$SiteGroup.*'      => 'none',
	'$SiteAdminGroup.*' => 'none',
	'PmWiki.*'          => 'none',
	'*.GroupFooter'     => 'none',
	'*.GroupHeader'     => 'none',
	'*.GroupAttributes' => 'none',
));	
/*// examples of page permission patterns:
	//'*.*' => 'all',     // all Fox actions allowed on all pages
	//'Comments.*'                => 'add,delete', // adding and deleting posts in Comments group
	//'Comments.{$Group}-{$Name}' => 'add,delete', // adding and deleting posts in comments group for pages with name Group-Name
	//'*.{$Name}-Comment' => 'add',  // adding posts for pages with -Comment suffix
	//'{$FullName}-Talk' => 'add',   // adding posts for pages in current group with -Talk suffix

	// The following patterns for 'current page' and 'current group' could be exploited to post to edit protected pages
	// There is no way to define 'current' strictly, even though it appears as if it has been defined.
	//'{$Group}.{$Name}' => 'add', // adding to 'current' page
	//'{$Group}.*' => 'add',       // adding to pages in 'current' group
*/

##security check for FoxConfig page location
if ($SiteGroup != $SiteAdminGroup)
if (PageExists('$SiteGroup.FoxConfig') && !PageExists('$SiteAdminGroup.FoxConfig'))
	echo "Security update: Fox detected page <a href='$ScriptUrl?n=$SiteGroup.FoxConfig'>$SiteGroup.FoxConfig</a><br />
	Please move the page to <a href='$ScriptUrl?n=$SiteAdminGroup.FoxConfig?action=edit'>$SiteAdminGroup.FoxConfig</a>! ";

// Search path for fox.css files, if any.
if (!isset($FarmPubDirUrl)) $FarmPubDirUrl = $PubDirUrl;
SDV($FoxPubListFmt, array (
       "pub/fox/fox.css"        => "$PubDirUrl/fox/fox.css",
       "$FarmD/pub/fox/fox.css" => "$FarmPubDirUrl/fox/fox.css" ));

# load special styles for edit/delete button and links and for message classes
foreach((array)$FoxPubListFmt as $k=>$v) {
  if (file_exists(FmtPageName($k,$pagename))) {
      $HTMLHeaderFmt['fox'][] =
      	"<link rel='stylesheet' type='text/css' href='$v' media='screen' />\n";
  		break;
  }
}

//conditionals for edit forms
$Conditions['foxpreview'] = '(boolean)$_POST["preview"]';
$Conditions['foxcheck'] = '(boolean)$GLOBALS["FoxCheckError"]';


//(:fox formname ....:) main form markup, starts a fox form
Markup('fox','directives','/\(:fox ([\\w]+)\\s?(.*?):\)/ei',"Keep(FoxMarkup(\$pagename, PSS('$1'), PSS('$2')))");
## Creates the HTML code for the (:fox name [placement] [parameters]:) directive
function FoxMarkup($pagename, $name, $args) {
	$PageUrl = PageVar($pagename, '$PageUrl');
	static $cnt = 0; $cnt++;
	$defaults = array();
	$args = ParseArgs($args);
	if (isset($args[''][0])) $args['put'] = $args[''][0];
	$opt = array_merge($defaults, $args);
	if (isset($opt['redirect'])) { $opt['redir'] = $opt['redirect']; unset($opt['redirect']);}
	$javacheck = (isset($opt['formcheck']) ? FoxJSFormCheck($opt['formcheck']) : '');
	$out = $javacheck;
	$out.= "<form  name='$name' action='{$PageUrl}' method='post' ".
		($opt['upload']==1||$opt['uptarget'] ? "enctype='multipart/form-data'" : "").
		(isset($opt['formcheck']) ? "onsubmit=\"return checkform(this);\">" : ">").
		"\n<input type='hidden' name='foxpage' value='$pagename' />".
		"\n<input type='hidden' name='action' value='foxpost' />".
		"\n<input type='hidden' name='foxname' value='$name' />"; 
	foreach ($opt as $key => $val) {
		if(!is_array($val))
			$out.= "\n<input type='hidden' name='".$key."' value='".$val."' />";
	}
	
	return Keep($out);
} //}}}

//(:foxtemplate "...":) (:foxpreviewtemplate "...":)
Markup('foxtemplate','<input','/\\(:fox(display|preview|)template\\s+"(.*?)\\s*":\\)/e',
			"Keep(FoxTemplateMarkup(PSS('$1'), PSS('$2')))");
# Creates the HTML code for (:foxtemplate "templatestring":) as hidden input form field
# $template is the template string
function FoxTemplateMarkup($type='', $template) {
    $t = ($type=='display' || $type=='preview') ? 'preview' : '';
    return '<input type="hidden" name="fox'.$t.'template" value="'.htmlspecialchars($template).'"/>';
} //}}}

//(:fox-post ...:) (:fox-add ...:) (:fox-copy ...:) (:fox-replace ...:) (:fox-ptv ...:)
//(:foxpost ...:) (:foxadd ...:) (:foxcopy ...:) (:foxreplace ...:) (:foxptv ...:)
# markup for multi-page processing, can be used multipe times in form
Markup('foxpost','directives','/\(:fox-?(post|add|copy|replace|ptv|mail)\\s+(.*?):\)/ei',"Keep(FoxPostMarkup(\$pagename, PSS('$1'), PSS('$2')))");
# create hidden HTML input tags for template and target pages, for foxpost and foxcopy markup
function FoxPostMarkup($pagename, $act, $args) {
	if ($act=='post') $act = 'add'; //'post' deprecated in favour of 'add'
	$defaults = array('put'=>'','mark'=>'', 'endmark'=>'', 'ptvfields'=>'', 'ptvfmt'=>'','ptvclear'=>'','foxtemplate'=>''); //placeholders
	$put_names = array('top','bottom','prepend','append','below','above','belowform','aboveform','string','marktomark');
	$args = ParseArgs($args,'(?>([-\\w]+(?:\.[-\\w]+)?(?:\\w#[-.\\w]*)?(?:\\#[-.\\w]*)?)(?:=&gt;|[=:]))');
	$opt = array_merge($defaults, $args);
	$opt[''] = (array)@$opt[''];
	$opt['foxaction'] = $act;
	foreach($opt[''] as $i=>$p) {
		if ($p =='') continue;
		if (in_array($p, $put_names) && !isset($args['put']))
			$opt['put'] = $p;
		else $param[] = $p;
	}
	if (isset($param)) $opt['foxparam'] = implode(',',$param);
	if ($act=='ptv') {
		if (isset($opt['foxparam']) && !(isset($opt['target']) OR isset($opt['ptvtarget'])))
			$opt['target'] = $opt['foxparam'];
		if (isset($opt['ptvtarget'])) $opt['target'] = $opt['ptvtarget'];
	}	
	unset($opt[''],$opt['#']);
	$keys = $opt;
	foreach($keys as $k=>$v)
		if (preg_match('/(^[A-Z0-9].*)|(^\\w+?\\..+)/',$k)) unset($keys[$k]);
	unset($keys['target'], $keys['template']);
	$out = '';
	foreach ($opt as $key=>$val) {
		//template=>target parameters. template in key>=val must start Upper case or numeral or contain a dot
		if (preg_match('/(^[A-Z0-9].*)|(^\\w+?\\..+)/',$key)) {
			$out .= "<input type='hidden' name=':template[]' value='$key'/>";
			$out .= "<input type='hidden' name=':target[]' value='$val'/>";
			foreach($keys as $d=>$val)
				$out .= "<input type='hidden' name=':".$d."[]' value='".$val."'/>";
		}
		//target= parameter
		elseif ($key=='target') {
			$targets = explode(",", $val); //can be comma separated list
			$templates = array();
			if(isset($opt['template'])) {
				$templates = explode(",", $opt['template']); //can be comma separated list
				//use last template for any missing templates
				while( count($templates)<count($targets) )
					$templates[] = @end($templates);
			}
			//make :target[] :template[] array elements, add other keys to get proper index mapping
			foreach($targets as $i => $tgt) {
				if (!isset($templates[$i])) $templates[$i] = '';
				$out .= "\n<input type='hidden' name=':target[]' value='{$tgt}'/>";
				$out .= "<input type='hidden' name=':template[]' value='{$templates[$i]}'/>";
				foreach($keys as $d=>$v)
					$out .= "<input type='hidden' name=':".$d."[]' value='".htmlspecialchars($v, ENT_NOQUOTES)."'/>";
			}
		}
	}
	return $out;
} //}}}

// (:foxcheck name [match='wikiwildcardpattern'] [regex='regexpattern'] [if='condition'] [msg='error message'] :)
Markup('foxcheck','directives','/\(:fox-?(check)\\s+(.*?):\)/ei',"Keep(FoxCheckMarkup(\$pagename, PSS('$1'), PSS('$2')))");
function FoxCheckMarkup($pagename, $act, $args) {
	static $idx = 0;
	$opt = ParseArgs($args);
	$opt[''] = (array)$opt[''];
	$opt['name'] = isset($opt['name']) ? $opt['name'] : array_shift($opt['']);
	if (!isset($opt['match']) && $opt['']) $opt['match'] = array_shift($opt['']); 
	unset($opt['#'], $opt['']);
	$out = '';
	foreach ($opt as $key=>$val) {
		if ($val=='') continue;
		$out .= "<input type='hidden' name='chk_".$key."[{$idx}]' value='".$val."'/>";
	}
	$idx++;
	return $out;
}

// (:foxmessage [form] [name]:) (:foxdisplay [form] [name]:)
Markup('foxdisplaymessage','directives','/\\(:fox-?(message|display|preview)s?\\s*(.*?)\\s*:\\)/e',
			"FoxDisplayMarkup(\$pagename, PSS('$1'), PSS('$2'))");
function FoxDisplayMarkup($pagename, $mode, $args) {
	global $FoxMsgFmt, $FoxDisplayFmt;
	if (!$FoxMsgFmt && !$FoxDisplayFmt) return '';
	$opt = ParseArgs($args);
	$opt[''] = (array)@$opt[''];
	$form = isset($opt['form']) ? $opt['form'] : array_shift($opt['']);
	$name = isset($opt['name']) ? $opt['name'] : array_shift($opt['']);
	$msg = '';
	if($FoxMsgFmt) { 
		if (!$form && !$name) $msg = implode("\\\\\n", $FoxMsgFmt); //show all messages
		elseif ($form==@$_REQUEST['foxname']) {
			if (isset($name) ) $msg = @$FoxMsgFmt[$name]; //show error message from check 'name'
			elseif (@$opt['list']=='nocheck')  //show non-name messages
				foreach($FoxMsgFmt as $k => $v) { if (is_int($k)) $msg .= $v; }
			elseif (@$opt['list']=='check')  //show name messages
				foreach($FoxMsgFmt as $k => $v) { 
					if (is_int($k)) continue;
					$msg .= $v; 
				}				
			else $msg = implode("\\\\\n", $FoxMsgFmt); //show all messages
		} 
	}
	if ($mode=='display' || $mode=='preview' || @$opt['list']=='display') 
		if (!$form || $form==@$_REQUEST['foxname']) 
			if(!is_array($FoxDisplayFmt))
				$msg = $FoxDisplayFmt;
			else if ($form==@$_REQUEST['foxname'] && isset($name))
				$msg = @$FoxDisplayFmt[$name];
	$out = MarkupToHTML($pagename, $msg); 
	//strip p tags from beginning and end, trim end space
	$out = rtrim(preg_replace("/^<p>(.*?)<\\/p>$/s","$1", $out));
	return Keep($out);
} //}}} 

# (:foxend name:)
Markup('foxendform','directives','/\(:foxend(\\s[\\w]+):\)/', "</form>");
# (:foxprepend:) and (:foxappend:) just vanish because they are only used later in  FoxInsertText
Markup('foxaprepend','directives','/\(:fox(ap|pre)pend\\s*(.*?)\\s*:\)/','');
# (:foxallow:) for permission check: if present will grant page permission
Markup('foxallow','directives','/\(:foxallow\\s*(.*?)\\s*:\)/','');
# #foxbegin# and #foxend# invisible markers used by foxdelete links and buttons
Markup('foxentry','<fulltext','/#fox(begin|end)( \w+)?#/','');

# add FoxEditTemplate to $EditFunctions for FoxHandlePost (foxaction newedit)
if($action=='edit' && @$_REQUEST['foxtemptext']==1)
   array_unshift($EditFunctions, 'FoxEditTemplate');

## provide page text for ?action=edit&foxtemptext=1
function FoxEditTemplate($pagename, &$page, &$new) {
   if (@$new['text'] > '') return '';
   if (@$_REQUEST['foxtemptext'])  {
      if ($_SESSION["FoxTempPageText"] > '') $new['text'] = $_SESSION["FoxTempPageText"];
         return '';
   }
} //}}}

# add action foxpost
$HandleActions['foxpost'] = 'FoxHandlePost';

## Main function called with action=foxpost
function FoxHandlePost($pagename, $auth) {
   global $InputValues, $EnableFoxUrlInput, $EnableFoxDefaultMsg, $EnablePostDirectives,
   		$IsPagePosted, $FoxDebug, $FmtV;
	FoxTimer($pagename, 'FoxHandlePost: begin');

   //get arguments from POST and GET
   if ($EnableFoxUrlInput==true)
   	$fx = FoxRequestArgs();
	else $fx = FoxRequestArgs($_POST);
	
	//get arguments from FILES	
	foreach($_FILES as $n => $upfile) { 
		if ($upfile['name']=='') continue;
		#$fx['UPFILES'][$n] = $upfile;
		foreach($upfile as $k => $v)
			if (!$fx[$n.'_'.$k]) $fx[$n.'_'.$k] = $v;
		//save file extension
		$fx[$n.'_ext'] = end(explode(".", $upfile['name']));
	}	
	
	//store current values to redisplay, in case we abort.
	foreach ($fx as $k=>$v) {
		if (is_array($v)) foreach ($v as $kk=>$vv)
			$InputValues[$k][$kk] = htmlspecialchars($vv,ENT_NOQUOTES);
		else $InputValues[$k] = htmlspecialchars($v,ENT_NOQUOTES);
	}	

	//abort if non-permitted input, i.e. if GET input is not allowed
	if ($fx['action']!='foxpost') FoxAbort($pagename, "$[Error: input not permitted]");

	//use foxpage as abort target.
	if(isset($fx['foxpage']))
		$pagename = $fx['foxpage'];

	//initialise
	$redirname = $pagename;

	//preprocess fields with FoxFilter, which calls external filter functions
   if (isset($fx['foxfilter']))
      FoxFilter($pagename, $fx);
      
	//initialising preview
   if (isset($fx['preview']) || isset($fx['foxdisplay'])) { 
   	//see if we got a preview template
   	if ($fx['foxpreviewtemplate']) { $fx['foxtemplate'] = $fx['foxpreviewtemplate']; }
   	else if ($fx['previewtemplate']) { $fx['template'] = $fx['previewtemplate']; }
   	//for cases not called by a foxedit form
   	if (!isset($_SESSION['foxedit'][$pagename])) { 
   		$fx['foxaction'] = 'display';
   		$fx['target'] = $pagename;
   		unset($fx['redir']);
   	}
   }

	//DEBUG//
	if($FoxDebug>2) { echo "<pre>\$fx "; print_r($fx); echo "</pre>"; }

   //make foxgroup input suitable for group name, add foxgrouptitle to preserve original input
   if (isset($fx['foxgroup'])) {
   	$fx['foxgrouptitle'] = $fx['foxgroup'];
   	$fx['foxgroup'] = FoxWikiWord($fx['foxgroup']);
   }

	//sanitize posted directives and markup expressions
	if ($EnablePostDirectives==false)
		FoxDefuseMarkup($pagename, $fx);

	//do {$$var} input field replacements and process {$$(expre ...)} markup expressions
	FoxInputVarReplace($pagename, $fx);

	//make ptv array from ptv_ fields
	FoxPTVFields($pagename, $fx);
   
	//check form input as set by (:foxcheck ..:) markup
	FoxInputCheck($pagename, $fx);
	
	//build list of targets as array with associated parameters
	$tar = FoxTargetList($pagename, $fx);
	
	//DEBUG//
	if($FoxDebug>1) { echo "<pre>\$tar "; print_r($tar); echo "</pre>"; }

   //check for foxnotify input to do notifications (needs FoxNotify installed)
   if (isset($fx['foxnotify'])) {
      global $FoxNotifyLists, $FoxNotifyListsGroup; 
      $FoxNotifyLists = array();
      if (is_array($fx['foxnotify'])) {
         foreach($fx['foxnotify'] as $n)
            $FoxNotifyLists[] = $FoxNotifyListsGroup.".".$n;
      }
      else $FoxNotifyLists[] = $FoxNotifyListsGroup.".".$fx['foxnotify'];
   }

	//set $redirectname for redirect
	if (isset($fx['redir'])) 
		$urlfmt = FoxRedirectFmt($pagename, $fx, $tar, $fx['redir']);

	if ($fx['urlfmt']) $urlfmt = $fx['urlfmt'];

	//cancel
	if (isset($fx['cancel'])) {
		if (isset($fx['cancelredirect']))
			$urlfmt = FoxRedirectFmt($pagename, $fx, $tar, $fx['cancelredirect']);
		else $urlfmt = '';
		Redirect($redirname, $urlfmt);
		exit; 
	}
		
   //check various genaral security restrictions (page permissions are checked later)
   //check for and possibly defuse posted markup 
   FoxSecurityCheck($pagename, $tar, $fx);

	//go to main page processing:
	//update pages (add, copy, replace, ptv-update...)
	$counter = FoxUpdatePages($pagename, $fx, $tar);
	
	//upload files
	if ($fx['uptarget'])
		FoxPostUpload($pagename, $fx);
	
	$xtime = FoxTimer($pagename,'FoxHandlePost: end');
	if( $EnableFoxDefaultMsg==1 && $counter>1 )
		$FoxMsgFmt[] = "$counter pages processed in $xtime seconds";

	if(!isset($urlfmt)) {
		FoxFinish($redirname, $fx, '');
	}
	else Redirect($redirname, $urlfmt);
} //}}}

# create url for redirections
function FoxRedirectFmt ($pagename, $fx, $tar, $redir) {
	global $EnablePathInfo, $ScriptUrl;
	$arg = ''; $anch = '';
	if (substr($redir,0,4)=="http") 
		$urlfmt = $redir;
	else {
		if($redir==='1') {
			$last = end(array_keys($tar));
			$pname = $tar[$last]['target'];
		}
		else {
			$rr = explode("?", $redir); 
			$aa = explode("#", $rr[0]);
			if ($aa[1]) $anch = "#".$aa[1];
			$pname = FoxGroupName($pagename, $fx, $rr[0]);
			if ($rr[1]) $arg = "?".$rr[1]; 
		}
		$pname = str_replace(".","/",$pname);
		//set urlfmt for redirect
		$urlfmt = (IsEnabled($EnablePathInfo, 0) ? $ScriptUrl."/".$pname.$anch.$arg : $ScriptUrl."?n=".$pname.$anch.$arg );
	}
	return $urlfmt;	
} //}}}

## create arrays from special fields & build target array
function FoxTargetList ($pagename, &$fx) {
	global $FoxDebug; if($FoxDebug) echo "<br /> TARGETLIST>"; //DEBUG//
	//assign current page as target if no target is specified, but template is given. 
	//exclude this for dangerous actions to prevent overwriting form page  
	if (!isset($fx[':target']) &&  !isset($fx['target']) && !isset($fx['newedit'])) 
		if ((isset($fx['template']) && !$fx['template']==0) || isset($fx['foxtemplate']))
			if ($fx['foxaction']!='copy' && !($fx['foxaction']=='replace' && $fx['put']=='overwrite'))
				$fx['target'] = array($pagename);
	if (isset($fx['target']) && !isset($fx['template']) && !isset($fx['foxtemplate']) && !isset($fx['foxpreviewtemplate']))
		unset($fx['target']);
	if (isset($fx['foxcopy'])) 
		if($fx['foxcopy']==1) $fx['foxaction'] = 'copy';
		
	//create upload target
	if (isset($fx['upload']) || isset($fx['uptarget'])) {
		if (@$fx['uptarget']) $fx['uptarget'] = FoxGroupName($pagename, $fx, $fx['uptarget']);
		elseif ($fx['upload']==1 && !$fx['target']=='') 
			if (!strstr(',',$fx['target'])) $fx['uptarget'] = FoxGroupName($pagename, $fx, $fx['target']);
			else $fx['uptarget'] = $pagename;
	}

	//create newedit target, will be used as last element in target array
	$ntar = array();
	if (isset($fx['newedit'])) {
		$ntar[0]['target'] = $fx['newedit'];
		$ntar[0]['foxaction'] = 'newedit';
		if ($fx['template']) $ntar[0]['template'] = end(explode(',',$fx['template']));
		elseif ($fx['foxtemplate']) $ntar[0]['template'] = 'foxtemplate';
		else $ntar[0]['template'] = 0;
	} 	
		
	//make arrays from keys fields which could be arrays or lists for making arrays
	//assign values to targets, csv lists: if no more value use previous one
	$star = array();
	$keys = array('foxaction','target','template','put','mark','endmark',
						'foxsuccess','foxfailure','foxtemplate');
	if (isset($fx['target']) && $fx['target']!='') {
		foreach ($keys as $n) {
			if(isset($fx[$n]) && $fx[$n]!='') {
				$kp[$n] = array();
				if(is_array($fx[$n])) $kp[$n] = $fx[$n];
				elseif ($n=='target' || $n=='template' || $n=='put') 
					$kp[$n] = explode(",", $fx[$n]);
				else $kp[$n] = array($fx[$n]);
			}
		}
		unset($keys['target']);
		foreach($keys as $k) {
			if (isset($kp['target'])) {
				foreach($kp['target'] as $i => $tg) {
					$star[$i]['target'] = $tg;
					if (isset($kp[$k][$i])) $star[$i][$k] = $kp[$k][$i];
					elseif (isset($kp[$k][$i-1])) $star[$i][$k] = $kp[$k][$i] = $kp[$k][$i-1];
				}
			}
		}
		
	} ##	if($FoxDebug>2) { echo "<pre>\$star "; print_r($star); echo "</pre>"; }		
	
	if ($fx['ptvupdate']==1)
		$fx['ptvtarget'] = $fx['target'];
	
	//create target array from ptvtarget
	$ptar = array();
	if (isset($fx['ptvtarget'])) {
		$fx['ptvtarget'] = explode(',',$fx['ptvtarget']);
		foreach($fx['ptvtarget'] as $i => $tgt) {
			$ptar[$i]['ptvtarget'] = $ptar[$i]['target'] = $tgt;
			$ptar[$i]['template'] = 'foxptvupdate';
			foreach(array('ptvfields','ptvclear','ptvfmt') as $n)
				if (isset($fx[$n])) $ptar[$i][$n] = $fx[$n];
		}
	}  ##	if($FoxDebug>2) { echo "<pre>\$ptar "; print_r($ptar); echo "</pre>"; }

	//create target array from fx[': '] fields
	$xtar = array();
	foreach($fx as $k => $ar) {
		if ($k{0}!=':') continue;
		foreach($ar as $i => $v) {
			$n = substr($k,1); // remove leading :
			if ($v!='')
				$xtar[$i][$n] = $v; // set only non-empty values
		}
	}	
	//remove entries with missing target silently
	foreach ($xtar as $i => $targ)
		if ($targ['target']=='') unset($xtar[$i]);
		
	## if($FoxDebug>2) { echo "<pre>\$xtar  "; print_r($xtar); echo "</pre>"; }

	//merge input targets. Process 1.extended markup targets, 2.std markup targets, 3.std ptv targets, 4. newedit target
	$tar = array_merge($xtar, $star, $ptar, $ntar);
	
	if($FoxDebug>3) { echo "<pre>\$tar 1 "; print_r($tar); echo "</pre>"; }
	
	//set any default parameters
	FoxTargetDefaults($fx, $tar);	

	//merge ptvtargets with targets for faster page processing
	foreach($tar as $i => $a) {
		if(!isset($tar[$i]['ptvtarget'])) continue;
		foreach($tar as $j => $b) {
			if(isset($tar[$j]['ptvtarget'])) continue;
			if ($tar[$j]['target'] == $tar[$i]['ptvtarget']) {
				$tar[$i] = array_merge($a, $b);
				unset($tar[$j]);
				continue 2;
			}
		}
	}

	//make target names
	foreach($tar as $i => $ar) {
		$tar[$i]['fulltarget'] = $ar['target']; // preserve full name
		$tar[$i]['target'] = FoxGroupName($pagename, $fx, $ar['target']);
		if($FoxDebug>2) echo " tgt=".$tar[$i]['target'];
		if (isset($ar['ptvtarget'])) {
			$tar[$i]['ptvtarget'] = FoxGroupName($pagename, $fx, $ar['ptvtarget']);
			if($FoxDebug>2) echo " ptv=".$tar[$i]['target'];
			if ($fx['ptvupdate']==1)
				if (PageExists($tar[$i]['target']))
					$tar[$i]['foxaction'] = 'ptvupdate';
		}
	}
	sort($tar);
	return $tar;
} //}}}

## set defaults for target array
function FoxTargetDefaults( $fx, &$tar ) {
	foreach($tar as $i => $targ) {
		//set 'template'
		if (isset($fx['foxtemplate']) && ($targ['template']=='' || !isset($targ['template'])))
				$tar[$i]['template'] = 'foxtemplate'; //used by LoadTemplate
		//set 'foxaction'
		if (!isset($targ['foxaction'])) $tar[$i]['foxaction'] = 'add';
		//set 'put' 
		if (!isset($targ['put']) && $tar[$i]['foxaction']=='add') $tar[$i]['put'] = 'bottom';
		elseif (!isset($targ['put']) && $tar[$i]['foxaction']=='replace') $tar[$i]['put'] = 'string';
		//set 'ptvtarget'
		if ($tar[$i]['foxaction']=='ptv') {
			if ($targ['target']) $tar[$i]['ptvtarget'] = $targ['target'];
			elseif ($targ['ptvtarget']) $tar[$i]['target'] = $targ['ptvtarget'];
			$tar[$i]['template'] = 'foxptvupdate';
		}
	}
} //}}}

## check 'foxgroup' and set targetname,
function FoxGroupName($pagename, $fx, $name) {
	global $FoxDebug; if($FoxDebug) echo "<br /> GROUPNAME>".$name; //DEBUG//
	if ($name{0}=='#') $name = PageVar($pagename,'$Name');
	if (!isset($fx['foxgroup'])) {
		$pname = MakePageName($pagename, $name);
		if (substr($pname, -1)==".") return '';
		return $pname;
	}
	else $group = $fx['foxgroup'];
	// exception: for 'escaped' target name ignore foxgroup
	if(substr($name,0)=="\\") {
		$name = str_replace("\\","",$name);
		return MakePageName($pagename, $name);
	}
	else $name = FoxWikiWord($name);
	return $group.'.'.$name;
} //}}}

## processing of target pages for add, replace, copy and ptvupdate actions
function FoxUpdatePages($pagename, $fx, $tar) { 
	global $FoxDebug; if($FoxDebug) echo " UPDATEPAGES> "; //DEBUG//
	global $FoxAuth, $EnableBlocklist, $FoxMsgFmt, $EnableFoxDefaultMsg, $ScriptUrl, $FmtV, 
			$IsPagePosted, $Now, $ChangeSummary, $EditFunctions, $EnablePost, $FoxDisplayFmt, $InputValues;
	if(!isset($tar[0])) FoxAbort($pagename, "$[Error: no target specified!]");

	//process each target page in turn. $targ is passed on to other functions called
	$counter = 0;
	foreach((array)$tar as $idx => $targ) { 
		$tgtname = $targ['target'];
		if ($tgtname=='') FoxAbort($pagename, "$[Error: no target specified or target not found!]");
		StopWatch('FoxUpdatePages: begin $tgtname');
		//DEBUG//
		if($FoxDebug>1) echo "<br /> TARGET=".$tgtname;//DEBUG//	

		$targ['taridx'] = $idx; //set a target index, used by FoxValue
		$act = $targ['foxaction']; //set fox-action

		//check page permission for the fox action
		if (FoxPagePermission($pagename, $act, $tgtname, $fx) == false) continue;
		
		//get template
		$template = FoxLoadTemplate($pagename, $targ, $fx);
			//DEBUG//
			if($FoxDebug>1) echo " ACTION>".$act. " INDEX=$idx";//DEBUG//
			if($FoxDebug>4) echo "<pre>TEMPL=".$template."</pre>";//DEBUG//

		//do var replacements on template string, skip for 'copy'
		if($template && ($act=='add' || $act=='replace' || $act=='display'|| $act=='newedit'))
			$template = FoxTemplateEngine($tgtname, $template, $fx, $targ, '','FoxUpdatePages');  //decode the template

			//DEBUG//
			#if($FoxDebug>4) echo "<pre>NEW ENTRY=".$template."</pre>";//DEBUG//

		//display only, no text save to target. Needs (:foxdisplay...:) as page location for output
		if ($act == 'display') {
			$FoxDisplayFmt = $template;
			//break out of this target page process, no need to open page
			continue;
		}
		if (isset($fx['preview']) && isset($_SESSION['foxedit'][$pagename])) { 
			$FoxDisplayFmt = '';
			if($template && ($act=='add' || $act=='replace')) {
				$InputValues['text'] = $template;
				$FoxDisplayFmt = $template;
			}
			if(function_exists(FoxHandleEdit))
				FoxHandleEdit($pagename);
			continue;
		}	

		//email notify only, no text save to target. Needs foxnotify.php installed.
		//target is page with list of email addresses. template is page with email template.
		//email list target needs to be in FoxNotifyListsGroup
		if ($act == 'mail') {		
			if(function_exists(FoxNotifyUpdate)) {
				global $FoxNotifyLists, $FoxNotifyListsGroup, $FoxNotifyTemplatePageFmt;
				if (PageVar($tgtname, '$Group') != $FoxNotifyListsGroup) 
					$FoxMsgFmt[] = "Error: Target list is not in the group for FoxNotifyLists"; 
				else {
					$IsPagePosted = 1;
					$FoxNotifyTemplatePageFmt = MakePageName($pagename, $targ['template']);
					$FoxNotifyLists[] = $FoxNotifyListsGroup.".".PageVar($tgtname, '$Name');
					register_shutdown_function('FoxNotifyUpdate', $pagename, getcwd(), $fx);
					$counter++;
					if (isset($targ['foxsuccess'])) $FoxMsgFmt[] = $targ['foxsuccess'];
					elseif (isset($fx['foxsuccess'])) $FoxMsgFmt[] = $fx['foxsuccess'];
					elseif ($EnableFoxDefaultMsg==1)	$FoxMsgFmt[] = "$[Successful post to] [[$tgtname(?action=browse)]]";
				}
			}
			else $FoxMsgFmt[] = "Error: Could not send mail. FoxNotify is not installed!";
			continue;
		}
		//open target page
		Lock(2);
		$page = RetrieveAuthPage($tgtname, $FoxAuth, true);
		if (!$page) Abort("?cannot read $pagename");
		$pgtxt = $text = isset($page['text']) ? $page['text'] : '';
		$toptxt = $bottxt = ''; 
		//extract anchored sections
		if (strstr($targ['fulltarget'],'#')) {
			$section = FoxTextSection($pgtxt, $targ['fulltarget']);
			//break off this page process if specified target section is not found
			if ($section['pos']==0) { 
				$FoxMsgFmt[] = "$[Error: could not find target section on] $tgtname";
				continue;
			}
			$text = $section['text'];
			$tpos = $section['pos'];
			$toptxt = substr($pgtxt, 0, $tpos);
			$bottxt = substr($pgtxt, $tpos + strlen($text));
		}
		$text = trim($text);
		$newtext = $text;
		//do one or the other
		switch ($act) {
			case 'add' :    $newtext = FoxAddText( $pagename, $text, $template, $fx, $targ ); break;
			case 'replace': $newtext = FoxReplaceText( $pagename, $text, $template, $fx, $targ ); break;
			case 'copy' : 	 $newtext = "\n".$template."\n"; break;
			case 'newedit': FoxNewEdit($pagename, $template, $fx, $targ); //ends process
		}
		//update any PTVs
		if(isset($targ['ptvtarget']))
			$newtext = FoxPTVAddUpdate($pagename, $newtext, $fx, $targ );
			
		//if we got changes update page
		if($newtext!=$text) {
			$text = trim($newtext);
			//recombine pagetext sections
			if (!$toptxt=='') $text = trim($toptxt)."\n".$text;
			if (!$bottxt=='') $text .= "\n".trim($bottxt);
			//save target page
			$new = $page;
			$new['text'] = rtrim($text); 
			//reduce $EditFunctions
			if ($act == 'copy')
				$EditFunctions = array('SaveAttributes','PostPage','PostRecentChanges');
			else 
				unset($EditFunctions['EditTemplate'], $EditFunctions['RestorePage'],
						$EditFunctions['AutoCreateTargets'], $EditFunctions['PreviewPage']); 

			//abort process if $FoxProcessTimeMax is exceeded, to avoid php fatal error on timeout.
			FoxTimer($pagename, "FoxUpdatePages: $tgtname");
			$IsPagePosted = 0;
			$new['csum'] = $ChangeSummary;
  			if ($ChangeSummary) $new["csum:$Now"] = $ChangeSummary;
  			
			if (@$fx['foxnosave']!=1)
			$IsPagePosted = UpdatePage($tgtname, $page, $new, $EditFunctions);
		}
		Lock(0);
		if ($IsPagePosted==1) {
			$_SESSION['foxedit'][$pagename] = array(); //clear up SESSION var; only use if Redirect is used
			$counter++;
			if (isset($targ['foxsuccess'])) $FoxMsgFmt[] = $targ['foxsuccess'];
			elseif (isset($fx['foxsuccess'])) $FoxMsgFmt[] = $fx['foxsuccess'];
			elseif ($EnableFoxDefaultMsg==1)	$FoxMsgFmt[] = "$[Successful post to] [[$tgtname(?action=browse)]]";
		}
		else {
			if (isset($targ['foxfailure'])) $FoxMsgFmt[] = $targ['foxfailure'];
			elseif (isset($fx['foxfailure'])) $FoxMsgFmt[] = $fx['foxfailure'];
			elseif($EnableFoxDefaultMsg==1) $FoxMsgFmt[] = "$[Nothing posted to] $tgtname ";		
		}
		unset($IsPagePosted);
	} //end foreach target loop//
	return $counter;
} //}}}

## get template from template page, #section, or (:foxtemplate 'string':)
function FoxLoadTemplate($pagename, $targ, $fx) {
	$tplname = $targ['template'];
	global $FoxDebug; if($FoxDebug) echo " TEMPLATE>$tplname"; //DEBUG//
	switch($tplname) {
		//first check if no template is wanted
		case '0' : return '';
		case 'foxptvupdate' :	return '';
		case 'foxtemplate' :
			if ($fx['foxtemplate']=='' ) return '';
			elseif ($fx['foxtemplate']=='NULL' ) $template = ''; 
			else $template = Fox_htmlspecialchars_decode($fx['foxtemplate']);
			break;
		default :
			//check if foxtemplate is set as array
			if (!$targ['foxtemplate']=='') { 
				$template = Fox_htmlspecialchars_decode($targ['foxtemplate']); 
				break;
			}
			//if tplname starts with # assume template is section on current page 
			if ($tplname{0}=='#') $tplname = $pagename.$tplname;
			$tplpage = MakePageName($pagename, $tplname);
			if ($tplpage)
				$page = ReadPage($tplpage, READPAGE_CURRENT);
			//TextSection will process any section passed, or the whole page
			if (isset($page['text']))
				$template = trim(TextSection($page['text'], $tplname),"\r\n");
	}
	if (!isset($template) && $tplpage)
		FoxAbort($pagename, "$[Error: Template page] $tplname $[is missing!]");
	if (!isset($template))
		FoxAbort($pagename, "$[Error: Template is missing!]");
	return $template;
} //}}}

## add processed template text at position defined with put, foxmark or mark
function FoxAddText( $pn, $text, $template, $fx, $targ ) {
	global $FoxMsgFmt, $FoxDebug; if($FoxDebug) echo " ADD>"; //DEBUG//
	//get array with mark & form positions
	$ms = FoxSetMarks($pn, $text, $fx, $targ);
	$mark = (isset($ms[0]['mark']) ? $ms[0]['mark'] : '');
	$pre = $aft = ''; $err=0;
	//calculate section position and length
	switch ($ms['put']) {
		case '#top' : //legacy, next
		case 'top'   :  $pos = 0; $aft = "\n"; break;
		case '#bottom' : //legacy, next
		case 'bottom':  $pos = strlen($text); $pre = "\n"; break;
		case 'above' :  if (!isset($ms['Mpos'])) { $FoxMsgFmt[] = "$[Error: Found no mark to add above!]"; return $text; }
							 else { $pos = $ms['Mpos']; $aft = "\n"; break; }
		case 'below' :  if (!isset($ms['Mpos']))  { $FoxMsgFmt[] = "$[Error: Found no mark to add below!]"; return $text; }
							 else { $pos = $ms['Mpos'] + $ms['Mlen']; $pre = "\n"; break; }
		case '#append' : //legacy, next
		case 'aboveform': if (!isset($ms['Fpos'])) { $FoxMsgFmt[] = "$[Error: Found no form to add above!]"; return $text; }
							 else { $pos = $ms['Fpos']; $aft = "\n"; break; }
		case '#prepend' : //legacy, next
		case 'belowform': if (!isset($ms['Fpos'])) { $FoxMsgFmt[] = "$[Error: Found no form to add below!]"; return $text; }
							 else { $pos = $ms['Fpos'] + $ms['Flen']; $pre = "\n"; break; }
		case 'insert' : if (!isset($ms['Mpos'])) { $FoxMsgFmt[] = "$[Error: Found no mark to insert after!]"; return $text; }
							 else { $pos = $ms['Mpos'] + $ms['Mlen']; break; }
		case 'insertbefore' : if (!isset($ms['Mpos'])) { $FoxMsgFmt[] = "$[Error: Found no mark to insert before!]"; return $text; }
							 else { $pos = $ms['Mpos']; break; }
		default: $FoxMsgFmt = "$[Error:] '{$ms['put']}' $[is not a valid option with 'add'!] "; return $text;
	}
	//add line breaks as needed
	$temp =  $pre.$template.$aft;
	// do string insert or repacement
	$text = substr_replace($text, $temp, $pos, 0);
		//DEBUG//
		if($FoxDebug>5) echo "<pre>FULL TEXT=".$text."</pre>";//DEBUG//
	return $text;
} //}}}

## replace text with processed template text at position defined by put, or mark and endmark
function FoxReplaceText( $pn, $text, $template, $fx, $targ ) {
	global $FoxDebug, $FoxMsgFmt; if($FoxDebug) echo " REPLACE>"; //DEBUG//
	//get array with mark & form positions
	$ms = FoxSetMarks($pn, $text, $fx, $targ);
	$mark = (isset($ms[0]['mark']) ? $ms[0]['mark'] : '');
	switch ($ms['put']) {
		case 'string' : if (!isset($ms['Mpos'])) { $FoxMsgFmt[]="$[Error: No string to find!]"; break; }
							else $text = substr_replace($text, $template, $ms['Mpos'], $ms['Mlen']); break;
		case 'all': $tlen = strlen($template);
						$i = 0; $icnt = count($ms[0]['pos']);
						while($i < $icnt) {
							$ipos = $ms[0]['pos'][$i] + $i*($tlen-$ms[0]['len']);
							$text = substr_replace($text, $template, $ipos, $ms[0]['len']);
							$i++;
						} break;
		case 'allplus' : $text = str_replace($mark, $template, $text); break;
		case 'regex' : $text = preg_replace("/$mark/", $template, $text); break;
		case 'marktomark': if ( $ms['Npos'] < $ms['Mpos'] ) {$FoxMsgFmt[]="$[Error: could not find endmark!]"; break;}
							else { $ipos = $ms['Mpos'] + $ms['Mlen']; $ilen = $ms['Npos'] - $ipos;
							$text = substr_replace($text, "\n".$template."\n", $ipos, $ilen); break; }
		case 'overwrite' : $text = $template; break;
		default: $FoxMsgFmt[] = "$[Error:] '{$ms['put']}' $[is not a valid option with 'replace'!] ";
	}
	//DEBUG//
	if($FoxDebug>5) echo "<pre>FULL TEXT=".$text."</pre>";//DEBUG//
	return $text;
} //}}}

## claculate and set mark positions, excluding positions in fox forms and overlappings
function FoxSetMarks($pn, $text, $fx, $targ) {
	global $FoxDebug; if($FoxDebug>3) echo " SETMARKS>"; //DEBUG//
	$ms = array(); $mk = array();
	$ms['put'] = $targ['put'];
	$mk[0] = isset($targ['mark']) ? $targ['mark'] : '';
	$mk[1] = isset($targ['endmark']) ? $targ['endmark'] : '';
	$formname = $fx['foxname'];
	//set foxmark
	if (isset($fx['foxmark']))      $foxmark = " ".$fx['foxmark'];
	elseif (isset($fx['foxplace'])) $foxmark = " ".$fx['foxplace']; //legacy keyword
	else $foxmark = '';
	//check for foxmarks, it overrides any other put setting
	$foxmarks = array(
		"(:foxappend {$formname}{$foxmark}:)"  => 'above',
		"(:foxprepend {$formname}{$foxmark}:)" => 'below');
	foreach($foxmarks as $pat=>$v)
		if (strpos($text, $pat)) { $ms['put'] = $v; $mk[0] = $pat; break; }
			//DEBUG//
			if($FoxDebug>2) echo " MARK=".$mk[0]." PLACE=".$ms['put'];//DEBUG//
	#if ($mk[0]=='') return $ms;
	//calculate any mark positions
	$marks = array();
	foreach($mk as $i => $m) {
		$ms[$i]['mark'] = $m;
		$ms[$i]['len'] = strlen($m);
		if ($mk[$i]=='') continue;
		$mv = array();
		$pat = preg_quote( $mk[$i],'/');
		if(preg_match_all("/$pat/", $text, $match, PREG_OFFSET_CAPTURE))
			foreach($match[0] as $k => $mark) {
				$mv[$k] = array( $mark[1], $mark[1]+strlen($mark[0]) );
			}
		$marks[$i] = $mv;
	}
		//DEBUG//
		if($FoxDebug>4) { echo "<pre>\$fx mv "; print_r($mv); echo "\$marks "; print_r($marks); echo "</pre>"; }
	//get any form positions and exclude marks found in any forms
	if (preg_match_all("/(\\(:fox\\s+([\\w]+)(?: *\\n)?)(.*?)(\\(:foxend \\2:\\))/s", $text, $matches, PREG_OFFSET_CAPTURE)) {
		$forms = array();
		foreach((array)$matches[0] as $i => $frm ) {
			//build forms array: [0] = form start pos, [1] = form end pos
			$forms[$i][0] = $frm[1];
			$forms[$i][1] = $frm[1] + strlen($frm[0]);
			//calculate form position of calling form
			if ($formname == $matches[2][$i][0]) {
				$ms['Fpos'] = $frm[1];
				$ms['Flen'] = strlen($frm[0]);
			}
		}
		// add end-of-text dummy to help calculate positions near end of text
		$txe = strlen($text)+1;
		$forms[] = array( $txe, $txe);
	}
	//calculate all positions outside forms
	if(!isset($forms)) $forms = '';
	foreach($marks as $m=>$mm) {
		$mk = FoxExcludeFormPos($mm, $forms);
		foreach ($mk as $i=>$item) {
			$ms[$m]['pos'][$i] = $item[0];
		}
	}
	//exclude formoverlapping mark to endmark positions
	if(isset($ms[1]['pos'])) {
		foreach($ms[0]['pos'] as $m=>$mm) {
			foreach($ms[1]['pos'] as $n=>$nn) {
				if ($mm>$nn) continue;
				$nms[] = array($mm,$nn);
			}
		}
		$mk2 = FoxExcludeFormPos($nms, $forms);
		foreach ($mk2 as $i=>$item) {
			$ms[0]['pos'][$i] = $item[0];
			$ms[1]['pos'][$i] = $item[1];
		}
	}
	//set mark position
	if (isset($ms[0]['pos'][0])) {
			$ms['Mpos'] = $ms[0]['pos'][0];
			$ms['Mlen'] = $ms[0]['len'];
	}
	//set endmark position
	if (isset($ms[1]['pos'])) {
		foreach($ms[1]['pos'] as $i => $v) {
			$ms['Npos'] = $v; break;
		}
		$ms['Nlen'] = $ms[1]['len'];
	}
	$ms['Tlen'] = strlen($text);
		//DEBUG//
		if($FoxDebug>4) { echo "<pre>\$ms "; print_r($ms); echo "</pre>"; }
	return $ms;
} //}}}

## exclude mark positions inside fox forms
function FoxExcludeFormPos($marks, $forms) {
	$mark = array();
	foreach($marks as $k=>$m) {
		if ($forms=='') { $mark[$k] = array($m[0],$m[1]); continue; }
		foreach($forms as $d=>$f) {
			if ($f[0]>$m[0] && $f[0]<$m[1]) continue 2;
			if ($m[1]<$f[0]) { $mark[] = array($m[0],$m[1]); continue 2; }
			if ($m[1]<$f[1]) continue 2;
		}
	}
	return $mark;
} //}}}

## modified function TextSection to return section and position as well
function FoxTextSection($text, $sections, $args = NULL) {
	$args = (array)$args;
	$npat = '[[:alpha:]][-\\w*]*';
	if (!preg_match("/#($npat)?(\\.\\.)?(#($npat)?)?/", $sections, $match))
		return $text;
	@list($x, $aa, $dots, $b, $bb) = $match;
	if (!$dots && !$b) $bb = $npat;
	if ($aa) {
		$pos = strpos($text, "[[#$aa]]"); if ($pos === false) return false;
	if (@$args['anchors']) 
		while ($pos > 0 && $text[$pos-1] != "\n") $pos--;
	else $pos += strlen("[[#$aa]]");
	$text = substr($text, $pos);
	}
	if ($bb) {
		$text = preg_replace("/(.*?)\\[\\[#$bb\\]\\].*$/s", '$1', $text, 1);
	}
	$sections = array('text' => $text, 'pos' => $pos);
	return $sections;
} //}}}

# FoxTemplateEngine -- Interprets the contents of a Fox template file.
/*
# Takes:
#   $pagename    - the current pagename (for inserting in links)
#   $template    - a string holding the template to interpret
#   $fx          - an array of fields to use to interpret the template
#   $targ        - an array of parameters associated to the target page  
#   $linekeyseed - optional seed for the random key used to identify
#                  parts of the output for later delete actions.
#   $caller      - name of function which calls
#
# Returns:
#   Returns a string containing the expanded template.
#
# Processing:
#   Special template markup is searched for in the template string,
#   and replaced, in order, as follows. Note that processing continues
#   until no replacements are made, or a global iteration count is
#   exceeded.
#
#   (:.....:) and {(....)}
#     if $EnablePostDirectives isn't set, then directives and markup 
#     expressions are escaped so that they appear as-is in the given 
#     text, without being executed or evalued. 
#     FoxDefuseMarkup is called to do this work.
#
#   {$$field}
#     When the value of the field 'field' is a single string, it is
#     substituted in place. When the field is an array of values, then
#     the value substituted is the same as if the replaced string had
#     been {$$field[{$$foxtindex}]}. The value of {$$foxtindex} is the
#     index of the current template being expanded, in the array of
#     templates, starting with zero.
#
#   {$$field[num]}
#     When the value of the field 'field' is an array, and 'num' is a
#     number, then the element with index 'num' in the array is
#     substituted. If the field is not an array, or if there is no
#     such element, then the markup is simply removed. Note that when
#     'num' is non-numeric, the markup is left unsubstituted, but do
#     not rely on that, as further extensions may make use of
#     non-numeric array indexes.
#
#   {$$(function args...)}
#     is replaced by the results of evaluating 'function' as if it
#     were an ordinary Markup Expression (ie, as if it were the string
#     '{(function args...)}' on a normal page). In order to provide
#     backward compatibility, Fox.php adds a 'date' function to the
#     list of valid markup extensions.
#
#   {$$field[]}
#     The existence of one or more special markups of this form cause
#     a special processing mode to be entered. For each element in the
#     longest array named by this form in the template, a duplicate of
#     the template is produced. The first duplicate contains all
#     values of the 0th element of the named arrays, the second
#     duplicate contains the 1st elements and so on. Missing elements
#     are treated as null strings. All of the duplicates are written
#     to the target, in order. The template can be broken into
#     multiple sections with {[foxsection]} markers, each such section
#     being treated independantly in this manner.
#
#   {$$$
#     A final pass is made in which '{$$$' is replaced by '{$$'. No
#     further expansion is done, even if this results in new valid
#     field names. This provides a mechanism to add special template
#     markup to a page, in order to create another template.
*/
function FoxTemplateEngine( $pn, $template, $fx, $targ, $linekeyseed=NULL, $caller=NULL ) {
	global $FoxDebug; if($FoxDebug) echo " ENGINE> "; //DEBUG//
	global $EnablePostDirectives, $FoxFxSafeKeys;
	if($template=="") return '';
	// create the data to be added, from template and variables
	$string = $template;
	// handle the {$$name[]} variables.
	$result = array();
	$parts = explode('{[foxsection]}',$string);
	foreach($parts as $section) {
		//find all occurences of {$$name[]}
		if( preg_match_all('/\\{\\$\\$([A-Za-z][-_:.\\w]*)\\[\\]\\}/',$section, $matches)) {
			$names = array_unique($matches[1]);
			$max   = 0;
			$keys  = array();
			$vals  = array();
			foreach($names as $i=>$var) {
				//get value
				$val  = (array)$fx[$var];
				$max  = max($max,count($val));
				$keys[$i] = '{$$'.$var.'[]}';
				$vals[$i] = $val;
			}
			$reps = array();
			for($i=0; $i < $max; $i++) {
				foreach((array)$vals as $k=>$val)
					$reps[$i][$k] = $val[$i];
			}
			//if more than one target, map vars to target index
			if(count($tar)>1)
				$result[] = str_replace($keys,$reps[$targ['taridx']],$section);
			//for one target build repeated sections
			else for($i=0; $i < $max; $i++) {
				$result[] = str_replace($keys,$reps[$i],$section);
			}
		}
		else  $result[] = $section;
	}

	// replace {$$var}, {$$var[num]} and {$$(func...)} markup.
	$result = FoxTemplateVarReplace($pn, $fx, $targ, $result);

	//replace {$$$...} with {$$...} for posting of forms with replacement vars
	$result = str_replace('{$$$','{$$',$result);

	//replace \n by newlines
	$result = preg_replace('/\\\\n/',"\n",$result);

	//create a unique linekeyseed, if necessary
	if ($linekeyseed==NULL)  $linekeyseed = time().'a'.rand(0,100000);
	foreach ($result as $index => $entry) {
		//skip if delete link already exists
		if (preg_match("/\\{\\[foxdel([^]]+)FullName\\}\\s*\\]\\}/", $entry)) continue;
		$linekey = $linekeyseed.'b'.$index;
		//adding linekey + pagename to any foxdelete markup for unique id
		// Add linekey to delete button for line delete
		$entry = str_replace( '{[foxdelline button', "{[foxdelline button $linekey {\$FullName} ", $entry );
		// Add linekey to delete link for line delete
		$entry = str_replace( '{[foxdelline', "{[foxdelline $linekey {\$FullName} ", $entry  );
		// Add linekey to delete button for range delete
		$entry = str_replace( '{[foxdelrange button', "{[foxdelrange button $linekey {\$FullName} ", $entry );
		// Add linekey to delete link for range delete
		$entry = str_replace( '{[foxdelrange', "{[foxdelrange $linekey {\$FullName} ", $entry );
		//Add line-key to delete range begin marker
		$entry = str_replace( '#foxbegin#', "#foxbegin $linekey#", $entry );
		// Add line-key to delete range end marker
		$entry = str_replace( '#foxend#', "#foxend $linekey#", $entry );
		$result[$index] = $entry;
	}
	return implode("\n",$result);
} //}}}

SDVA($FoxFxSafeKeys, array(
	'n','foxpage','action','foxaction','foxname','post',
	'put','foxfields',':foxaction',':fulltarget',':put',':foxfields',
));

## input field var replacements, exclude fields we know are not variables
function FoxInputVarReplace($pn, &$fx) {
	global $FoxDebug; if($FoxDebug) echo "<br /> INPUT-VR> ";//DEBUG//
	global $FoxFxSafeKeys;
	$fx_check = $fx;
	foreach ($fx_check as $val) {
		foreach ($FoxFxSafeKeys as $key) {
			 if (array_key_exists($key, $fx_check)) {
				  unset($fx_check[$key]);
			 }
		}
	}

	foreach($fx_check as $key => $value) {
		if(is_array($value)) {
			foreach($value as $i=>$val) {
				if (strstr($val, '{$$')) {
						if($FoxDebug>3) echo " N>".$key."[".$i."]=".$val;//DEBUG//
					$fx[$key][$i] = FoxIVReplace($pn, $fx, $val);
				}
			}
		}
		else if (strstr($value, '{$$')) {
				if($FoxDebug>3) echo "<pre> N>".$key."=".$value."</pre>";//DEBUG//
			$fx[$key] = FoxIVReplace($pn, $fx, $value);
		}
	}
} //}}}

## replaces input variables by checking pattern and if success returns value
function FoxIVReplace($pn, $fx, &$str) {
	global $Now, $FoxDebug; if($FoxDebug>4) echo "<br /><pre>IVREP=".$str."</pre>"; //DEBUG//
	#replace {$$name} fields with values
	$str = preg_replace('/\\{\\$\\$([a-z][-_\\w]*)\\}/ie', 
							"FoxValue(\$fx, '', PSS('$0'), PSS('$1'), '')", $str);
	#replace {$$name[num]} fields
	$str = preg_replace('/\\{\\$\\$([a-z][-_\\w]*)\\[\\s*([a-z0-9]+)\\s*\\]\\}/ie',
							"FoxValue(\$fx, '', PSS('$0'), PSS('$1'), PSS('$2'))", $str);
	#replace {$$(function args...)} string must be exactly a markup expression!
	$str = preg_replace('/^\\{\\$\\$(\\(\\w+\\b.*?\\))\\}$/e',
							"MarkupExpression(\$pn, PSS('$1'))", $str);
	//DEBUG//
	if($FoxDebug>4) echo "<pre>IVNEW=".$str."</pre>";//DEBUG//
	return $str;
} //}}}

## replace any {$$var} or {$$(func...)} in $arg using values from $fx
function FoxTemplateVarReplace($pn, $fx, $targ, $args) {
	global $FoxDebug; if($FoxDebug) echo "<br /> TEMPLATE-VR> "; //DEBUG//
	if( is_array($args) ) {
		$data = array( 'more' => false, 'pn' => $pn, 'fx' => $fx, 'targ' => $targ );
		array_walk_recursive( $args, 'FoxVarRepRecursive' , $data );
	} else FoxVarReplace($pn, $fx, $targ, $args);
	return $args;
} //}}}

function FoxVarRepRecursive(&$v, $k, &$d) {
	global $FoxMaxIterations;
	SDV($FoxMaxIterations, 100);
	static $cnt = 0;
	FoxVarReplace($d['pn'], $d['fx'], $d['targ'], $v);
	$cnt++;
	$maxcnt = $FoxMaxIterations + $d['targ']['taridx'];
	if( $cnt >= $maxcnt )
		FoxAbort( $d['fx']['foxpage'], "$[Error: max iterations exceeded while replacing variables!]" );
} //}}

## replaces variables by checking pattern and if success returns value
function FoxVarReplace($pn, $fx, $targ, &$str) {
	global $Now, $FoxDebug; if($FoxDebug>4) echo "<br /><pre>VREP=".$str."</pre>"; //DEBUG//

	$repvar['{$$var}'] = array('pat' => '/\\{\\$\\$([a-z][-_\\w]*)\\}/ie',
										'rep' => "FoxValue(\$fx,\$targ, PSS('$0'), PSS('$1'))");
	$repvar['{$$var[]}'] = array('pat' => '/\\{\\$\\$([a-z][-_\\w]*)\\[\\s*([a-z0-9]+)\\s*\\]\\}/ie',
											'rep' => "FoxValue(\$fx,\$targ, PSS('$0'), PSS('$1'), PSS('$2'))");
	$repvar['{$$(date)}'] = array('pat' => '/\\{\\$\\$\\(date[:\\s]+(.*?)\\)\\}/e',
											'rep' => "date(PSS('$1'))");
	$repvar['{$$(timestamp)}'] = array('pat' => '/\\{\\$\\$\\(timestamp\\)\\}/e',
													'rep' => "\$Now");
	$repvar['{$$(expr)}'] = array('pat' => '/\\{\\$\\$(\\(\\w+\\b.*?\\))\\}/e',
											'rep' => "MarkupExpression(\$pn, PSS('$1'))");
	foreach($repvar as $k => $var) {
		preg_match_all( $var['pat'], $str, $m, PREG_SET_ORDER);
		$repvar[$k]['var'] = $m;
	}
	foreach($repvar as $k=>$rv)
		foreach($rv['var'] as $i=>$var)
			$str = preg_replace($rv['pat'], $rv['rep'], $str);
	//DEBUG//
	if($FoxDebug>4) echo "<pre>VNEW=".$str."</pre>"; //DEBUG//
	return $str;
} //}}}

# looks up a field name and returns its value for {$$field} and {$$field[num]}
# either strings or arrays. The final optional parameter is the value of num. If
# num isn't given, and the field is an array, it uses the value of $targ['taridx']
# (index of targetpage process) if it exists, or else 0.
# In the InputVarReplace process if the field is an array, a comma-separated list of
# the array elements will be returned (not just the first array element)
function FoxValue($fx, $targ, $fullvar, $var, $index=NULL) {
	global $FoxDebug; if($FoxDebug>2) echo "<pre>VALUE>".$var."=</pre>"; //DEBUG//
	$fti = 'none';
	if ($targ) $fti =  $targ['taridx'];
	
	if (array_key_exists($var, $fx) ) {
		$val = $fx[$var];
		if(is_array($val) ) {
			if ($fti=='none')
				$val = implode(',',$val);
			elseif (is_null($index) )
				$val = $val[$fti];
			else
				$val = $val[$index];
		}
		//DEBUG//
		if($FoxDebug>2) echo "<pre>".$val."</pre>"; //DEBUG
		return $val;
	}
	//var is no key name: if action 'add' return empty, otherwise full var string
	if( $targ['foxaction']=='add') $fullvar = '';
	return $fullvar;
} //}}}

## get arguments from POST or GET
function FoxRequestArgs ($fx = NULL) {
	if (is_null($fx)) $fx = array_merge($_GET, $_POST);
	foreach ($fx as $key=>$val) {
    	if(is_array($val))
   		foreach($val as $k=>$v) {
    			$fx[$key][$k] = str_replace("\r",'',stripmagic($v));
   		}
		else $fx[$key] = str_replace("\r",'',stripmagic($val));
	}
	return $fx;
} //}}}

## call external filter functions
SDV($FoxFilterFunctions, array());
function FoxFilter($pagename, &$fx) {
	global $FoxDebug; if($FoxDebug) echo " FILTER> "; //DEBUG//
	global $FoxFilterFunctions;
	//get filter keynames
	$fx['foxfilter'] = preg_split("/[\s,|]+/", $fx['foxfilter'], -1, PREG_SPLIT_NO_EMPTY);
	foreach($fx['foxfilter'] as $f) {
		$ffn = $FoxFilterFunctions[$f];
		if (function_exists($ffn) ) {
			// use specific filter
			if(is_callable($ffn, false, $callable_name)) {
				$fx = $callable_name($pagename, $fx);
				if(!$fx) Redirect($pagename); // Filter is telling us to abort;
			}
		}
	}
} //}}}

## create NAME fields from ptv_NAME fields and add NAME to ptvfields array
function FoxPTVFields($pagename, &$fx) {
	global $FoxDebug; if($FoxDebug) echo "<br /> PTVPRE>"; //DEBUG//
	//strip ptv_ and make name fields

	if($fx['ptvfields'])
		$fx['ptvfields'] = explode(",",$fx['ptvfields']);
	foreach($fx as $n => $v) {
		if(substr($n,0,4)=="ptv_") {
			$n = substr($n,4);
			$fx[$n] = $v;
			$fx['ptvfields'][] = $n;
		}
	}
	if(is_array($fx['ptvfields']))
		$fx['ptvfields'] = array_unique($fx['ptvfields']);
	//flatten input values as arrays for ptvs
	if (is_array(@$fx['ptvfields'])) { 
		foreach($fx['ptvfields'] as $n)
			if (is_array($fx[$n])) $fx[$n] = implode(",", $fx[$n]);
	}
} //}}}

## add & update page text variables
function FoxPTVAddUpdate($pagename, $text, $fx, $targ ) {
	global $FoxDebug; if($FoxDebug) echo " PTVUPDATE>"; //DEBUG//
	global $PageTextVarPatterns, $InputValues, $EnablePostDirectives, 
			$FoxClearPTVFmt, $EnableFoxPTVDelete, $FoxPTVDeleteKey, $FoxMsgFmt;
		//PTVs to check
		if($targ['ptvfields']) {
			if (is_array($targ['ptvfields'])) $fields = $targ['ptvfields'];
			else $fields = explode(',', $targ['ptvfields']);
			foreach($fields as $n)
				$update[$n] = $fx[$n];
		}
		else $update = $fx; //use all fields to look for PTVs
		//array of input fields which will clear PTVs if empty 
		$ptvclear = array();
		if (isset($targ['ptvclear']))
			$ptvclear = explode(",", $targ['ptvclear']);
		//look through PTV patterns and replace matches
		$ptvs = array(); //to build array of ptv names in page
		foreach($PageTextVarPatterns as $pat) {
			if (!preg_match_all($pat, $text, $match, PREG_SET_ORDER)) continue;
			foreach($match as $m) {   //$m[0]=all, $m[1]=beforevalue, $m[2]=name, $m[3]=value, $m[4]=aftervalue			
				$ptvs[] = $var = $m[2];
				if (isset($update[$var])) $val = $update[$var];  //new value
				else $val = '';
				if ($val=='') { //empty input gets ignored, unless ptvclear is set to 1 or to ptv field names
					if (!($ptvclear[0]==1 || in_array($var, $ptvclear))) continue;
				}
				if ($val==$FoxClearPTVFmt) $val = '';  // 'NULL' or other special string clears ptv
				if (is_array($val)) $val = implode(",", $val);   //array input gets converted to csv
				//prevent posting of directives & markup expressions
				if($EnablePostDirectives==false)
			 		$val = FoxDefuseItem($val);
				if($FoxDebug>5) echo "<pre> ".$var."=".$val."</pre>"; //new ptv name=value
				if (!preg_match('/s[eimu]*$/', $pat))  //for any inline pattern replace newlines with spaces
					$val = str_replace("\n", ' ', $val);
				if (strstr($m[4],'[[#')) { 
					$val = trim($val); 
					$m[4] = "\n".$m[4]; //preserve linebreak before ending anchor
				}  
				//erasing ptv
				if ($EnableFoxPTVDelete==1 && $val==$FoxPTVDeleteKey) 
					$text = str_replace($m[0], '', $text);
				else $text = str_replace($m[0], $m[1].$val.$m[4], $text);
			}
		}
		//add any new ptvs named in ptvfields and do not exist in page ptvtarget
		if ($fields) {
			$ptvfmt = isset($targ['ptvfmt']) ? $targ['ptvfmt'] : 'hidden';
			$newptvs = array_diff($fields, $ptvs );
			foreach($newptvs as $key) {
				$val = $update[$key];
				if (is_array($val)) 
					$val = implode(",",$val);
				switch($ptvfmt) {
					case 'text' :   //add as text: val 
						$text = $text."\n$key: $val\n"; break;
					case 'deflist' :   //add as definition list
						$text = $text."\n: $key : $val\n"; break; 
					case 'section' :   //add as anchor section
						$text = $text."\n[[#".$key."]]\n$val\n[[#".$key."end]]\n"; break; 
					case 'extra' :   //add as extra hidden PTV 
						$text = $text."\n(::$key:\n$val\n::)\n"; break;  
					case 'hidden' :   //add as hidden PTV
						$text = $text."\n(:$key: $val:)\n"; break; 
					default : $FoxMsgFmt[] = "$[Error: cannot recognise PTV format] $ptvfmt";
				}
			}
		}
	return $text;
} //}}}

## check page posting permissions
function FoxPagePermission($pagename, $act, $targetname, $fx) {
	global $FoxDebug; if($FoxDebug) echo " PERMISSION>$targetname";
	global $FoxMsgFmt, $FoxConfigPageFmt, $FoxPagePermissions;
	if(!$act) { $FoxMsgFmt[] = "ERROR ($targetname): Unknown action: $act . Cannot proceed!"; return;}
	// get name patterns from FoxConfig page
	$Name = PageVar($pagename, '$Name');
	$Group = PageVar($pagename, '$Group');
	$config = FmtPageName($FoxConfigPageFmt, $pagename);
	if (PageExists($config)) {
		$cfpage = ReadPage($config, READPAGE_CURRENT);
		if ($cfpage) {
			$text = $cfpage['text'];
			if(preg_match_all("/^\\s*([\\*\\w][^\\s:]*):\\s*(.*)/m", $text, $matches, PREG_SET_ORDER))
				foreach($matches as $m)
					$FoxPagePermissions[$m[1]] = $m[2];
		}
	}
	// name check for $act against $FoxPagePermissions
	$pnames = array();
	foreach($FoxPagePermissions as $n => $t) {
		if(strstr($t,'-'.$act)||strstr($t,'none')) { $pnames[$n]='-'.$n; continue; }
		if(strstr($t,$act)||strstr($t,'all')) $pnames[$n]=$n;
	}
	$pnames = FmtPageName(implode(',',$pnames),$pagename);
	if($pnames=='') $pnames = '-';
	$namecheck = (boolean)MatchPageNames($targetname,$pnames);
	// string check against string patterns
	$strcheck = 0;
	if(PageExists($targetname)) { 
		$page = ReadPage($targetname, READPAGE_CURRENT);
		$strcheck = (boolean)( preg_match("/\\(:fox(prepend|append|allow)/", $page['text']) OR
			preg_match("/\\(:fox ".$fx['foxname']." /", $page['text']) );
	}
	if($namecheck==0 && $strcheck==0) {
		$FoxMsgFmt[] = "PERMISSION DENIED to $act on $targetname!";
		return false;
	}
	else return true;
} //}}}

## check access code, captcha, new page exists, and required fields
## this runs before individual page processing
function FoxSecurityCheck($pagename, $tar, &$fx) {
	global $FoxDebug; if($FoxDebug) echo " SECURITY>";
	global $FoxNameFmt, $EnableAccessCode, $EnablePostCaptchaRequired;
	//if preview
	if ($fx['preview']) return '';
	
	//if enabled check for access code match
	if($EnableAccessCode AND (!(isset($fx['access'])&&($fx['access']==$fx['accesscode'])))) {
		FoxAbort($pagename, "$[Error: Missing or wrong Access Code!]");
	}
	//if enabled check for Captcha code (captcha.php is required)
	if($EnablePostCaptchaRequired AND !IsCaptcha()) {
		FoxAbort($pagename, "$[Error: Missing or wrong Captcha Code!]");
	}
	//check pagecheck: if pagecheck page names exists already
	if(isset($fx['pagecheck'])) {
		$check = explode(',',$fx['pagecheck']);
		$stop = 0;
		// pagecheck=1 checks all target pages
		foreach($tar as $targ) $targets[] = $targ['target'];  //make targetname array
		if($check[0]==1) $check = $targets;
		foreach ($check as $pt) {
			$page = MakePageName($pagename, $pt);
			if($pagename==$page) { $FoxMsgFmt[] = "$[Error: You are not allowed to post to this page!]"; $stop=1; continue;}
			if(PageExists($page) AND in_array($pt, $targets)) { 
				$FoxMsgFmt[] = "$[Page] [[$pt]] $[exists already. Please choose another page name!]"; $stop=1; continue;}
		}
		if ($stop==1) FoxAbort($pagename, "$[Error: please check your page targets!]");
	}
	//check for 'post' and 'cancel' from submit button 
	if ( !isset($fx['post']) AND !isset($fx['cancel']) AND !isset($fx['preview']) ) {
		 FoxAbort($pagename, "$[Error: No text or missing post!]");
	}
} //}}}

## defuse posting of directives (:...:) and expressions {(...)} by rendering as code
## check only relevant input fields
function FoxDefuseMarkup($pagename, &$fx ) {
	global $EnablePostDirectives, $FoxFxSafeKeys;
	$fx_check = $fx;
	unset($fx_check['foxtemplate']);
	foreach ($fx_check as $val) {
		foreach ($FoxFxSafeKeys as $key) {
			if (array_key_exists($key, $fx_check)) {
				unset($fx_check[$key]);
			}
		}
	}
	array_walk_recursive( $fx_check, FoxDefuseItem );
	$fx = array_merge($fx, $fx_check);
} //}}}

## defuse by rendering as code any markup directives and markup expressions
function FoxDefuseItem( &$item ) {
	global $FoxDebug; if($FoxDebug>2) echo " DEFUSE>$item"; //DEBUG//
	if (is_array($item)) return $item;
	if (!preg_match("/\\(:|\\{\\(/", $item)) return $item;
	// render {(..)} and (:...:) as code by using HTML character codes
	$item = preg_replace("/\\{(\\(\\w+\\b.*?\\))\\}/", "&#123;$1&#125;", $item); 
	$item = str_replace("(:", "(&#x3a;", $item);
	$item = str_replace(":)", "&#x3a;)", $item);
	//undo for markup directives wrapped in [@...@] or [=...=]
	if (preg_match_all("/(\\[[@|=])[^\\]]*(\\(&#x3a;)(.*?[@|=]\\])/s", $item, $mp)) {
		foreach($mp[0] as $i => $v) {
			$v = str_replace("(&#x3a;","(:",$v);
			$v = str_replace("&#x3a;)",":)",$v);
			$item = str_replace( $mp[0][$i], $v, $item);
		}
	}
	//undo for markup expressions wrapped in [@...@] or [=...=]
	if (preg_match_all("/(\\[[@|=])[^\\]]*(&#123;\\(.*?\\)&#125;)(.*?[@|=]\\])/s", $item, $mp)) {
		foreach($mp[0] as $i => $v) {
			$v = str_replace("&#123;(","{(",$v);
			$v = str_replace(")&#125;",")}",$v);
			$item = str_replace( $mp[0][$i], $v, $item);
		}		
	}
} //}}}

## make a WikiWord out of a string
function FoxWikiWord($str) {
	global $FoxDebug; if($FoxDebug) echo " WIKIWORD> "; //DEBUG//
	global $MakePageNamePatterns;
	$str = preg_replace('/[#?].*$/', '', $str);
	$nm = preg_replace(array_keys( $MakePageNamePatterns ),
						array_values( $MakePageNamePatterns ), $str);
	return $nm;
} //}}}

## newedit opens page in the edit form, it can only run as last page process
function FoxNewEdit($pagename, $template, $fx, $targ) {
	global $FoxDebug; if($FoxDebug) echo " NEWEDIT> "; //DEBUG//
	if(PageExists($targ['target'])) Redirect($targ['target']); //jump to existing page
	$urlfmt = '$PageUrl?action=edit';
	if ($template) {
		//merging fields and template, put into Session var for use with ?action=edit&foxtemptext=1
		@session_start();
		$_SESSION["FoxTempPageText"] = $template;
		//add special template marker before redirecting to edit
		$urlfmt.= '&foxtemptext=1';
	}
	Redirect($targ['target'], $urlfmt); // open new page to edit
} //}}}

## upload files
function FoxPostUpload($pagename, $fx, $auth='upload') {
	global $FoxDebug; if ($FoxDebug) echo " POSTUPLOAD>"; 
	global $UploadVerifyFunction, $UploadDir, $UploadPrefix, $UploadPrefixFmt, 
		   	 $LastModFile, $EnableUploadVersions, $Now, $FoxMsgFmt, $FmtV;
	$uptarget = $fx['uptarget'];
	if (function_exists('MakeUploadPrefix'))
		$upprefix = MakeUploadPrefix($uptarget);
	else $upprefix = FmtPageName("$UploadPrefixFmt", $uptarget);
	$dirpath = $UploadDir.$upprefix;

	$page = RetrieveAuthPage($uptarget, $auth, true, READPAGE_CURRENT);
	if (!$page) FoxAbort($pagename, "?cannot upload to $uptarget");
	foreach($_FILES as $n => $upfile) { 
		$upname = $upfile['name'];
		if ($upname=='') continue; 
		// check for new upload filename
		if ($fx[$n.'_name']) $upname = $fx[$n.'_name'];
		$upname = MakeUploadName($uptarget, $upname);
		if (!function_exists($UploadVerifyFunction))
			FoxAbort($pagename, '?no UploadVerifyFunction available');
		$filepath = $dirpath.'/'.$upname; 
		$result = $UploadVerifyFunction($uptarget, $upfile, $filepath);
		if ($result=='') {
			$filedir = preg_replace('#/[^/]*$#','',$filepath);
			mkdirp($filedir);
			if (IsEnabled($EnableUploadVersions, 0))
				@rename($filepath, "$filepath, $Now");
			if (!move_uploaded_file($upfile['tmp_name'], $filepath))
				{ FoxAbort($pagename, "?cannot move uploaded file to $filepath"); return; }
			fixperms($filepath, 0444);
			if ($LastModFile) { touch($LastModFile); fixperms($LastModFile); }
			$result = "upresult=success";
		}
		# process results for message
		$re = explode('&',substr($result,9));
		# special cases: 
		if($re[0]=='badtype' OR $re[0]=='toobigext') {
			global $upext, $upmax;
			$r1 = explode('=',$re[1]);
			$upext = $r1[1];
			$r2 = explode('=',$re[2]);
			$upmax = $r2[1];
		}
		$result = $re[0];
		$FoxMsgFmt[] = "$[UL$result] $upname";
	}
} //}}}

# last
function FoxFinish($pagename, $fx, $msg) {
	StopWatch('FoxFinish start');
	global $InputValues, $FoxMsgFmt;
	// wipe out input values, so there's no redisplay
	if (isset($fx['keepinput'])) { //keep values for selected input fields
		$keep = explode(',', $fx['keepinput']);
		if ($fx['keepinput']!=1)  {
			foreach($InputValues as $i => $v) {
				if (in_array($i, $keep)) continue;
				unset($InputValues[$i]);
			}
		}
	} else //wipe all
		foreach($InputValues as $i => $v)
			unset($InputValues[$i]);
	HandleDispatch($pagename,'browse',$msg);
	exit;
} //}}}

## abort by displaying error message and returning to page
function FoxAbort($pagename, $msg) {
	global $InputValues, $FoxMsgFmt, $MessagesFmt;
	$FoxMsgFmt[] = $msg;
	$MessagesFmt[] = "<div class='wikimessage'>$msg</div>"; //legacy using (:messages:) markup
	HandleDispatch($pagename,'browse');
	exit;
} //}}}

# FoxTimer aborts if $FoxProcessTimeMax is exceeded, returns process time,
# sets entries in $StopWatch array, displayed with config settings:
# $EnableDiag = 1;
# $HTMLFooterFmt['stopwatch'] = 'function:StopWatchHTML 1'; //function is in scripts/diag.php
function FoxTimer($pagename, $x) {
	global $FoxProcessTimeMax, $StopWatch;
	static $wstart = 0;
	$wtime = strtok(microtime(), ' ') + strtok('');
	if (!$wstart) $wstart = $wtime;
	$wtime = $wtime-$wstart;
	$StopWatch[] = sprintf("%04.2f %s", $wtime, $x);
	$xtime = sprintf("%04.2f %s", $wtime, '');
	if($xtime>$FoxProcessTimeMax)
		FoxAbort($pagename, "$[Error: processing stopped before maximum script timeout.] $[Page process:] $xtime sec");
	return $xtime;
} //}}}

## validation check for form input as set by (:foxcheck ... :) markup
function FoxInputCheck($pagename, $fx) {
	global $FoxDebug; if($FoxDebug) echo " INPUTCHECK> "; //DEBUG//
	global $FoxCheckError, $FoxCheckErrorMsg, $FoxMsgFmt;
	if (isset($fx['cancel'])) return '';
	$check = array();
	foreach($fx as $k => $ar) {
		if (substr($k,0,4)!='chk_') continue;
		if (!is_array($ar)) continue;
		foreach($ar as $i => $v) {
			$n = substr($k,4); // remove leading 'chk_'
			if ($v=='') continue; // set only non-empty values
			if ($n=='name') {
				$nms = explode(',',$v);
				foreach($nms as $j => $nm)
					$check[$i]['names'][$j] = $nm; 
			} else $check[$i][$n] = $v;
		}
	}
	if($FoxDebug>4) { echo "<pre>\$check "; print_r($check); echo "</pre>"; }
	$FoxCheckError = 0;
	foreach($check as $i => $opt) {
		foreach($opt['names'] as $n) {
			if (!isset($opt['match'])) $opt['match'] = "?*";
			$pat = (isset($opt['regex'])) ? $opt['regex'] : ".+";
			list($inclp, $exclp) = GlobToPCRE($opt['match']);
			if ( $inclp && !preg_match("/$inclp/is", $fx[$n]) 
						 || $exclp && preg_match("/$exclp/is", $fx[$n])
						 || !preg_match("/$pat/is", $fx[$n])) {
				$FoxMsgFmt[$n] = isset($opt['msg']) ? $opt['msg']
											: "$[Invalid parameter:] $n";
				$FoxCheckError = 1;
			}
			if (@$opt['if'] && !CondText($pagename, 'if '.$opt['if'], 'yes')) {
				$FoxMsgFmt[$n] = isset($opt['msg']) ? $opt['msg']
											: "$[Input condition failed]";
				$FoxCheckError = 1;
			}
		}
	}
   $errmsg = ($fx['foxcheckmsg']) ? $fx['foxcheckmsg'] : $FoxCheckErrorMsg;
   
   //avoid abort or call to foxedit when preview
   if (isset($fx['preview'])) $FoxCheckError = 0;
   
   if ($FoxCheckError==1) { 
   	if ($_SESSION['foxedit'][$pagename]) FoxHandleEdit($pagename);	
   	else FoxAbort($pagename, $errmsg);
	}
} //}}}

## build javascript for simple validation that required fields have values
function FoxJSFormCheck($formcheck) {
	$reqfields = preg_split("/[\s,|]+/", $formcheck, -1, PREG_SPLIT_NO_EMPTY);
	$out = "
	<script type='text/javascript' language='JavaScript1.2'><!--
		function checkform ( form ) {
		";
	foreach($reqfields as $required) {
		$out .=
		"if (form.$required && form.$required.value == \"\") {
			  window.alert( 'Entry in field \"$required\" is required!' );
			  form.$required.focus();
			  return false ;
			}
		";
	}
	$out .=
	"return true; }
	--></script>";
	return $out;
} //}}}

## provide {$AccessCode} page variable:
$FmtPV['$AccessCode'] = rand(100, 999);

## add page variable {$FoxPostCount}, counts message items per page
$FmtPV['$FoxPostCount'] = 'FoxStringCount($pn,"#foxbegin")';
function FoxStringCount($pagename,$find) {
	$page = ReadPage($pagename, READPAGE_CURRENT);
	$n = substr_count($page['text'], $find);
//   if ($n==0) return '';  #suppressing 0
	return $n;
}

## helper function for php 4 which has no array_walk_recursive function
if (!function_exists('array_walk_recursive')) {
	function array_walk_recursive(&$input, $funcname, $userdata = "") {
		if (!is_callable($funcname) || !is_array($input)) return false;
		foreach ($input as $key => $value) {
			if (is_array($input[$key]))
				array_walk_recursive($input[$key], $funcname, $userdata);
			else {
				$saved_value = $value;
				if (!empty($userdata)) $funcname($value, $key, $userdata);
				else $funcname($value, $key);
				if ($value != $saved_value) $input[$key] = $value;
			}
		}
	return true;
	}
} //}}}

## decode htmlspecialchars
function Fox_htmlspecialchars_decode($str, $style=ENT_COMPAT) {
	if ($style === ENT_COMPAT) $str = str_replace('&quot;','\"',$str);
	if ($style === ENT_QUOTES) $str = str_replace('&#039;','\'',$str);
	$str = str_replace('&lt;','<',$str);
	$str = str_replace('&gt;','>',$str);
	$str = str_replace('&amp;','&',$str);
	return $str;	
}

## (:input default ... :) calls modified function allowing arrays as input values  
$InputTags['default'] = array(':fn' => 'InputDefault2');
$InputTags['defaults'] = array(':fn' => 'InputDefault2');

##  (:input default:) directive, allowing array input values. 
#  modified InputDefault function from scripts/forms.php
function InputDefault2($pagename, $type, $args) {
	global $InputValues, $PageTextVarPatterns; 
	$args = ParseArgs($args);
	$args[''] = (array)@$args[''];
	$name = (isset($args['name'])) ? $args['name'] : array_shift($args['']);
	$name = preg_replace('/^\\$:/', 'ptv_', $name);
	$value = (isset($args['value'])) ? $args['value'] : array_shift($args['']);
	if (!isset($InputValues[$name])) $InputValues[$name] = $value;
	if (@$args['request']) {
		$req = array_merge($_GET, $_POST);
		foreach($req as $k => $v) {
			if (is_array($v)) { 
				foreach($v as $kk => $vv)
					if (!isset($InputValues[$k][$kk])) {
						$InputValues[$k][$kk] = htmlspecialchars(stripmagic($vv), ENT_NOQUOTES); 
					}
			}
			else if (!isset($InputValues[$k])) {
				$InputValues[$k] = htmlspecialchars(stripmagic($v), ENT_NOQUOTES);
			}
		}
	}
	$source = @$args['source'];
	if ($source) {
		$source = MakePageName($pagename, $source);
		$page = RetrieveAuthPage($source, 'read', false, READPAGE_CURRENT);
		if ($page) {
			foreach((array)$PageTextVarPatterns as $pat)
				if (preg_match_all($pat, $page['text'], $match, PREG_SET_ORDER))
					foreach($match as $m)
						if (!isset($InputValues['ptv_'.$m[1]])) 
							$InputValues['ptv_'.$m[2]] = 
									htmlspecialchars($m[3], ENT_NOQUOTES);

		}
	}
	return '';
} //}}}

///EOF