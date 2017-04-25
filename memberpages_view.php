<?php
/**
 * Memberpages
 * @author svasti
 * @link http://svasti.de/
 */

if (!(defined('CMSIMPLE_XH_VERSION') || defined('CMSIMPLE_VERSION'))) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


function memberpages_view($page){
	global $cf, $c, $cl, $h, $hjs, $l, $plugin, $plugin_cf, $plugin_tx, $pth, $sl, $sn, $su, $tx, $u, $pd_router;

    $view = "\n\n<!-- Memberpages Plugin -->\n";

    $tabID = defined('CMSIMPLE_VERSION') || strpos(CMSIMPLE_XH_VERSION,'1.5')
           ? 'tab_M'
           : 'xh_tab_memberpages_view' ;

    if($page['mpage']){

        $view .=  '<script type="text/javascript">
                                // <![CDATA[
                   document.getElementById("'.$tabID.'").style.color = "'.$plugin_cf['memberpages']['color_pagedata_tab'].'";
                   document.getElementById("'.$tabID.'").style.fontWeight = "bold";
                   document.getElementById("'.$tabID.'").style.fontSize = "120%";';

        if($page['mplevel'] && $plugin_cf['memberpages']['accessmode']){
            $view .=  'document.getElementById("'.$tabID.'").innerHTML = "M-'.$page['mplevel'].'";';
        }

        $view .=  '             // ]]>
                                </script>';
    }


	$view .= "\n".'<form action="'.$sn.'?'.$su.'" method="post" id="memberpages" name="memberpages">';
	$view .= "\n".'<p><b>'.'Memberpages'.'</b></p>' . "\n";

    $checked =($page['mpage'] == '1')? ' checked':'';
    $view .= '<p>'
          .  '<input type="hidden" name="mpage" value="0">'
          .  '<input type="checkbox" name="mpage" value="1"' . $checked . '>'
          .  ' ' . $plugin_tx['memberpages']['pd_is_memberpage'].'</p>';

    if($plugin_cf['memberpages']['accessmode']) {
        $select = '';
        $x = 0;
        $end = $plugin_cf['memberpages']['selectable_levels']>5 ? $plugin_cf['memberpages']['selectable_levels'] : 5;
        foreach (range(0,$end) as $key=>$value) {
            $selected = '';
            if($page['mplevel'] == $value) {$selected = ' selected';$x++;}
            $select .= "\n".'<option value="' . $value . '"' . $selected.'> &nbsp; '. $value.' &nbsp; </option>';
        }
        if(!$x) $select = "\n".'<option value="'
                        . $page['mplevel']
                        . '" selected>'
                        . $page['mplevel']
                        . '</option>'
                        . $select;

        $view .= '<p><select name="mplevel">'
              .  "\n" . $select . "\n</select>"
              .  ' ' . $plugin_tx['memberpages']['pd_access_level'] . '</p>';
    }


	$view .= "\n\t" . '<input name="save_page_data" type="hidden">';
	$view .= "\n\t\t" . '<input type="submit" value="' . ucfirst($tx['action']['save']) . '">' . '<br>';
	$view .= "\n" . '</form>';
	return $view;
}
?>
