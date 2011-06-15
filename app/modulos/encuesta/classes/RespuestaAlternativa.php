<?
    require_once dirname(__FILE__).'/../../../../conf.php';
	require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Respuesta.php';

class RespuestaAlternativa extends Respuesta
{
   function RespuestaAlternativa($sesion, $fields=array(), $params)
	{ 
		$this->tabla = 'encuesta_respuesta_alternativa';
		$this->id = 'id_encuesta_respuesta_alternativa';
      	parent::Respuesta($sesion,$fields, $params); 
	}
}
?>
