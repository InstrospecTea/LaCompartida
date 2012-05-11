<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Gasto.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	#require_once Conf::ServerDir().'/classes/GastoGeneral.php';

	$sesion = new Sesion(array('OFI'));
	$pagina = new Pagina($sesion);
	
	$gasto = new Gasto($sesion);
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

	set_time_limit(300);
	
	if($id_gasto != "")
	{
		$gasto->Load($id_gasto);
		if($accion == "eliminar")
		{
			if($gasto->Eliminar())
				$pagina->AddInfo(__('El gasto ha sido eliminado satisfactoriamente'));
		}
	}
	

	$pagina->titulo = __('Revisar Gastos');
	$pagina->PrintTop();

	if($opc == 'buscar')
	{
		if($orden == "")
			$orden = "fecha DESC";

		if($where == '')
		{
			$where = 1;
			if( UtilesApp::GetConf($sesion,'CodigoSecundario') )
				{
					if( $codigo_cliente_secundario )
					{
							$where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario'";
							$cliente = new Cliente($sesion);
							$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
						if($codigo_asunto_secundario)
						{
							$asunto = new Asunto($sesion);
							$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
							$query_asuntos = "SELECT codigo_asunto_secundario FROM asunto WHERE id_contrato = '".$asunto->fields['id_contrato']."' ";
							$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh);
							$asuntos_list_secundario = array();
							while( list($codigo) = mysql_fetch_array($resp) )
							{
								array_push($asuntos_list_secundario,$codigo);
							}
							$lista_asuntos_secundario = implode("','", $asuntos_list_secundario);
						}
					}
				}
				else 
				{
					if( $codigo_cliente )
					{
							$where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
							$cliente = new Cliente($sesion);
							$cliente->LoadByCodigo($codigo_cliente);
						if($codigo_asunto)
						{
							$asunto = new Asunto($sesion);
							$asunto->LoadByCodigo($codigo_asunto);
							$query_asuntos = "SELECT codigo_asunto FROM asunto WHERE id_contrato = '".$asunto->fields['id_contrato']."' ";
							$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh);
							$asuntos_list = array();
							while( list($codigo) = mysql_fetch_array($resp) )
							{
								array_push($asuntos_list,$codigo);
							}
							$lista_asuntos = implode("','", $asuntos_list);
						}
					}
				}
			if( $fecha1 != '' ) $fecha_ini = Utiles::fecha2sql($fecha1); else $fecha_ini = '';
			if( $fecha2 != '' ) $fecha_fin = Utiles::fecha2sql($fecha2); else $fecha_fin = '';
			
			if($cobrado == 'NO')
				$where .= " AND cta_corriente.id_cobro is null ";
			if($cobrado == 'SI')
				$where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado='INCOBRABLE') ";
			if($codigo_asunto && $lista_asuntos)
				$where .= " AND cta_corriente.codigo_asunto IN ('$lista_asuntos')";
			if($codigo_asunto_secundario && $lista_asuntos_secundario)
				$where .= " AND asunto.codigo_asunto_secundario IN ('$lista_asuntos_secundario')";
			if($id_usuario_orden)
				$where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
			if($id_usuario_responsable)
				$where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable' ";
			if($id_tipo)
				$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
			if($clientes_activos == 'activos')
				$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
			if($clientes_activos == 'inactivos')
				$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
			if($fecha1 && $fecha2)
				$where .= " AND cta_corriente.fecha BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
			else if($fecha1)
				$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."' ";
			else if($fecha2)
				$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2)."' ";
			else if(!empty($id_cobro))
				$where .= " AND cta_corriente.id_cobro='$id_cobro' ";
			
			// Filtrar por moneda del gasto
			if ($moneda_gasto != '')
				$where .= " AND cta_corriente.id_moneda=$moneda_gasto ";
		}
		else
			$where = base64_decode($where);
		
		$idioma_default = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
		$idioma_default->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
		
		$total_cta = number_format(UtilesApp::TotalCuentaCorriente($sesion, $where),0,$idioma_default->fields['separador_decimales'],$idioma_default->fields['separador_miles']);

		
		$col_select ="";
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		{
			$col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";
		}

		$query = "SELECT SQL_CALC_FOUND_ROWS 
									cta_corriente.id_movimiento,
									cta_corriente.fecha,
									cta_corriente.egreso, 
									cta_corriente.ingreso, 
									cta_corriente.monto_cobrable, 
									cta_corriente.codigo_cliente, 
									cta_corriente.numero_documento,
									cta_corriente.numero_ot,
									cta_corriente.descripcion,
									cta_corriente.id_cobro,
									asunto.glosa_asunto,
									cliente.glosa_cliente, 
									prm_moneda.simbolo,
									prm_moneda.cifras_decimales,
									prm_cta_corriente_tipo.glosa as tipo, 
									cobro.estado, 
									cta_corriente.con_impuesto,
									prm_idioma.codigo_idioma,
                                                                        contrato.activo AS contrato_activo
									$col_select
								FROM cta_corriente
								LEFT JOIN asunto USING(codigo_asunto)
								LEFT JOIN prm_idioma ON asunto.id_idioma = prm_idioma.id_idioma 
								LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
								LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
								LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro
								LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
								LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo=prm_cta_corriente_tipo.id_cta_corriente_tipo
								JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
								WHERE $where";
		$x_pag = 12;
		$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
		$b->formato_fecha = "$formato_fecha";
		$b->nombre = "busc_gastos";
		$b->titulo = "Gastos por ".__('asunto');
		#$b->AgregarFuncion("Nombre",__('Nombre'));
		$b->AgregarEncabezado("fecha",__('Fecha'));
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
			$b->AgregarEncabezado("numero_documento",__('N� Doc'));
		}
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) )
		{
				$b->AgregarEncabezado("numero_ot",__('N� OT'));
		}
		$b->AgregarEncabezado("glosa_cliente",__('Cliente'), "align=left");
		$b->AgregarEncabezado("glosa_asunto",__('Asunto'), "align=left");
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
		{
			$b->AgregarEncabezado("tipo",__('Tipo'),"align=left");
		}
		$b->AgregarEncabezado("descripcion",__('Descripci�n'),"align=left");
		$b->AgregarFuncion("Egreso","Monto","align=right nowrap");
		$b->AgregarFuncion("Ingreso","Ingreso","align=right nowrap");
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )
			{
				$b->AgregarEncabezado("con_impuesto","Impuesto","align=center");
			}
		$b->AgregarFuncion(__('Cobro'),"CobroFila","align=left nowrap");
		if( UtilesApp::GetConf($sesion,'FacturaAsociada') && !(UtilesApp::GetConf($sesion,'FacturaAsociadaEsconderListado')) )
		{
			$b->AgregarEncabezado("codigo_factura_gasto","Factura","align=left nowrap");
			$b->AgregarFuncion(__('Fecha Factura'),"FechaFactura","align=center nowrap");
		}
		$b->AgregarEncabezado("estado",__('Estado') . " " . __("Cobro") ,"align=left");
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		{
			$b->AgregarEncabezado("esCobrable","Cobrable","align=center");
		}
                $b->AgregarEncabezado("contrato_activo", __('Contrato') . ' ' . __('Activo'), "align='right nowrap'");
		$b->AgregarFuncion(__('Opci�n'),"Opciones","align=right nowrap");

		$b->color_mouse_over = "#bcff5c";

		function FechaFactura(& $fila)
		{
			$html_fecha_factura .= "&nbsp;".( !empty($fila->fields['fecha_factura']) ? Utiles::sql2fecha($fila->fields['fecha_factura'],"%m/%d/%Y") : "-")."&nbsp;";
			return $html_fecha_factura;
		}
		
		function CobroFila(& $fila)
		{
			$html_cobro .= "&nbsp;<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Contrato',810,700,'cobros6.php?id_cobro=".$fila->fields['id_cobro']."&popup=1&contitulo=true');\" title='".__('Ver ') . __('Cobro asociado')."'>".$fila->fields['id_cobro']."</a>&nbsp;";
			return $html_cobro;
		}
		
		function Opciones(& $fila)
		{
			global $sesion;
			$html_opcion = "";
			//la variable editar existe para que en el caso de que el cobro ya est� emitido no se pueda modificar
			$editar=false;
			if($fila->fields[estado] == 'CREADO' || $fila->fields[estado] == 'EN REVISION' || empty($fila->fields[estado]))
				$editar=true;

			$id_gasto = $fila->fields['id_movimiento'];
			$prov = $fila->fields[egreso] != '' ? 'false' : 'true';
			if($editar)
			{
				$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuovaFinestra('Editar_Gasto',730,580,'agregar_gasto.php?id_gasto=$id_gasto&popup=1&prov=$prov');\" ><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar></a>&nbsp;";
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
					$html_opcion .= "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaGasto($id_gasto)\" ><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 title=Eliminar></a>";
				else	
					$html_opcion .= "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaGasto($id_gasto)\" ><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 title=Eliminar></a>";
			}
			else
				$html_opcion .= "<a href='javascript:void(0)' onclick=\"alert('".__('No se puede modificar este gasto.\n') . __('El Cobro') . __(' que lo incluye ya ha sido Emitido al Cliente.')."');\" ><img src='".Conf::ImgDir()."/editar_off.gif' border=0 title=\"".__('Cobro ya Emitido al Cliente')."\"></a>&nbsp;";
			return $html_opcion;
		}
		/*function Nombre(& $fila)
		{
			return $fila->fields[apellido1].", ".$fila->fields[nombre];
		}*/
		function Monto(& $fila)
		{
			global $sesion;
			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			if( $fila->fields['codigo_idioma'] != '' )
				$idioma->Load($fila->fields['codigo_idioma']);
			else
				$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
			return $fila->fields['egreso'] > 0 ? $fila->fields[simbolo] . " " .number_format($fila->fields['monto_cobrable'],$fila->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : '';
		}

		function Ingreso(& $fila)
		{
			global $sesion;
			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			if( $fila->fields['codigo_idioma'] != '' )
				$idioma->Load($fila->fields['codigo_idioma']);
			else
				$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
			return $fila->fields['ingreso'] > 0 ? $fila->fields['simbolo'] . " " .number_format($fila->fields['monto_cobrable'],$fila->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : '';
		}
	}
	elseif($opc == 'xls')
	{
		require_once('gastos_xls.php');
		exit;
	}
	elseif($opc == 'xls_resumen')
	{
		require_once('gastos_xls_resumen.php');
		exit;
	}
	if( $preparar_cobro == 1 )
	{
		$where = 1;
		if($id_usuario)
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) {
			if($codigo_cliente_secundario) 
				$where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario' ";
			}
		else {
		if($codigo_cliente)
			$where .= " AND contrato.codigo_cliente = '$codigo_cliente' ";
		}			
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) {
			if($codigo_asunto_secundario)
						{
							$asunto = new Asunto($sesion);
							$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
							$query_asuntos = "SELECT codigo_asunto_secundario FROM asunto WHERE id_contrato = '".$asunto->fields['id_contrato']."' ";
							$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh);
							$asuntos_list_secundario = array();
							while( list($codigo) = mysql_fetch_array($resp) )
							{
								array_push($asuntos_list_secundario,$codigo);
							}
							$lista_asuntos_secundario = implode("','", $asuntos_list_secundario);
							if($lista_asuntos_secundario)
								$where .= " AND asunto.codigo_asunto IN ('$lista_asuntos_secundario')";
						}
			}
		else {
			if($codigo_asunto)
						{
							$asunto = new Asunto($sesion);
							$asunto->LoadByCodigo($codigo_asunto);
							$query_asuntos = "SELECT codigo_asunto FROM asunto WHERE id_contrato = '".$asunto->fields['id_contrato']."' ";
							$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh);
							$asuntos_list = array();
							while( list($codigo) = mysql_fetch_array($resp) )
							{
								array_push($asuntos_list,$codigo);
							}
							$lista_asuntos = implode("','", $asuntos_list);
							if($lista_asuntos)
							 $where .= " AND asunto.codigo_asunto IN ('$lista_asuntos')";
						}
				
			}
		$query = "SELECT SQL_CALC_FOUND_ROWS contrato.id_contrato,cliente.codigo_cliente, contrato.id_moneda, contrato.forma_cobro, contrato.monto, contrato.retainer_horas,
									contrato.id_moneda
									FROM contrato
									JOIN tarifa ON contrato.id_tarifa = tarifa.id_tarifa
									LEFT JOIN asunto ON asunto.id_contrato=contrato.id_contrato
									JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
									JOIN prm_moneda  ON (prm_moneda.id_moneda=contrato.id_moneda)
									WHERE $where AND contrato.incluir_en_cierre = 1
									GROUP BY contrato.id_contrato";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		#cobros solo gastos
			while($contra = mysql_fetch_array($resp))
			{
				$cobro = new Cobro($sesion);
				if(!$id_proceso_nuevo)
				{
					$id_proceso_nuevo = $cobro->GeneraProceso();
				}
				//Por conf se permite el uso de la fecha desde
				$fecha_ini_cobro = "";
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarFechaDesdeCobranza') ) || ( method_exists('Conf','UsaFechaDesdeCobranza') && Conf::UsaFechaDesdeCobranza() ) ) && $fecha_ini)
					$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);

				$cobro->PrepararCobro($fecha_ini_cobro,Utiles::fecha2sql($fecha_fin),$contra['id_contrato'], false , $id_proceso_nuevo,'','',true,true,1,0);
			}
}

