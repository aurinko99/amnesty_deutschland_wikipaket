<?php

/***********************************************************
* pmfeed.php, requires PmWiki 2.1                          *
* This is an RSS feed display cookbook recipe for PmWiki.  *
*                                                          *
* Uses (and includes) lastRSS which is a GPL'd php class   *
* for handling RSS feeds.                                  *
* See "copyright" after this boiler plate below...         *
*                                                          *
* Copyright (c) 2006, Chris Cox ccox@airmail.net           *
* All Rights, Reserved.                                    *
*                                                          *
* This program is free software; you can redistribute it   *
* and/or modify it under the terms of the GNU General      *
* Public License as published by the Free Software         *
* Foundation; either version 2 of the License, or (at your *
* option) any later version.                               * 
*                                                          *
* Installation:                                            *
*                                                          *
* Place pmfeed.php (this file) into your cookbook          *
* directory (e.g. /srv/www/htdocs/pmwiki/cookbook)         *
*                                                          *
* Include the cookbook in your local/config.php            *
* include_once('cookbook/pmfeed.php');                     *
*                                                          *
* Create a cache directory at pub/pmfeed under your        *
* your PmWiki base directory.  Needs to be writable by     *
* the web username.                                        *
*                                                          *
* On a page include the markup with a feed url.            *
* (:pmfeed feed='http://www.digg.com/rss/index.xml':)      *
*                                                          *
* Enabling UTF-8 PmWiki pages can help make more feeds     *
* usable. Add to your local/config.php:                    *
* include_once("$FarmD/scripts/xlpage-utf-8.php");         *
*                                                          *
-<0.01: Initial release.

-<0.02: Attempt to interpret some HTML tags as PmWiki equivalents.  Enabled for now.

-<0.03: Renamed banner to title and added several "show" options and added 
an unsafe option for some flexibility/risk.

-<0.04: Fixed bug regarding use of newwin=false resulting in bad output (space problem).

-<0.05: Attempt to handle some character issues (utf8 and other hacks).

-<0.06: Major updates.  Added more options (see below).  Much better i18n
support especially when pmwiki is running in utf8 mode.

-<0.07: Fixes bug with itemspace handler canceling out max_count.

-<0.08: Added showpubdate option.

->Variables:
     [@
     feed=              URL to RSS feed file. Defaults to pmwiki.org's
                           Site/AllRecentChanges.
     cache_time=        Time to cache data... be nice to the providers.
                           Defaults to 2000 seconds. Set to 0 to disable.
     encoding=          Override the feed's encoding with this value.
     imagestyle=        A wikistyle to apply to images (see showimages).
     itemspace=         Defaults to 1, means 1 blank line after item. 
     max_count=         Number of items to read.  Defaults to 0.
     newwin=            Open links to items in a new window. Defaults to true.
     overrides=         Set this to false to prevent URL line GET overrides to
                           these parameters.
     showfeedxmllink=   Defaults to false. Add link by the title to
                           the original RSS feed file.
     showimages=        Defaults to false. Attempt to show images in feed.
     showitems=         Defaults to true.  If false, don't show RSS items.
     showitemdescr=     Defaults to true.  If false, don't include the
                           description along with the item.
     showpubdate=       Defaults to false. Attempt to show publish date for item.
     showtables=        Defaults to false. Attempt to show tables in feed.
     showtitle=         Defaults to true.  If false, don't show the feed title.
     title=             Alternate title instead of using RSS title.
     unsafe=            Defaults to false.  If true, allows setting of title
                           and feed from the URL line (_GET).
     @]
***********************************************************/

