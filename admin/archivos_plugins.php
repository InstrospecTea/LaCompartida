<?php

require_once dirname(__FILE__) . '/../app/conf.php';

$Sesion = new Sesion(array('ADM'));

echo 'Hemos detectado los siguientes plugins<br><br>';
echo '<form id="formplugins">';
echo '<ul class="buttonset" id="plugins" style="list-style:none;">';


$archivos = array();
$maxid = 0;
$orden = 0;

$plugins = $Sesion->pdodbh->query('select * from prm_plugin order by activo desc, orden ASC, archivo_nombre');

$plugin_html = '<li><input type="checkbox" class="checkbox" id="%s_%s" name="%s" value="1" %s/><label for="%s_%s">%s</label><span class="updown ui-icon ui-icon-arrowthick-2-n-s"></span></li>' . "\n";

while ($plugin = $plugins->fetch(PDO::FETCH_ASSOC)) {
	$id = $plugin['id_plugin'];
	$orden = $plugin['orden'];
	$archivo = $plugin['archivo_nombre'];
	$activo = $plugin['activo'];
	$archivos[] = $archivo;
	$hEntryName = ucwords(str_replace('_', ' ', $archivo));
	printf($plugin_html, $id, $archivo, $archivo, ($activo ? 'checked="checked"' : ''), $id, $archivo, $hEntryName);
}

$eldirectorio = Conf::ServerDir() . '/plugins/';

if ($myDirectory = opendir($eldirectorio)) {

	while ($entryName = readdir($myDirectory)) {
		if (!in_array($entryName, $archivos) && is_file(Conf::ServerDir() . '/plugins/' . $entryName)) {
			$Sesion->pdodbh->exec("insert into prm_plugin (archivo_nombre, orden, activo) values ('$entryName',0,0)");
			$lastId = $Sesion->pdodbh->lastInsertId();
			$hEntryName = ucwords(str_replace('_', ' ', $entryName));
			printf($plugin_html, $lastId, $entryName, $entryName, '', $lastId, $entryName, $hEntryName);
		}
	}

	closedir($myDirectory);
}

echo '</ul>';
echo '<input type="hidden" id="cantidad" name="cantidad" value="' . $maxid . '"/>';
echo '<input type="hidden" id="accion" name="accion" value="actualiza_plugins"/>';
echo '</form>';
