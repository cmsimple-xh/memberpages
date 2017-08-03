<?php
/**
 * @package	Memberpages
 * @copyright	Copyright (c) 2012-2016 svasti <http://svasti.de>
 *
 */

if ((!function_exists('sv')) || preg_match('!admin.php!i', sv('PHP_SELF')))die('Access denied');

$mbp_activated = is_file($pth['file']['plugin_config'])
    ? true
    : false;

if (!defined('CMSIMPLE_URL')) {
    define(
        'CMSIMPLE_URL',
        'http'
        . (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
        . '://' . $_SERVER['HTTP_HOST'] . $sn
    );
}

if (isset($plugin_cf['memberpages']['membersfilepath']) && $plugin_cf['memberpages']['membersfilepath']) {
    define('MEMBERSFILEPATH', $pth['folder']['base'] . rtrim($plugin_cf['memberpages']['membersfilepath'],'/').'/');
} else {
    define('MEMBERSFILEPATH', $pth['folder']['userfiles'].'plugins/memberpages/');
}

if ($mbp_activated) include_once 'memberlist.php';

if (preg_match("!Googlebot!i",$_SERVER['HTTP_USER_AGENT']));
else if (preg_match("!MSNbot!i",$_SERVER['HTTP_USER_AGENT']));
else if (preg_match("!slurp!i",$_SERVER['HTTP_USER_AGENT']));
elseif (function_exists('XH_startSession')) {
    XH_startSession();
} elseif (!session_id()) {
    session_start();
}
$plugin = basename(dirname(__FILE__),"/");

//if php 4 is used, this function has to be supplied (by cmb)
if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $data) {
        $f = @fopen($filename, 'w');
        if (!$f) {
            return false;
        } else {
        if (is_array($data)) {$data = implode('', $data);}
            $bytes = fwrite($f, $data);
            fclose($f);
            return $bytes;
        }
    }
}
if (!function_exists('XH_hsc')) {
    function XH_hsc($str) {
        return htmlspecialchars($str);
    }
}


###################################################################################################

if ($function == 'autologin') {
    setcookie("cookname", $_SESSION['username'], time()+60*60*24*100, "/");
    setcookie("cookpass", $_SESSION['password'], time()+60*60*24*100, "/");
    $_SESSION['autologin'] = 1;
}

if ($function == 'autologinoff') {
    setcookie("cookname", "", 1, "/");
    setcookie("cookpass", "", 1, "/");
    unset($_SESSION['autologin']);
}

###################################################################################################

// LOGOUT
// has to be processed before the hiding feature, as otherwise on logout
// from a memberpage that page is still visible along with the membersnotice

if ($function == 'memberslogout')
{
    $username = isset($_SESSION['username'])? $_SESSION['username'] : '???';
    unset($_SESSION['username'], $_SESSION['accesslevel'], $_SESSION['password'],
          $_SESSION['fullname'], $_SESSION['autologin']);
    if (isset($_COOKIE['cookname']) || isset($_COOKIE['cookpass']))
    {
        setcookie("cookname", "", time()-60*60*24*100, "/");
        setcookie("cookpass", "", time()-60*60*24*100, "/");
    }
    memberpages_LogEntry($username .' logged out');

    //If a logout_page is defined, use it
    if ($plugin_tx['memberpages']['page_on_logout'])
    {
        header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_on_logout']);
        exit;
    }
    //else give a message
    else
    {
        $o .= '<div class="membp_message">'.$plugin_tx['memberpages']['notice_logged_out'].'</div>';
    }
}

###################################################################################################
//Hide memberpages

if ($mbp_activated) {

    //get memberslist
    $member = parse_ini_file(MEMBERSFILEPATH.'membersfile.php',true);

    //hide pages via pdtabs
    if ($plugin_cf['memberpages']['pdtab']) memberpages_HidePages();

    //hide pages via CMSimple scripting
    if ($plugin_cf['memberpages']['texttrigger']) {
        include_once 'oldfuncs.php';
        memberpages_OldStyleHide();
    }

    // automatic log in
    if ($plugin_cf['memberpages']['rememberme'])
    {
        if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookpass']))
        {
            if (!isset($_SESSION['username']))$function='memberslogin';
        }
    }
}
// PASSWORD FORGOTTEN
$temp = isset($_POST['password_forgotten']) ? $_POST['password_forgotten'] : '';
if (isset($_POST['stoppwforgotten'])) $temp = $function = '';
if ($temp) $o .= memberpages_PwForgotten();

// Register Me
$temp = isset($_POST['register_me']) ? $_POST['register_me'] : '';
if (isset($_POST['stopregister_me'])) $temp = $function = '';
if ($temp) $o .= memberpages_RegisterMe();

###################################################################################################


