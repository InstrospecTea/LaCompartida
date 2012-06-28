<?php 

require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";
require_once "../../../fw/classes/Utiles.php";

$sesion = new Sesion();

if($usuarios)
	$where_usuario = ' AND trabajo.id_usuario IN ('.$usuarios.')';
else
	$where_usuario = '';

if($solo_activos)
	$where_usuario = ' AND usuario.activo = 1 ';

if($clientes)
	$where_cliente = ' AND cliente.codigo_cliente IN ('.$clientes.')';
else
	$where_cliente = '';

$total_tiempo = 0;
$query = "SELECT 
						asunto.codigo_asunto, 
						SUM(TIME_TO_SEC(duracion))/3600 as tiempo
					FROM trabajo 
					JOIN asunto ON (trabajo.codigo_asunto = asunto.codigo_asunto) 
					JOIN cliente ON (cliente.codigo_cliente = asunto.codigo_cliente)
						WHERE 1 ".$where_cliente.$where_usuario." AND
						trabajo.fecha BETWEEN '".$fecha_ini."' AND '".$fecha_fin."'  
					GROUP BY asunto.codigo_asunto 
					ORDER BY tiempo DESC LIMIT 0,14";


$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
for($i = 0; $fila = mysql_fetch_array($resp); $i++)
{
	$asunto[$i] = $fila[codigo_asunto];	
	$tiempo[$i] = $fila[tiempo];
	$total_tiempo += $fila[tiempo];
}

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
$c->Titulo(sprintf("Horas trabajadas por %s / %s - %s (Sólo 14 más relevantes)", __('asunto'), Utiles::sql2date($fecha_ini), Utiles::sql2date($fecha_fin) ));

#Add a title to the y-axis
$c->Ejes(__('Asunto'),"Horas");

#Set the x axis labels
$c->Labels($asunto);

$c->layer->addDataSet($tiempo, 0xff8080,__('Horas trabajadas').': '.$total_tiempo);
#$layer->addDataSet($terminados, 0x80ff80, "Terminadas");

#output the chart
$c->Imprimir();
?>
