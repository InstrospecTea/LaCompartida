<?
#Variables $_REQUEST
#fname : nombre del archivo
#frequire : direccion del reporte
#popup : opcional (si su valor es 1, no se mostraran los menus)

$popup = $_REQUEST['popup'];
$fname = empty($_REQUEST['fname']) ? 'reporte.pdf' : $_REQUEST['fname'];

/*
if($_REQUEST["opc"] == 'pdf')
{
	set_time_limit(100);
	$ruta='/tmp/tmp2.html';
	$f1 = fopen($ruta,'r');
	$html = fread($f1,filesize($ruta));
	fclose($f1);

	require_once("../classes/dompdf/dompdf_config.inc.php");
	$dompdf = new DOMPDF();
	$dompdf->set_paper("a4", "landscape");
	#$dompdf->load_html_file($ruta);
	$dompdf->load_html(stripslashes($html));
	$dompdf->render();
	$dompdf->stream($fname.'.pdf');
	exit;
}
*/
#captura la salida HTML del informe
ob_start();
require_once $_REQUEST["frequire"];
$html = ob_get_contents();
ob_end_clean();

header('Content-Type: application/vnd.ms-excel;');
header("Content-type: application/x-msexcel");
header('Content-Disposition: attachment; filename="Informe_periodico.xls"');
echo($html);
exit();

#elimina la cabecera adicional
#$html2_pos = strpos($html,'<!DOCTYPE',1);
#$html = substr($html,$html2_pos,strlen($html));

#crear funcion para que el nombre del archivo sea dinámico
/*$f1 = fopen('/tmp/tmp2.html','w');
fwrite($f1, $html);
fclose($f1);
exit();
header('Location: html_to_pdf.php?opc=pdf&popup=1&fname='.$_REQUEST['fname']);
*/
?>
