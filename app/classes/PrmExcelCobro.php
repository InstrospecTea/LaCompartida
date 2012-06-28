<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class PrmExcelCobro extends Objeto
{
	function PrmExcelCobro($sesion, $fields = "", $params = "")
	{
		$this->tabla = "prm_excel_cobro";
		$this->campo_id = "id_prm_excel_cobro";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}
}

class ListaPrmExcelCobro extends Lista
{
    function ListaPrmExcelCobro($sesion, $params, $query)
    {
        $this->Lista($sesion, 'PrmExcelCobro', $params, $query);
    }
}
