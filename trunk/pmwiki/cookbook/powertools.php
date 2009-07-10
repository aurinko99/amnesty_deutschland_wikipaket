<?php if (!defined('PmWiki')) exit();	

/*	powertools.php a PmWiki 2.2 extension, copyright Hans Bracker 2008

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published
   by the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

	(plist ) markup expression returns a comma separated list of full pagenames
	from pagenames including wildcards.
	syntax {(plist PageName1 PageName2 ... -PageName3 ....)}
	PageName - source pages from PageName or Group. 
				 Allowed are Wiki wildcards * and ? OR PageName#section.
	-PageName - PageName excluded from source pagelist, wildcards allowed.
	Extra options: 
	group=GROUP - source pages from GROUP (same as pagelist group= option) (wiki wildcards and comma-separated listing allowed)
	name=NAME - source pages NAME from any group (same as pagelist name= option) (wiki wildcards and comma-separated listing allowed)	
	fmt=groups - output only list of group names.
	groups=GROUPPATTERN - as group=GROUPPATTERN but with fmt=groups output: 
	groups=* will give a list of all group names.
	sep=SEPARATOR - character or string inserted between each full pagename. Default is a comma. 
						CR or \n makes new line separations.
	pre=PREFIX - PREFIX string is put in front of every name.
	suf=SUFFIX - SUFFIX string is put behind every name.
	
	(pagelist ) markup expression returns a comma separated list of full pagenames
	and accepts all parameters of the (:pagelist :) directive.
	The difference to (:pagelist :) is: the default fmt is a comma-separated list (fmt=csv), not a HTML list.
	
	{(rename PAGELIST group=GROUP pre=PREFIX suf=SUFFIX)} with input from plist or pagelist returns new pagelist
	with new Group.PageNames GROUP.PREFIX.PageName.SUFFIX if parameters are specified. Use for page backup forms.
 	
	{(pagecount PAGELIST)} markup expression returns the number of pages from a comma-separated pagelist supplied as argument.
	
	{(wordcount STRING|PAGENAME|PAGELIST)} returns count of words in argument STRING or page PAGENAME or csv PAGELIST. 
	
	{(trail [next|prev] label trailpagename)} markup expresion returns a link for eithe rnext or previous page
	   on wiki trail set on trailpagename. If no trailpage is given all pages in the current wiki group are used as trail.

	{(serialname Group Name)} returns incremented pagename of format Group.Name0001 
					(appended 4-digit number defined by var $SerialStart, incrementing number of Group.Name1234 pages)
	
	{(serial Group Name)} returns incremented serial number for Group.Name1234 pages
					(4-digit number defined by $SerialStart)
	
	{(newticket Group)} returns auto increasing pagename of format Group.20071220001 (today date and 3 digit number)

	{(allptvs PAGE|PAGELIST)} show all PTVs as name: value; input argument: PageName or csv list of pagenames for instance with (plist)

	{(random LIST)} returns item selected at random from csv list provided. Doubles are prevented. 
					sep= to specify custom listitem separator

*/
$RecipeInfo['PowerTools']['Version'] = '2008-07-31';

$MarkupExpr['plist'] = 'MxMakePList($pagename, @$args, @$argp)';
	
