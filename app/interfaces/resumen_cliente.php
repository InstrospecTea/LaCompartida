<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$Html = new \TTB\Html;
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	if($fecha1 != '') {
    	$pagina->Redirect("planillas/planilla_resumen_abogado.php?fecha_ini=$fecha1&fecha_fin=$fecha2");
  	}

  	if ($fecha_ini == '') {
  		$fecha_ini = date('d-m-Y', strtotime('-1 month'));
  	}

	$pagina->titulo = __('Reporte de Ventas');
	$pagina->PrintTop();

	$query = 'SELECT id_moneda FROM prm_moneda WHERE moneda_base = 1';
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_moneda_base) = mysql_fetch_array($resp);
?>

<form method=post name=formulario action="planillas/planilla_resumen_cliente.php">

<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?php echo __('Fecha desde'); ?>
		</td>
		<td align=left>
			<?php echo $Html::PrintCalendar('fecha_ini', $fecha_ini); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Fecha hasta'); ?>
		</td>
		<td align=left>
			<?php echo  $Html::PrintCalendar('fecha_fin', $fecha_fin); ?>
		</td>
	</tr>
   <tr>
        <td align=right>
           <?php echo __('Clientes'); ?>
        </td>
        <td align=left>
            <?php echo Html::SelectQuery($sesion, 'SELECT codigo_cliente, glosa_cliente FROM cliente WHERE activo=1 ORDER BY glosa_cliente', 'clientes[]', $clientes, 'multiple size=5', ''); ?>
        </td>
    </tr>
    <tr>
        <td align=right>
           <?php echo __('Grupos Clientes'); ?>
        </td>
        <td align=left>
            <?php echo Html::SelectQuery($sesion, 'SELECT id_grupo_cliente, glosa_grupo_cliente FROM grupo_cliente', 'grupos[]', $grupos, 'multiple size=3', ''); ?>
        </td>
    </tr>
   <tr>
        <td align=right>
			<?php echo __('Forma Tarificación'); ?>
        </td>
        <td align=left>
            <?php echo Html::SelectQuery($sesion, 'SELECT forma_cobro, descripcion FROM prm_forma_cobro ORDER BY forma_cobro', 'forma_cobro[]', $forma_cobro, 'multiple size=5', ''); ?>
        </td>
    </tr>
  	<tr>
		<td align=right>
			<?php echo __('Facturado en'); ?>
		</td>
		<td align=left>
			<?php echo Html::SelectQuery($sesion, 'SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda', 'monedas[]', $monedas, 'multiple size=3', ''); ?>
		</td>
	</tr>
  	<tr>
		<td align=right>
			<?php echo __('Mostrar valores en:'); ?>
		</td>
		<td align=left>
			<?php echo Html::SelectQuery($sesion, 'SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda', 'id_moneda', $id_moneda ? $id_moneda : $id_moneda_base, '', ''); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Comparar seg&uacute;n:'); ?>
		</td>
		<td align=left>
			<select name="tarifa" id="tarifa" style="width: 150px;">
				<option value="monto_thh"> Tarifa del cliente </option>
				<option value="monto_thh_estandar"> Tarifa estandar </option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan=4 align=right>
			<input type=submit class=btn value="<?php echo __('Generar planilla'); ?>">
		</td>
	</tr>
</table>

</form>

<?php
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
