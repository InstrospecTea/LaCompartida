<?php 
	require_once dirname(__FILE__).'/Utiles.php';
	require_once dirname(__FILE__).'/Lista.php';

class Archivo
{
	// Sesion PHP
	var $sesion = null;
	
	// Arreglo con los valores de los campos
	var $fields = null;

	var $tabla = null;
	var $campo_id = null;
	var $campo_data = null;

	//tamaño archivo
	var $tamano = null;

	var $changes = null;

	var $error = null;

	function Archivo($sesion, $tabla, $campo_id, $campo_data = "") 
	{
		$this->sesion = $sesion;
		$this->tabla = $tabla;
		$this->campo_id = $campo_id;
		$this->campo_data = $campo_data;
		return true;
	}

    function Edit($field, $value)
    {
        $this->fields[$field] = $value;
        $this->changes[$field] = true;
    }
	function Load($id)
	{
        if($this->tabla == "" or $id == "")
		Utiles::errorFatal("Falta nombre de tabla o id archivo en archivo.php",__FILE__,__LINE__);
		
        $query = "SELECT * FROM ".$this->tabla." WHERE ".$this->campo_id."='".$id."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

        if(! $this->fields = mysql_fetch_assoc($resp))
			Utiles::errorFatal("No se encontró el archivo con id_archivo=".$this->fields['$id_archivo'],__FILE__,__LINE__);
		$this->fields['id_archivo'] = $id;
		return true;
	}
	function GetData()
	{
        if($this->tabla == "" or $this->fields['id_archivo'] == "")
		Utiles::errorFatal("Falta nombre de tabla o id archivo en archivo.php",__FILE__,__LINE__);
		
        $query = "SELECT ".$this->campo_data." FROM ".$this->tabla." WHERE ".$this->campo_id."='".$this->fields['id_archivo']."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

        if(!list($this->fields['data']) = mysql_fetch_array($resp))
			Utiles::errorFatal("No se encontró el archivo con id_archivo=".$this->fields['$id_archivo'],__FILE__,__LINE__);
        return $this->fields['data'];
	}

	function Write()
    {
        if($this->tabla == "")
        {
            $this->error = "Falta nombre de tabla";
            return false;
        }
		$tabla = $this->tabla;
        if($this->Loaded())
        {
            $query = "UPDATE $tabla SET
                            fecha_modificacion=NOW() ";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $query .= " WHERE ".$this->campo_id."='".$this->fields['id_archivo']."'";
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			return true;

        }
        else
        {
            $query = "INSERT INTO $tabla SET
                            fecha_modificacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            $this->fields['id_archivo'] = mysql_insert_id($this->sesion->dbh);
		}

		$this->id_archivo = mysql_insert_id();
		if($this->id_archivo == "")
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
            $this->error = "Falta nombre de tabla";
            return false;
        }
        $tabla = $this->tabla;
		$query = "DELETE FROM $tabla WHERE ".$this->campo_id."='".$this->fields['id_archivo']."'";
 		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
			
	function Loaded()
	{
		if($this->fields['id_archivo'] == "")
			return false;
		return true;
	}

	function GetDataFromFile(&$fileName)
	{
	    $fp      = fopen($fileName, 'r');
        $content = fread($fp, filesize($fileName)) or die("No pudo leer el archivo $fileName");
        $content = addslashes($content);
		$this->Edit($this->campo_data,$content);
        fclose($fp);
	}
    function FindArchivo($params_array)
    {
        if($this->fields['id_archivo'] == $params_array['id_archivo'])
            return true;
        return false;
    }

}