// This function is useful if running with PmWikis default charset
// of ISO-8859-1 and you don't have mb_convert_encoding or iconv
// support.
//
//
function pmfeedutf8htmlentityencode($s) {

    $t = array_flip(array(
        '&nbsp;'   => "\xc2\xa0",
        '&iexcl;'  => "\xc2\xa1",
        '&cent;'   => "\xc2\xa2",
        '&pound;'  => "\xc2\xa3",
        '&curren;' => "\xc2\xa4",
        '&yen;'    => "\xc2\xa5",
        '&brvbar;' => "\xc2\xa6",
        '&sect;'   => "\xc2\xa7",
        '&uml;'    => "\xc2\xa8",
        '&copy;'   => "\xc2\xa9",
        '&ordf;'   => "\xc2\xaa",
        '&laquo;'  => "\xc2\xab",
        '&not;'    => "\xc2\xac",
        '&shy;'    => "\xc2\xad",
        '&reg;'    => "\xc2\xae",
        '&macr;'   => "\xc2\xaf",
        '&deg;'    => "\xc2\xb0",
        '&plusmn;' => "\xc2\xb1",
        '&sup2;'   => "\xc2\xb2",
        '&sup3;'   => "\xc2\xb3",
        '&acute;'  => "\xc2\xb4",
        '&micro;'  => "\xc2\xb5",
        '&para;'   => "\xc2\xb6",
        '&middot;' => "\xc2\xb7",
        '&cedil;'  => "\xc2\xb8",
        '&sup1;'   => "\xc2\xb9",
        '&ordm;'   => "\xc2\xba",
        '&raquo;'  => "\xc2\xbb",
        '&frac14;' => "\xc2\xbc",
        '&frac12;' => "\xc2\xbd",
        '&frac34;' => "\xc2\xbe",
        '&iquest;' => "\xc2\xbf",
        '&Agrave;' => "\xc3\x80",
        '&Aacute;' => "\xc3\x81",
        '&Acirc;'  => "\xc3\x82",
        '&Atilde;' => "\xc3\x83",
        '&Auml;'   => "\xc3\x84",
        '&Aring;'  => "\xc3\x85",
        '&AElig;'  => "\xc3\x86",
        '&Ccedil;' => "\xc3\x87",
        '&Egrave;' => "\xc3\x88",
        '&Eacute;' => "\xc3\x89",
        '&Ecirc;'  => "\xc3\x8a",
        '&Euml;'   => "\xc3\x8b",
        '&Igrave;' => "\xc3\x8c",
        '&Iacute;' => "\xc3\x8d",
        '&Icirc;'  => "\xc3\x8e",
        '&Iuml;'   => "\xc3\x8f",
        '&ETH;'    => "\xc3\x90",
        '&Ntilde;' => "\xc3\x91",
        '&Ograve;' => "\xc3\x92",
        '&Oacute;' => "\xc3\x93",
        '&Ocirc;'  => "\xc3\x94",
        '&Otilde;' => "\xc3\x95",
        '&Ouml;'   => "\xc3\x96",
        '&times;'  => "\xc3\x97",
        '&Oslash;' => "\xc3\x98",
        '&Ugrave;' => "\xc3\x99",
        '&Uacute;' => "\xc3\x9a",
        '&Ucirc;'  => "\xc3\x9b",
        '&Uuml;'   => "\xc3\x9c",
        '&Yacute;' => "\xc3\x9d",
        '&THORN;'  => "\xc3\x9e",
        '&szlig;'  => "\xc3\x9f",
        '&agrave;' => "\xc3\xa0",
        '&aacute;' => "\xc3\xa1",
        '&acirc;'  => "\xc3\xa2",
        '&atilde;' => "\xc3\xa3",
        '&auml;'   => "\xc3\xa4",
        '&aring;'  => "\xc3\xa5",
        '&aelig;'  => "\xc3\xa6",
        '&ccedil;' => "\xc3\xa7",
        '&egrave;' => "\xc3\xa8",
        '&eacute;' => "\xc3\xa9",
        '&ecirc;'  => "\xc3\xaa",
        '&euml;'   => "\xc3\xab",
        '&igrave;' => "\xc3\xac",
        '&iacute;' => "\xc3\xad",
        '&icirc;'  => "\xc3\xae",
        '&iuml;'   => "\xc3\xaf",
        '&eth;'    => "\xc3\xb0",
        '&ntilde;' => "\xc3\xb1",
        '&ograve;' => "\xc3\xb2",
        '&oacute;' => "\xc3\xb3",
        '&ocirc;'  => "\xc3\xb4",
        '&otilde;' => "\xc3\xb5",
        '&ouml;'   => "\xc3\xb6",
        '&divide;' => "\xc3\xb7",
        '&oslash;' => "\xc3\xb8",
        '&ugrave;' => "\xc3\xb9",
        '&uacute;' => "\xc3\xba",
        '&ucirc;'  => "\xc3\xbb",
        '&uuml;'   => "\xc3\xbc",
        '&yacute;' => "\xc3\xbd",
        '&thorn;'  => "\xc3\xbe",
        '&yuml;'   => "\xc3\xbf",
        '&fnof;'   => "\xc6\x92",
        '&Alpha;'  => "\xce\x91",
        '&Beta;'   => "\xce\x92",
        '&Gamma;'  => "\xce\x93",
        '&Delta;'  => "\xce\x94",
        '&Epsilon;' => "\xce\x95",
        '&Zeta;'   => "\xce\x96",
        '&Eta;'    => "\xce\x97",
        '&Theta;'  => "\xce\x98",
        '&Iota;'   => "\xce\x99",
        '&Kappa;'  => "\xce\x9a",
        '&Lambda;'  => "\xce\x9b",
        '&Mu;'      => "\xce\x9c",
        '&Nu;'      => "\xce\x9d",
        '&Xi;'      => "\xce\x9e",
        '&Omicron;' => "\xce\x9f",
        '&Pi;'      => "\xce\xa0",
        '&Rho;'     => "\xce\xa1",
        '&Sigma;'   => "\xce\xa3",
        '&Tau;'     => "\xce\xa4",
        '&Upsilon;' => "\xce\xa5",
        '&Phi;'     => "\xce\xa6",
        '&Chi;'     => "\xce\xa7",
        '&Psi;'     => "\xce\xa8",
        '&Omega;'   => "\xce\xa9",
        '&alpha;'   => "\xce\xb1",
        '&beta;'    => "\xce\xb2",
        '&gamma;'   => "\xce\xb3",
        '&delta;'   => "\xce\xb4",
        '&epsilon;' => "\xce\xb5",
        '&zeta;'    => "\xce\xb6",
        '&eta;'     => "\xce\xb7",
        '&theta;'   => "\xce\xb8",
        '&iota;'    => "\xce\xb9",
        '&kappa;'   => "\xce\xba",
        '&lambda;'  => "\xce\xbb",
        '&mu;'      => "\xce\xbc",
        '&nu;'      => "\xce\xbd",
        '&xi;'      => "\xce\xbe",
        '&omicron;' => "\xce\xbf",
        '&pi;'      => "\xcf\x80",
        '&rho;'     => "\xcf\x81",
        '&sigmaf;'  => "\xcf\x82",
        '&sigma;'   => "\xcf\x83",
        '&tau;'     => "\xcf\x84",
        '&upsilon;' => "\xcf\x85",
        '&phi;'     => "\xcf\x86",
        '&chi;'     => "\xcf\x87",
        '&psi;'     => "\xcf\x88",
        '&omega;'   => "\xcf\x89",
        '&thetasym;'=> "\xcf\x91",
        '&upsih;'   => "\xcf\x92",
        '&piv;'     => "\xcf\x96",
        '&bull;'    => "\xe2\x80\xa2",
        '&hellip;'  => "\xe2\x80\xa6",
        '&prime;'   => "\xe2\x80\xb2",
        '&Prime;'   => "\xe2\x80\xb3",
        '&oline;'   => "\xe2\x80\xbe",
        '&frasl;'   => "\xe2\x81\x84",
        '&weierp;'  => "\xe2\x84\x98",
        '&image;'   => "\xe2\x84\x91",
        '&real;'    => "\xe2\x84\x9c",
        '&trade;'   => "\xe2\x84\xa2",
        '&alefsym;' => "\xe2\x84\xb5",
        '&larr;'    => "\xe2\x86\x90",
        '&uarr;'    => "\xe2\x86\x91",
        '&rarr;'    => "\xe2\x86\x92",
        '&darr;'    => "\xe2\x86\x93",
        '&harr;'    => "\xe2\x86\x94",
        '&crarr;'   => "\xe2\x86\xb5",
        '&lArr;'    => "\xe2\x87\x90",
        '&uArr;'    => "\xe2\x87\x91",
        '&rArr;'    => "\xe2\x87\x92",
        '&dArr;'    => "\xe2\x87\x93",
        '&hArr;'    => "\xe2\x87\x94",
        '&forall;'  => "\xe2\x88\x80",
        '&part;'    => "\xe2\x88\x82",
        '&exist;'   => "\xe2\x88\x83",
        '&empty;'   => "\xe2\x88\x85",
        '&nabla;'   => "\xe2\x88\x87",
        '&isin;'    => "\xe2\x88\x88",
        '&notin;'   => "\xe2\x88\x89",
        '&ni;'      => "\xe2\x88\x8b",
        '&prod;'    => "\xe2\x88\x8f",
        '&sum;'     => "\xe2\x88\x91",
        '&minus;'   => "\xe2\x88\x92",
        '&lowast;'  => "\xe2\x88\x97",
        '&radic;'   => "\xe2\x88\x9a",
        '&prop;'    => "\xe2\x88\x9d",
        '&infin;'   => "\xe2\x88\x9e",
        '&ang;'     => "\xe2\x88\xa0",
        '&and;'     => "\xe2\x88\xa7",
        '&or;'      => "\xe2\x88\xa8",
        '&cap;'     => "\xe2\x88\xa9",
        '&cup;'     => "\xe2\x88\xaa",
        '&int;'     => "\xe2\x88\xab",
        '&there4;'  => "\xe2\x88\xb4",
        '&sim;'     => "\xe2\x88\xbc",
        '&cong;'    => "\xe2\x89\x85",
        '&asymp;'   => "\xe2\x89\x88",
        '&ne;'      => "\xe2\x89\xa0",
        '&equiv;'   => "\xe2\x89\xa1",
        '&le;'      => "\xe2\x89\xa4",
        '&ge;'      => "\xe2\x89\xa5",
        '&sub;'     => "\xe2\x8a\x82",
        '&sup;'     => "\xe2\x8a\x83",
        '&nsub;'    => "\xe2\x8a\x84",
        '&sube;'    => "\xe2\x8a\x86",
        '&supe;'    => "\xe2\x8a\x87",
        '&oplus;'   => "\xe2\x8a\x95",
        '&otimes;'  => "\xe2\x8a\x97",
        '&perp;'    => "\xe2\x8a\xa5",
        '&sdot;'    => "\xe2\x8b\x85",
        '&lceil;'   => "\xe2\x8c\x88",
        '&rceil;'   => "\xe2\x8c\x89",
        '&lfloor;'  => "\xe2\x8c\x8a",
        '&rfloor;'  => "\xe2\x8c\x8b",
        '&lang;'    => "\xe2\x8c\xa9",
        '&rang;'    => "\xe2\x8c\xaa",
        '&loz;'     => "\xe2\x97\x8a",
        '&spades;'  => "\xe2\x99\xa0",
        '&clubs;'   => "\xe2\x99\xa3",
        '&hearts;'  => "\xe2\x99\xa5",
        '&diams;'   => "\xe2\x99\xa6",
        '&OElig;'  => "\xc5\x92",
        '&oelig;'  => "\xc5\x93",
        '&Scaron;' => "\xc5\xa0",
        '&scaron;' => "\xc5\xa1",
        '&Yuml;'   => "\xc5\xb8",
        '&circ;'   => "\xcb\x86",
        '&tilde;'  => "\xcb\x9c",
        '&ensp;'   => "\xe2\x80\x82",
        '&emsp;'   => "\xe2\x80\x83",
        '&thinsp;' => "\xe2\x80\x89",
        '&zwnj;'   => "\xe2\x80\x8c",
        '&zwj;'    => "\xe2\x80\x8d",
        '&lrm;'    => "\xe2\x80\x8e",
        '&rlm;'    => "\xe2\x80\x8f",
        '&ndash;'  => "\xe2\x80\x93",
        '&mdash;'  => "\xe2\x80\x94",
        '&lsquo;'  => "\xe2\x80\x98",
        '&rsquo;'  => "\xe2\x80\x99",
        '&sbquo;'  => "\xe2\x80\x9a",
        '&ldquo;'  => "\xe2\x80\x9c",
        '&rdquo;'  => "\xe2\x80\x9d",
        '&bdquo;'  => "\xe2\x80\x9e",
        '&dagger;' => "\xe2\x80\xa0",
        '&Dagger;' => "\xe2\x80\xa1",
        '&permil;' => "\xe2\x80\xb0",
        '&lsaquo;' => "\xe2\x80\xb9",
        '&rsaquo;' => "\xe2\x80\xba",
        '&euro;'   => "\xe2\x82\xac" 
    ));

    preg_match_all('/[\xc2\xc3\xc5\xc6\xcb\xce\xcf][\x80-\xbf]|\xe2[\x80-\x99][\x82-\xac]/sx', $s, $m);

    foreach (array_unique($m[0]) as $c)
    {
        if (array_key_exists($c, $t))
        {
            $s = str_replace($c, $t[$c], $s);
        }
    }
    return $s;

}
// For users prior to PHP 4.3.0 you may do this:
function pmfeedunhtmlentities($string,$encoding) {
	global $Charset, $PmFeedXLateEncoding;

	// replace numeric entities
	$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
	$string = preg_replace('~&#([0-9]+);~e', 'chr(intval("\\1"))', $string);

	switch ($PmFeedXLateEncoding) {
	case "mb_convert_endcoding":
		if (function_exists('mb_convert_encoding'))
			$string=mb_convert_encoding($string, $Charset, $encoding);
		else
			$PmFeedXLateEncoding = "auto";
		break;
	case "iconv":
		if (function_exists('iconv'))
			$string=iconv($encoding, $Charset, $string);
		else
			$PmFeedXLateEncoding = "auto";
		break;
	case "pmfeed":
		if (preg_match('/utf-8/i', $encoding))
			$string = pmfeedutf8htmlentityencode($string);
		break;
	}
	if ($PmFeedXLateEncoding == "auto") {
		if (preg_match('/utf-8/i', $Charset)) {
			if (function_exists('mb_convert_encoding'))
				$string=mb_convert_encoding($string, $Charset, $encoding);
			else if (function_exists('iconv'))
				$string=iconv($encoding, $Charset, $string);
			else if (preg_match('/utf-8/i', $encoding))
				$string = pmfeedutf8htmlentityencode($string);
		} else {
			if (preg_match('/utf-8/i', $encoding))
				$string = pmfeedutf8htmlentityencode($string);
		}
	}

//	$string=iconv($encoding, $Charset, $string);
//Alternative is mb_convert_encoding, parms reversed.
	//$string=mb_convert_encoding($string, $Charset, $encoding);
	// replace literal entities
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	$string=strtr($string, $trans_tbl);
	return $string;
}

