<?php
header('Content-type: application/x-ms-application');
header('Content-Disposition: filename="TimeTracking.application"');
echo file_get_contents( urldecode($_GET['host']) . '/cliente_windows/TimeTracking.application?titulo=' . $_GET['titulo'] . '&host=' . $_GET['host'] . '&titulo_asunto=' . $_GET['titulo_asunto']);
?>