// LOGIN
if ($function == 'memberslogin')
{
    $loginpassed = FALSE;
    initvar('username');
    initvar('password');
    if ($plugin_cf['memberpages']['show_fullname']) initvar('fullname');
    if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookpass']))
    {
        $username = $_COOKIE['cookname'];
        $password_hash = $_COOKIE['cookpass'];
        $autologin = true;
    }
    else
    {
        $password_hash = memberpages_Hash($password);
        $autologin = false;
    }

    foreach ($member as $i=>$value) {

        if ($username == $member[$i]['user'] && $password_hash == memberpages_Hash($member[$i]['pass'])) {

            if ($plugin_cf['memberpages']['show_expires']
                && $member[$i]['expires']
                && $member[$i]['expires'] < time()) {
                if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookpass'])) {
                    setcookie("cookname", "", 1, "/");
                    setcookie("cookpass", "", 1, "/");
                }

                memberpages_LogEntry(" $username rejected: membership expired");

                $loginpassed = TRUE;

                //If an expired page is defined use it
                if ($plugin_tx['memberpages']['page_on_expired']) {
                    header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_on_expired']);
                    exit;
                } else {
                    $o .= '<div class="membp_message">'.$plugin_tx['memberpages']['notice_membership_expired'].'</div>';
                }
                break;

            } else {
                if ($autologin) $_SESSION['autologin']=1;
                $_SESSION['accesslevel'] = $member[$i]['access'];
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $password_hash;
                if ($plugin_cf['memberpages']['show_fullname']) {
                    $_SESSION['fullname'] = $member[$i]['full']? $member[$i]['full'] : $username;
                }
                if ($plugin_cf['memberpages']['show_expires']) {
                    $_SESSION['expires'] = $member[$i]['expires']? $member[$i]['expires'] : '';
                }
                memberpages_LogEntry("$username logged in");

                $loginpassed = TRUE;

                //If a login_page is defined use it
                if ($plugin_cf['memberpages']['VIP_level']
                    && $plugin_tx['memberpages']['page_on_VIP_login']
                    && $_SESSION['accesslevel'] >= $plugin_cf['memberpages']['VIP_level']) {

                    header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_on_VIP_login']);
                    exit;

                } else if ($plugin_tx['memberpages']['page_on_login']) {

                    header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_on_login']);
                    exit;

                } else {
                    header('Location: '.CMSIMPLE_URL.'?'.$su);
                    exit;
                }
            }
            break;
        }
    }
    if (!$loginpassed) {

        if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookpass']))
        {
            setcookie("cookname", "", 1, "/");
            setcookie("cookpass", "", 1, "/");
        }

        memberpages_LogEntry($username . ' wrong name/password');

        header('Location: '.$sn.'?'.$su.'&error=true#membp_anchor');
        exit;
    }
}


###################################################################################################

// Show members notice on every page for logged in members
if ($plugin_cf['memberpages']['onlogin_membersnotice'] == 1
    && isset($_SESSION['username'])
    && !(XH_ADM && $edit))
{
    foreach ($c as $i=>$page) {
        $c[$i] = loggedinform(true) . $page;
    }
}

// Hide a page for logged in members
if ($plugin_tx['memberpages']['hide_on_login'] && isset($_SESSION['username']) && !(XH_ADM && $edit)) {
    $hideOnLogin = array_search($plugin_tx['memberpages']['hide_on_login'],$u);
    $c[$hideOnLogin] = '#CMSimple hide#';
}

###################################################################################################

// Members Control Panel for changing password and email address

if ($function=='memberspanel' || $function=='savepaneldata') $o .= memberpages_MembersPanel();
 

##################################################################################################



// FUNCTIONS

function memberpages_LogEntry($entry)
{
    global $pth;
    $log = fopen(MEMBERSFILEPATH . 'log.txt', 'a');
    fwrite($log, date("Y-m-d H:i:s") . ' ' . $entry . "\n");
    fclose($log);
}



function memberpages_Mail($to, $subject, $message)
{
    global $plugin_cf;

    $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
    $header = "MIME-Version: 1.0\r\nContent-type: text/plain; charset=UTF-8\r\nFrom: "
            . $plugin_cf['memberpages']['site_email'];
    return mail($to, $subject, $message, $header);
}



function memberpages_Hash($item)
{
    global $cf;
    if (function_exists('hash')) return hash('sha256',$cf['security']['password'].$item,true);
    else return sha1($cf['security']['password'].$item,true);
}



