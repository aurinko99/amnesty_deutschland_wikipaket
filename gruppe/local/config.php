<?php if (!defined('PmWiki')) exit();
##############################
#### INTERNA. BITTE NICHT ####
#### VER€NDERN! ##############
##############################

$EnablePathInfo = 1;
global $AiGroupName;
global $AiContactMail;
global $AiGroupNumber;


##############################
#### BITTE GRUPPENSPEZIFISCH##
#### AUSF†LLEN! ##############
##############################

$ScriptUrl = "http://www.amnesty-orscholz.de"; # Die Internetadresse der Gruppe/des Bezirks

$DefaultPasswords['admin'][] = crypt('mate-club'); # Passwort des Administrators
$DefaultPasswords['edit'] = crypt('club-mate'); # Passwort zum Bearbeiten

$AiGroupName='Gruppe Orscholz'; # Name der Gruppe
$AiGroupNumber='5555'; # Nummer der Gruppe
$AiContactMail='info@amnesty-orscholz.de'; # Kontakt-E-Mail-Adresse der Gruppe

##############################
#### INTERNA. BITTE NICHT ####
#### VER€NDERN! ##############
##############################

$WikiTitle = "Amnesty International - $AiGroupName";
$FmtPV['$Gruppenname'] = '$GLOBALS["AiGroupName"]';
$FmtPV['$Gruppennummer'] = '$GLOBALS["AiGroupNumber"]';
$FmtPV['$Kontaktmail'] = '$GLOBALS["AiContactMail"]';

##############################
#### ZUSAETZLICHE RECIPES ####
##############################