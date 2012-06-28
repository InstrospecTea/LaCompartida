<?php 

require_once "GraficoBarras.php";
require_once dirname(__FILE__).'/../../conf.php';
require_once "../../../fw/classes/Sesion.php";

$sesion = new Sesion();

	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
		$letra_profesional = 'username';
	else
		$letra_profesional = 'usuario';
	
	if($usuarios)
		$where_usuario = ' AND trabajo.id_usuario IN ('.$usuarios.')';
	else
		$where_usuario = '';

	if($solo_activos)
		$where_usuario .= ' AND usuario.activo = 1 ';

	if($clientes)
		$where_cliente = ' AND cliente.codigo_cliente IN ('.$clientes.')';
	else
		$where_cliente = '';




$total_tiempo = 0;
$query = "SELECT 
						CONCAT_WS(', ',apellido1,nombre) as usuario, 
						username,
						SUM(TIME_TO_SEC(duracion))/3600 as tiempo
					FROM trabajo 
					JOIN usuario ON (usuario.id_usuario = trabajo.id_usuario) 
					JOIN asunto ON (trabajo.codigo_asunto = asunto.codigo_asunto)
					JOIN cliente ON (asunto.codigo_cliente = cliente.codigo_cliente)
					WHERE
						(fecha BETWEEN '$fecha_ini' AND '$fecha_fin') ".$where_cliente.$where_usuario."
					GROUP BY usuario.id_usuario";

$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
for($i = 0; $fila = mysql_fetch_array($resp); $i++)
{
	$total_tiempo += $fila[tiempo];
	$usuario[$i] = $fila[$letra_profesional];
	$tiempo[$i] = $fila[tiempo];
}

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
$title = __('Horas trabajadas').' / '.Utiles::sql2date($fecha_ini).' - '.Utiles::sql2date($fecha_fin);
$c->Titulo(" $title ");

#Add a title to the y-axis
$c->Ejes(__("Usuario"),__("Cantidad"));

#Set the x axis labels
$c->Labels($usuario);

$c->layer->addDataSet($tiempo, 0xff8080, __("Horas trabajadas").': '.$total_tiempo);
#$layer->addDataSet($terminados, 0x80ff80, "Terminadas");

#output the chart
$c->Imprimir();
?>
