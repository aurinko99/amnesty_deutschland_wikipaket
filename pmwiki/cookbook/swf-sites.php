<?php if (!defined('PmWiki')) exit ();

/* 
    
	Original info:
	copyright 2010, Adam Overton
	based on code by Jon Haupt, copyright 2007-8
	which was built on code from swf.php copyright 2004 Patrick R. Michaud
	and from quicktime.php copyright 2006 Sebastian Siedentopf.
	
	YouTube, Vimeo, GoogleVideo, and FlickrVid code majorly amended by Adam Overton, 
	starting 2009-09-21, based primarily upon the original code of Jon Haupt, 
	but with the updated ability to define parameters from the YouTube api:
	http://code.google.com/apis/youtube/player_parameters.html. Additional suggestions and code
	from Jabba Laci & Byron Lunz was incorporated for non-XHTML-compliant output.
	
	This file is distributed under the terms of the GNU General Public 
	License as published by the Free Software Foundation; either 
	version 2 of the License, or (at your option) any later version.  

	. . .
	
	This module enables embedding of Google Video, Vimeo, and YouTube movies into wiki pages.
	The simplest use of any of these includes:
		(:googlevideo id:)
		(:youtube id:)
		(:vimeo id:)
	where 'id' is the unique alpha-numberic id assigned to your movie.
	For example, if the URL to play the video is:
		http://video.google.com/videoplay?docid=348928491823948192,
	you would do 
		(:googlevideo 348928491823948192:)
	
	In addition, Replace-On-Edit Patterns ($ROEPatterns) have been created for each recipe, so that visitors can simply copy-and-paste the embed code from the respective video website, click save or save-and-edit, and have the code automatically converted to the appropriate pmwiki markup. If this is undesired, an admin can disable this in config.php by setting $YouTubeROEenabled, $VimeoROEenabled, etc to false.
	
	For more info on how to use this, see http://www.pmwiki.org/wiki/Cookbook/Flash
		
	. . .
		
	Versions:
	* 2011-04-07 - YOUTUBE & VIMEO - bugfix: iframe ROE for both was accidentally throwing away height & width values; YOUTUBE: adjusted iframe output that has changed since the last version.
	* 2010-12-19 - YOUTUBE - bugfix to prevent display of extra url arguments &blah=blah....
	* 2010-12-18 - 
		** YOUTUBE, VIMEO & GOOGLEVIDEO:
			*** added YouTubeSimpleEmbed, VimeoSimpleEmbed, & GoogleVideoSimpleEmbed (respectively) - YouTube, Vimeo & GoogleVideo vids can now be embedded by simply using the URL (rather than the full embed code). to disable, set $YouTubeSimpleEmbed=false (or $VimeoSimpleEmbed=false, or $GoogleVideoSimpleEmbed=false) in config.php
	* 2010-11-14 - YOUTUBE: fixed 2 minor bugs in YouTube code
	* 2010-11-05 -
		** GOOGLEVIDEO: cleaned up code and ensured ability to use params: initialTime, showShareButtons, and playerMode
	* 2010-09-10 - fixed a bug in ROEPattern for the Universal Vimeo embed code
	* 2010-08-25 - 
		** VIMEO: 
			*** added an ROEPattern for the new univeral version of the Vimeo embed code; the ROE for the old version of Vimeo embed code remains enabled as well, since both can be used by embedders. To keep using the old version of the player, one can set $Vimeo_enableNewPlayer=false;. More info on the new Vimeo player, which can run on the iPhone, iPads, and in HTML5: http://vimeo.com/blog:334
			*** added an ROEPattern to update old Vimeo markup on pages - parameters like 'show_title' have now been shortened simply to 'title'
			*** removed the $Vimeo_ROEFmt variable - now the ROEPattern displays all possible variables as set up in your $VimeoDefaultParams array
			*** added $VimeoOverrideUserParams - admins can provide a list of default parameters that will override any user-input. example: $VimeoOverrideUserParams = 'color,fullscreen';  will prevent users from changing the availability of fullscreen mode, and from changing the color of the video player controls
		** YOUTUBE:
			*** added code for playing YouTube video with the new beta iframe version of the player, which works with HTML5. Since it's in beta version, and doesn't have nearly as many features and inputs as the older version, it is off by default. To use this new version, place $YouTube_enableNewPlayer=true in your config.php.
			*** updated YouTube ROEPattern code... Created new ROEPattern for new (beta) YouTube iframe code
			*** removed $YouTubeROEFmt - the ROEPattern now displays whatever properties it finds in the embed code.
			*** added $YouTubeOverrideUserParams - admins can provide a list of default parameters that will override any user-input. example: $YouTubeOverrideUserParams = 'fs,width,height';  will prevent users from changing the availability of fullscreen mode, and from changing the width and height of the embedded videos
	* 2010-05-17 - moved FlickrVid recipe into a separate recipe with FlickrSlideshow
	* 2009-10-25 - slightly cleaned up & simplified youtube code (scaling); removed plwidth & plheight from docs, to prevent misuse.
	* 2009-10-20 - 
		** Vimeo, GoogleVideo, FlickrVid: updated (:vimeo:) & (:googlevideo:) & (:flickrvid:) to allow arguments, and created a number of various setup variables accessed in config.php
		** Vimeo, Youtube, GoogleVideo, FlickrVid: added ROEPatterns to auotmatically convert pasted <embed> code with the appropriate pmwiki video markup
		** cleaned up YouTube code
		** fixed GoogleVideo bug that prevented fullscreen
		** permitted option of compliant and non-compliant XHTML versions of each recipe (except FlickrVid).
		** removed $YouTubeDefaultHeightFmt & ..WidthFmt (for all recipes) - default width and height are now included in $YouTubeDefaultParams, $VimeoDefaultParams, and so forth.
		** replaced/updated much of the documentation
	* 2009-10-09 - YouTube: added 'scale' option for youtube; returned youtube code back to being xhtml 1.0 compliant; incorporated suggestion from Byron Lunz for non-XHTML-compliant version, via $YouTube_XHTMLcompliant
	* 2009-09-21b: YouTube: added ability to embed YouTube playlists
	* 2009-09-21: Updated swf-sites.php - changed YouTube code so that it accepts valid YouTube api parameters
	
*/