function MxMakePList($pagename, $args, $opt='') {	
	if ($args[0]=='' && $opt=='') return $pagename;
	$grp = PageVar($pagename, '$Group');
	$exlist =	array();
	$inlist = array();
	$plist = array();
	
	if (isset($opt['groups'])) {
		$grps = explode(",",$opt['groups']);
		foreach($grps as $g) $args[] = "$g.*";
		$opt['fmt'] = 'groups';
	}		
	if (isset($opt['group'])) {
		$gp = explode(",",$opt['group']);
		foreach($gp as $g) $args[] = "$g.*";
	}
	if (isset($opt['name'])) {
		$ns = explode(",",$opt['name']);
		foreach($ns as $n) $args[] = "*.$n";
	}
	
	foreach($args as $src) {
		$pgs = preg_split("/[\\s,|]+/", $src, -1, PREG_SPLIT_NO_EMPTY);
		$plist = array_merge($plist, $pgs);
	}

	foreach((array)$plist as $pn) {
		//check for exclusions
		if($pn{0}=='-') {
			$pn = substr($pn, 1);
			//check for group.name pattern
			if (strstr($pn,'.')) 
				$pgpat = $pn;
			else $pgpat = $grp.".".$pn;
			//make preg pattern from wildcard pattern
			$prpat = GlobToPCRE(FixGlob($pgpat));
			//make list from preg name pattern
			$exlist = array_merge($exlist, ListPages("/$prpat[0]/"));			
		}
		//check for section suffix
		elseif (strstr($pn,'#')) 
			$inlist[] = $pn;
		//additions
		else { 
			//check for group.name pattern
			if (strstr($pn,'.')) 
				$pgpat = $pn;
			else $pgpat = $grp.".".$pn;
			//make preg pattern from wildcard pattern
			$prpat = GlobToPCRE(FixGlob($pgpat));
			
			//make list from preg name pattern
			$inlist =	array_merge($inlist, ListPages("/$prpat[0]/"));
		}
	}
	$plist = array_diff($inlist, $exlist);
	sort($plist);
	
	if ($opt['fmt']=='groups') {
		$glist = array();
		foreach($plist as $p) {
			$pp = explode('.',$p);
			if (in_array($pp[0], $glist)) continue;
			$glist[] = $pp[0];
		}
		$plist = $glist;
	}
	$sep = (@$opt['sep']) ? $opt['sep'] : ",";
	$sep = str_replace('\n',"\n",$sep);
	if ($sep=='LF') $sep = "\n";
	
	foreach ($plist as $i => $p)
		$plist[$i] = @$opt['pre'].$p.@$opt['suf'];
	return implode($sep, $plist);
}


$MarkupExpr['pagelist'] = 'MxPageList($pagename, $params)'; 
function MxPageList($pagename, $args) {
	$opt = array('o' => $args, 'fmt' => 'csv');
	$out = FmtPageList('$MatchList', $pagename, $opt, 0);
	$out = preg_replace("/[\n]+/s","\n",$out);
	return $out;
}

$FPLFormatOpt['csv'] = array('fn' =>  'FPLSimpleText');
function FPLSimpleText($pagename, &$matches, $opt) {
	$matches = MakePageList($pagename, $opt, 0);
	##  extract page subset according to 'count=' parameter
	if (@$opt['count']) {
		list($r0, $r1) = CalcRange($opt['count'], count($matches));
		if ($r1 < $r0) 
			$matches = array_reverse(array_slice($matches, $r1-1, $r0-$r1+1));
	else 
		$matches = array_slice($matches, $r0-1, $r1-$r0+1);
	}
	$opt['sep'] = ((@$opt['sep']) ? $opt['sep'] : ",");
	$opt['sep'] = str_replace('\n',"\n",$opt['sep']);
	if ($opt['sep']=='LF') $opt['sep'] = "\n";
	return Keep(implode($opt['sep'], $matches), 'P');
}


## (rename plist() [group=NEWGROUP] [pre=NAMEPREFIX] [suf=NAMESUFFIX]) 
$MarkupExpr['rename'] = 'MxPageRename($pagename, $args[0], @$argp)';
function MxPageRename($pagename, $plist, $opt='') {
	$plist = explode(",", $plist);
	foreach($plist as $k => $pn) {
		if (!strstr($pn,".")) {
			$grp = PageVar($pagename, '$Group');
			$pn = $grp.".".$pn;
		}
		$plist[$k] = explode(".", $pn);
	}
	$pre = ($opt['pre'] ? $opt['pre'] : '');
	$suf = ($opt['suf'] ? $opt['suf'] : '');
	$newplist = array();
	foreach($plist as $i => $pn) {
		foreach($plist[$i] as $grp => $name) {
			if ($opt['group'])
				$newplist[$i] = $opt['group'].".".$pre.$name.$suf;
			else
				$newplist[$i] = $plist[$i][0].".".$pre.$name.$suf;		
		}
	}
	return implode(",", $newplist);
}

## (pagecount plist())
$MarkupExpr['pagecount'] = 'PageCount($pagename, @$args[0])';
function PageCount($pn, $arg='') {
	if ($arg=='') return 0;
	return count(explode(",", $arg));
}


