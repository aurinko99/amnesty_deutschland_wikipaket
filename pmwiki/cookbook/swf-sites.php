<?php if (!defined('PmWiki')) exit ();

/* 
    
	Original info:
	copyright 2009, Adam Overton
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
	
	This module enables embedding of Google Video, Vimeo, YouTube, and FlickrVid movies into wiki pages.
	The simplest use of any of these includes:
		(:googlevideo videocode:)
		(:youtube videocode:)
		(:vimeo videocode:)
		(:flickrvid videocode secret=value:)
	where 'videocode' is the unique alpha-numberic id assigned to your movie. (For FlickrVid, see more details below).
	For example, if the URL to play the video is:
		http://video.google.com/videoplay?docid=348928491823948192,
	you would do 
		(:googlevideo 348928491823948192:)
	
	In addition, Replace-On-Edit Patterns ($ROEPatterns) have been created for each recipe, so that visitors can simply copy-and-paste the embed code from the respective video website, click save or save-and-edit, and have the code automatically converted to the appropriate pmwiki markup. If this is undesired, an admin can disable this in config.php by setting $YouTubeROEenabled, $VimeoROEenabled, etc to false.
	
	
	YOUTUBE
	The YouTube markup can accept various parameters as defined by the YouTube api, via the following format:
		(:youtube videocode [ param1=val1 param2=val2 ... ] :) 
		* [ brackets denote optional arguments ]
	
	An abbreviated* list of common YouTube parameters:
		* width & height (defaults: width=425, height=244) - in pixels, but don't add 'px' at the end! (non-api)
		* plwidth & plheight (defaults: width=480, height=385) - height and width for playlists, which are slightly larger than regular videos (non-api)
		* scale (default: 1) - scale the video's dimensions by some numerical factor (ex: 0.5 = half-size) (non-api)
		* loop - 0 or 1 (default: 0)
		* start - 0 or 1 (default: 0) - starting frame of the video
		* autoplay - 0 or 1 (default: 0)
		* fs - 0 or 1 (default: 0) - add the fullscreen button to the playbar
		* playlist - 0 or 1 (default: 0) - provide this if you're embedding a playlist (non-api)
		* border, color1 & color2 - (default: border=0) - border adds a frame around the video, of the color1; color2 affects the controls (whether or not border is turned on). colors are supplied in hexadecimal, but with no '#': 996633
		* nocookie - 0 or 1 (default: 1) - if enabled, video will come from http://www.youtube-nocookie.com, for privacy issues
	
	* For more possible parameters, see the YouTube api reference page: 
	http://code.google.com/apis/youtube/player_parameters.html
	
	Examples:
		if you'd like your video to loop:
		(:youtube nr-SZXIVvuo loop=1:) 
	
		... or if you'd like it to loop and enable the FullScreen button, and you'd you'd like to double the default size
		(:youtube nr-SZXIVvuo loop=1 fs=1 scale=2:) 
	
		... or provide your own width & height
		(:youtube nr-SZXIVvuo width=425 height=344:) 
	
	Finally, the following default setup variables are now available for use in config.php, before including the recipe:
	
		* $YouTubeDefaultParams - the default is fs=1 & hd=1 (FullScreen and HighDefinition enabled), width=425 & height=344, plwidth=480 & plheight=385
		* $YouTube_XHTMLcompliant - true or false (default: true) - set to false if you'd rather use the non-XHTML-compliant embed code from the YouTube site
		* $YouTubeROEenabled - true or false (default: true) - turns Replace-On-Edit-Patterns Off/On for embed code entered into a browser window
		* $YouTubeROEFmt - default is '(:youtube $1 scale=1:)' - default replacement of YouTube embed code - change it if you'd rather YouTube embed code be replaced with a different set of default variables.
		
	
	VIMEO
	Vimeo markup accepts various parameters via the following format:
		(:vimeo videocode [ param1=val1 param2=val2 ... ] :) 
	
	Vimeo parameters:
		* width & height (defaults: width=400, height=327) - in pixels, but don't add 'px' at the end! (non-api)
		* scale (default: 1) - scale the video's dimensions by some numerical factor (ex: 0.5 = half-size) (non-api)
		* show_title - 0 or 1 (default: 1) - displays the title of the video
		* show_byline - 0 or 1 (default: 1) - displays the name of the artist/author/creator
		* show_portrait - 0 or 1 (default: 0) - displays the portrait of the creator
		* color - a hex color-code, with no leading '#' (default: 00ADEF) - the color of the videoplayer text and controls
		* fullscreen - 0 or 1 (default: 1) - permits the fullscreen button on the playbar
		* autoplay - 0 or 1 (default: 0)
		* loop - 0 or 1 (default: 0)
		
	Examples:
		Simplest vimeo embed:
		(:vimeo 6507668:) 
	
		... or provide your own width & height
		(:vimeo 6507668 width=425 height=344:) 
	
	The following default setup variables are available for use in config.php, before including the recipe:
	
		* $VimeoDefaultParams
		* $Vimeo_XHTMLcompliant - true or false (default: true) - set to false if you'd rather use the non-XHTML-compliant embed code from the Vimeo site.
		* $VimeoROEenabled - true or false (default: true) - turns Replace-On-Edit-Patterns Off/On for embed code entered into a browser window
		* $VimeoROEFmt - default is '(:vimeo $1 scale=1 $2 $3 $4 $5:)' - default replacement of Vimeo embed code - change it if you'd rather Vimeo embed code be replaced with a different set of default variables.
		
	
	GOOGLEVIDEO
	GoogleVideo markup accepts a handful of optional parameters, though not as many as Vimeo & Youtube:
		(:googlevideo videocode [ param1=val1 param2=val2 ... ] :) 
	
	GoogleVideo parameters:
		* width & height (defaults: width=400, height=326) - in pixels, but don't add 'px' at the end! (non-api)
		* scale (default: 1) - scale the video's dimensions by some numerical factor (ex: 0.5 = half-size) (non-api)
		* fs - 0 or 1 (default: 1) - permits the fullscreen button on the playbar
		
	Examples:
		Simplest GoogleVideo embed:
		(:googlevideo 8953172273825999151:) 
	
		... or provide your own width & height, and disallow fullscreen:
		(:googlevideo 8953172273825999151 width=425 height=344 fs=0:) 
	
	The following default setup variables are available for use in config.php, before including the recipe:
	
		* $GoogleVideoDefaultParams
		* $GoogleVideo_XHTMLcompliant - true or false (default: true) - set to false if you'd rather use the non-XHTML-compliant embed code provided at the GoogleVideos site
		* $GoogleVideoROEenabled - true or false (default: true) - turns Replace-On-Edit-Patterns Off/On for embed code entered into a browser window
		* $GoogleVideoROEFmt - default is '(:googlevideo $1 scale=1:)' - default replacement of Vimeo embed code - change it if you'd rather Vimeo embed code be replaced with a different set of default variables.
	
	
	FLICKRVID
	FlickrVid markup accepts a handful of parameters, though not as many as Vimeo & Youtube:
		(:flickrvid videocode secret=secretval [ param1=val1 param2=val2 ... ] :) 
	
	Look for videocode in the embed code for "photo_id=somenumber"

	FlickrVid parameters:
		* secret (required) - this can be obtained from the embed code on the users pages - look for photo_secret=somenumber
		* width & height (defaults: width=400, height=300) - in pixels, but don't add 'px' at the end! (non-api)
		* scale (default: 1) - scale the video's dimensions by some numerical factor (ex: 0.5 = half-size) (non-api)
		* showinfo - true or false (default: true) - display the video information before and after playing
		
	Examples:
		Simplest FlickrVid embed:
		(:flickrvid secret=98765 id=43210:) 
	
		... or provide your own width & height, and turn video info off
		(:flickrvid secret=98765 id=43210 width=425 height=344 showinfo=false:) 
	
	The following default setup variables are available for use in config.php, before including the recipe:
	
		* $FlickrVidDefaultParams
		* $FlickrVidROEenabled - true or false (default: true) - turns Replace-On-Edit-Patterns Off/On for embed code entered into a browser window
		* $FlickrVidROEFmt - default is '(:flickrvid $1 scale=1:)' - default replacement of FlickrVid embed code - change it if you'd rather FlickrVid embed code be replaced with a different set of default variables.
	
	* Note: FlickrVid is not yet XHTML 1.0 compliant - I was unable to locate any leads online yet that work.
	If you have any suggestions, do let me know.
	
	. . .
		
	Versions:
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
$RecipeInfo['SWFSites']['Version'] = '2009-10-25';


### GOOGLEVIDEO

Markup('googlevideo', '<img', "/\\(:googlevideo\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowGoogleVideo('$1','$2')");
SDVA($GoogleVideoDefaultParams, array(
	'width' => '400'
	,'height' => '326'
	,'scale' => '1'
	,'fs' => 'true'
));
SDV($GoogleVideo_XHTMLcompliant, true);
SDV($GoogleVideoROEenabled, true);

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

function ShowGoogleVideo($id, $args) {
	global $GoogleVideoDefaultParams, $GoogleVideo_XHTMLcompliant; 
	
	# add default parameters before parsing arguments
	$args = array_merge($GoogleVideoDefaultParams, ParseArgs($args)); # uses GoogleVideoDefaultParams, unless supplied by user

	# define width & height
	## one can supply a 'scale' default different than '1', but it's not suggested, as it could be confusing to users
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
				$params .= "&$key=$val";
				$i++;
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
		$out .= "\n  <param name='FlashVars' value='playerMode=embedded' />";
		$out .= "\n</object>";
	} else {
		$out = "<embed id=VideoPlayback src=http://video.google.com/googleplayer.swf?docid=$id&fs=true style=width:{$width}px;height:{$height}px allowFullScreen=true allowScriptAccess=always flashvars='' type=application/x-shockwave-flash> </embed>";	
	}
      return Keep($out);
}



### VIMEO

Markup('vimeo', '<img', "/\\(:vimeo\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowVimeoVideo('$1','$2')");  # new
SDVA($VimeoDefaultParams, array(
	'width' => '400'
	,'height' => '327'
	,'scale' => '1'
	,'show_title' => '1'
	,'show_byline' => '1'
	,'show_portrait' => '0'
	,'color' => '00ADEF'  # color of text
	,'fullscreen' => '1'
	,'autoplay' => '0'
	,'loop' => '0'
));
SDV($Vimeo_XHTMLcompliant, true);
SDV($VimeoROEenabled, true);

# ROEPatterns - VIMEO EMBED CONVERSION 
# Converts pasted Vimeo embed code into valid pmwiki (:vimeo:) code
if ($VimeoROEenabled) {
	$ROEPatterns['!<object.*vimeo\.com.*<\/object>!ie'] = "VimeoROE(PSS('$0'))";
	
	SDV($VimeoROEFmt, '(:vimeo $1 scale=1 $2 $3 $4 $5:)');
	function VimeoROE($videocode) {
		global $VimeoROEFmt;

		# gather other parameters - width, height
		## gathering width and height here b/c vimeo now offers different embed dimensions
		preg_match('#width=["\'](\d+)["\'] height=["\'](\d+)["\']#', $videocode, $matches);
		$params = 'width='.$matches[1].' height='.$matches[2];

		# append extra parameters after the videocode - create _local to prevent writing to the global
		$VimeoROEFmt_local = str_replace('$1','$1 '.$params,$VimeoROEFmt);

		# output
		$out = preg_replace('#^.*clip_id=(.*)&amp;server=vimeo.com&amp;(.*)&amp;(.*)&amp;(.*)&amp;(.*)&amp;.*$#', $VimeoROEFmt_local, $videocode);
		# remove empty 'color=' (sometimes provided by vimeo embed code) - causes error below in ParseArgs
		$out = preg_replace('/color=\\s*:\)/',':)',$out);
		return $out;
	}
}

function ShowVimeoVideo($id, $args='') {
	global $VimeoDefaultParams, $Vimeo_XHTMLcompliant; 
	
	# add default parameters before parsing arguments
	$args = array_merge($VimeoDefaultParams, ParseArgs($args)); # uses VimeoDefaultParams, unless supplied by user

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


### YOUTUBE

Markup('youtube', '<img', "/\\(:youtube\\s+([^\\s]+)\\s*(.*):\\)/e", "ShowYouTube('$1','$2')");
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

# ROEPatterns - YOUTUBE EMBED CONVERSION 
# Converts pasted youtube embed code into valid pmwiki (:youtube:) code
if ($YouTubeROEenabled) {
	$ROEPatterns['!<object.*http://www\.youtube(-nocookie)?\.com.*<\/object>!ie'] = "YoutubeROE(PSS('$0'))";
	SDV($YouTubeROEFmt, '(:youtube $1 scale=1:)');
	function YoutubeROE($videocode) {
		global $YouTubeROEFmt;
		
		# add nocookie=1 if embed code is from http://youtube-nocookie.com
		if(strpos($videocode,'-nocookie')) $YouTubeROEFmt = str_replace('$1','$1 nocookie=1',$YouTubeROEFmt);
		
		# gather other parameters - width, height, border, color1, color2, rel
		## gathering width and height here b/c youtube now offers different embed dimensions
		preg_match('#<object width="(\d+)" height="(\d+)"#', $videocode, $matches);
		$params = 'width='.$matches[1].' height='.$matches[2];
		if(preg_match('#&(color1=[^&]+)&#', $videocode, $matches)) $params .= " $matches[1]";
		if(preg_match('#&(color2=[^&"]+)&?#', $videocode, $matches)) $params .= " $matches[1]";
		if(preg_match('#&(border=\d)#', $videocode, $matches)) $params .= " $matches[1]";
		if(preg_match('#&(rel=\d)#', $videocode, $matches)) $params .= " $matches[1]";

		# check for playlist
		if(strpos($videocode, '/p/')) $params .= " playlist=1";
	
		# append extra parameters after the videocode
		$YouTubeROEFmt_local = str_replace('$1','$1 '.$params,$YouTubeROEFmt);
		# must use _local version, or else multiple conversions on the same page keeping adding params to the ROEFmt array		
		$out = preg_replace('#^.*/[vp]/([^&]+)&.*$#', $YouTubeROEFmt_local, $videocode);
		return $out;
	}
}


