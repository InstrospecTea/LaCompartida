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
	require_once Conf::ServerDir().'/classes/Factura.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	require_once Conf::ServerDir().'/classes/CtaCteFact.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);

	$factura = new Factura($sesion);

	if($id_factura != "")
	{
		$factura->Load($id_factura);
		if( empty($codigo_cliente) )
			$codigo_cliente=$factura->fields['codigo_cliente'];
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
	  	{
	  		$cliente_factura = new Cliente($sesion);
	  		$codigo_cliente_secundario = $cliente_factura->CodigoACodigoSecundario( $codigo_cliente );
	  	}
	}
	
	if($factura->loaded() && !$id_cobro)
	{
		$id_cobro = $factura->fields['id_cobro'];
	}
	
	/*Si se cambió de cliente, el cobro se reemplazó por 'nulo'*/
	/*if($id_cobro == 'nulo')
	{
		$id_cobro = null;
	}*/
	
	if($factura->loaded() && !$codigo_cliente)
		$codigo_cliente = $factura->fields['codigo_cliente'];
	
	if($factura->loaded())
	{
		$id_documento_legal = $factura->fields['id_documento_legal'];
	}
	$query = "SELECT id_documento_legal, glosa, codigo FROM prm_documento_legal WHERE id_documento_legal = '$id_documento_legal'";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($id_documento_legal, $tipo_documento_legal, $codigo_tipo_doc) = mysql_fetch_array($resp);
	
	if(!$tipo_documento_legal)
		$pagina->FatalError('Error al cargar el tipo de Documento Legal');

   if($opc == 'generar_factura')
   {
       // POR HACER
       // mejorar
       if($id_factura)
           UtilesApp::generarFacturaPDF($id_factura, $sesion);
       else
           echo "Error";
       exit;
   }

		if($opcion == "restaurar")
		{
			$factura->Edit('estado','ABIERTA');
			$factura->Edit('anulado',0);
			$factura->Edit("id_estado", "1");
			if($factura->Escribir())
			{
				$pagina->AddInfo(__('Documento Tributario').' '.__('restaurado con éxito'));
				$requiere_refrescar = "window.opener.Refrescar();";
				if( $id_cobro )
				{
					$cobror = new Cobro( $sesion ); #cobro restaurar
					$cobror->Load($id_cobro);
					if( $cobror->Loaded() )
					{
						$fsa = $cobror->CantidadFacturasSinAnular(); #fsa = facturas sin anular
						if ( $fsa == 1 )
						{
							$cobror->Edit('estado', 'FACTURADO');
						}
					}
					$cobror->Write();
				}
			}
		}
		if($opcion == "anular")
		{
			$factura->Edit('estado','ANULADA');
			$factura->Edit("id_estado", $id_estado ? $id_estado : "1");
			$factura->Edit('anulado',1);
			if($factura->Escribir())
			{
				$pagina->AddInfo(__('Documento Tributario').' '.__('anulado con éxito'));
				$requiere_refrescar = "window.opener.Refrescar();";
			}
		}
		if($opcion == "guardar")
		{
			if( empty($RUT_cliente) ) $pagina->AddError(__('Debe ingresar el').' '.__('RUT').' '.__('del cliente.'));
			if( empty($cliente) )			$pagina->AddError(__('Debe ingresar la razon social del cliente.'));
			
			$errores = $pagina->GetErrors();
			$guardar_datos = true;
			if (!empty($errores))
			{
				$guardar_datos = false;
			}
			
			if( $guardar_datos )
			{ 
				//chequear
				$mensaje_accion = 'guardar';
				$factura->Edit('subtotal',$monto_neto);
				$factura->Edit('porcentaje_impuesto',$porcentaje_impuesto);
				$factura->Edit('iva',$iva);
				$factura->Edit('total',''.($monto_neto+$iva));
				$factura->Edit("id_factura_padre", $id_factura_padre? $id_factura_padre : "NULL");
				$factura->Edit("fecha",Utiles::fecha2sql($fecha));
				$factura->Edit("cliente",$cliente ? $cliente : "NULL");
				$factura->Edit("RUT_cliente",$RUT_cliente ? $RUT_cliente : "NULL");
				$factura->Edit("direccion_cliente",$direccion_cliente ? $direccion_cliente : "NULL");
				$factura->Edit("codigo_cliente",$codigo_cliente ? $codigo_cliente : "");
				$factura->Edit("id_cobro",$id_cobro ? $id_cobro: NULL);
				$factura->Edit("id_documento_legal",$id_documento_legal? $id_documento_legal:1);
				if( !isset( $factura->fields['serie_documento_legal']) )
				{
					$factura->Edit("serie_documento_legal",Conf::GetConf($sesion,'SerieDocumentosLegales'));
				}
				$factura->Edit("numero", $numero ? $numero : "1");
				$factura->Edit("id_estado", $id_estado ? $id_estado : "1");
				$factura->Edit("id_moneda", $id_moneda_factura ? $id_moneda_factura : "1");
				if($id_estado=='5')
				{
					$factura->Edit('estado','ANULADA');
					$factura->Edit('anulado',1);
					$mensaje_accion = 'anulado';
				}
				else if(!empty($factura->fields['anulado'])){
					$factura->Edit('estado','ABIERTA');
					$factura->Edit('anulado','0');
				}

				if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'DesgloseFactura')=='con_desglose')))
				{ 
					$factura->Edit("descripcion", $descripcion_honorarios_legales);
					$factura->Edit("honorarios", $monto_honorarios_legales ? $monto_honorarios_legales : NULL);
					$factura->Edit("subtotal", $monto_honorarios_legales ? $monto_honorarios_legales: NULL);
					$factura->Edit("subtotal_sin_descuento", $monto_honorarios_legales ? $monto_honorarios_legales: NULL);
					$factura->Edit("descripcion_subtotal_gastos",$descripcion_gastos_con_iva ? $descripcion_gastos_con_iva: NULL);
					$factura->Edit("subtotal_gastos",$monto_gastos_con_iva ? $monto_gastos_con_iva: NULL);
					$factura->Edit("descripcion_subtotal_gastos_sin_impuesto",$descripcion_gastos_sin_iva ? $descripcion_gastos_sin_iva: NULL);
					$factura->Edit("subtotal_gastos_sin_impuesto",$monto_gastos_sin_iva ? $monto_gastos_sin_iva: NULL);
					$factura->Edit("total",$total ? $total: NULL);
					$factura->Edit("iva",$iva_hidden ? $iva_hidden: NULL);
				}
				else
				{
					$factura->Edit("descripcion",$descripcion);
				}
			
				$factura->Edit('letra',$letra);
				if($letra_inicial)
					$factura->Edit('letra',$letra_inicial);
	
				if (empty($factura->fields['id_factura'])) $generar_nuevo_numero = true;
	
				if($id_cobro){
					$cobro = new Cobro($sesion);
					if(!$cobro->Load($id_cobro)) $cobro = null;
					if($cobro) $factura->Edit('id_moneda', $cobro->fields['opc_moneda_total']);
					}
	
				if (!$factura->ValidarDocLegal())
				{
					$pagina->AddInfo('El numero ' . $numero . ' del ' . __('documento tributario') .' ya fue usado, pero se ha asignado uno nuevo, por favor verifique los datos y vuelva a guardar');
					$factura->Edit('numero', $factura->ObtenerNumeroDocLegal($id_documento_legal));
				}
				else if($factura->Escribir())
				{
					if($id_cobro)
					{
						$cobro = new Cobro($sesion);
						if(!$cobro->Load($id_cobro)) $cobro = null;
						if($cobro)
						{
							$factura->Edit('id_moneda', $cobro->fields['opc_moneda_total']);
							if($id_estado=='5')
							{
								if( !$cobro->TieneFacturasSinAnular() )
								{
									$cobro->Edit('estado', 'EMITIDO');
								}
								elseif( $cobro->TieneFacturasSinAnular() && !$cobro->TienePago() )
								{
									$cobro->Edit('estado', 'ENVIADO AL CLIENTE');
								}
								elseif( $cobro->TieneFacturasSinAnular() && $cobro->TienePago() )
								{
									$cobro->Edit('estado', 'PAGO PARCIAL');
								}
							}
							elseif($id_estado == '1')
							{
								$facturas_sin_anular = $cobro->CantidadFacturasSinAnular();
								if ( $facturas_sin_anular == 1 )
								{
									$cobro->Edit('estado', 'FACTURADO');
									$cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s'));
								}
							}
							$cobro->Write();
						}
					}
					
				if ($generar_nuevo_numero) {
					$factura->GuardarNumeroDocLegal($id_documento_legal, $numero);
				}
				$signo = $codigo_tipo_doc == 'NC' ? 1 : -1; //es 1 o -1 si el tipo de doc suma o resta su monto a la liq
				$neteos = empty($id_factura_padre) ? null : array(array($id_factura_padre, $signo*$factura->fields['total']));

				$cta_cte_fact = new CtaCteFact($sesion);
				$mvto_guardado = $cta_cte_fact->RegistrarMvto($factura->fields['id_moneda'],
					$signo*($factura->fields['total']-$factura->fields['iva']),
					$signo*$factura->fields['iva'],
					$signo*$factura->fields['total'],
					$factura->fields['fecha'],
					$neteos,
					$factura->fields['id_factura'],
					null,
					$codigo_tipo_doc,
					$ids_monedas_documento,
					$tipo_cambios_documento,
					!empty($factura->fields['anulado']));
				
				if( $mvto_guardado->fields['tipo_mvto'] = 'NC' && $mvto_guardado->fields['saldo'] == 0 )
				{
					$query = "SELECT id_estado FROM prm_estado_factura WHERE codigo = 'C'";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($id_estado_cobrado) = mysql_fetch_array($resp);
					
					$factura->Edit('id_estado',$id_estado_cobrado);
				}
				
				$pagina->AddInfo(__('Documento Tributario').' '.$mensaje_accion.' '.__(' con éxito'));
				$requiere_refrescar = "window.opener.Refrescar();";
				
				
				# Esto se puede descomentar para imprimir facturas desde la edición
				
				if($id_cobro)
				{
					$documento = new Documento($sesion);
					$documento->LoadByCobro($id_cobro);
					
					$valores = array(
						$factura->fields['id_factura'],
						$id_cobro,
						$documento->fields['id_documento'],
						$factura->fields['subtotal_sin_descuento']+$factura->fields['subtotal_gastos']+$factura->fields['subtotal_gastos_sin_impuesto'],
						$factura->fields['iva'],
						$documento->fields['id_moneda'],
						$documento->fields['id_moneda']
					);
					
					$query = "DELETE FROM factura_cobro WHERE id_factura = '".$factura->fields['id_factura']."' AND id_cobro = '".$id_cobro."' ";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					
					$query = "INSERT INTO factura_cobro (id_factura, id_cobro, id_documento, monto_factura, impuesto_factura, id_moneda_factura, id_moneda_documento)
					VALUES ('".implode("','",$valores)."')";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				}
				
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ImprimirFacturaPdf') ) || ( method_exists('Conf','ImprimirFacturaPdf') && Conf::ImprimirFacturaPdf() ) )
				{
	?>
				<script type='text/javascript'>
					window.open("agregar_factura.php?opc=generar_factura&id_factura=<?=$factura->fields['id_factura']?>","Factura",'width=500,height=500,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,copyhistory=yes,resizable=yes');
				</script>
<?
				}
			}
			
			//echo "entré";
			$observacion = new Observacion($sesion);
			$observacion->Edit('fecha', date('Y-m-d H:i:s'));
			$observacion->Edit('comentario', "MODIFICACIÓN FACTURA");
			$observacion->Edit('id_usuario',$sesion->usuario->fields['id_usuario']);
			$observacion->Edit('id_factura', $factura->fields['id_factura']);
			$observacion->Write();			
		}
	}
		
		#Se ingresa la anotación de modificación de factura en el historial	
		if(!$id_factura && $factura->loaded())
			$id_factura = $factura->fields['id_factura'];
			
		$txt_pagina = $id_factura ? __('Edición de ').$tipo_documento_legal.' #'.$factura->fields['numero'] : __('Ingreso de ').$tipo_documento_legal;
		if($id_cobro)
			$txt_pagina .= ' '.__('para Cobro').' #'.$id_cobro;
		
		$pagina->titulo = $txt_pagina;
		$pagina->PrintTop($popup);


		/*
		 * Mostrar valores por defecto  
		 */
		
		//SIN DESGLOSE
		$suma_monto =0;
		$suma_iva =0;
		$suma_total =0;
		
		//CON DESGLOSE
		$descripcion_honorario = __('Honorarios Legales');
		$monto_honorario = 0;
		$descripcion_subtotal_gastos = __('Gastos c/ IVA');
		$monto_subtotal_gastos = 0;
		$descripcion_subtotal_gastos_sin_impuesto = __('Gastos s/ IVA');
		$monto_subtotal_gastos_sin_impuesto = 0;
		
		//ASIGNO LOS MONTOS POR DEFECTO DE LOS DOCUMENTOS
		$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion,$id_cobro);
		$opc_moneda_total = $x_resultados['opc_moneda_total'];
		$id_moneda_factura = $opc_moneda_total;
		if($factura->loaded())
			$id_moneda_factura = $factura->fields['id_moneda'];
		$cifras_decimales_opc_moneda_total = $x_resultados['cifras_decimales_opc_moneda_total'];
		$subtotal_honorarios = $x_resultados['monto_honorarios'][$opc_moneda_total];
		$subtotal_gastos_sin_impuestos = $x_resultados['subtotal_gastos_sin_impuesto'][$opc_moneda_total];
		$subtotal_gastos = $x_resultados['subtotal_gastos'][$opc_moneda_total]-$subtotal_gastos_sin_impuestos;
		$impuesto_gastos = $x_resultados['impuesto_gastos'][$opc_moneda_total];
		$impuesto = $x_resultados['impuesto'][$opc_moneda_total];
		
		//SIN DESGLOSE
		$suma_monto =$subtotal_honorarios+$subtotal_gastos;
		$suma_iva =$impuesto_gastos+$impuesto;
		$suma_total =$subtotal_honorarios+$subtotal_gastos+$impuesto_gastos+$impuesto;
		
		//CON DESGLOSE
		$descripcion_honorario = __('Honorarios Legales');
		$monto_honorario = $subtotal_honorarios;
		$descripcion_subtotal_gastos = __('Gastos c/ IVA');
		$monto_subtotal_gastos = $subtotal_gastos;
		$descripcion_subtotal_gastos_sin_impuesto = __('Gastos s/ IVA');
		$monto_subtotal_gastos_sin_impuesto = $subtotal_gastos_sin_impuestos;			


		if($factura->loaded())
		{
			$porcentaje_impuesto = $factura->fields['porcentaje_impuesto'];
		}
		else if($id_cobro > 0)
		{
			$cobro = new Cobro($sesion);
			$cobro->load($id_cobro);
			$porcentaje_impuesto = $cobro->fields['porcentaje_impuesto'];
		}
		else
		{
			//$porcentaje_impuesto = Conf::GetConf($sesion,'ValorImpuesto');
			$porcentaje_impuesto = 0;
		}
		
		
			
			$query_moneda = "SELECT m.simbolo , m.glosa_moneda FROM prm_moneda m WHERE m.id_moneda = ".$id_moneda_factura;
			$resp_moneda = mysql_query($query_moneda,$sesion->dbh) or Utiles::errorSQL($resp_moneda,__FILE__,__LINE__,$sesion->dbh);
			list($simbolo, $glosa_moneda)=mysql_fetch_array($resp_moneda);
	if($factura->fields['total'] >0){
			$simbolo = "<span style='padding-left:5px'>".$simbolo."</span>";
			
			//SIN DESGLOSE 
			if($factura->fields['subtotal']) {
				$suma_monto = $factura->fields['subtotal'];
			}
			if($factura->fields['iva']) {
				$suma_iva = $factura->fields['iva'];
			}
			if($factura->fields['total']) {
				$suma_total = $factura->fields['total'];
			}
			
			//CON DESGLOSE

			$descripcion_honorario = $factura->fields['descripcion'];
			$monto_honorario = $factura->fields['subtotal'];
			$descripcion_subtotal_gastos = $factura->fields['descripcion_subtotal_gastos'];
			$monto_subtotal_gastos = $factura->fields['subtotal_gastos'];
			$descripcion_subtotal_gastos_sin_impuesto = $factura->fields['descripcion_subtotal_gastos_sin_impuesto'];
			$monto_subtotal_gastos_sin_impuesto = $factura->fields['subtotal_gastos_sin_impuesto'];
			
			if($descripcion_honorario == '') {
				$descripcion_honorario = __('Honorarios Legales');
			}
			if($descripcion_subtotal_gastos == '') {
				$descripcion_subtotal_gastos = __('Gastos c/ IVA');
			}
			if($descripcion_subtotal_gastos_sin_impuesto == '') {
				$descripcion_subtotal_gastos_sin_impuesto = __('Gastos s/ IVA');
			}
			
		}
		if($monto_honorario == '') {
			$monto_honorario = 0;
		}
		if($monto_subtotal_gastos == '') {
			$monto_subtotal_gastos = 0;
		}
		if($monto_subtotal_gastos_sin_impuesto == '') {
			$monto_subtotal_gastos_sin_impuesto = 0;
		}
		
		/*
		 * FIN - Mostrar valores por defecto  
		 */
