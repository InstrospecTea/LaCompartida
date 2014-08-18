<?php
$archivos = array();
$maxid = 0;
$orden = 0;

$query = 'SELECT * FROM prm_lang ORDER BY orden ASC';
$resultado = mysql_query($query, $Sesion->dbh);
echo '<form id="formlangs"><ul class="sortable buttonset" id="langs">';

while ($archivo = mysql_fetch_array($resultado)) {
	$maxid = $archivo[0];
	$orden = $archivo[2];
	$archivos[$archivo[1]] = $archivo[1];

	echo '<li><input type="checkbox" class="checkbox" id="' . $archivo[0] . '_' . $archivo[1] .'" name="' . $archivo[1] . '" value="1" ' . ($archivo[3]? 'checked="checked"' : '') . '" /><label for="' . $archivo[0] . '_' . $archivo[1] . '">' . $archivo[1] . '</label> <span class="updown ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
}

$myDirectory = opendir(Conf::ServerDir() . '/lang/');

while ($entryName = readdir($myDirectory)) {
	if (!array_key_exists($entryName, $archivos) && is_file(Conf::ServerDir() . '/lang/' . $entryName)) {
		echo '<li><input type="checkbox" class="checkbox" id="' . ++$maxid . '_' . $entryName . '" name="' . $entryName . '" value="1" /><label for="' . $maxid . '_' . $entryName . '">' . $entryName . '</label> <span class="updown ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
	}
}

// close directory
closedir($myDirectory);

echo '</ul>';
echo '<input type="hidden" id="cantidad" name="cantidad" value="' . $maxid . '"/>';
echo '<input type="hidden" id="accion" name="accion" value="actualiza_langs"/>';
echo '</form>';
