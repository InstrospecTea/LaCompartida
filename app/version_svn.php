<?php
if ($_GET["show"] == 1) {
	exec("svn info 2>&1", $output, $return_var); 
	foreach ($output as $o) { if ($revision = strstr($o, "Revision:")) { echo "Ver." . str_replace("Revision:", "", trim($revision)); exit; }}
}
?>