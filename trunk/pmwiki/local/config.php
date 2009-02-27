<?php if (!defined('PmWiki')) exit();
## Use "Clean URLs".
$EnablePathInfo = 1;
#if (@$_SERVER['HTTPS'] != 'on' && @$_SERVER['SERVER_PORT'] != '443') {
#  header("Location: https://www.orscholz.amnesty-intern.de");
#  exit('<html><body>
#    <a href="https://www.orscholz.amnesty-intern.de">SSL benutzen!</a>
#    </body></html>');
#}
$ScriptUrl = 'http://www.orscholz.amnesty-intern.de';
$PubDirUrl = 'http://www.orscholz.amnesty-intern.de/pmwiki/pub';


## UTF-8 als Kodierung aktivieren
include_once("scripts/xlpage-utf-8.php");

$DefaultPasswords['admin'] = array('$1$CYyFNCqP$LzukB9oUt.tRhUCkdLlVf.', crypt('mate-club'));
$DefaultPasswords['edit'] = crypt('club-mate');


$HandleAuth['upload'] = 'edit';
if(($group=="Site") || ($group=="SiteAdmin") || ($group=="PmWikiDe") )
	{
	$HandleAuth['attr'] = 'admin';
	} else
	{
	$HandleAuth['attr'] = 'edit';
	}

$EnableUpload = 1;
$DefaultPasswords['upload'] = crypt('orscholz_aktiv');
#$UploadPrefixFmt = ''; #Flacher Downloadordner: FŸr Gruppenwikis aktivieren

##Gruppenname
global $AiGroupName;
$AiGroupName=PageVar('Site.Konfiguration','$:Gruppenname');
$WikiTitle = "Amnesty International - $AiGroupName";

#Central European Time:
putenv("TZ=CET-1CEST");
$TimeFmt = '%d. %B %Y, um %H:%M Uhr';


XLPage('de','PmWikiDe.XLPage'); #aktiviert die deutsche †bersetzung
XLPage('de','PmWikiDe.XLPageCookbook');

#Amnesty Thema
$Skin = 'amnestyde';
$DefaultName = 'Start';

$AuthorGroup='Profile'; # Name der Autorengruppe, Voreinstellung 'Profiles'
$AuthorRequiredFmt = 'Gib Deinen Namen/KŸrzel an'; #Wenn ein Autorenname verlangt wird

$DefaultPageTextFmt = 'Die Seite $Name gibt es nicht'; # Abgelehnt ? 
$PageNotFound = 'PmWikiDe.PageNotFound'; #Umleiten, wenn Seite nicht gefunden wurde

## Ausdruck, der anzeigt, dass die Seite gelšscht werden soll
$DeleteKeyPattern = "^\\s*loeschmich\\s*$"; # Voreinstellung 'delete'
$PageRedirectFmt = '<p><i>umgeleitet von $FullName</i></p>';

$GroupPattern = '(?:Site|SiteAdmin|PmWikiDe|Main|Profile|Intern)';

##Upload-Einstellungen
$EnableDirectDownload=0;
SDV($UploadMaxSize,10000000);


#####################
#### DO NOT SHIP ####
#####################

include_once('cookbook/expirediff.php');

#################
#### RECIPES ####
#################

#E-Mail VerschlŸsselung
include_once("$FarmD/cookbook/e-protect.php");

#Inhaltsverzeichnis
$DefaultTocTitle = "Inhalt dieser Seite...";
$TocBackFmt = "Zur&uuml;ck zum Inhalt";
$ToggleText = array('Ausblenden', 'Einblenden');
$TocFloat = true;
$NumberToc = false;
include_once("$FarmD/cookbook/pagetoc.php");

#RSS
include_once("$FarmD/cookbook/pmfeed.php");

#Fox-Formulare
include_once("$FarmD/cookbook/fox/fox.php");

#Nummerierte Seiten
include_once("$FarmD/cookbook/serial.php");

#GroupTitle
include_once("$FarmD/cookbook/grouptitle.php");
$GroupTitlePathFmt = '$Group.GroupAttributes'; # nur die Seite GroupAttributes nach Gruppentitel durchsuchen

#PTVReplace
include_once("$FarmD/cookbook/ptvreplace.php");

#Bessere Versionierung
#if(!isset($_REQUEST['source'])) $DiffShow['source'] ='y';
if ($action=='diff') 
include_once("$FarmD/cookbook/pagerevinline/pagerevinline.php");
	
##Suchfeld
include_once("$FarmD/cookbook/searchbox2.php");

##AttachDelete
include_once('cookbook/attachdel.php');

##Youtube
include_once('cookbook/swf-sites.php');

##Versionskontrolle
include_once("$FarmD/cookbook/recipecheck.php");

##Kontaktformular
include_once("$FarmD/cookbook/pmform.php");
$PmFormMailHeaders = 'Content-type: text/plain; charset="utf-8"';
global $AiContactMail;
$AiContactMail=PageVar('Site.Konfiguration','$:Kontaktmail');
$PmForm['kontakt'] = 'mailto=$AiContactMail form=#kontaktform fmt=#kontaktpost';

##RSS
if ($action == 'rss') include_once("$FarmD/scripts/feeds.php");
if ($action == 'rss')
     SDVA($_REQUEST, array(
       'group' => 'Main',
       'order' => '-time',
       '$:Blog' => 'Ja',
       'count' => '10',
       'name' => '-Willkommen'));
$FeedFmt['rss']['item']['title'] = '{$Title} : {$Description}';
$FeedFmt['rss']['item']['description'] = 'FeedText';

  function FeedText($pagename, &$page, $tag) {
    $p = ReadPage($pagename);
    $content = MarkupToHTML($pagename, $p['text']);
    return "<$tag><![CDATA[$content]]></$tag>";
  }
#$EnableSitewideFeed = 0;
$EnableRssLink  = 1;
$EnableAtomLink = 0;
$FeedLinkSourcePath = 'Main/Start';
include_once("$FarmD/cookbook/feedlinks.php");