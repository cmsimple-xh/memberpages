<?php
// Function check so that the plugin works with CMSimple 4.x
if (!function_exists('XH_message')) {
    function XH_message($type, $message) {
        $class = in_array($type, array('warning', 'fail'))
        ? 'cmsimplecore_warning'
        : '" style="background:#e1f8cb;color:#37620d;border:1px solid #c6d880;';
        return '<p class="' . $class . '">' . $message . '</p>';
    }
}

function memberpages_Date($timestamp)
{
	return $timestamp
            ? date("Y-m-d",(int)$timestamp)
            : '';
}

/**
 * Returns the editable log file
 *
 * @return string  The (X)HTML.
 */
function memberpages_Log()
{
    global $pth,$tx,$plugin,$plugin_tx,$plugin_cf;
    $o = '';

    if(isset($_POST['savelog']))  {
        if(file_put_contents(MEMBERSFILEPATH . 'log.txt', rtrim($_POST['logfile']) . PHP_EOL, LOCK_EX))
            $o .= XH_message('success',$plugin_tx['memberpages']['saving_ok']);
        else $o .= XH_message('fail',$tx['error']['cntsave']);
    }
    if(isset($_POST['reducelog']))  {
        $loglines = count(file(MEMBERSFILEPATH . 'log.txt'));
        $array = file(MEMBERSFILEPATH . 'log.txt');
        if($loglines>500) {
            array_splice($array,0,($loglines - 500));
            file_put_contents(MEMBERSFILEPATH . 'log.txt', $array, LOCK_EX);
        }
    }

    //======== logfile with save button ==============
    $logfile  = file_get_contents(MEMBERSFILEPATH . 'log.txt');
    $logsize  = filesize(MEMBERSFILEPATH . 'log.txt');
    $loglines = count(file(MEMBERSFILEPATH . 'log.txt'));

    $o .= '<p class="membp_pluginname2">Memberpages_XH ' . $plugin_cf['memberpages']['version'] . '</p>';
    $o .= '<form method="POST" action="">' . "\n"
       .  '<button type="text" name="savelog" value="1">'
       .  $plugin_tx['memberpages']['save_log'].'</button>'
       .  '<button type="text" name="reducelog" value="1">'
       .  $plugin_tx['memberpages']['reduce_log'].'</button> '
       .  '<span style="white-space:nowrap;">' . $logsize . ' Bytes, '
       .  $loglines . ' ' . $plugin_tx['memberpages']['log_entries'] . '</span>' . '<br>' . "\n"
       .  '<textarea  class="membp_log" name="logfile" id="logfile">'
       .  $logfile
       .  '</textarea>'
       .  '</form>' . "\n";

   $o .=  '<script type="text/javascript">
                        // <![CDATA[
            document.getElementById("logfile").scrollTop = 999999;
                       // ]]>
                        </script>';
    return $o;
}



/**
 *	Reads the changes to the members list and saves them,
 *	returns either an errormessage or goes back to members list
 */
function memberpages_ProcessList()
{
    global $pth,$plugin_cf,$plugin_tx;
    $o = '';

    $member = parse_ini_file(MEMBERSFILEPATH.'membersfile.php',true);


    $delete   = isset($_POST['delete'])   ? $_POST['delete']   : array();
    $add      = isset($_POST['add_x'])    ? $_POST['add_x']    : false;
    $user     = isset($_POST['user'])     ? $_POST['user']     : array();
    $pass     = isset($_POST['pass'])     ? $_POST['pass']     : array();
    $email    = isset($_POST['email'])    ? $_POST['email']    : array();
    $fullname = isset($_POST['fullname']) ? $_POST['fullname'] : array();
    $expires  = isset($_POST['expires'])  ? $_POST['expires']  : array();
    $sort     = isset($_POST['sort'])     ? $_POST['sort']     : false;
    $import   = isset($_POST['import'])   ? $_POST['import']   : false;
    $sendpw   = isset($_POST['sendpw'])   ? $_POST['sendpw']   : false;
    $delreq   = isset($_POST['delreq'])   ? $_POST['delreq']   : false;

    // $notice=print_r($user,true);

    if($plugin_cf['memberpages']['accessmode'] > 2) {
        $check1 = isset($_POST['check1']) ? $_POST['check1'] : array();
        $check2 = isset($_POST['check2']) ? $_POST['check2'] : array();
        $check3 = isset($_POST['check3']) ? $_POST['check3'] : array();
        $check4 = isset($_POST['check4']) ? $_POST['check4'] : array();
        $check5 = isset($_POST['check5']) ? $_POST['check5'] : array();

        foreach ($user as $i=>$value) {
            $check1[$i] = isset($check1[$i])? $check1[$i] : '0';
            $check2[$i] = isset($check2[$i])? $check2[$i] : '0';
            $check3[$i] = isset($check3[$i])? $check3[$i] : '0';
            $check4[$i] = isset($check4[$i])? $check4[$i] : '0';
            $check5[$i] = isset($check5[$i])? $check5[$i] : '0';
            $access[$i] = bindec($check5[$i] . $check4[$i] . $check3[$i] . $check2[$i] . $check1[$i]);
        }
    } else {
        $access = isset($_POST['access']) ? $_POST['access'] : array();
    }

    foreach ($member as $key=>$row) {
        $username[$key] = $row['user'];
    }

    // change sent values
    foreach ($user as $key=>$value) {
        $user[$key] = stsl(str_replace(array('"','\\'),'',$user[$key]));
        // the $username[$key] has to be eliminated from the username array, 
        // to prevent detection of false doubles
        unset($username[$key]);
        if(in_array($user[$key],$username)) $user[$key] .= '_DOUBLE';
        $username[$key]=$user[$key];
        $member[$key]['user'] = $user[$key];
        $member[$key]['access'] = $access[$key];
        $member[$key]['pass'] = stsl(str_replace(array('"','\\'),'',$pass[$key]));
        $member[$key]['email']= stsl(str_replace(array('"','\\'),'',$email[$key]));
        $member[$key]['full'] = stsl(str_replace(array('"','\\'),'',$fullname[$key]));

        if (preg_match('/^\d{2,4}\-\d{1,2}\-\d{1,2}$/',trim($expires[$key]))) {
            list($year,$month,$day) = explode('-',$expires[$key]);
        } else if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{2,4}$/',trim($expires[$key]))) {
            list($day,$month,$year) = explode('.',$expires[$key]);
        } else $day = $month = $year = '';

        $member[$key]['expires'] = mktime(0,0,0,(int)$month,(int)$day,(int)$year);
    }


    if($import || $delreq || $sendpw) {
        $new = parse_ini_file(MEMBERSFILEPATH.'new.php',true);
    }

    if($delreq) {
        $delreq = memberpages_ReformatNewReqIndex($delreq);
        unset($new[$delreq]);
        memberpages_SaveNewRequests($new);
    }

    // has to be repeated, as a new username may have been added
    foreach ($member as $key=>$row) {
        $username[$key] = $row['user'];
    }

    if($import) {
        $import = memberpages_ReformatNewReqIndex($import);
        $count = count($member);
        if(in_array($new[$import]['name'],$username)) $new[$import]['name'] .= '_DOUBLE';
        $member[$count]['user'] = $new[$import]['name'];
        $member[$count]['access'] = '';
        $member[$count]['pass'] = memberpages_NewPW();
        $member[$count]['email'] = $new[$import]['email'];
        $member[$count]['full'] = $new[$import]['fullname'];
        $member[$count]['expires'] = '';
        $member[$count]['x'] = '';
    }


    if($add) {
        array_unshift($member,array(
            'user'=>'',
            'access'=>'',
            'pass'=>memberpages_NewPW(),
            'email'=>'',
            'full'=>'',
            'expires'=>'',
            'x'=>''));
    }

    if($delete) {
        foreach ($delete as $key=>$value) {
            unset($member[$key]);
            break;
        }
    }

    if($sort){
        foreach ($member as $key=>$row) {
            $username[$key] = $row['user'];
        }
        array_multisort($username, SORT_ASC, $member);
    }

    if(memberpages_SaveList($member)) {
        if(!$import && !$delreq) setcookie('savingReport',1,time()+2);
    } else setcookie('savingReport',-1,time()+2);


    if($sendpw) {
        $sendpw = memberpages_ReformatNewReqIndex($sendpw);
        $o .= memberpages_EmailToNewMember($new[$sendpw]['name']);
    }

    if(!$o) {
        header('Location: '.CMSIMPLE_URL.'?&memberpages&admin=plugin_main');
        exit;
    }

    return '<div class="membp_message" style="line-height:2.4;">' . $o . '</div>';
}



