<?
class CategoriaBiblio
{
	// Sesion PHP
	var $sesion = null;
	
	// Arreglo con los valores de los campos
	var $fields = null;

	//identificador del archivo
	var $tabla = null;

	//tamaño archivo
	var $tamano = null;

	var $changes = null;

	var $error = null;

	function CategoriaBiblio($sesion, $fields=array(), $params) //constructor para listas
	{
		$this->sesion = $sesion;
		$this->tabla='categoria';
        if($this->tabla == "")
        {
            Utiles::errorFatal("No se pudo cargar el archivo",__FILE__,__LINE__); 
        }

		if(is_numeric($params['id_categoria']))
		{
			$this->fields['id_categoria']=$params['id_categoria'];
			$this->Load();
		}
		else
			$this->fields=$fields;
		return true;

	}

    function Edit($field, $value)
    {
        $this->fields[$field] = $value;
        $this->changes[$field] = true;
    }
	function Load()
	{
		if($this->tabla == "")
		{
			$this->error = "Falta nombre de tabla Load() en archivo.php";
			return false;
		}
		$query = "SELECT id_categoria, glosa_categoria FROM ".$this->tabla ." WHERE id_categoria='".$this->fields['id_categoria']."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if(!$this->fields = mysql_fetch_assoc($resp))
		{
//			Utiles::errorFatal("No se encontró el categoria con id_categoria=".$this->fields['$id_categoria'],__FILE__,__LINE__);
			return false;
		}
		return true;
	}

	function Write()
    {
        if($this->tabla == "")
        {
            $this->error = "Falta nombre de tabla en archivo.php";
            return false;
        }
		$tabla = $this->tabla;


        if($this->Loaded())
        {
            $query = "UPDATE $tabla SET
                            fecha_mod=NOW() ";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $query .= " WHERE id_categoria='".$this->fields['id_categoria']."'";
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

        }
        else
        {
			if($this->LoadByName( $this->fields['glosa_categoria'] ))
			{
				$this->error = "Existe una categoria con el mismo nombre";
				return false;
			}
            $query = "INSERT INTO $tabla  SET
                            fecha_mod=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            $this->fields['id_categoria'] = mysql_insert_id($this->sesion->dbh);
		}


		$this->id_categoria= mysql_insert_id();
		if($this->id_categoria == "")
		{
			$this->error = "No se pudo insertar el archivo";
			return false;
		}
        return true;
    }
	function DbRemove()
	{
		if($this->tabla == "")
        {
            $this->error = "Falta nombre de tabla en archivo.php";
            return false;
        }
		
        $params_array['codigo_permiso'] = 'ADM';
        $p = $this->sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de administrador
        if( $p->fields['permitido'] )
		{
        	$tabla = $this->tabla;
			$query = "DELETE FROM $tabla WHERE id_categoria='".$this->fields['id_categoria']."'";
 			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			return true;
		}
		else
			$this->error = "No tierne permisos para ejecutar esta opcion";
		return false;
	}
    function Loaded()
    {

        if($this->fields['id_categoria'] == "")
            return false;
//      if($this->fields['nombre'] == "")
//          return false;
//      if($this->fields['data'] == "")
//          return false;
//      if($this->fields['tipo'] == "")
//          return false;

        return true;
    }
    function LoadByName( $name )
    {
        $query = "SELECT * FROM categoria WHERE glosa_categoria='$name'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

        if( $esta = mysql_fetch_assoc($resp) )
		{
			return true;
		}
    }

}
?>
