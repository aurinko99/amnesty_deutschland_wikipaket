<?php if (!defined('PmWiki')) exit();

/* serial.php  for PmWiki 2.2.0 +
	Copyright Hans Bracker 2007
	
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published
   by the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

	A number of markup expression definitions for creating
	serial number page names, to produce auto-increasing page names
	with a form processor.
*/

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
   $n = $ThreadStart-1;
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
   $n = $ThreadStart-1;
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