function memberslogin($membersnotice=false)
{
    global $sn,$su,$plugin_cf,$plugin_tx,$tx,$mbp_activated;
    $o = '';

    if (!isset($_SESSION['username']))
    {
        if ($plugin_cf['memberpages']['small_login'] && !isset($_GET['error']))
        {
            $o .= "<a class='membp_not_clicked'
                  style='cursor:pointer;margin:-3px;padding:3px;display:block;'
                  id='small_memberpages_Login'
                  onClick=\"
                 if (document.getElementById('small_memberpages_Login').className == 'membp_not_clicked') {
                   document.getElementById('small_memberpages_Login').className = 'membp_clicked';
                   document.getElementById('memberpages_Login').style.display = 'block';
                 } else {
                   document.getElementById('small_memberpages_Login').className = 'membp_not_clicked';
                   document.getElementById('memberpages_Login').style.display = 'none';
                 }\">" . $plugin_tx['memberpages']['log_in_small'] .'</a>';
            $o .= '<div id="memberpages_Login">';
        }
        $error = isset($_GET['error'])
            ?  '<div class="membp_message">'
               . $plugin_tx['memberpages']['notice_login_error'] . '</div>'
            :  '';

        $o .= "\n"
           .  '<a class="membp_anchor"  id="membp_anchor"></a>'
           .  $error
           .  '<form action="'.$sn.'?'.$su.'" method="post">'
           .  "\n"
           .  '<input type="hidden" name="function" value="memberslogin">'
           .  "\n"
           .  $plugin_tx['memberpages']['memberslist_username']
           .  '<br>'
           .  "\n"
           .  '<input type="text" name="username" class="membp_member" tabindex="1">'
           .  '<br>'
           .  "\n"
           .  $plugin_tx['memberpages']['memberslist_password'];
        $o .= '<br>'
           .  "\n"
           .  '<input type="password" name="password" class="membp_member" tabindex="2">'
           .  '<br>'
           .  "\n"
           .  '<button type="submit" name="submit" class="membp_member_button" value="1" tabindex="3">'
           .  $plugin_tx['memberpages']['log_in'] . '</button>';

        if ($plugin_cf['memberpages']['passwordforgotten'] && $plugin_cf['memberpages']['site_email'])
        {
            $o .= ' <button type="submit" name="password_forgotten" value="1" class="membp_small_button" tabindex="5">'
               .  $plugin_tx['memberpages']['passwordforgotten'] . '</button>';
        }
        if ($plugin_cf['memberpages']['registerme'])
        {
            $o .= '<br>'
               .  '<button type="submit" name="register_me" value="1" class="membp_registerme" tabindex="6">'
               .  $plugin_tx['memberpages']['registerme'] . '</button>';
        }
        $o .= '</form>'."\n"."\n";
        if ($plugin_cf['memberpages']['small_login'] && !isset($_GET['error']))
        {
            $o .=  '</div><script type="text/javascript">
                // <![CDATA[
                    document.getElementById(\'memberpages_Login\').style.display = \'none\';
                 // ]]>
                </script>';
        }
    }
    else
    {
        $o = $membersnotice
            ? '<p>'.$plugin_tx['memberpages']['notice_loggedin'].'</p>'
            : loggedinform();
    }
    return $o;
}


function loggedinform ($oneline=false)
{
    global $sn,$su,$plugin_cf,$plugin_tx,$tx,$pth,$function;
    $autologin = $logoff = $controlpanel = $warning = $actives = '';

    $loggedinnotice =  sprintf($plugin_tx['memberpages']['notice_loggedin_as'], $_SESSION['username']);

    if ($plugin_cf['memberpages']['show_expires']
        && $plugin_cf['memberpages']['warning_time']
        && isset($_SESSION['expires']) && $_SESSION['expires']
        && ($_SESSION['expires'] - $plugin_cf['memberpages']['warning_time'] * 86400 ) < time()) {
        $warning .= '<span class="membp_warning">';
        $warning .=  $oneline
            ? $plugin_tx['memberpages']['memberslist_expires'] .' ' . date("d.m.Y",$_SESSION['expires']) .'.'
            : sprintf($plugin_tx['memberpages']['warning_text'],date("d.m.Y",$_SESSION['expires']));
        $warning .=  '</span>';
    }

    if ($plugin_cf['memberpages']['show_actives']) {
        $actives .= $plugin_tx['memberpages']['notice_active_members'] . ': '. memberlist(true);
    } 

    if ($plugin_cf['memberpages']['rememberme']) {

        if (isset($_SESSION['autologin'])) {

            $autologin .= '<form action="'.$sn.'?'.$su.'" method="post">'
                .  '<input type="hidden" name="function" value="autologinoff">'
                .  '<input type="checkbox" checked="checked" onChange="this.form.submit()">'
                .  $plugin_tx['memberpages']['log_in_remember_me']
                .  '<noscript><button type="submit" style="padding:0">'
                .  $plugin_tx['memberpages']['change']
                . '</button></noscript>' . '. '
                .  '</form>';
        } else {
            $autologin .= '<form action="'.$sn.'?'.$su.'" method="post">'
                .  '<input type="hidden" name="function" value="autologin">'
                .  '<input type="checkbox" onChange="this.form.submit()">'
                .  $plugin_tx['memberpages']['log_in_remember_me']
                .  '<noscript><button type="submit" style="padding:0">'
                .  $plugin_tx['memberpages']['change']
                . '</button></noscript>' . ' ?'
                .  '</form>';

            $logoff .= '<form style="display:inline;" action="'.$sn.'?'.$su.'" method="post">'
                 . '<input type="hidden" name="function" value="memberslogout">'
                 . '<button type="submit" style="white-space:normal;">'
                 . $plugin_tx['memberpages']['log_out']
                 . '</button></form>';
        }
    } else {
        $logoff .='<form style="display:inline;" action="'.$sn.'?'.$su.'" method="post">'
            . '<input type="hidden" name="function" value="memberslogout">'
            . '<button type="submit">'.$plugin_tx['memberpages']['log_out'].'</button>'
            . '</form>';
    }

    if ($plugin_cf['memberpages']['controlpanel']) {
        $controlpanel .= '<form action="'.$sn.'?'.$su.'" method="post">';
        $controlpanel .= $function == 'memberspanel' || $function == 'savepaneldata'
           ?  '<input type="hidden" name="function" value="closepanel">'
               .  '<button type="submit" class="membp_buttonpressed">'
           :  '<input type="hidden" name="function" value="memberspanel">'
               .  '<button type="submit">';
        $controlpanel .= $plugin_tx['memberpages']['member_control_panel']
            . '</button></form>';
    }

    return $oneline
        ? '<div class="membp_oneline">'
            . $loggedinnotice . ' '
            . $controlpanel
            . $logoff 
            . $autologin
            . $warning
            . ' '
            . $actives
            . '</div>'
        : $loggedinnotice . ' '
            . $autologin
            . $warning
            . $actives
            . $logoff
            . $controlpanel;
}



function membersnotice($oneline=false)
{
    return isset($_SESSION['username'])
    ? loggedinform($oneline)
    : '';
}



function memberpages_HidePages()
{
    global $pd_router,$pth,$adm,$edit,$plugin_cf,$c;

    // Add used interests to router
    $pd_router -> add_interest('mpage');
    $pd_router -> add_interest('mplevel');

    // tab for admin-menu
    $pd_router -> add_tab('M', $pth['folder']['plugins'].'memberpages/memberpages_view.php');

    // MAIN PLUGIN-FUNCTION: hide memberpages unless a member is logged in or CMS is in edit or adm mode

    if (!$adm OR ($adm && !$edit))
    {
        $pages = $pd_router -> find_all();
        foreach($pages as $i => $values)
        {
            if (isset($values['mpage']) && $values['mpage'])
            {
                if ($plugin_cf['memberpages']['onlogin_membersnotice'] == 2 && isset($_SESSION['username'])) {
                    $c[$i] = loggedinform(true) . $c[$i];
                }

                if ($values['mplevel'])
                {
                    $access_level = $values['mplevel'];

                    if (!$plugin_cf['memberpages']['accessmode'])
                    {
                        if (!isset($_SESSION['accesslevel']))
                        {
                            $c[$i] = '#CMSimple hide#';
                        }
                    }
                    elseif ($plugin_cf['memberpages']['accessmode']==1)
                    {
                        if (!isset($_SESSION['accesslevel'])
                           || $_SESSION['accesslevel'] < $access_level)
                        {
                            $c[$i] = '#CMSimple hide#';
                        }
                    }
                    elseif ($plugin_cf['memberpages']['accessmode']==1.5)
                    {
                        if (!isset($_SESSION['accesslevel'])
                             || ($_SESSION['accesslevel'] < $plugin_cf['memberpages']['VIP_level']
                                 && $_SESSION['accesslevel'] != $access_level)
                             || ($_SESSION['accesslevel'] > $plugin_cf['memberpages']['VIP_level']
                                 && $_SESSION['accesslevel'] < $access_level))
                        {
                            $c[$i] = '#CMSimple hide#';
                        }
                    }
                    elseif ($plugin_cf['memberpages']['accessmode']==2)
                    {
                        if (!isset($_SESSION['accesslevel'])
                             || $_SESSION['accesslevel'] != $access_level)
                        {
                            $c[$i] = '#CMSimple hide#';
                        }
                    }
                    elseif ($plugin_cf['memberpages']['accessmode'] > 2)
                    {
                        if (!isset($_SESSION['accesslevel'])
                           || !($_SESSION['accesslevel'] & pow(2,$access_level-1)))
                        {
                            $c[$i] = '#CMSimple hide#';
                        }
                    }
                }
                else
                {
                    if (!isset($_SESSION['accesslevel']))
                    {
                        $c[$i] = '#CMSimple hide#';
                    }
                }
            }
        }
    }
}



function memberpages_PwForgotten()
{
    global $function,$member,$plugin_tx,$plugin_cf;

    $o = $function = $log = $answer = $name = $email = '';
    $ok = false;

    // ========================= Sending the password =========================
    if (isset($_POST['pwforgotten']))
    {
        $name    = isset($_POST['name_pwforgotten'])  ? trim(stsl(XH_hsc($_POST['name_pwforgotten'])))    : '';
        $email   = isset($_POST['email_pwforgotten']) ? stsl(XH_hsc($_POST['email_pwforgotten']))   : '';

        if (!$name) {
            $answer .= '<ul><li>'.$plugin_tx['memberpages']['passwordforgotten_no_name'].'</li></ul>';
        } else {

            $found_key = false;
            foreach ($member as $key => $value) {
                if ( $name == $member[$key]['user']
                    && $name
                    && $member[$key]['user']
                    && $member[$key]['email']) {
                    $found_key = $key;
                    break;
                }
            }
            // prepare sending of mail
            if ($found_key!==false) {
                $to = $member[$found_key]['email'];
                $message = sprintf($plugin_tx['memberpages']['passwordforgotten_email_message'],
                                   $member[$found_key]['user'],CMSIMPLE_URL,$member[$found_key]['pass']);
            } else {
            // in case no member's email or no member's name was found
                $log .= "pw asked by $name, name not found — ";

                // send mail to site address/webmaster
                $to = $plugin_cf['memberpages']['admin_email'];
                $message = sprintf($plugin_tx['memberpages']['passwordforgotten_webmaster_inform'], CMSIMPLE_URL,$name);
                if ($email) $message .= "\n\n"
                                     . $plugin_tx['memberpages']['passwordforgotten_webmaster_inform_email']
                                     . ': '.$email;
            }

            $subject = $plugin_tx['memberpages']['passwordforgotten_email_subject'];

            if (memberpages_Mail($to,$subject,$message)) {

                $answer .= $found_key!==false
                        ? $plugin_tx['memberpages']['passwordforgotten_send']
                        : $plugin_tx['memberpages']['passwordforgotten_webmaster_contacted'] ;

                $log .= $found_key!==false
                    ? 'pw send to '.$member[$found_key]['user'].' at '.$member[$found_key]['email']
                    : "pw request by $name sent to $to ";
                $ok = true;

            } else {

                $answer =  sprintf($plugin_tx['memberpages']['passwordforgotten_could_not_send'],$plugin_cf['memberpages']['admin_email']);
                $log .= "pw asked by $name, mail function failed ";
            }

            // writing to log file
            if ($log) memberpages_LogEntry($log);
        }
    }

    //========================= Form for asking for the forgotten password to be send ============================
    $o .= '<div class="membp_message" id="pwforgotten">'
       . $plugin_tx['memberpages']['notice_password_forgotten'];

    if ($answer) $o .= "\n" . '<div>' ."\n"
                    .  $answer
                    . '</div>' . "\n" ;

    $o .= '<form action="" method="post">'
       . '<input type="hidden" name="password_forgotten" value="1">'
       . '<table class="membp_memberspanel">';

    if (!$ok) {
       $o .= '<tr>'
       .  '<td style="text-align:center">'
       .  $plugin_tx['memberpages']['passwordforgotten_name']
       .  ' </td><td>'
       .  '<input type="text" maxlength="20" name="name_pwforgotten" value="'.$name.'">'
       .  '</td>'
       .  '</tr>' . "\n"
       .  '<td style="text-align:center">'
       .  $plugin_tx['memberpages']['passwordforgotten_email']
       .  ' </td><td>'
       .  '<input type="text" maxlength="60" name="email_pwforgotten" value="'.$email.'">'
       .  '</td>'
       .  '</tr>' . "\n"

       .  '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>'

       .  '<tr><td colspan="2" class="membp_justify">'
       .  $plugin_tx['memberpages']['passwordforgotten_text']
       .  "</td></tr>\n";
    }

     $o .= $ok
       ?  '<tr><td colspan="2">' . '<br>'
       .  '<input type="button" value="'.$plugin_tx['memberpages']['close']
       .  '" onclick="document.getElementById(\'pwforgotten\').style.display = \'none\';">'
       .  '</td></tr>' . "\n"
       :  '<tr><td>'
       .  '<button type="submit" name="stoppwforgotten"'
       .  ' onclick="document.getElementById(\'pwforgotten\').style.display = \'none\';">'
       .  $plugin_tx['memberpages']['close']
       .  '</button></td><td>'
       .  '<button type="submit" name="pwforgotten">'
       .  $plugin_tx['memberpages']['passwordforgotten_send_password']
       .  '</button></td></tr>' . "\n";

    $o .= '</table></div>' . "\n";

    return $o;
}



function memberpages_RegisterMe()
{
    global $function,$member,$plugin_tx,$plugin_cf,$pth;

    $function = $answer = $username = $email = $email2 = $fullname = $comment = $log = '';
    $ok = false;

    // ========================= receiving the registration request =========================

    if (isset($_POST['register']))
    {
        $ok = true;
        $username = isset($_POST['username']) ? trim(stsl(XH_hsc(str_replace(array('"','\\'),'',$_POST['username'])))) : '';
        $email    = isset($_POST['email'])    ? stsl(XH_hsc(str_replace(array('"','\\'),'',$_POST['email'])))    : '';
        $email2   = isset($_POST['email2'])   ? stsl(XH_hsc(str_replace(array('"','\\'),'',$_POST['email2'])))   : '';
        $fullname = isset($_POST['fullname']) ? trim(stsl(XH_hsc(str_replace(array('"','\\'),'',$_POST['fullname'])))) : '';
        $comment  = isset($_POST['comment'])  ? stsl(XH_hsc(str_replace(array('"','\\'),'',$_POST['comment'])))  : '';

        if (!$username) {
            $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_username_missing'].'</li></ul>';
            $ok = false;
        }
        if ($username && mb_strlen($username,'UTF-8')<3) {
            $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_username_too_short'].'</li></ul>';
            $ok = false;
        }
        if ($username && strpos($username, ' ')) {
            $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_username_has_space'].'</li></ul>';
            $ok = false;
        }
        if (!$email) {
            $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_email_missing'].'</li></ul>';
            $ok = false;
        }
        if ($email != $email2) {
            $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_email_not_matching'].'</li></ul>';
            $ok = false;
            $email = $email2 = '';
        }
        if ($email && $email==$email2 && !preg_match('/^.+@\S+$/u',$email)) {
            $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_email_no_email'].'</li></ul>';
            $ok = false;
        }
        foreach ($member as $key=>$value) {
            if ($username==$member[$key]['user']) {
                $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_username_taken'].'</li></ul>';
                $ok = false;
                break;
            }
            if ($fullname && $fullname==$member[$key]['full']) {
                $answer .= '<ul><li>'.$plugin_tx['memberpages']['register_fullname_taken'].'</li></ul>';
                $ok = false;
                break;
            }
        }

        if ($ok) {
            $to = $plugin_cf['memberpages']['admin_email']
                ? $plugin_cf['memberpages']['admin_email']
                : $plugin_cf['memberpages']['site_email'];

            $message = sprintf($plugin_tx['memberpages']['register_via_webmaster'], $username, $fullname, $email, $comment);
            $subject = $plugin_tx['memberpages']['register_email_subject'];

            if (memberpages_Mail($to,$subject,$message)) {

                $answer .= $plugin_tx['memberpages']['register_sent'] . '<br>'
                         . $plugin_tx['memberpages']['memberslist_username'] . ' = ' . $username  . '<br>'
                         . $plugin_tx['memberpages']['memberslist_email'] . ' = ' . $email;
                if ($fullname) $answer .= '<br>' . $plugin_tx['memberpages']['memberslist_fullname'] . ' = '  . $fullname;
                if ($comment)  $answer .= '<br>' . $plugin_tx['memberpages']['memberslist_comment'] . ' = '   . $comment;
                $log .= "registration asked by $username ($fullname) at $email ";
                file_put_contents(MEMBERSFILEPATH
                    . 'new.php','['. date("Y-m-d H:i:s") .']' . PHP_EOL
                    . 'name = "' . $username . '"' . PHP_EOL
                    . 'fullname = "' . $fullname . '"' . PHP_EOL
                    . 'email = "' . $email . '"' . PHP_EOL
                    . 'comment = "' . $comment . '"' . PHP_EOL, FILE_APPEND | LOCK_EX);

            } else {

                $answer .= $plugin_tx['memberpages']['register_failed'] .' '. $to ;
                $log .= "registration asked by $username ($fullname) at $email, mail function failed ";
                $ok = false;
            }
            memberpages_LogEntry($log);
        }
    }

    //================================ register me display ======================================
    $o = '<div class="membp_message" id="register_me">'.$plugin_tx['memberpages']['register_headline'];

    $o .= '<form action="" method="post">'
      . '<input type="hidden" name="register_me" value="1">';

    if ($answer) $o .= "\n" . '<div>' ."\n"
                    .  $answer
                    . '</div>' . "\n" ;

    if (!$ok) {
        $o .='<table class="membp_memberspanel">'
           . '<tr>'
           . '<td style="text-align:center">'
           . $plugin_tx['memberpages']['register_username']
           . ' </td><td>'
           . '<input type="text" name="username" maxlength="20" value="'.$username.'">'
           . '</td>'
           . '</tr>' . "\n"

           . '<tr>'
           . '<td style="text-align:center">'
           . $plugin_tx['memberpages']['register_email']
           . ' </td><td>'
           . '<input type="text" name="email" maxlength="60" value="'.$email.'">'
           . '</td>'
           . '</tr>' . "\n"
           . '<tr>'
           . '<td style="text-align:center">'
           . $plugin_tx['memberpages']['register_email2']
           . ' </td><td>'
           . '<input type="text" name="email2" value="'.$email2.'">'
           . '</td>'
           . '</tr>' . "\n"

           . '<tr>'
           . '<td style="text-align:center">'
           . $plugin_tx['memberpages']['register_fullname']
           . ' </td><td>'
           . '<input type="text" name="fullname" maxlength="60" value="'.$fullname.'">'
           . '</td>'
           . '</tr>' . "\n"

           . '<tr>'
           . '<td style="text-align:center">'
           . $plugin_tx['memberpages']['register_comment']
           . ' </td><td>'
           . '<textarea name="comment" style="height:3em;width:100%;">' . $comment . '</textarea>'
           . '</td>'
           . '</tr>' . "\n"

           . '<tr><td colspan="2">'
           . $plugin_tx['memberpages']['register_pw_by_webmaster'];

        if ($plugin_cf['memberpages']['controlpanel']) {
            $o .= ' ' . $plugin_tx['memberpages']['register_pw_change'];
        }

        $o .=  "</td></tr>\n";
    }

    $o .=  $ok
        ?  '<tr><td colspan=2>' . '<br>'
        .  '<input type="submit" name="stopregister_me" value="'.$plugin_tx['memberpages']['close']
        .  '" onclick="document.getElementById(\'register_me\').style.display = \'none\';">'
        . ' </td>'
        : '<tr><td>'
        .  '<button type="submit" name="stopregister_me"'
        .  ' onclick="document.getElementById(\'register_me\').style.display = \'none\';">'
        .  $plugin_tx['memberpages']['close']
        . '</button></td><td>'
        . '<button type="submit" name="register">'.$plugin_tx['memberpages']['register'].'</button>'
        . '</td>';

    $o .= '</tr>' . "\n" . '</table>' . "\n" . '</form>' . "\n" . '</div>';

    return $o;
}



function memberpages_MembersPanel()
{
    global $pth,$plugin_cf,$plugin_tx;
    $member = parse_ini_file(MEMBERSFILEPATH.'membersfile.php',true);
    $tx = $plugin_tx['memberpages'];
    $o = $answer = $oldpassword = $newpassword = $newpassword2 = $newemail = $newfullname = '';
    $i = false;
    foreach ($member as $key=>$value) {
         if ($_SESSION['username']==$member[$key]['user']) {
            $i = $key;
            break;
         }
    }
    if ($i === false) return false;

    $ok = $systemfault = false;
    $not_ok_detail = $change = '';
    $nothingnew = true;
    $pwlength = round($plugin_cf['memberpages']['passwordbyuser']);
    $pwextra = $plugin_cf['memberpages']['passwordbyuser'] * 10 % 10;


    // ======================== processing received changes =================================
    if (isset($_POST['function']) && $_POST['function']=='savepaneldata')
    {
        $oldpassword    = isset($_POST['oldpassword'])  ? stsl($_POST['oldpassword'])       : '';
        $newpassword    = isset($_POST['newpassword'])  ? stsl($_POST['newpassword'])       : '';
        $newpassword2   = isset($_POST['newpassword2']) ? stsl($_POST['newpassword2'])      : '';
        $newemail       = isset($_POST['newemail'])     ? trim(stsl($_POST['newemail']))    : '';
        $newfullname    = isset($_POST['newfullname'])  ? str_replace('"','',trim(stsl($_POST['newfullname']))) : '';

        $pw_found          = $oldpassword? true : false;
        $pw_wrong          = ($pw_found && $member[$i]['pass'] != $oldpassword)? true : false;
        $newpw_notmatching = $newpassword && $newpassword != $newpassword2 ? true : false;
        $newpw_too_short   = $newpassword && mb_strlen($newpassword,'UTF-8') < $pwlength? true : false;
        $newpw_too_simple  = $newpassword && $pwextra
            ? ($pwextra== 1 && !preg_match('!^.*(?=.*[\p{Lu}]).*(?=.*[\p{Ll}]).*$!u',$newpassword)
              ? true
              : $pwextra == 2 && !preg_match('!^.*(?=.*[\p{Lu}]).*(?=.*[\p{Ll}]).*(?=.*[\p{N}]).*$!u',$newpassword)
              ? true
              : false)
            : false;
        $newpw_forb_chars  = $newpassword && !preg_match('!^[^\"\\\\]*$!u',$newpassword) ? true : false;

        if ($newpassword && !$newpw_notmatching && !$newpw_too_short && !$newpw_too_simple && !$newpw_forb_chars
            && !$newpw_notmatching && $member[$i]['pass'] != $newpassword)
            $change .= $tx['new_password'] . '<br><br>';
        if ($newpassword && $member[$i]['pass'] != $newpassword) $nothingnew = false;


        $newemail_not_ok   = ($newemail && !preg_match('!^[^\r\n|,]+@[^\s|;|{|}|,]+$!',$newemail))? true : false;
        if (!$newemail_not_ok && $newemail && $newemail != $member[$i]['email'])
            $change .= $tx['new_email'] . '<br><br>';
        if ($newemail && $member[$i]['email'] != $newemail) $nothingnew = false;

        $fullname_not_ok   = ($newfullname && !preg_match('!^[^\r\n|;|{|}|<|>|,]*$!',$newfullname))? true : false;
        if ($newfullname && !$fullname_not_ok && $newfullname != $member[$i]['full'])
            $change .= $tx['new_full_name'] . '<br><br>';
        if ($newfullname && $member[$i]['full'] != $newfullname) $nothingnew = false;


        if ($pw_found && !$pw_wrong && !$newpw_notmatching && !$newpw_too_short && !$newpw_forb_chars
            && !$newpw_too_simple && !$newemail_not_ok && !$fullname_not_ok && !$nothingnew) {

            $newpassword = $newpassword? $newpassword : $oldpassword;

            $member[$i]['full'] = $newfullname;
            if ($newpassword) {
                $member[$i]['pass'] = $newpassword;
                $member[$i]['x'] = 1;
            }  
            $member[$i]['email']    = $newemail;
            if (memberpages_SaveList($member)) {
                $ok = true;
                if ($newpassword) $_SESSION['password'] = $newpassword;
                if ($newfullname != $_SESSION['fullname']) {
                    $_SESSION['fullname'] = $newfullname;
                } 
            } else {
                $sytemfault = true;
            }

        }

        $answer  .= "\n<div>\n$change";

        if ($systemfault)       $answer .= $tx['error_systemfault_memberspanel']
                                        .  '<br><br>';
        if (!$pw_found)         $answer .= $tx['error_password_not_entered']
                                        .  '<br><br>';
        if ($pw_wrong)          $answer .= $tx['error_wrong_password']
                                        .  '<br><br>';
        if ($newpw_notmatching) $answer .= $tx['error_passwords_not_matching']
                                        .  '<br><br>';
        if ($newpw_too_short)   $answer .= $tx['error_password_too_short']
                                        .  '<br><br>';
        if ($newpw_too_simple)  $answer .= $pwextra == 1
            ? $tx['error_password_no_caps'] . '<br><br>'
            : $tx['error_password_too_simple'] . '<br><br>';
        if ($newpw_forb_chars)  $answer .= $tx['error_password_forbidden_chars']
                                        .  '<br><br>';
        if ($newemail_not_ok)   $answer .= $tx['error_email_wrong']
                                        .  '<br><br>';
        if ($fullname_not_ok)   $answer .= $tx['error_fullname_wrong']
                                        .  '<br><br>';
        if ($nothingnew)        $answer .= $tx['error_no_change_detected']
                                        .  '<br><br>';

        $answer .= $ok
                ? $tx['notice_data_successful_changed']
                : $tx['notice_data_not_changed'];
        $answer .= "\n</div>\n";

        // writing to log file
        if ($ok) memberpages_LogEntry($_SESSION['username'] . ' changed his/her data');

    }

    // ============================== Members control panel entry form ==============================


        $o .= '<div class="membp_message" id="memberspanel">'
           . sprintf($tx['member_control_panel_greeting'],$_SESSION['username']);

    if ($answer) $o .= $answer;

        $o .= '<form action="" method="post">'
           . '<table class="membp_memberspanel">';

    if (!$ok) {
       $o .= '<tr><td>'
           . $tx['member_old_password']
           . ' </td><td>'
           . '<input type="text" name="oldpassword" value="'.$oldpassword.'">'
           . "</td></tr>\n"
           . '<tr><td>';


        $length = $pwlength
            ? sprintf($tx['member_new_password_length'], $pwlength)
            : '';
        $cap_lower = $pwextra ? $tx['member_new_password_cap_lower'] : '';
        $cipher = $pwextra == 2 ? $tx['member_new_password_cipher'] : '';
        $o .= sprintf($tx['member_new_password'], $length, $cap_lower, $cipher);

        $o .= ' </td><td>'
           . '<input type="text" name="newpassword" value="'.$newpassword.'">'
           . "</td></tr>\n"
           . '<tr><td>'
           . $tx['member_new_password2']
           . ' </td><td>'
           . '<input type="text" name="newpassword2" value="'.$newpassword2.'">'
           . "</td></tr>\n"
           . '<tr><td>'
           . $tx['member_new_email']
           . '<br>';


        $o .= $newemail
           ?  '</td><td>'
           .  '<input type="text" name="newemail" value="'.$newemail.'">'
           .  "</td></tr>\n"
           :  '</td><td>'
           .  '<input type="text" name="newemail" value="'.$member[$i]['email'].'">'
           .  '</td><tr>' . "\n";

        if ($plugin_cf['memberpages']['show_fullname']) {
            $o .= $newfullname
               ?  '<tr><td>' . $tx['notice_your_fullname_is'] . '</td><td>'
               .  '<input type="text" value="'.$newfullname.'" name="newfullname">'
               .  '</td><tr>'."\n"
               :  '<tr><td>' . $tx['notice_your_fullname_is'] . '</td><td>'
               .  '<input type="text" value="'.$member[$i]['full'].'" name="newfullname">'
               .  '</td><tr>' . "\n";
        }
    }

    $o .= $ok
       ?  '<tr><td colspan="2">' . '<br>'
       .  '<button type="submit" name="function" value="0">'.$tx['close'].'</button>'
       .  '</td></tr>' . "\n"
       :  '<tr><td>'
       .  '<button type="submit" name="function" value="0">'.$tx['close'].'</button>'
       .  '</td><td>'
       .  '<button type="submit" name="function" value="savepaneldata">'.$tx['save_settings'].'</button>'
       .  '</td></tr>' . "\n";

    $o .= '</table>' . "\n" . '</form>'. "\n" . '</div>';

    return $o;
}


function memberpages_SaveList($member)
{
    $file = ';<?php' . PHP_EOL . ';die();' . PHP_EOL . ';/*' . PHP_EOL;
    $i = 0;
    foreach ($member as $value) {
        $file .= "[$i]" . PHP_EOL
              .  'user = "'.   $value['user'].'"'. PHP_EOL
              .  'pass = "'.   $value['pass'].'"'. PHP_EOL
              .  'access = "'. $value['access'].'"'. PHP_EOL
              .  'email = "'.  $value['email'].'"'. PHP_EOL
              .  'full = "'.   $value['full'].'"'. PHP_EOL
              .  'expires = "'.$value['expires'].'"'. PHP_EOL
              .  'x = "'.      $value['x'].'"'. PHP_EOL;
        $i ++;
    }
    if (file_put_contents(MEMBERSFILEPATH.'membersfile.php',$file,LOCK_EX) !== false) {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(MEMBERSFILEPATH.'membersfile.php');
        }
        return true;
    }
    else return false;
}