?>
    <style type="text/css">
      @import "https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css";
  
    </style>
   <script  src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="https://estaticos.thetimebilling.com/tabletools/js/TableTools.js"></script>
<script type="text/javascript">
function Preparar_Cobro(form)
{
	form.action = 'gastos.php?preparar_cobro=1';
	form.submit();
}
	
function EliminaGasto(id)
{
	var form = document.getElementById('form_gastos'); <?
	if(( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) ) { ?>
		var acc = 'gastos.php?id_gasto='+id+'&accion=eliminar&codigo_cliente='+$('codigo_cliente_secundario').value+'&codigo_asunto='+$('codigo_asunto_secundario').value+'&fecha1='+$('fecha1').value+'&fecha2='+$('fecha2').value<?=( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) ) ? "+'&id_tipo='+$('id_tipo').value" : "" ?>+'&opc=buscar';
	<? }
	else { ?>
		var acc = 'gastos.php?id_gasto='+id+'&accion=eliminar&codigo_cliente='+$('codigo_cliente').value+'&codigo_asunto='+$('codigo_asunto').value+'&fecha1='+$('fecha1').value+'&fecha2='+$('fecha2').value<?=( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) ) ? "+'&id_tipo='+$('id_tipo').value" : "" ?>+'&opc=buscar';
	<? } ?>
	if(parseInt(id) > 0 && confirm('�Desea eliminar el gasto seleccionado?') == true)
		self.location.href = acc;
}