function memberpages_EmailToNewMember($user)
{
	global $pth,$plugin_tx,$plugin_cf,$tx;
    $o = $to = $password = $expiredate = $text = '';
    $ok = true;

    $member = parse_ini_file(MEMBERSFILEPATH.'membersfile.php',true);
    foreach ($member as $key=>$row) {
        $username[$key] = $row['user'];
    }
    $i = array_search($user,$username,true);

    if($i === false) return $plugin_tx['memberpages']['error_rec_not_found'];
    if($member[$i]['email']) {
        $to .= $member[$i]['email'];
    } else {
        $ok = false;
        $o .= $plugin_tx['memberpages']['error_rec_no_email'] . '<br>';
    }
    if($member[$i]['pass']) {
        $password .= $member[$i]['pass'];
    } else {
        $o .= $plugin_tx['memberpages']['error_rec_no_password'] . '<br>';
        $ok = false;
    }
    $text .= $plugin_cf['memberpages']['controlpanel']
        ? $plugin_tx['memberpages']['register_pw_change'] . "\n"
        : '';
    if($member[$i]['expires']) {
        if(trim($member[$i]['expires']) > time()) {
            $text .= sprintf($plugin_tx['memberpages']['register_mail_membership_expires'],
                memberpages_Date($member[$i]['expires']))
               .  "\n";
        } else {
            $o .= sprintf($plugin_tx['memberpages']['error_rec_expired'],$user,
                memberpages_Date($member[$i]['expires'])) . '<br>';
            $ok = false;
        }
    }
    $text .= "\n" . $plugin_tx['memberpages']['email_goodbye'];

    $name = $member[$i]['full']? $member[$i]['full']:$user;
    $domain = str_replace(array('http://','https://','www.'),'',CMSIMPLE_URL);
    $subject = sprintf($plugin_tx['memberpages']['register_mail_subject'],$domain);
    $message = sprintf($plugin_tx['memberpages']['register_mail_message'],
        $name,$domain,$user,$password,$text);

    if($ok) {
        $o .= '<form action="" method="POST">'
            . '<div><b>To:</b> ' . $to . '<br>'
            . '<b>Subject:</b> ' . $subject . '<br>'
            . '<b>Message:</b>'
            . '<textarea name="message" rows="17">'
            .  $message
            . '</textarea></div>'
            .  '<input type="submit" class="submit" name="sendPwToNewMember" style="width:auto;" value=" &nbsp; '
            .  $tx['mailform']['sendbutton'] . ' &nbsp; ">'
            . '<input type="hidden" name="subject" value="'.$subject.'">'
            . '<input type="hidden" name="to" value="'.$to.'">'
            . '</form>';
    }
    return $o;
}

function memberpages_SendEmailToNewMember()
{
    global $plugin_tx;

    If(memberpages_Mail($_POST['to'], $_POST['subject'], $_POST['message'])) {
        return XH_message('success',$plugin_tx['memberpages']['register_mail_sent'])
            . memberpages_ShowMembersList();
    } else return XH_message('fail',$plugin_tx['memberpages']['register_mail_failed'])
        . memberpages_ShowMembersList();
}




/**
 * Creates a new password according to settings in plugin config
 *
 */
function memberpages_NewPW()
{
	global $plugin_cf;
    $newpassword = $plugin_cf['memberpages']['default_password'];

    if($plugin_cf['memberpages']['generate_password'] > 1) {
        $newpassword = substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz!?&+@"),1,
        ($plugin_cf['memberpages']['generate_password'] + 2));
    }
    if($plugin_cf['memberpages']['generate_password']) {
        $tmp = preg_split("//u", $newpassword, -1, PREG_SPLIT_NO_EMPTY);
        shuffle($tmp);
        $newpassword = join("", $tmp);
        $newpassword = preg_replace_callback(
            '/[a-z|äöü]/i',
            function ($m) {
                return rand(0,1) ? $m[0] ^ str_pad("", strlen($m[0]), " ") : $m[0];
            },
            $newpassword
        );
    }

    return $newpassword;
}


/**
 * Puts the index of register requests back into it original form so it can be used as index
 *
 */
function memberpages_ReformatNewReqIndex($index)
{
    $index = str_split($index);
    return $index[0].$index[1].$index[2].$index[3].'-'.$index[4].$index[5].'-'.$index[6]
          .$index[7].' '.$index[8].$index[9].':'.$index[10].$index[11].':'.$index[12].$index[13];
}

/**
 * saves the array of new register requests in an ini-type php file
 *
 */
function memberpages_SaveNewRequests($array)
{
    $file = ';<?php' . PHP_EOL . ';die();' . PHP_EOL . ';/*' . PHP_EOL;
    foreach ($array as $key=>$value) {
        $file .= "[$key]" . PHP_EOL;
        foreach ($array[$key] as $k=>$v) {
            $file .= $k . ' = "' . $v . '"' . PHP_EOL;
        }
    }
    file_put_contents(MEMBERSFILEPATH.'new.php',$file);
        if (function_exists('opcache_invalidate')) {
        opcache_invalidate(MEMBERSFILEPATH.'new.php');
    }

}


