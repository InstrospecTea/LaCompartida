<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Archivo extends Objeto
{
	function Archivo($sesion, $fields = "", $params = "")
	{
		$this->tabla = "archivo";
		$this->campo_id = "id_archivo";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	function Check()
	{
		if( $this->changes['archivo_data'] )
		{
			$val = $this->fields['archivo_data'];
			if( $val['size'] > 16000000 )
			{
				$this->error = 'El tamaño del archivo es muy grande (Máx: 16Mb)';
				return false;
			}
			else
			{
				$archivo = fopen($val['tmp_name'],"r");
				$contenido = fread($archivo, filesize($val['tmp_name']) );
				fclose($archivo);

				$this->fields['archivo_data'] = addslashes($contenido);
				$this->Edit('data_tipo', $val['type'] );
				$this->Edit('archivo_nombre', $val['name']);
			}
		}
		return true;
	}
	function Eliminar($id_archivo)
	{
		$query = "DELETE FROM archivo WHERE id_archivo='$id_archivo'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
	
	function LoadById($id_archivo)
	{
		$query = "SELECT id_archivo FROM archivo WHERE id_archivo='$id_archivo' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
}
?>