<?
    require_once dirname(__FILE__).'/../../../../conf.php';
	require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Respuesta.php';

class RespuestaAbierta extends Respuesta
{
   function RespuestaAbierta($sesion, $fields=array(), $params)
	{ 
		$this->tabla = 'encuesta_respuesta_abierta';
		$this->id = 'id_encuesta_respuesta_abierta';
      	parent::Respuesta($sesion,$fields, $params); 
	}
}
?>