function ShowYouTube($id, $args='') {
	global $YouTubeDefaultParams, $YouTube_XHTMLcompliant;
	
	# $args here is unlike the other recipes in that it waits to see if it's a playlist, and checks for the presence width/height params before combining default and current param-arrays. this is to make sure default values are implemented correctly
	$args = ParseArgs($args); # user input only - will check it against default values below
	
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


	# combine presets with supplied args, replacing presets with supplied args
	$args = array_merge($YouTubeDefaultParams, $args);

	# get cookie info before array is cleared from the api-array
	$nocookieArg = $args['nocookie'];
	
	# create api-url - need to clear out non-api params first
	if($args) {
		# remove width, height, scale, playlist and nocookie params if present - not necessary for parameter string
		$args = array_diff_key($args, array('width'=>'','height'=>'','scale'=>'','playlist'=>'','nocookie'=>''));

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


#### FLICKRVID

Markup('flickrvid', '<img', "/\\(:flickrvid\\s+([^\\s]+)\\s*(.*)\\s*:\\)/e", "ShowFlickrVid('$1','$2')");
SDVA($FlickrVidDefaultParams, array(
	'secret' => ''
	,'width' => '400'
	,'height' => '300'
	,'scale' => '1'
	,'showinfo' => '1'
));
#SDV($FlickrVid_XHTMLcompliant, true);
SDV($FlickrVidROEenabled, true);

# ROEPatterns - FLICKRVID EMBED CONVERSION 
# Converts pasted FlickrVid embed code into valid pmwiki (:flickrvid:) code
if ($FlickrVidROEenabled) {
	$ROEPatterns['!<object.*data="http://www\.flickr\.com/apps/video.*<\/object>!ie'] = "FlickrVidROE(PSS('$0'))";
	
	SDV($FlickrVidROEFmt, '(:flickrvid [args] scale=1:)');
	function FlickrVidROE($args) {
		global $FlickrVidROEFmt;

		# gather other parameters - secret, id, width, height, showinfo
		if(preg_match('#photo_id=(\d+)#', $args, $matches)) $params = "$matches[1]";
		## gathering width and height here b/c flickrvid now offers different embed dimensions
		preg_match('#width=["\'](\d+)["\'] height=["\'](\d+)["\']#', $args, $matches);
		$params .= " width=".$matches[1]." height=".$matches[2];
		if(preg_match('#photo_(secret=[^&]+)&#', $args, $matches)) $params .= " $matches[1]";
		if(strpos($args,'flickr_show_info_box=true')) $params .= " showinfo=true";
		
		$out = str_replace('[args]',$params,$FlickrVidROEFmt);
		return $out;
	}
}

function ShowFlickrVid($videocode, $args) {
	global $FlickrVidDefaultParams;

	$args = array_merge($FlickrVidDefaultParams, ParseArgs($args));
	
	if(!$args['secret']) return "flickr error: you must provide the photo_secret";
	
	# define width & height
	## one can supply a 'scale' default different than '1', but it's not suggested, as it could be confusing to users
	$scale = $args['scale'];
	$width = $args['width'] * $scale;
	$height = $args['height'] * $scale;

	# flickr_show_info_box - this is an optional api-argument
	if ($args['showinfo']=='1' || $args['showinfo']=='true') $showinfo = "&flickr_show_info_box=true";

	# ?v=NNNN doesn't seem to be necessary
	//$url = "http://www.flickr.com/apps/video/stewart.swf?v=1.167";
	$url = "http://www.flickr.com/apps/video/stewart.swf";
	$params = "intl_lang=en-us&photo_secret=".$args['secret']."&photo_id=".$videocode.$showinfo;
	$hw = "width='$width' height='$height'";
	#echo "<br />$url $params $hw";
	
	/*	# XHTML 1.0 COMPLIANT
		## seems like this should work, but it doesn't - commented out for now
		$out = "\n<object type='application/x-shockwave-flash' $hw data='$url' classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'>";
		$out .= "\n   <param name='movie' value='$url' />";
		$out .= "\n   <param name='flashvars' value='$params' />";
		$out .= "\n   <param name='bgcolor' value='#000000' />";
		$out .= "\n   <param name='allowFullScreen' value='true' />";
		$out .= "\n</object>";
	*/
		# NON-COMPLIANT (exactly what Flickr provides)
		$out = "\n<object type='application/x-shockwave-flash' $hw data='$url' classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'>";
		$out .= "\n   <param name='flashvars' value='$params' />";
		$out .= "\n   <param name='movie' value='$url' />";
		$out .= "\n   <param name='bgcolor' value='#000000' />";
		$out .= "\n   <param name='allowFullScreen' value='true' />";
		$out .= "\n   <embed type='application/x-shockwave-flash' src='$url' bgcolor='#000000' allowfullscreen='true' flashvars='$params' $hw></embed>";
		$out .= "\n</object>";

	return Keep($out);
}

