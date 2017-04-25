<?php


function memberpagesUpdate()
{
    global $plugin_cf,$plugin_tx,$hjs,$pth,$sl,$cf;
    $o = $text = $error = $olddir = '';

    $hjs .= '<style type="text/css"><!--
.membp_update {
    border:2px red solid;
    padding:1em;
    background:#ffa;
    margin-bottom:10em;
}
.membp_update div {
    max-width:30em;
    margin:0 auto 2em;
}
.membp_update h1 {
    color:red;
    text-align:center;
}
.membp_update p, .membp_update button, .membp_update input{
    color:#600;
    font-size:110%;
    width:100%;
    padding:0;
}
.membp_update input {
    font-weight:bold;
    font-family: Consolas, monospace;
}
.membp_update button {
    line-height:2;
    color:black;
    font-weight:bold;
    display:block;
    text-align:center;
    background:#ccc;
    cursor: pointer;
}
.membp_update form {
    margin:0;
}
.membp_update .error {
    background: #fbe5e2 url("fail.png") no-repeat 10px;
    color: #992213;
    border: 1px solid #f2a197;
}
.membp_update div .membp_found {
    border:2px solid #aaa;
    margin: 16px -1em;
    padding:1em 1em;
    background:#ffe;
}
//--></style>';

    $en = $de = array();
    $en['logfile_bytesimport']  = ' Bytes from old log file imported into new log file.';
    $de['logfile_bytesimport']  = ' Bytes aus alter Logdatei in neue Logdatei importiert.';
    $en['error_logfileimport']  = 'ERROR: Import of log file failed.';
    $de['error_logfileimport']  = 'FEHLER: Import der Logdate nicht gelungen.';
    $en['memberlist_importok']  = '<p>Old members list imported and saved as new list.</p>';
    $de['memberlist_importok']  = '<p>Alte Mitgliederliste importiert und als neue Liste gespeichert.</p>';
    $en['err_memberlistimport'] = 'ERROR: Import of old members list failed. Please check writing permissions and try again.';
    $de['err_memberlistimport'] = 'FEHLER: Import der alten Mitgliederliste fehlgeschlagen. Bitte Schreibrechte überprüfen und noch einmal versuchen.';
    $en['oldpath_now_newpath']  = '<p>Old members list path has become path for new members list</p>';
    $de['oldpath_now_newpath']  = '<p>Alter Pfad zur Mitgliederliste jetzt als aktueller Pfad eingestellt</p>';
    $en['err_oldpath_newpath']  = '<p>ERROR: New members list path could not be written into config.php. '
                                . 'Please check writing permissions of config.php and start plugin activation again.</p>';
    $de['err_oldpath_newpath']  = '<p>FEHLER: Neuer Pfad zur Mitgliederliste konnte nicht in die config.php geschrieben werden. '
                                . 'Bitte Schreibrechte prüfen und Pluginaktivierung wiederholen.</p>';
    $en['path_memlistchanged']  = '<p>Path for new list changed to ';
    $de['path_memlistchanged']  = '<p>Pfad zu neuen Liste geändert zu ';
    $en['err_path_listchange']  = '<p>ERROR: New members list path could not be written into config.php. '
                                . 'Please check writing permissions of config.php and start plugin activation again.</p>';
    $de['err_path_listchange']  = '<p>FEHLER: Pfad zur neuen Mitgliederliste konnte nicht in die config.php geschrieben werden. '
                                . 'Bitte Schreibrechte bei der config.php prüfen und Pluginaktivierung wiederholen.</p>';
    $en['activation_alert']     = 'Plugin Activation Form';
    $de['activation_alert']     = 'Plugin-Aktivierung';
    $en['update']               = 'update from version<br>';
    $de['update']               = 'Update von Version<br>';
    $en['activation_click_here']= 'click here to activate plugin';
    $de['activation_click_here']= 'hier klicken zum Aktivieren.';
    $en['debug_notice']         = '(Please note: php warnings while in debug mode are normal when certain files have not '
                                . 'yet been created. '
                                . 'The warnings should disappear after completion of the plugin activation '
                                . 'and entereing of at least one member.)<br>';
    $en['activation_explained'] = 'It this Activation Form doesn\'t complain, your folder settings and writing permissions should be OK.<br>'
                                . 'Existing data from version 3.5 will remain, data from old versions (3.0-3.4.4) '
                                . 'can be imported, as default a new members\' list will be started. '
                                . 'If folder settings, permissions and import are OK,';
    $de['debug_notice']         = '(Hinweis: Bei aktiviertem Debug-Modus sind PHP-Warnungen normal weil einige Dateien '
                                . 'noch nicht angelegt sind. Die Warnungen sollten nach Abschluss der Aktivierung und '
                                . 'Anlage mindestens eines Mitglieds verschwinden.)<br>';
    $de['activation_explained'] = 'Wenn Ordner und Schreibrechte nicht in Ordnung sind, gibt das Aktivierungsformular eine entsprechende Meldung aus.<br>'
                                . 'Mitgliederliste ab Version 3.5 werden übernommen, aus älteren Version (3.0-3.4.4) können sie importiert werden.<br>Nachdem alles geprüft ist,';
    $en['update_explained']     = 'Your member\'s data is not going to be changed. Setting saved in the language file
                                   will be imported from the language file backup';
    $de['update_explained']     = 'Die Mitgliederdaten bleiben unverändert. Die in der Sprachdatei gespeicherten
                                   Daten werden aus dem Backup übernommen.';
    $en['details_explained']    = '<p>Starting with version 3.5, Memberpages saves</p>'
                                . '<ul><li>members\' list (membersfile.php)</li>'
                                . '<li>log file (log.txt)</li>'
                                . '<li>names of momentarily active members (members.dat)</li>'
                                . '<li>enquiries to become member (new.php)</li>'
                                . '<li>protective .htaccess file (.htaccess)</li></ul>'
                                . '<p>together in the same folder, which is at present: ';
    $de['details_explained']    = '<p>Ab Version 3.5 speichert Memberpage_XH</p>'
                                . '<ul><li>die Mitgliederliste (membersfile.php)</li>'
                                . '<li>die Logdatei (log.txt)</li>'
                                . '<li>die Namen der gegenwärtig eingeloggten Mitglieder (members.dat)</li>'
                                . '<li>die Anträge auf Mitgliedschaft (new.php)</li>'
                                . '<li>und die gegen Fremdzugriff schützende .htaccess Datei (.htaccess)</li></ul>'
                                . '<p>zusammen im selben Ordner, der gegenwärtig folgender ist: ';
    $en['standard_location']    = ' (standard location)</p>';
    $de['standard_location']    = ' (Standard Ordnereinstellung)</p>';
    $en['changed_location']     = ' (changed location)</p>';
    $de['changed_location']     = ' (Geänderte Ordnereinstellung)</p>';
    $en['newlist_foundat']      = '<p><b>New members list</b> found at<br>%s<br>with %s members.</p>';
    $de['newlist_foundat']      = '<p><b>Neue Mitgliederliste</b> gefunden unter<br>%s<br>mit %s Mitgliedern.</p>';
    $en['newlog_foundat']       = '<p>New log file of %s Byte found.</p>';
    $de['newlog_foundat']       = '<p>Neue Logdatei von %s Byte Umfang gefunden.</p>';
    $en['emptylist_exists']     = '<p>Empty new members file already created.</p>';
    $de['emptylist_exists']     = '<p>Leere neue Mitgliederdatei bereits angelegt.</p>';
    $en['nonewlist_found']      = '<p>No new members file found</p>';
    $de['nonewlist_found']      = '<p>Keine neue Mitgliederliste gefunden.</p>';
    $en['csv_was_set']          = '<p>Old config found: members\' list in csv-file.</p>';
    $de['csv_was_set']          = '<p>Alte Einstellung gefunden: Mitgliederliste im csv-Format.</p>';
    $en['oldlist_with_members'] = '<p>Old Membersfile %s with %s members found, namely: %s.</p>';
    $de['oldlist_with_members'] = '<p>Alte Mitgliederliste %s mit %s Mitgliedern gefunden, und zwar: %s.</p>';
    $en['oldlog_found']         = '<p>Old log file of %s Byte found.</p>';
    $de['oldlog_found']         = '<p>Alte Logdatei von %s Byte Umfang gefunden.</p>';
    $en['info_old_permis']      = '<p>(Writing permissions for the old members\' list are only relevant,
                                   if you want to put your new members\' file in the old folder and not
                                   in the new default folder <br>./userfiles/plugins/memberpages/)</p>';
    $de['info_old_permis']      = '<p>(Schreibrechte für die alte Mitgliederliste sind nur von Bedeutung,
                                   wenn man die neue Mitgliederliste im bisherigen Ordner der alten Liste speichern will
                                   und nicht im neuen Standardordner <br>./userfiles/plugins/memberpages/)</p>';
    $en['info_changefolder']    = 'You can change the folder path to the old location if you think that\'s better.';
    $de['info_changefolder']    = 'Sie können als Pfad zur Mitgliederliste den Pfad zur alten Mitgliederliste einstellen,
                                   wenn Ihnen das besser als der Standardpfad erscheint.';
    $en['changeto_oldloc']      = 'Change folder path to the old location';
    $de['changeto_oldloc']      = 'Pfad ändern zu Pfad zum alten Speicherort';
    $en['info_anylocation']     = 'You can also change the folder
                                   to any other location. While changing the folder location the plugin will
                                   not move any lists but start a new list.';
    $de['info_anylocation']     = 'Sie können auch einen ganz anderen Pfad für den Speicherort der Mitgliederliste
                                   wählen. Wenn Sie einen neuen Pfad hier eingeben, wird
                                   unter dem neuen Pfad eine neue Liste angelegt. (Der Pfad ist jederzeit änderbar.)';
    $en['change_toanylocation'] =  'Change folder path to above location';
    $de['change_toanylocation'] =  'Pfad auf angegebenen Pfad ändern';
    $en['import_oldlist']       = 'If all permissions for the choosen folder location are OK
                                   you can import your old list and log file.
                                   (Any new list and log at that location will be overwritten.)';
    $de['import_oldlist']       = 'Wenn alle Schreibrechte für den ausgewählten Speicherort passen,
                                   können die alte Mitgliederliste und die Logdatei importiert werden.
                                   (Eventuell an diesem Speicherort vorhandene neue Mitgliederliste und Logdatei werden überschrieben.)';
    $en['import_csvlistandlog'] = 'Import old csv members\' list and log';
    $de['import_csvlistandlog'] = 'Alte csv-Mitgliederliste und Logdatei importieren';
    $en['import_phplistandlog'] = 'Import old php members\' list and log';
    $de['import_phplistandlog'] = 'Alte php-Mitgliederliste und Logdatei importieren';

    $tx = $sl=='de'? $de : $en;

    if(isset($_POST['importcsv'])) {
        $member = memberpages_GetOldList('csv');
        $logsize = filesize($pth['folder']['plugins'] . 'memberpages/logfile/logfile.txt');
        if($logsize) {
            $logfile = file_get_contents($pth['folder']['plugins'] . 'memberpages/logfile/logfile.txt');
            $i = file_put_contents(MEMBERSFILEPATH . 'log.txt',$logfile);
            $text .= $i
                   ? '<p>' . $i . $tx['logfile_bytesimport']
                   : '<p>' . $tx['error_logfileimport'];
        }
        if(memberpages_SaveList($member)) {
            $text .= $tx['memberlist_importok'];
        } else $text .= '<p class="error">'. $tx['err_memberlistimport'] .'</p>';
    }
    if(isset($_POST['importphp'])) {
        $member = memberpages_GetOldList();
        $logsize = filesize($pth['folder']['plugins'] . 'memberpages/logfile/logfile.txt');
        if($logsize) {
            $logfile = file_get_contents($pth['folder']['plugins'] . 'memberpages/logfile/logfile.txt');
            $i = file_put_contents(MEMBERSFILEPATH . 'log.txt',$logfile);
            $text .= $i
                   ? '<p>' . $i . $tx['logfile_bytesimport']
                   : '<p>' . $tx['error_logfileimport'];
        }
        if(memberpages_SaveList($member)) {
            $text .= $tx['memberlist_importok'];
        } else $text .= '<p class="error">'. $tx['err_memberlistimport'] .'</p>';
    }

    $config = file_get_contents($pth['folder']['plugins'] . 'memberpages/config/config.php');
    $config = rtrim($config,"\n\r>?")."\n";

    if(isset($plugin_cf['memberpages']['usecsvfile']) && $plugin_cf['memberpages']['usecsvfile']) {
        $olddir = substr($plugin_cf['memberpages']['csvfile'],0,
                  strrpos($plugin_cf['memberpages']['csvfile'], '/')+1);
    } else {
        $olddir = substr(memberpages_SearchPhpList(),0,
                  strrpos(memberpages_SearchPhpList(), '/')+1);
    }


    if(isset($_POST['changeToOldDir'])) {
        $config .= '$plugin_cf[\'memberpages\'][\'membersfilepath\']="'
                . $olddir . '";' . "\n";
        if(file_put_contents($pth['folder']['plugins'] . 'memberpages/config/config.php', $config, LOCK_EX)) {
            $message .= $tx['oldpath_now_newpath'];
        } else $message .= $tx['err_oldpath_newpath'];
        setcookie('mmp_pluginActivation', $message, time()+2);
        header('Location: '.CMSIMPLE_URL.'?&memberpages&admin=plugin_main');
        exit;
    }

    if(isset($_POST['newDir'])) {
        $config .= '$plugin_cf[\'memberpages\'][\'membersfilepath\']="'
                . $_POST['newDir'] . '";' . "\n";
        if(file_put_contents($pth['folder']['plugins'] . 'memberpages/config/config.php', $config, LOCK_EX)) {
            $message .= $tx['path_memlistchanged'] . $_POST['newDir'] . '</p>';
        } else $message .= $tx['err_path_listchange'];
        setcookie('mmp_pluginActivation', $message, time()+2);
        header('Location: '.CMSIMPLE_URL.'?&memberpages&admin=plugin_main');
        exit;
    }

    if(isset($_POST['activate'])) {
        memberpages_UpdateLang();
        memberpages_SaveConfig();
        memberpages_ProcessList();
    }