# Version date
$RecipeInfo['SWFSites']['Version'] = '2011-04-07';


### VIMEO
# More info on new universal version: http://vimeo.com/blog:334
SDV($Vimeo_enableNewPlayer, true);
SDV($VimeoSimpleEmbed, true);
# some parameter names have changed - drops the "show_" in some param names
SDVA($VimeoDefaultParams, array(
	'width' => '400'
	,'height' => '327'
	,'scale' => '1'
	,'title' => '1'
	,'byline' => '1'
	,'portrait' => '0'
	,'color' => '00ADEF'  # color of text
	,'fullscreen' => '1'
	,'autoplay' => '0'
	,'loop' => '0'
));

SDV($Vimeo_XHTMLcompliant, true);
SDV($VimeoROEenabled, true);
SDV($VimeoOverrideUserParams, ''); # make a list of the parameters above that users shouldn't have control over, as in $VimeoOverrideUserParams = 'color,fullscreen';


#####################
# EMBED (:VIMEO:)
if($Vimeo_enableNewPlayer)
	Markup('vimeo', '<img', "/\\(:vimeo\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowVimeoUniversalVideo('$1','$2')");  # new version
else
	Markup('vimeo', '<img', "/\\(:vimeo\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowVimeoOLDVideo('$1','$2')");  # old version


# SIMPLE EMBED: VIMEO
## embed vimeo by simply pasting the url: http://www.vimeo.com/16894768
## automatically embeds using the default parameters
## set ____ to true if you want to convert to PmWiki youtube markup

if($VimeoSimpleEmbed) {
	$VimeoSimplePattern = '/(`?)(http:\\/\\/(www\\.)?vimeo\\.com\\/(\\d+))/e';
	$VimeoSimpleFunc = "'$4','',array('$1','$2')";
	
	if($Vimeo_enableNewPlayer)
		Markup('VimeoSIMPLE', '<img', $VimeoSimplePattern, "ShowVimeoUniversalVideo($VimeoSimpleFunc)");
	else
		Markup('VimeoSIMPLE', '<img', $VimeoSimplePattern, "ShowVimeoOLDVideo($VimeoSimpleFunc)");
		
}
######################




#### ROEPatterns - VIMEO EMBED CONVERSION

