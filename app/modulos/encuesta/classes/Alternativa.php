<?
    require_once dirname(__FILE__).'/../../../../conf.php';

class Alternativa
{
	// Sesion PHP
	var $sesion = null;
	
	// Arreglo con los valores de los campos
	var $fields = null;

    // Arreglo que indica los campos con cambios
    var $changes = null;

	var $error = null;

	var $id_encuesta_pregunta_alternativa = null;


    function Alternativa($sesion,$fields=null,$params=null)
    {
        $this->sesion = $sesion;
		if($fields!=null)
        {
			$this->id_encuesta_pregunta_alternativa = $fields['id_encuesta_pregunta_alternativa'];
        	$this->fields = $fields;
		}
    }

    function Edit($field, $value)
    {
        $this->fields[$field] = $value;
        $this->changes[$field] = true;
    }

	function Load($id_encuesta_pregunta_alternativa)
	{
		$query = "SELECT * FROM encuesta_pregunta_alternativa WHERE id_encuesta_pregunta_alternativa='$id_encuesta_pregunta_alternativa'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $this->fields = mysql_fetch_assoc($resp) )
            return true;
		$this->error="No existe la alternativa para esta pregunta";
        return false;
    }

    function Write()
    {
        $this->error = "";

        if($this->Loaded())
        {
            $query = "UPDATE encuesta_pregunta_alternativa SET
                            fecha_modificacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $query .= " WHERE id_encuesta_pregunta_alternativa='".$this->fields['id_encuesta_pregunta_alternativa']."'";
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        }
        else
        {
            $query = "INSERT INTO encuesta_pregunta_alternativa SET
                            fecha_creacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            $this->fields['id_encuesta_pregunta_alternativa'] = mysql_insert_id($this->sesion->dbh);
					
        }

        return true;
	}

	function Loaded()
	{
		if($this->fields['id_encuesta_pregunta_alternativa'])
			return true;
		return false;
	}
}
?>
