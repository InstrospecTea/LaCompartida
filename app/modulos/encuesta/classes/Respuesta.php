<?
    require_once dirname(__FILE__).'/../../../../conf.php';

class Respuesta
{
	// Sesion PHP
	var $sesion = null;
	
	// Arreglo con los valores de los campos
	var $fields = null;

	var $tabla = null;

	var $id = null;

	var $changes = null;

	var $error = null;

	var $id_respuesta = null;

	function Respuesta($sesion, $fields=array(), $params) 
	{
		$this->sesion = $sesion;

        if($fields!=null)
        {
            $this->id_respuesta = $fields[''.$this->id.''];
            $this->fields = $fields;
        }
	}

    function Edit($field, $value)
    {
        $this->fields[$field] = $value;
        $this->changes[$field] = true;
    }

	function Write()
    {
        if($this->tabla == "" or $this->id == "")
        {
            $this->error = "Falta nombre de tabla o id en archivo.php";
            return false;
        }

		$tabla = $this->tabla;
		$id = $this->id;

        if($this->Loaded())
        {
            $query = "UPDATE $tabla SET fecha_modificacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $query .= " WHERE $id='".$this->fields[''.$id.'']."'";
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			return true;
        }
        else
        {
            $query = "INSERT INTO $tabla SET fecha_creacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            $this->fields[''.$id.''] = mysql_insert_id($this->sesion->dbh);
		}


		$this->fields[''.$id.'']= mysql_insert_id();

		if($this->fields[''.$id.''] == "")
		{
			$this->error = "No se pudo insertar la respuesta";
			return false;
		}
        return true;
    }

	function Loaded()
	{
		if($this->fields[''.$this->id.''] == "")
			return false;
		return true;
	}
    function Load($id_respuesta)
    {
        $query = "SELECT * FROM ".$this->tabla ." WHERE ".$this->id."  ='".$this->fields['id_archivo']."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if(!$this->fields = mysql_fetch_assoc($resp))
        {
             $this->error= "La respuesta no existe";
            return false;
        }
        return true;
    }
    function LoadResp($id_encuesta_pregunta, $rut)
    {
        $query = "SELECT * FROM ".$this->tabla ." WHERE id_encuesta_pregunta = '$id_encuesta_pregunta' AND rut_usuario = '$rut'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if(!$this->fields = mysql_fetch_assoc($resp))
        {
             $this->error= "La respuesta no existe";
            return false;
        }
        return true;
    }
}
?>
