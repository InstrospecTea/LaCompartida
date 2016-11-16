<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
require_once Conf::ServerDir() . '/../fw/funciones/funciones.php';
require_once 'Tablas.php';

$Sesion = new Sesion('');
$Tablas = new Tablas($Sesion);

$fkeys = $Tablas->GetForeignKeys($tabla);
$primaryKey = $Tablas->primaryKey($tabla);

$nuevo = filter_input(INPUT_POST, 'nuevo', FILTER_SANITIZE_STRING);

if (!empty($id)) {
    $query = sprintf("SELECT * FROM `%s` WHERE `%s` = '%s'", $tabla, $primaryKey, $id);
    $resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
    $datos = mysql_fetch_assoc($resp);
}
?>
<form action="ajax_tablas.php" method="post">
    <input type="hidden" name="tabla" value="<?php echo $tabla; ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="accion" value="guardar">
    <table width="96%">
        <colgroup><col style="width: 35%"/><col style="width: 65%"/></colgroup>
            <?php
            $describe = $Tablas->describe($tabla);

            foreach ($describe as $field_name => $field) {
                $valor = empty($datos[$field_name]) ? '' : $datos[$field_name];
                $extra = '';
                $input = $id;

                if ($field['Key'] == 'PRI' || $field['Field'] == 'fecha_creacion' || $field['Field'] == 'fecha_modificacion') {

                    if ($field['Extra'] == 'auto_increment') {
                        $input = empty($id) ? 'nuevo' : $valor;
                    } else if ($field['Key'] == 'PRI' && $nuevo == ''){
                        $input = sprintf('<input type="text" name="data[%s]" value="" />', $field_name);
                    }

                    if ($field['Field'] == 'fecha_creacion' || $field['Field'] == 'fecha_modificacion') {
                        $input = sprintf('<input type="hidden" name="data[%s]" value="%s" />', $field_name, $valor);
                    }
                } else if (stristr($field['Type'], 'tinyint')) {
                    $checked = $valor ? 'checked="checked"' : '';
                    $input = sprintf('<input type="hidden" name="data[%s]" value="0" />', $field_name);
                    $input .= sprintf('<input type="checkbox" name="data[%s]" value="1" %s/>', $field_name, $checked);
                } else if (stristr($field['Type'], 'text')) {
                    $input = sprintf('<textarea name="data[%s]" style="width: 98%%; height: 5em;">%s</textarea>', $field_name, $valor);
                } else {
                    if (stristr($field['Type'], 'double') || stristr($field['Type'], 'int') || stristr($field['Type'], 'float')) {
                        $extra = 'onkeypress="return isNumberKey(event)" size="6"';
                    }
                    $input = sprintf('<input type="text" name="data[%s]" value="%s" style="width: 98%%;" %s/>', $field_name, $valor, $extra);

                    if (isset($fkeys[$field_name])) {
                        $fk_table = $fkeys[$field_name]['table'];
                        $fk_id = $fkeys[$field_name]['field'];
                        $fk_glosa = $Tablas->asText($fk_table, $fk_id);
                        $fk_describe = $Tablas->describe($fk_table);
                        $fk_where = isset($fk_describe['activo']) ? 'WHERE activo' : '';
                        $query = sprintf('SELECT %s, %s FROM %s %s ORDER BY %s', $fk_id, $fk_glosa, $fk_table, $fk_where, $fk_glosa);
                        $input = Html::SelectQuery($Sesion, $query, "data[$field_name]", $valor, '', ' ');
                        $field_name = "$field_name ($fk_glosa)";
                    }
                }

                printf('<tr><td style="text-align: right; font-weight: bold;">%s: </td><td style="text-align: left;">%s</td></tr>', $field_name, $input);
            }
            ?>
        <tr>
            <td align="center" colspan="2">
                <br/>
                <input type="button" value="<?php echo __('Guardar') ?>" onclick="guardar_tabla(this.form)">
                <input type="button" value="<?php echo __('Cancelar') ?>" onclick="cerrar_tabla(this.form)">
            </td>
        </tr>
    </table>
</form>