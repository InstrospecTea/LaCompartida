<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class GastoGeneral extends Objeto
{
    function GastoGeneral($sesion, $fields = "", $params = "")
    {
        $this->tabla = "gasto_general";
        $this->campo_id = "id_gasto_general";
        #$this->guardar_fecha = false;
        $this->sesion = $sesion;
        $this->fields = $fields;
    }
}
class ListaGastosGenerales extends Lista
{
    function ListaGastosGenerales($sesion, $params, $query)
    {
        $this->Lista($sesion, 'GastoGeneral', $params, $query);
    }
}
?>
