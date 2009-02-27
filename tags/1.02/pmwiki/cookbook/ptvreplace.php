<?php if (!defined('PmWiki')) exit();

/*  ptvreplace.php , a module for PmWiki 2
    Copyright 2007 Hans Bracker.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    
    ptvreplace.php enables replacing of page text variable values.
    Markup syntax for replace links:
    (:ptvreplace name=PTVName val=VALUE:) 
    Use " " for strings with spaces, like val="Value String"
    Optional parameters:
    label=Change: link text label
    target=Group.TargetPage: for replacement
    redir=1: redirect to TargetPage after replacement.
    By default no redirection to a TargetPage takes place.
    The redirect default can be changed with $PTVRedirect = 1;  
    
    By default PTV replacing is enabled for other pages.
    To disable replacing for other pages set in local config file:
    $EnablePTVReplaceTarget = 0;
    
    Replacements can also be done with Input forms, using action ptvreplace.
    See Cookbook:PTVReplace for details.
*/
$RecipeInfo['PTVReplace'] = '2007-04-26';

SDV($EnablePTVReplaceTarget, 1);
SDV($PTVRedirect, 0);

# create replacelink
function PTVReplaceLinkMarkup($pagename, $opt) {
    global $PTVRedirect;
    $defaults = array(
        'target' => $pagename,
    );
    $PTVRedirect ? $defaults['redir'] = 1 : $defaults['redir'] = 0;
    $opt = array_merge($defaults, ParseArgs($opt));
    if(!$opt['label']) $opt['label'] = $opt['val'];
    
    return "<a class='replacelink' href='{$PageUrl}?action=ptvreplace&amp;ptv={$opt['name']}&amp;val={$opt['val']}&amp;target={$opt['target']}&amp;redir={$opt['redir']}' rel='nofollow'>{$opt['label']}</a>";
}

Markup('ptvreplace','directives','/\(:ptvreplace\\s+(.*?)\\s*:\)/e',
        "Keep(PTVReplaceLinkMarkup(\$pagename, PSS('$1')))");

# add ptvreplace to actions
$HandleActions['ptvreplace'] = 'PTVReplace';
function PTVReplace($pagename) {
    global $EnablePTVReplaceTarget, $PTVRedirect;
    # initialise
    $currpage = $pagename;
    
    # security check if page has 'ptvreplace' (in markup or Input)
#    $cpage = RetrieveAuthPage($currpage, "read");
#    $ctext = $cpage['text'];
#    if(strstr($ctext,'ptvreplace')==false) Redirect($currpage);
    
    # set optional target page
    if(isset($_POST['target']) OR $_GET['target']) {
        if($EnablePTVReplaceTarget==0) Redirect($currpage); //targets not allowed. stop.
        if(isset($_POST['target'])) $pagename = MakePageName($pagename, $_POST['target']);
        elseif($_GET['target']) $pagename = MakePageName($pagename, $_GET['target']);
    }
   
    # check edit permission
    $page = RetrieveAuthPage($pagename, 'edit', true);
    if (!$page) Abort("?cannot edit $pagename");
    $newpage = $page;
    $text = $page['text'];
        
    # get name and value  from (:ptvreplace ..:)
    if(@$_GET) {
       $ptv = stripmagic($_GET['ptv']);
       $val = '0'; #initialise 0 specificly to have value
       $val = stripmagic($_GET['val']);
       # set into $post array for replacement routine
       $post[$ptv] = $val;
    }   
    # get names and values from (:input :)
    if(@$_POST) {
       $post = $_POST;
       unset($post['action']);
       unset($post['post']);
    }
    # replacing routine in three cascading steps to limit erronous replacements.
    # only one (first found) replacement per PTV
    foreach ($post as $ptv => $val) {
      $old = $text;
      # replace PTVs of form  (:Name:Value:)  (hidden PTVs)
      $text = preg_replace("/\\(: *$ptv *:(?!\\))\\s?(.*?):\\)/s","(:$ptv:$val:)", $text, 1);
      # if no change try replace PTVs of form  :Name: Value  (definition list markup)
      if($text==$old) { 
         $text = preg_replace("/^:+$ptv:[ \\t]?(.*)$/m",":$ptv: $val", $text, 1);
         # if no change try replace PTVs of form  Name: Value  (open listing)
         if($text==$old) { 
            $text = preg_replace("/^$ptv:[ \\t]?(.*)$/m", "$ptv: $val", $text, 1);
         }
      }
    }
    
    # save page
    $newpage['text'] = $text;
    UpdatePage($pagename, $page, $newpage);
    
    # check redirect
    if(@$_POST['redir']==1 OR $_GET['redir']==1) Redirect($pagename);
    elseif($PTVRedirect==1 AND (@$_POST['redir']==1 OR $_GET['redir']==1)) Redirect($pagename);
    else Redirect($currpage);
}
        