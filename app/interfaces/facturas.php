<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Factura.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);

	$factura = new Factura($sesion);

	if($id_factura != "")
	{
		$factura->Load($id_factura);
	}

	if($opc == 'generar_factura')
	{
		// POR HACER
		// mejorar
		if($id_factura_grabada)
			include dirname(__FILE__).'/factura_doc.php';
		else
			echo "Error";
		exit;
	}
	if($exportar_excel)
	{
		// Es necesaria esta bestialidad para que no se caiga cuando es llamada desde otro lado.
		$no_activo = !$activo;
		$multiple = true;
		require_once Conf::ServerDir().'/interfaces/facturas_listado_xls.php';
		exit;
	}
	

	$pagina->titulo = __('Revisar Documentos Tributarios');
	$pagina->PrintTop();

	

	if( $opc == 'buscar' || $opc == 'generar_factura' )
	{
		if($orden == "")
			$orden = "fecha DESC";

		if($where == '')
		{
			$join = "";
			$where = 1;
			if($numero != '')
				$where .= " AND numero = '$numero'";
			if($fecha1 && $fecha2)
				$where .= " AND fecha BETWEEN '".Utiles::fecha2sql($fecha1)." 00:00:00' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
			else if( $fecha1 )
				$where .= " AND fecha >= '".Utiles::fecha2sql($fecha1).' 00:00:00'."' ";
			else if( $fecha2 ) 
				$where .= " AND fecha <= '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario )
				{
					$cliente = new Cliente($sesion);
					$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
					$codigo_cliente = $cliente->fields['codigo_cliente'];
				}
			if($tipo_documento_legal_buscado)
				$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";
			
			if($codigo_cliente)
				{
				$where .= " AND factura.codigo_cliente='".$codigo_cliente."' ";
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario)
				{
					$asunto = new Asunto($sesion);
					$asunto->LoadByCodigoSecundario($codigo_cliente_secundario);
					$id_contrato = $asunto->fields['id_contrato'];
				}
			if($codigo_asunto)
				{
					$asunto = new Asunto($sesion);
					$asunto->LoadByCodigo($codigo_asunto);
					$id_contrato = $asunto->fields['id_contrato'];
				}
			if($id_contrato)
				{
					$where .= " AND cobro.id_contrato=".$id_contrato." ";
				}
			if($id_cobro)
				{
					$where .= " AND factura.id_cobro=".$id_cobro." ";
				}
			if($id_estado)
				{
					$where .= " AND factura.id_estado = ".$id_estado." ";
				}
			if($id_moneda)
				{
					$where .= " AND factura.id_moneda = ".$id_moneda." ";
				}
			if($grupo_ventas)
			{
				$where .= " AND prm_documento_legal.grupo = 'VENTAS' ";
			}
		}
		else
			$where = base64_decode($where);

		$query = "SELECT SQL_CALC_FOUND_ROWS *
					
					, prm_documento_legal.codigo as tipo
					, numero
					, cliente.glosa_cliente
					, fecha
					, CONCAT(LEFT(nombre,1),LEFT(apellido1,1),LEFT(apellido2,1)) AS encargado_comercial
					, descripcion
					, prm_estado_factura.codigo as estado
					, factura.id_cobro
					, prm_moneda.simbolo
					, prm_moneda.cifras_decimales
					, prm_moneda.tipo_cambio
					, factura.id_moneda
					, factura.honorarios
					, factura.subtotal_gastos
					, factura.subtotal_gastos_sin_impuesto
					, factura.iva
					, total
					, '' as saldo_pagos
					, cta_cte_fact_mvto.saldo as saldo
					, '' as monto_pagos_moneda_base
					, '' as saldo_moneda_base
					, factura.id_factura
				FROM factura
				JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
				JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
				LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
				LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
				LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
				LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
				LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
				LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
							WHERE $where";

		$resp = mysql_query($query.' LIMIT 0,12', $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$monto_saldo_total = 0;
		$glosa_monto_saldo_total = '';
		$where_moneda = ' WHERE moneda_base = 1';
		if($id_moneda>0) {
			$where_moneda = 'WHERE id_moneda = '.$id_moneda;
		}
		$query_moneda = 'SELECT id_moneda, simbolo, cifras_decimales, moneda_base, tipo_cambio FROM prm_moneda	'. $where_moneda .' ORDER BY id_moneda ';
		$resp_moneda = mysql_query($query_moneda, $sesion->dbh) or Utiles::errorSQL($query_moneda, __FILE__, __LINE__, $sesion->dbh);
		$id_moneda_base=0;
		while(list($id_moneda_tmp, $simbolo_moneda_tmp, $cifras_decimales_tmp, $moneda_base_tmp, $tipo_cambio_tmp) = mysql_fetch_array($resp_moneda)){
			while($row = mysql_fetch_assoc($resp))
			{
				$monto_saldo_total += UtilesApp::CambiarMoneda($row['saldo'],$row['tipo_cambio'],$row['cifras_decimales'],$tipo_cambio_tmp,$cifras_decimales_tmp);
			}
			$glosa_monto_saldo_total = '<b>'.__('Saldo'). ' ' . $simbolo_moneda_tmp. ' ' .number_format($monto_saldo_total,$cifras_decimales_tmp,",",".")."</b>";
		}
		// calcular el saldo en moneda base
		
		$x_pag = 12;
		$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
		$b->nombre = "busc_facturas";
		$b->titulo = "Documentos Tributarios <br />".$glosa_monto_saldo_total;
		$b->AgregarEncabezado("fecha",__('Fecha'),"width=60px ");
		$b->AgregarEncabezado("tipo",__('Tipo'),"align=center width=40px");
		$b->AgregarEncabezado("numero",__('N°'),"align=right width=30px");
		$b->AgregarEncabezado("glosa_cliente",__('Cliente'),"align=left width=40px");
		$b->AgregarEncabezado("glosa_asunto",__('Asunto'),"align=left width=40px");
		$b->AgregarEncabezado("encargado_comercial",__('Abogado'),"align=left width=20px");
		$b->AgregarEncabezado("descripcion",__('Descripción'),"align=left width=50px");
		$b->AgregarEncabezado("estado",__('Estado'),"align=center");
		$b->AgregarEncabezado("id_cobro",__('Cobro'),"align=center");
		$b->AgregarFuncion("SubTotal","SubTotal","align=right nowrap");
		$b->AgregarFuncion("iva","Iva","align=right nowrap");
		$b->AgregarFuncion("Monto Total","MontoTotal","align=right nowrap");
		$b->AgregarFuncion("Pagos","MontoTotal","align=right nowrap");
		$b->AgregarFuncion("Saldo","MontoTotal","align=right nowrap");
		$b->AgregarFuncion("Fecha último pago",__('Fecha último pago'),"align=right nowrap");
		$b->AgregarFuncion(__('Opción'),"Opciones","align=right nowrap");
		$b->color_mouse_over = "#bcff5c";
		$b->funcionTR = "funcionTR";
	}

	function Opciones(& $fila)
	{
		global $where;
		$id_factura = $fila->fields['id_factura'];
		$codigo_cliente = $fila->fields['codigo_cliente'];
		$prov = $fila->fields[egreso] != '' ? 'false' : 'true';
		$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Factura',730,580,'agregar_factura.php?id_factura=$id_factura&codigo_cliente=$codigo_cliente&popup=1');\" ><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar></a>&nbsp;";
		$html_opcion .= "<a href='javascript:void(0)' onclick=\"ImprimirDocumento(".$id_factura.");\" ><img src='".Conf::ImgDir()."/pdf.gif' border=0 title=Imprimir></a>";
		return $html_opcion;
	}
	
	function SubTotal(& $fila)
	{
		$subtotal = $fila->fields['honorarios'] +$fila->fields['subtotal_gastos'] +$fila->fields['subtotal_gastos_sin_impuesto'];
		
		return $subtotal > 0 ? $fila->fields['simbolo'].' '.number_format($subtotal,$fila->fields['cifras_decimales'],",",".") : '';
	}
	function Iva(& $fila)
	{
		return $fila->fields['iva'] > 0 ? $fila->fields['simbolo'].' '.number_format($fila->fields['iva'],$fila->fields['cifras_decimales'],",",".") : '';
	}
	function MontoTotal(& $fila)
	{
		return $fila->fields['total'] > 0 ? $fila->fields['simbolo'].' '.number_format($fila->fields['total'],$fila->fields['cifras_decimales'],",",".") : '';
	}

	function Saldo(& $fila)
	{
		$saldo = $fila->fields['saldo']*(-1);
		return  $fila->fields['simbolo'].' '.number_format($saldo,$fila->fields['cifras_decimales'],",",".");
	}
	function Pago(& $fila, $sesion)
	{
		$query = "SELECT SUM(ccfmn.monto) as monto_aporte
						,ccfm.id_moneda as id_moneda
						,mo.cifras_decimales
						,mo.simbolo
					FROM factura_pago AS fp
					JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
					JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
					LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
					LEFT JOIN prm_moneda mo ON ccfm.id_moneda = mo.id_moneda
					WHERE ccfm2.id_factura =  '".$fila->fields['id_factura']."' GROUP BY ccfm2.id_factura ";

		//echo "<br>".$query;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$monto_pago = 0;
		$simbolo_aporte_pago = $fila->fields['simbolo'];
		$cifras_decimales_aporte_pago = $fila->fields['cifras_decimales'];
		while(list($monto_aporte,$id_moneda_aporte,$cifras_decimales_aporte,$simbolo_aporte) = mysql_fetch_array($resp)){
			$monto_pago = $monto_aporte;
			$simbolo_aporte_pago = $simbolo_aporte;
			$cifras_decimales_aporte_pago = $cifras_decimales_aporte;
		}
		return  $simbolo_aporte_pago.' '.number_format($monto_pago,$cifras_decimales_aporte_pago,",",".");
	}

	function FechaUltimoPago(& $fila, $sesion)
	{
		$query = "SELECT MAX(ccfm.fecha_modificacion) as ultima_fecha_pago
					FROM factura_pago AS fp
					JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
					JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
					LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
					LEFT JOIN prm_moneda mo ON ccfm.id_moneda = mo.id_moneda
					WHERE ccfm2.id_factura =  '".$fila->fields['id_factura']."' GROUP BY ccfm2.id_factura ";

		//echo "<br>".$query;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$monto_pago = 0;
		$simbolo_aporte_pago = $fila->fields['simbolo'];
		$cifras_decimales_aporte_pago = $fila->fields['cifras_decimales'];
		list($ultima_fecha_pago) = mysql_fetch_array($resp);
		return  $ultima_fecha_pago;
	}

	function Glosa_asuntos(& $fila, $sesion)
	{
		$query = "SELECT GROUP_CONCAT(ca.codigo_asunto SEPARATOR ', ') , GROUP_CONCAT(a.glosa_asunto SEPARATOR ', ')
					FROM cobro_asunto ca
					LEFT JOIN asunto a ON ca.codigo_asunto = a.codigo_asunto
					WHERE ca.id_cobro='".$fila->fields['id_cobro']."' GROUP BY ca.id_cobro";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$lista_asuntos = '';
		$lista_asuntos_glosa = '';
		while(list($lista_codigo_asunto,$lista_glosa_asunto) = mysql_fetch_array($resp)){
			$lista_asuntos = '('.$lista_codigo_asunto.')';
			$lista_asuntos_glosa = $lista_glosa_asunto;
		}
		$lista_asuntos_glosa = str_replace(', ','<br />',$lista_asuntos_glosa);
		return  $lista_asuntos_glosa;
	}
	
	function funcionTR(& $fila)
	{
		global $sesion;
		global $id_cobro;
		static $i = 0;
		
		if($i % 2 == 0)
			$color = "#dddddd";
		else
			$color = "#ffffff";
		
		$html .= "<tr id=\"t".$fila->fields['id_factura']."\" bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B;\">";
		$glosa_tramite = $tramite->fields['glosa_tramite'];
		$html .= "<td align=left>".Utiles::sql2fecha($fila->fields['fecha'],'%d-%m-%y')."</td>";
		$html .= "<td align=left>".$fila->fields['tipo']."</td>";
		$html .= "<td align=right>#".$fila->fields['numero']."&nbsp;</td>";
		$html .= "<td align=left>".$fila->fields['glosa_cliente']."</td>";
		$html .= "<td align=left>".Glosa_asuntos(& $fila, $sesion)."</td>";
		$html .= "<td align=left>".$fila->fields['encargado_comercial']."</td>";
		$html .= "<td align=left>".$fila->fields['descripcion']."</td>";
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') )
			$html .= "<td align=center>".$fila->fields['estado']."</td>";
		else
			$html .= "<td align=center>".$fila->fields['anulado']."</td>";
		$html .= "<td align=center><a href='javascript:void(0)' onclick=\"nuevaVentana('Editar Cobro',950,660,'cobros6.php?id_cobro=".$fila->fields['id_cobro']."&popup=1');\">".$fila->fields['id_cobro']."</a></td>";
		$html .= "<td align=right nowrap>".SubTotal(& $fila)."</td>";
		$html .= "<td align=right nowrap>".Iva(& $fila)."</td>";
		$html .= "<td align=right nowrap>".MontoTotal(& $fila)."</td>";
		$html .= "<td align=right nowrap>".Pago(& $fila, $sesion)."</td>";
		$html .= "<td align=right nowrap>".Saldo(& $fila)."</td>";
		$html .= "<td align=right>".FechaUltimoPago(& $fila, $sesion)."</td>";
		$html .= "<td align=center nowrap>".Opciones(& $fila)."</td>";
		$html .= "</tr>";

    $i++;
    return $html;
	}

?>
<script style="text/javascript">
function ImprimirDocumento( id_factura )
{
	var fecha1=$('fecha1').value;
	var fecha2=$('fecha2').value;
	var vurl = 'facturas.php?opc=generar_factura&id_factura_grabada=' + id_factura + '&fecha1=' + fecha1 + '&fecha2=' + fecha2;
	
	self.location.href=vurl;
}

function Refrescar()
{
		BuscarFacturas('','buscar');
}

function BuscarFacturas( form, from )
{
	if(!form)
		var form = $('form_facturas');
	if(from == 'buscar') {
		form.action = 'facturas.php?buscar=1';
	}
	else if(from == 'exportar_excel'){
		form.action = 'facturas.php?exportar_excel=1';
	}
	else
		return false;
	form.submit();
	return true;
}

function AgregarNuevo()
{
	var urlo = "agregar_factura.php?popup=1";
	nuevaVentana('Agregar_Factura',730,470,urlo,'top=100, left=125');
}
</script>

<? echo Autocompletador::CSS(); ?>
<form method='post' name="form_facturas" id="form_facturas">
<input type='hidden' name='opc' id='opc' value='buscar'>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<?
if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) 
{ 
echo "<table width=\"90%\"><tr><td>"; 
$class_diseno = 'class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;"';
}
else
$class_diseno = '';
?>
<fieldset class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;">
<legend><?=__('Filtros')?></legend>
<table style="border: 0px solid black" width='720px'>
	    <tr>
        <td align=right width="20%">
			<?=__('Cliente')?>
        </td>
        <td colspan="3" align=left nowrap>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario);
		else
			echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,""           ,"CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320,$codigo_asunto_secundario);
		}
		else
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320,$codigo_asunto);
		}
	}