// ===================================
// Standard dialog plugin activation
// ===================================


    if(isset($_COOKIE['mmp_pluginActivation'])) $text .= $_COOKIE['mmp_pluginActivation'];

    $text .= isset($plugin_cf['memberpages']['version'])
           ? '<h1>Memberpages ' . MEMBERPAGES_VERSION . '<br>'
             . $tx['update']
             . $plugin_cf['memberpages']['version']
             . '</h1>'
           : '<h1>Memberpages ' . MEMBERPAGES_VERSION . '<br>'
             . $tx['activation_alert']
             . '</h1>';

    if(isset($plugin_cf['memberpages']['version'])
        && (version_compare($plugin_cf['memberpages']['version'],'3.5') >= 0)) {

        $text .= '<p>'.$tx['update_explained'].'</p>'
              . '<form action="" method="POST">'
              . '<button type="submit" name="activate"
                style="font-weight:bold;padding:0 1em;letter-spacing:.05em;" value="1">'
              . $tx['activation_click_here']
              . '</button>'
              . '</form>';

    } else {

        if (error_reporting() > 0) $text .= '<p>'.$tx['debug_notice'].'</p>';

        $text .= '<p>'.$tx['activation_explained'].'</p>'
              . '<form action="" method="POST">'
              . '<button type="submit" name="activate"
                style="font-weight:bold;padding:0 1em;letter-spacing:.05em;" value="1">'
              . $tx['activation_click_here']
              . '</button>'
              . '</form>' . '<br>';

        $text .= $tx['details_explained']
               .  MEMBERSFILEPATH;
        $text .=  MEMBERSFILEPATH == $pth['folder']['userfiles'].'plugins/memberpages/'
               ? $tx['standard_location']
               : $tx['changed_location'];
        $text .=  memberpages_CheckPermissions();

        $text .= '<div class="membp_found">';
        // new files found
        if(is_file(MEMBERSFILEPATH.'membersfile.php')) {
            $member = parse_ini_file(MEMBERSFILEPATH.'membersfile.php',true);
            if(count($member)>1
                && isset($member['0']['user'])
                && isset($member['0']['pass'])) {
                $text .= sprintf($tx['newlist_foundat'], MEMBERSFILEPATH, count($member));
                $newlogsize = filesize(MEMBERSFILEPATH . 'log.txt');
                if($newlogsize) $text .= sprintf($tx['newlog_foundat'], $newlogsize);
            } else $text .= $tx['emptylist_exists'];
        } else $text .= $de['nonewlist_found'];

        $text .= '</div>';

        // old files found
        if(memberpages_GetOldList('csv') || memberpages_GetOldList()) {
            $text .= '<div class="membp_found">';

            if(isset($plugin_cf['memberpages']['usecsvfile']) && $plugin_cf['memberpages']['usecsvfile'])
                $text .=  $tx['csv_was_set'];

            if($csv = memberpages_GetOldList('csv'))
            $members = '';
            foreach ($csv as $key=>$value) {
            	$members .= $csv[$key]['user'] . ', ';
            }
            $text .= sprintf($tx['oldlist_with_members'], memberpages_SearchCsvList(), count($csv), trim($members,' ,'));

            if($php = memberpages_GetOldList('php'))
            $members = '';
            foreach ($php as $key=>$value) {
            	$members .= $php[$key]['user'] . ', ';
            }
            $text .= sprintf($tx['oldlist_with_members'], memberpages_SearchPhpList(), count($php), trim($members,' ,'));


            $logsize = filesize($pth['folder']['plugins'] . 'memberpages/logfile/logfile.txt');
            if($logsize) $text .= sprintf($tx['oldlog_found'],$logsize);

            $text .= memberpages_CheckPermissions($olddir);
            $text .= $tx['info_old_permis'];

            $text .= '</div>';

            // Change path to old location
            if(trim(MEMBERSFILEPATH,'./') != trim($olddir,'./'))
                $text .= '<form action="" method="POST">' . $tx['info_changefolder']
                       . '<button type="text" name="changeToOldDir"
                         value="'.$olddir.'">'.$tx['changeto_oldloc']
                       . '<br>' . $olddir . '</button></form>'. '<br>';
        }

        // change path to any location
        $text .= '<form action="" method="POST">' . $tx['info_anylocation']
               . '<input type="text" name="newDir" value="./">'
               . '<button type="submit">' . $tx['change_toanylocation']
               . '</button></form>'. '<br>';

        // import old lists
        if($csv || $php) {
            $text .= '<form action="" method="POST">'.$tx['import_oldlist'];
            if($csv)         $text .= '<button type="text" name="importcsv"
                                       value="1">'. $tx['import_csvlistandlog'] . '</button>';
            if($php)         $text .= '<button type="text" name="importphp"
                                       value="1">'. $tx['import_phplistandlog'] . '</button>';
            $text .= '</form>';
        }
    }


    // Wrapper for activation messages
    $o .=  '<div class="membp_update"><div>'
        . $text
        . '</div></div>';

    return $o;
}


