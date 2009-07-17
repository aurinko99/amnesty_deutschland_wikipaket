<?php if (!defined('PmWiki')) exit();
#------------------------------------------------------------
/*
This program is free software.
You can redistribute it and/or modify it under
the terms of the GNU General Public License as
published by the Free Software Foundation
http://www.fsf.org either version 2 of the
License, or (at your option) any later version.

Copyright 2007 by GNUZoo (guru@gnuzoo.org)
http://www.pmwiki.org/wiki/Profiles/GNUZoo

Please donate to the author at url:a
http://gnuzoo.org/GNUZooPayPal
*/
#------------------------------------------------------------
$RecipeName = 'WebsiteIcon';
$RecipeVersion = '1.1';
$RecipeInfo[$RecipeName]['Version'] = $RecipeVersion;
if ($DisableRecipe[$RecipeName] === true) return;
#----------------------------------------
#recipe runs below this line
#----------------------------------------
if ($FavIcon === '') return;
$HTMLHeaderFmt['favicon'][0] = '<link rel="shortcut icon" href="$FavIcon" type="image/x-icon" />';
