<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('OFI'));
$pagina = new Pagina($sesion);
$gasto = new Gasto($sesion);
$nuevo_modulo_gastos = UtilesApp::GetConf($sesion, 'NuevoModuloGastos');
$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

set_time_limit(300);

function FechaFactura(& $fila) {
	$html_fecha_factura .= "&nbsp;" . (!empty($fila->fields['fecha_factura']) ? Utiles::sql2fecha($fila->fields['fecha_factura'], "%m/%d/%Y") : "-") . "&nbsp;";
	return $html_fecha_factura;
}

function CobroFila(& $fila) {
	$html_cobro .= "&nbsp;<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Contrato',1024,700,'cobros6.php?id_cobro=" . $fila->fields['id_cobro'] . "&popup=1&contitulo=true');\" title='" . __('Ver ') . __('Cobro asociado') . "'>" . $fila->fields['id_cobro'] . "</a>&nbsp;";
	return $html_cobro;
}

function Opciones(& $fila) {
	global $sesion;
	$html_opcion = "";
	//la variable editar existe para que en el caso de que el cobro ya esté emitido no se pueda modificar
	$editar = false;
	if ($fila->fields[estado] == 'CREADO' || $fila->fields[estado] == 'EN REVISION' || empty($fila->fields[estado])) {
		$editar = true;
	}

	$id_gasto = $fila->fields['id_movimiento'];
	$prov = $fila->fields[egreso] != '' ? 'false' : 'true';
	if ($editar) {
		$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuovaFinestra('Editar_Gasto',730,580,'agregar_gasto.php?id_gasto=$id_gasto&popup=1&prov=$prov');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border=0 title=Editar></a>&nbsp;";
		if (UtilesApp::GetConf($sesion, 'UsaDisenoNuevo')) {
			$html_opcion .= "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaGasto($id_gasto)\" ><img src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' border=0 title=Eliminar></a>";
		} else {
			$html_opcion .= "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaGasto($id_gasto)\" ><img src='" . Conf::ImgDir() . "/cruz_roja.gif' border=0 title=Eliminar></a>";
		}
	} else {
		$html_opcion .= "<a href='javascript:void(0)' onclick=\"alert('" . __('No se puede modificar este gasto.\n') . __('El Cobro') . __(' que lo incluye ya ha sido Emitido al Cliente.') . "');\" ><img src='" . Conf::ImgDir() . "/editar_off.gif' border=0 title=\"" . __('Cobro ya Emitido al Cliente') . "\"></a>&nbsp;";
	}

	return $html_opcion;
}

function Monto(& $fila) {
	global $sesion;
	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	if ($fila->fields['codigo_idioma'] != '') {
		$idioma->Load($fila->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));
	}
	return $fila->fields['egreso'] > 0 ? $fila->fields[simbolo] . " " . number_format($fila->fields['monto_cobrable'], $fila->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '';
}

function Ingreso(& $fila) {
	global $sesion;
	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	if ($fila->fields['codigo_idioma'] != '') {
		$idioma->Load($fila->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));
	}
	return $fila->fields['ingreso'] > 0 ? $fila->fields['simbolo'] . " " . number_format($fila->fields['monto_cobrable'], $fila->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '';
}

if ($id_gasto != "") {
	$gasto->Load($id_gasto);
	if ($accion == "eliminar") {
		if ($gasto->Eliminar()) {
			$pagina->AddInfo(__('El gasto ha sido eliminado satisfactoriamente'));
		}
	}
}

if ($opc == 'buscar') {
	if ($orden == "") {
		$orden = "fecha DESC";
	}

	if ($where == '') {
		$where=$gasto->WhereQuery($_REQUEST);
	} else {
		$where = base64_decode($where);
	}

	if ($exportar_excel) {
		$search_query = $gasto->SearchQuery($sesion, $where);
		$gasto->DownloadExcel($search_query);
	}

	$idioma_default = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma_default->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));

	$total_cta = number_format($gasto::TotalCuentaCorriente($sesion, $where), 0, $idioma_default->fields['separador_decimales'], $idioma_default->fields['separador_miles']);
} else if ($opc == 'xls') {
	require_once('gastos_xls.php');
	exit;
} else if ($opc == 'xls_resumen') {
	require_once('gastos_xls_resumen.php');
	exit;
}

$pagina->titulo = __('Revisar Gastos');
$pagina->PrintTop();