function memberpages_CheckFile($file)
{
    $o = array('missing'=>'','notwritable'=>'');

    if(!is_file($file) && !ini_get('safe_mode')) file_put_contents($file,'');
    if(!is_file($file)) $o['missing'] .=  $file . '<br>';
    elseif(!is_writable($file)) {
        $o['notwritable'] .=  $file . '<br>';
    }
    return $o;
}



function memberpages_CheckPermissions($path='')
{
    global $pth,$tx,$sl;
    $o = '';
    static $savemodeinfo = 0;

    $en = $de = array();
    $en['ok']   = '<p>Folder settings and writing permissions for %s OK</p>';
    $de['ok']   = '<p>Ordnereinstellungen und Schreibrechte für %s in Ordnung.</p>';
    $en['savemode'] = '<p class="error">PHP-setting <b>save_mode=on</b> detected.</p>';
    $en['info_savemode'] = '<p>Save-mode=on
                 prevents php from creating writable folders.
                 In order to get writable folders you have to upload the folders via ftp.</p>'
               .'<p>If the folder for saving the members\' list is missing, get
                 <b>memberpages_userfiles.zip</b>, unzip and upload it to the base of your CMSimple_XH installation.
                 This creates the standard userfiles/plugin/memberpages/ folder plus the new empty files.</p>
                 <p>However, if you want to save the members\' list somewhere else, check first if the necessary folders
                 exist and are usable (i.e. have the correct owner).
                 If not, you will have to create empty folders with the wanted names on your pc first
                 and upload these empty folders via ftp to the correct place in your website.</p>
                 <p>Missing files can usually be created with the ftp-programm. Finally check writing permissions.</p>';
    $de['savemode'] = '<p class="error">PHP-Einstellung <b>save_mode=on</b> festgestellt.</p>';
    $de['info_savemode'] = '<p>Save-mode=on
                 verhindert, dass PHP Ordner mit einstellbaren Schreibrechten erstellen kann.
                 Um schreibbare Ordner zu erhalten müssen diese per FTP
                 hochgeladen werden, andernfalls haben sie den falschen Besitzer und sind unbenutzbar.</p>'
               . '<p>Wenn bei Ihnen der Ordner für die Speicherung der Mitgliederliste fehlt,
                 können Sie das zip-Paket <b>memberpages_userfiles.zip</b>
                 herunterladen, entpacken und auf das Basisverzeichnis Ihrer CMSimple_XH-Website aufspielen. Dieses Paket
                 enthält die Ordnerstruktur userfiles/plugins/memberpages/ mit neuen leeren Dateien.</p>
                 <p>Möchten Sie Ihre Mitgliederliste aber an anderem Ort speichern, überprüfen Sie zuerst, ob die 
                 gewünschten Ordner vorhanden sind und prinzipiell bearbeitbar sind. Wenn nicht, legen Sie diese auf 
                 dem eigenen PC an und laden Sie sie per FTP an die richtige Stelle hoch.</p>
                 <p>Anschließend eventuell fehlende Dateien mit Ihrem FTP-Programm erzeugen und Schreibrechte einstellen.</p>';
    $en['no-apache'] = '<p>Your server was not detected as Apache, thus .htaccess protection may not work.
               Please secure your folder <b>%s</b> against external access.</p>';
    $de['no-apache'] = '<p>Ihr Server wurde nicht als Apache erkannt. Bei Nicht-Apache-Servern wirkt der .htaccess-Schutz eventuell nicht.
               Schützen Sie daher den Ordner <b>%s</b> gegen Fremdzugriff entsprechend.</p>';

    $t = $sl=='de'? $de : $en;


    $error = memberpages_CheckFile($pth['folder']['plugins'].'memberpages/config/config.php');
    if(!$path) $path = MEMBERSFILEPATH;
    if(!is_dir($path) && !ini_get('safe_mode')) mkdir($path, 0777, true);
    if(!is_dir($path)) {
        $error['missing'] .=  $path . '<br>';
    }
    elseif(!is_writable($path)) {
        $error['notwritable'] .= $path . '<br>';
    }

    $x = memberpages_CheckFile($path.'log.txt');
    $error['notwritable'] .= $x['notwritable'];
    $error['missing'] .= $x['missing'];


    $x = memberpages_CheckFile($path.'membersfile.php');
    $error['notwritable'] .= $x['notwritable'];
    $error['missing'] .= $x['missing'];
    if(!is_file($path.'membersfile.php') || filesize($path.'membersfile.php') < 10)
        file_put_contents($path.'membersfile.php',
         ';<?php' . PHP_EOL . ';die();' . PHP_EOL . ';/*' . PHP_EOL);

    if(!is_file($path.'.htaccess') || filesize($path.'.htaccess') < 10) {
        copy("{$pth['folder']['plugins']}memberpages/htaccess.tpl", "{$path}.htaccess");
    }
    $x = memberpages_CheckFile($path.'.htaccess');
    $error['notwritable'] .= $x['notwritable'];
    $error['missing'] .= $x['missing'];

    $server = strtolower( $_SERVER["SERVER_SOFTWARE"] );
    if (strpos($server, "apache") === false ) {
        $o .= sprintf($t['no-apache'],$path);
    }

    if($error['missing']) {
        $o .= '<div class="error"><u>'. $tx['error']['missing'] .':</u>'. '<br>' . $error['missing'] . '</div>';
        if( ini_get('safe_mode' ) ){

            $o .= $savemodeinfo
                ? $t['savemode']
                : $t['savemode'] . $t['info_savemode'];
            $savemodeinfo ++;
        }
    }
    if($error['notwritable']) $o .= '<div class="error"><u>'. $tx['error']['notwritable']
                                  . ':</u>'. '<br>' . $error['notwritable'] . '</div>';

    return $o? $o : sprintf($t['ok'],$path);
}



