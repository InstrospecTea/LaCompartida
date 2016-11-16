<?php
	require_once dirname(__FILE__).'/../../app/conf.php';
	require_once dirname(__FILE__).'/../classes/Utiles.php';
	require_once dirname(__FILE__).'/../classes/Lista.php';

class Usuario
{
	// Sesion PHP
	var $sesion = null;

	// Boolean que indica si la info. es cargada desde BD
	var $loaded = false;

	// Arreglo con los valores de los campos
	var $fields = null;

	// Arreglo que indica los campos con cambios
	var $changes = null;

	// Permisos
	var $permisos = null;

	// String con el último error
	var $error = "";

	// Variables de conexion
	var $tbl_usuario = 'usuario';

	function Usuario($sesion, $rut=null)
	{
		$this->sesion =& $sesion;
		global $tbl_usuario_permiso, $tbl_prm_permiso;

		$tbl_usuario_permiso = 'usuario_permiso';
				$tbl_prm_permiso = 'prm_permisos';

		if( method_exists('Conf','TablaJuicios') && Conf::TablaJuicios() )
		{
				$tbl_usuario_permiso = 'j_usuario_permiso';
				$tbl_prm_permiso = 'j_prm_permisos';
		}

		if($rut != null)
			$this->Load($rut);
	}

	function Edit($field, $value)
	{
		$this->fields[$field] = $value;
		$this->changes[$field] = true;
	}

	function EditPermisos($permiso)
	{
			global $tbl_usuario_permiso;
		$id_usuario = $this->fields['id_usuario'];
		$codigo_permiso = $permiso->fields['codigo_permiso'];
		if($id_usuario == "" or $codigo_permiso == "")
		{
			$this->error = "El Username o el Codigo de permiso están vacíos.";
			return false;
		}
		if($permiso->fields['permitido'])
		{
			$query .= "INSERT INTO ".$tbl_usuario_permiso." SET id_usuario=$id_usuario, codigo_permiso='$codigo_permiso' ON DUPLICATE KEY UPDATE id_usuario=$id_usuario";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
//          Utiles::CrearLog($this->sesion, "".$tbl_usuario_permiso."", $rut, "INSERTAR", $codigo_permiso, "", $query);
		}
		else
		{
			$query .= "DELETE FROM ".$tbl_usuario_permiso." WHERE id_usuario=$id_usuario and codigo_permiso='$codigo_permiso'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
//          Utiles::CrearLog($this->sesion, "".$tbl_usuario_permiso."", $rut, "ELIMINAR", $codigo_permiso, "", $query);
		}
		return true;
	}
	function PermisoALL()
	{
			global $tbl_usuario_permiso;
		$id_usuario = $this->fields['id_usuario'];
		$query = "INSERT INTO ".$tbl_usuario_permiso." SET id_usuario=$id_usuario, codigo_permiso='ALL' ON DUPLICATE KEY UPDATE id_usuario=$id_usuario ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	}

	function Write() {
		$this->error = '';
		$fields = '';

		if ($this->loaded) {
			$this->Edit('fecha_edicion', 'NOW()');
		} else {
			$this->Edit('fecha_creacion', 'NOW()');
		}

		foreach ($this->fields as $key => $val) {
			if ($this->changes[$key]) {
				if (!empty($fields)) {
					$fields .= ', ';
				}

				if ($val == 'NULL') {
					$fields .= "{$key} = NULL";
				} else if ($val == 'NOW()') {
					$fields .= "{$key} = NOW()";
				} else {
					$val = mysql_real_escape_string($val);
					$fields .= "{$key} = '{$val}'";
				}
			}
		}

		if ($this->loaded) {
			$query = "UPDATE {$this->tbl_usuario} SET {$fields} WHERE id_usuario = '{$this->fields['id_usuario']}'";
			$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			/*if (!$rs) {
				$this->error = 'No se pudo agregar al nuevo usuario';
			}*/
		} else {
			$query = "INSERT INTO {$this->tbl_usuario} SET {$fields}";
			$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			$this->fields['id_usuario'] = mysql_insert_id($this->sesion->dbh);
			$this->LoadId($this->fields['id_usuario']);

			/*if ($rs) {
				$this->fields['id_usuario'] = mysql_insert_id($this->sesion->dbh);
				$rs = $this->LoadId($this->fields['id_usuario']);
				if (!$rs) {
					$this->error = 'No se pudo cargar los datos del nuevo usuario';
				}
			} else {
				$this->error = 'No se pudo editar los datos datos del usuario';
			}*/

		}

		/* return $rs; */
		return true;
	}

	function LoadPermisos($id_usuario)
	{
			global $tbl_usuario_permiso, $tbl_prm_permiso;
		#Este query crea una lista con todos los permisos y con un campo permiso que vale > 1 si es que efectivamente el rut tiene ese privilegio
		$query = "SELECT prm . * , SUM( 1 - ( usr.id_usuario <> '$id_usuario' OR usr.id_usuario IS NULL ) ) AS permitido
					FROM ".$tbl_prm_permiso." AS prm LEFT JOIN ".$tbl_usuario_permiso." AS usr
						ON usr.codigo_permiso = prm.codigo_permiso
					WHERE prm.codigo_permiso <> 'ALL'
					GROUP BY prm.codigo_permiso
					ORDER BY prm.codigo_permiso ASC";
		$this->permisos = new ListaPermisos( $this->sesion,'', $query);
	}

	function Login()
	{
		$query = "UPDATE ".$this->tbl_usuario." SET ultimo_ingreso=NOW() WHERE id_usuario='".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	}

	function Load($rut)	{
		$query = "SELECT * FROM ".$this->tbl_usuario." WHERE rut='$rut'";
		return $this->LoadWithQuery($query);
	}

	function LoadId($id_usuario) {
		$query = "SELECT * FROM ".$this->tbl_usuario." WHERE id_usuario='$id_usuario'";
		return $this->LoadWithQuery($query);
	}

	function LoadByNick($username) {
		$query = "SELECT * FROM ".$this->tbl_usuario." WHERE username='$username'";
		return $this->LoadWithQuery($query);
	}

	function LoadByEmail($email) {
		$query = "SELECT * FROM ".$this->tbl_usuario." WHERE email='$email'";
		return $this->LoadWithQuery($query);
	}

	function LoadWithQuery($query) {
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		if ($this->fields = mysql_fetch_assoc($resp))   {
			$this->LoadPermisos($this->fields['id_usuario']);
			$this->loaded = true;
			return true;
		}
		return false;
	}
}