/*
function change_height(iframe)
{
    if(document.getElementById && !(document.all))  //Mozillla
	{
		body = iframe.contentDocument.body;
    }
    else if(document.all) //Explorer
	{
		body = iframe.document.body;
    }
	height = body.scrollHeight + (body.offsetHeight - body.clientHeight);
	iframe.style.height = height + "px";//height;
}

function VerClientesProyectos (form)
{
    var cliente  = document.getElementById('cliente');
		var proyecto = document.getElementById('proyecto');
    if(form.general.checked == true)
    {
        cliente.style['display']  = "inline";
		proyecto.style['display'] = "inline";
    }
    else
    {
      cliente.style['display']  = "none";
      proyecto.style['display'] = "none";
	}
}

var getFFVersion = navigator.userAgent.substring(navigator.userAgent.indexOf("Firefox")).split("/")[1];
var FFextraHeight = parseFloat(getFFVersion)>=0.1? 16 : 0 //extra height in px to add to iframe in FireFox 1.0+ browsers

function resizeIframe(frameid)
{
	var currentfr = document.getElementById(frameid)
	if (currentfr && !window.opera)
	{
		if (currentfr.contentDocument && currentfr.contentDocument.body.offsetHeight) //ns6 syntax
			currentfr.style.height = currentfr.contentDocument.body.offsetHeight + FFextraHeight + "px";
		else if (currentfr.Document && currentfr.Document.body.scrollHeight) //ie5+ syntax
			currentfr.style.height = currentfr.Document.body.scrollHeight;
	}
}
*/

