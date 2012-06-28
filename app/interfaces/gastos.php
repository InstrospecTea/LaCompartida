<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/InputId.php';

require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/Funciones.php';
require_once Conf::ServerDir() . '/classes/Gasto.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Cliente.php';
require_once Conf::ServerDir() . '/classes/Asunto.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Autocompletador.php';
#require_once Conf::ServerDir().'/classes/GastoGeneral.php';

$sesion = new Sesion(array('OFI'));
$pagina = new Pagina($sesion);

$gasto = new Gasto($sesion);
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
        if ($fila->fields[estado] == 'CREADO' || $fila->fields[estado] == 'EN REVISION' || empty($fila->fields[estado]))
            $editar = true;

        $id_gasto = $fila->fields['id_movimiento'];
        $prov = $fila->fields[egreso] != '' ? 'false' : 'true';
        if ($editar) {
            $html_opcion .= "<a href='javascript:void(0)' onclick=\"nuovaFinestra('Editar_Gasto',730,580,'agregar_gasto.php?id_gasto=$id_gasto&popup=1&prov=$prov');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border=0 title=Editar></a>&nbsp;";
            if (UtilesApp::GetConf($sesion, 'UsaDisenoNuevo') ) {
                $html_opcion .= "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaGasto($id_gasto)\" ><img src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' border=0 title=Eliminar></a>";
			} else {
                $html_opcion .= "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaGasto($id_gasto)\" ><img src='" . Conf::ImgDir() . "/cruz_roja.gif' border=0 title=Eliminar></a>";
			}
        }
        else
            $html_opcion .= "<a href='javascript:void(0)' onclick=\"alert('" . __('No se puede modificar este gasto.\n') . __('El Cobro') . __(' que lo incluye ya ha sido Emitido al Cliente.') . "');\" ><img src='" . Conf::ImgDir() . "/editar_off.gif' border=0 title=\"" . __('Cobro ya Emitido al Cliente') . "\"></a>&nbsp;";
        return $html_opcion;
    }

    /* function Nombre(& $fila)
      {
      return $fila->fields[apellido1].", ".$fila->fields[nombre];
      } */

    function Monto(& $fila) {
        global $sesion;
        $idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
        if ($fila->fields['codigo_idioma'] != '')
            $idioma->Load($fila->fields['codigo_idioma']);
        else
            $idioma->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));
        return $fila->fields['egreso'] > 0 ? $fila->fields[simbolo] . " " . number_format($fila->fields['monto_cobrable'], $fila->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '';
    }

    function Ingreso(& $fila) {
        global $sesion;
        $idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
        if ($fila->fields['codigo_idioma'] != '')
            $idioma->Load($fila->fields['codigo_idioma']);
        else
            $idioma->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));
        return $fila->fields['ingreso'] > 0 ? $fila->fields['simbolo'] . " " . number_format($fila->fields['monto_cobrable'], $fila->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '';
    }

if ($id_gasto != "") {
    $gasto->Load($id_gasto);
    if ($accion == "eliminar") {
        if ($gasto->Eliminar())
            $pagina->AddInfo(__('El gasto ha sido eliminado satisfactoriamente'));
    }
}


$pagina->titulo = __('Revisar Gastos');
$pagina->PrintTop();