/*
 ======================================================================
 lastRSS 0.9.1
 
 Simple yet powerfull PHP class to parse RSS files.
 
 by Vojtech Semecky, webmaster @ webdot . cz
 
 Latest version, features, manual and examples:
 	http://lastrss.webdot.cz/

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ======================================================================
*/

/**
* lastRSS
* Simple yet powerfull PHP class to parse RSS files.
*/
class lastRSS {
	// -------------------------------------------------------------------
	// Public properties
	// -------------------------------------------------------------------
	var $default_cp = 'ISO-8859-1';
	var $CDATA = 'content';
	var $cp = '';
	var $items_limit = 0;
	var $stripHTML = False;
	var $date_format = '';

	// -------------------------------------------------------------------
	// Private variables
	// -------------------------------------------------------------------
	var $channeltags = array ('title', 'link', 'description', 'language',
		'copyright', 'managingEditor', 'webMaster', 'lastBuildDate',
		'rating', 'docs');
	var $itemtags = array('title', 'link', 'description', 'author',
		'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');
	var $imagetags = array('title', 'url', 'link', 'width', 'height');
	var $textinputtags = array('title', 'description', 'name', 'link');

	// -------------------------------------------------------------------
	// Parse RSS file and returns associative array.
	// -------------------------------------------------------------------
	function Get ($rss_url, $encoding) {
		global $PmFeedAltGet;

		// If CACHE ENABLED
		if ($this->cache_dir != '') {
			$cache_file = $this->cache_dir . '/rsscache_' . md5($rss_url);
			$timedif = @(time() - filemtime($cache_file));
			if ($timedif < $this->cache_time) {
				// cached file is fresh enough, return cached array
				$result = unserialize(join('', file($cache_file)));
				// set 'cached' to 1 only if cached file is correct
				if ($result) $result['cached'] = 1;
			} else {
				// cached file is too old, create new
				$result = $this->Parse($rss_url,$encoding);
				$serialized = serialize($result);
				if ($f = @fopen($cache_file, 'w')) {
					fwrite ($f, $serialized, strlen($serialized));
					fclose($f);
				}
				if ($result) $result['cached'] = 0;
			}
		}
		// If CACHE DISABLED >> load and parse the file directly
		else {
			$result = $this->Parse($rss_url,$encoding);
			if ($result) $result['cached'] = 0;
		}
		// return result
		return $result;
	}
	