function memberpages_SavingReport()
{
    global $bjs,$tx,$plugin_tx;
  
    if(isset($_COOKIE['savingReport'])) {
        if($_COOKIE['savingReport'] == 1) {
        $bjs .= '<script type="text/javascript">'."\n"
              . 'document.getElementById(\'membp_saved\').style.visibility = \'visible\';'
              . 'document.getElementById(\'membp_saved2\').style.visibility = \'visible\';'
              . 'document.getElementById(\'membp_saved\').innerHTML = \''.$plugin_tx['memberpages']['saving_ok'].'\';'
              . 'document.getElementById(\'membp_saved2\').innerHTML = \''.$plugin_tx['memberpages']['saving_ok'].'\';'
              . 'setTimeout(function(){document.getElementById(\'membp_saved\').style.visibility = \'hidden\'},2500);'
              . 'setTimeout(function(){document.getElementById(\'membp_saved2\').style.visibility = \'hidden\'},2500);'
              . '</script>'."\n";
        } elseif($_COOKIE['savingReport'] == -1) {
        $bjs .= '<script type="text/javascript">'."\n"
              . 'document.getElementById(\'membp_saved\').style.visibility = \'visible\';'
              . 'document.getElementById(\'membp_saved2\').style.visibility = \'visible\';'
              . 'document.getElementById(\'membp_saved\').innerHTML = \''.$tx['error']['cntsave'].'\';'
              . 'document.getElementById(\'membp_saved2\').innerHTML = \''.$tx['error']['cntsave'].'\';'
              . 'setTimeout(function(){document.getElementById(\'membp_saved\').style.visibility = \'hidden\'},2500);'
              . 'setTimeout(function(){document.getElementById(\'membp_saved2\').style.visibility = \'hidden\'},2500);'
              . '</script>'."\n";
        }
    }
}


function memberpages_ShowMembersList()
{

    global $plugin_tx,$tx,$plugin_cf,$pth,$tx,$sn,$memberpageimages,$hjs,$bjs;
    memberpages_CheckFiles();
    $o ='';
    $member = parse_ini_file(MEMBERSFILEPATH.'membersfile.php',true);

    $password = array();
    $double = false;
    foreach ($member as $key=>$row) {
        if(strpos($row['user'],'_DOUBLE')) $double=true;
        $password[$key] = $row['pass'];
    }
    if($double) {
        $o .= XH_message('warning',$plugin_tx['memberpages']['error_username_not_unique']);
    }
    // check missing passwords
    if(in_array('',$password)) $o .= XH_message('warning',$plugin_tx['memberpages']['error_missing_password']);



    // go back to the last scroll position
    If(isset($_COOKIE['scrollpos'])) {
        $bjs .= '<script type="text/javascript">'."\n"
              . 'window.scrollTo(0,'.($_COOKIE['scrollpos']).');'
              . '</script>'."\n";
    } 
    // show success of saving list
    memberpages_SavingReport();

    // only changed data is transmitted to prevent transmitting
    // the whole file and risking data loss when member file is large
    $hjs .= '<script type="text/javascript">'."\n"
     .'/* <![CDATA[ */'."\n"
     .'function keepSrollPos() {
        document.cookie = "scrollpos=" + document.documentElement.scrollTop + "; max-age=2";
     }

     function makeName(j) {
              document.getElementById("line"+j).className = "membp_changes";
              var pass = "pass["+j+"]";
              document.getElementById("pass"+j).name = pass;
              var user = "user["+j+"]";
              document.getElementById("user"+j).name = user;';
    if($plugin_cf['memberpages']['register_email']) {
        $hjs .= 'var email = "email["+j+"]";
              document.getElementById("email"+j).name = email;';
    }
    if($plugin_cf['memberpages']['show_fullname']) {
        $hjs .= 'var fullname = "fullname["+j+"]";
              document.getElementById("fullname"+j).name = fullname;';
    }
    if($plugin_cf['memberpages']['show_expires']) {
        $hjs .= 'var expires = "expires["+j+"]";
              document.getElementById("expires"+j).name = expires;';
    }

    if($plugin_cf['memberpages']['accessmode'] > 2) {
        $hjs .= "\n"
             .'
              var check1 = "check1["+j+"]";
              document.getElementById("check1a"+j).name = check1;

              var check2 = "check2["+j+"]";
              document.getElementById("check2a"+j).name = check2;

              var check3 = "check3["+j+"]";
              document.getElementById("check3a"+j).name = check3;

              var check4 = "check4["+j+"]";
              document.getElementById("check4a"+j).name = check4;

              var check5 = "check5["+j+"]";
              document.getElementById("check5a"+j).name = check5;';
    } else {
        $hjs .= 'var access = "access["+j+"]";
              document.getElementById("access"+j).name = access;';
    }
    $hjs .= '}'."\n"
      .  '/* ]]> */'."\n"
      .  '</script>'."\n";

    $o .= '<p class="membp_pluginname">Memberpages_XH ' . $plugin_cf['memberpages']['version'] . '</p>';
    $o .= '<form method="POST" action="'.$sn.'?&memberpages" class="membp_form">'."\n";

    // headline
    $o .=  '<h1>' . count($member) . ' ' . $plugin_tx['memberpages']['menu_main'] .' '
        .  '<input type="submit" onClick="keepSrollPos();" style="display:inline-block;" value=" '
        .  $plugin_tx['memberpages']['save']
        .  ' " name="send">'
        .  '<span id="membp_saved"></span>'
        .  '</h1>'. "\n";

    // start table + input form
    $o .= '<table class="membp_configtable" cellpadding="1" cellspacing="3">' . "\n";


    // table headline
    $o .= '<tr><th>'
       .  $plugin_tx['memberpages']['memberslist_username']
       .  '</th><th>'
       .  $plugin_tx['memberpages']['memberslist_password']
       .  '</th>' . "\n";
    if($plugin_cf['memberpages']['accessmode']) {
        $o .= '<th>' . $plugin_tx['memberpages']['memberslist_accesslevel'];
        if($plugin_cf['memberpages']['accessmode'] > 2) $o .= ' 1-'.$plugin_cf['memberpages']['accessmode'];
        $o .= '</th>' . "\n";
    }

    if($plugin_cf['memberpages']['register_email'] || $plugin_cf['memberpages']['show_expires']) {
        $o .= '<th style="width:40%; text-align:center;">';
        if($plugin_cf['memberpages']['register_email']) $o .= $plugin_tx['memberpages']['memberslist_email'];
        $o .= '</th>' . "\n";
    }

    $o .= '<td>'
       .  '<input type="image" onClick="keepSrollPos();" src="'.$memberpageimages
       .  '/add.png" style="width:16;height:16" name="add" value="add" alt="Add entry">'
       .  "</td></tr>\n";

    foreach($member as $j=>$i)
    {
        // start a new line for every member
        $o .= '<tr id="line'.$j
           . '" OnKeyDown="'
           . 'makeName('.$j.');'
           . 'document.getElementById(\'check1a'.$j.'\').style.background = \'#efe\';'
           . '">'."\n"
           . '<td colspan="2"><span style="white-space:nowrap;">';

        //name
        $class1 = strpos($member[$j]['user'],'_DOUBLE') !==false ? ' class="membp_warning"':'';
        $o .= "\n" . '<input type="text" style="width:48%;margin-right:1%;" '
            . $class1 .' value="'.$member[$j]['user'].'" id="user'.$j.'">';

        //password
        $class2 = $member[$j]['pass'] ? '' : ' class="membp_warning"';
        $type = $member[$j]['x']? 'type="password" readonly="readonly" class="membp_readonly"':'type="text"';
        $o .=  "\n" . '<input '.$type.' style="width:48%;" '.$class2.' value="'
           .  $member[$j]['pass'].'" id="pass'.$j.'">' . '</span>';

        if($plugin_cf['memberpages']['show_fullname']) {
            $o .=  '<br>'
                .  "\n" . '<input type="text" style="width:calc(97% + 5px)" value="'
                .  $member[$j]['full'].'" id="fullname'.$j.'"  placeholder="'
                .  $plugin_tx['memberpages']['memberslist_fullname'] . '">';
        } else {
            $o .=  "\n" . '<input type="hidden" value="'.$member[$j]['full'].'" id="fullname'.$j.'">';
        }
        $o .= '</td>' . "\n";

        // if accessmode is wanted start accessmode field
        if($plugin_cf['memberpages']['accessmode']) {
            $o .= '<td style="text-align:center;" OnClick="makeName('.$j.')">'
               .  memberpages_AccessSelect($j,$member[$j]['access']);

        } else {
            // if accessmode is not wanted save at least possible values for later re-use
            $o .= "\n" . '<input type="hidden" value='.$member[$j]['access'].' id="access'.$j.'">';
        }

        if($plugin_cf['memberpages']['register_email'] || $plugin_cf['memberpages']['show_expires']) {
            $o .= '<td>';
            if($plugin_cf['memberpages']['register_email']) {
                $o .= "\n"
                   .  '<input type="text" style="width: 96%;" value="'.$member[$j]['email'].'" id="email'.$j.'">';
            }
            if($plugin_cf['memberpages']['show_expires']) {
                $style = $member[$j]['expires'] && $member[$j]['expires'] < time()
                    ? 'background:#ffc;' : '';
                $o .= '<span style="float:right;">'
                   .  $plugin_tx['memberpages']['memberslist_expires']
                   .  ' '
                   .  '<input type="text" style="width:60%;min-width:10ch;' . $style . '"
                      value="' . memberpages_Date($member[$j]['expires']) . '" id="expires' . $j . '" placeholder="'
                   .  $plugin_tx['memberpages']['memberslist_date_format'] . '">'
                   .  '</span>';
            } else {
                $o .=  '<input type="hidden" value="'.memberpages_Date($member[$j]['expires']).'" id="expires'.$j.'">';
            }
            $o .= '</td>' . "\n";
        }

        // delete button
        $o .= '<td>' . '<input type="image" onClick="keepSrollPos();" src="' . $memberpageimages
           .  '/delete.png" style="width:16;height:16" name="delete['.$j.']" value="delete" alt="Delete entry">';

        $o .=  "</td></tr>\n";

    }

    $o .= '</table>' . "\n"
       .  '<input type="hidden" value="memberslist" name="action">'
       .  '<input type="submit" onClick="keepSrollPos();" value=" &nbsp; '
       .  $plugin_tx['memberpages']['save'].' &nbsp; " name="send">'
       .  '<input type="submit" onClick="keepSrollPos();" value=" &nbsp; '
       .  $plugin_tx['memberpages']['memberslist_sort'].' &nbsp; " name="sort">'
       .  '<span id="membp_saved2"></span>';

    if(is_file(MEMBERSFILEPATH.'new.php') &&
        filesize(MEMBERSFILEPATH.'new.php') > 22) {
        $o .= '<br>' .'<h5>' . $plugin_tx['memberpages']['new_requests'] . '</h5>';
        $new = parse_ini_file(MEMBERSFILEPATH.'new.php',true);
        foreach ($member as $key=>$row) {
            $username[$key] = $row['user'];
        }

        foreach ($new as $key=>$value) {
            // cleaning the key so that it can be used as $_POST value
            $import = str_replace(array('-',':',' '),'',$key);

            // checking if the name has already been imported
            $style = in_array($new[$key]['name'],$username)? ' style="border-style:inset;background:#def;"' : '';

            $o .=  '<b>' . $key . '</b> '
               .  '<button type="submit" onClick="keepSrollPos();"'.$style.' name="import" value="'
               .  $import.'" >'. $plugin_tx['memberpages']['new_import']
               .  '</button>'
               .  '<button type="submit" onClick="keepSrollPos();" name="sendpw" value="'
               .  $import.'" >'. $plugin_tx['memberpages']['new_sendpassword']
               .  '</button>'
               .  '<button type="submit" onClick="keepSrollPos();" name="delreq" value="'
               .  $import.'" >'. $plugin_tx['memberpages']['new_delete']
               .  '</button>'
               .  '<br>';
            foreach ( $value as $key2=>$value2) {
                if($value2) $o .= $key2 . ' = ' . $value2 . '<br>';
            }
        }
    }
    $o .= '</form>' . "\n";

    return $o;
}