?>
        </td>
     </tr>
     <tr>
        <td align='right' width="20%">
             <?=__('Asunto')?>
        </td>
        <td colspan="3" align=left nowrap>
<?

					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto,"","CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}

?>
       </td>
    </tr>
    <tr>
		<td align=right>
			<?=__('Tipo de Documento')?>
		</td>
		<td align=left >
			<?= Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal",'tipo_documento_legal_buscado',$tipo_documento_legal_buscado,'','Cualquiera',150); ?>
		</td>
		<td align=right>
			<?=__('Grupo Ventas')?>
			<input type=checkbox name=grupo_ventas id=grupo_ventas value=1 <?=$grupo_ventas ? 'checked' : '' ?>>
		</td>
    </tr>
	<tr>
		<td align=right>
			<?=__('Estado')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC","id_estado", $id_estado, 'onchange="mostrarAccionesEstado(this.form)"','Cualquiera',"150"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Moneda')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY glosa_moneda ASC","id_moneda", $id_moneda, '','Cualquiera',"150"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('N° Factura')?>
		</td>
		<td align=left width="18%">
			<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="numero" name="numero" size="15" value="<?=$numero?>" onchange="this.value=this.value.toUpperCase();">
		</td>
		<td align=right width="18%">
			<?=__('N° Cobro')?>
		</td>
		<td align=left width="44%">
			<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="id_cobro" name="id_cobro" size="15" value="<?=$id_cobro?>">
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Fecha')?>
		</td>
		<td nowrap align=left>
			<input type="text" id="fecha1" name="fecha1" value="<?=$fecha1 ?>" id="fecha1" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha1" style="cursor:pointer" />
		</td>
		<td>
		</td>
		<td nowrap align=right>
			<input type="text" id="fecha2" name="fecha2" value="<?=$fecha2 ?>" id="fecha2" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha2" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td colspan=3 align=right>
			<input name=boton_buscar id='boton_buscar' type=button value="<?=__('Buscar')?>" onclick="BuscarFacturas(this.form,'buscar')" class=btn>
		</td>
		<td align="right">
			<input type="button" value="<?php echo  __('Descargar Excel');?>" class="btn" name="boton_excel" onclick="BuscarFacturas(this.form, 'exportar_excel')">
		</td>
	</tr>