?>
<script type="text/javascript">
	<!-- funcion ajax para asignar valores a los campos del cliente en agregar factura -->
	function CargarDatosCliente()
	{
		<?php
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{ 
		?>
		var id_origen = 'codigo_cliente_secundario';
		<?php
		}
		else
		{
		?>
		var id_origen = 'codigo_cliente';
		<?php
		}
		?>
		var accion = 'cargar_datos_cliente';
		var select_origen = document.getElementById(id_origen);
		var rut = document.getElementById('RUT_cliente');
		var cliente = document.getElementById('cliente');;
		var direccion_cliente = document.getElementById('direccion_cliente');
		
		<?php
		if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'DesgloseFactura')=='con_desglose')))
		{ 
		?>
		var descripcion_honorarios_legales = document.getElementById('descripcion_honorarios_legales');
		var monto_honorarios_legales = document.getElementById('monto_honorarios_legales');
		var monto_iva_honorarios_legales = document.getElementById('monto_iva_honorarios_legales');
		var descripcion_gastos_con_iva = document.getElementById('descripcion_gastos_con_iva');
		var monto_gastos_con_iva = document.getElementById('monto_gastos_con_iva');
		var monto_iva_gastos_con_iva = document.getElementById('monto_iva_gastos_con_iva');
		
		<?php
			if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'UsarGastosConSinImpuesto')=='1'))) {
		?>
			var descripcion_gastos_sin_iva = document.getElementById('descripcion_gastos_sin_iva');
			var monto_gastos_sin_iva = document.getElementById('monto_gastos_sin_iva');
		<?php
			}
		}
		else
		{
		?>
		var descripcion = document.getElementById('descripcion');
		<?php
		}
		?>	
		var http = getXMLHTTP();

		var url = root_dir + '/app/interfaces/ajax.php?accion=' + accion + '&codigo_cliente=' + select_origen.value ;

		http.open('get', url, true);
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;

				if(response.indexOf('|') != -1)
				{
					response = response.split('\\n');
					response = response[0];
					var campos = response.split('~');
					if(response.indexOf('VACIO') != -1)
					{ 
						//dejamos los campos en blanco.
						rut.value = '';
						direccion_cliente.value = '';
						cliente.value = '';
						
						select_destino.options.length = 1;
						offLoading();
						alert('No existen <?php echo __('cobros'); ?> para este cliente.');
					}
					else
					{
						//select_destino.length = 1;
						for(i = 0; i < campos.length; i++)
						{
							valores = campos[i].split('|');
							var option = new Option();
							option.value = valores[0];
							option.text = valores[1];
							
							if(valores[2] != '')
								rut.value = valores[2];
							else
								rut.value = '';
							if(valores[1] != '')
								direccion_cliente.value = valores[1];
							else
								direccion_cliente.value = '';
							if(valores[0] != '')
								cliente.value = valores[0];
							else
								cliente.value = '';
						}
					}
				}
				else
				{
					if(response.indexOf('head')!=-1)
					{
						alert('Sesión Caducada');
						top.location.href='".Conf::Host()."';
					}
					else
						alert(response);
				}
			}
			cargando = false;
		};
		http.send(null);
	}