# UPDATE OLD VIMEO MARKUP
# some of the parameters like 'show_title' have been shortened to 'title' in the new version - this removes them
if ($VimeoROEenabled) {

	if($Vimeo_enableNewPlayer) {
		$ROEPatterns['!\(\:vimeo [^\)]*show_[^\)]*\:\)!ie'] = "VimeoMarkupUpdateROE(PSS('$0'))";	
		function VimeoMarkupUpdateROE($vimeopattern) {
			$vimeopattern = str_replace("show_","",$vimeopattern);
			return $vimeopattern;
		}
	}

	# *** VIMEO-EMBED-CODE-ROE: NEW UNIVERSAL VERSION - AUGUST 2010 ***
	# Converts pasted Vimeo embed code into valid pmwiki (:vimeo:) code
	# More info: http://vimeo.com/blog:334
	# <iframe src="http://player.vimeo.com/video/5842670" width="400" height="270" frameborder="0"></iframe>
	$ROEPatterns['!<iframe.*player\.vimeo\.com.*<\/iframe>!ie'] = "VimeoROE(PSS('$0'))";
	function VimeoROE($vimeopattern) {
		global $VimeoDefaultParams;

		preg_match("#/video/(\d+)\?*#", $vimeopattern, $matchesVideoCode);
		$id = $matchesVideoCode[1];

		# gather all other parameters
		preg_match('#\?([^\"]+)\"#', $vimeopattern, $matchesParams);
		#echo "test: $matchesParams[1]"; break;
		#$params .= " scale=1 ".str_replace("&amp;"," ",$matchesParams[1]);  # replace &amp; with space
		# if params aren't provided, plug in default params so that user knows what's available
		if($matchesParams[1]) {
			$paramsArray = explode("&amp;",$matchesParams[1]);  # separate params
			foreach($paramsArray as $arr) {
				# separate params into key=>val pairs, to be used in comparison with DefaultParamArray
				list($arrKey,$arrVal) = explode("=",$arr);
				$paramsArrayWithKeys[$arrKey] = $arrVal;
			}
			
			# add in elements from the DefaultParamArray not already provided
			$paramsArray = array_merge($VimeoDefaultParams,$paramsArrayWithKeys);
		} else {
			$paramsArray = $VimeoDefaultParams;
		}
		
		# gather parameters - width, height
		preg_match('#width=["\'](\d+)["\']\s+height=["\'](\d+)["\']#', $vimeopattern, $matchesWH);
		$paramsArray['width'] = $matchesWH[1];
		$paramsArray['height'] = $matchesWH[2];

		# convert back to string
		$params = '';
		foreach($paramsArray as $key => $val) {
			$params .= " $key=$val";
		}
		#echo $params; break;
		
		# output
		$out = "(:vimeo $id$params:)"; #echo $out; break;
		return $out;
	}
	

	# *** VIMEO-EMBED-CODE-ROE: OLD, NON-HTML5 EMBED VERSION ***
	# Converts pasted Vimeo embed code into valid pmwiki (:vimeo:) code
	$ROEPatterns['!<object.*vimeo\.com.*<\/object>!ie'] = "VimeoOLD_ROE(PSS('$0'))";


	function VimeoOLD_ROE($vimeopattern) {
		global $VimeoDefaultParams;
		
		# gather parameters - width, height
		preg_match('#width=["\'](\d+)["\'] height=["\'](\d+)["\']#', $vimeopattern, $matches);
		$params = 'width='.$matches[1].' height='.$matches[2];
		$params .= " scale=".$VimeoDefaultParams['scale'];

		# gather other parameters
		preg_match('#clip_id=(\d+)&amp;server=vimeo.com&amp;([^\"\']*)[\"\']#', $vimeopattern, $matchesParams);
		$id = $matchesParams[1];
		$params .= " ".str_replace("&amp;"," ",$matchesParams[2]);
		
		# output
		# remove empty 'color=' (sometimes provided by vimeo embed code) - causes error below in ParseArgs
		$params = preg_replace('#(color=\\s+|color=$)#','',$params);
		
		$out = "(:vimeo $id $params:)";
		return $out;
	}
}
###########


########
# *** NEW UNIVERSAL VIMEO PLAYER FUNCTION ***
function ShowVimeoUniversalVideo($id, $args='', $escape='') {
	global $VimeoDefaultParams, $VimeoOverrideUserParams;
	
	# escape SIMPLE EMBED if url is preceded by a `, ex.: `http://...
	if($escape[0]=="`") return $escape[1];
	
	# cleanup for new universal version - several params shortened from 'show_param' to 'param' (title, byline, portrait)
	$args = str_replace("show_","",$args);

	# gather up args
	$args = ParseArgs($args);
	
	# determine if any parameters/args should be overridden using the default values
	if($VimeoOverrideUserParams) {
		$VimeoOverrideUserParams = str_replace(" ","",$VimeoOverrideUserParams); # remove spaces
		foreach(explode(",",$VimeoOverrideUserParams) as $key) {
			$overrideArrayKeys[$key] = '';
		}
		$args = array_diff_key($args, $overrideArrayKeys);
	}
	
	# add default parameters before parsing arguments
	$args = array_merge($VimeoDefaultParams, $args); # uses VimeoDefaultParams, unless supplied by user

	# define width, height & scale
	$scale = $args['scale']; 
	$width = $args['width'] * $scale;
	$height = $args['height'] * $scale;
	
	if($args) {
		# remove width, height, scale params from param list, if there are any - they'll go elsewhere
		# all other params go into $params
		$args = array_diff_key($args, array('width'=>'','height'=>'','scale'=>''));
		# create parameter string, in the form: ?arg1=val1&amp;arg2=val2
		$i = 0;
		foreach($args as $key => $val) {
			if ($key!='#') {
				#echo "$key - $val<br />";
				if ($i!=0) $params .= "&amp;";
				$params .= "$key=$val";
				$i++;
			}
		}
	}
	#echo $params;

	# Examples
	# <iframe src="http://player.vimeo.com/video/14413519?portrait=0" width="601" height="398" frameborder="0"></iframe>
	# 
	# OR one with more parameters
	# 
	# <iframe src="http://player.vimeo.com/video/14413519?title=0&amp;byline=0&amp;portrait=0&amp;color=ff0179&amp;autoplay=1&amp;loop=1" width="601" height="398" frameborder="0"></iframe>

	# Output
	$out = "<iframe src='http://player.vimeo.com/video/$id?$params' width='$width' height='$height' frameborder='0'></iframe>";

	return Keep($out);
}



