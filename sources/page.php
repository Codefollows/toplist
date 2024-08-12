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

class page extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $TMPL, $LNG;

    $id = isset($FORM['id']) ? $DB->escape($FORM['id'], 1) : '';

	if ($CONF['clean_url'] == 1 && preg_match('/\?/', $_SERVER['REQUEST_URI']))
	{
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$CONF['list_url']}/page/{$id}/");
		exit;
	}

    list($TMPL['id'], $TMPL['header'], $TMPL['content'], $description, $keywords) = $DB->fetch("SELECT id, title, content, description, keywords FROM {$CONF['sql_prefix']}_custom_pages WHERE id = '{$id}'", __FILE__, __LINE__);

    // Include tag
    $TMPL['content'] = preg_replace_callback('/{include \"(.+?)\"}/i', function($matches) {

        return file_get_contents($matches[1]);
    }, $TMPL['content']);

    $TMPL['meta_description'] = !empty($description) ? $description : $TMPL['meta_description'];
    $TMPL['meta_keywords']    = !empty($keywords)    ? $keywords    : $TMPL['meta_keywords'];

	// Canonical header real pagename
	$canonical_page = str_replace('&amp;', '&', "{$TMPL['list_url']}/{$TMPL['url_helper_a']}page{$TMPL['url_helper_id']}{$TMPL['id']}{$TMPL['url_tail']}");
	header("Link: <{$canonical_page}>; rel=\"canonical\"");

    eval (PluginManager::getPluginManager ()->pluginHooks ('page_query'));

    if(empty($TMPL['id'])) {
      $this->error($LNG['g_invalid_page']);
    }

  }
}