//Extend the scal library to add draggable calendar support.
//This script block can be added to the scal.js file.
Object.extend(scal.prototype,
{
    toggleCalendar: function()
    {
        var element = $(this.options.wrapper) || this.element;
        this.options[element.visible() ? 'onclose' : 'onopen'](element);
        this.options[element.visible() ? 'closeeffect' : 'openeffect'](element, {duration: 0.5});
    },

    isOpen: function()
    {
        return ( $(this.options.wrapper) || this.element).visible();
    }
});

//this is a global variable to have only one instance of the calendar
var calendar = null;

//@element   => is the <div> where the calender will be rendered by Scal.
//@input     => is the <input> where the date will be updated.
//@container => is the <div> for dragging.
//@source    => is the img/button which raises up the calender, the script will locate the calenar over this control.
function showCalendar(element, input, container, source)
{
    if (!calendar)
    {
        container = $(container);
        //the Draggable handle is hard coded to "rtop" to avoid other parameter.
        new Draggable(container, {handle: "rtop", starteffect: Prototype.emptyFunction, endeffect: Prototype.emptyFunction});

        //The singleton calendar is created.
        calendar = new scal(element, $(input),
        {
            updateformat: 'dd-mm-yyyy',
            closebutton: '&nbsp;',
            wrapper: container
        });
    }
    else
    {
        calendar.updateelement = $(input);
    }

    var date = new Date($F(input));
    calendar.setCurrentDate(isNaN(date) ? new Date() : date);

    //Locates the calendar over the calling control  (in this example the "img").
    if (source = $(source))
    {
        Position.clone($(source), container, {setWidth: false, setHeight: false, offsetLeft: source.getWidth() + 2});
    }

    //finally show the calendar =)
    calendar.openCalendar();
};