if ($preparar_cobro == 1) {
	$where = 1;
	if ($id_usuario) {
		$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
	}

	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		if ($codigo_cliente_secundario) {
			$where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario' ";
		}
	} else {
		if ($codigo_cliente) {
			$where .= " AND contrato.codigo_cliente = '$codigo_cliente' ";
		}
	}

	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		if ($codigo_asunto_secundario) {
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
			$query_asuntos = "SELECT codigo_asunto_secundario FROM asunto WHERE id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
			$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos, __FILE__, __LINE__, $sesion->dbh);
			$asuntos_list_secundario = array();
			while (list($codigo) = mysql_fetch_array($resp)) {
				array_push($asuntos_list_secundario, $codigo);
			}
			$lista_asuntos_secundario = implode("','", $asuntos_list_secundario);
			if ($lista_asuntos_secundario) {
				$where .= " AND asunto.codigo_asunto IN ('$lista_asuntos_secundario')";
			}
		}
	} else {
		if ($codigo_asunto) {
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigo($codigo_asunto);
			$query_asuntos = "SELECT codigo_asunto FROM asunto WHERE id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
			$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos, __FILE__, __LINE__, $sesion->dbh);
			$asuntos_list = array();
			while (list($codigo) = mysql_fetch_array($resp)) {
				array_push($asuntos_list, $codigo);
			}
			$lista_asuntos = implode("','", $asuntos_list);
			if ($lista_asuntos) {
				$where .= " AND asunto.codigo_asunto IN ('$lista_asuntos')";
			}
		}
	}

	$query = "SELECT SQL_CALC_FOUND_ROWS
							contrato.id_contrato,
							cliente.codigo_cliente,
							contrato.id_moneda,
							contrato.forma_cobro,
							contrato.monto,
							contrato.retainer_horas,
							contrato.id_moneda
						FROM contrato
						JOIN tarifa ON contrato.id_tarifa = tarifa.id_tarifa
						LEFT JOIN asunto ON asunto.id_contrato=contrato.id_contrato
						JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
						JOIN prm_moneda  ON (prm_moneda.id_moneda=contrato.id_moneda)
						WHERE $where AND contrato.incluir_en_cierre = 1
						GROUP BY contrato.id_contrato";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	#cobros solo gastos
	while ($contra = mysql_fetch_array($resp)) {
		$cobro = new Cobro($sesion);
		if (!$id_proceso_nuevo) {
			$id_proceso_nuevo = $cobro->GeneraProceso();
		}
		//Por conf se permite el uso de la fecha desde
		$fecha_ini_cobro = "";
		if (UtilesApp::GetConf($sesion, 'UsarFechaDesdeCobranza') && $fecha_ini) {
			$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);
		}

		$cobro->PrepararCobro($fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $contra['id_contrato'], false, $id_proceso_nuevo, '', '', true, true, 1, 0);
	}
}
?>
<style type="text/css">
		@import "https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css";
		#tablon {border-spacing:0;border-collapse:collapse;}
		#tablon th {font-size:10px;}
		.dataTables_paginate {clear: both; margin: -20px 350px 15px 0;width:390px;vertical-align:middle;}
		.dttnombres, .dttactivo {text-align:left;font-size:10px;white-space: nowrap;}
		.dataTables_paginate .last, dataTables_paginate .first,  .DataTables_sort_icon {display:none;}
		.activo, .usuarioinactivo, .usuarioactivo {float:left;display:inline;}
		.inactivo {opacity:0.4;}
		.inactivo td {background:#F0F0F0;}
		/*th.dttpermisos {-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);}*/
		#contienefiltro {display:inline-block;margin-bottom:-8px;}
		#tablon_paginate .fg-button {padding:0 5px;}
		#tablon_paginate .first, #tablon_paginate .last {display:none;}
		#tablon  tbody tr.odd {background-color: #fff !important;}
		#tablon  tbody tr.even {background-color: #EFE !important;}
		td.sorting_1 {background:transparent !important;}
		.tipodescripcion {font-size:8pt;clear:left;overflow:visible;position:relative;margin:3px 0;}
		.marginleft {margin-left: 3px;}
		.eligegasto {float:left;margin-top: 0 !important;}
		#totalcta {float: left;position: relative;top: 15px;}
</style>
<script  src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://estaticos.thetimebilling.com/tabletools/js/TableTools.js"></script>
<script type="text/javascript">
	var contratos = {};
	var tablagastos = null;
	function Preparar_Cobro(form) {
		form.action = 'gastos.php?preparar_cobro=1';
		form.submit();
	}

	function EliminaGasto(id) {
		var form = document.getElementById('form_gastos');
<?php if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) { ?>
		var acc = 'gastos.php?id_gasto='+id+'&accion=eliminar&codigo_cliente='+$('codigo_cliente_secundario').value+'&codigo_asunto='+$('codigo_asunto_secundario').value+'&fecha1='+$('fecha1').value+'&fecha2='+$('fecha2').value<?php echo UtilesApp::GetConf($sesion, 'TipoGasto') ? "+'&id_tipo='+$('id_tipo').value" : "" ?>+'&opc=buscar';
<?php } else { ?>
		var acc = 'gastos.php?id_gasto='+id+'&accion=eliminar&codigo_cliente='+$('codigo_cliente').value+'&codigo_asunto='+$('codigo_asunto').value+'&fecha1='+$('fecha1').value+'&fecha2='+$('fecha2').value<?php echo UtilesApp::GetConf($sesion, 'TipoGasto') ? "+'&id_tipo='+$('id_tipo').value" : "" ?>+'&opc=buscar';

<?php } ?>
		if (parseInt(id) > 0 && confirm('¿Desea eliminar el gasto seleccionado?') == true) {
			self.location.href = acc;
		}
	}

	function CargarContrato(asunto) {
		var ajax_url = './ajax/ajax_gastos.php?opc=contratoasunto&codigo_asunto=' + asunto;
		jQuery.getJSON(ajax_url,function(data) {
			if (data) {
				jQuery('#id_contrato').val(data.id_contrato);
			}
		});
	}

	function AgregarNuevo(tipo) {
<?php if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) { ?>
		var codigo_cliente = $('codigo_cliente_secundario').value;
		var codigo_asunto = $('codigo_asunto_secundario').value;
		var url_extension = "&codigo_cliente_secundario="+codigo_cliente+"&codigo_asunto_secundario="+codigo_asunto;
<?php } else { ?>
		var codigo_cliente = $('codigo_cliente').value;
		var codigo_asunto = $('codigo_asunto').value;
		var url_extension = "&codigo_cliente="+codigo_cliente+"&codigo_asunto="+codigo_asunto;
<?php } ?>

		if (tipo == 'provision') {
			var urlo = "agregar_gasto.php?popup=1&prov=true"+url_extension;
			var ancho = 730;
			var alto = 400;
		} else if (tipo == 'gasto') {
			var urlo = "agregar_gasto.php?popup=1&prov=false"+url_extension;
			var ancho = 730;
			var alto = 570;
		}

		nuovaFinestra('Agregar_Gasto',ancho,alto,urlo);
	}

	jQuery('document').ready(function() {
		jQuery('#selectodos').live('click',function() {
			if(jQuery(this).is(':checked')) {
				jQuery('.eligegasto').attr('checked','checked');
			} else {
				jQuery('.eligegasto').removeAttr('checked');
			}
		});

		jQuery('.buscargastos').click(function() {
			var form=jQuery('#form_gastos');
			var from=jQuery(this).attr('rel');
			//jQuery(this).attr('disabled','disabled');
<?php
$pagina_excel = " jQuery('#form_gastos').attr('action','gastos.php?exportar_excel=1').submit();";
if (UtilesApp::GetConf($sesion, 'ExcelGastosSeparado')) {
		$pagina_excel = "jQuery('#form_gastos').attr('action','gastos_xls_separado.php').submit();";
}
if (UtilesApp::GetConf($sesion, 'ExcelGastosDesglosado')) {
		$pagina_excel = "jQuery('#form_gastos').attr('action','gastos_xls_por_encargado.php').submit();  ";
}
?>
			//if(from == 'buscar')                   jQuery('#form_gastos').attr('action','gastos.php?buscar=1').submit();
			if (from == 'excel') {
				jQuery('#boton_excel').attr('disabled','disabled');
				jQuery.post('ajax/estimar_datos.php',jQuery('#form_gastos').serialize(),function(data) {
				if (parseInt(data)>20000) {
					var formated=data/1000;
					jQuery('#dialog-confirm').attr('title','Advertencia').append('<p style="text-align:center;padding:10px;">Su consulta retorna '+data+' datos, por lo que el sistema s&oacute;lo puede exportar a un excel simplificado ycon funcionalidades limitadas.<br /><br /> Le advertimos que la descarga puede demorar varios minutos y pesar varios MB</p>');
					jQuery( "#dialog:ui-dialog" ).dialog( "destroy" );
					jQuery( "#dialog-confirm" ).dialog({
						resizable: false,
						autoOpen:true,
						height:200,
						width:450,
						modal: true,
						close: function(ev,ui) {
							jQuery(this).html('');
						},
						buttons: {
							"<?php echo __('Entiendo y acepto') ?>": function() {
								jQuery('#boton_excel').removeAttr('disabled');
								jQuery('#form_gastos').attr('action','ajax/csv_gastos.php').submit(); //planillon_gastos
								jQuery( this ).dialog( "close" );
								return true;
							},
							"<?php echo __('Cancelar') ?>": function() {
								jQuery('#boton_excel').removeAttr('disabled');
								jQuery( this ).dialog( "close" );
								return false;
							}
						}
					});
				} else {
					jQuery('#boton_excel').removeAttr('disabled');
<?php echo $pagina_excel ?>
					return true;
				}
			});
			return true;

		} else if (from == 'excel_resumen') {
			jQuery('#form_gastos').attr('action','gastos_xls_resumen.php').submit();
			return true;
		} else if (from =='datatables' || from == 'buscar') {
			contratos= {};
<?php if ($nuevo_modulo_gastos) { ?>
			var id_contrato = jQuery('#id_contrato').val();
			var params = jQuery('#form_gastos').serialize();
			var ajax_url = './planillas/planilla_saldo.php?opcion=json&tipo_liquidacion=2&id_contrato=' + id_contrato + '&' + params;
			var html_url = './planillas/planilla_saldo.php?popup=1&opcion=buscar&tipo_liquidacion=2&mostrar_detalle=1&id_contrato=' + id_contrato + '&' + params;
			jQuery('#totalcta').text('');
			jQuery.getJSON(ajax_url,function(data) {
				var onclick_html = "nuevaVentana('',1000,700,'" + html_url + "', '');";
				var restul_html = '<b>Balance cuenta gastos: <input type="hidden" id="codcliente" name="codcliente" value="0"/><a href="#" onclick="' + onclick_html +  '">' + data.resultado + '</a></b>';
				jQuery('#totalcta').html(restul_html);
			});
<?php } else { ?>
			jQuery('#totalcta').load('ajax/ajax_gastos.php?totalctacorriente=1&'+jQuery('#form_gastos').serialize());
<?php } ?>

			tablagastos = jQuery('#tablon').dataTable({
				"fnPreDrawCallback": function(oSettings) {
					jQuery('#tablon').fadeTo('fast',0.1);
				},
				"bDestroy": true,
				"bServerSide": true,
				"oLanguage": {
					"sProcessing": "Procesando...",
					"sLengthMenu": "Mostrar _MENU_ registros",
					"sZeroRecords": "No se encontraron resultados",
					"sInfo": "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
					"sInfoEmpty": "Mostrando desde 0 hasta 0 de 0 registros",
					"sInfoFiltered": "(filtrado de _MAX_ registros en total)",
					"sInfoPostFix": "",
					"sSearch": "Filtrar:",
					"sUrl": "",
					"oPaginate": {
            "sPrevious": "Anterior",
            "sNext": "Siguiente"
          }
				},
				"bFilter": false,
				"bProcessing": true,
				"sAjaxSource": "ajax/ajax_gastos.php?where=1&"+jQuery('#form_gastos').serialize(),
				"bJQueryUI": true,
				"bDeferRender": true,
				"sServerParams": jQuery('#form_gastos').serialize(),
				"fnServerData": function ( sSource, aoData, fnCallback ) {
					jQuery.ajax({
						"dataType": 'json',
						"type": "POST",
						"url": sSource,
						"data": aoData,
						"success": fnCallback,
						"complete" :function() {
							jQuery('#tablon').fadeTo(0, 1);
						}
					});
				},
				"aoColumnDefs": [
					{  "sClass": "alignleft",    	"aTargets": [ 1,2,3,4 ]   },
					{  "sClass": "marginleft",    	"aTargets": [ 2 ]   },
					{  "sClass": "tablagastos",    	"aTargets": [ 0,1,2,3,4,5,6,7,8,9,10  ]   },
					{  "sWidth": "60px",    		"aTargets": [ 0,1,5,6,11,12] },
					{  "bSortable":false,    		"aTargets": [ 2,3,4,11,12] },
					{  "bVisible": false, 			"aTargets": [ 5,10,12,14] },
<?php

if (!UtilesApp::GetConf($sesion, 'NumeroGasto')) {
	echo ' { "bVisible": false, "aTargets": [ 0 ] },';
}

if ( !UtilesApp::GetConf($sesion,'NumeroOT') ) {
	echo ' { "bVisible": false, "aTargets": [ 2 ] },';
}
//if ( !UtilesApp::GetConf($sesion,'FacturaAsociada') ) echo ' { "bVisible": false, "aTargets": [ 14 ] },';

if (!UtilesApp::GetConf($sesion, 'UsarImpuestoPorGastos')) {
	echo ' { "bVisible": false, "aTargets": [ 7 ] },';
}
if (!UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) {
	echo ' { "bVisible": false, "aTargets": [10 ] },';
}
?>
		{  "fnRender": function ( o, val ) {
			return o.aData[2];
		}, "bUseRendered": false, "aTargets": [2]},

		{"fnRender": function ( o, val ) {
			var idcobro=o.aData[10];
			var respuesta='';
			if (idcobro>0) {
				respuesta+="<a title=\"Ver Cobro asociado\" onclick=\"nuevaVentana('Editar_Contrato',1024,700,'cobros6.php?id_cobro="+idcobro+"&amp;popup=1&amp;contitulo=true');\" href=\"javascript:void(0)\">"+idcobro+"</a><br/>";
			}
			return respuesta+'<small>'+o.aData[9]+'</small>';
		}, "aTargets": [9]},

		{  "fnRender": function ( o, val ) {


			var respuesta='';

			/*mejorar*/
			var estado = o.aData[9].replace('</small>','').replace('<small>','');
			if(estado=='SIN COBRO' || estado=='CREADO' || estado=='EN REVISION') {
				respuesta+="<a href=\"#\" style=\"float:left;display:inline;\" onclick=\"nuevaVentana('Editar_Gasto',1000,700,'agregar_gasto.php?id_gasto="+o.aData[0]+"&popup=1&contitulo=true&id_foco=7', '');\"><img border='0' title='Editar' src='https://static.thetimebilling.com/images/editar_on.gif'></a><a style='float:left;display:inline;' onclick='EliminaGasto("+o.aData[0]+")' href='javascript:void(0)' target='_parent'><img border='0' title='Eliminar' src='https://static.thetimebilling.com/images/cruz_roja_nuevo.gif'></a>";
				respuesta+="<input type='checkbox' class='eligegasto' id='check_"+o.aData[0]+"'/>";
			} else {
				respuesta+="<a href=\"#\"  style=\"float:left;display:inline;\" onclick=\"alert('<?php echo __('No se puede modificar este gasto') . ': ' . __('El Cobro') . __(' que lo incluye ya ha sido Emitido al Cliente.'); ?>');\"><img border='0' title='Editar' src='https://static.thetimebilling.com/images/editar_off.gif'></a>";
			}

			return respuesta;
		}, "bUseRendered": false, "aTargets": [13]},

		{"fnRender": function (o,val) {
			if(o.aData[13]) {
				return o.aData[13]+'<br/><small>'+o.aData[13]+'</small>';
			}
		}, "aTargets": [6]},

		{"fnRender": function (o,val) {
			var tipo=(o.aData[15]!=' - ')? o.aData[15]+' ':'';
			return o.aData[4]+'<div class="tipodescripcion">('+tipo+o.aData[5]+')</div>';
		}, "aTargets": [4]},

		{"fnRender": function (o,val) {
			var activo=(o.aData[12] == 'SI') ? 'activo' :'inactivo';
			if (typeof(contratos) != "undefined") {

				contratos['contrato_' + o.aData[0]] = o.aData[12];
			}
			 var datacliente=o.aData[3].split('|');
			return '<a href="agregar_cliente.php?codigo_cliente='+datacliente[0]+'">'+datacliente[0]+'</a> '+datacliente[1]+'<div class="tipodescripcion">('+activo+')</div>';
		}, "bUseRendered": false , "aTargets": [3] }
		],
		"aaSorting": [[0,'desc']],
		"iDisplayLength": 25,
		"aLengthMenu": [[25,50, 150, 300,500, -1], [25,50, 150, 300,500, "Todo"]],
		"sPaginationType": "full_numbers",
		"sDom":  'T<"top"ip>rt<"bottom">',
		"oTableTools": {            "sSwfPath": "../js/copy_cvs_xls.swf",	"aButtons": [
<?php ($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_js_gastos') : false; ?>  {
				"sExtends":    "copy",
				"sAction":     "flash_copy",
				"sButtonText": "Copiar esta consulta",
				"fnClick": function ( nButton, oConfig, oFlash ) {
					var uri='<?php echo Conf:: Server() . $_SERVER['REQUEST_URI']; ?>';
					oFlash.setText( uri+'?buscar=1&'+jQuery('#form_gastos').serialize() );
				},
				"fnComplete": function ( nButton, oConfig, oFlash, sFlash ) {
					alert( 'Se ha copiado la consulta actual al portapapeles' );
				}
			}, {
				"sExtends":    "text",
				"sButtonText": "Editar Seleccionados",
				"fnClick": function ( nButton, oConfig, oFlash ) {
					top.window.jQuery('#dialogomodal .divloading').hide();
					if (jQuery('#selectodos').is(':checked')) {
						var url='ajax/ajax_gastos.php?opclistado=listado&selectodos=1&'+jQuery('#form_gastos').serialize();
					} else {
						var arrayseleccionados=new Array();
						jQuery('.eligegasto:checked').each(function() {
							var laid=jQuery(this).attr('id').replace('check_','');
							arrayseleccionados.push(parseInt(laid));
						});
						if (arrayseleccionados.length==0) return false;
							jQuery('#serializacion').val(arrayseleccionados);
							var url='ajax/ajax_gastos.php?opclistado=listado&movimientos='+jQuery('#serializacion').val().replace(',',';');
						}

						top.window.jQuery('#dialogomodal').dialog('open').dialog('open').dialog('option','title',' Editar Gastos Masivamente ').dialog( "option",
							"buttons", {
								"Modificar": function() {
									jQuery.post('ajax/ajax_gastos.php?opc=actualizagastos',jQuery('#form_edita_gastos_masivos').serialize(),function(data) {
										if (window.console) {
											console.log(data);
										}
									},'jsonp');

									jQuery('#codigo_cliente,#campo_codigo_asunto,#campo_codigo_asunto_secundario, #codigo_cliente_secundario, #glosa_cliente').removeAttr('readonly');
									jQuery('#selectclienteasunto').insertBefore('#leyendaasunto');

									jQuery(this).dialog("close");
								},
								"Cancelar": function() {
									jQuery('#codigo_cliente,#campo_codigo_asunto,#campo_codigo_asunto_secundario, #codigo_cliente_secundario, #glosa_cliente').removeAttr('readonly');
									jQuery('#selectclienteasunto').insertBefore('#leyendaasunto');
									jQuery(this).dialog("close");
								}
							});

							top.window.jQuery('#dialogomodal').load(url, function() {
								if (jQuery('#codcliente').val()==1) {
									jQuery('#codigo_cliente,#campo_codigo_asunto,#campo_codigo_asunto_secundario, #codigo_cliente_secundario, #glosa_cliente').attr('readonly','readonly');
									jQuery('#overlayeditargastos').prepend(jQuery('#selectclienteasunto'));
								}
							});
						}
					} ]
				}
			}).show();

			jQuery("#boton_buscar").removeAttr('disabled');
			return true;
		} else {
			return false;
		}
	});

<?php
if ($opc == 'buscar' || isset($_GET['buscar'])) {
	echo "jQuery('#boton_buscar').click();";
}
?>
});
function Refrescarse() {
	if (window.tablagastos != null) {
		if (typeof(window.tablagastos.fnDraw)=='function')  {
			window.tablagastos.fnDraw();
		}
	}
}
function Refrescar() {
	if (window.tablagastos != null) {
		if (typeof(window.tablagastos.fnDraw)=='function')  {
			window.tablagastos.fnDraw();
		}
	}
}
</script>


			<input type="hidden" name="serializacion" id="serializacion" size="70"/>
		<td>
			<input type="hidden" name="serializacion" id="serializacion" size="70"/>
			<form method='post' name="form_gastos" action='' id="form_gastos">

