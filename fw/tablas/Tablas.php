<?php

/**
 *
 */
class Tablas {

    public $Sesion;
    private $describes = array();

    public function __construct($Sesion) {
        $this->Sesion = $Sesion;
    }

    /**
     * Dibuja tabla con la data de la tabla.
     * @param string $tabla
     * @param int $failsafe
     * @return string
     */
    public function printTable($tabla, $failsafe = 0) { //AGREGO FLAG FAILSAFE PARA QUE NO TIRE ERROR
        $query = $this->selectQuery($tabla);

        if ($failsafe == 0) {
            $sesion = &$Sesion;
            $resp = mysql_query($query, $this->Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->Sesion->dbh);
        } else {
            $resp = mysql_query($query, $this->Sesion->dbh);
        }
        $trs = array();
        if ($resp) {
            $primaryKey = $this->primaryKey($tabla);
            $i = 0;
            while ($fila = mysql_fetch_assoc($resp)) {
                if ($i++ == 0) {
                    $thead = self::Encabezado($fila);
                }
                $trs[] = self::getTr($fila, $tabla, $primaryKey);
            }
        }
        $table = sprintf('<table cellpadding="3" class="buscador" style="width: 100%%" id="%s"><thead>%s</thead><tbody>%s</tbody></table>', $tabla, $thead, implode('', $trs));
        return $table;
    }

    /**
     * devuelve array de llaves foraneas o FALSE
     * @param string $tabla
     * @return variant
     */
    public function GetForeignKeys($tabla) {
        $query = "SHOW CREATE TABLE $tabla";
        $sesion = &$Sesion;
        $res = mysql_query($query, $this->Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->Sesion->dbh);
        $row = mysql_fetch_assoc($res);
        $elements = explode(',', $row['Create Table']);
        $constraints = array();
        if (count($elements) > 0) {
            $foreignKeyArr = array();
            foreach ($elements as $element) {
                if (ereg('CONSTRAINT', $element)) {
                    array_push($constraints, $element);
                }
            }
            if (count($constraints) > 0) {
                foreach ($constraints as $constraint) {
                    preg_match_all('/`(.*?)`/', $constraint, $tmp);
                    $foreignKeyArr[$tmp[1][1]] = array(
                        'table' => $tmp[1][2],
                        'field' => $tmp[1][3],
                    );
                }
            }
        } else {
            return false;
        }

        return $foreignKeyArr;
    }

    /**
     * Devielve nombre de la llave primaria de la tabla.
     * @param string $tabla
     * @return string
     */
    public function primaryKey($tabla) {
        $describe = $this->describe($tabla);
        foreach ($describe as $field) {
            if ($field['Key'] == 'PRI') {
                $pk = $field['Field'];
                break;
            }
        }
        return $pk;
    }

    /**
     * Devuelve definición de la tabla.
     * @param string $tabla
     * @return array
     */
    public function describe($tabla) {
        if (isset($this->describes[$tabla])) {
            return $this->describes[$tabla];
        }
        $query = "DESC $tabla";
        $resp = mysql_query($query, $this->Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->Sesion->dbh);
        $describe = array();
        while ($field = mysql_fetch_assoc($resp)) {
            $describe[$field['Field']] = $field;
        }
        $this->describes[$tabla] = $describe;

        return $describe;
    }

    /**
     * Devuelve primer campo 'string' de la tabla, o el mismo campo id
     * @param string $tabla
     * @param int $id
     * @return string
     */
    public function asText($tabla, $id) {
        $describe = $this->describe($tabla);
        $text = $id;
        foreach ($describe as $field_name => $field) {
            if (stripos($field['Type'], 'char') !== false) {
                $text = $field_name;
                break;
            }
        }
        return $text;
    }

    /**
     * Crea una Query para la tabla, joineando segun llaves foraneas.
     * @param string $table
     * @return string
     */
    public function selectQuery($table) {
        $describe = $this->describe($table);
        $fkeys = $this->GetForeignKeys($table);
        $selectFields = array();
        $joins = array();
        $renameNumber = 0;
        foreach ($describe as $field_name => $field) {

            if (isset($fkeys[$field_name])) {
                $alias = 't_' . $renameNumber;
                $fk_table = $fkeys[$field_name]['table'];
                $fk_id = $fkeys[$field_name]['field'];
                $fk_glosa = $this->asText($fk_table, $fk_id);
                $fk_describe = $this->describe($fk_table);
                $fk_where = isset($fk_describe['activo']) ? sprintf('AND %s.activo', $alias) : '';
                $selectFields[] = sprintf('%s.%s AS %s', $alias, $fk_glosa, $field_name);
                $join = ($field['Null'] == 'YES' ? 'LEFT' : 'INNER') . ' JOIN';
                $joins[] = sprintf('%s %s AS %s ON %s.%s = %s.%s %s', $join, $fk_table, $alias, $alias, $fk_id, $table, $field_name, $fk_where);
                $renameNumber++;
            } else {
                $selectFields[] = sprintf('%s.%s', $table, $field_name);
            }
        }
        $sFields = implode(', ', $selectFields);
        $sJoins = implode(' ', $joins);
        $query = "SELECT $sFields FROM $table $sJoins";
        return $query;
    }

    /**
     *
     * @param type $data
     * @param boolean $nuevo
     * @return string
     */
    public static function arrayToQuerySet($data, $nuevo) {
        $t = array();
        foreach ($data as $key => $value) {
            $value = "'$value'";
            if ($key == 'fecha_modificacion') {
                $value = 'now()';
            } else if ($nuevo && $key == 'fecha_creacion') {
                $value = 'now()';
            } else if (is_null($data[$key])) {
                $value = 'NULL';
            }
            $t[] = "$key = $value";
        }
        return implode(', ', $t);
    }

    /**
     * Prepara datos antes de guardar.
     * @param string $tabla
     * @param array $data
     * @return array
     */
    public function prepare($tabla, $data) {
        $desc = $this->describe($tabla);
        foreach ($data as $field => $value) {
            $f = $desc[$field];
            if ($f['Null'] == 'YES' && is_null($f['Default']) && $value == '') {
                $data[$field] = null;
            }
        }
        return $data;
    }

    /**
     * Devuelve HTML con TR de datos de la fila indicada.
     * @param array $fila
     * @param string $tabla
     * @param string $primaryKey
     * @return string
     */
    public static function getTr($fila, $tabla, $primaryKey) {
        $id = 0;
        $tds = array();
        foreach ($fila as $key => $value) {
            if ($primaryKey == $key) {
                $id = $value;
                $tds[] = sprintf('<td style="color: #666; font-weight: bold;">%s</td>', $value);
            } else {
                $tds[] = sprintf('<td>%s</td>', $value);
            }
        }
        return sprintf('<tr class="editable" data-row-id="%s" id="%s" >%s</tr>', $id, "{$tabla}_{$id}", implode('', $tds));
    }

    /**
     * Devuelve encabezdos de tabla.
     * @param array $fila
     * @return string
     */
    public static function Encabezado($fila) {
        $tds = array();
        foreach ($fila as $key => $value) {
            $tds[] = sprintf('<td class="encabezado">%s</td>', $key);
        }
        return sprintf('<tr class="encabezado">%s</tr>', implode('', $tds));
    }

    /**
     * Verifica existencia de la tabla.
     * @param type $tabla
     * @return type
     */
    public function exists($tabla) {
        $query = "DESC $tabla";
        $resp = mysql_query($query, $this->Sesion->dbh);
        return $resp !== false;
    }

}