if ($opc == 'buscar') {
    if ($orden == "")
        $orden = "fecha DESC";

    if ($where == '') {
        $where = 1;
        if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
            if ($codigo_cliente_secundario) {
                $where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario'";
                $cliente = new Cliente($sesion);
                $cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
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
                }
            }
        } else {
            if ($codigo_cliente) {
                $where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
                $cliente = new Cliente($sesion);
                $cliente->LoadByCodigo($codigo_cliente);
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
                }
            }
        }
        if ($fecha1 != '')
            $fecha_ini = Utiles::fecha2sql($fecha1); else
            $fecha_ini = '';
        if ($fecha2 != '')
            $fecha_fin = Utiles::fecha2sql($fecha2); else
            $fecha_fin = '';

        if ($cobrado == 'NO')
            $where .= " AND cta_corriente.id_cobro is null ";
        if ($cobrado == 'SI')
            $where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado='INCOBRABLE') ";
        if ($codigo_asunto && $lista_asuntos)
            $where .= " AND cta_corriente.codigo_asunto IN ('$lista_asuntos')";
        if ($codigo_asunto_secundario && $lista_asuntos_secundario)
            $where .= " AND asunto.codigo_asunto_secundario IN ('$lista_asuntos_secundario')";
        if ($id_usuario_orden)
            $where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
        if ($id_usuario_responsable)
            $where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable' ";
        if ($id_tipo)
            $where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
        if ($clientes_activos == 'activos')
            $where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
        if ($clientes_activos == 'inactivos')
            $where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
        if ($fecha1 && $fecha2)
            $where .= " AND cta_corriente.fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . "' AND '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
        else if ($fecha1)
            $where .= " AND cta_corriente.fecha >= '" . Utiles::fecha2sql($fecha1) . "' ";
        else if ($fecha2)
            $where .= " AND cta_corriente.fecha <= '" . Utiles::fecha2sql($fecha2) . "' ";
        else if (!empty($id_cobro))
            $where .= " AND cta_corriente.id_cobro='$id_cobro' ";

        // Filtrar por moneda del gasto
        if ($moneda_gasto != '')
            $where .= " AND cta_corriente.id_moneda=$moneda_gasto ";
    }
    else
        $where = base64_decode($where);

    $idioma_default = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
    $idioma_default->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));

    $total_cta = number_format(UtilesApp::TotalCuentaCorriente($sesion, $where), 0, $idioma_default->fields['separador_decimales'], $idioma_default->fields['separador_miles']);
  

} elseif ($opc == 'xls') {
    require_once('gastos_xls.php');
    exit;
} elseif ($opc == 'xls_resumen') {
    require_once('gastos_xls_resumen.php');
    exit;
}
if ($preparar_cobro == 1) {
    $where = 1;
    if ($id_usuario)
        $where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
    if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() )) {
        if ($codigo_cliente_secundario)
            $where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario' ";
    }
    else {
        if ($codigo_cliente)
            $where .= " AND contrato.codigo_cliente = '$codigo_cliente' ";
    }
    if (UtilesApp::GetConf($sesion,'CodigoSecundario') ) {
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
            if ($lista_asuntos_secundario)
                $where .= " AND asunto.codigo_asunto IN ('$lista_asuntos_secundario')";
        }
    }
    else {
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
            if ($lista_asuntos)
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
    $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
    #cobros solo gastos
    while ($contra = mysql_fetch_array($resp)) {
        $cobro = new Cobro($sesion);
        if (!$id_proceso_nuevo) {
            $id_proceso_nuevo = $cobro->GeneraProceso();
        }
        //Por conf se permite el uso de la fecha desde
        $fecha_ini_cobro = "";
        if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarFechaDesdeCobranza') ) || ( method_exists('Conf', 'UsaFechaDesdeCobranza') && Conf::UsaFechaDesdeCobranza() ) ) && $fecha_ini)
            $fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);

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
  
    function Preparar_Cobro(form)
    {
        form.action = 'gastos.php?preparar_cobro=1';
        form.submit();
    }
	
    function EliminaGasto(id)
    {
        var form = document.getElementById('form_gastos'); <?php if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))) { ?>
                    var acc = 'gastos.php?id_gasto='+id+'&accion=eliminar&codigo_cliente='+$('codigo_cliente_secundario').value+'&codigo_asunto='+$('codigo_asunto_secundario').value+'&fecha1='+$('fecha1').value+'&fecha2='+$('fecha2').value<?php echo  ( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'TipoGasto') ) || ( method_exists('Conf', 'TipoGasto') && Conf::TipoGasto() ) ) ? "+'&id_tipo='+$('id_tipo').value" : "" ?>+'&opc=buscar';