<?php if (isset($_GET['opc']) && $_GET['opc'] == 'buscar' && $where != '') echo '<input type="hidden" name="where" id="where" value="' . base64_encode($where) . '"/>'; ?>
				<input type='hidden' name='opc' id='opc' value=buscar>
				<input type='hidden' name='motivo' id='motivo' value='gastos'/>
				<fieldset class="tb_base" style="width: 90%;border: 1px solid #BDBDBD;margin:auto;">
					<legend><?php echo __('Filtros') ?></legend>

					<table style="border: 0px solid black" width='750px'>
						<tr>
							<td align=right><?php echo __('Cobrado') ?></td>
							<td align='left'>
<?php echo Html::SelectQuery($sesion, "SELECT codigo_si_no, codigo_si_no FROM prm_si_no", "cobrado", isset($cobrado) ? $cobrado : 'NO', '', 'Todos', '60') ?>
							</td>
							<td align="left" nowrap>
								<?php echo __('id_cobro') ?>&nbsp;
								<input onkeydown="if(event.keyCode==13)BuscarGastos(this.form, 'buscar')" type="text" size="6" name="id_cobro" id="id_cobro" value="<?php echo $id_cobro ?>">
							</td>
						</tr>
						<tbody id="selectclienteasunto">
							<tr>
								<td align=right width='20%'><?php echo __('Nombre Cliente') ?></td>
								<td nowrap colspan=3 align=left>