function MostrarTipoCambioPago()
{
	$('TipoCambioFactura').show();
}
function CancelarDocumentoMonedaPago()
{
	$('TipoCambioFactura').hide();
}

document.observe('dom:loaded', function() {
});

function BuscarFacturas()
{
	document.forms.item(submit);
}

function Letra()
{
	$('letra_inicial').show();
}

function mostrarAccionesEstado(form)
{
	var id_estado = form.id_estado.value;
	$('letra_inicial').hide();
	if(id_estado=='4')
	{
		$('letra_inicial').show();
	}
	else if(id_estado=='5')
	{
		//Cambiar(form,'anular');
	}
}

function CambioCliente()
{
	//$('id_cobro').value = 'nulo';
	CargarDatosCliente();	
}

function Cambiar(form,opc)
{
		form.opcion.value = opc;
		form.submit();
}
var saltar_validacion_saldo = 0;
var mostrar_alert_saldo =0;
function ValidaSaldoPendienteCobro(form)
{
	var http = getXMLHTTP();
	var url = 'ajax.php?accion=saldo_cobro_factura&id=' + $('id_cobro').value;
	var honorarios = form.monto_neto.value;
	var gastos_con_impuestos = form.monto_gastos_con_iva.value;
	var gastos_sin_impuestos = 0;
	var tipo_doc_legal = form.id_documento_legal.value;
    loading("Actualizando campo");
    http.open('get', url, false);
    http.onreadystatechange = function()
    {
       if(http.readyState == 4)
       {
			var response = http.responseText;
			if(response == 'primera_factura')
			{
				saltar_validacion_saldo=1;
			}
			saldos = response.split('//');
			$('honorario_disp').value = saldos[0];
			$('gastos_con_impuestos_disp').value = saldos[1];
			$('gastos_sin_impuestos_disp').value = saldos[2];

			offLoading();
       }
    };
    http.send(null);
}

