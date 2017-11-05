<?php

/**
 * Backend of Memberpages_XH
 *
 * Copyright 2012-2016 by svasti@svasti.de
 */

if ((!function_exists('sv')) || preg_match('!admin.php!i', sv('PHP_SELF'))) die('Access denied');

define('MEMBERPAGES_VERSION', '3.6.5');

include 'funcs.php';

if (function_exists('XH_registerPluginMenuItem')) {
    XH_registerPluginMenuItem('memberpages',$plugin_tx['memberpages']['menu_main'],
        '?&memberpages&admin=plugin_main&action=plugin_text');
    XH_registerPluginMenuItem('memberpages',$plugin_tx['memberpages']['menu_log'],
        '?&memberpages&admin=plugin_config&action=log');
    XH_registerStandardPluginMenuItems(false);
    XH_registerPluginMenuItem('memberpages',$plugin_tx['memberpages']['menu_credits'],
        '?&memberpages&admin=plugin_config&action=credits');
}

if (function_exists('XH_wantsPluginAdministration') && XH_wantsPluginAdministration('memberpages')
   || isset($memberpages) && $memberpages === 'true'
) {
    $plugin = basename(dirname(__FILE__),"/");
    $admin  = isset($_POST['admin']) ? $_POST['admin'] : $admin = isset($_GET['admin']) ? $_GET['admin'] : '';
    $action = isset($_POST['action']) ? $_POST['action'] : $action = isset($_GET['action']) ? $_GET['action'] : '';

    $memberpageimages=$pth['folder']['plugins'].$plugin."/images/";


    $plugin_main_on   = ( !$admin || $admin=='plugin_main') ?        ' class="membp_selected"':'';
    $config_on        = ($admin=='plugin_config' && $action=='plugin_edit')? ' class="membp_selected"':'';
    $stylesheet_on    = $admin=='plugin_stylesheet'?                 ' class="membp_selected"':'';
    $language_on      = $admin=='plugin_language'?                   ' class="membp_selected"':'';
    $credits_on       = $action=='credits'?                          ' class="membp_selected"':'';
    $log_on           = $action=='log'?                              ' class="membp_selected"':'';

    $o .= '<p class="membp_admin_menu">' . "\n"
       .  '<a'.$plugin_main_on  .' href="?&amp;' . $plugin . '&amp;admin=plugin_main&action=plugin_text">'
       .  $plugin_tx[$plugin]['menu_main'].'</a>&nbsp; ' . "\n"
       .  '<a'.$log_on          .' href="?&amp;' . $plugin . '&amp;admin=plugin_config&action=log">'
       .  $plugin_tx[$plugin]['menu_log'].'</a>&nbsp; ' . "\n"
       .  '<a'.$config_on       .' href="?&amp;' . $plugin . '&amp;admin=plugin_config&action=plugin_edit">'
       .  $plugin_tx[$plugin]['menu_config'].'</a>&nbsp; ' . "\n"
       .  '<a'.$stylesheet_on   .' href="?&amp;' . $plugin . '&amp;admin=plugin_stylesheet&action=plugin_text">'
       .  $plugin_tx[$plugin]['menu_css'].'</a>&nbsp; ' . "\n"
       .  '<a'.$language_on     .' href="?&amp;' . $plugin . '&amp;admin=plugin_language&action=plugin_edit">'
       .  $plugin_tx[$plugin]['menu_language'].'</a>&nbsp; ' . "\n"
       .  '<a                      href="'.        $pth['file']['plugin_help'] . '" target="_blank">'
       .  $plugin_tx[$plugin]['menu_help'].'</a>&nbsp; ' . "\n"
       .  '<a'.$credits_on      .' href="?&amp;' . $plugin . '&amp;admin=plugin_config&action=credits">'
       .  $plugin_tx[$plugin]['menu_credits'].'</a></p>' . "\n";

    if($admin == '' && $action != 'credits') $admin = 'plugin_main';

    if($admin != 'plugin_main' and $admin != 'plugin_config')
    {
        $o .= plugin_admin_common($action,$admin,$plugin);
    }

    if($action=='credits') {
        $o.= '<h2>Memberpages_XH ' . constant('MEMBERPAGES_VERSION') . '</h2>' . "\n"
           . '<p>&copy; 2012-2016 by <a href="http://frankziesing.de/cmsimple/">svasti</a></p>'."\n"
           . '<p>based on:</p>' ."\n" . '<ul>'
           . '<li>versions 3.0 and higher by <a href="http://frankziesing.de/cmsimple/">svasti</a></li>'."\n"
           . '<li>versions 2.0–2.3 (2009-2010) by <a href="http://www.ge-webdesign.de">ge-webdesign.de</a></li>'."\n"
           . '<li>versions 0.1–1.7 (2005-2007) by <a href="http://cmsimpleplugins.svarrer.dk">cmsimpleplugins.svarrer.dk</a></li>'."\n".'</ul>' . "\n"
           . '<p>This program is free software: you can redistribute it and/or modify '
	       . 'it under the terms of the GNU General Public License as published by '
	       . 'the Free Software Foundation, either version 3 of the License, or '
	       . '(at your option) any later version.</p>'
           . '<p>For a copy of the GNU General Public License see https://www.gnu.org/licenses/gpl.html</p>' ."\n"
           . '<p><small><b>Acknowledgement:</b></small><br />'
           . 'Memberpages_XH uses Memberlist_XH by <a href="http://3-magi.net">cmb</a> to show which members are logged in.<p>'
           . "\n";

    }


// action on choosing "members list" (Plugin main) in plugin menu
//================================================================
    if($admin == 'plugin_main' && $action != 'memberslist') 
    {
        // activation/update
        //==========================================
        if(!$plugin_cf['memberpages']['version']
            || version_compare($plugin_cf['memberpages']['version'], constant('MEMBERPAGES_VERSION'), '!=')) {
            include 'pluginactivation.php';
            $o .= memberpagesUpdate();
        } elseif(isset($_POST['sendPwToNewMember'])) $o .= memberpages_SendEmailToNewMember();
        else $o .= memberpages_ShowMembersList();
    }


    // action on choosing "log" in plugin menu
    //================================================
    if($admin == 'plugin_config' && $action == 'log') $o .= memberpages_Log();


    // action on changing anything in the members list
    //=================================================
    if($admin == 'plugin_main' && $action == 'memberslist' && !isset($_POST['activate_memberpages'])) {
        $o .= memberpages_ProcessList();
    } 


    // action on choosing "config" in plugin menu
    //================================================
    if($admin == 'plugin_config' && $action == 'plugin_edit' || isset($_POST['save_memberpagesconfig'])) {

        if(isset($_POST['save_memberpagesconfig'])) {
            $t = memberpages_SaveConfig();
			$url = CMSIMPLE_URL . '?&memberpages&admin=plugin_config&action=plugin_edit';
			header("Location: $url", true, 303);
			exit;
        }
        $o .= memberpages_Config();
    }  
}
