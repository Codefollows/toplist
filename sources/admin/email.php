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

set_time_limit(0);
ini_set('max_execution_time',0);

class email extends base {
  public function __construct() {
    global $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_email_header'];

    if (!isset($FORM['submit'])) {
      $this->form();
    }
    else {
      $this->process();
    }
  }

  function form() {
    global $LNG, $TMPL, $DB, $CONF;

    $username_list = '';
    $extra_fields = '';

    $result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites WHERE unsubscribe != '1'", __FILE__, __LINE__);
    while (list($username) = $DB->fetch_array($result)) {
        $username_list .= $username.',';
    }

    $username_list = explode(",", rtrim($username_list, ','));
    $username_list = array_unique($username_list);
    $username_list = implode(", ", $username_list);

    // Plugin Hook - build form: use $extra_fields
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_email_build_form'));


    $TMPL['admin_content'] = <<<EndHTML

      <script type="text/javascript">

	    $(function() {

            var email_container = $('#email_results');
            var error_container = $('#error_container');

            // Update mail queue
            $("#inactive_days").on("input propertychange", function() {

                var dataObj = { action: 'username_list' };
                do_mail(dataObj);
            });

            $("#inactive_method").on("change", function() {

                var dataObj = { action: 'username_list' };
                do_mail(dataObj);
            });

            // Send email batches
            $("#submit").on("click", "button", function(e) {

                tinyMCE.triggerSave(); // Push tinymce to original textarea

                // empty subject or message? stop here
                if(!$('#subject').val() || !$('#message').val())
                {
                    do_error('Please enter a subject and message!', error_container);
                    return false;
                }

                // Some cleared box where mails are stored? stop here
                if(!$('#user_queue textarea').val())
                {
                    do_error('Please have at least one email in the box below!', error_container);
                    return false;
                }

                // Confirm to avoid "send" by mistake
                var agree = confirm("Are you sure you wish to proceed?");
                if (agree)
                {
                    $('#email_slide_away').slideToggle('slow', function() {

                        email_container.append('<div class="ui-state-highlight ui-corner-all"><span class="progress_bar" style="float: left; margin-right: .3em;"></span> Going through your user list, this may take a while. Please see below for the results.<div>Sent: <span id="sent_count">0</span> - Failed: <span id="fail_count">0</span><br class="cb"/></div></div>');
                        email_container.find('.ui-state-highlight').css({ 'padding': '5px', 'margin': '10px 0', 'font-size': '12px' });

                        var dataObj = {
                            action: 'send_mail',
                            subject: $('#subject').val(),
                            message: $('#message').val(),
                            username_list: $('#user_queue textarea').val()
                        };

                        do_mail(dataObj);
                    });
                }

                e.preventDefault();
            });

            function do_error(msg, container) {
                container.find('.ui-state-error').fadeOut('slow').remove();
                $('<div class="ui-state-error ui-corner-all"><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> '+msg+'</div>').hide().prependTo(container).fadeIn('slow');
                container.find('.ui-state-error').css({ 'padding': '5px', 'margin': '10px 0', 'font-size': '12px' });
            }

            function do_mail(dataObj) {

                // Send mail checks
                if(dataObj.action == 'send_mail')
                {
                    // No mails? stop here
                    if($.isEmptyObject(dataObj.username_list))
                    {
                        do_error('No members to email', email_container);
                        return false;
                    }

                    // Push throttle seconds to obj
                    dataObj.seconds = $('#seconds').val();
                }
                else if(dataObj.action == 'username_list')
                {
                    // Push inactive days method ( greater, smaller etc ) into object
                    dataObj.inactive_method = $("#inactive_method").val();
                }

                // Push inactive days into object
                dataObj.inactive_days = $("#inactive_days").val();

                $.ajax({
                    type: "POST",
                    url: "{$CONF['list_url']}/ajax.php",
                    data: dataObj,
                    async: true,
                    dataType: "json"
                }).success(function(response) {

                    // Update mail list
                    if(dataObj.action == 'username_list')
                    {
                        $("#user_queue textarea").val(response.username_list);

                        if($.isEmptyObject(response.username_list))
                        {
                            $('#submit').fadeOut('slow', function() {
                                do_error('No members to email', error_container);
                            });
                        }
                        else
                        {
                            error_container.find('.ui-state-error').fadeOut('slow', function() {
                                $(this).remove();
                                $('#submit').fadeIn('slow');
                            });
                        }
                    }
                    // Send the mail
                    else if(dataObj.action == 'send_mail')
                    {
                        var success = response.mail_sent == 1 ? 'yes' : 'no';

                        email_container.find('div:first-child').after('<div><img src="skins/admin/images/'+success+'.png" /> '+response.username+'</div>');
                        $('#sent_count').text(response.sent_count);
                        $('#fail_count').text(response.fail_count);

                        // Last mail
                        if($.isEmptyObject(response.next_set))
                        {
                            email_container.find('.ui-state-highlight').fadeOut('slow', function() {
                                do_error('Successfully went through the email list. No more members to email.<div>Sent: <span>'+response.sent_count+'</span> - Failed: <span>'+response.fail_count+'</span></div>', email_container);
                            });
                        }
                        // repeat function with new values
                        else
                        {
                            dataObj = {
                                action: 'send_mail',
                                subject: response.subject,
                                message: response.message,
                                username_list: response.next_set,
                                sent_count: response.sent_count,
                                fail_count: response.fail_count
                            };
                            do_mail(dataObj);
                        }
                    }
                }).error(function(jqXHR, textStatus, errorThrown) {
                    // Something went wrong with ajax! lets catch it and display
                    email_container.find('.ui-state-highlight').fadeOut('slow', function() {
                        do_error(errorThrown, email_container);
                    });
                });

            }

        });

      </script>

    <fieldset>
        <legend>{$LNG['a_email_header']}</legend>

        <div id="email_slide_away">
			<i>Subject and Message supports template tags as in any html file of your skin. So you can use for example "Hello {\$username}"</i>

            <label for="inactive_days">{$LNG['a_email_inactive_days']}</label>
                <select name="inactive_method" id="inactive_method">
                    <option value="gt">{$LNG['a_email_method_gt']}</option>
                    <option value="gte">{$LNG['a_email_method_gte']}</option>
                    <option value="lt">{$LNG['a_email_method_lt']}</option>
                    <option value="lte">{$LNG['a_email_method_lte']}</option>
                    <option value="eq">{$LNG['a_email_method_eq']}</option>
                </select>
                <input type="text" name="inactive_days" id="inactive_days" size="2" />


            {$extra_fields}

            <label for="subject">{$LNG['a_email_subject']}</label>
            <input type="text" name="subject" id="subject" size="50" />

            <label for="message">{$LNG['a_email_message']}</label>
            <textarea cols="40" rows="15" name="message" id="message" class="tinymce"></textarea>

            <label>{$LNG['a_email_throttle']} <input type="text" name="seconds" id="seconds" value="1" size="3"/></label>

            <div id="submit"><br /><button class="positive">{$LNG['a_email_header']}</button></div>
            <div id="error_container"></div>

            <div id="user_queue">
                <h2>The following users are in the queue to receive this email</h2>
                <textarea>{$username_list}</textarea>
            </div>

        </div>

        <div id="email_results"></div>

    </fieldset>

EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;


  }
}