enviado = 0;
function Validar(form)
{
	
<? 
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{ ?>
		if( form.glosa_cliente.value == "" )
		{
			alert('<?=__('Debe ingresar un cliente')?>');
			form.glosa_cliente.focus();
			return false;
		}
<? }
	else if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
	{ ?>
		if( form.codigo_cliente_secundario.value == "" )
		{
			alert('<?=__('Debe ingresar un cliente')?>');
			form.codigo_cliente_secundario.focus();
			return false;
		}
<? }
	else 
	{ ?>
		if( form.codigo_cliente.value == "" )
		{
			alert('<?=__('Debe ingresar un cliente')?>');
			form.codigo_cliente.focus();
			return false;
		}
<? } ?>
		
		if( form.RUT_cliente.value == "")
		{
			alert("<?=__('Debe ingresar el').' '.__('RUT').' '.__('del cliente.')?>");
			form.RUT_cliente.focus();
			return false;
		}
		if( form.cliente.value == "" )
		{
			alert("<?=__('Debe ingresar la razon social del cliente.')?>");
			form.cliente.focus();
			return false;
		}
	<?php
		if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'DesgloseFactura')=='con_desglose')))
		{ 
		?>
		if(form.descripcion_honorarios_legales.value == "")
		{
			alert('<?=__('Debe ingresar una descripción para los honorarios')?>');
			form.descripcion_honorarios_legales.focus();
			return false;
		}
		if(form.monto_honorarios_legales.value == "")
		{
			alert('<?=__('Debe ingresar un monto para los honorarios')?>');
			form.monto_honorarios_legales.focus();
			return false;
		}
		if(form.monto_iva_honorarios_legales.value == "")
		{
			alert('<?=__('Debe ingresar un monto IVA para los honorarios')?>');
			form.monto_iva_honorarios_legales.focus();
			return false;
		}
		if(form.descripcion_gastos_con_iva.value == "")
		{
			alert('<?=__('Debe ingresar una descripción para los gastos c/ IVA')?>');
			form.descripcion_gastos_con_iva.focus();
			return false;
		}
		if(form.monto_gastos_con_iva.value == "")
		{
			alert('<?=__('Debe ingresar un monto para los gastos c/ IVA')?>');
			form.monto_gastos_con_iva.focus();
			return false;
		}
		if(form.monto_iva_gastos_con_iva.value == "")
		{
			alert('<?=__('Debe ingresar un monto iva para los gastos c/ IVA')?>');
			form.monto_iva_gastos_con_iva.focus();
			return false;
		}
		<?php
		if(!$factura->loaded() && ($id_documento_legal!=2)){
		?>
		ValidaSaldoPendienteCobro(form);
		if((form.id_documento_legal.value!=2) && (saltar_validacion_saldo==0) && (
			form.monto_honorarios_legales.value > form.honorario_disp.value ||
			form.monto_gastos_con_iva.value > form.gastos_con_impuestos_disp.value)){
			if(!confirm('<?=__("Los montos ingresados superan el saldo a facturar")?>')){
				if(form.monto_honorarios_legales.value > form.honorario_disp.value) {
					form.monto_honorarios_legales.focus();
				}
				else if(form.monto_gastos_con_iva.value > form.gastos_con_impuestos_disp.value) {
					form.monto_gastos_con_iva.focus();
				}
				return false;
			}
		}
		<?php
		}
		?>
		<?php
			if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'UsarGastosConSinImpuesto')=='1')))
			{ 
			?>
				if(form.descripcion_gastos_sin_iva.value == "")
				{
					alert('<?=__('Debe ingresar una descripción para los gastos s/ IVA')?>');
					form.descripcion_gastos_con_iva.focus();
					return false;
				}
				if(form.monto_gastos_sin_iva.value == "")
				{
					alert('<?=__('Debe ingresar un monto para los gastos s/ IVA')?>');
					form.monto_gastos_sin_iva.focus();
					return false;
				}
			<?php
			}
		}
		else
		{
		?>
		if(form.descripcion.value == "")
		{
			alert('<?=__('Debe ingresar una descripción')?>');
			form.descripcion.focus();
			return false;
		}
		<?php
		}
		?>
	
	if(form.id_factura_padre && form.id_factura_padre.value == ""){
		alert('<?=__('Este documento debe estar asociado a un documento tributario')?>');
		form.id_factura_padre.focus();
		return false;
	}
	

	form.opcion.value='guardar';
	if(!enviado)
	{
		<?php
		if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'DesgloseFactura')=='con_desglose')))
		{
		?>
		form.iva_hidden.value = form.iva.value;
		<?php
		}
		?>
		enviado = 1;
		form.submit();
	}
	return true;
}

function Cerrar()
{
	window.close();
}

function desgloseMontosFactura(form){
	var porcentaje_impuesto = <?=$porcentaje_impuesto;?>;
	var porcentaje_impuesto_gastos = 0;
	var monto_impuesto = 0;
	var monto_impuesto_gasto = 0;
	var monto_honorario = 0;
	var monto_gasto_con_impuesto = 0;
	var monto_gasto_sin_impuesto = 0;
	var monto_neto_suma = 0;
	var decimales = <?php echo $cifras_decimales_opc_moneda_total;?>;
	<?php
	if($id_cobro > 0){
		$cobro = new Cobro($sesion);
		$cobro->load($id_cobro);
		?>
		porcentaje_impuesto_gastos = <?=$cobro->fields['porcentaje_impuesto_gastos'];?>;
		
		<?php
	}
	else{
		if($cobro->fields['porcentaje_impuesto_gastos'] == 0 && (( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'ValorImpuestoGastos'))))) {
			?>
			porcentaje_impuesto_gastos = <?=Conf::GetConf($sesion,'ValorImpuestoGastos');?>;
			//porcentaje_impuesto_gastos = 0;
			<?php
		}
	}	
	?>
	monto_impuesto = form.monto_honorarios_legales.value*(porcentaje_impuesto/100);
	monto_impuesto_gasto = form.monto_gastos_con_iva.value*(porcentaje_impuesto_gastos/100);
	monto_impuesto = monto_impuesto.toFixed(decimales);
	monto_impuesto_gasto = monto_impuesto_gasto.toFixed(decimales);
	monto_impuesto_suma = parseFloat(monto_impuesto) + parseFloat(monto_impuesto_gasto);
	
	<?php
	if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'UsarGastosConSinImpuesto')=='1')))
	{
		?>
		monto_gasto_sin_impuesto = form.monto_gastos_sin_iva.value;
		<?php
	} 
	?>
		
	monto_neto_suma = parseFloat(form.monto_honorarios_legales.value) + parseFloat(form.monto_gastos_con_iva.value) + parseFloat(monto_gasto_sin_impuesto);
	
	form.monto_neto.value = monto_neto_suma;
	form.monto_iva_honorarios_legales.value = monto_impuesto;
	form.monto_iva_gastos_con_iva.value = monto_impuesto_gasto;
	form.iva.value = monto_impuesto_suma.toFixed(decimales);
	var total = Number($('monto_neto').value.replace(',','.')) + Number($('iva').value.replace(',','.')); 
	$('total').value = total.toFixed(decimales);

<?php if ( method_exists('Conf','GetConf') && (Conf::GetConf($sesion, 'CantidadDecimalesTotalFactura') != '-1') ) { ?>
	// Si esta la configuración, los redondeo después de cada cálculo
	aproximarDecimales();
<?php } ?>
}

function ActualizarDocumentoMonedaPago()
{
	ids_monedas = $('ids_monedas_factura').value;
	arreglo_ids = ids_monedas.split(',');
	$('tipo_cambios_factura').value = "";
	for(var i = 0; i<arreglo_ids.length-1; i++)
		$('tipo_cambios_factura').value += $('factura_moneda_'+arreglo_ids[i]).value + ",";
	i=arreglo_ids.length-1;
	$('tipo_cambios_factura').value += $('factura_moneda_'+arreglo_ids[i]).value;
	alert( $('id_factura').value );
	if( $('id_factura').value != '' )
		{
			var tc = new Array();
			for(var i = 0; i< arreglo_ids.length; i++)
					tc[i] = $('factura_moneda_'+arreglo_ids[i]).value;
			$('contenedor_tipo_load').innerHTML = 
			"<table width=510px><tr><td align=center><br><br><img src='<?=Conf::ImgDir()?>/ajax_loader.gif'/><br><br></td></tr></table>";
			var http = getXMLHTTP();
			var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_factura_moneda&id_factura=<?=$factura->fields['id_factura']?>&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');	
			http.open('get', url);
			http.onreadystatechange = function()
			{
				if(http.readyState == 4)
				{
					var response = http.responseText;
					alert( response );
					if(response == 'EXITO')
					{
						$('contenedor_tipo_load').innerHTML = '';	
					}
				}
			}	
			http.send(null);
		}
	CancelarDocumentoMonedaPago();
}
</script>
<? echo Autocompletador::CSS(); ?>

