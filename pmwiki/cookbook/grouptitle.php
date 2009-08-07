<?php if (!defined('PmWiki')) exit();
/*  Copyright 2006 Patrick R. Michaud (pmichaud@pobox.com)
    This file is grouptitle.php; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  

    This recipe adds the {$GroupTitle} and {$GroupTitlespaced}
    page variables to PmWiki.  
        Group.GroupAttributes
        Group.GroupHeader
        Group.GroupFooter
        Group.Group
        Group.HomePage (or however $DefaultName is set)

    If no title is found, then returns the current group name
    (spaced if $GroupTitlespaced or if $SpaceWikiWords is set).

    The list of pages to be checked is held in $GroupTitlePathFmt .

*/

SDV($RecipeInfo['GroupTitle']['Version'], '2006-11-14');

SDV($FmtPV['$GroupTitle'], 'GroupTitle($pn, $var)');
SDV($FmtPV['$GroupTitlespaced'], 'GroupTitle($pn, $var)');

function GroupTitle($pagename, $var, $fmt = NULL) {
  global $GroupTitlePathFmt, $DefaultName, $SpaceWikiWords, $AsSpacedFunction;
  if (is_null($fmt)) {
    SDV($GroupTitlePathFmt, array(
      '$Group.GroupAttributes', '$Group.GroupHeader', 
      '$Group.GroupFooter', '$Group.$Group', "\$Group.$DefaultName"));
    $fmt = $GroupTitlePathFmt;
  }
  foreach((array)$fmt as $f) {
    $pn = FmtPageName($f, $pagename);
    $page = ReadPage($pn, READPAGE_CURRENT);
    if ($page['title']) { return $page['title']; }
  }
  $title = PageVar($pagename, '$Group');
  return ($var == '$GroupTitlespaced' || $SpaceWikiWords)
         ? $AsSpacedFunction($title) : $title;
}
 
 