function memberpages_GetOldList($type = 'php')
{
    global $plugin_cf,$pth,$tx,$plugin_tx;

    if($type == 'csv')
    {
        $user = $exuser = $pass = $expass = $access = $email = $fullname = $expires = array();

        if(!$csvlist = memberpages_SearchCsvList()) return false;

        $array = file($csvlist);

        foreach($array as $key => $value) {
            $data = explode(',', $array[$key]);
            $x = mb_strlen($data[1],'UTF-8')>4? 1 : '';
            if(trim($data[5])) {
                list($year,$month,$day) = explode('-',trim($data[5]));
                $expires = mktime(0,0,0,$month,$day,$year);
            } else {
                $expires = '';
            }
        	$member[$key] = array(
                'user'   =>$data[0],
                'pass'   =>$data[1],
                'access' =>$data[2],
                'email'  =>trim($data[3]),
                'full'   =>trim($data[4]),
                'expires'=>$expires,
                'x'      =>$x
                );
        }
    }
    else
    {
        if(!$list = memberpages_SearchPhpList()) {
            return false;

        } else {

            include $list;

            $user     = explode(",", $members['user_array']);
            $pass     = explode(",", $members['pass_array']);
            $access   = explode(",", $members['accesslevel_array']);
            $email    = explode(",", $members['email_array']);
            $full     = explode(",", $members['fullname_array']);
            $expires  = explode(",", $members['expires_array']);
            foreach ($user as $key=>$value) {
                $x = mb_strlen($pass[$key],'UTF-8')>4? 1 : '';
                if($expires[$key]) {
                    list($year,$month,$day) = explode('-',$expires[$key]);
                    $expires[$key] = mktime(0,0,0,$month,$day,$year);
                } else $expires[$key] = '';

            	$member[$key] = array(
                    'user'   =>$user[$key],
                    'pass'   =>$pass[$key],
                    'access' =>$access[$key],
                    'email'  =>$email[$key],
                    'full'   =>$full[$key],
                    'expires'=>$expires[$key],
                    'x'      =>$x
                    );
            }
        }
    }
    return $member;
}

