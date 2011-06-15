<?
require_once("../../libs/chart_director/phpchartdir.php");

require_once dirname(__FILE__).'/../../../conf.php';
require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';
require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Pregunta.php';
require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Alternativa.php';
require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Reportes.php';

require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Lista.php';
require_once Conf::ServerDir().'/fw/classes/Pagina.php';
require_once Conf::ServerDir().'/fw/classes/Sesion.php';
require_once Conf::ServerDir().'/fw/classes/Usuario.php';
require_once Conf::ServerDir().'/fw/classes/Utiles.php';
require_once Conf::ServerDir().'/fw/classes/Html.php';

	$sesion = new Sesion( array('ADM') );

	$pagina = new Pagina($sesion);

	$pregunta = new Pregunta($sesion);
	if(!$pregunta->Load($id_encuesta_pregunta))
		$pagina->FatalError("Pregunta Inválida");

	$lista_alternativas = new ListaPreguntasAlternativas($sesion,'',"SELECT * FROM encuesta_pregunta_alternativa
													WHERE id_encuesta_pregunta = $id_encuesta_pregunta");

	for($y = 0; $y < $lista_alternativas->num; $y++)
	{
		$alternativas = $lista_alternativas->Get($y);
		$glosa_alternativa = $alternativas->fields['glosa_alternativa'];
		$cant = Reportes::Alternativa($sesion, $id_encuesta_pregunta,$alternativas->fields['id_encuesta_pregunta_alternativa']);
		$labels[$y] = $glosa_alternativa;
		$data[$y] = $cant['num'];
	}
	
	$c = new PieChart(500, 210);

	$c->setPieSize(250, 110, 80);

	$c->addTitle($pregunta->fields['glosa_pregunta']);

	$c->set3D();

	$c->setData($data, $labels);

	header("Content-type: image/png");
	print($c->makeChart2(PNG));
?>
