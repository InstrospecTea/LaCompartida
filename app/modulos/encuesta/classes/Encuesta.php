<?
    require_once dirname(__FILE__).'/../../../../conf.php';

class Encuesta
{
	// Sesion PHP
	var $sesion = null;
	
	// Arreglo con los valores de los campos
	var $fields = null;

    // Arreglo que indica los campos con cambios
    var $changes = null;

	var $error = null;

	var $id_encuesta = null;

    function Encuesta($sesion,$fields=null,$params=null)
    {
        $this->sesion = $sesion;
		if($fields!=null)
        {
			$this->id_encuesta = $fields['id_encuesta'];
        	$this->fields = $fields;
		}
    }

    function Edit($field, $value)
    {
        $this->fields[$field] = $value;
        $this->changes[$field] = true;
    }

	function Load($id_encuesta)
	{
		$query = "SELECT * FROM encuesta WHERE id_encuesta='$id_encuesta'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $this->fields = mysql_fetch_assoc($resp) )
            return true;
		$this->error="No existe la encuesta";
        return false;
    }
    function IsRespondida($id_encuesta,$rut)
    {
        $query = "SELECT encuesta.id_encuesta FROM encuesta_respuesta_alternativa 
								INNER JOIN encuesta_pregunta ON encuesta_pregunta.id_encuesta_pregunta = encuesta_respuesta_alternativa.id_encuesta_pregunta
								INNER JOIN encuesta ON encuesta_pregunta.id_encuesta = encuesta.id_encuesta
							    WHERE encuesta_respuesta_alternativa.rut_usuario='$rut' 
									AND encuesta.id_encuesta = '$id_encuesta' LIMIT 0,1" ;

        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $ohyes = mysql_fetch_assoc($resp) )
            return true;

        $query = "SELECT encuesta.id_encuesta FROM encuesta_respuesta_abierta
                                INNER JOIN encuesta_pregunta ON encuesta_pregunta.id_encuesta_pregunta = encuesta_respuesta_abierta.id_encuesta_pregunta
                                INNER JOIN encuesta ON encuesta_pregunta.id_encuesta = encuesta.id_encuesta
                                WHERE encuesta_respuesta_abierta.rut_usuario='$rut'
                                    AND encuesta.id_encuesta = '$id_encuesta' LIMIT 0,1" ;

        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $ohyes = mysql_fetch_assoc($resp) )
            return true;


        return false;
    }
    function Write()
    {
        $this->error = "";

        if($this->Loaded())
        {
            $query = "UPDATE encuesta SET
                            fecha_modificacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $query .= " WHERE id_encuesta='".$this->fields['id_encuesta']."'";
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

        }
        else
        {
            $query = "INSERT INTO encuesta SET
                            fecha_creacion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }

            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            $this->fields['id_encuesta'] = mysql_insert_id($this->sesion->dbh);
					
        }

        return true;
	}

    function Delete( $testing=false )
    {
		$detec=true;
        $query = "SELECT encuesta.id_encuesta FROM encuesta_respuesta_alternativa
                                INNER JOIN encuesta_pregunta ON encuesta_pregunta.id_encuesta_pregunta = encuesta_respuesta_alternativa.id_encuesta_pregunta
                                INNER JOIN encuesta ON encuesta_pregunta.id_encuesta = encuesta.id_encuesta
                                WHERE encuesta.id_encuesta = ".$this->fields['id_encuesta']." LIMIT 0,1" ;

        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

        if( $ohyes = mysql_fetch_assoc($resp) )
			$detec=false;

		if($detec==true)
		{

    	    $query = "SELECT encuesta.id_encuesta FROM encuesta_respuesta_abierta
                                INNER JOIN encuesta_pregunta ON encuesta_pregunta.id_encuesta_pregunta = encuesta_respuesta_abierta.id_encuesta_pregunta
                                INNER JOIN encuesta ON encuesta_pregunta.id_encuesta = encuesta.id_encuesta
                                WHERE encuesta.id_encuesta = ".$this->fields['id_encuesta']." LIMIT 0,1" ;

	        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        	if( $ohyes = mysql_fetch_assoc($resp) )
			$detec=false;
		}

        if( $detec==false )
        {

            $this->error = 'No se puede eliminar porque tiene respuestas asociadas.';
            return false;
        }

        if( !$testing )
        {
            if($this->Loaded())
            {
                $query = "DELETE FROM encuesta WHERE id_encuesta='".$this->fields['id_encuesta']."'";
                $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

                $this->marked = false;
            }
			else
				$this->error = 'No existe la encuesta.';
        }
        return true;
    }

	function Loaded()
	{
		if($this->fields['id_encuesta'])
			return true;

		return false;
	}
}