# *** OLD VIMEO FUNCTION ***
function ShowVimeoOLDVideo($id, $args='', $escape='') {
	global $VimeoDefaultParams, $Vimeo_XHTMLcompliant, $VimeoOverrideUserParams;

	# escape SIMPLE EMBED if url is preceded by a `, ex.: `http://...
	if($escape[0]=="`") return $escape[1];
	
	# gather up args
	$args = ParseArgs($args);
	
	# determine if any parameters/args should be overridden using the default values
	if($VimeoOverrideUserParams) {
		$VimeoOverrideUserParams = str_replace(" ","",$VimeoOverrideUserParams); # remove spaces
		foreach(explode(",",$VimeoOverrideUserParams) as $key) {
			$overrideArrayKeys[$key] = '';
		}
		$args = array_diff_key($args, $overrideArrayKeys);
	}
	
	# add default parameters before parsing arguments
	$args = array_merge($VimeoDefaultParams, $args); # uses VimeoDefaultParams, unless supplied by user

	# define width & height
	$scale = $args['scale']; 
	$width = $args['width'] * $scale;
	$height = $args['height'] * $scale;
	
	if($args) {
		# remove width, height, scale params if there are any - they're non-api, so not necessary for parameter string
		$args = array_diff_key($args, array('width'=>'','height'=>'','scale'=>''));
		# create parameter string, in the form &amp;arg1=val1&amp;arg2=val2
		$i = 0;
		foreach($args as $key => $val) {
			if ($key!='#') {
				#echo "$key - $val<br />";
				$params .= "&amp;$key=$val";
				$i++;
			}
		}
	}

	$url = "http://vimeo.com/moogaloop.swf?clip_id=$id&amp;server=vimeo.com$params";
	#echo "<br />$url";

	# Output
	if ($Vimeo_XHTMLcompliant) {
		# XHTML 1.0 COMPLIANT
		$out = "\n<object type='application/x-shockwave-flash' width='$width' height='$height' data='$url'>";
		$out .= "\n  <param name='quality' value='best' />";
		$out .= "\n  <param name='allowfullscreen' value='true' />";
		$out .= "\n  <param name='allowscriptaccess' value='always' />";
		$out .= "\n  <param name='wmode' value='transparent' />";
		#$out .= "\n  <param name='scale' value='showAll' />";
		$out .= "\n  <param name='movie' value='$url' />";
		$out .= "\n</object>";
	} else {
		$out = "\n<object width='$width' height='$height'> ";
		$out .= "\n  <param name='allowFullScreen' value='true' />";
		$out .= "\n  <param name='allowscriptaccess' value='always' />";
		#$out .= "\n  <param name='scale' value='showAll' />";
		$out .= "\n  <param name='wmode' value='transparent' />";
		$out .= "\n  <param name='movie' value='$url' />";
		$out .= "\n  <embed src='$url' type='application/x-shockwave-flash' allowscriptaccess='always' allowfullscreen='true' wmode='transparent' width='$width' height='$height'></embed>";
		$out .= "\n</object>";
	}

	return Keep($out);
}


##############################

### YOUTUBE
SDV($YouTubeSimpleEmbed, true);
SDV($YouTube_enableNewPlayer, false);
SDVA($YouTubeDefaultParams, array(
	'width' => '425'
	,'height' => '344'
	,'plwidth' => '480' # slightly different default size for embedding playlists
	,'plheight' => '385' # slightly different default size for embedding playlists
	,'scale' => '1'
	,'fs' => '1'
	,'hd' => '1'
	,'nocookie' => '1'
));
SDV($YouTube_XHTMLcompliant, true);
SDV($YouTubeROEenabled, true);
SDV($YouTubeOverrideUserParams, ''); # make a list of the parameters above that users shouldn't have control over, as in $YouTubeOverrideUserParams = 'fs,width,height';


#####################
# EMBED: (:YOUTUBE:)
if($YouTube_enableNewPlayer)
	Markup('youtube', '<img', "/\\(:youtube\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowYouTubeIFRAME('$1','$2')");
	else
	Markup('youtube', '<img', "/\\(:youtube\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowYouTubeOLD('$1','$2')");


# SIMPLE EMBED: YOUTUBE
## embed youtube by simply pasting the url: 
	## http://www.youtube.com/watch?v=nr-SZXIVvuo
	## http://www.youtube.com/view_play_list?p=0B86E30DB3034994  (playlist)
## automatically embeds using the default parameters
## set ____ to true if you want to convert to PmWiki youtube markup

if($YouTubeSimpleEmbed) {
	$youtubeSimplePattern = '/(`?)(http:\\/\\/(www\\.)?youtube\\.com\\/(watch|view_play_list)\\?([vp])=([^&\\s]+)[^\\s]*)/e';
	# sets playlist=1 if playlist is detected
	$youtubeSimpleFunc = "'$6',str_replace(array('v','p'), array('','playlist=1'), '$5'), array('$1','$2')";
	
	if($YouTube_enableNewPlayer)
		Markup('youtubeSIMPLE', '<img', $youtubeSimplePattern, "ShowYouTubeIFRAME($youtubeSimpleFunc)");
	else
		Markup('youtubeSIMPLE', '<img', $youtubeSimplePattern, "ShowYouTubeOLD($youtubeSimpleFunc)");
}
######################