function AgregarNuevo(tipo)
{
	<?
	if(( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) ) { ?>
		var codigo_cliente = $('codigo_cliente_secundario').value;
		var codigo_asunto = $('codigo_asunto_secundario').value;
		var url_extension = "&codigo_cliente_secundario="+codigo_cliente+"&codigo_asunto_secundario="+codigo_asunto;
	<? }
	else { ?>
		var codigo_cliente = $('codigo_cliente').value;
		var codigo_asunto = $('codigo_asunto').value;
		var url_extension = "&codigo_cliente="+codigo_cliente+"&codigo_asunto="+codigo_asunto;
<? } ?>

	if(tipo == 'provision')
	{
		var urlo = "agregar_gasto.php?popup=1&prov=true"+url_extension;
		var ancho=730;
                var alto=400;
	}
	else if(tipo == 'gasto')
	{
		var urlo = "agregar_gasto.php?popup=1&prov=false"+url_extension;
		var ancho=730;
                var alto=570;
	}
        
        
                            	nuovaFinestra('Agregar_Gasto',ancho,alto,urlo);

                       
        
}

function BuscarGastos( form, from )
{
	<?
		$pagina_excel = "form.action = 'gastos_xls.php';";	
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ExcelGastosSeparado') ) || ( method_exists('Conf','ExcelGastosSeparado') && Conf::ExcelGastosSeparado() ) )
		{
			$pagina_excel = "form.action = 'gastos_xls_separado.php';";
		}
		if( UtilesApp::GetConf($sesion,'ExcelGastosDesglosado') )
		{
			$pagina_excel = "form.action = 'gastos_xls_por_encargado.php';";
		}
	?>


	if(!form)
		var form = $('form_gastos');
	if(from == 'buscar') {
		form.action = 'gastos.php?buscar=1';
	} else if(from == 'excel') {
		<?=$pagina_excel?>
	} else if(from == 'excel_resumen') {
		form.action = 'gastos_xls_resumen.php';
	} else if(from =='estimar_datos') {

	jQuery.post('ajax/estimar_datos.php',jQuery('#form_gastos').serialize(),function(data) {
	    alert('Su consulta recorre '+data+' registros');
	});
	return false;
	} else if(from =='datatables') {

	

      
 jQuery('#tablon').dataTable({
		  "bDestroy":true,
               
		 		"oLanguage": {   
		    "sProcessing":   "Procesando..." ,
		    "sLengthMenu":   "Mostrar _MENU_ registros",
		    "sZeroRecords":  "No se encontraron resultados",
		    "sInfo":         "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
		    "sInfoEmpty":    "Mostrando desde 0 hasta 0 de 0 registros",
		    "sInfoFiltered": "(filtrado de _MAX_ registros en total)",
		    "sInfoPostFix":  "",
		    "sSearch":       "Buscar:",
		    "sUrl":          "",
		    "oPaginate": {
			"sFirst":    "Primero",
			"sPrevious": "Anterior",
			"sNext":     "Siguiente",
			"sLast":     "�ltimo"
		 }
	 },
	 "bFilter": false,
	 

		  
		     "bProcessing": true,
	   /* "bServerSide": true,
	     "sServerMethod": "POST",*/
	    "sAjaxSource": "ajax/gastos_ajax.php",
		     "bJQueryUI": true,
		     "bDeferRender": true,
		    
		"sServerParams": jQuery('#form_gastos').serialize(),
	 
	  "aoColumnDefs": [
	       {  "sClass": "alignleft",    "aTargets": [ 1,2,3,4 ]   },
	       
	       {  "sClass": "tablagastos",    "aTargets": [ 0,1,2,3,4,5,6,7,8,9,10,11,12 ]   },
	         {  "sWidth": "30px",    "aTargets": [ 12 ]   },
		 {  "sWidth": "50px",    "aTargets": [0,5,6 ]   },
		  {  "bSortable":false,    "aTargets": [3,4,5,6,7,9,10,11, 12 ]   },
		     <?php 
		     //if ( !UtilesApp::GetConf($sesion,'NumeroGasto') ) echo ' { "bVisible": false, "aTargets": [ 14 ] },';
//if ( !UtilesApp::GetConf($sesion,'NumeroOT') ) echo ' { "bVisible": false, "aTargets": [ 14 ] },';
//if ( !UtilesApp::GetConf($sesion,'FacturaAsociada') ) echo ' { "bVisible": false, "aTargets": [ 14 ] },';

if ( !UtilesApp::GetConf($sesion,'UsarImpuestoPorGastos') ) echo ' { "bVisible": false, "aTargets": [ 7 ] },';
if ( !UtilesApp::GetConf($sesion,'TipoGasto') ) echo ' { "bVisible": false, "aTargets": [ 3 ] },';
if ( !UtilesApp::GetConf($sesion,'UsarGastosCobrable') ) echo ' { "bVisible": false, "aTargets": [ 11 ] },';?>    
	{  "fnRender": function ( o, val ) {
          return "<a href=\"#\" style=\"float:left;display:inline;\" onclick=\"nuevaVentana('Editar_Gasto',1000,700,'agregar_gasto.php?id_gasto="+o.aData[12]+"&popup=1&contitulo=true&id_foco=7', '');\"><img border='0' title='Editar' src='<?php echo Conf::ImgDir()?>/editar_on.gif'></a><a style='float:left;display:inline;' onclick='parent.EliminaGasto("+o.aData[12]+")' href='javascript:void(0)' target='_parent'><img border='0' title='Eliminar' src='<?php echo Conf::ImgDir()?>/cruz_roja_nuevo.gif'></a>";
        },    "aTargets": [ 12 ]   },
	{  "fnRender": function ( o, val ) {
          return "<a title=\"Ver Cobro asociado\" onclick=\"nuevaVentana('Editar_Contrato',810,700,'cobros6.php?id_cobro="+o.aData[8]+"&amp;popup=1&amp;contitulo=true');\" href=\"javascript:void(0)\">"+o.aData[8]+"</a>";
        },    "aTargets": [ 8 ]   },
	
	{  "fnRender": function ( o, val ) {
          return "<a title=\"Ver Cobro asociado\" onclick=\"nuevaVentana('Editar_Contrato',810,700,'cobros6.php?id_cobro="+o.aData[8]+"&amp;popup=1&amp;contitulo=true');\" href=\"javascript:void(0)\">"+o.aData[8]+"</a>";
        },    "aTargets": [ 8 ]   }
	
   
    
    ],
   
	    "iDisplayLength": 25,
	    "aLengthMenu": [[25, 50, 100,200, -1], [25, 50, 100,200, "Todo"]],
	    "sPaginationType": "full_numbers",
	    "sDom":  'T<"top"lp>rt<"bottom"i>',
	    "oTableTools": {            "sSwfPath": "../js/copy_cvs_xls.swf",	"aButtons": [ "xls","copy", "print" ]        }
	  ,"aaSorting": [[ 2, "asc" ]]
	     }).show();



		
		
		
		
	
	return false;
	} else {
	    	return false;
	}
	form.submit();
	return true;
}
function Refrescar()
{
<?
	if($desde)
		echo "var pagina_desde = '&desde=".$desde."';";
	else
		echo "var pagina_desde = '';";
	if($orden)
		echo "var orden = '&orden=".$orden."';";
	else
		echo "var orden = '';";
?>
	var opc= $('opc').value;
	var codigo_cliente = $('codigo_cliente').value;
	var codigo_asunto = $('codigo_asunto').value;
	var fecha1 = $('fecha1').value;
	var fecha2 = $('fecha2').value;
	var id_usuario_orden = $('id_usuario_orden').value;
	var url = "gastos.php?opc="+opc+"&codigo_cliente="+codigo_cliente+"&codigo_asunto="+codigo_asunto+orden+"&fecha1="+fecha1+"&fecha2="+fecha2+"&id_usuario_orden="+id_usuario_orden+pagina_desde+"&buscar=1";
	self.location.href= url;


}
</script>
<? echo(Autocompletador::CSS()); ?>
<table width="90%"><tr><td>
<form method='post' name="form_gastos" action='' id="form_gastos">
<input type='hidden' name='opc' id='opc' value=buscar>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
<legend><?=__('Filtros')?></legend>
<table style="border: 0px solid black" width='720px'>
	<tr>
		<td align=right>
			<?=__('Cobrado')?>
		</td>
		<td align='left'>
			<?=Html::SelectQuery($sesion,"SELECT codigo_si_no, codigo_si_no FROM prm_si_no","cobrado",$cobrado,'','Todos','60')?>
			</td>
			<td align="left" nowrap>
				<?=__('id_cobro')?>&nbsp;
				<input onkeydown="if(event.keyCode==13)BuscarGastos(this.form, 'buscar')" type="text" size="6" name="id_cobro" id="id_cobro" value="<?=$id_cobro ?>">
			</td>
	</tr>
	<tr>
	    <td align=right width='30%'>
	        <?=__('Nombre Cliente')?>
	    </td>
	    <td nowrap colspan=3 align=left>
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
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto_secundario);
					else
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
				}?>
	  </td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Asunto') ?>
		</td>
		<td nowrap colspan=3 align=left>
		<?
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					else
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto,"", "CargarSelectCliente(this.value);", 320,$codigo_cliente);
		?>
		</td>
	</tr>
	<tr>
		<td nowrap colspan=4 align=center style='font-size:9px;'>
			<?=__('Si Ud. selecciona el').' '.__('asunto').' '.__('mostrar� los gastos de todos los').' '.__('asuntos').' '.__('que se cobrar�n en la misma carta.')?>
		</td>
	</tr>
	<tr>
    <td align=right>
			<?=__('Fecha Desde')?>
    </td>
    <td nowrap align=left>
    	<input onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha1" value="<?=$fecha1 ?>" id="fecha1" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha1" style="cursor:pointer" />
    </td>
    <td nowrap align=left colspan=2>
    	<?=__('Fecha Hasta')?>
    	<input onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha2" value="<?=$fecha2 ?>" id="fecha2" size="11" maxlength="10" />
	<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha2" style="cursor:pointer" />
    </td>
	</tr>
	<tr>
		<td align=right><?=__('Encargado comercial')?>&nbsp;</td>
		<td colspan=2 align=left><?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre","id_usuario_responsable",$id_usuario_responsable,'',__('Cualquiera'),'200')?>
	</tr>
	<tr>
 		<td align=right>
     	<?=__('Ordenado por')?>
    </td>
    <td align=left colspan=3>
    	<?= Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(' ', apellido1,apellido2,',',nombre) FROM usuario ORDER BY apellido1", "id_usuario_orden", $id_usuario_orden, "", __('Ninguno'),'200'); ?>
    </td>
	</tr>