<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
								</td>
							</tr>
							<tr>
								<td align=right><?php echo __('Asunto') ?></td>
								<td nowrap colspan=3 align=left>
<?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 320, $oncambio = "CargarSelectCliente(this.value);CargarContrato(this.value)"); ?>
									<input type="hidden" name='id_contrato' id='id_contrato' value='<?php $id_contrato ?>' />
								</td>
							</tr>
						</tbody>

						<tbody id="leyendaasunto">
							<tr>
								<td nowrap colspan=4 align=center style='font-size:9px;'>
<?php echo __('Si Ud. selecciona el') . ' ' . __('asunto') . ' ' . __('mostrará los gastos de todos los') . ' ' . __('asuntos') . ' ' . __('que se cobrarán en la misma carta.') ?>
								</td>
							</tr>
						</tbody>

						<tr>
							<td align=right><?php echo __('Fecha Desde') ?></td>
							<td nowrap align=left>
								<input class="fechadiff" onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
							</td>
							<td nowrap align=left colspan=2>
								&nbsp;&nbsp; <?php echo __('Fecha Hasta') ?>
								<input  class="fechadiff" onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
								</td>
							</tr>
							<tr>
								<td align=right><?php echo __('Encargado comercial') ?>&nbsp;</td>
								<td colspan=2 align=left><?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre", "id_usuario_responsable", $id_usuario_responsable, '', __('Cualquiera'), '200') ?>
							</tr>
							<tr>
								<td align=right><?php echo __('Ordenado por') ?></td>
									<td align=left colspan=3>
