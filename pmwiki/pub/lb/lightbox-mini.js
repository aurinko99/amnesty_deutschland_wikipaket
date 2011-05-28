// This file was modified by Petko Yotov for the purposes of
// the Mini gallery for PmWiki. (2010-04-13)

var LightboxDirUrl;
$A(document.getElementsByTagName("script")).findAll( function(s) {
  return (s.src && s.src.match(/prototype\.js$/))
}).each( function(s) { LightboxDirUrl = s.src.replace(/prototype\.js$/,'');});

LightboxOptions = {
  fileLoadingImage:        LightboxDirUrl + "loading.gif",
  fileBottomNavCloseImage: LightboxDirUrl + "close.gif",
  overlayOpacity: 0.8,   // controls transparency of shadow overlay

  animate: true,         // toggles resizing animations
  resizeSpeed: 7,        // controls the speed of the image resizing animations (1=slowest and 10=fastest)
  borderSize: 10,        // if you adjust the padding in the CSS, you will need to update this variable

  // When grouping images this is used to write: Image # of #.
  // Change it for non-english localization
  labelImage: "",
  labelOf: "/"
}

var prevnext = ['prev', 'next'];
var MiniLocalCss = "<style>";
for(var i = 0; i<2; i++) {
  var curr = prevnext[i];
  MiniLocalCss += "#" + curr + "Link:hover, #"
    +curr+"Link:visited:hover { background-image: url("
    +LightboxDirUrl+curr+".gif) !important;}";
}
MiniLocalCss += "</style>";
document.write(MiniLocalCss);
