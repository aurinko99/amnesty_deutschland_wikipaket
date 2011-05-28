<?php if(!defined('PmWiki'))exit;
/**
  A mini square thumbnail generator for PmWiki
  Written by (c) Petko Yotov 2006-2011

  This text is written for PmWiki; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 3 of the License, or
  (at your option) any later version. See pmwiki.php for full details
  and lack of warranty.

  This text is partly based on the ThumbList2 picture gallery
  and on the PmWiki upload.php script.

  Copyright 2006-2011 Petko Yotov http://notamment.fr
  Copyright 2004-2007 Patrick R. Michaud http://www.pmichaud.com
*/
$RecipeInfo['Mini']['Version'] = '20110527a';

SDVA($Mini, array('EnableLightbox' => 0,
  'ImgFmt' => '<img class="mini" src="%1$s" title="%2$s" alt="%2$s" border="0" />',
  'LinkFmt' => '<a href="%2$s" class="minilink" %3$s>%1$s</a>',
  'MiniFmt' => '%s',

  'ImTypes' => array(1=>"gif",2=>"jpeg",3=>"png",15=>"wbmp",16=>"xbm"),
  'ImRx' => array("/\\.(gif|png|jpe|jpe?g|wbmp|xbm)$/i", "!^th\\d+---!"),

  'LbJS' => '<script type="text/javascript" src="%1$s/prototype.js"></script>
<script type="text/javascript" src="%1$s/builder.js"></script>
<script type="text/javascript" src="%1$s/effects.js"></script>
<script type="text/javascript" src="%1$s/lightbox-mini.js"></script>
<script type="text/javascript" src="%1$s/lightbox.js"></script>
<link rel="stylesheet" href="%1$s/lightbox.css" type="text/css" media="screen"/>',
  'LbUrl' => '$FarmPubDirUrl/lb',
  'LbRels' => array('','%s[mini]','%s[mini%d]','%s'),
  'EnableCache' => 0, 'CacheFilename' => '.%s.mini-cache.txt',
  'FixFilenames' => 0,
  'CreateFunction' => 'MiniCreate',
  'PurgeRedirectFmt' => '{$PageUrl}?action=upload',
));
SDVA($Mini['thumbs'], array('x'=>'100x100x50x50x90xffffff'));
SDVA($Mini['FixFilenamePatterns'], array('/\\s/'=>'_', '/[^-\\w.]/'=>''));
SDVA($Mini['FNameRPat'], array("/\\.\\w{3,4}$/i"=>'', '/_+/'=>' '));
SDVA($HandleActions, array('mini'=>'HandleMini','purgethumbs'=>'HandlePurgeMini'));

Markup('Mini:','<links',
  "/\\b([Mm]ini\\d?(?:_\\w+)?:)([^\\s\"\\|\\[\\]$KeepToken%]+)(\"([^\"]*)\")?/e",
  "Keep(LinkMini(\$pagename,'$1','$2','$4','$1$2'),'L')");
Markup('(:mini:)', 'directives', '/\\(:mini (.+):\\)/e', "MiniConf(PSS('$1'))");