# ROEPatterns - YOUTUBE EMBED CONVERSION 
# Converts pasted youtube embed code into valid pmwiki (:youtube:) code
if ($YouTubeROEenabled) {

	# *** YOUTUBE-EMBED-CODE-ROE: NEW IFRAME EMBED VERSION --- EXPERIMENTAL ***
	# example: 
	# <iframe title="YouTube video player" width="480" height="390" src="http://www.youtube.com/embed/X-4_BVLnAgw" frameborder="0" allowfullscreen></iframe>
	#$ROEPatterns['!<iframe.*youtube-player.*<\/iframe>!ie'] = "YouTubeIFRAME_ROE(PSS('$0'))";
	$ROEPatterns['!<iframe.*youtube\.com/embed/.*<\/iframe>!ie'] = "YouTubeIFRAME_ROE(PSS('$0'))";
	function YouTubeIFRAME_ROE($videopattern) {
		global $YouTubeDefaultParams;

		preg_match("#/embed/([^\"]+)[\"\?]#", $videopattern, $matchesID);
		$id = $matchesID[1];

		$params = '';

		# check for playlist
		#if(strpos($videocode, '/p/')) {
		#	$params = " playlist=1 ";
		#	$pl = 1;
		#}

		# gather all other parameters after the ?-mark
		if(preg_match('#\?([^\"]+)\"#', $videopattern, $matchesParams)) {
			# if params aren't provided, plug in default params so that user knows what's available
			$paramsArray = explode("&amp;",$matchesParams[1]);  # separate params
			foreach($paramsArray as $arr) {
				# separate params into key=>val pairs, to be used in comparison with DefaultParamArray
				list($arrKey,$arrVal) = explode("=",$arr);
				$paramsArrayWithKeys[$arrKey] = $arrVal;
			}
		} else {
			$paramsArrayWithKeys = array();
		}
			
		# gather width & height
		preg_match('#width=["\'](\d+)["\']\s+height=["\'](\d+)["\']#', $videopattern, $matchesWH);
		#$params = 'width='.$matchesWH[1].' height='.$matchesWH[2];
		$paramsArrayWithKeys['width'] = $matchesWH[1];
		$paramsArrayWithKeys['height'] = $matchesWH[2];

		# add in elements from the DefaultParamArray not already provided
		$paramsArray = array_merge($YouTubeDefaultParams,$paramsArrayWithKeys);
		
		# get rid of plwidth & plheight if not a playlist
		if(!$pl) $paramsArray = array_diff_key($paramsArray, array('plwidth'=>'','plheight'=>''));
		else {
			# if it is a playlist, then set plwidth and plheight to width and height
			$paramsArray['plwidth'] = $paramsArrayWithKeys['width'];
			$paramsArray['plheight'] = $paramsArrayWithKeys['height'];
			# remove regular width and height params, leave behind plwidth and plheight
			$paramsArray = array_diff_key($paramsArray, array('width'=>'','height'=>''));
		}
		
		# convert back to string
		foreach($paramsArray as $key => $val) {
			$params .= " $key=$val";
		}
		#echo $params; break;
		
		# output
		$out = "(:youtube $id$params:)"; #echo $out; break;
		return $out;
	}



	# *** YOUTUBE-EMBED-CODE-ROE: OLD NON-IFRAME EMBED VERSION ***
	$ROEPatterns['!<object.*http://www\.youtube(-nocookie)?\.com.*<\/object>!ie'] = "YouTubeROE_NONIFRAME(PSS('$0'))";
	function YouTubeROE_NONIFRAME($videocode) {
		global $YouTubeDefaultParams;

		# get videoID
		preg_match('#/[vp]/([^\?]+)\?#', $videocode, $matchesID);
		$id = $matchesID[1];

		$params = '';

		# check for playlist
		if(strpos($videocode, '/p/')) {
			$params = " playlist=1 ";
			$pl = 1;
		}

		# gather width & height
		preg_match('#<object width="(\d+)" height="(\d+)"#', $videocode, $matchesWH);
		$paramsArrayWithKeys['width'] = $matchesWH[1];
		$paramsArrayWithKeys['height'] = $matchesWH[2];
		
		# gather other parameters - border, color1, color2, rel, etc
		preg_match('#\?([^\"]+)\"#', $videocode, $matchesParams);
		# YouTube isn't consistent in its use of &amp; and &, so switch it out...
		$matchesParams = str_replace("&amp;","&", $matchesParams[1]);
		# if params aren't provided, plug in default params so that the user knows what's available
		#$paramsArray = explode("&amp;",$matchesParams[1]);  # separate params
		$paramsArray = explode("&",$matchesParams);  # separate params
		foreach($paramsArray as $arr) {
			# separate params into key=>val pairs, to be used in comparison with DefaultParamArray
			list($arrKey,$arrVal) = explode("=",$arr);
			$paramsArrayWithKeys[$arrKey] = $arrVal;
		}
		
		# add in elements from the DefaultParamArray not already provided
		$paramsArray = array_merge($YouTubeDefaultParams,$paramsArrayWithKeys);
		
		# get rid of plwidth & plheight if not a playlist
		if(!$pl) $paramsArray = array_diff_key($paramsArray, array('plwidth'=>'','plheight'=>''));
		else {
			# if it is a playlist, then set plwidth and plheight to width and height
			$paramsArray['plwidth'] = $paramsArrayWithKeys['width'];
			$paramsArray['plheight'] = $paramsArrayWithKeys['height'];
			# remove regular width and height params, leave behind plwidth and plheight
			$paramsArray = array_diff_key($paramsArray, array('width'=>'','height'=>''));
		}
		
		# convert back to string
		foreach($paramsArray as $key => $val) {
			$params .= " $key=$val";
		}
		#echo $params; break;

		# append extra parameters after the videocode
		$out = "(:youtube $id$params:)";
		return $out;
	}
}