</table>
</fieldset><?
if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) 
	echo "</td></tr></table>"; ?>
</form>
<!--table style="border: 0px solid black" width='94%'>
	<tr>
		<td > &nbsp;</td>
		<td width=220px align="right" style='border: 1px solid #BDBDBD'>
			<b><?=__('Nueva')?>:</b>&nbsp;
			<?= Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal",'tipo_documento_legal','','','',150); ?>
			<br>
			<span onclick="CrearNuevoDocumentoLegal()" >			
				<img src="<?=Conf::ImgDir()?>/mas_16.gif" /><a href="javascript:void(0)"><?=__('Agregar Documento Tributario')?></a>
				<br>&nbsp;
			</span>
		</td>
	</tr>
</table-->
<script type="text/javascript">
function CrearNuevoDocumentoLegal()
{
	var dl_url = 'agregar_factura.php?popup=1&id_documento_legal='+$('tipo_documento_legal').value;
	if($('codigo_cliente')){
		dl_url += '&codigo_cliente='+$('codigo_cliente').value
	}
	if($('id_cobro')){
		dl_url += '&id_cobro='+$('id_cobro').value
		$('id_cobro').focus();
	}
	nuevaVentana('Agregar_Factura',730,580,dl_url, 'top=100, left=155');')	';	
}
	
	
Calendar.setup(
	{
		inputField	: "fecha1",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha1"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha2",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha2"		// ID of the button
	}
);
</script>
<?
	if($opc == 'buscar')
	{
		$b->Imprimir();
	}

	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion));
	}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