<?
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
	{
?>
	<tr>
		<td align=right>
			<?=__('Tipo de Gasto')?>
		</td>
		<td align=left colspan=3>
			<?= Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo ORDER BY glosa", "id_tipo", $id_tipo, "", __('Cualquiera'),'200'); ?>
		</td>
	</tr>
<?
	}
?>
	<tr>
		<td align=right>
			<?=__('Clientes activos')?>
		</td>
		<td colspan="2" align="left">
			<select name='clientes_activos' id='clientes_activos' style='width: 120px;'>
				<option value=''> Todos </option>
				<option value='activos'> Solo activos </option>
				<option value='inactivos'> Solo inactivos </option>
			</select>
		</td>
		<td></td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Moneda')?>
		</td>
		<td colspan="2" align="left">
			<?= Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_gasto", $moneda_gasto, "", __('Todas'),''); ?>
		</td>
		<td></td>
	</tr>
	<tr>
		<td></td>
		<td colspan=2 align=left>
			<input name=boton_buscar id='boton_buscar' type=button value="<?=__('Buscar')?>" onclick="BuscarGastos(this.form,'buscar')" class=btn>
			<input name=boton_xls type=button value="<?=__('Descargar Excel')?>" onclick="BuscarGastos(this.form,'excel')" class=btn>
			<input name="boton_xls_resumen" type="button" value="<?=__('Descargar Resumen Excel')?>" onclick="BuscarGastos(this.form,'excel_resumen')" class="btn" />
			
			<input id="boton_xls_estimar" name="boton_xls_estimar" type="button" value="<?=__('Estimar Cantidad de Datos')?>" onclick="BuscarGastos(this.form,'estimar_datos')" class="btn" />
			<input id="boton_datatables" name="boton_datatables" type="button" value="<?=__('DataTables')?>" onclick="BuscarGastos(this.form,'datatables')" class="btn" />
		</td>
		<td width='40%' align=right>
			<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('provision')" title="Agregar provisi&oacute;n"><?=__('Agregar provisi�n')?></a>&nbsp;&nbsp;&nbsp;&nbsp;
			<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('gasto')" title="Agregar Gasto"><?=__('Agregar')?> <?=__('gasto')?></a>
		</td>
	</tr>
