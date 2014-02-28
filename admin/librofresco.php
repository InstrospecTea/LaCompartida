<?php
require_once dirname(__FILE__).'/../app/conf.php';

$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);
$pagina->titulo = __('Librofresco');
$pagina->PrintTop();

if (!$sesion->usuario->TienePermiso('SADM')) {
	die('No Autorizado');
}

$servidores = array('192.168.1.24', '192.168.2.101', '192.168.2.102', 'rdsdb1.thetimebilling.com', 'rdsdb2.thetimebilling.com', 'rdsdb3.thetimebilling.com', 'rdsdb4.thetimebilling.com', 'rdsdb5.thetimebilling.com', 'rdsdb6.thetimebilling.com');
$dbhost = isset($_POST['dbhost']) ? $_POST['dbhost'] : Conf::dbHost();
?>

<style>
table {
	width: 100%;
}
table td.titulo {
	font-weight: bold;
}
</style>

<form class="form-horizontal" method="POST">
	<table>
		<tr>
			<td class="titulo">Host de Base de Datos</td>
			<td>
				<?php echo Html::SelectArray($servidores, 'dbhost', $dbhost, ' class="span5" ', '', '380px'); ?>
			</td>
		</tr>
	</table>
</form>