<?php echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(' ', apellido1,apellido2,',',nombre) FROM usuario ORDER BY apellido1", "id_usuario_orden", $id_usuario_orden, "", __('Ninguno'), '200'); ?>
									</td>
								</tr>
<?php if (UtilesApp::GetConf($sesion, 'TipoGasto')) { ?>
								<tr>
									<td align=right><?php echo __('Tipo de Gasto') ?></td>
									<td align=left colspan=3>
	<?php echo Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo ORDER BY glosa", "id_tipo", $id_tipo, "", __('Cualquiera'), '200'); ?>
								</td>
							</tr>
<?php } ?>
							<tr>
								<td align=right><?php echo __('Clientes activos') ?></td>
								<td colspan="2" align="left">
									<select name='clientes_activos' id='clientes_activos' style='width: 140px;'>
										<option value=''  selected="selected"> Todos </option>
										<option value='activos'> S&oacute;lo activos </option>
										<option value='inactivos'> S&oacute;lo inactivos </option>
									</select>
								</td>
								<td></td>
							</tr>
<?php if (!$nuevo_modulo_gastos) { ?>
							<tr>
								<td align="right"> <?php echo __('Gastos'); ?>  y  <?php echo __('Provisiones'); ?>                        </td>
								<td colspan="2" align="left">
									<select name="egresooingreso" id="egresooingreso" style="width: 140px;">
										<option value=""  selected="selected"> <?php echo __('Gastos'); ?>  y  <?php echo __('Provisiones'); ?>  </option>
										<option value="soloingreso"> Sólo <?php echo __('provisiones'); ?></option>
										<option value="sologastos"> Sólo <?php echo __('gastos'); ?> </option>
									</select>
								</td>
								<td></td>
							</tr>
<?php } else {
	echo '<input name="egresooingreso" id="egresooingreso" type="hidden" value="" />';
  	$Slim=Slim::getInstance('default')  ? $Slim->applyHook('hook_formulario_gastos') : false;
	}
 ?>
							<tr>
								<td align=right><?php echo __('Moneda') ?></td>
								<td colspan="2" align="left">
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_gasto", $moneda_gasto, "", __('Todas'), ''); ?>
								</td>
								<td></td>
						</tr>