</table>
</fieldset>
<br>
<? 

if($buscar == 1 && ( $codigo_cliente != '' || $codigo_cliente_secundario !=''))
{
	?>
	<table width="100%">
		<tr>
			<td align="right">
				<input type="button" value="Generar borrador" class="btn" name="boton" onclick="Preparar_Cobro( this.form )">
			</td>
		</tr>
	</table>
	<?
} 
?>
	</form>
</td></tr></table>
<!--<iframe id="iframe2" src="lista_gastos.php?popup=1" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0" style="width:800px; height: 300px" onload="resizeIframe('iframe2');"></iframe>-->
<!--<iframe id="iframe1" src="lista_gastos_generales.php?popup=1" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0" style="width:800px; height: 300px" onload="resizeIframe('iframe1');"></iframe>-->
<script type="text/javascript">
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
<?php 
	echo '<table cellpadding="0" cellspacing="0" border="0" class="display" id="tablon" style="width:920px;display:none;">
	<thead>
		<tr class="encabezadolight">
		<th >Fecha</th>
<th>Cliente</th>
<th>Asunto</th>
<th>Tipo</th>
<th>Descripci�n</th>
<th>Egreso</th>
<th>Ingreso</th>
<th>Impuesto</th>
<th>Cobro</th>
<th>Estado Cobro</th>
<th>Cobrable</th>
<th>Contrato Activo</th>
<th>Opci�n</th></tr>
	</thead>
	<tbody>
		
	</tbody></table>';
	if($opc == 'buscar')
	{
		echo($total_cta ? "<table width=90%><tr><td align=left><span style='font-size:11px'><b>".__('Balance cuenta gastos: '.Moneda::GetSimboloMoneda($sesion,Moneda::GetMonedaBase($sesion)))." ".$total_cta."</b></span></td></tr></table>":"");
		$b->Imprimir();
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
		{
			echo(Autocompletador::Javascript($sesion));
		}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>