#################

# *** NEW YOUTUBE PLAYER USING IFRAME ***
# EXPERIMENTAL -- most parameters don't seem to be working yet
function ShowYouTubeIFRAME($id, $args='', $escape='') {
	global $YouTubeDefaultParams, $YouTubeOverrideUserParams;
	
	# escape SIMPLE EMBED if url is preceded by a `, ex.: `http://...
	if($escape[0]=="`") return $escape[1];
	
	# gather args
	$args = ParseArgs($args); # user input only - will check it against default values below
	
	# determine if any parameters/args should be overridden using the default values
	if($YouTubeOverrideUserParams) {
		$YouTubeOverrideUserParams = str_replace(" ","",$YouTubeOverrideUserParams); # remove spaces
		foreach(explode(",",$YouTubeOverrideUserParams) as $key) {
			$overrideArrayKeys[$key] = '';
		}
		$args = array_diff_key($args, $overrideArrayKeys);
	}
	
	# add default parameters before parsing arguments
	$args = array_merge($YouTubeDefaultParams, $args); # uses VimeoDefaultParams, unless supplied by user

	
	# check to see if it's a regular video, or a playlist consisting of many videos
	if ($args['playlist']=="1" || $args['playlist']=="true") { $vidtype = 'p'; $playlist_flag = true; }
		else $vidtype = 'v';
		
	# define width & height & scale
	if ($args['width']) {
		# use user-supplied width if it's supplied
		$width = $args['width'];
	} else {
		# default vals: check to see if it's a regular video, or a playlist consisting of many videos
		if ($playlist_flag) $width = $YouTubeDefaultParams['plwidth'];
		else $width = $YouTubeDefaultParams['width'];
	}
	if ($args['height']) {
		# use user-supplied height if it's supplied
		$height = $args['height'];
	} else {
		# default vals: check to see if it's a regular video, or a playlist consisting of many videos
		if ($playlist_flag) $height = $YouTubeDefaultParams['plheight'];
		else $height = $YouTubeDefaultParams['height'];
	}
	## one can supply a 'scale' default different than '1', but it's not suggested, as it could be confusing to users
	if ($args['scale']) $scale = $args['scale'];
		else $scale = $YouTubeDefaultParams['scale'];

	$width = $width * $scale;
	$height = $height * $scale;

	# if not a playlist, remove plwidth and plheight from default array in order to elimnate them
	if($vidtype=="v") {
		$YouTubeDefaultParams = array_diff_key($YouTubeDefaultParams, array('plwidth'=>'','plheight'=>''));
	}

	# get cookie info before array is cleared from the api-array
	$nocookieArg = $args['nocookie'];
	
	# create api-url - need to clear out non-api params first
	if($args) {
		# remove width, height, scale, playlist and nocookie params if present - not necessary for parameter string
		$args = array_diff_key($args, 
			array('width'=>'','height'=>'','plwidth'=>'','plheight'=>'','scale'=>'','playlist'=>'','nocookie'=>''));

		# create parameter string, in the form ?arg1=val1&arg2=val2
		$params = '';
		$i = 0;
		foreach($args as $key => $val) {
			if ($key!='#') {
				#echo "$key - $val<br />";
				if($i!=0) $params .= "&";
				$params .= "$key=$val";
				$i++;
			}
		}
	}
	if ($params) $params = "?$params";
	#echo $params; break;

	/*
	# Privacy: NoCookie Option
	if ($YouTubeDefaultParams['nocookie'] || $nocookieArg) 
		$url = "http://www.youtube-nocookie.com/$vidtype/$id$params";
		else $url = "http://www.youtube.com/$vidtype/$id$params";
	*/

	# Examples
	# <iframe class="youtube-player" type="text/html" width="873" height="525" src="http://www.youtube.com/embed/nr-SZXIVvuo" frameborder="0"></iframe>

	# Output
	$out = "<iframe class='YouTube video player' width='$width' height='$height' src='http://www.youtube.com/embed/$id$params' frameborder='0' allowfullscreen></iframe>";

	return Keep($out);
}