function memberpages_SearchPhpList()
{
    global $pth,$plugin_cf;

    if(!isset($plugin_cf['memberpages']['phpfile']) || !$plugin_cf['memberpages']['phpfile']) {
        if(is_file($pth['folder']['plugins'].'memberpages/data/memberslist.php')) {
            $list = $pth['folder']['plugins'].'memberpages/data/memberslist.php';
        }
        if(is_file($pth['folder']['plugins'].'memberpages/data/memberlist.php')) {
            $list = $pth['folder']['plugins'].'memberpages/data/memberlist.php';
        } else $list = false;
    } else $list = strpos($plugin_cf['memberpages']['phpfile'], './') === 0
                 ? $plugin_cf['memberpages']['phpfile']
                 : $pth['folder']['base'] . $plugin_cf['memberpages']['phpfile'];

    return $list;
}

function memberpages_SearchCsvList()
{
    global $pth,$plugin_cf;

    $list = isset($plugin_cf['memberpages']['csvfile']) && $plugin_cf['memberpages']['csvfile']
          ? (strpos($plugin_cf['memberpages']['csvfile'], './') === 0
            ? $plugin_cf['memberpages']['csvfile']
            : $pth['folder']['base'] . $plugin_cf['memberpages']['csvfile'])
          : $pth['folder']['base'] . 'plugins/memberpages/data/members.csv';

    if(!is_file($list)) return false;
    return $list;
}

