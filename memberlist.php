<?php
/**
 * Memberlist_XH
 *
 * Addon for Memberpages_XH and Register_XH to display a list of currently
 * active members.
 *
 * @package   Memberlist
 * @copyright Copyright (c) 2013 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   1beta2
 */


/*
 * BEGIN of CONFIGURATION SECTION
 */


/**
 * The path to the memberlist file.
 */
// use 1 of the following
define('MEMBERLIST_PATH', MEMBERSFILEPATH . 'members.dat');
//define('MEMBERLIST_PATH', $pth['folder']['plugins'] . 'register/logfile/members.dat');
//define('MEMBERLIST_PATH', $pth['folder']['base'] . 'path/relative/to/cmsimple/root/members.dat'); // change the path to an existing folder!


/**
 * The session variable to access for the user name.
 */
// use 1 of the following
//define('MEMBERLIST_VAR', 'Name'); // Memberpages < 3.0
//define('MEMBERLIST_VAR', 'username'); // Memberpages >= 3.0 and Register
//define('MEMBERLIST_VAR', 'fullname'); // Register only: use Full Name
if($plugin_cf['memberpages']['show_fullname']) define('MEMBERLIST_VAR', 'fullname');
    else define('MEMBERLIST_VAR', 'username');

/**
 * The function value for the member's logout.
 */
// use 1 of the following
define('MEMBERLIST_LOGOUT', 'memberslogout'); // Memberpages
//define('MEMBERLIST_LOGOUT', 'registerlogout'); // Register


/**
 * The max. inactivity (in seconds) for still counting a member as active.
 */
define('MEMBERLIST_PERIOD', 120);


/**
 * The intervall in seconds between to automatic updates of the memberlist.
 */
define('MEMBERLIST_INTERVAL', 30);


/**
 * The separator for the (X)HTML view of the memberlist.
 */
define('MEMBERLIST_SEPARATOR', ', ');


/*
 * END of CONFIGURATION SECTION
 */


/**
 * The modell of memberlists.
 *
 * Use as singleton by calling Memberlist::instance().
 *
 * @package Memberlist
 */
class Memberlist
{
    /**
     * The list of members.
     *
     * @access private
     *
     * @var array
     */
    var $members;

    /**
     * The file handle for locking.
     *
     * @access private
     *
     * @var resource
     */
    var $lock;


    /**
     * Returns the unique instance of the class.
     *
     * @access public
     * @static
     *
     * @return object
     */
    static function instance() // changed by svasti
    {
        static $instance = null;

        if (!isset($instance)) {
            $instance = new Memberlist();
        }
        return $instance;
    }

    /**
     * Locks resp. unlocks the memberlist file.
     *
     * @access private
     *
     * @return void
     */
    function lock($op)
    {
        $fn = MEMBERLIST_PATH;
        if (!file_exists($fn)) {
            touch($fn);
        }
        if (!isset($this->lock)) {
            $this->lock = fopen($fn, 'r');
        }
        flock($this->lock, $op);
        if ($op = LOCK_UN) {
            fclose($this->lock);
            unset($this->lock);
        }

    }

    /**
     * Returns the memberlist.
     *
     * @access private
     *
     * @return array
     */
    function read()
    {
        if (!isset($this->members)) {
            $fn = MEMBERLIST_PATH;
            $members = file_get_contents($fn);
            if ($members !== false) {
                $members = unserialize($members);
            }
            if ($members === false) {
                $members = array();
            }
            $this->members = $members;
        }
        return $this->members;
    }

    /**
     * Saves the new members and returns whether that succeeded.
     *
     * @access private
     *
     * @return bool
     */
    function write()
    {
        $fn = MEMBERLIST_PATH;
        $ok = ($fh = fopen($fn, 'w')) !== false
            && fwrite($fh, serialize($this->members)) !== false;
        if ($fh !== false) {
            fclose($fh);
        }
        return $ok;
    }

    /**
     * Updates the memberlist according to the currently logged in user.
     * Returns whether that succeeded.
     *
     * @access public
     *
     * @global string  The "function" GET or POST parameter.
     * @return void
     */
    function update()
    {
        global $function;

        $this->lock(LOCK_EX);
        $members = $this->read();
        if ($function == MEMBERLIST_LOGOUT) {
            unset($members[$_SESSION[MEMBERLIST_VAR]]);
        } else {
            $members[$_SESSION[MEMBERLIST_VAR]] = time();
        }
        $this->members = $members;
        $ok = $this->write();
        $this->lock(LOCK_UN);
        return $ok;
    }

    /**
     * Returns the currently active members.
     *
     * @access public
     *
     * @return array
     */
    function active()
    {
        $this->lock(LOCK_SH);
        $members = $this->read();
        $this->lock(LOCK_UN);
        $time = time();
        $active = array();
        foreach ($members as $member => $t0) {
            if ($t0 >= $time - MEMBERLIST_PERIOD) {
                $active[] = $member;
            }
        }
        natcasesort($active);
        return $active;
    }
}


/**
 * Returns the (X)HTML view of the currently active members.
 *
 * @access private
 *
 * @return string
 */
function Memberlist_list()
{
    $members = Memberlist::instance()->active();
    $members = array_map('htmlspecialchars', $members);
    return implode(MEMBERLIST_SEPARATOR, $members);
}


/**
 * Returns the (X)HTML view of the currently active members
 * enclosed in a <div id="memberlist">. If $update is true,
 * it polls automatically for changes via Ajax.
 *
 * @access public
 *
 * @global string $sn  The name of the site.
 * @global string $su  The GET variable of the current page.
 * @param  bool $update  Whether the memberlist should automatically update.
 * @return string
 */
function memberlist($update = false)
{
    global $sn, $su;

    $o = '<div id="memberlist">' . Memberlist_list() . '</div>';
    if ($update) {
        $interval = MEMBERLIST_INTERVAL;
        $o .= <<<EOS
<script type="text/javascript">
if (typeof window.XMLHttpRequest != 'undefined') {
    window.setInterval(function() {
        var request = new XMLHttpRequest();
        request.open("GET", "$sn?$su&memberlist_ajax");
        request.onreadystatechange = function() {
            if (request.readyState == 4 && request.status == 200) {
                document.getElementById("memberlist").innerHTML =
                    request.responseText;
            }
        }
        request.send(null);
    }, 1000 * $interval);
}
</script>
EOS;
    }
    return $o;
}


/*
 * Start the session.
 */
if (function_exists('XH_startSession')) {
    XH_startSession();
} elseif (session_id() == '') {
    session_start();
}

/*
 * Update the memberlist, if the user is currently logged in.
 */
if (isset($_SESSION[MEMBERLIST_VAR])) {
    if (!Memberlist::instance()->update()) {
        e('cntsave', 'file', MEMBERLIST_PATH);
    }
}


/*
 * Handle Ajax request.
 */
if (isset($_GET['memberlist_ajax'])) {
    echo Memberlist_list();
    exit;
}
?>