function memberpages_ReceiveConfig($value,$preset='',$tx=false)
{
    global $pth, $sl,$plugin_cf,$plugin_tx;

    $var = $tx ? "plugin_tx" : "plugin_cf";
    return isset($_POST[$value])
           ? $_POST[$value]
           : (isset(${$var}['memberpages'][$value]) &&
              ${$var}['memberpages'][$value] !== ''
           ? ${$var}['memberpages'][$value]
           : $preset );
}


function memberpages_SaveConfig()
{
    global $plugin_cf,$plugin_tx,$tx,$pth,$sl,$pd_router,$edit,$bjs;
    $t = array('error'=>'','success'=>'','cntsave'=>'');

    // go back to the last scroll position
    If(isset($_COOKIE['scrollpos'])) {
        $bjs .= '<script type="text/javascript">'."\n"
              . 'window.scrollTo(0,'.($_COOKIE['scrollpos']).');'
              . '</script>'."\n";
    }


    //config data
    $cf = array();
    $cf['phpfile']                = memberpages_ReceiveConfig('phpfile');
    $cf['csvfile']                = memberpages_ReceiveConfig('csvfile');
    $cf['usecsvfile']             = memberpages_ReceiveConfig('usecsvfile');
    $cf['membersfilepath']        = memberpages_ReceiveConfig('membersfilepath');
    $cf['pdtab']                  = memberpages_ReceiveConfig('pdtab',1);
    $cf['texttrigger']            = memberpages_ReceiveConfig('texttrigger',0);
    $cf['onlogin_membersnotice']  = memberpages_ReceiveConfig('onlogin_membersnotice',0);
    $cf['small_login']            = memberpages_ReceiveConfig('small_login');
    $cf['rememberme']             = memberpages_ReceiveConfig('rememberme',1);
    $cf['controlpanel']           = memberpages_ReceiveConfig('controlpanel',1);
    $cf['register_email']         = memberpages_ReceiveConfig('register_email',1);
    $cf['show_actives']           = memberpages_ReceiveConfig('show_actives',1);
    $cf['show_fullname']          = memberpages_ReceiveConfig('show_fullname',1);
    $cf['show_expires']           = memberpages_ReceiveConfig('show_expires');
    $cf['registerme']             = memberpages_ReceiveConfig('registerme',1);
    $cf['passwordforgotten']      = memberpages_ReceiveConfig('passwordforgotten',1);
    $cf['site_email']             = memberpages_ReceiveConfig('site_email');
    $cf['admin_email']            = memberpages_ReceiveConfig('admin_email');
    $cf['warning_time']           = memberpages_ReceiveConfig('warning_time',14);
    $cf['default_password']       = memberpages_ReceiveConfig('default_password','wxyz');
    $cf['generate_password']      = memberpages_ReceiveConfig('generate_password',2);
    $cf['passwordbyuser']         = memberpages_ReceiveConfig('passwordbyuser',5.1);
    $cf['accessmode']             = memberpages_ReceiveConfig('accessmode',1);
    $cf['selectable_levels']      = memberpages_ReceiveConfig('selectable_levels',5);
    $cf['VIP_level']              = memberpages_ReceiveConfig('VIP_level');
    $cf['color_pagedata_tab']     = memberpages_ReceiveConfig('color_pagedata_tab','Blue');

    //language data
    $page_on_login          = memberpages_ReceiveConfig('page_on_login','',true);
    $hide_on_login          = memberpages_ReceiveConfig('hide_on_login','',true);
    $page_on_VIP_login      = memberpages_ReceiveConfig('page_on_VIP_login','',true);
    $page_on_logout         = memberpages_ReceiveConfig('page_on_logout','',true);
    $page_on_expired        = memberpages_ReceiveConfig('page_on_expired','',true);
    $warning_text           = memberpages_ReceiveConfig('warning_text','',true);
    $email_goodbye          = memberpages_ReceiveConfig('email_goodbye','',true);

    $languagefile = file_get_contents($pth['folder']['plugins'] . "memberpages/languages/$sl.php");
    if(!$languagefile) $languagefile ="<?php\n?>";

    // create language file entries if not there
    foreach (array(
        'page_on_login',
        'hide_on_login',
        'page_on_VIP_login',
        'page_on_logout',
        'page_on_expired',
        'warning_text',
        'email_goodbye'
        ) as $key=>$value) {
        $languagefile = !preg_match('!'.$value.'\'\]!' ,$languagefile)
                      ? str_replace('?>',"\t".'$plugin_tx[\'memberpages\'][\''.$value.'\']="";'."\n?>" ,$languagefile)
                      : $languagefile;
    }

    $pattern = array(
        '!page_on_login\'\]="(.*)";!',
        '!hide_on_login\'\]="(.*)";!',
        '!page_on_VIP_login\'\]="(.*)";!',
        '!page_on_logout\'\]="(.*)";!',
        '!page_on_expired\'\]="(.*)";!',
        '!warning_text\'\]="([^"]*)";!s',
        '!email_goodbye\'\]="([^"]*)";!s'
        );
    $replacement = array(
        "page_on_login']=\"$page_on_login\";",
        "hide_on_login']=\"$hide_on_login\";",
        "page_on_VIP_login']=\"$page_on_VIP_login\";",
        "page_on_logout']=\"$page_on_logout\";",
        "page_on_expired']=\"$page_on_expired\";",
        "warning_text']=\"$warning_text\";",
        "email_goodbye']=\"$email_goodbye\";"
        );
    $languagefile = preg_replace($pattern,$replacement,$languagefile);

    if(!file_put_contents($pth['folder']['plugins'] . "memberpages/languages/$sl.php",$languagefile,LOCK_EX))
        $t['cntsave'] = $tx['editmenu']['language'];
    if(!file_put_contents($pth['folder']['plugins'] . "memberpages/languages/backup_$sl.php",$languagefile))
        $t['cntsave'] = $tx['editmenu']['language'].' '.$tx['editmenu']['backups'];


    // if method "#CMSimple member();#" was active and has been unchecked in plugin admin,
    // this checks if the call is still in the content.htm
    if(!$cf['texttrigger'] && $plugin_cf['memberpages']['texttrigger']) {
        $content = file_get_contents($pth['file']['content']);
        if(strpos($content,'member(')!==false) {
            $t['error'] = true;
            $cf['texttrigger'] = '1';
        }
    }

    // if pagedata taps were active and have been made inactive, the pagedata.php has to be cleaned
    if(!$cf['pdtab'] && $plugin_cf['memberpages']['pdtab']) {

        $edit = true;

        $key  = array_search('mpage', $pd_router->model->params);
        $key2 = array_search('mplevel', $pd_router->model->params);
        if ($key  !== FALSE) {unset($pd_router->model->params[$key]);}
        if ($key2 !== FALSE) {unset($pd_router->model->params[$key2]);}
        for ($i = 0; $i < count($pd_router->model->data); $i++) {
        unset($pd_router->model->data[$i]['mpage']);
        unset($pd_router->model->data[$i]['mplevel']);
        }
        unset($pd_router->model->temp_data['mpage']);
        unset($pd_router->model->temp_data['mplevel']);
        $pd_router->model->save();
    }

    $text = "<?php\n\n"
          . '$plugin_cf[\'memberpages\'][\'version\']="'. MEMBERPAGES_VERSION .'";'."\n";
    foreach ($cf as $key=>$value) {
        $text .= '$plugin_cf[\'memberpages\'][\''.$key.'\']="'.$value. '";' . "\n";
    }
    $text .=  "\n?>";
    if(file_put_contents($pth['folder']['plugins'] . "memberpages/config/config.php",$text,LOCK_EX))
        $t['success'] = true;
        else $t['cntsave'] .= $t['cntsave']
            ? ', ' . $plugin_tx['memberpages']['menu_config']
            : $plugin_tx['memberpages']['menu_config'];

    return $t;
}