# *** OLD YOUTUBE PLAYER USING OBJECT & EMBED ***
function ShowYouTubeOLD($id, $args='', $escape='') {
	global $YouTubeDefaultParams, $YouTube_XHTMLcompliant, $YouTubeOverrideUserParams;
	
	# escape SIMPLE EMBED if url is preceded by a `, ex.: `http://...
	if($escape[0]=="`") return $escape[1];
	
	# $args here is unlike the other recipes in that it waits to see if it's a playlist, and checks for the presence width/height params before combining default and current param-arrays. this is to make sure default values are implemented correctly
	$args = ParseArgs($args); # user input only - will check it against default values below
		
	# determine if any parameters/args should be overridden using the default values
	if($YouTubeOverrideUserParams) {
		$YouTubeOverrideUserParams = str_replace(" ","",$YouTubeOverrideUserParams); # remove spaces
		foreach(explode(",",$YouTubeOverrideUserParams) as $key) {
			$overrideArrayKeys[$key] = '';
		}
		$args = array_diff_key($args, $overrideArrayKeys);
	}
	
	# add default parameters before parsing arguments
	$args = array_merge($YouTubeDefaultParams, $args);

	
	# check to see if it's a regular video, or a playlist consisting of many videos
	if ($args['playlist']=="1" || $args['playlist']=="true") { $vidtype = 'p'; $playlist_flag = true; }
		else $vidtype = 'v';

	# define width & height & scale
	if ($args['width']) {
		# use user-supplied width if it's supplied
		$width = $args['width'];
	} else {
		# default vals: check to see if it's a regular video, or a playlist consisting of many videos
		if ($playlist_flag) $width = $YouTubeDefaultParams['plwidth'];
		else $width = $YouTubeDefaultParams['width'];
	}
	if ($args['height']) {
		# use user-supplied height if it's supplied
		$height = $args['height'];
	} else {
		# default vals: check to see if it's a regular video, or a playlist consisting of many videos
		if ($playlist_flag) $height = $YouTubeDefaultParams['plheight'];
		else $height = $YouTubeDefaultParams['height'];
	}
	## one can supply a 'scale' default different than '1', but it's not suggested, as it could be confusing to users
	if ($args['scale']) $scale = $args['scale'];
		else $scale = $YouTubeDefaultParams['scale'];

	$width = $width * $scale;
	$height = $height * $scale;


	# get cookie info before array is cleared from the api-array
	$nocookieArg = $args['nocookie'];
	
	# create api-url - need to clear out non-api params first
	if($args) {
		# remove width, height, scale, playlist and nocookie params if present - not necessary for parameter string
		$args = array_diff_key($args, 
			array('width'=>'','height'=>'','plwidth'=>'','plheight'=>'','scale'=>'','playlist'=>'','nocookie'=>''));

		# create parameter string, in the form ?arg1=val1&arg2=val2
		$params = "?";
		$i = 0;
		foreach($args as $key => $val) {
			if ($key!='#') {
				#echo "$key - $val<br />";
				if($i!=0) $params .= "&";
				$params .= "$key=$val";
				$i++;
			}
		}
	}

	# Privacy: NoCookie Option
	if ($YouTubeDefaultParams['nocookie'] || $nocookieArg) 
		$url = "http://www.youtube-nocookie.com/$vidtype/$id$params";
		else $url = "http://www.youtube.com/$vidtype/$id$params";
	
	# Output
	if ($YouTube_XHTMLcompliant) {
		# XHTML 1.0 COMPLIANT
		$out = "\n<object type='application/x-shockwave-flash' width='$width' height='$height' data='$url'>";
		$out .= "\n  <param name='movie' value='$url' />";
		$out .= "\n  <param name='wmode' value='transparent' />";
		$out .= "\n  <param name='allowFullScreen' value='true' />";
		$out .= "\n  <param name='allowscriptaccess' value='always' />";
		$out .= "\n</object>";
	} else {
		$out = "\n<object width='$width' height='$height'> ";
		$out .= "\n  <param name='movie' value='$url'></param>";
		$out .= "\n  <param name='wmode' value='transparent'></param>";
		$out .= "\n  <param name='allowFullScreen' value='true'></param>";
		$out .= "\n  <param name='allowscriptaccess' value='always'></param>";
		$out .= "\n  <embed src='$url' type='application/x-shockwave-flash' allowscriptaccess='always' allowfullscreen='true' wmode='transparent' width='$width' height='$height'></embed>";
		$out .= "\n</object>";
	}

	return Keep($out);
}



##############


