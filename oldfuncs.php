<?php

function memberpages_OldStyleHide()
{
    global $adm,$edit,$plugin_cf,$c,$cl;

    if(!($edit && $adm))
    {
    	for($i = 0; $i < $cl; $i++)
    	{
            if(strpos($c[$i],'member('))
    		{
                if (preg_match('!(?:#CMSimple |{{{PLUGIN:)member\((.*?)\);(?:#|}}})!i',$c[$i],$matches))
                {
                    if(trim($matches[1],"\"'"))
                    {
                        $access_level = trim($matches[1],"\"'");

                        if(!$plugin_cf['memberpages']['accessmode'])
                        {
                			if(!isset($_SESSION['accesslevel']))
                			{
                				$c[$i] = '#CMSimple hide#';
                			}
                        }
                        elseif($plugin_cf['memberpages']['accessmode']==1)
                        {
                			if(!isset($_SESSION['accesslevel'])
                               || $_SESSION['accesslevel'] < $access_level)
                			{
                				$c[$i] = '#CMSimple hide#';
                			}
                        }
                        elseif($plugin_cf['memberpages']['accessmode']==2)
                        {
                			if(!isset($_SESSION['accesslevel'])
                                 || $_SESSION['accesslevel'] != $access_level)
                			{
                				$c[$i] = '#CMSimple hide#';
                			}
                        }
                        elseif($plugin_cf['memberpages']['accessmode'] > 2)
                        {
                			if(!isset($_SESSION['accesslevel'])
                               || !($_SESSION['accesslevel'] & pow(2,$access_level-1)))
                			{
                                $c[$i] = '#CMSimple hide#';
                			}
                        }
                    }
                    else
                    {
                		if(!isset($_SESSION['accesslevel']))
                		{
                			$c[$i] = '#CMSimple hide#';
                		}
                    }
        		}
            }
    	}
    }
}



// session entries
function memberpages_session($s)
{
    global $_SESSION;
    if(isset($_SESSION[$s])) return $_SESSION[$s]; else return '';
}



function member($level=0)
{
    global $sn,$plugin_tx,$plugin_cf;

    if(headers_sent($filename,$linenum))
    {
        echo "ERROR : memberpages detected that someone already had sent headers, look for the error in $filename line $linenum";
        exit;
    }
    if(!$level || !$plugin_cf['memberpages']['accessmode'])
    {
        if(!isset($_SESSION['username'],$_SESSION['accesslevel']))
        {
            header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_error']);
            exit;
        }
    }
    elseif($plugin_cf['memberpages']['accessmode']==1)
    {
        if(!isset($_SESSION['username'],$_SESSION['accesslevel'])
            || memberpages_session('accesslevel') != $level)
        {
            header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_error']);
            exit;
        }
    }
    elseif($plugin_cf['memberpages']['accessmode']==2)
    {
        if(!isset($_SESSION['username'],$_SESSION['accesslevel'])
               ||  memberpages_session('accesslevel') < $level)
        {
            header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_error']);
            exit;
        }
    }
    elseif($plugin_cf['memberpages']['accessmode'] > 2)
    {
        if(!isset($_SESSION['username'],$_SESSION['accesslevel'])
               ||  !(memberpages_session('accesslevel') & pow(2,$level-1)))
        {
            header('Location: '.CMSIMPLE_URL.'?'.$plugin_tx['memberpages']['page_error']);
            exit;
        }
    }
}