<form method=post id="form_facturas" name="form_facturas">
  <input type=hidden name=opcion value="" />
  <input type=hidden name=id_factura id=id_factura value="<?=$factura->fields['id_factura']?>" />
  <input type=hidden name=id_documento_legal value="<?=$id_documento_legal?>" />
  <input type=hidden name=elimina_ingreso id=elimina_ingreso value=''>
  <input type=hidden name=id_cobro id=id_cobro value='<?=$id_cobro?>'/>
  <input type=hidden name="id_moneda_factura" id="id_moneda_factura" value='<?=$id_moneda_factura?>'/>
  <input type=hidden name="honorario_disp" id="honorario_disp" value='<?=$honorario_disp?>'/>
  <input type=hidden name="gastos_con_impuestos_disp" id="gastos_con_impuestos_disp" value='<?=$gastos_con_impuestos_disp?>'/>
  <input type=hidden name="gastos_sin_impuestos_disp" id="gastos_sin_impuestos_disp" value='<?=$gastos_sin_impuestos_disp?>'/>
  <input type='hidden' name='opc' id='opc' value='buscar'>
  <input type="hidden" name="porcentaje_impuesto" id="porcentaje_impuesto" value="<?=$porcentaje_impuesto;?>">
  
  <!-- Calendario DIV -->
  <div id="calendar-container" style="width:221px; position:absolute; display:none;">
    <div class="floating" id="calendar"></div>
  </div>
  <!-- Fin calendario DIV --> 
  <br>
  <table width='90%'>
    <tr>
      <td align=left><b>
        <?=$txt_pagina ?>
        </b></td>
    </tr>
  </table>
  <br>
  <table style="border: 0px solid black;" width='90%'>
    <tr>
      <td align=left><b>
        <?=__('Información de').' '.$tipo_documento_legal?>
        </b></td>
    </tr>
  </table>
  <table class="border_plomo" style="background-color:#FFFFFF;" width='90%'>
    <?
	$numero_documento = '';
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaNumeracionAutomatica') ) || ( method_exists('Conf','UsaNumeracionAutomatica') && Conf::UsaNumeracionAutomatica() ) )
	{ 
		$numero_documento = $factura->ObtieneNumeroFactura();
	} 
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') ) || ( method_exists('Conf','NuevoModuloFactura') && Conf::NuevoModuloFactura() ) ) )
	{
		$numero_documento = $factura->ObtenerNumeroDocLegal($id_documento_legal);
	} 
	?>
    <tr>
      <td align=right><?=__('Número')?></td>
      <td align=left><input type="text" name="numero" value="<?=$factura->fields['numero'] ? $factura->fields['numero']:$numero_documento ?>" id="numero" size="11" maxlength="10" /></td>
      <td align=right><?=__('Estado')?></td>
      <td align=left><?= Html::SelectQuery($sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC","id_estado", $factura->fields['id_estado'] ? $factura->fields['id_estado']:$id_estado, 'onchange="mostrarAccionesEstado(this.form)"','',"160"); ?></td>
    </tr>
    <?  //Se debe elegir un documento legal padre si:
		$buscar_padre = false;
	  
		$query_doc = " SELECT codigo FROM prm_documento_legal WHERE id_documento_legal = '$id_documento_legal'";		
		$resp_doc = mysql_query($query_doc,$sesion->dbh) or Utiles::errorSQL($query_doc,__FILE__,__LINE__,$sesion->dbh);
		list($codigo_documento_legal) = mysql_fetch_array($resp_doc);
	
		if(($codigo_documento_legal == 'NC') && ($id_cobro || $codigo_cliente))
		{
			if($id_cobro)
			{
				$query_padre ="SELECT id_factura, CONCAT( prm_documento_legal.glosa,' #',numero) FROM factura JOIN prm_documento_legal USING (id_documento_legal) WHERE id_cobro = '$id_cobro'";
			}
			else if($codigo_cliente)
			{
				$query_padre ="SELECT id_factura, CONCAT( prm_documento_legal.glosa,' #',numero) FROM factura JOIN prm_documento_legal USING (id_documento_legal) WHERE codigo_cliente = '$codigo_cliente'";
			}
			$resp_padre = mysql_query($query_padre,$sesion->dbh) or Utiles::errorSQL($query_padre,__FILE__,__LINE__,$sesion->dbh);
			if( list($a,$b) = mysql_fetch_array($resp_padre))
			{
					$buscar_padre = true;
			}
		}
			
		if($buscar_padre){
	?>
    <tr>
      <td align=right><?=__('Para Documento Tributario:')?></td>
      <td align=left colspan=3><?=Html::SelectQuery( $sesion, $query_padre, 'id_factura_padre',$factura->fields['id_factura_padre'],'','--','160')?></td>
    </tr>
    <?}?>
    <tr>
      <td align=right><?=__('Fecha')?></td>
      <td align=left colspan=2><input type="text" name="fecha" value="<?=$factura->fields['fecha'] ? Utiles::sql2date($factura->fields['fecha']) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
        <img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" /></td>
      <td><span style='display:none' id=letra_inicial>&nbsp;&nbsp;
        <?=__('Letra')?>
        :&nbsp;
        <input name='letra_inicial' value='<?=$factura->fields['letra'] ? $factura->fields['letra']:'' ?>' size=10/>
        </span></td>
    </tr>
    <tr>
      <td align=right><?=__('Cliente')?></td>
      <td align=left colspan=3><?
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						{
							echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario, '',280,'');
						}
					else
						{
							echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente,'', '',280,'');
						}
				}
			else
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						{
							echo InputId::ImprimirSinCualquiera($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario, "","CambioCliente()", 280); 
						}
					else
						{
							echo InputId::ImprimirSinCualquiera($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $factura->fields['codigo_cliente'] ? $factura->fields['codigo_cliente'] : $codigo_cliente," ","CambioCliente();", 280); 
						}
				}
			?>
        
        <!--<input type="text" name="cliente" value="<?=$factura->fields['cliente']?>" id="cliente" size="70" maxlength="99" />--> 
        <span style="color:#FF0000; font-size:10px">*</span></td>
    </tr>
    <tr>
      <td align=right><?=__('RUT/NIT')?></td>
      <td align=left colspan=3><input type="text" name="RUT_cliente" value="<?=$factura->fields['RUT_cliente']?>" id="RUT_cliente" size="70" maxlength="20" /></td>
    </tr>
    <tr>
      <td align=right><?=__('Raz&oacute;n Social Cliente')?></td>
      <td align=left colspan=3><input type="text" name="cliente" value="<?=$factura->fields['cliente']?>" id="cliente" size="70"/></td>
    </tr>
    <tr>
      <td align=right><?=__('Dirección Cliente')?></td>
      <td align=left colspan=3><input type="text" name="direccion_cliente" value="<?=$factura->fields['direccion_cliente']?>" id="direccion_cliente" size="70" maxlength="255" /></td>
    </tr>
    <?php
	
	
	
	if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'DesgloseFactura')=='con_desglose')))
	{ 	
	?>
    <tr id='descripcion_factura'>
      <td align=right width="100">&nbsp;</td>
      <td align=left style="vertical-align:bottom" width="250"><?=__('Descripción');?></td>
      <td align=left width="100"><?=__('Monto');?></td>
      <td align=left><?=__('Monto Impuesto');?></td>
    </tr>
    <tr>
      <td align=right><?=__('Honorarios legales');?></td>
      <td align=left><input type="text" name="descripcion_honorarios_legales" value="<?=$descripcion_honorario;?>" size="40" maxlength="300"></td>
      <td align=left><?=$simbolo;?>
        <input type="text" name="monto_honorarios_legales" value="<?php echo isset($honorario) ? $honorario : $monto_honorario;?>" size="10" maxlength="30" onblur="desgloseMontosFactura(this.form)";></td>
      <td align=left><?=$simbolo;?>
        <input type="text" name="monto_iva_honorarios_legales" value="<?=$impuesto;?>" disabled="true" value="0" size="10" maxlength="30"></td>
    </tr>
    <tr>
      <td align=right><?=__('Gastos c/ IVA');?></td>
      <td align=left><input type="text" name="descripcion_gastos_con_iva" value="<?=$descripcion_subtotal_gastos;?>" size="40" maxlength="30"></td>
      <td align=left><?=$simbolo;?>
        <input type="text" name="monto_gastos_con_iva" value="<?php echo isset($gastos_con_iva) ? $gastos_con_iva : $monto_subtotal_gastos;?>" size="10" maxlength="30" onblur="desgloseMontosFactura(this.form)"></td>
      <td align=left><?=$simbolo;?>
        <input type="text" name="monto_iva_gastos_con_iva" value="<?=$impuesto_gastos;?>" disabled="true" value="0" size="10" maxlength="30"></td>
    </tr>
    <?php
		if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'UsarGastosConSinImpuesto')=='1'))) {
	?>
    <tr>
      <td align=right><?=__('Gastos s/ IVA');?></td>
      <td align=left><input type="text" name="descripcion_gastos_sin_iva" value="<?=$descripcion_subtotal_gastos_sin_impuesto;?>" size="40" maxlength="30"></td>
      <td align=left><?=$simbolo;?>
        <input type="text" name="monto_gastos_sin_iva" value="<?php echo isset($gastos_sin_iva) ? $gastos_sin_iva : $monto_subtotal_gastos_sin_impuesto;?>" size="10" maxlength="30" onblur="desgloseMontosFactura(this.form)"></td>
      <td align=left>&nbsp;</td>
    </tr>
    <?php
		}
	?>
    <tr>
      <td align=right colspan=2 ><?=__('Monto')?></td>
      <td align=left><?=$simbolo;?>
        <input type="text" name="monto_neto" id='monto_neto' value="<?=$suma_monto;?>" size="10" maxlength="30" disabled="true" onchange="var total = Number($('monto_neto').value.replace(',','.')) + Number($('iva').value.replace(',','.')); $('total').value = total.toFixed(2);" /></td>
      <td align=left>&nbsp;</td>
    </tr>
    <tr id='descripcion_factura'>
      <td align=right colspan=2><?=__('Impuesto')?></td>
      <td align=left><?=$simbolo;?>
        <input type="text" id='iva' name="iva" value="<?=$suma_iva;?>" size="10" maxlength="30" disabled="true" onchange="var total = Number($('monto_neto').value.replace(',','.')) + Number($('iva').value.replace(',','.')); $('total').value = total.toFixed(2);" />
        <input type="hidden" id='iva_hidden' name="iva_hidden"></td>
    </tr>
    <tr id='descripcion_factura'>
      <td align=right colspan=2><?=__('Monto Total')?></td>
      <td align=left><?=$simbolo;?>
        <input type="text" id='total' name="total" value="<?=$suma_total;?>" size="10" maxlength="30"  readonly onfocus="this.blur();"></td>
      <td>&nbsp;</td>
    </tr>
    <?php	
	}
	else
	{
	?>
    <tr id='descripcion_factura'>
      <td align=right><?=__('Descripción')?></td>
      <td align=left><textarea id='descripcion' name=descripcion cols="45" rows="3"><?=$factura->fields['descripcion']?>
</textarea></td>
    </tr>
    <tr id='descripcion_factura'>
      <td align=right><?=__('Monto')?></td>
      <td align=left><input type="text" name="monto_neto" id='monto_neto' value="<?=$suma_monto;?>" onchange="var total = Number($('monto_neto').value.replace(',','.')) + Number($('iva').value.replace(',','.')); $('total').value = total.toFixed(2);" /></td>
    </tr>
    <tr id='descripcion_factura'>
      <td align=right><?=__('Impuesto')?></td>
      <td align=left><input type="text" id='iva' name="iva" value="<?=$suma_iva;?>" size="10" maxlength="30"   onchange="var total = Number($('monto_neto').value.replace(',','.')) + Number($('iva').value.replace(',','.')); $('total').value = total.toFixed(2);" /></td>
    </tr>
    <tr id='descripcion_factura'>
      <td align=right><?=__('Monto Total')?></td>
      <td align=left><input type="text" id='total' name="total" value="<?=$suma_total;?>" size="10" maxlength="30"  readonly onfocus="this.blur();"></td>
    </tr>
    <?php
	}
	?>
    <tr>
      <td align=right colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="4" align=center><img src="<?=Conf::ImgDir()?>/money_16.gif" border=0> <a href='javascript:void(0)' onclick="MostrarTipoCambioPago()" title="<?=__('Tipo de Cambio del Documento de Pago al ser pagado.')?>">
        <?=__('Actualizar Tipo de Cambio')?>
        </a></td>
    </tr>
    <tr>
      <td align=right colspan="4">&nbsp;</td>
    </tr>
    <tr>
      <td align=right colspan="4"><div id="TipoCambioFactura" style="display:none; left: 100px; top: 300px; background-color: white; position:absolute; z-index: 4;">
          <fieldset style="background-color:white;">
            <legend>
            <?=__('Tipo de Cambio Docuemnto de Pago')?>
            </legend>
            <div id="contenedor_tipo_load">&nbsp;</div>
            <div id="contenedor_tipo_cambio">
              <table style='border-collapse:collapse;' cellpadding='3'>
                <tr>
                  <?
						if( $factura->fields['id_factura'] )
							{
								$query = "SELECT count(*) 
													FROM cta_cte_fact_mvto_moneda 
													LEFT JOIN cta_cte_fact_mvto AS ccfm ON ccfm.id_cta_cte_mvto=cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto 
													WHERE ccfm.id_factura = '".$factura->fields['id_factura']."'";
								$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
								list($cont) = mysql_fetch_array($resp);
							}
						else
							$cont = 0;
						if( $cont > 0 )
							{
								$query = 
								"SELECT prm_moneda.id_moneda, glosa_moneda, cta_cte_fact_mvto_moneda.tipo_cambio 
									FROM cta_cte_fact_mvto_moneda 
									JOIN prm_moneda ON cta_cte_fact_mvto_moneda.id_moneda = prm_moneda.id_moneda 
									LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_cta_cte_mvto = cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto 
									WHERE cta_cte_fact_mvto.id_factura = '".$factura->fields['id_factura']."'";
								$resp =mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
							}
						else
							{
								$query =
								"SELECT prm_moneda.id_moneda, glosa_moneda, cobro_moneda.tipo_cambio 
									FROM cobro_moneda 
									JOIN prm_moneda ON cobro_moneda.id_moneda = prm_moneda.id_moneda 
									WHERE id_cobro = '".$id_cobro."'";
								$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
							}
						$num_monedas=0; $ids_monedas = array(); $tipo_cambios = array();
						while(list($id_moneda,$glosa_moneda,$tipo_cambio) = mysql_fetch_array($resp))
						{
						?>
                  <td><span><b>
                    <?=$glosa_moneda?>
                    </b></span><br>
                    <input type='text' size=9 id='factura_moneda_<?=$id_moneda?>' name='factura_moneda_<?=$id_moneda?>' value='<?=$tipo_cambio?>' /></td>
                  <?
							$num_monedas++;
							$ids_monedas[] = $id_moneda;
							$tipo_cambios[] = $tipo_cambio;
						}
						?>
                <tr>
                  <td colspan=<?=$num_monedas?> align=center><input type=button onclick="ActualizarDocumentoMonedaPago($('todo_cobro'))" value="<?=__('Guardar')?>" />
                    <input type=button onclick="CancelarDocumentoMonedaPago()" value="<?=__('Cancelar')?>" />
                    <input type=hidden id="tipo_cambios_factura" name="tipo_cambios_factura" value="<?=implode(',',$tipo_cambios)?>" />
                    <input type=hidden id="ids_monedas_factura" name="ids_monedas_factura" value="<?=implode(',',$ids_monedas)?>" /></td>
                </tr>
              </table>
            </div>
          </fieldset>
        </div></td>
    </tr>
  </table>
  <br>
  <table style="border: 0px solid black;" width='90%'>
    <tr>
      <td align=left><input type=button class=btn value="<?=__('Guardar')?>" onclick='return Validar(this.form);' />
        <input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
        <?if($factura->loaded() && $factura->fields['anulado']==1){?>
        <input type=button class=btn value="<?=__('Restaurar')?>" onclick="return Cambiar(this.form,'restaurar');" />
        <?}?></td>
    </tr>
  </table>
