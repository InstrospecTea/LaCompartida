<?php


 if (extension_loaded('newrelic')) {
 newrelic_set_appname ("PHPMyAdmin");
}
$cfg['LeftDisplayServers']      = true;
$cfg['LoginCookieValidity'] = 3600;
$cfg['LoginCookieStore'] = 7200;
$cfg['ShowServerInfo']             = TRUE;   
$cfg['ShowPhpInfo']           = TRUE;  

 $cfg['blowfish_secret'] = '4ff45ae26c9f46.08070353';
if($_SERVER['HTTP_HOST']=='db2.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb2.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb2.thetimebilling.com';

} else if($_SERVER['HTTP_HOST']=='db3.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb3.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb3.thetimebilling.com';

} else if($_SERVER['HTTP_HOST']=='db4.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb4.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb4.thetimebilling.com';

}  else if($_SERVER['HTTP_HOST']=='db0.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb0.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb0.thetimebilling.com';

}  else if($_SERVER['HTTP_HOST']=='db5.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb5.thetimebilling.com';
$cfg['Servers'][1]['controlhost'] = 'rdsdb5.thetimebilling.com';

} else if($_SERVER['HTTP_HOST']=='db1ro.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb1ro.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb1ro.thetimebilling.com';

} else if($_SERVER['HTTP_HOST']=='db2ro.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb2ro.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb2ro.thetimebilling.com';

} else if($_SERVER['HTTP_HOST']=='db3ro.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb3ro.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb3ro.thetimebilling.com';

} else if($_SERVER['HTTP_HOST']=='db4ro.thetimebilling.com') {
$cfg['Servers'][1]['host'] = 'rdsdb4ro.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb4ro.thetimebilling.com';

}  else {
$cfg['Servers'][1]['host'] = 'rdsdb1.thetimebilling.com';
 $cfg['Servers'][1]['controlhost'] = 'rdsdb1.thetimebilling.com';
}
$cfg['Servers'][1]['auth_type'] = 'cookie';
$cfg['Servers'][1]['connect_type'] = 'tcp';
$cfg['Servers'][1]['extension'] = 'mysqli';


 $cfg['Servers'][1]['controluser'] = 'pmauser';
 $cfg['Servers'][1]['controlpass'] = 'pma.,pass';


 $cfg['Servers'][1]['pmadb'] = 'phpmyadmin';
 $cfg['Servers'][1]['bookmarktable'] = 'pma_bookmark';
 $cfg['Servers'][1]['relation'] = 'pma_relation';
 $cfg['Servers'][1]['table_info'] = 'pma_table_info';
 $cfg['Servers'][1]['table_coords'] = 'pma_table_coords';
 $cfg['Servers'][1]['pdf_pages'] = 'pma_pdf_pages';
 $cfg['Servers'][1]['column_info'] = 'pma_column_info';
 $cfg['Servers'][1]['history'] = 'pma_history';
 $cfg['Servers'][1]['table_uiprefs'] = 'pma_table_uiprefs';
 $cfg['Servers'][1]['tracking'] = 'pma_tracking';
 $cfg['Servers'][1]['designer_coords'] = 'pma_designer_coords';
 $cfg['Servers'][1]['userconfig'] = 'pma_userconfig';
 $cfg['Servers'][1]['recent'] = 'pma_recent';
 
 
 
 //$cfg['LeftFrameDBTree']='true';
$cfg['Error_Handler']['display'] = true;
$cfg['Error_Handler']['gather'] = true;
 
 //$cfg['ThemeManager']        = TRUE;   
//$cfg['ThemePath']           = './themes'; 
$cfg['DefaultQueryTable']    = 'SELECT * FROM %t WHERE 1';
$cfg['SQP']['fmtType']      = 'html';  
$cfg['SQP']['fmtIndUnit']   = 'em';  

$cfg['Servers'][2]['host'] = $cfg['Servers'][2]['controlhost'] = 'rdsdb2.thetimebilling.com';
$cfg['Servers'][3]['host'] = $cfg['Servers'][3]['controlhost'] = 'rdsdb3.thetimebilling.com';
$cfg['Servers'][4]['host'] = $cfg['Servers'][4]['controlhost'] = 'rdsdb4.thetimebilling.com';
$cfg['Servers'][5]['host'] = $cfg['Servers'][5]['controlhost'] = 'rdsdb2ro.thetimebilling.com';
$cfg['Servers'][6]['host'] = $cfg['Servers'][6]['controlhost'] = 'rdsdb3ro.thetimebilling.com';
$cfg['Servers'][7]['host'] = $cfg['Servers'][7]['controlhost'] = 'rdsdb4ro.thetimebilling.com';  
 
 