	// -------------------------------------------------------------------
	// Modification of preg_match(); return trimed field with index 1
	// from 'classic' preg_match() array output
	// -------------------------------------------------------------------
	function my_preg_match ($pattern, $subject) {
		// start regullar expression
		preg_match($pattern, $subject, $out);

		// if there is some result... process it and return it
		if(isset($out[1])) {
			// Process CDATA (if present)
			if ($this->CDATA == 'content') {
				// Get CDATA content (without CDATA tag)
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			} elseif ($this->CDATA == 'strip') { // Strip CDATA
				$out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
			}
			// If code page is set convert character encoding to required
			if ($this->cp != '')
				$out[1] = iconv($this->rsscp, $this->cp.'//TRANSLIT', $out[1]);

			// Return result
			return trim($out[1]);
		} else {
			// if there is NO result, return empty string
			return '';
		}
	}

	// -------------------------------------------------------------------
	// Replace HTML entities &something; by real characters
	// -------------------------------------------------------------------
	function unhtmlentities ($string) {
		// Get HTML entities table
		$trans_tbl = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);
		// Flip keys<==>values
		$trans_tbl = array_flip ($trans_tbl);
		// Add support for &apos; entity (missing in HTML_ENTITIES)
		$trans_tbl += array('&apos;' => "'");
		// Replace entities by values
		return strtr ($string, $trans_tbl);
	}

	// -------------------------------------------------------------------
	// Parse() is private method used by Get() to load and parse RSS file.
	// Don't use Parse() in your scripts - use Get($rss_file) instead.
	// -------------------------------------------------------------------
	function Parse ($rss_url,$encoding) {
		global $PmFeedAltGet;

		// Open and load RSS file
		$rss_content = '';
		if ($f = @fopen($rss_url, 'r')) {
			$rss_content = '';
			while (!feof($f)) {
				$rss_content .= fgets($f, 4096);
			}
			fclose($f);
		} else {
			# Try alternative gets
			foreach ($PmFeedAltGet as $altgetfmt) {
				$altget=sprintf($altgetfmt, $rss_url);	
				if ($f = popen("$altget", 'r')) {
					while (!feof($f)) {
						$rss_content .= fgets($f, 4096);
					}
					fclose($f);
					break;
				}
			}
		}		

		if ($rss_content != '') {
			// Use $encoding if set
			if ($encoding != '') {
				$this->rsscp = $encoding;
				$result['encoding'] = $encoding;
			} else {
				// Parse document encoding
				$result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si",
					$rss_content);
				// if document codepage is specified, use it
				if ($result['encoding'] != '') {
					// This is used in my_preg_match()
					$this->rsscp = $result['encoding'];
				} else {
 					// This is used in my_preg_match()
					// otherwise use the default codepage
					$this->rsscp = $this->default_cp;
					$result['encoding'] = $this->default_cp;
				}
			}

			// Parse CHANNEL info
			preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
			foreach($this->channeltags as $channeltag) {
				$temp = $this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si",
					$out_channel[1]);
				if ($temp != '') {
					// Set only if not empty
					$result[$channeltag] = $temp; // Set only if not empty
				}
			}
			// If date_format is specified and lastBuildDate is valid
			if ($this->date_format != '' &&
			   ($timestamp = strtotime($result['lastBuildDate'])) !==-1) {
				// convert lastBuildDate to specified date format
				$result['lastBuildDate'] = date($this->date_format, $timestamp);
			}

			// Parse TEXTINPUT info
			preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);
				// This a little strange regexp means:
				// Look for tag <textinput> with or without any attributes, 
				// but skip truncated version <textinput /> (it's not beggining tag)
			if (isset($out_textinfo[2])) {
				foreach($this->textinputtags as $textinputtag) {
					$temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
					if ($temp != '') {
						// Set only if not empty
						$result['textinput_'.$textinputtag] = $temp;
					}
				}
			}
			// Parse IMAGE info
			preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
			if (isset($out_imageinfo[1])) {
				foreach($this->imagetags as $imagetag) {
					$temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si",
						$out_imageinfo[1]);
					if ($temp != '') {
						// Set only if not empty
						$result['image_'.$imagetag] = $temp;
					}
				}
			}
			// Parse ITEMS
			preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
			$rss_items = $items[2];
			$i = 0;
			$result['items'] = array(); // create array even if there are no items
			foreach($rss_items as $rss_item) {
				// If number of items is lower then limit: Parse one item
				if ($i < $this->items_limit || $this->items_limit == 0) {
					foreach($this->itemtags as $itemtag) {
						$temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
						if ($temp != '') {
							// Set only if not empty
							$result['items'][$i][$itemtag] = $temp; // Set only if not empty
						}
					}
					// Strip HTML tags and other stuff from DESCRIPTION
					if ($this->stripHTML && $result['items'][$i]['description']) {
						$result['items'][$i]['description'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['description'])));
					}
					// Strip HTML tags and other stuff from TITLE
					if ($this->stripHTML && $result['items'][$i]['title']) {
						$result['items'][$i]['title'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['title'])));
					}
					// If date_format is specified and pubDate is valid
					if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {
						// convert pubDate to specified date format
						$result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
					}
					// Item counter
					$i++;
				}
			}

			$result['items_count'] = $i;
			return $result;
		} else {
			// Error in opening return False
			return False;
		}
	}
}
/**
* End lastRSS
*/

