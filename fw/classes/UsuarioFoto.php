<?php 
    require_once dirname(__FILE__).'/../classes/Utiles.php';

class UsuarioFoto
{
    // Sesion PHP
    var $sesion = null;

    // Boolean que indica si la info. es cargada desde BD
    var $loaded = false;

    // Arreglo con los valores de los campos
    var $fields = null;

    // Arreglo que indica los campos con cambios
    var $changes = null;

    // String con el último error
    var $error = "";

   function UsuarioFoto($sesion)
    {
        $this->sesion =& $sesion;
        if($fields != null)
        {
            $this->rut_usuario = $fields['rut_usuario'];
            $this->fields = $fields;
        }
    }

    function Edit($field, $value)
    {
        if( $this->fields[$field] != $value )
        {
            $this->fields[$field] = $value;
            $this->changes[$field] = true;
        }
    }

 	function Load($rut)
	{
		$query = "SELECT * FROM usuario_foto WHERE rut_usuario = '$rut'";

        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		if( $this->fields = mysql_fetch_assoc($resp) )
			$this->loaded = true;
		else
			$this->loaded = false;

		 return $this->loaded;
    }

	function Write()
    {
	    $this->error = "";
		
		$val = $this->fields['data_foto'];

		$ext = strtolower(substr($val['name'], -4));

		if( $val['size'] > 100 * 1024)
		{
			$this->error = 'El tamaño de la foto no puede sobrepasar los 100 KBs';
			return false;
		
		}	
		else if( !( $ext == '.gif' || $ext == '.jpg' || $ext == '.bmp' || $ext == '.png')) 
		{
			$this->error = 'El formato de la foto es inválido. Sólo se aceptan JPG, GIF, BMP o PNG.';
			return false;
		}

		$imagen = imagick_readimage($val['tmp_name']);

		$tipo = imagick_getmimetype($imagen);
		$data = imagick_image2blob($imagen);

		$this->fields['data_foto'] = addslashes($data);
		$this->Edit('tipo_foto', $tipo );
			
		if($this->loaded)
        {
            $query = "UPDATE usuario_foto SET
                            fecha_edicion=NOW()";

            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                    $query .= ",$key='$val'";
            }
            $query .= " WHERE rut_usuario='".$this->fields['rut_usuario']."'";
           	$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		}	
        else
        {
			$query = "INSERT INTO usuario_foto SET fecha_edicion=NOW()";

			foreach ( $this->fields as $key => $val )
			{
				if( $this->changes[$key] )
					$query .= ",$key='$val'";
			}

            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			$this->loaded = true;
		}
		return true;
	}
}

