<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';

class DocumentoLegalNumero extends Objeto {
	function DocumentoLegalNumero($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_doc_legal_numero';
		$this->campo_id = 'id_doc_legal_numero';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	function SeriesQuery($id_estudio = null) {
		$where = '1';

		if (!empty($id_estudio)) {
			$where .= " AND `{$this->tabla}`.`id_estudio` = '{$id_estudio}'";
		}

		return "SELECT DISTINCT `{$this->tabla}`.`serie` FROM `{$this->tabla}` WHERE $where";
	}

	function SeriesPorTipoDocumento($tipo_documento_legal, $primero = false) {
		$lista = array();
		$tipo_documento_legal = mysql_real_escape_string($tipo_documento_legal);
		$query = "SELECT DISTINCT serie FROM {$this->tabla} WHERE id_documento_legal = $tipo_documento_legal";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while($fields = mysql_fetch_assoc($resp)) {
			$lista[] = $fields['serie'];
		}

		if ($primero) {
			return $lista[0];
		}

		return $lista;
	}

	function UltimosNumerosSerie($tipo_documento_legal) {
		$lista = array();
		$tipo_documento_legal = mysql_real_escape_string($tipo_documento_legal);
		$query = "SELECT serie, numero_inicial AS numero, id_estudio AS estudio FROM {$this->tabla} WHERE id_documento_legal = $tipo_documento_legal";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while($fields = mysql_fetch_assoc($resp)) {
			$lista[] = $fields;
		}

		return $lista;
	}
}
