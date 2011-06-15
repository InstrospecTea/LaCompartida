<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once 'Cobro.php';
require_once 'Cliente.php';
require_once 'Asunto.php';
require_once 'CobroMoneda.php';
require_once 'MontoEnPalabra.php';

class ContratoDocumentoLegal extends Objeto
{

	function ContratoDocumentoLegal($sesion, $fields = "", $params = "")
	{
		$this->tabla = "contrato_documento_legal";
		$this->campo_id = "id_contrato_documento_legal";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	function EliminarDocumentosLegales($sesion, $id_contrato = null, $defecto=false) {
		if (empty($id_contrato) && !$defecto or empty($sesion))
			return false;
		$query = "DELETE FROM contrato_documento_legal WHERE id_contrato ".($id_contrato ? "= $id_contrato" : "IS NULL");
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		return true;
	}
}
