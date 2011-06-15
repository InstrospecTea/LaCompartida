<?
    require_once dirname(__FILE__).'/../../../../conf.php';

class Pregunta
{
	// Sesion PHP
	var $sesion = null;
	
	// Arreglo con los valores de los campos
	var $fields = null;

    // Arreglo que indica los campos con cambios
    var $changes = null;

	var $error = null;

	var $id_encuesta_pregunta = null;


    function Pregunta($sesion,$fields=null,$params=null)
    {
        $this->sesion = $sesion;
		if($fields!=null)
        {
			$this->id_encuesta_pregunta = $fields['id_encuesta_pregunta'];
        	$this->fields = $fields;
		}
    }

    function Edit($field, $value)
    {
        $this->fields[$field] = $value;
        $this->changes[$field] = true;
    }

	function Load($id_encuesta_pregunta)
	{
		$query = "SELECT * FROM encuesta_pregunta WHERE id_encuesta_pregunta='$id_encuesta_pregunta'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $this->fields = mysql_fetch_assoc($resp) )
            return true;
		$this->error="No existe la pregunta de la encuesta";
        return false;
    }

    function Write()
    {
        $this->error = "";

        if($this->Loaded())
        {
            $query = "UPDATE encuesta_pregunta SET
                            fecha_modificacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $query .= " WHERE id_encuesta_pregunta='".$this->fields['id_encuesta_pregunta']."'";
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        }
        else
        {
            $query = "INSERT INTO encuesta_pregunta SET
                            fecha_creacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            $this->fields['id_encuesta_pregunta'] = mysql_insert_id($this->sesion->dbh);
					
        }

        return true;
	}
    function Delete()
    {
        if($this->fields['id_encuesta_pregunta'] == '')
        {
            $this->error = 'Debes cargar la pregunta antes de eliminarla.';
            return false;
        }
        $query = "DELETE FROM encuesta_pregunta WHERE id_encuesta_pregunta='".$this->fields['id_encuesta_pregunta']."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        return true;
    }
    function Loaded()
    {
        if($this->fields['id_encuesta_pregunta'])
            return true;
        return false;
    }
}
?>
