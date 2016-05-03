<?php

class Archivo extends Objeto {

	public $editable_fields = ['archivo_nombre', 'archivo_tipo', 'archivo_data', 'archivo_s3'];

	public function __construct($sesion, $fields = "", $params = "") {
		$this->tabla = "archivo";
		$this->campo_id = "id_archivo";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->setFieldsAllowNull();
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
		return $this->Load($id_archivo);
	}

	function Upload($codigo_cliente, $id_contrato, $archivo_anexo = '') {
		$nombre = $archivo_anexo['name'];
		$archivoname = UtilesApp::slug(substr($nombre, 0, strpos($nombre, '.')));
		$archivoext = substr($nombre, stripos($nombre, '.'));
		$codigo_cliente = UtilesApp::slug($codigo_cliente);

		$name = "/{$codigo_cliente}/contrato_{$id_contrato}/{$archivoname}{$archivoext}";

		if (UtilesApp::FileExistS3($name)) {
			return false;
		}

		$url_s3 = UtilesApp::UploadToS3($name, $archivo_anexo['tmp_name'], $archivo_anexo['type']);
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
