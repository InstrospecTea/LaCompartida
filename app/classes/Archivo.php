<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

class Archivo extends Objeto {

	function Archivo($sesion, $fields = "", $params = "") {
		$this->tabla = "archivo";
		$this->campo_id = "id_archivo";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function Check() {
		if ($this->changes['archivo_data']) {
			$val = $this->fields['archivo_data'];
			if ($val['size'] > 16000000) {
				$this->error = 'El tamaño del archivo es muy grande (Máx: 16Mb)';
				return false;
			} else {
				$archivo = fopen($val['tmp_name'], "r");
				$contenido = fread($archivo, filesize($val['tmp_name']));
				fclose($archivo);

				$this->fields['archivo_data'] = addslashes($contenido);
				$this->Edit('data_tipo', $val['type']);
				$this->Edit('archivo_nombre', $val['name']);
			}
		}
		return true;
	}

	function Eliminar($id_archivo) {
		$query = "DELETE FROM archivo WHERE id_archivo='$id_archivo'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function LoadById($id_archivo) {
		$query = "SELECT id_archivo FROM archivo WHERE id_archivo='$id_archivo' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function Upload($codigo_cliente, $id_contrato, $archivo_anexo = '') {
		$archivo_subir = $archivo_anexo['tmp_name'];
		$subir = fopen($archivo_subir, 'r');
		$contenido = fread($subir, filesize($archivo_subir));
		fclose($subir);

		$nombre = $archivo_anexo['name'];
		$archivoname = UtilesApp::slug(substr($nombre, 0, strpos($nombre, '.')));
		$archivoext = substr($nombre, stripos($nombre, '.'));
		$codigo_cliente = UtilesApp::slug($codigo_cliente);

		$name = "/{$codigo_cliente}/contrato_{$id_contrato}/{$archivoname}{$archivoext}";

		if (UtilesApp::FileExistS3($name)) {
			return false;
		}

		$url_s3 = UtilesApp::UploadToS3($name, $contenido, $archivo_anexo['type']);
		$this->Edit('archivo_nombre', $archivo_anexo['name']);
		$this->Edit('data_tipo', $archivo_anexo['type']);

		return $url_s3;
	}

	function Download($id_archivo) {
		if (!empty($id_archivo)) {
			if ($this->Load($id_archivo)) {
				if (!empty($this->fields['archivo_s3'])) {
					header("Location: {$this->fields['archivo_s3']}");
				} else {
					$contenido = stripslashes($this->fields['archivo_data']);
					header("Content-type: {$this->fields['archivo_tipo']}");
					header('Content-Length: ' . strlen($contenido));
					header("Content-Disposition: attachment; filename='{$this->fields['archivo_nombre']}'");
					echo $contenido;
				}
			}
		}
	}

}