<?php if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) { ?>
						<tr>
							<td align='right'><?php echo __('Cobrable') ?></td>
							<td  align="left">
								<select name="cobrable" id="cobrable" style="width: 140px;">
									<option value=""  >Todos </option>
									<option value="1" selected="selected"> Sólo <?php echo __('Cobrable'); ?></option>
									<option value="0"> Sólo No <?php echo __('Cobrable'); ?> </option>
								</select>
							</td>
							<td></td>
						</tr>
<?php } ?>

					</table>
							<div  style="padding:10px;text-align:right;">
								<a name="boton_buscar" id='boton_buscar' icon="find" class="btn botonizame buscargastos" rel="buscar" ><?php echo __('Buscar') ?></a>
								<a name="boton_xls" id="boton_excel"  icon="xls" class="btn botonizame buscargastos"  rel="excel" ><?php echo __('Descargar Excel') ?></a>
								<a name="boton_xls_resumen"  icon="xls" rel="excel_resumen" class="btn botonizame buscargastos" ><?php echo __('Descargar Resumen Excel') ?></a>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php if (!$nuevo_modulo_gastos) { ?>
								&nbsp;<a href='javascript:void(0)' class="btn botonizame" icon="agregar" onclick="AgregarNuevo('provision')" title="Agregar provisi&oacute;n"><?php echo __('Agregar provisión') ?></a>
<?php } ?>
								&nbsp;<a href='javascript:void(0)' class="btn botonizame"  icon="agregar"  onclick="AgregarNuevo('gasto')" title="Agregar Gasto"><?php echo __('Agregar') ?> <?php echo __('gasto') ?></a>
							</div>

				</fieldset>
				<br>
