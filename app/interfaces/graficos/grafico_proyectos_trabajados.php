<?php 

require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";
require_once "../../../fw/classes/Utiles.php";

$sesion = new Sesion();

$query = "SELECT glosa_asunto, SUM(TIME_TO_SEC(duracion))/3600 as tiempo, codigo_cliente
			FROM asunto LEFT JOIN trabajo USING (codigo_asunto)
				WHERE 
					trabajo.id_usuario = '$id_usuario' AND
				(fecha BETWEEN '$fecha1' AND '$fecha2')
			GROUP BY asunto.codigo_asunto";
$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
for($i = 0; $fila = mysql_fetch_array($resp); $i++)
{
	$asunto[$i] = $fila[codigo_cliente]." - ".$fila[glosa_asunto];	
	$tiempo[$i] = $fila[tiempo];	
}

$nombre_usuario = Utiles::Glosa($sesion, $id_usuario, "CONCAT_WS(' ',nombre,apellido1)", "usuario");

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
$title = __('Horas trabajadas').' - '.$nombre_usuario;
$c->Titulo($title);

#Add a title to the y-axis
$c->Ejes(__("Proyecto"),__("Horas"));

#Set the x axis labels
$c->Labels($asunto);

$c->layer->addDataSet($tiempo, 0xff8080, __("Horas trabajadas"));
#$layer->addDataSet($terminados, 0x80ff80, "Terminadas");

#output the chart
$c->Imprimir();
?>
