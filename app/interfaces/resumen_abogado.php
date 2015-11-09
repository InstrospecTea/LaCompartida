<?php
require_once dirname(__FILE__).'/../conf.php';

$Sesion = new Sesion(array('REP'));
$Pagina = new Pagina($Sesion);
$Form = new Form($Sesion);
$Html = new \TTB\Html;
$id_usuario = $Sesion->usuario->fields['id_usuario'];

$fecha_ini = date('d-m-Y', strtotime('first day of this month'));
$fecha_fin = date('d-m-Y');

$Pagina->titulo = __('Reporte Resumen Profesional');
$Pagina->PrintTop();
?>
<form method="post" name="formulario" action="planillas/planilla_resumen_abogado.php">
<table class="border_plomo tb_base">
	<tr>
		<td align="right">
			<?php echo __('Fecha desde')?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha_ini', $fecha_ini); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta')?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha_fin', $fecha_fin); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Profesionales')?>
		</td>
		<td align="left">
			<?php echo $Form->select('usuarios[]', $Sesion->usuario->ListarActivos('', 'PRO'), $usuarios, array('empty' => FALSE, 'style' => 'width: 200px', 'multiple' => 'multiple','size' => '7')); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Forma Tarificación')?>
		</td>
		<td align="left">
			<?php echo Html::SelectQuery($Sesion,'SELECT forma_cobro, descripcion FROM prm_forma_cobro ORDER BY forma_cobro', 'forma_cobro[]', $forma_cobro, 'multiple size=5', '', '200'); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Mostrar valores en:')?>
		</td>
		<td align="left">
			<?php echo Html::SelectQuery($Sesion,'SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda', 'id_moneda', $id_moneda ? $id_moneda : $id_moneda_base, '', ''); ?>
		</td>
	</tr>
	<tr>
		<td colspan="4" align="right">
			<input type="submit" class="btn" value="<?php echo __('Generar planilla')?>">
		</td>
	</tr>
</table>
</form>

<?php
	echo InputId::Javascript($Sesion);
	$Pagina->PrintBottom();
?>