### GOOGLEVIDEO
SDV($GoogleVideoSimpleEmbed, true);
SDV($GoogleVideo_XHTMLcompliant, true);
SDV($GoogleVideoROEenabled, true);
SDVA($GoogleVideoDefaultParams, array(
	'width' => '400'
	,'height' => '326'
	,'scale' => '1'
	,'autoplay' => ''
	,'playerMode' => 'embedded'
	,'fs' => 'true'
));
/*
possible arguments:
	* width & height
	* scale
	* fs (fullscreen): true (default) or false
	* autoPlay: true or false (default)
	* initialTime: start-time in seconds (default is 0)
	* loop: true or false (default)
	* showShareButtons: true or false (default) (not sure if this works)
	* playerMode: embedded (default): the standard skin; simple (a basic version of the player without progress bar and volume control); mini (even more basic); clickToPlay (the skin used for video ads);
	
more info here: http://googlesystem.blogspot.com/2006/11/customize-embedded-google-video-player.html
*/

#####################
# EMBED (:GOOGLEVIDEO:)
Markup('googlevideo', '<img', "/\\(:googlevideo\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowGoogleVideo('$1','$2')");


# SIMPLE EMBED: GOOGLEVIDEO
## embed googlevideo by simply pasting the url: http://video.google.com/videoplay?docid=-2175875963366512516#
## automatically embeds using the default parameters
## set ____ to true if you want to convert to PmWiki youtube markup

if($GoogleVideoSimpleEmbed) {
	$GoogleVideoSimplePattern = '/(`?)(http:\\/\\/video\\.google\\.com\\/videoplay\\?docid=([\\d\\-]+)#?)/e';
	Markup('GoogleVideoSIMPLE', '<img', $GoogleVideoSimplePattern, "ShowGoogleVideo('$3','',array('$1','$2'))");
}
######################



# ROEPatterns - GOOGLE EMBED CONVERSION
# Converts pasted GoogleVideo embed code into valid pmwiki (:google:) code
if ($GoogleVideoROEenabled) {
	$ROEPatterns['!<embed.*video\.google\.com/googleplayer\.swf.*</embed>!ie'] = "GoogleVideoROE(PSS('$0'))";	
	SDV($GoogleVideoROEFmt, '(:googlevideo $1 scale=1:)');
	function GoogleVideoROE($videocode) {
		global $GoogleVideoROEFmt;
		$out = preg_replace('#^.*docid=([^&]+)&.*$#', $GoogleVideoROEFmt, $videocode);
		return $out;
	}
}

function ShowGoogleVideo($id, $args='', $escape='') {
	global $GoogleVideoDefaultParams, $GoogleVideo_XHTMLcompliant; 
	
	# escape SIMPLE EMBED if url is preceded by a `, ex.: `http://...
	if($escape[0]=="`") return $escape[1];
	
	# add default parameters before parsing arguments
	$args = array_merge($GoogleVideoDefaultParams, ParseArgs($args)); # uses GoogleVideoDefaultParams, unless supplied by user

	# define width & height
	## one can supply a 'scale' default different than '1', but it's not suggested, as it could be confusing to users
	$scale = $args['scale'];
	$width = $args['width'] * $scale;
	$height = $args['height'] * $scale;

	# other arguments
	# NOTE: GoogleVideo does not understand arg=false - it treats this the same as if stating arg=true. therefore, we must exclude any false statement, and only include the arg if it's set to true
	if($args) {
		# remove width, height, scale params if there are any - they're non-api, so not necessary for parameter string
		$args = array_diff_key($args, array('width'=>'','height'=>'','scale'=>''));
		# create parameter string, in the form &amp;arg1=val1&amp;arg2=val2
		foreach($args as $key => $val) {
			if ($key!='#') {
				#echo "$key - $val<br />";
				# don't use param if it's set to 0 or false or nil
				if($val!=="0" && $val!=="false" && $val!='') $params .= "&$key=$val";
			}
		}
	}
	
	$url = "http://video.google.com/googleplayer.swf?docId=$id$params";
	#echo "$url<br />";

	# Output
	if ($GoogleVideo_XHTMLcompliant) {
		# XHTML 1.0 COMPLIANT
		$out = "\n<object type='application/x-shockwave-flash' ";
		$out .= "data='$url' width='$width' height='$height' class='VideoPlayback'>";
		$out .= "\n  <param name='movie' value='$url' />";
		$out .= "\n  <param name='allowfullscreen' value='true' />";
		$out .= "\n  <param name='allowScriptAccess' value='sameDomain' />";
		$out .= "\n  <param name='quality' value='best' />";
		$out .= "\n  <param name='bgcolor' value='#ffffff' />";
		$out .= "\n  <param name='scale' value='noScale' />";
		$out .= "\n  <param name='salign' value='TL' />";
		$out .= "\n  <param name='wmode' value='transparent' />";
		$out .= "\n</object>";
	} else {
		$out = "<embed id=VideoPlayback src=$url style=width:{$width}px;height:{$height}px allowFullScreen=true allowScriptAccess=always flashvars='' type=application/x-shockwave-flash> </embed>";	
	}
      return Keep($out);
}