function memberpages_UpdateLang()
{
	global $pth;

    if ($handle = opendir($pth['folder']['plugins'].'memberpages/languages/')) {
        while (false !== ($filename = readdir($handle))) {
            if (strpos($filename,'backup') === 0) {
                if (is_file($pth['folder']['plugins'].'memberpages/languages/'.substr($filename,-6))) {
                    $oldfile = $pth['folder']['plugins'].'memberpages/languages/' . $filename;
                    $newfile = $pth['folder']['plugins'].'memberpages/languages/'.substr($filename,-6);
                    $oldlangfile = file_get_contents($oldfile);

                    $languagefile = file_get_contents($newfile);

                    $pattern = array(
                        '!hide_on_login\'\]="(.*)";!',
                        '!page_on_login\'\]="(.*)";!',
                        '!page_on_VIP_login\'\]="(.*)";!',
                        '!page_on_logout\'\]="(.*)";!',
                        '!page_on_expired\'\]="(.*)";!',
                        '!warning_text\'\]="([^"]*)";!s',
                        '!email_goodbye\'\]="([^"]*)";!s'
                        );

                    foreach ($pattern as $key=>$value) {
                        preg_match($value,$oldlangfile,$matches);
                        $x[$key] = isset($matches[1])
                                 ? $matches[1]
                                 : '';
                    }

                    $replacement = array(
                        "hide_on_login']=\"".$x['0']."\";",
                        "page_on_login']=\"".$x['1']."\";",
                        "page_on_VIP_login']=\"".$x['2']."\";",
                        "page_on_logout']=\"".$x['3']."\";",
                        "page_on_expired']=\"".$x['4']."\";",
                        "warning_text']=\"".$x['5']."\";",
                        "email_goodbye']=\"".$x['6']."\";"
                        );
                    $languagefile = preg_replace($pattern,$replacement,$languagefile);

                    if(!file_put_contents($newfile,$languagefile,LOCK_EX))
                        e('cntwriteto','file',$newfile);

                } elseif (function_exists('XH_renameFile')) {
                    XH_renameFile($pth['folder']['plugins']
                        . 'memberpages/languages/'
                        . $filename, $pth['folder']['plugins']
                        . 'memberpages/languages/'
                        . substr($filename,-6));
                }
            }
        }
        closedir($handle);
    }


}