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

	$encuesta = new Encuesta($sesion);
	if(!$encuesta->Load($id_encuesta))
		$pagina->FatalError("Encuesta Inválida");

	$cant = Reportes::Universo($sesion, $id_encuesta);
	$labels[0] = 'Respondidas';
	$data[0] = $cant['respondidas'];

	$labels[1] = 'No Respondidas';
	$data[1] = $cant['no_respondidas'];

	$c = new PieChart(500, 210);
	
	$c->setPieSize(250, 110, 80);

	$c->addTitle('Total: '. $cant['total']);

	$c->set3D();

	$c->setData($data, $labels);
	
	header("Content-type: image/png");
	print($c->makeChart2(PNG));
?>
