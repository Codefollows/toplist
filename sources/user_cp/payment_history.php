<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2007 Jeremy Scheff.  All rights reserved.              \\
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

class payment_history extends base 
{
  public function __construct() 
  {
    global $CONF, $FORM, $DB, $LNG, $TMPL;

    $TMPL['header'] = 'Payment History';

	// Get all owner usernames
	$usernames = [];
	$result = $DB->query("SELECT username FROM `{$CONF['sql_prefix']}_sites` WHERE `owner` = '{$TMPL['username']}'", __FILE__, __LINE__);
	while (list($username) = $DB->fetch_array($result))
	{
		$usernames[] = $DB->escape($username, 1);
	}
	$usernames = implode("','", $usernames);
	
    list($pagination_rows) = $DB->fetch("SELECT COUNT(*) FROM `{$CONF['sql_prefix']}_payment_logs` WHERE `username` IN ('{$usernames}') AND `cheat` = 0", __FILE__, __LINE__);
	
	$page       = isset($FORM['p']) ? (int)$FORM['p'] : 1;
    $page_count = $pagination_rows > 0 ? ceil($pagination_rows / $CONF['num_list']) : 1;	
	
	if (isset($FORM['p']) && $page <= 1)
	{
		header("Location: {$CONF['list_url']}/index.php?a=user_cpl&b=payment_history");
		exit;
	}	
	elseif ($page > $page_count)
	{
		header("Location: {$CONF['list_url']}/index.php?a=user_cpl&b=payment_history&p={$page_count}");
		exit;
	}	
	
	$start = ($page * $CONF['num_list']) - $CONF['num_list'];	

	$result = $DB->select_limit("SELECT * FROM `{$CONF['sql_prefix']}_payment_logs` WHERE `username` IN ('{$usernames}') AND `cheat` = 0 ORDER BY `payment_date` DESC", $CONF['num_list'], $start, __FILE__, __LINE__);

    if ($page_count > 1) 
	{
		// Previous control
        if ($page > 1) 
		{
			$previous_page = $page - 1;		

            $TMPL['pagination_rel']  = 'rel="prev"';
            $TMPL['pagination_link'] = "{$CONF['list_url']}/index.php?a=user_cpl&b=payment_history";

			if ($previous_page > 1) {
				$TMPL['pagination_link'] .= "&amp;p={$previous_page}"; 
			}
			
			$TMPL['pagination_prev'] = $this->do_skin('pagination_prev');
		}
		
		// Page numbers
		$pagination_dots = true;
		$TMPL['pagination_items'] = '';

        for ($page_number = 1; $page_number <= $page_count; $page_number++) 
		{
			// If first or last page or the page number falls within the pagination limit, generate the links for these pages
			if($page_number == 1 || $page_number == $page_count || ($page_number >= $page - 4 && $page_number <= $page + 4))
			{
				// Set to true again for possible second dots block
				$pagination_dots = true;

				// Current Page, default state
				$TMPL['pagination_state'] = 'disabled active';
				$TMPL['pagination_link']  = '#';
					
				// All other page number links
				if ($page_number != $page) 
				{
					$TMPL['pagination_state'] = '';

					$TMPL['pagination_link'] = "{$CONF['list_url']}/index.php?a=user_cpl&b=payment_history";
					
					if ($page_number > 1) {
						$TMPL['pagination_link'] .= "&amp;p={$page_number}"; 
					}
				}
				
				$TMPL['pagination_page']   = $page_number;	
				$TMPL['pagination_items'] .= $this->do_skin('pagination_item');
			}
			elseif ($pagination_dots == true)
			{
				// set it to false, until needed again
				$pagination_dots = false;
				
				// The dots 
				$TMPL['pagination_state'] = 'disabled';
				$TMPL['pagination_rel']  = '';
				$TMPL['pagination_link']  = '#';
				$TMPL['pagination_page']  = '...';	
				
				$TMPL['pagination_items'] .= $this->do_skin('pagination_item');				   
			}
		}
					
		// Next control
		$next_page = $page + 1;
        if ($next_page <= $page_count) 
		{				
            $TMPL['pagination_rel']  = 'rel="next"';
			$TMPL['pagination_link'] = "{$CONF['list_url']}/index.php?a=user_cpl&b=payment_history&amp;p={$next_page}"; 
				
			$TMPL['pagination_next'] = $this->do_skin('pagination_next');
		}
    }
	
	$TMPL['payment_history_pagination'] = $this->do_skin('pagination');

	
    if ($DB->num_rows($result)) 
	{
		$TMPL['payment_history_rows'] = '';
		while ($row = $DB->fetch_array($result)) 
		{
			$service_info = json_decode($row['service_info'], true);
			$row['service_info'] = $service_info['info'];

			$row = array_map(function($value) {
				return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
			}, $row);
		
			$TMPL = array_merge($TMPL, $row);
		
			$TMPL['service_info'] = nl2br($TMPL['service_info']);
			
			// Not every provider passes back customer data
			// If they do pass email, it is displayed as "Prodiver: PayPal (email)"
			$TMPL['if_email'] = '';
			if (!empty($TMPL['email'])) {
				$TMPL['if_email'] = "({$TMPL['email']})";
			}

			$TMPL['payment_history_rows'] .= $this->do_skin('payment_history_row');
		}
	}
	else {
		$TMPL['payment_history_rows'] = '<tr><td colspan="8">No payments tracked yet</td></tr>';
	}
	
	$TMPL['user_cp_content'] = $this->do_skin('payment_history');
  }
}