<?php if ($buscar == 1 && ( $codigo_cliente != '' || $codigo_cliente_secundario != '')) { ?>
				<table id="gran_tabla_gastos" width="100%">
					<tr>
						<td align="right">
							<input type="button" value="Generar borrador" class="btn" name="boton" onclick="Preparar_Cobro( jQuery('#form_gastos').get(0));">
						</td>
					</tr>
				</table>
<?php } ?>
			</form>

	</tr>
</table>

<div id="totalcta" style='font-size:11px;z-index:999;'><b>
<?php
if ($opc == 'buscar') {
		if ($total_cta) {
			echo __('Balance cuenta gastos') . ': ' . UtilesApp::GetSimboloMonedaBase($sesion) . " " . $total_cta;
		}
}
?>
</b></div>
<table cellpadding="0" cellspacing="0" border="0" class="display" id="tablon" style="width:920px;display:none;">
	<thead>
		<tr class="encabezadolight">
			<th>Correlativo</th>
			<th>Fecha</th>
			<th>Nº OT</th>
			<th width="190"><?php echo __('Cliente'); ?></th>
			<th width="260"><?php echo __('Asunto'); ?><br><small>(descripcion)</small></th>
			<th>Descripción</th>
			<th>Egreso<br/><small>(<?php echo __('Cobrable'); ?>)</small></th>
			<th>Ingreso</th>
			<th><?php echo __('Impuesto'); ?></th>
			<th width="70"><?php echo __('Cobro'); ?><br/><small>(estado)</small></th>
			<th>Cobro</th>
			<th width="60"><?php echo __('Cobrable'); ?></th>
			<th><?php echo __('Contrato'); ?><br>Activo</th>
			<th width="60">Opción <input type="checkbox" id="selectodos"/></th>
			<th>contrato </th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
<?php
$pagina->PrintBottom();
