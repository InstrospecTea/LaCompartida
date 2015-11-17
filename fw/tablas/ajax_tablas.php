<?php

require_once dirname(__FILE__) . '/../../app/conf.php';
require_once 'Tablas.php';

$Sesion = new Sesion('');
$Tablas = new Tablas($Sesion);
$ret = array('success' => false);
switch ($accion) {
    case 'eliminar_registro':
        $primaryKey = $Tablas->primaryKey($tabla);
        $query = sprintf("DELETE FROM `%s` WHERE `%s` = '%s'", $tabla, $primaryKey, $_POST['id']);
        $resp = mysql_query($query, $Sesion->dbh);
        if ($resp) {
            $ret['success'] = true;
        }
        break;
    case 'guardar':
        $describe = $Tablas->describe($tabla);
        $primaryKey = $Tablas->primaryKey($tabla);

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
        $ret = array(
            'new' => $id,
        );

        if (!$id) {
            $where = '';
            $queryAction = 'INSERT INTO';
        } else {
            $where = sprintf("WHERE `%s` = '%s'", $primaryKey, $id);
            $queryAction = 'UPDATE';
        }

        $data = $Tablas->prepare($tabla, array_map(utf8_decode, $data));
        $querySet = $Tablas->arrayToQuerySet($data, $nuevo);
        $query = "$queryAction $tabla SET $querySet $where";
        $resp = mysql_query($query, $Sesion->dbh);

        if (!$id && $describe[$primaryKey]['Extra'] == 'auto_increment') {
            $id =  mysql_insert_id($Sesion->dbh);
        } else if (!$id && $describe[$primaryKey]['Key'] == 'PRI') {
            $id = $data[$primaryKey];
        }

        if (!$resp) {
            $ret = array(
                'success' => false,
                'error' => mysql_error($Sesion->dbh)
            );
        } else {
            $ret['table'] = $tabla;

            if ($resp) {
                $ret['success'] = true;
                $query = sprintf("SELECT * FROM `%s` WHERE `%s` = '%s'", $tabla, $primaryKey, $id);
                $resp = mysql_query($query, $Sesion->dbh);
                $row = mysql_fetch_assoc($resp);
                $ret['row_id'] = "{$tabla}_{$row[$primaryKey]}";
                $ret['row'] = Tablas::getTr($row, $tabla, $primaryKey);
            }
        }
        break;

    case 'eliminar':
        if (empty($_POST['id'])) {
            break;
        }
        $resp = mysql_query("DELETE FROM prm_mantencion_tablas WHERE id_tabla = '{$_POST['id']}'", $Sesion->dbh);
        $ret['success'] = ($resp !== false);
        break;
}
echo json_encode(array_map(utf8_encode, $ret));