function LinkMini($PN, $imap, $path, $alt, $txt, $fmt=NULL, $listonly=0){
  global $FmtV, $UploadFileFmt, $LinkUploadCreateFmt, $UploadUrlFmt, $PCache,
    $UploadPrefixFmt, $EnableDirectDownload, $Mini, $HTMLHeaderFmt, $Charset;
  if(! function_exists('imagecreate'))
    return "Mini: PHP-GD image library not found. Exiting.";
  static $cnt = 0; $cnt++;

  $lb = sprintf(@$Mini['LbRels'][ $Mini['EnableLightbox'] ], 'lightbox', $cnt);
  $ptime = $PCache[$PN]['time'];

  $test_cache = ($Mini['EnableCache'] && !@$_POST['preview'] && !$listonly);
  if($test_cache){
    $cachedir = FmtPageName($UploadFileFmt, $PN);
    $cachefile =  sprintf("$cachedir/{$Mini['CacheFilename']}", $PN);
    if(!@$_GET['recache'] && !(isset($Mini['Cache'][0]) && isset($Mini['Cache'][$cnt]))){
      $Mini['Cache'][0] = 1;
      if(file_exists($cachefile) && filemtime($cachefile) >= $ptime){
        $cache = implode('', file($cachefile));
        preg_match_all("/<(Mini(\\d+))>(.*?)<\\/\\1>/", $cache, $m);
        foreach($m[2] as $i=>$x)$Mini['Cache'][$x] = $m[3][$i];
      }
    }
    if(isset($Mini['Cache'][$cnt])){
      if(strpos($Mini['Cache'][$cnt], "rel='lightbox") )
        $HTMLHeaderFmt['lightbox'] = sprintf($Mini['LbJS'], $Mini['LbUrl']);
       return $Mini['Cache'][$cnt];
    }
  }
  $cache_ok = 1;

  if (preg_match('!^(.*)/([^/]+)$!', $path, $m)) {
    $path = $m[2]; $PN = MakePageName($PN, $m[1]);
  }
  $uploadurl = FmtPageName(IsEnabled($EnableDirectDownload,1)
      ? "$UploadUrlFmt$UploadPrefixFmt/"
      : "\$PageUrl?action=download&amp;upname=",
    $PN);
  $flist = array();
  if(preg_match("/(^|,)[!-]|[\\*\\?]/", $path)){
    $uploaddir = FmtPageName($UploadFileFmt, $PN);
    if($dirp=@opendir($uploaddir)){
      while (($f=readdir($dirp))!==false)if($f{0}!='.')$flist[$f]=$f;
      closedir($dirp);
    }
    $flist = MatchNames($flist, array_merge($Mini['ImRx'], array($path)));
    natcasesort($flist);
  }

  foreach(explode(',', $path) as $v)$mylist[$v] = $v;
  $mylist = array_merge(preg_grep("/^[!-]|[\\*\\?]/", $mylist, PREG_GREP_INVERT), $flist);
  if(preg_match("/^(Mini)(\\d)(_\\w+)?:$/i", $imap, $m) ){
    $imap = "{$m[1]}{$m[3]}:"; $pxidx = intval($m[2]);
  }
  else $pxidx = 0;
  if(preg_match("/^Mini_(\\w+):$/i", $imap, $m) ){
    $imap = substr($imap, 0, 4).':';   $uMini = "uMini_{$m[1]}";
    if(function_exists($uMini) ) $mylist = $uMini($mylist); else return "$txt$alt";
  }

  $ImgFmt =  IsEnabled($Mini["ImgFmt$pxidx"],  $Mini['ImgFmt']);
  $LinkFmt = IsEnabled($Mini["LinkFmt$pxidx"], $Mini['LinkFmt']);
  $MiniFmt = IsEnabled($Mini["MiniFmt$pxidx"], $Mini['MiniFmt']);

  $html = array();
  $htmlH = $htmlF = '';
  foreach($mylist as $file=>$v){
    list($upname, $fpath) = MiniFixFName($PN, $v);
    $picurl = PUE("$uploadurl$upname");

    if($listonly){ $html[] = "\n$upname"; continue; }

    if(!file_exists($fpath)){
      if(@$Mini['nofileurl']) {
        $html[] = sprintf($ImgFmt, $Mini['nofileurl'], '');
      }
      else {
        $FmtV['$LinkText'] = $upname;
        $FmtV['$LinkUpload'] =
          FmtPageName("\$PageUrl?action=upload&amp;upname=$upname", $PN);
        $html[] = FmtPageName($LinkUploadCreateFmt, $PN);
      }
      continue;
    }
    list($w, $h, $t) = @getimagesize($fpath, $info);
    if(!isset($Mini['ImTypes'][$t])){
      $html[] =  LinkIMap($PN, "Attach:", $upname, $alt, "Attach:$upname", $fmt);
      continue;
    }

    $mupname = "th0$pxidx---$upname.jpg";
    $mpath = FmtPageName("$UploadFileFmt/$mupname", $PN);

    if(file_exists($mpath) && filemtime($mpath)>=filemtime($fpath))
      $miniurl = PUE("$uploadurl$mupname");
    else{
      $miniurl = PUE(FmtPageName("{\$PageUrl}?action=mini&amp;idx=$pxidx&amp;upname=$upname", $PN));
      $cache_ok = 0; NoCache();
    }
    if(trim($alt) == '-') $xalt='';
    elseif($alt>'') $xalt=str_replace('"', "&quot;", strip_tags($alt));
    else $xalt = preg_replace(array_keys($Mini['FNameRPat']), array_values($Mini['FNameRPat']), $v);

    if(IsEnabled($Mini['EnableHeaderFooter'], 0) && strpos($xalt, '|')!==false){
      list($htmlH, $htmlF) = explode('|', $xalt, 2);
      $xalt = trim("$htmlH $htmlF");
    }
    $out = sprintf($ImgFmt, $miniurl, $xalt);
    if($imap == 'Mini:'){ # links enabled
      $rel='';
      if($lb>''){
        $HTMLHeaderFmt['lightbox'] = sprintf($Mini['LbJS'], $Mini['LbUrl']);
        $rel = "rel='$lb' title=\"$xalt\"";
      }
      $out = sprintf($LinkFmt, $out, $picurl, $rel);
    }
    $html[] = $out;
  }
  $html = sprintf($MiniFmt, implode(' ', $html));
  if($htmlH) $html = "<span class='miniH'>$htmlH</span> $html";
  if($htmlF) $html .= " <span class='miniF'>$htmlF</span>";
  if($test_cache){
    if($cache_ok){
      $mode = ($cnt==1)? 'w+' : 'a+';
      mkdirp($cachedir);
      if ($handle = @fopen($cachefile, $mode)){
        @fwrite($handle, "<Mini$cnt>$html</Mini$cnt>\n");fclose($handle);
      }
    }
    elseif(file_exists($cachefile)) unlink($cachefile);
  }
  return $html;
}
function HandleMini($PN, $auth="read"){
  global $Mini, $WorkDir, $UploadFileFmt, $UploadDir, $UploadPrefixFmt;
  $page = RetrieveAuthPage($PN,$auth,1, READPAGE_CURRENT);# ask for pw if needed
  $Mini['Px'] = (array)$Mini['Px']; $Mini['Py'] = (array)$Mini['Py'];
  $q = preg_replace('/\\(:mini (.+):\\)/e',  "MiniConf(PSS('$1'))", $page['text']);
  $idx = intval(@$_REQUEST['idx']);
  if(!isset($Mini['thumbs'][$idx])) $idx=0;

  $upname = MakeUploadName($PN, $_REQUEST['upname']);
  $mupname = "th0$idx---$upname.jpg";
  $fpath = FmtPageName("$UploadFileFmt/$upname", $PN);
  $mpath = FmtPageName("$UploadFileFmt/$mupname", $PN);

  if(!file_exists($fpath)){Abort("? file '$fpath' not found."); exit;}
  if(!file_exists($mpath) || filemtime($mpath)<filemtime($fpath)){
    list($W, $H, $T) = @getimagesize($fpath);
    if(!isset($Mini['ImTypes'][$T])){Abort("? format $T not supported."); exit;}
    $f = $Mini['CreateFunction'];
    $f($fpath,$mpath,$W,$H,$T,$idx);
  }
  $_REQUEST['upname'] = $mupname;
  HandleDownload($PN);
}