<?php } else {
    ?>
                    var acc = 'gastos.php?id_gasto='+id+'&accion=eliminar&codigo_cliente='+$('codigo_cliente').value+'&codigo_asunto='+$('codigo_asunto').value+'&fecha1='+$('fecha1').value+'&fecha2='+$('fecha2').value<?php echo  ( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'TipoGasto') ) || ( method_exists('Conf', 'TipoGasto') && Conf::TipoGasto() ) ) ? "+'&id_tipo='+$('id_tipo').value" : "" ?>+'&opc=buscar';
<?php } ?>
                if(parseInt(id) > 0 && confirm('¿Desea eliminar el gasto seleccionado?') == true)
                    self.location.href = acc;
            }

            function AgregarNuevo(tipo)
            {
<?php if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))) { ?>
                    var codigo_cliente = $('codigo_cliente_secundario').value;
                    var codigo_asunto = $('codigo_asunto_secundario').value;
                    var url_extension = "&codigo_cliente_secundario="+codigo_cliente+"&codigo_asunto_secundario="+codigo_asunto;
<?php } else {
    ?>
                    var codigo_cliente = $('codigo_cliente').value;
                    var codigo_asunto = $('codigo_asunto').value;
                    var url_extension = "&codigo_cliente="+codigo_cliente+"&codigo_asunto="+codigo_asunto;
<?php } ?>

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
jQuery('document').ready(function() {
	
jQuery('#selectodos').live('click',function() {
	if(jQuery(this).is(':checked')) {
		jQuery('.eligegasto').attr('checked','checked');
	} else {
		jQuery('.eligegasto').removeAttr('checked');
	}
	
});

   jQuery('.buscargastos').click(function()     {
	   
   var form=jQuery('#form_gastos');
   var from=jQuery(this).attr('rel');
   jQuery(this).attr('disabled','disabled');
<?php $pagina_excel = " jQuery('#form_gastos').attr('action','gastos_xls.php').submit();";
if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'ExcelGastosSeparado') ) || ( method_exists('Conf', 'ExcelGastosSeparado') && Conf::ExcelGastosSeparado() )) {
    $pagina_excel = "jQuery('#form_gastos').attr('action','gastos_xls_separado.php').submit();";
}
if (UtilesApp::GetConf($sesion, 'ExcelGastosDesglosado')) {
    $pagina_excel = "jQuery('#form_gastos').attr('action','gastos_xls_por_encargado.php').submit();  ";
}
?>
                //if(from == 'buscar')                   jQuery('#form_gastos').attr('action','gastos.php?buscar=1').submit();
                 if(from == 'excel') {
					 jQuery('#boton_excel').attr('disabled','disabled');
					 jQuery.post('ajax/estimar_datos.php',jQuery('#form_gastos').serialize(),function(data) {
 				if(parseInt(data)>20000)	{
					var formated=data/1000;
				jQuery('#dialog-confirm').attr('title','Advertencia').append('<p style="text-align:center;padding:10px;">Su consulta retorna '+data+' datos, por lo que el sistema s&oacute;lo puede exportar a un excel simplificado ycon funcionalidades limitadas.<br /><br /> Le advertimos que la descarga puede demorar varios minutos y pesar varios MB</p>');
				jQuery( "#dialog:ui-dialog" ).dialog( "destroy" );
				jQuery( "#dialog-confirm" ).dialog({
						resizable: false,						autoOpen:true,						height:200,						width:450,
						modal: true,
						close:function(ev,ui) {
							jQuery(this).html('');
						},
						buttons: {
										"<?php echo __('Entiendo y acepto')?>": function() {
											 jQuery('#boton_excel').removeAttr('disabled');
											 jQuery('#form_gastos').attr('action','ajax/csv_gastos.php').submit();
												jQuery( this ).dialog( "close" );
												return true;
												},
										"<?php echo __('Cancelar')?>": function() {
											jQuery('#boton_excel').removeAttr('disabled');
											jQuery( this ).dialog( "close" );
											return false;
										}
									}
						});
						} else {
						jQuery('#boton_excel').removeAttr('disabled');
						<?php echo  $pagina_excel ?>
						return true;
						}		
                  });
				return true;
                        } else if(from == 'excel_resumen') {
                            jQuery('#form_gastos').attr('action','ajax/csv_gastos.php').submit();
							
							return true;
                       }   else if(from =='datatables' || from == 'buscar') {
					
					jQuery('#totalcta').load('ajax/gastos_ajax.php?totalctacorriente=1&'+jQuery('#form_gastos').serialize());
					var tablagastos=   jQuery('#tablon').hide().dataTable({
                                 "fnPreDrawCallback": function( oSettings ) {
									jQuery('#tablon').fadeTo('fast',0.1);
									
								},
								 
								"bDestroy":true,	"bServerSide": true,
                                "oLanguage": {    "sProcessing":   "Procesando..." ,   "sLengthMenu":   "Mostrar _MENU_ registros","sZeroRecords":  "No se encontraron resultados",  "sInfo":         "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
                                    "sInfoEmpty":    "Mostrando desde 0 hasta 0 de 0 registros",  "sInfoFiltered": "(filtrado de _MAX_ registros en total)",   "sInfoPostFix":  "",  "sSearch":       "Filtrar:",
                                    "sUrl":          "", 	"oPaginate": {            "sPrevious": "Anterior",   "sNext":     "Siguiente"}
                                },
                                "bFilter": false,    "bProcessing": true,
                                "sAjaxSource": "ajax/gastos_ajax.php?where=1&"+jQuery('#form_gastos').serialize(),
                                "bJQueryUI": true,
                                "bDeferRender": true,
								"sServerParams": jQuery('#form_gastos').serialize(),
								"fnServerData": function ( sSource, aoData, fnCallback ) {
									jQuery.ajax( {	"dataType": 'json', "type": "POST", "url": sSource, "data": aoData, 
										"success": fnCallback,
										"complete" :function() {
											jQuery('#tablon').fadeTo(0, 1);
										}
									})
								},
							
                                "aoColumnDefs": [
                                    {  "sClass": "alignleft",    "aTargets": [ 1,2,3,4 ]   },
									 {  "sClass": "marginleft",    "aTargets": [ 2 ]   },
                                    {  "sClass": "tablagastos",    "aTargets": [ 0,1,2,3,4,5,6,7,8,9,10  ]   },
                                    
                                    {  "sWidth": "60px",    "aTargets": [0,5,6,11,12 ]   },
                                    {  "bSortable":false,    "aTargets": [2,3,4,11,12 ]   },
									   { "bVisible": false, "aTargets": [ 3,4,9,11 ] },
<?php
//if ( !UtilesApp::GetConf($sesion,'NumeroGasto') ) echo ' { "bVisible": false, "aTargets": [ 14 ] },';
//if ( !UtilesApp::GetConf($sesion,'NumeroOT') ) echo ' { "bVisible": false, "aTargets": [ 14 ] },';
//if ( !UtilesApp::GetConf($sesion,'FacturaAsociada') ) echo ' { "bVisible": false, "aTargets": [ 14 ] },';

if (!UtilesApp::GetConf($sesion, 'UsarImpuestoPorGastos'))     echo ' { "bVisible": false, "aTargets": [ 7 ] },';
if (!UtilesApp::GetConf($sesion, 'UsarGastosCobrable'))     echo ' { "bVisible": false, "aTargets": [ 11 ] },';
?>    
               
				  {  "fnRender": function ( o, val ) {
                            var respuesta='';
						if(o.aData[9]=='SIN COBRO' || o.aData[9]=='CREADO' || o.aData[9]=='EN REVISION') {
								respuesta+="<a href=\"#\" style=\"float:left;display:inline;\" onclick=\"nuevaVentana('Editar_Gasto',1000,700,'agregar_gasto.php?id_gasto="+o.aData[12]+"&popup=1&contitulo=true&id_foco=7', '');\"><img border='0' title='Editar' src='https://static.thetimebilling.com/images/editar_on.gif'></a><a style='float:left;display:inline;' onclick='EliminaGasto("+o.aData[12]+")' href='javascript:void(0)' target='_parent'><img border='0' title='Eliminar' src='https://static.thetimebilling.com/images/cruz_roja_nuevo.gif'></a>";
								respuesta+="<input type='checkbox' class='eligegasto' id='check_"+o.aData[12]+"'/>";
						} else {
								respuesta+="<a href=\"#\" style=\"float:left;display:inline;\" onclick=\"alert('<?php echo  __('No se puede modificar este gasto').': ' . __('El Cobro') . __(' que lo incluye ya ha sido Emitido al Cliente.') ; ?>');\"><img border='0' title='Editar' src='https://static.thetimebilling.com/images/editar_off.gif'></a>";	
							
						}
							return respuesta;
                        },    "aTargets": [ 12 ]   },
                    {  "fnRender": function ( o, val ) {
						
						var idcobro=o.aData[8];
							var respuesta='';
							if(idcobro>0) 	respuesta+="<a title=\"Ver Cobro asociado\" onclick=\"nuevaVentana('Editar_Contrato',1024,700,'cobros6.php?id_cobro="+idcobro+"&amp;popup=1&amp;contitulo=true');\" href=\"javascript:void(0)\">"+idcobro+"</a><br/>";
							 return respuesta+o.aData[9];
                        },    "aTargets": [ 8 ]   },
					{"fnRender": function (o,val) {
							var tipo=(o.aData[3]!=' - ')? o.aData[3]+' ':'';
							return o.aData[2]+'<div class="tipodescripcion">('+tipo+o.aData[4]+')</div>';
					}, "aTargets": [2] },
					{"fnRender": function (o,val) {
							var activo=(o.aData[11]='SI')? 'activo' :'inactivo';
							return o.aData[1]+'<div class="tipodescripcion">('+activo+')</div>';
					}, "aTargets": [1] }
                    
	         ],
			 "aaSorting": [[0,'desc']],
                "iDisplayLength": 25,
                "aLengthMenu": [[25,50, 150, 300,500, -1], [25,50, 150, 300,500, "Todo"]],
                "sPaginationType": "full_numbers",
                "sDom":  'T<"top"ip>rt<"bottom">',
                "oTableTools": {            "sSwfPath": "../js/copy_cvs_xls.swf",	"aButtons": [ "xls","copy", {
                    "sExtends":    "text",
                    "sButtonText": "Editar Seleccionados",
					 "fnClick": function ( nButton, oConfig, oFlash ) {
                      top.window.jQuery('#dialogomodal .divloading').hide();
					  
					 if(jQuery('#selectodos').is(':checked')) {
						 var url='ajax/gastos_ajax.php?opclistado=listado&selectodos=1&'+jQuery('#form_gastos').serialize();
					 } else {
						var arrayseleccionados=new Array();
					    jQuery('.eligegasto:checked').each(function() {
							var laid=jQuery(this).attr('id').replace('check_','');
							arrayseleccionados.push(parseInt(laid));
						});
						if(arrayseleccionados.length==0) return false;
						jQuery('#serializacion').val(arrayseleccionados);
						var url='ajax/gastos_ajax.php?opclistado=listado&movimientos='+jQuery('#serializacion').val().replace(',',';');
					 }
					 
					   top.window.jQuery('#dialogomodal').dialog('open').dialog('open').dialog('option','title',' Editar Gastos Masivamente ').dialog( "option", 
					   "buttons", { 
													"Modificar": function() { 
														jQuery.post('ajax/gastos_ajax.php?opc=actualizagastos',jQuery('#form_edita_gastos_masivos').serialize(),function(data) {
															if(window.console) console.log(data);
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
						if(jQuery('#codcliente').val()==1) {
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
	
	<?php if ($opc == 'buscar' || isset($_GET['buscar'])) echo "jQuery('#boton_buscar').click();"; ?>
});	
function Refrescarse() {
	if(window.tablagastos && tablagastos.fnReloadAjax) tablagastos.fnReloadAjax();
}
    function Refrescar()    {    }
</script>
<?php echo(Autocompletador::CSS()); ?>
<table  width="90%"><tr><td><input type="hidden" name="serializacion" id="serializacion" size="70"/>
            <form method='post' name="form_gastos" action='' id="form_gastos">
				
			<?php if(isset($_GET['opc']) && $_GET['opc']=='buscar' && $where!='') echo '<input type="hidden" name="where" id="where" value="'. base64_encode($where).'"/>'; ?>
                <input type='hidden' name='opc' id='opc' value=buscar>
                 <input type='hidden' name='motivo' id='motivo' value='gastos'/>
                <!-- Calendario DIV -->
                <div id="calendar-container" style="width:221px; position:absolute; display:none;">
                    <div class="floating" id="calendar"></div>
                </div>
                <!-- Fin calendario DIV -->

                <fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
                    <legend><?php echo  __('Filtros') ?></legend>
                    <table style="border: 0px solid black" width='720px'>
                        <tr>
                            <td align=right>
<?php echo  __('Cobrado') ?>
                            </td>
                            <td align='left'>
<?php echo  Html::SelectQuery($sesion, "SELECT codigo_si_no, codigo_si_no FROM prm_si_no", "cobrado", 'NO', '', 'Todos', '60') ?>
                            </td>
                            <td align="left" nowrap>
<?php echo  __('id_cobro') ?>&nbsp;
                                <input onkeydown="if(event.keyCode==13)BuscarGastos(this.form, 'buscar')" type="text" size="6" name="id_cobro" id="id_cobro" value="<?php echo  $id_cobro ?>">
                            </td>
                        </tr><tbody id="selectclienteasunto">
                        <tr >
                            <td align=right width='30%'>
<?php echo  __('Nombre Cliente') ?>
                            </td>
                            <td nowrap colspan=3 align=left>
<?php UtilesApp::CampoCliente($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>
                            </td>
                        </tr>
                        <tr>
                            <td align=right>
                                <?php echo  __('Asunto') ?>
                            </td>
                            <td nowrap colspan=3 align=left>
                                <?php   UtilesApp::CampoAsunto($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>
								
								
                            </td>
                        </tr></tbody>
                        <tbody id="leyendaasunto"><tr>
                            <td nowrap colspan=4 align=center style='font-size:9px;'>
                                <?php echo  __('Si Ud. selecciona el') . ' ' . __('asunto') . ' ' . __('mostrará los gastos de todos los') . ' ' . __('asuntos') . ' ' . __('que se cobrarán en la misma carta.') ?>
                            </td>
                        </tr></tbody>
                        <tr>
                            <td align=right>
                                <?php echo  __('Fecha Desde') ?>
                            </td>
                            <td nowrap align=left>
                                <input class="fechadiff" onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha1" value="<?php echo  $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
                            </td>
                            <td nowrap align=left colspan=2>
                               &nbsp;&nbsp; <?php echo  __('Fecha Hasta') ?>
                                <input  class="fechadiff" onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha2" value="<?php echo  $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
                            </td>
                        </tr>
                        <tr>
                            <td align=right><?php echo  __('Encargado comercial') ?>&nbsp;</td>
                            <td colspan=2 align=left><?php echo  Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre", "id_usuario_responsable", $id_usuario_responsable, '', __('Cualquiera'), '200') ?>
                        </tr>
                        <tr>
                            <td align=right>
                                <?php echo  __('Ordenado por') ?>
                            </td>
                            <td align=left colspan=3>
                                <?php echo  Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(' ', apellido1,apellido2,',',nombre) FROM usuario ORDER BY apellido1", "id_usuario_orden", $id_usuario_orden, "", __('Ninguno'), '200'); ?>
                            </td>
                        </tr>
<?php if (UtilesApp::GetConf($sesion,'TipoGasto')) {     ?>
                            <tr>
                                <td align=right>
    <?php echo  __('Tipo de Gasto') ?>
                                </td>
                                <td align=left colspan=3>
                                    <?php echo  Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo ORDER BY glosa", "id_tipo", $id_tipo, "", __('Cualquiera'), '200'); ?>
                                </td>
                            </tr>
    <?php }
?>
                        <tr>
                            <td align=right>
<?php echo  __('Clientes activos') ?>
                            </td>
                            <td colspan="2" align="left">
                                <select name='clientes_activos' id='clientes_activos' style='width: 120px;'>
                                    <option value=''> Todos </option>
                                    <option value='activos' selected="selected"> S&oacute;lo activos </option>
                                    <option value='inactivos'> S&oacute;lo inactivos </option>
                                </select>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td align=right>
                                <?php echo  __('Moneda') ?>
                            </td>
                            <td colspan="2" align="left">
                        <?php echo  Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_gasto", $moneda_gasto, "", __('Todas'), ''); ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan=2 align=left>
                               <!--<input name=boton_buscar id='boton_buscar' type="button" value="<?php echo  __('Buscar') ?>"   rel="buscar" class=" btn buscargastos">-->
								<input name="boton_buscar" id='boton_buscar' type="button" value="<?php echo  __('Buscar') ?>"  rel="buscar" class=" btn buscargastos" />
                                <input name="boton_xls" id="boton_excel" type="button" value="<?php echo  __('Descargar Excel') ?>"   rel="excel" class=" btn buscargastos">
                                <input name="boton_xls_resumen" type="button" value="<?php echo  __('Descargar Resumen Excel') ?>"   rel="excel_resumen" class=" btn buscargastos" />
                                
                                
                            </td>
                            <td width='40%' align=right>
                                <img src="<?php echo  Conf::ImgDir() ?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('provision')" title="Agregar provisi&oacute;n"><?php echo  __('Agregar provisión') ?></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                <img src="<?php echo  Conf::ImgDir() ?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('gasto')" title="Agregar Gasto"><?php echo  __('Agregar') ?> <?php echo  __('gasto') ?></a>
                            </td>
                        </tr>
                    </table>
                </fieldset>
                <br>
<?php if ($buscar == 1 && ( $codigo_cliente != '' || $codigo_cliente_secundario != '')) {
    ?>
                    <table id="gran_tabla_gastos" width="100%">
                        <tr>
                            <td align="right">
                                <input type="button" value="Generar borrador" class="btn" name="boton" onclick="Preparar_Cobro( this.form )">
                            </td>
                        </tr>
                    </table>
                                    <?php                                 }
                                ?>
            </form>
        </td></tr></table>
      
<div id="totalcta" style='font-size:11px'><b>
                <?php
				 if ($opc == 'buscar') {
                    if($total_cta) {
					 echo __('Balance cuenta gastos').': '. UtilesApp::GetSimboloMonedaBase($sesion) . " " . $total_cta ; 
					}
                }
				?>
		</b></div>
                <table cellpadding="0" cellspacing="0" border="0" class="display" id="tablon" style="width:920px;display:none;">
	<thead>
		<tr class="encabezadolight">
		<th >Fecha</th>
<th width="200"><?php echo __('Cliente') ;?></th>
<th width="250"><?php echo __('Asunto') ;?><br><small>(descripcion)</small></th>
<th>Tipo</th>
<th>Descripción</th>
<th>Egreso</th>
<th>Ingreso</th>
<th><?php echo __('Impuesto'); ?></th>
<th><?php echo __('Cobro'); ?><br/><small>(estado)</small></th>
<th>Estado<br>Cobro</th>
<th><?php echo  __('Cobrable') ;?></th>
<th><?php echo __('Contrato') ;?><br>Activo</th>
<th width="60">Opción <input type="checkbox" id="selectodos"/></th></tr>
	</thead>
	<tbody>
		
	</tbody></table>
               <?php
                if (UtilesApp::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador' ) {
                    echo(Autocompletador::Javascript($sesion));
                }
                echo(InputId::Javascript($sesion));
                $pagina->PrintBottom();
?>