## (wordcount PageName), (wordcount plist()) (wordcount 'string') (wordcount)
$MarkupExpr['wordcount'] = 'WordCount($pagename, @$args[0])';
function WordCount($pn, $arg='') {
	//wordcount current page 	
	if ($arg=='') {
		$page = RetrieveAuthPage($pn, 'read', false, READPAGE_CURRENT);
		return str_word_count($page['text']);	
	}
	//wordcount string
	if (strpos($arg," ")) {
		return str_word_count($arg);
	}
	//wordcount page(s) PageName1,PageName2,PageName3 ...
	else {
		$pglist = explode(",", $arg);
		$wcnt = 0;
		foreach($pglist as $pg) {
			$pn = MakePagename($pn, $pg);
			$page = RetrieveAuthPage($pn, 'read', true);
			$wcnt = $wcnt + str_word_count($page['text']);
		}
		return $wcnt;
	}
}

## {(trail [next|prev] label trailpagename)}  
$MarkupExpr['trail'] = 'MakeTrailStopNext($pagename, $args[0], $args[1], $args[2])';
function MakeTrailStopNext($pagename, $which='', $label='', $trailname='') {
	$trail = array(); $link='';
	if ($which=='') $which ='next';
	if ($label=='') $label = $which;
	//if no trailname make trail from all group pages
	if ($trailname=='') {
		$grp = PageVar($pagename, '$Group');
		$gplist = ListPages("/^$grp\\..+/");
		sort($gplist);
		foreach($gplist as $i => $p)
			$trail[$i]['pagename'] = $p;
	}
	else  $trail = ReadTrail($pagename, $trailname);
	for($i=0; $i < count($trail); $i++) {
		if ($trail[$i]['pagename']==$pagename) {
			if ($which=='next') {
				if ($i+1<count($trail)) $link = "[[".$trail[$i+1]['pagename']."|".$label."]]";
			}
			if ($which=='prev') {
				if ($i>0) $link = "[[".$trail[$i-1]['pagename']."|".$label."]]";
			}
		}
	}
	return $link;
}

## {(rndpage label trailpagename group=GroupName exclude=PageA,PageB)}  
$MarkupExpr['rndpage'] = 'MXRandomPageLink($pagename, $args[0], $args[1], $argp)';
function MXRandomPageLink($pagename, $label, $trailname='', $args) {
	global $RandomPageNamesUsed;
	$trail = array();
	//if no trailname make trail from all group pages
	if ($trailname=='') {
		if (isset($args['group'])) $group = $args['group'];
		else $group = PageVar($pagename, '$Group');
		$gplist = ListPages("/^$group\\..+/");
		$exclude = array(
			$pagename,
			$group.'.RecentChanges',
			$group.'.RecentUploads',
			$group.'.GroupHeader',
			$group.'.GroupFooter',
			$group.'.GroupAttributes',
		);		
		if (isset($args['exclude'])) {
			$excl = explode(',',$args['exclude']);
			foreach($excl as $i => $p)
				$excl[$i] = MakePageName($group.'.HomePage',$p);
			$exclude = array_merge($exclude, $excl);
		}
		foreach($gplist as $i => $p)
			if (in_array($p,$exclude) || in_array($p,(array)$RandomPageNamesUsed)) 
				unset($gplist[$i]);
		sort($gplist);
		foreach($gplist as $i => $p)	
			$trail[$i]['pagename'] = $p;
	}
	else  $trail = ReadTrail($pagename, $trailname);
	//pick random pagename from trail
	$max = count($trail)-1;
	$i = rand(0,$max);
	$pn = $trail[$i]['pagename'];
	$RandomPageNamesUsed[] = $pn;
	if ($label=='title') $label = '+';
	if ($label=='') $label = 'next';	
	if ($label=='name') $label = PageVar($pn, '$Name'); 
	return "[[$pn|$label]]";
}

SDV($SerialStart,'0001');