function MiniConf($args){
  global $Mini; $opt = ParseArgs($args);
  if(is_array(@$opt[''])) $Mini['thumbs'] = $opt['']+$Mini['thumbs'];
  for($i=0;$i<10;$i++) if(isset($opt["m$i"]) )$Mini['thumbs'][$i] = $opt["m$i"];
}

function MiniParseAttr($x,$d=null) # '100x100x50x50x90xffffff'
{
  $y = explode('x', $x);
  $y[5] = hexdec(@$y[5]);
  if($d && $d!=$x) {
    $d = MiniParseAttr($d);
    foreach($d as $k=>$v)
      if(!isset($y[$k])||$y[$k]=='') $y[$k] = $v;
  }
  ksort($y);
  return array_map('intval', $y);
}
function MiniCreate($fpath,$mpath,$W,$H,$T,$idx){
  global $Mini;
  list($w,$h,$cx,$cy,$quality,$bg) = MiniParseAttr($Mini['thumbs'][$idx],$Mini['thumbs']['x']);
  if($h==0)$h=round($w*$H/$W);
  elseif($w==0)$w=round($h*$W/$H);
  if($h*$w==0)$h=$w=100;

  list($rr, $gg, $bb) =  $bg;
  $imcopy = (function_exists('imagecopyresampled'))?'imagecopyresampled':'imagecopyresized';
  $imcreate=(function_exists('imagecreatetruecolor'))?'imagecreatetruecolor':'imagecreate';
  $fcreate = "imagecreatefrom".$Mini['ImTypes'][$T];
  $img = $fcreate($fpath);
  if (!@$img){return;}

  $nimg = $imcreate($w, $h);
  imagefill($nimg, 0, 0, imagecolorallocate($nimg, floor($bg/256/256), ($bg/256)%256, $bg%256));

  $percent = max(1, min($H/$h, $W/$w));
  $_h = round($percent*$h);
  $_w = round($percent*$w);

  $sW = min($W,$_w); #source width
  $sH = min($H,$_h);
  $sY = max(0, round(($H-$_h)*$cx/100));
  $sX = max(0, round(($W-$_w)*$cy/100));

  $tW = min($w, $W); #target width
  $tH = min($h, $H);
  $tY = max(0, round(($h-$H)/2));
  $tX = max(0, round(($w-$W)/2));

  $imcopy($nimg,$img,$tX,$tY,$sX,$sY,$tW,$tH,$sW,$sH);

  imagedestroy($img);
  if(function_exists('imageconvolution'))
    imageconvolution($nimg, array(array(-1,-1,-1),array(-1,16,-1),array(-1,-1,-1)),8,0);
  imagejpeg($nimg,$mpath,$quality);
  imagedestroy($nimg);
}
function HandlePurgeMini($PN, $auth='edit'){
  RetrieveAuthPage($PN, $auth, 1, READPAGE_CURRENT);
  global $UploadDir, $UploadPrefixFmt, $Mini;
  $udir = FmtPageName("$UploadDir$UploadPrefixFmt", $PN);
  $cachefile =  preg_quote(sprintf($Mini['CacheFilename'], $PN));
  if ($dirp = @opendir($udir)){
    while (($file=readdir($dirp)) !== false)
      if (preg_match("/^(th\\d+---|$cachefile$)/", $file))
        unlink("$udir/$file");
    closedir($dirp);
  }
  Redirect($PN, $Mini['PurgeRedirectFmt']);
}
function MiniFixFName($PN, $x){
  global $Mini, $UploadFileFmt;
  $y = MakeUploadName($PN, $x);
  if(!$Mini['FixFilenames']) return array($y, FmtPageName("$UploadFileFmt/$y", $PN));

  $z = preg_replace(array_keys($Mini['FixFilenamePatterns']),array_values($Mini['FixFilenamePatterns']), $y);
  $zpath = FmtPageName("$UploadFileFmt/$z", $PN);
  foreach(array($x, $y) as $v){
    $path = FmtPageName("$UploadFileFmt/$v", $PN);
    if($path != $zpath && file_exists( $path ) ) @rename($path, $zpath);
  }
  return array($z, $zpath);
}
if(!function_exists('MatchNames')){function MatchNames($l,$p){return MatchPageNames($l,$p);}}
