<?
    require_once dirname(__FILE__).'/../../../../conf.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/Usuario.php';
    require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Lista.php';


class Reportes
{
    // Sesion PHP
    var $sesion = null;

    // String con el último error
    var $error = "";


    function Universo($sesion, $id_encuesta )
    {
		$encuesta = new Encuesta($sesion);
		$encuesta->Load($id_encuesta);
		$id_empresa = $encuesta->fields['id_empresa'];
		$lista_usuarios = new ListaEncuestas($sesion,'',"SELECT rut_usuario FROM usuario_empresa WHERE id_empresa = '$id_empresa' ");

		$universo['total'] = $lista_usuarios->num;
		$universo['respondidas']= 0;
		$universo['no_respondidas']= 0;

		for($x=0;$x < $lista_usuarios->num; $x++)
		{
			$usuario_rut = $lista_usuarios->Get($x);
			if($encuesta->IsRespondida($id_encuesta, $usuario_rut->fields['rut_usuario']))
				$universo['respondidas'] +=1;
			else
				$universo['no_respondidas'] +=1;

		}
		return ($universo);
    }

    function Alternativa($sesion, $id_encuesta_pregunta,$id_alternativa)
    {
        $query = "SELECT COUNT(*)AS num FROM encuesta_respuesta_alternativa WHERE
                            id_encuesta_pregunta = '$id_encuesta_pregunta'
                            AND id_encuesta_pregunta_alternativa = '$id_alternativa'";

        $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
        $resp  =  mysql_fetch_assoc($resp);
        return $resp;
    }
}

?>
