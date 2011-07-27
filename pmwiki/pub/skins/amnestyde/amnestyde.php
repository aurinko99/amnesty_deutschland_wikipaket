<?php if (!defined('PmWiki')) exit();
/*
 * PmWiki amnestyde skin
 * Version 1.4.1  (27.07.2011)
 * @requires PmWiki 2.2
 *
 * Copyright (c) 2008-2011 Amnesty International, Deutsche Sektion
 */
global $FmtPV, $SkinName, $SkinVersionDate, $SkinVersionNum, $SkinVersion, $SkinRecipeName, 
       $SkinSourceURL, $RecipeInfo;
$SkinName = 'amnestyde';
$SkinRecipeName = "AmnestyDe";
$RecipeInfo['amnestyde']['Version'] = '2011-07-27 (1.4.1)';

## Add a custom page storage location
global $PageStorePath, $WikiLibDirs;
$PageStorePath = dirname(__FILE__)."/wikilib.d/{\$FullName}";
$where = count($WikiLibDirs);
if ($where>1) $where--;
array_splice($WikiLibDirs, $where, 0, array(new PageStore($PageStorePath)));


## Define a link stye for new page links
global $LinkPageCreateFmt;
SDV($LinkPageCreateFmt, "<a class='createlinktext' href='\$PageUrl?action=edit'>\$LinkText</a>");

##Gruppennummer und Gruppenname
global $AiGroupNumber, $AiDonationPath, $AiSupporterPath, $AiMemberPath;
$AiDonationPath = "https://www.amnesty.de/spendentool/$AiGroupNumber";
$AiSupporterPath = "https://www.amnesty.de/foerdererwerden/$AiGroupNumber";
$AiMemberPath = "https://www.amnesty.de/mitgliedwerden/$AiGroupNumber";
$FmtPV['$AiDonationPath'] = '$GLOBALS["AiDonationPath"]';
$FmtPV['$AiSupporterPath'] = '$GLOBALS["AiSupporterPath"]';
$FmtPV['$AiMemberPath'] = '$GLOBALS["AiMemberPath"]';

##  The following lines make additional editing buttons appear in the
##  edit page for subheadings, lists, tables, etc.
global $EnableGUIButtons, $GUIButtons, $GUIButtonDirUrlFmt;
SDV($EnableGUIButtons, 1);
$GUIButtonDirUrlFmt = $SkinDirUrl.'/images/guiedit';
$GUIButtons['h2'] = array(100, '\\n!! ', '\\n', '&Uuml;berschrift',
                    '$GUIButtonDirUrlFmt/h.gif"&Uuml;berschrift zweiter Ordnung"');

$GUIButtons['separator1'] = array(190, '', '', '', '$GUIButtonDirUrlFmt/space1.gif');
$GUIButtons['strong'] = array(200, "'''", "'''", 'Fett',
                  '$GUIButtonDirUrlFmt/strong.gif"Fett"',
                  '$[ak_strong]');
$GUIButtons['em'] = array(210, "''", "''", 'Kursiv',
                  '$GUIButtonDirUrlFmt/em.gif"Kursiv"',
                  '$[ak_em]');
$GUIButtons['sup'] = array(224, "'^", "^'", '$[Superscript]',
                  '$GUIButtonDirUrlFmt/sup.gif"$[Superscript]"');
$GUIButtons['sub'] = array(225, "'_", "_'", '$[Subscript]',
                  '$GUIButtonDirUrlFmt/sub.gif"$[Subscript]"');
$GUIButtons['big'] = array(230, "'+", "+'", '$[Big text]',
                  '$GUIButtonDirUrlFmt/big.gif"$[Big text]"');
$GUIButtons['small'] = array(240, "'-", "-'", '$[Small text]',
                  '$GUIButtonDirUrlFmt/small.gif"$[Small text]"');

$GUIButtons['separator2'] = array(290, '', '', '', '$GUIButtonDirUrlFmt/space1.gif');
$GUIButtons['ol'] = array(300, '\\n# ', '\\n', '$[Ordered list]',
                    '$GUIButtonDirUrlFmt/ol.gif"$[Ordered (numbered) list]"');
$GUIButtons['ul'] = array(310, '\\n* ', '\\n', '$[Unordered list]',
                    '$GUIButtonDirUrlFmt/ul.gif"$[Unordered (bullet) list]"');
$GUIButtons['hr'] = array(320, '\\n----\\n', '', '',
                    '$GUIButtonDirUrlFmt/hr.gif"$[Horizontal rule]"');
$GUIButtons['table'] = array(330,
                      '(:table border=1 width=100%:)\\n(:cell:)\\n\\n(:cell:)\\n\\n(:cell:)\\n\\n(:cellnr:)\\n\\n(:cell:)\\n\\n(:cell:)\\n\\n(:tableend:)', '', '',
                    '$GUIButtonDirUrlFmt/table.gif"$[Table]"');

$GUIButtons['separator3'] = array(390, '', '', '', '$GUIButtonDirUrlFmt/space1.gif');
$GUIButtons['center'] = array(400, '%25center%25', '', '',
                  '$GUIButtonDirUrlFmt/center.gif"$[Center]"');
$GUIButtons['right'] = array(410, '%25right%25', '', '',
                  '$GUIButtonDirUrlFmt/right.gif"Rechtsb&uuml;ndig"');
$GUIButtons['indent'] = array(420, '\\n->', '\\n', '$[Indented text]',
                    '$GUIButtonDirUrlFmt/indent.gif"$[Indented text]"');
$GUIButtons['outdent'] = array(430, '\\n-<', '\\n', '$[Hanging indent]',
                  '$GUIButtonDirUrlFmt/outdent.gif"$[Hanging indent]"');

$GUIButtons['separator4'] = array(490, '', '', '', '$GUIButtonDirUrlFmt/space1.gif');
$GUIButtons['pagelink'] = array(500, '[[', ']]', '$[Page link]',
                  '$GUIButtonDirUrlFmt/pagelink.gif"$[Link to internal page]"');
$GUIButtons['extlink'] = array(510, '[[', ']]', 'http:// | $[link text]',
                  '$GUIButtonDirUrlFmt/extlink.gif"$[Link to external page]"');
$GUIButtons['attach'] = array(520, 'Attach:', '', 'file.ext | $[link text]',
                  '$GUIButtonDirUrlFmt/attach.gif"Datei oder Bild anh&auml;ngen"');
$GUIButtons['youtube'] = array(530, '(:youtube ', ':)', '',
				'$GUIButtonDirUrlFmt/youtube.gif"Youtube-Video (nur Video-Code (hinter v=) eingeben)"');

$GUIButtons['separator5'] = array(590, '', '', '', '$GUIButtonDirUrlFmt/space1.gif');
$GUIButtons['hib'] = array(600, '%textmarker%', '%%', '',
				'$GUIButtonDirUrlFmt/highbgyellow.gif"Textmarker"');
$GUIButtons['bot'] = array(610, '%botschaft%', '%%', '',
				'$GUIButtonDirUrlFmt/botschaft.gif"Botschaft"');
$EnableGUIButtons = 1;