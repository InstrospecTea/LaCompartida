<?
require_once dirname(__FILE__).'/../conf.php';

class PrmCodigo extends Objeto
{

    var $sesion = null;


    function PrmCodigo($sesion, $fields = "", $params = array())
    {
        $this->tabla = "prm_codigo";
        $this->campo_id = "id_codigo";
        if (isset($params['grupo']) && !is_null($params['grupo'])) {
            $this->grupo = $params['grupo'];
        } else {
            $this->grupo = "";
        }
        $this->guardar_fecha = false;
        $this->sesion = $sesion;
        $this->fields = $fields;
    }

    function nextCode()
    {
        $query = "SELECT CONCAT(SUBSTRING(codigo, 1,1), MAX(CONVERT(SUBSTRING(codigo, 2), SIGNED))+1) AS codigo
                    FROM prm_codigo WHERE grupo = 'PRM_FACTURA_MX_METOD';";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        list($next) = mysql_fetch_array($resp);
        return $next;
    }

    function query($where = "")
    {
        if ($where == "") {
            $where = "1";
        }
        $query = "SELECT SQL_CALC_FOUND_ROWS *
                    FROM prm_codigo
                    WHERE $where AND grupo = '{$this->grupo}'";
        return $query;
    }

    function id($id=null){
        if($id) $this->fields[$this->campo_id] = $id;
        if(empty($this->fields[$this->campo_id])) return false;
        return $this->fields[$this->campo_id];
    }


}