/* pmfeed */
Markup('pmfeed','directives',"/^\(:pmfeed[ 	]*(.*?):\)\s*$/e",
	"pmfeed('$1')");


function pmfeed($opts) {
	global  $ImgExtPattern, $FarmD, $pagename, $MaxIncludes,
		$HTMLHeaderFmt, $PmFeedCacheDir, $Charset,
		$PmFeedTitleMark, $PmFeedItemMark, $PmFeedDescrBold,
		$PmFeedDescrItalic, $PmFeedDescrUnderline, $PmFeedDescrSmall,
		$PmFeedDescrStrong, $PmFeedMarkupReplace, $PmFeedDescrHeader,
		$PmFeedImageStyle, $PmFeedImageStyleEnd, $PmFeedXLateEncoding,
		$PmFeedAltGet;

	//Force encoding/decoding conversion style
	SDV($PmFeedXLateEncoding, 'auto');
	//Image styling
	SDV($PmFeedImageStyle, '%class=pmfeedimage%');
	//Image style end string, normally %%
	SDV($PmFeedImageStyleEnd, '%%');
	//Location of cache directory (must exist)
	SDV($PmFeedCacheDir, $FarmD.'/pub/cache');
	//Initial PmWiki markup for feed title
	SDV($PmFeedTitleMark, '!!');
	//Initial PmWiki markup for items
	SDV($PmFeedItemMark, '* %font-style=italic%');
	//Convert bold tags
	SDV($PmFeedDescrBold, '\'\'\'$1\'\'\'');
	//Convert italic tags
	SDV($PmFeedDescrItalic, '\'\'$1\'\'');
	//Convert underline tags
	SDV($PmFeedDescrUnderline, '{+$1+}');
	//Convert small tags
	SDV($PmFeedDescrSmall, '[-$1-]');
	//Convert strong tags: <strong>text</strong> to '''''text'''''
	SDV($PmFeedDescrStrong, '\'\'\'\'\'$1\'\'\'\'\'');
	//Convert header tags: <h4>text</h4> to ''text''
	SDV($PmFeedDescrHeader, '\'\'$1\'\'');
	//Attempt to escape markups special to PmWiki that might be found in description
	SDV($PmFeedMarkupReplace, '/([#*[\]])/');
	//Alternative ways to get RSS feed in case direct open fails.
	SDVA($PmFeedAltGet, array(
		'/usr/bin/curl -k %s',
		'/usr/bin/wget -O - %s',
		'/usr/bin/lynx -source %s'));

	// Determine this Group
	//
	$group = FmtPageName('$Group',$pagename);
	$name = FmtPageName('$Name',$pagename);

	// Process markup arguments first
	//
	$defaults = array(
		'feed'=>'http://www.pmwiki.org/wiki/Site/AllRecentChanges?action=rss',
		'title'=>'',
		'cache_time'=>2000,
		'encoding'=>'',
		'imagestyle'=>$PmFeedImageStyle,
		'itemspace'=>1,
		'max_count'=>0,
		'showpubdate'=>'false',
		'showencoding'=>'false',
		'showimages'=>'false',
		'showitems'=>'true',
		'showitemdescr'=>'true',
		'showtables'=>'false',
		'showtitle'=>'true',
		'showfeedxmllink'=>'false',
		'newwin'=>'true',
		'overrides'=>'true',
		'unsafe'=>'false'
	);

	$args = array_merge($defaults, ParseArgs($opts));
	$urladd='';


	// Allows overrides=false in the :pmfeed: markup to disallow
	// settings on the URL line.
	//
	$overrides = $args['overrides'];
	if ($overrides == 'false') {
		$_GET = NULL;
	}

	$cache_time = isset($_GET['cache_time']) ? $_GET['cache_time'] :
		$args['cache_time'];
	if (isset($_GET['cache_time']))
		$urladd.="&amp;cache_time=".urlencode($_GET['cache_time']);
	$encoding = isset($_GET['encoding']) ? $_GET['encoding'] :
		$args['encoding'];
	if (isset($_GET['encoding']))
		$urladd.="&amp;encoding=".urlencode($_GET['encoding']);
	$imagestyle = isset($_GET['imagestyle']) ? $_GET['imagestyle'] :
		$args['imagestyle'];
	if (isset($_GET['imagestyle']))
		$urladd.="&amp;imagestyle=".urlencode($_GET['imagestyle']);
	$itemspace = isset($_GET['itemspace']) ? $_GET['itemspace'] :
		$args['itemspace'];
	if (isset($_GET['itemspace']))
		$urladd.="&amp;itemspace=".urlencode($_GET['itemspace']);
	$showpubdate= isset($_GET['showpubdate']) ? $_GET['showpubdate'] :
		$args['showpubdate'];
	if (isset($_GET['showpubdate']))
		$urladd.="&amp;showpubdate=".urlencode($_GET['showpubdate']);
	$showencoding= isset($_GET['showencoding']) ? $_GET['showencoding'] :
		$args['showencoding'];
	if (isset($_GET['showencoding']))
		$urladd.="&amp;showencoding=".urlencode($_GET['showencoding']);
	$showimages = isset($_GET['showimages']) ? $_GET['showimages'] :
		$args['showimages'];
	if (isset($_GET['showimages']))
		$urladd.="&amp;showimages=".urlencode($_GET['showimages']);
	$showtables= isset($_GET['showtables']) ? $_GET['showtables'] :
		$args['showtables'];
	if (isset($_GET['showtables']))
		$urladd.="&amp;showtables=".urlencode($_GET['showtables']);
	$showitemdescr = isset($_GET['showitemdescr']) ? $_GET['showitemdescr'] :
		$args['showitemdescr'];
	if (isset($_GET['showitemdescr']))
		$urladd.="&amp;showitemdescr=".urlencode($_GET['showitemdescr']);
	$showitems = isset($_GET['showitems']) ? $_GET['showitems'] :
		$args['showitems'];
	if (isset($_GET['showitems']))
		$urladd.="&amp;showitems=".urlencode($_GET['showitems']);
	$showtitle = isset($_GET['showtitle']) ? $_GET['showtitle'] :
		$args['showtitle'];
	if (isset($_GET['showtitle']))
		$urladd.="&amp;showtitle=".urlencode($_GET['showtitle']);
	$showfeedxmllink = isset($_GET['showfeedxmllink']) ? $_GET['showfeedxmllink'] :
		$args['showfeedxmllink'];
	if (isset($_GET['showfeedxmllink']))
		$urladd.="&amp;showfeedxmllink=".urlencode($_GET['showfeedxmllink']);
	$max_count = isset($_GET['max_count']) ? $_GET['max_count'] :
		$args['max_count'];
	if (isset($_GET['max_count']))
		$urladd.="&amp;max_count=".urlencode($_GET['max_count']);
	$newwin= isset($_GET['newwin']) ? $_GET['newwin'] : $args['newwin'];
	if (isset($_GET['newwin']))
		$urladd.="&amp;newwin=".urlencode($_GET['newwin']);

	// For unsafe (?) things
	// Enabling unsafe would allow you to create a totally user driven
	// feed.  It's possible that some "nasty" markup could come in via
	// title if made user modifiable.
	$unsafe=$args['unsafe'];
	if ($unsafe == 'true') {
		$feed= isset($_GET['feed']) ? $_GET['feed'] : $args['feed'];
		if (isset($_GET['feed']))
			$urladd.="&amp;feed=".urlencode($_GET['feed']);
		$title= isset($_GET['title']) ? $_GET['title'] : $args['title'];
		if (isset($_GET['title']))
			$urladd.="&amp;title=".urlencode($_GET['title']);
	} else {
		$title=$args['title'];
		$feed=$args['feed'];
	}

	// Create lastRSS object
	$rss = new lastRSS;

	// $rss->stripHTML = True;

	// Set cache dir and cache time limit 
	// (don't forget to chmod cache to allow writing)
	$rss->cache_dir = $PmFeedCacheDir;
	$rss->cache_time = $cache_time;

	if ($newwin == 'true') {
		$pmnewwin='newwin ';
	} else {
		$pmnewwin='';
	}

	// Initialize output string
	$out="\n";

	// Try to load and parse RSS file
	$feed=str_replace('&amp;', '&', $feed);
	if ($rs = $rss->Get($feed,$encoding)) {
		$encoding=$rs['encoding'];

	    	// Show title or  clickable website rss title if not supplied
		if ($showtitle == 'true') {
			$rs[title]=preg_replace('/\n/','',strip_tags(pmfeedunhtmlentities($rs[title],$encoding)));
			if ($title != '') {
    				$out.="\n$PmFeedTitleMark %".$pmnewwin."class=pmfeedtitle%[[$rs[link]|$title\"$rs[title]\"]]";
			} else {
    				$out.="\n$PmFeedTitleMark %".$pmnewwin."class=pmfeedtitle%[[$rs[link]|$rs[title]]]";
			}
		} 
		// Display a link to the original feed xml
		if ($showencoding == 'true') {
    			$out.="[$encoding,CS:$Charset]";
		}
		// Display a link to the original feed xml
		if ($showfeedxmllink == 'true') {
    			$out.="[[$feed|(XML)]]";
		}
		$out.="\n";
	
    		// Show last published articles (title, link, description)
		if ($showitems == 'true') {
			$i=0;
			foreach($rs['items'] as $item) {
				if ($max_count && $i >= $max_count) {
					break;
				}
				$pmfeedtitle=preg_replace('/\n/','',strip_tags(pmfeedunhtmlentities($item['title'],$encoding)));
				$pmfeedlink=$item['link'];
				if ($pmfeedlink == '') {
					$pmfeeditem="$PmFeedItemMark %class=pmfeeditem%".$pmfeedtitle;
				} else {
					$pmfeeditem="$PmFeedItemMark %".$pmnewwin."class=pmfeeditem%[[$pmfeedlink|$pmfeedtitle]]";
				}
				$out.="$pmfeeditem";
				if ($showpubdate == 'true') {
					$out.="\\\\\n";
					$out.="%class=pmfeedpubdate% ".$item['pubDate']."%%";
				}
				if ($showitemdescr == 'true') {
					$out.="\\\\\n";
					$d=$item['description'];
					$d=pmfeedunhtmlentities($d,$encoding);

					//Hack to hide PmWiki Markup, pound and asterisk
					//... sigh, there could be a ton of these.
					$d=preg_replace($PmFeedMarkupReplace, '[=$1=]', $d);


					// Eliminate returns
					$d=preg_replace('/\n\n*/', '', $d);

					// Eliminate leading spaces
					$d=preg_replace('/^\s\s*/m', '', $d);

					// Handle paragraphs
					$d=preg_replace('/\s*<\/{0,1}p>\s*/m', '\\\\\\'."\n".'\\\\'."\n", $d);

					// Handle breaks
					$d=preg_replace('/\s*<[bh]r\s*\/*>\s*/m', '\\\\\\'."\n".'\\\\'."\n", $d);

					if ($showimages == 'true') {
						// Attempt to convert free standing image URLs to have thumb
						$d=preg_replace('/([^["\'])(http:[^ 	">]+'.$ImgExtPattern.')/is', '$1'.$imagestyle.'[[$2|$2]]'.$PmFeedImageStyleEnd, $d);
					} else {
						$d=preg_replace('/([^["\'])(http:[^ 	">]+'.$ImgExtPattern.')/is', '$1%newwin%[[$2|IMG]]%%', $d);
					}

					if ($showimages == 'true') {
						// Attempt to convert <a href="url"><img src="image"></a> to [[url|image]]
						$d=preg_replace('/<a\s+[^<>]*?href=["\']([^"\']+)["\'][^>]*>\s*<img [^<>]*src=["\']([^"\']+'.$ImgExtPattern.')["\'][^>]*>\s*[^<]*<\/a>/is', $imagestyle.'[[$1|$2]]'.$PmFeedImageStyleEnd, $d);
//						$d=preg_replace('/<a\s+.*?href="([^"\']+)"[^>]*>\s*<img\s+src="([^"\']+'.$ImgExtPattern.')["\'][^>]*>\s*[^<]*<\/a>/is', $imagestyle.'[[$1|$2]]'.$PmFeedImageStyleEnd, $d);
					} else {
						$d=preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>\s*<img\s+src="([^"]+)"[^>]*>\s*[^<]*<\/a>/is', '', $d);
					}

					// Attempt to convert <a href="url">name</a> to [[url|name]]
					$d=preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '[[$1|$2]]', $d);

					if ($showimages == 'true') {
						// Attempt to convert <img src into images
						$d=preg_replace('/<img [^<>]*src="([^"]+'.$ImgExtPattern.')"[^>]*>/is', $imagestyle.'$1'.$PmFeedImageStyleEnd, $d);
//						$d=preg_replace('/<img src="([^"]+'.$ImgExtPattern.')"[^>]*>/is', $imagestyle.'$1'.$PmFeedImageStyleEnd, $d);
					} else {
						$d=preg_replace('/<img src="([^"]+)"[^>]*>/is', '', $d);
					}

					//Table handling
					if ($showtables == 'true') {
						$d=preg_replace('/<table\([^>]*\)>/', '(:table:)'."\n", $d);
						$d=preg_replace('/<td[^>]*>/', "\n".'(:cell:)'."\n", $d);
						$d=preg_replace('/<tr[^>]*>/', "\n".'(:cell:)'."\n".'(:cellnr:)'."\n", $d);
						$d=preg_replace('/<\/table[^>]*>/', "\n".'(:tableend:)'."\n", $d);
					}


					//Convert bold tags
					$d=preg_replace('/<b>(.*?)<\/b>/', $PmFeedDescrBold, $d);
	
					//Convert italics tags
					$d=preg_replace('/<i>(.*?)<\/i>/', $PmFeedDescrItalic, $d);
	
					//Convert underline tags
					$d=preg_replace('/<u>(.*?)<\/u>/', $PmFeedDescrUnderline, $d);
	
					//Convert small tags
					$d=preg_replace('/<small>(.*?)<\/small>/', $PmFeedDescrSmall, $d);
	
					//Convert strong tags: <strong>text</strong> to '''''text'''''
					$d=preg_replace('/<strong>(.*?)<\/strong>/', $PmFeedDescrStrong, $d);
	
					//Convert header tags 
					$d=preg_replace('/<h\d>(.*?)<\/h\d>/', $PmFeedDescrHeader.'\\'."\n", $d);

					//Eliminate white space (lines) at head of item.
					$d=preg_replace('/^[\\\\\n][\\\\\n]*/','',$d);

					// Slaughter the rest of the html tags, prepend block
					$d="%block ".$pmnewwin."%".ltrim(strip_tags($d))." %%\n";

					//Eliminate white space (lines) at end of item.
					$d=preg_replace('/[\\\\\n][\\\\\n]*%%$/','%%',$d);

					$d=preg_replace('/[\r]/','&nbsp;',$d);

					//Add an extra lines if desired to end of item.
					for ($is = 1; $is <= $itemspace; $is++) { 
						$d.='\\\\'."\n\n";
					}
					$out.=$d;
				} else {
					$out.="\n";
				}
				$i++;
       			}
		}
	} else {
    		$out.="Error: It's not possible to reach RSS file $feed ...\n";
	}
	if ($pmfeeddebug == 'true') {
		$debug_file = $PmFeedCacheDir . '/debug_';
		if ($f = @fopen($debug_file, 'a+')) {
			fwrite ($f, $out);
			fclose($f);
		}
	}

	PRR(); return $out;
}