</form>
<?php
if(( method_exists('Conf','GetConf') && (Conf::GetConf($sesion,'DesgloseFactura')=='con_desglose')))
{ 
?>
<script type="text/javascript">
desgloseMontosFactura(document.form_facturas);
<?php if($factura->loaded() && $factura->fields['id_estado']=='4' && $factura->fields['letra']!='') {?>
Letra();
<?php } ?>
</script>
<?php
}
?>
<script type="text/javascript">

if (document.getElementById('img_fecha'))
{
	Calendar.setup(
		{
			inputField	: "fecha",				// ID of the input field
			ifFormat	: "%d-%m-%Y",			// the date format
			button		: "img_fecha"		// ID of the button
		}
	);
}

<?php if ( method_exists('Conf','GetConf') && (Conf::GetConf($sesion, 'CantidadDecimalesTotalFactura') != '-1') ) { ?>
var cantidad_decimales = <?php echo Conf::GetConf($sesion, 'CantidadDecimalesTotalFactura'); ?>;

var formulario = document.form_facturas;

$(formulario.monto_honorarios_legales).observe('change', aproximarDecimales);
$(formulario.monto_gastos_con_iva).observe('change', aproximarDecimales);

function aproximarDecimales(input) {
	
	formulario.monto_honorarios_legales.value = Number(formulario.monto_honorarios_legales.value).toFixed(cantidad_decimales);
	
	formulario.monto_gastos_con_iva.value = Number(formulario.monto_gastos_con_iva.value).toFixed(cantidad_decimales);
	
	formulario.monto_neto.value = Number(formulario.monto_neto.value).toFixed(cantidad_decimales);
	
	formulario.monto_iva_honorarios_legales.value = Number(formulario.monto_iva_honorarios_legales.value).toFixed(cantidad_decimales);
	
	formulario.monto_iva_gastos_con_iva.value = Number(formulario.monto_iva_gastos_con_iva.value).toFixed(cantidad_decimales);
	
	formulario.iva.value = Number(formulario.iva.value).toFixed(cantidad_decimales);
	
	formulario.total.value = Number(formulario.total.value).toFixed(cantidad_decimales);
}

$(document).observe('dom:loaded', aproximarDecimales);

<?php } ?>

</script>
<?
	if($codigo_cliente || $codigo_cliente_secundario)
	{
		if(empty($id_factura))
		{
?>
<script type="text/javascript">
			CargarDatosCliente();
		</script>
<?
		}
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo Autocompletador::Javascript($sesion,false,'CambioCliente();');
	}
	if($requiere_refrescar)
		echo '<script type="text/javascript">'.$requiere_refrescar.'</script>';
	
	echo InputId::Javascript($sesion);
	$pagina->PrintBottom($popup);
?>
