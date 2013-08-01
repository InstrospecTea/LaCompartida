<?php
require_once dirname(__FILE__) . '/../conf.php';

class PrmEstudio extends Objeto
{
	function PrmEstudio($sesion, $fields = "", $params = "")
	{
		$this->tabla = "prm_estudio";
		$this->campo_id = "id_estudio";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	/**
	 * Obtiene todos los estudios del sistema para ser utilizadas en Select
	 *
	 * @param Sesion $Sesion
	 * @param integer $id_estudio
	 * @param boolean $como_objeto
	 * @return array Arreglo con monedas para ser usados en Selects
	 */
	public static function GetEstudios(Sesion $Sesion, $id_estudio = '', $como_objeto = false, $mostrar_todos = false) {
		$query = "SELECT
					prm_estudio.id_estudio,
					prm_estudio.glosa_estudio,
					prm_estudio.metadata_estudio,
					prm_estudio.visible
				FROM prm_estudio
				WHERE 1";

		if (!empty($id_estudio)) {
			$query .= " AND id_estudio = '$id_estudio'";
		}

		if (!$mostrar_todos) {
			$query .= " AND visible = '1'";
		}

		$r = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
		$estudios = array();

		if ($como_objeto) {
			while (list($id_estudio, $glosa_estudio, $metadata_estudio, $visible) = mysql_fetch_array($r)) {
				$estudios[$id_estudio]['id_estudio'] = $id_estudio;
				$estudios[$id_estudio]['glosa_estudio'] = $glosa_estudio;
				$estudios[$id_estudio]['metadata_estudio'] = $metadata_estudio;
				$estudios[$id_estudio]['visible'] = $visible;
			}
		} else {
			while ($estudio = mysql_fetch_array($r)) {
				$estudios[] = $estudio;
			}
		}

		return $estudios;
	}

}