# add markup expression {(serialname Group Name)}
# creates auto increasing pagename of format 
# Group.Name0001 (appended 4-digit number defined by $SerialStart
# incrementing number of Group.Name1234 pages 
$MarkupExpr['serialname'] = 'MakeSerialPageName($pagename, $args[0], $args[1])';
function MakeSerialPageName($pagename, $grp="", $name="" ) {
   global $SerialStart;
   $len = strlen($SerialStart);
   if (!$grp) $grp = PageVar($pagename, '$Group');
   $n = $SerialStart-1;
   foreach(ListPages("/^$grp.$name\\d/") as $p) { 
      preg_match("/.*[^\\d](\\d+)$/",$p, $m);
		$mlen = strlen($m[1]);
		if($mlen>$len) $len = $mlen;
		$n = max($n,$m[1]);
   }
   $target= $grp.".".$name.sprintf("%0{$len}d",$n+1);
   return $target;
}

# add markup expression {(serial Group Name)}
# creates auto increasing number only  defined by $SerialStart
# incrementing number of Group.Name1234 pages 
$MarkupExpr['serial'] = 'MakeSerialNumber($pagename, $args[0], $args[1])';
function MakeSerialNumber($pagename, $grp="", $name="" ) {
   global $SerialStart;
   $len = strlen($SerialStart);
   if (!$grp) $grp = PageVar($pagename, '$Group');
   $n = $SerialStart-1;
   foreach(ListPages("/^$grp.$name\\d/") as $p) { 
      preg_match("/.*[^\\d](\\d+)$/",$p, $m);
		$mlen = strlen($m[1]);
		if($mlen>$len) $len = $mlen;
		$n = max($n,$m[1]);
   }
   return sprintf("%0{$len}d",$n+1);
}

# add markup expression {(newticket Group)}
# creates auto increasing pagename of format  
# Group.20061220001 (date and 3 digit number)
$MarkupExpr['newticket'] = 'MakeNewTicket($pagename, $args[0])';
function MakeNewTicket($pagename, $grp) {
   if (!$grp) $grp = PageVar($pagename, '$Group');
   foreach(ListPages("/^$grp.\\d{11}/") as $p) 
       $issue = max(@$issue,substr($p,-11)); 
   $issueday = substr($issue,0,8);
   $today = strftime("%Y%m%d", time());
   if($issueday==$today) {
      $nextissue = $issue+1;
      $target = $grp.".".$nextissue;
   }
   else $target = $grp.".".$today."001";
   return $target;    
} 

# show all ptvs as name: value; input argument: PageName or list of page names
$MarkupExpr['allptvs'] = 'MXDisplayAllPTVs($pagename, $args[0])';
function MXDisplayAllPTVs($pagename, $list) {
   global $PCache; 
   $pgout = array(); $out = array();
   $plist = (isset($list)) ? explode(',',$list) : array($pagename);
   foreach($plist as $pn) {
   	$pn = MakePageName($pagename, $pn);
   	$pgout[$pn][0] = "!!!![[$pn]]\n";
   	PageTextVar($pn, '');
	   foreach($PCache[$pn] as $key => $val) {
	   	if (substr($key,0,3)!="=p_")	continue;
	   	$pgout[$pn][] = "'''".substr($key,3)."''': ".$val."\\\\\n";
	   }
	   if (!isset($pgout[$pn][1])) { 
	   	unset($pgout[$pn]); 
	   	continue; 
	   }
	   sort($pgout[$pn]);
	   $out[] = implode("\n",$pgout[$pn]);
	}
   return implode("\n",$out);
}

# select item at random from csv list provided. No doubles. sep= to specify custom listitem separator
$MarkupExpr['random'] = 'MXRandomItem($pagename,$args[0],$argp)';
function MXRandomItem($pagename, $list, $args) {
	global $RandomItemsUsed; echo $args['sep'];
	$sep = (isset($args['sep'])) ? $args['sep'] : ',';
	$sep = str_replace('\n',"\n",$sep);
	$list = explode($sep,$list);
	foreach($list as $i => $item)
		if (in_array($item,(array)$RandomItemsUsed) || $item=='') 
			unset($list[$i]);
	sort($list);
	$i = rand(0,count($list)-1);
	$RandomItemsUsed[] = $list[$i];
	return $list[$i];	
}