function memberpages_ConfigCheckBox($cf_name, $tx_name, $js='', $css='')
{
    global $plugin_cf, $plugin_tx;

    ${$cf_name.'checked'} = $plugin_cf['memberpages'][$cf_name] ? 'checked="checked"' : '';

    return '<p id="p_'.$cf_name.'" '.$css.'>'
            .  '<input type="hidden" name="'.$cf_name.'" value="0">'
            .  '<input type="checkbox" '.${$cf_name.'checked'}.' value="1" id="'
            .  $cf_name.'" name="'.$cf_name.'" '.$js.'>'
            .  ' '
            .  $plugin_tx['memberpages'][$tx_name]
            .  '</p>' . "\n";
}

function memberpages_Config()
{

    global $plugin_tx, $plugin_cf,$sn,$cl,$admin,$l,$u,$hm,$hjs,$h;
    $o ='';

    if($plugin_cf['memberpages']['passwordforgotten']
        && !($plugin_cf['memberpages']['site_email'] && $plugin_cf['memberpages']['admin_email'])) {
        $o .= '<p class="cmsimplecore_warning">'.$plugin_tx['memberpages']['error_site_email'].'</p>';
    }
    $hjs .= '<script type="text/javascript">'."\n"
     .'/* <![CDATA[ */'."\n"
     .'function keepSrollPos() {document.cookie = "scrollpos=" + document.documentElement.scrollTop + "; max-age=2";}'
     ."\n" . '/* ]]> */'."\n" . '</script>' . "\n";

    // headline log/config
    $o .= '<p class="membp_pluginname">Memberpages_XH ' . $plugin_cf['memberpages']['version'] . '</p>';


    //config settings form
    $o .= '<form action="'.$sn.'?&memberpages&admin=plugin_config&action=plugin_edit" class="membp_form" method="POST">'
       .  '<h1>'.$plugin_tx['memberpages']['menu_config_headline']
       .  ' '
       .  '<input type="submit" onClick="keepSrollPos();" name="save_memberpagesconfig" value=" &nbsp; '
       .  $plugin_tx['memberpages']['save_settings'].' &nbsp; ">'
       .  '<span id="membp_saved">'.$plugin_tx['memberpages']['saving_ok'].'</span>'
       .  '</h1>'
       .  "\n";


    $o .= '<h5>' . $plugin_tx['memberpages']['config_how_memberpages'] . '</h5>';

    $o .= memberpages_ConfigCheckBox('pdtab', 'memberpages_via_pdtab', 'OnClick="
                if(this.checked) {
                    document.getElementById(\'pdtab_color_select\').style.display = \'table-row\';
                } else {
                    document.getElementById(\'pdtab_color_select\').style.display = \'none\';
                } ; "');

    // color for M-tab in pagedata tabs if page is memberpage
    $o .= '<table class="membp_config membp_admin">' . "\n";
    $pdtab_color_select = ($plugin_cf['memberpages']['pdtab'])
                        ? 'style="display:table-row;"':'style="display:none;"';


    $o .= '<tr id="pdtab_color_select"  '. $pdtab_color_select.'><td>'
       .  $plugin_tx['memberpages']['config_color_m_tab'] . '</td><td>' . "\n";
    $select = '';
    $x = 0;
    foreach (array('Blue','Green','Magenta','Purple','Red','DeepPink','DarkOrange','Yellow','White') as $key=>$value) {
        $selected = '';
        if($plugin_cf['memberpages']['color_pagedata_tab'] == $value) {$selected = ' selected';$x++;}
        $select .= "\n".'<option value="' . $value . '"' . $selected.'>'. $value.'</option>';
    }
    if(!$x) $select = "\n".'<option value="'
                    . $plugin_cf['memberpages']['color_pagedata_tab']
                    . '" selected>'
                    . $plugin_cf['memberpages']['color_pagedata_tab']
                    . '</option>'
                    . $select;

    $o .= '<select name="color_pagedata_tab">'
       .  "\n" . $select . "\n</select>";

    $o .= '</td></tr></table>' . "\n";

    $o .= memberpages_ConfigCheckBox('texttrigger','memberpages_via_texttrigger');


    $o  .= '<hr><h5>' . $plugin_tx['memberpages']['config_login_form'] . '</h5>';

    $o .= memberpages_ConfigCheckBox('small_login','config_small_login');
    $o .= memberpages_ConfigCheckBox('passwordforgotten','passwordforgotten_option','OnClick="
                if(this.checked || document.getElementById(\'registerme\').checked) {
                    document.getElementById(\'emaildetails\').style.display = \'table-row-group\';
                } else if(!(this.checked && document.getElementById(\'registerme\').checked)) {
                    document.getElementById(\'emaildetails\').style.display = \'none\';
                } ; "');
    $o .= memberpages_ConfigCheckBox('registerme','config_show_register_me','OnClick="
                if(this.checked || document.getElementById(\'passwordforgotten\').checked) {
                    document.getElementById(\'emaildetails\').style.display = \'table-row-group\';
                } else if(!(this.checked && document.getElementById(\'passwordforgotten\').checked)) {
                    document.getElementById(\'emaildetails\').style.display = \'none\';
                } ; "');



    $o  .= '<hr><h5>' . $plugin_tx['memberpages']['config_how_loginout'] . '</h5>';

    $o .= '<table class="membp_config membp_admin">' . "\n";

    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_on_login'] . '</td><td>' . "\n";
    $o .= '<select name="page_on_login">'
       .  "\n" . '<option value="">' . $plugin_tx['memberpages']['config_notice_above_loginfield'] . '</option>' . "\n"
       .  "\n" . memberpages_SelectPage($plugin_tx['memberpages']['page_on_login'],
                 $plugin_tx['memberpages']['config_use_special_page']) . "\n</select><br>\n";

    $o .= '<select name="hide_on_login">'
       .  "\n" . '<option value="">' . $plugin_tx['memberpages']['config_on_login_dont_hide_page'] . '</option>' . "\n"
       .  "\n" . memberpages_SelectPage($plugin_tx['memberpages']['hide_on_login'],
                 $plugin_tx['memberpages']['config_on_login_hide_page']) . "\n</select><br>\n";


    $o .= '<select name="onlogin_membersnotice">'
       .  "\n" . '<option value="">' . $plugin_tx['memberpages']['config_on_login_no_membersnotice']
       . '</option>' . "\n"
       .  "\n" . '<option value="1"';
    if($plugin_cf['memberpages']['onlogin_membersnotice']=="1") $o .=' selected';
    $o .= '>' . $plugin_tx['memberpages']['config_on_login_all_membersnotice'] . "</option>\n"
       . '</option>' . "\n"
       .  "\n" . '<option value="2"';
    if($plugin_cf['memberpages']['onlogin_membersnotice']=="2") $o .=' selected';
    $o .= '>' . $plugin_tx['memberpages']['config_on_login_memberp_membersnotice'] . "</option>\n"
       . "\n</select>";

    $o .= '</td></tr>';

    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_VIP_login_level'] . '</td><td>' . "\n"
       .  '<input type="text" name="VIP_level" style="width:5ex;text-align:center;" value="'
       .  $plugin_cf['memberpages']['VIP_level'] . '" OnChange="
               if(this.value > 0) {
                   document.getElementById(\'VIP_longin\').style.display = \'table-row\';
               } else {
                   document.getElementById(\'VIP_longin\').style.display = \'none\';
               };
          ">'
       .  $plugin_tx['memberpages']['VIP_login_activation_level']
       .  '</td></tr>' . "\n";

    $o .= '<tr id="VIP_longin"';
    if (!$plugin_cf['memberpages']['VIP_level']) $o .= ' style="display:none;"';
    $o .= '><td>'
       .  $plugin_tx['memberpages']['config_on_VIP_login'] . '</td><td>' . "\n";

    $o .= '<select name="page_on_VIP_login">'
       .  "\n" . '<option value="">' . $plugin_tx['memberpages']['config_notice_above_loginfield'] . '</option>'
       .  "\n" . memberpages_SelectPage($plugin_tx['memberpages']['page_on_VIP_login'],
                $plugin_tx['memberpages']['config_use_special_page']) . "\n</select>";


    $o .= '</td></tr><tr><td>'

       .  $plugin_tx['memberpages']['config_on_logout'] . '</td><td>' . "\n";
    $o .= '<select name="page_on_logout">'
       .  "\n" . '<option value="">' . $plugin_tx['memberpages']['config_no_special_page'] . '</option>'
       .  "\n" . memberpages_SelectPage($plugin_tx['memberpages']['page_on_logout'],
                $plugin_tx['memberpages']['config_use_special_page']) . "\n</select>";

    $o .= '</td></tr><tr><td>'

       .  $plugin_tx['memberpages']['config_on_expired'] . '</td><td>' . "\n";
    $o .= '<select name="page_on_expired">'
       .  "\n" . '<option value="">' . $plugin_tx['memberpages']['config_no_special_page'] . '</option>' . "\n"
       .  "\n" . memberpages_SelectPage($plugin_tx['memberpages']['page_on_expired'],
                 $plugin_tx['memberpages']['config_use_special_page']) . "\n</select>";

    $o .= '</table>' . "\n";


    $o  .= '<hr><h5>' . $plugin_tx['memberpages']['config_how_membersnotice'] . '</h5>';

    $o .= memberpages_ConfigCheckBox('rememberme','config_show_remember_me');
    $o .= memberpages_ConfigCheckBox('controlpanel','config_show_controlpanel');
    $o .= memberpages_ConfigCheckBox('show_actives','config_show_actives','OnClick="
                if(this.checked) {
                    document.getElementById(\'p_show_fullname\').style.display = \'block\';
                } else {
                    document.getElementById(\'p_show_fullname\').style.display = \'none\';
                } ; "');

    $show_fullname_display = ($plugin_cf['memberpages']['show_actives'])
        ? 'style="display:block;"'
        : 'style="display:none;"';
    $o .= memberpages_ConfigCheckBox('show_fullname','config_show_fullname','',$show_fullname_display);



    $o  .= '<hr><h5>' . $plugin_tx['memberpages']['config_what_extras'] . '</h5>';

    $o .= memberpages_ConfigCheckBox('show_expires','config_show_expires','OnClick="
                if(this.checked) {
                    document.getElementById(\'expireswarning\').style.display = \'table-row-group\';
                } else if(!this.checked) {
                    document.getElementById(\'expireswarning\').style.display = \'none\';
                } ; "');

    $o .= memberpages_ConfigCheckBox('register_email','config_register_members_email');

    $o .= '<hr><br>';

    $o .= '<table class="membp_config membp_admin">' . "\n";



    // accessmode choice
    //====================

    $o .= '<tr><td><h5>'
       .  $plugin_tx['memberpages']['access_mode']
       . '</h5></td><td>';

    $o .= '<select name="accessmode">';

    // all members can access all memberpages
    $o .= '<option value="0">'.$plugin_tx['memberpages']['access_to_all_memberpages']."</option>\n"

    // access to equal + lower
       .  '<option value="1"';
    if($plugin_cf['memberpages']['accessmode']=="1") $o .=' selected';
    $o .= '>'.$plugin_tx['memberpages']['access_equal_or_higher']."</option>\n"

    // access to equal
       .  '<option value="2"';
    if($plugin_cf['memberpages']['accessmode']=="2") $o .=' selected';
    $o .= '>'.$plugin_tx['memberpages']['access_must_match_pageaccess']."</option>\n"

    // access to equal + VIP access to lower
       .  '<option value="1.5"';
    if($plugin_cf['memberpages']['accessmode']=="1.5") $o .=' selected';
    $o .= '>'.$plugin_tx['memberpages']['access_must_match_VIP_see_lower']."</option>\n"

    // bitmask 3 bits
       .  '<option value="3"';
    if($plugin_cf['memberpages']['accessmode']=="3") $o .=' selected';
    $o .= '>'.sprintf($plugin_tx['memberpages']['access_must_match_bitmask'],3)."</option>\n"

    // bitmask 4 bits
       .  '<option value="4"';
    if($plugin_cf['memberpages']['accessmode']=="4") $o .=' selected';
    $o .= '>'.sprintf($plugin_tx['memberpages']['access_must_match_bitmask'],4)."</option>\n"

    // bitmask 5 bits
       .  '<option value="5"';
    if($plugin_cf['memberpages']['accessmode']=="5") $o .=' selected';
    $o .= '>'.sprintf($plugin_tx['memberpages']['access_must_match_bitmask'],5)."</option>\n"
       .  "</select>\n"
       .  '</td></tr>';


    // choose number of available level in access options same and same+lower
    $access_levels_display = ($plugin_cf['memberpages']['accessmode']=="1"
                           || $plugin_cf['memberpages']['accessmode']=="2")
                           ?  'style="display:table-row;"'
                           :  'style="display:none;"';
    $o .= '<tr id="access_levels" '.$access_levels_display.'><td>'
       .  $plugin_tx['memberpages']['access_levels'] . '</td><td>'
       .  '<input type="text" style="width:3em;text-align:center;" name="selectable_levels" value="'
       .  $plugin_cf['memberpages']['selectable_levels'].'" >'
       .  '</td></tr>';



    // new passwords
    $o .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
    $o .= '<tr><td><h5>'
       .  $plugin_tx['memberpages']['config_new_passwords']
       .  '</h5></td><td>'

       .  '<select name="generate_password" OnChange="
                if(this.options[this.selectedIndex].value == \'0\' ||
                   this.options[this.selectedIndex].value == \'1\') {
                    document.getElementById(\'defaultpw\').style.display = \'table-row\';
                } else {
                    document.getElementById(\'defaultpw\').style.display = \'none\';
                } ; ">'
       .  '<option value="0">'.$plugin_tx['memberpages']['password_take_default']."</option>\n"
       .  '<option value="1"';
    if($plugin_cf['memberpages']['generate_password']=="1") $o .=' selected';
    $o .= '>'.$plugin_tx['memberpages']['password_take_default_variation']."</option>\n";
    for ($i=2;$i < 7;$i++) {
        $o .= '<option value="'.$i.'"';
        if($plugin_cf['memberpages']['generate_password']==$i) $o .=' selected';
        $o .= '>'.sprintf($plugin_tx['memberpages']['password_generate_random_string'],($i + 2))."</option>\n";
    }
    $o .= "</select>\n"
       .  '</td></tr>' . "\n";


    // default password
    $defaultpw = $plugin_cf['memberpages']['generate_password'] <2
        ? ' style="display:table-row;" '
        : ' style="display:none;" ';
    $o .= '<tr'.$defaultpw.'id="defaultpw"><td>'
       .  $plugin_tx['memberpages']['config_default_password'] . '</td><td style="white-space:nowrap;">'

       .  '<input type="text" style="width:50%" name="default_password" value="'
       .  $plugin_cf['memberpages']['default_password'].'">'
       .  '</td></tr>' ."\n";


    // security level of user changed passwords
    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_password_change']
       .  '</td><td>'

       .  '<select name="passwordbyuser">'
       .  '<option value="0">'.$plugin_tx['memberpages']['config_new_pw_any']."</option>\n"
       .  '<option value="4"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 4) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_4chars']."</option>\n"
       .  '<option value="5"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 5) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_5chars']."</option>\n"
       .  '<option value="6"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 6) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_6chars']."</option>\n"
       .  '<option value="7"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 7) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_7chars']."</option>\n"
       .  '<option value="8"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 8) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_8chars']."</option>\n"
       .  '<option value="5.1"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 5.1) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_5chars_cap_low']."</option>\n"
       .  '<option value="5.2"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 5.2) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_5chars_cap_low_cipher']."</option>\n"
       .  '<option value="6.2"';
    if ($plugin_cf['memberpages']['passwordbyuser'] == 6.2) $o .= ' selected';
    $o .= '>'.$plugin_tx['memberpages']['config_new_pw_6chars_cap_low_cipher']."</option>\n"
       .  "</select>\n"
       .  '</td></tr>' . "\n";


    // in case of forgotten password or registration demand, we need a sender's
    // email to send missing passwords and an admin email to inform admin
    $css = ($plugin_cf['memberpages']['passwordforgotten']) || $plugin_cf['memberpages']['registerme']
        ? 'style="display:table-row-group;"'
        : 'style="display:none;"';
    $o .= '<tbody  id="emaildetails" '.$css.'>' . "\n";
    $o .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_site_email']
       .  '</td><td>' . "\n"
       .  '<input type="text" name="site_email" value="'
       .  $plugin_cf['memberpages']['site_email']
       .  '">'
       .  '</td></tr>' . "\n";

    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_email_closing']
       .  '</td><td>' . "\n"
       .  '<textarea name="email_goodbye">'
       .  $plugin_tx['memberpages']['email_goodbye']
       .  '</textarea>'
       .  '</td></tr>' . "\n";

    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_admin_email']
       .  '</td><td>' . "\n"
       .  '<input type="text" name="admin_email" value="'
       .  $plugin_cf['memberpages']['admin_email']
       .  '">'
       .  '</td></tr>' . "\n";


    $o .= '</tbody>' . "\n";

    // details for warning of expiring membership
    $css = $plugin_cf['memberpages']['show_expires']
        ? 'style="display:table-row-group;"'
        : 'style="display:none;"';
    $o .= '<tbody  id="expireswarning" '.$css.'>' . "\n";
    $o .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_warningtext']
       .  '</td><td>' . "\n"
       .  '<textarea name="warning_text">'
       .  $plugin_tx['memberpages']['warning_text']
       .  '</textarea>'
       .  '</td></tr>' . "\n";

    $o .= '<tr><td>&nbsp;'
       .  '</td><td>' . "\n"
       .  '<input type="text" style="width:3em;text-align:center;" name="warning_time" value="'
       .  $plugin_cf['memberpages']['warning_time']
       .  '">'
       .  ' ' . $plugin_tx['memberpages']['config_warning_time']
       .  '</td></tr>' . "\n";


    $o .= '</tbody>' . "\n";



    $o .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

    $o .= '<tr><td>'
       .  $plugin_tx['memberpages']['config_membersfilepath']
       .  '</td><td>' . "\n"
       .  '<input type="text" name="membersfilepath" value="'
       .  $plugin_cf['memberpages']['membersfilepath']
       .  '">'
       .  '</td></tr>' . "\n";

    $o .= '</table>' . "\n";



    $o .= '<br>'
       .  '<input type="submit" onClick="keepSrollPos();" name="save_memberpagesconfig" value=" &nbsp; '
       .  $plugin_tx['memberpages']['save_settings'].' &nbsp; ">'
       .  '<span id="membp_saved2">'.$plugin_tx['memberpages']['saving_ok'].'</span>'
       . '</form>' . "\n";

    return $o;
}


function memberpages_SelectPage($oldvalue, $text)
{
global $cl,$h,$u,$l;

    $pages_select = '';
    for ($i = 0; $i < $cl; $i++) {
        $levelindicator = '';
        for ($j = 1; $j <$l[$i]; $j++) {$levelindicator .= '&ndash;&nbsp;';}
        $page = $levelindicator.$h[$i];
        $selected = '';
        if($oldvalue == $u[$i]) {$selected = ' selected';}
        $pages_select .= "\n".'<option value="' . $u[$i] . '"' . $selected .  '>'
                      . $text . ':&nbsp; ' . $page . '</option>';
    }
    return $pages_select;
}



function memberpages_AccessSelect($j,$access_level)
{
    global $plugin_cf;
    $o = '';

    // bitmask matching, access level numbers are converted into binary checkbox contents
    if($plugin_cf['memberpages']['accessmode'] > 2) {

        $o .= '<span style="white-space:nowrap;">';

        for ($k = 1; $k <= $plugin_cf['memberpages']['accessmode']; $k++) {
            $checked = ($access_level & pow(2,($k-1))) ? ' checked=checked' : '';
           $o .= '<input type="checkbox" '.$checked.' value="1" id="check'.$k.'a'.$j.'"  >';
        }
        for (; $k <= 5; $k++) {
            $value = ($access_level & pow(2,($k-1))) ?  1 : 0;
            $o .= '<input type="hidden" value='.$value.' id="check'.$k.'a'.$j.'"  >';
        }
        $o .=  '</span></td>' . "\n";

    } else {
        $x = 0;
        $values_select = '';
        for($k = 0; $k <= $plugin_cf['memberpages']['selectable_levels']; $k++) {
            $selected = '';
            if($k == $access_level) {$selected = ' selected'; $x++;}
            $values_select .= "<option value='$k'$selected> &nbsp; &nbsp; $k &nbsp; &nbsp; </option>\n" ;
        }
        $o .= '<select id="access'.$j.'">';
        if($x==0 && $access_level) {
            $o .= "<option value='$access_level' selected='selected'> &nbsp; &nbsp;  $access_level &nbsp; </option>\n" ;
        }
        $o .= "$values_select</select>\n</td>\n";
    }
    return $o;
}



function memberpages_CheckFiles()
{
    if(!is_dir(MEMBERSFILEPATH) && !ini_get('safe_mode')) {
        mkdir(MEMBERSFILEPATH, 0777, true);
        if(!is_writable(MEMBERSFILEPATH)) {
            if(!is_file(MEMBERSFILEPATH)) e('missing', 'folder', MEMBERSFILEPATH);
            else e('cntwriteto', 'folder', MEMBERSFILEPATH);
        }
    }
    foreach (array(
        MEMBERSFILEPATH.'new.php',
        MEMBERSFILEPATH.'membersfile.php',
        MEMBERSFILEPATH.'log.txt',
        MEMBERSFILEPATH.'members.dat'
        ) as $file) {
        if(!is_writable($file)) {
            if(!is_file($file)) {
                file_put_contents($file,'');
                if(!is_file($file)) e('missing', 'file', $file);
            } 
            else e('cntwriteto', 'file', $file);
        } 
    }
}