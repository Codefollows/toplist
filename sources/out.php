<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2009 Jeremy Scheff.  All rights reserved.              \\
//---------------------------------------------------------------------------\\
// http://www.aardvarktopsitesphp.com/                http://www.avatic.com/ \\
//---------------------------------------------------------------------------\\
// This program is free software; you can redistribute it and/or modify it   \\
// under the terms of the GNU General Public License as published by the     \\
// Free Software Foundation; either version 2 of the License, or (at your    \\
// option) any later version.                                                \\
//                                                                           \\
// This program is distributed in the hope that it will be useful, but       \\
// WITHOUT ANY WARRANTY; without even the implied warranty of                \\
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General \\
// Public License for more details.                                          \\
//===========================================================================\\

if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

class out extends in_out {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG;

    // Prevent search engine indexing. Google now reads javascript, indexing all out urls, duh!
    header('X-Robots-Tag: noindex');

    $username = isset($FORM['u']) ? $DB->escape($FORM['u']) : '';
    $this->record($username, 'out');

    // Plugin Hook
    eval (PluginManager::getPluginManager ()->pluginHooks ('out_forward'));

    // If $_GET['go'] is set, then forward to the member's URL
    // If it is not set, then this is being called in the background by javascript, so stop executing to conserve resources
    if (isset($_GET['go']) && $_GET['go']) {
        list($url) = $DB->fetch("SELECT url FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);
		
		if (!$url) {
			$this->error($LNG['g_invalid_u']);
		}
		else {
			header("Location: {$url}");
		}
    }
    else {
        exit;
    }
  }
}
