<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/SelectorHoras.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Tramite.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('PRO','REV','SEC'));
	$pagina = new Pagina($sesion);
	
	$tramite = new Tramite($sesion);
	if ($id_tramite > 0) {
		$tramite->Load($id_tramite);
	}
	if ( $tramite->fields['trabajo_si_no']==1 || $como_trabajo==1 ) {
		$t = new Trabajo($sesion);
	}
	
	$params_array['codigo_permiso'] = 'REV';
	$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	$params_array['codigo_permiso'] = 'COB';
	$permiso_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

	//echo $como_trabajo . " < al abrir o recargar la página ";
	if($id_tramite > 0)
	{
		if( $tramite->fields['trabajo_si_no']==1 || $como_trabajo==1 )
			{
			$query = "SELECT id_trabajo FROM trabajo WHERE id_tramite=".$id_tramite;
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($id_trabajo)=mysql_fetch_array($resp);
			if($id_trabajo > 0);
			$t->Load($id_trabajo);
			}
		if($tramite->Estado() == 'Cobrado' && $opcion != 'nuevo')
		{
			$pagina->AddError(__('Trámite ya cobrado'));
			$pagina->PrintTop($popup);
			$pagina->PrintBottom($popup);
			exit;
		}
		if($tramite->Estado() == 'Revisado' && $opcion != 'nuevo')
		{
			if(!$permisos->fields['permitido'])
			{
				$pagina->AddError(__('Trámite ya revisado'));
				$pagina->PrintTop($popup);
				$pagina->PrintBottom($popup);
				exit;
			}
		}
		if(!$id_usuario)
			$id_usuario = $tramite->fields['id_usuario'];

		
		// hemos cambiado el cliente por lo tanto
		// este trabajo tomará un cobro CREADO del asunto, sino NULL
		if(!$codigo_asunto_secundario)
		{
			//se carga el codigo secundario
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigo($tramite->fields['codigo_asunto']);
			$codigo_asunto_secundario=$asunto->fields['codigo_asunto_secundario'];
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($asunto->fields['codigo_cliente']);
			$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
			$codigo_cliente=$asunto->fields['codigo_cliente'];
		}
		if($codigo_asunto != $tramite->fields['codigo_asunto'])#revisar para codigo secundario
		{
			$cambio_asunto = true;
		}
	}
	else //Si no se está editando un trámite
	{
			if(!$id_usuario) {
				$id_usuario = $sesion->usuario->fields['id_usuario'];
			}
			if( $opcion != 'guardar' )
			{
				$tramite->fields['cobrable']=1;
			}
			$es_tramite_nuevo=1;
	}


  // OPCION -> Guardar else Eliminar
	if($opcion == "guardar")
	{
			$valida = true;
			$asunto = new Asunto($sesion);
			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) {
				$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
				$codigo_asunto=$asunto->fields['codigo_asunto'];
			}
			else
			{
				$asunto->LoadByCodigo($codigo_asunto);
			}

			
			//Ha cambiado el asunto del trabajo se setea nuevo Id_cobro de alguno que esté creado
			//y corresponda al nuevo asunto y esté entre las fechas que corresponda, sino, se setea NULL
			
			if($cambio_asunto)
			{
				$cobro = new Cobro($sesion);
				$id_cobro_cambio = $cobro->ObtieneCobroByCodigoAsunto($codigo_asunto, $tramite->fields['fecha']);
				if($id_cobro_cambio)
				{
					if($t)
					$t->Edit('id_cobro',$id_cobro_cambio);
					$tramite->Edit('id_cobro',$id_cobro_cambio);
				}
				else
				{
					if($t)
					$t->Edit('id_cobro','NULL');
					$tramite->Edit('id_cobro','NULL');
				}
			}
			//Revisa el Conf si esta permitido y la función existe
			if($t)
			{
					if( method_exists('Conf','GetConf') )
					{
						if(Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal')
						{
								$t->Edit("duracion",UtilesApp::Decimal2Time($duracion));
								$tramite->Edit("duracion",UtilesApp::Decimal2Time($duracion));
						}
						else if(Conf::GetConf($sesion,'TipoIngresoHoras')=='java')
						{
								$t->Edit("duracion",$duracion);
								$tramite->Edit("duracion",$duracion);
						}
						else if(Conf::GetConf($sesion,'TipoIngresoHoras')=='selector')
						{
								$t->Edit("duracion",$duracion);
								$tramite->Edit("duracion",$duracion);
						}
					}
					else if (method_exists('Conf','TipoIngresoHoras'))
					{
						if(Conf::TipoIngresoHoras()=='decimal')
						{
								$t->Edit("duracion",UtilesApp::Decimal2Time($duracion));
								$tramite->Edit("duracion",UtilesApp::Decimal2Time($duracion));
						}
						else if(Conf::TipoIngresoHoras()=='java')
						{
								$t->Edit("duracion",$duracion);
								$tramite->Edit("duracion",$duracion);
						}
						else if(Conf::TipoIngresoHoras()=='selector')
						{
								$t->Edit("duracion",$duracion);
								$tramite->Edit("duracion",$duracion);
						}
					}
					else
					{
						$t->Edit("duracion",$duracion);
						$tramite->Edit("duracion",$duracion);
					}
					//Revisa el Conf si esta permitido y la función existe
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
						{
							$t->Edit('duracion_cobrada',UtilesApp::Decimal2Time($duracion));
						}
						else 
						{
							$t->Edit("duracion_cobrada",$duracion);
						}
				}
				else
				{
				$tramite->Edit('duracion','00:00:00');
				}

			if($t) {
			$t->Edit('codigo_asunto', $codigo_asunto);
			}
			$tramite->Edit('codigo_asunto', $codigo_asunto);

			if (method_exists('Conf','GetConf'))
			{
				$Ordenado_por = Conf::GetConf($sesion, 'OrdenadoPor');
			}
			else if(method_exists('Conf','Ordenado_por'))
			{
				$Ordenado_por = Conf::Ordenado_por();
			}
			else
			{
				$Ordenado_por = 0;
			}
			
		
			if(($Ordenado_por==1 || $Ordenado_por==2) && $t){
				$t->Edit('solicitante',$solicitante);
			}

			if($t) {
			$t->Edit('descripcion',$descripcion);
			$t->Edit('fecha',Utiles::fecha2sql($fecha));
			}
			$tramite->Edit('descripcion',$descripcion);
			$tramite->Edit('fecha',Utiles::fecha2sql($fecha));			
			#$t->Edit('fecha',$fecha);
			if($codigo_actividad && $t)
				$t->Edit('codigo_actividad',$codigo_actividad);
			if($revisado) {
				if($t) {
				$t->Edit('revisado',1);
				}
				$tramite->Edit('revisado',1);
				}
		
			if(!$cobrable) {
				$tramite->Edit('cobrable','0');
			}
			else
			{
				$tramite->Edit('cobrable','1');
			}
			
		
			if($t) {
				if(!$cobrable) {
					$t->Edit('cobrable','0');
				} else {
					$t->Edit('cobrable','1');
				}
				$t->Edit('visible','1');
				}
			
			if(!$id_usuario) {
				if ($t) {
				$t->Edit("id_usuario",$sesion->usuario->fields['id_usuario']);
				}
				$tramite->Edit("id_usuario",$sesion->usuario->fields['id_usuario']);
			} else {
				if($t) {
					$t->Edit("id_usuario",$id_usuario);
				}
				$tramite->Edit("id_usuario",$id_usuario);
				}
			
			if( $monto_modificar == "1")
			{
				$tramite->Edit("tarifa_tramite_individual",$tarifa_tramite_individual);
			}
			else
			{
				$tramite->Edit("tarifa_tramite_individual", "0");
			}
			$tramite->Edit("id_moneda_tramite_individual",$id_moneda_tramite_individual);
			$tramite->Edit("trabajo_si_no",$como_trabajo);
			$tramite->Edit("id_tramite_tipo",$lista_tramite);

			// Agregar valores de tarifa
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigo($tramite->fields['codigo_asunto']);
			$contrato = new Contrato($sesion);
			$contrato->Load($asunto->fields['id_contrato']);
			$tramite->Edit('id_moneda_tramite', $contrato->fields['id_moneda_tramite']);
			$tramite->Edit('tarifa_tramite', Funciones::TramiteTarifa($sesion, $lista_tramite, $contrato->fields['id_moneda_tramite'], $codigo_asunto));
			if($t) {
				if (!$t->fields['tarifa_hh']) {
				$t->Edit('tarifa_hh', Funciones::Tarifa($sesion, $id_usuario, $contrato->fields['id_moneda'], $codigo_asunto));
				}
				if (!$t->fields['costo_hh']) {
				$t->Edit('costo_hh', Funciones::TarifaDefecto($sesion, $id_usuario, $contrato->fields['id_moneda']));
			}
			}
			
			if( $como_trabajo==0 && $t )
			{
				$t->Eliminar();
			}
	
	if( !$como_trabajo ) { 		
		$guardados = 0;
		for($i = 1; $i <= $multiplicador; $i++) {
			$tramite->fields['id_tramite'] = null;
			if( $tramite->Write() ) {
				$guardados++;
			} else {
				$pagina->AddError( __("Error al guardar") . ' ' . ( $multplicador > 1 ? __('los trámites')  : __('el trámite') ) );
				$i = $multiplicador + 1;
			}
			
		}
		if( $guardados > 1 ) {
			$pagina->AddInfo(__('Trámites').' '.($nuevo?__('guardados con exito'):__('editado con éxito')));
		} else {
			$pagina->AddInfo(__('Trámite').' '.($nuevo?__('guardado con exito'):__('editado con éxito')));
		}
		
		if( $edit==1 ) {
						?>
										<script>
											if(window.opener)
												{
												window.opener.Refrescar( 'edit' );
												}
										</script>
<?php
				} else {
						?>
										<script>
											if(window.opener)
												{
												window.opener.Refrescar( 'nuevo' );
												}
										</script>
<?php
		}
	} else {
		if($tramite->Write())
			{
			if($t){
				$t->Edit('id_tramite',$tramite->fields['id_tramite']);
			}
			if( !$t )
				{
						
								$pagina->AddInfo(__('Trámite').' '.($nuevo?__('guardado con exito'):__('editado con éxito')));
				#refresca el listado de horas.php cuando se graba la informacion desde el popup
				
				if( $edit==1 ) {
						?>
										<script>
											if(window.opener)
												{
												window.opener.Refrescar( 'edit' );
												}
										</script>
<?php
				} else {
						?>
										<script>
											if(window.opener)
												{
												window.opener.Refrescar( 'nuevo' );
												}
										</script>
<?php
						}
			} else if($t->Write()) {
						
								$pagina->AddInfo(__('Trámite').' '.($nuevo?__('guardado con exito'):__('editado con éxito')));
				#refresca el listado de horas.php cuando se graba la informacion desde el popup
				
				if( $edit==1 ) {
						?>
										<script>
											if(window.opener)
												{
												window.opener.Refrescar( 'edit' );
												}
										</script>
<?php
				} else {
						?>
										<script>
											if(window.opener)
												{
												window.opener.Refrescar( 'nuevo' );
												}
										</script>
<?php
						}
				}
			}
			unset($id_trab);
		}
		// Nuevo en el caso de ser llamado desde Resumen semana, para que haga
		// refresh al form
		if($nuevo || $edit)
		{
?>
			<script>
				if(window.opener && window.opener.document.form_semana.submit() )
				{
					window.close();
				}
			</script>
<?
		}
	} 
	else if($opcion == "eliminar")  #ELIMINAR TRABAJO
	{
		$tramite = new Tramite($sesion);
		$tramite->Load($id_tramite);
		if(! $tramite->Eliminar() )
			$pagina->AddError($tramite->error);
		if($t) 
			unset($t);
		unset($tramite);
		unset($codigo_asunto_secundario);
		unset($codigo_cliente_secundario);
?>
		<script>
			if(window.opener)
				{
					window.opener.Refrescar( 'edit' );
				}
		</script>
<?
		$tramite = new Tramite($sesion);
		if($como_trabajo==1) {
 		$t = new Trabajo($sesion);
 		}
		$pagina->AddInfo(__('Trámite').' '.__('eliminado con éxito'));
		#$up = 1;
	}

	// Título opcion 
	if($opcion == '' && $id_tramite > 0)
		$txt_opcion = __('Modificación de Trámite');
	else if($id_tramite == NULL) // si no tenemos id de trabajo es porque se está agregando uno nuevo.
		$txt_opcion = __('Agregando nuevo Trámite');
	else if($opcion == '')
		$txt_opcion = '';
	
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente != '' && !$codigo_cliente_secundario)
	{
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($codigo_cliente);
		$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
	}
	$pagina->titulo = __('Modificación de').' '.__('Trámite');
	
	if( isset($id_tramite) || !isset( $tramite->fields['multiplicador'] ) || !is_numeric($tramite->fields['multiplicador']) || ( is_numeric($tramite->fields['multiplicador']) && $tramite->fields['multiplicador'] < 1 ) ) {
		$multiplicador = 1;
		$tramite->fields['multiplicador'] = 1;
	}
	$pagina->PrintTop($popup);
?>

<script type="text/javascript">

function ShowTime()
{
		var check = $('como_trabajo');
		var tr = $('time_tr');
	
		if(check.checked)
		{
			tr.style['display'] = '';
		}
		else
		{
			tr.style['display'] = 'none';
		}
}

function ToggleCantidad(activar){
	var idTramite = <?php echo ( $id_tramite || $tramite->fields['id_tramite'] ) ? 'true' : 'false' ;  ?>;
	if( !idTramite ) {
		var despliegue = ( activar ) ? 'none' : 'table-row';
		jQuery('#filamultiplicador').css('display',despliegue);
	}
	document.getElementById('multiplicador').value = 1;
}

function validaCantidad(cantidad, desdedonde ) {
	if( cantidad > 99 ) {
		alert('La cantidad de repetición del trámite no puede superar las 99 veces.');		
		if( desdedonde == 'validandoform') {
			return false;
		}
		document.getElementById('multiplicador').focus();		
	}
	return true;
}

function ModificarMonto( tipo ) 
{
	if( tipo == "modificar" )
	{
		$('tr_tarifa_mod').style.display = "table-row";
		$('modificar_monto').style.display = "none";
		$('usar_monto_original').style.display = "inline";
		$('monto_modificar').value = "1";
	}
	else
	{
		$('tr_tarifa_mod').style.display = "none";
		$('modificar_monto').style.display = "inline";
		$('usar_monto_original').style.display = "none";
		$('monto_modificar').value = "0";
	}
}

function Confirmar(form, id_trab)
{
			var r=confirm("Está modificando un trámite, desea continuar?");
			if(r==true)
				{
				var como_trab=$('como_trabajo').checked;
				if( como_trab==false && id_trab != '' )
					{
					if( confirm("Se va a borrar el trabajo correspondiente al trámite, desea continuar?"))
						{
						Validar(form);
						}
					else
						{
						return false;
						}
					}
				else
					{
					Validar(form);
					}
				}
			else
				{
				return false;
				}
}

function CargarMonedaContrato()
{ 
	var id_tramite_tipo = $('lista_tramite').value;
	<?php
		if( UtilesApp::GetConf($sesion,'CodigoSecundario') )
		{ ?>
			var codigo_asunto = $('codigo_asunto_secundario').value;
			var codigo_cliente = $('codigo_cliente_secundario').value;
<?php }
		else
		{ ?>
			var codigo_asunto = $('codigo_asunto').value;
			var codigo_cliente = $('codigo_cliente').value;
<?php } ?>

	var http = getXMLHTTP();
	var vurl = 'ajax.php?accion=cargar_moneda_contrato&id_tramite_tipo='+id_tramite_tipo+'&codigo_asunto='+codigo_asunto+'&codigo_cliente='+codigo_cliente;
	
	cargando = true;
	http.open('get', vurl, true);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			
			response = response.split('//');
			$('simbolo_moneda_contrato').value = response[0];
			$('id_moneda_contrato').value = response[1];
			$('tarifa_tramite').value = response[2];
			DefinirAlertaTarifa( response[2] );
		}
		cargando = false;
	};
	http.send(null);
}

function DefinirAlertaTarifa( tarifa_valor )
{
	if( ( tarifa_valor == 0 || tarifa_valor == '' ) && $('monto_modificar').value != 1 ) {
		$('tr_contenedor_alerta').style.background = 'red';
		$('tr_contenedor_alerta').innerHTML = 'La tarifa de este trámite no está definida.';
	}
	else {
		$('tr_contenedor_alerta').style.background = 'white';
		$('tr_contenedor_alerta').innerHTML = '';
	}
}

function SetDuracionDefecto( form )
{
	var tramite=$('lista_tramite').value;
	var http = getXMLHTTP();
	
	var vurl = 'ajax.php?accion=set_duracion_defecto&id='+tramite;
	
	  cargando = true;
    http.open('get', vurl, true);
    	 
    http.onreadystatechange = function()
    {
        if(http.readyState == 4)
        {
			var response = http.responseText;
			response = response.split('-');
			
			if($('hora_duracion'))
				{
					horas_separado = response[0].split(':');
					$('hora_duracion').value = (horas_separado[0]-0);
					if( $('minuto_duracion') )
						$('minuto_duracion').value = (horas_separado[1]-0);
				}
			
			$('duracion').value=response[0];
			if( response[1]==1 ){
				$('como_trabajo').checked = true;
				$('time_tr').style['display'] = '';				
				ToggleCantidad(true);
			}
			else if( response[1]==0 )
				{
				$('como_trabajo').checked = false;
				$('time_tr').style['display'] = 'none';
				ToggleCantidad(false);
			}
        }
        cargando = false;
	};
    http.send(null);
}

function Refrescar( text )
{
	var url = "listar_tramites.php?popup=1&opc=buscar&accion=refrescar&opc_orden=" + text;
	self.location.href= url;
}

function Validar(form)
{
<?php
	if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
	{
?>
	if(!form.codigo_asunto_secundario.value)
	{
		alert("<?=__('Debe seleccionar un').' '.__('asunto')?>");
		form.codigo_asunto_secundario.focus();
		return false;
	}
<?php
	}
	else
	{
?>
	if(!form.codigo_asunto.value)
	{
		alert("<?=__('Debe seleccionar un').' '.__('asunto')?>");
		form.codigo_asunto.focus();
		return false;
	}
<?php
	}
?>
	
	if(!form.fecha.value)
	{
		alert("<?=__('Debe ingresar una fecha.')?>");
		form.fecha.focus();
		return false;
	}
	
	if(!form.lista_tramite.value)
	{
		alert("<?=__('Debe seleccionar un Tipo Trámite')?>");
		form.lista_tramite.focus();
		return false;
	}
	
	//Revisa el Conf si esta permitido y la función existe
	if(form.como_trabajo.checked)
	{
<?php
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
	{
?>
		var dur=form.duracion.value.replace(",",".");
		if(isNaN(dur))
		{
			alert("<?=__('Solo se aceptan valores numéricos')?>");
			form.duracion.focus();
			return false;
		}
		var decimales=dur.split(".");
		if(decimales[1].length > 1)
		{
			alert("<?=__('Solo se permite ingresar un decimal')?>");
			form.duracion.focus();
			return false;
		}
<?php
	}
?>
	}
	
	//Valida si el asunto ha cambiado para este trabajo que es parte de un cobro, si ha cambiado se emite un mensaje indicandole lo ki pa
	if(form.id_cobro.value != '' && $('id_tramite').value != '')
	{
<?php
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) ) 
	{
?> 
		if(ActualizaCobro(form.codigo_asunto_secundario.value))
		{
			return true;
		}
		else
		{
			return false;
		}
<?php
	}else{ 
?> 
		if(ActualizaCobro(form.codigo_asunto.value))
		{
			return true;
		}
		else
		{
			return false;
		}
<?php
	} 
?> 
	}	
<?php
	if (method_exists('Conf','GetConf'))
	{
		$Ordenado_por = Conf::GetConf($sesion, 'OrdenadoPor');
	}
	else if(method_exists('Conf','Ordenado_por'))
	{
		$Ordenado_por = Conf::Ordenado_por();
	}
	else
	{
		$Ordenado_por = 0;
	}
	
	if($Ordenado_por==1)
	{
?> 
	if(form.solicitante.value=='')
	{
		alert("<?=__('Debe ingresar la persona que solicitó el tramite')?>");
		form.solicitante.focus();
		return false;
	}
<?php
	}
	
	if ( (method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TodoMayuscula') ) || ( method_exists('Conf','TodoMayuscula') && Conf::TodoMayuscula() ) )
	{
?>
	//Se pasa todo a mayúscula por configuración
	form.descripcion.value=form.descripcion.value.toUpperCase();
<?php
		if (method_exists('Conf','GetConf'))
		{
			$Ordenado_por = Conf::GetConf($sesion, 'OrdenadoPor');
		}
		else if(method_exists('Conf','Ordenado_por'))
		{
			$Ordenado_por = Conf::Ordenado_por();
		}
		else
		{
			$Ordenado_por = 0;
		}

		if ($Ordenado_por!=0)
		{
?>
	form.solicitante.value=form.solicitante.value.toUpperCase();
<?php
		}
	}

	// Si el usuario no tiene permiso de cobranza validamos la fecha del trabajo
	if (!$permiso_cobranza->fields['permitido'])
	{
?> 
	temp = $('fecha').value.split("-");
	fecha = new Date(temp[2]+'//'+temp[1]+'//'+temp[0]);
	hoy = new Date();
	fecha_tope = new Date(hoy.getTime()-(<?=($sesion->usuario->fields['dias_ingreso_trabajo']+1) ?>*24*60*60*1000));
	if (fecha_tope > fecha)
	{
		alert("No se pueden ingresar trámites anteriores a <?=date('d-m-Y',mktime(0,0,0,date('m'),date('d')-$dias,date('Y')))?>");
		$('fecha').focus;
		return false;
	}
<?php
	}
	
	//Si esta editando desde la página de ingreso de trabajo le pide confirmación para realizar los cambios
	if(isset($tramite) && $tramite->Loaded() && $opcion != 'nuevo')
	{
?>
	var string = new String(top.location);
	/*if(string.search('/ingreso_tramite.php') > 0)//revisa que esté en la página de ingreso de trámites
		if(!confirm('Está modificando un trámite, desea continuar?'))
			return false;*/
<?php
	}
?>
	pasavalidacion = validaCantidad(document.getElementById('multiplicador').value, 'validandoform');
	if( !pasavalidacion) { return false; }
	
	form.action='ingreso_tramite.php'
	form.submit();

	return true;
}

function CambiaDuracion(form, input)
{

	if(document.getElementById('duracion_cobrada') && input=='duracion')
		form.duracion_cobrada.value = form.duracion.value;

//	if(form.duracion.value != '00:00:00' && input == 'duracion')
//		form.duracion_cobrada.value = form.duracion.value;
}

/*Clear los elementos*/
function DivClear(div, dvimg)
{
	var left_data = document.getElementById('left_data');
	var content_data = document.getElementById('content_data');
	var right_data = document.getElementById('right_data');
	left_data.innerHTML = '';
	content_data.innerHTML = '';
	right_data.innerHTML = '';

	var content = document.getElementById('content_data2');
	var right = document.getElementById('right_data2');
	content.innerHTML = '';
	right.innerHTML = '';

	if( div == 'tr_cliente' )
	{
		var img = document.getElementById( 'img_asunto' );
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_asunto\',\'inline\',\'img_asunto\');">';
	}
	else
	{
		var img = document.getElementById( 'img_historial' );
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_cliente\',\'inline\',\'img_historial\');">';
	}
}

function ShowDiv(div, valor, dvimg)
{
	var div_id = document.getElementById(div);
	var img = document.getElementById(dvimg);
	var form = document.getElementById('form_editar_trabajo');
	var codigo = document.getElementById('campo_codigo_cliente').value;
	var tr = document.getElementById('tr_cliente');
	var tr2 = document.getElementById('tr_asunto');
	var al = document.getElementById('al');
	//var tbl_trabajo = document.getElementById('tbl_trabajo');

	DivClear(div, dvimg);

	if( div == 'tr_asunto' && codigo == '')
	{
		tr.style['display'] = 'none';
		alert("<?=__('Debe seleccionar un cliente')?>");
		form.codigo_cliente.focus();
		return false;
	}

	div_id.style['display'] = valor;
	/* FADE
	if(valor == 'inline')
		var fade = true;
	else
		var fade = false;
	setTimeout("MSG('"+div+"',"+fade+")",10);
	*/

	if( div == 'tr_cliente' )
	{
		WCH.Discard('tr_asunto');
		tr2.style['display'] = 'none';
		Lista('lista_clientes','left_data','','');
	}
	else if( div == 'tr_asunto' )
	{
		WCH.Discard('tr_cliente');
		tr.style['display'] = 'none';
		Lista('lista_asuntos','content_data2',codigo,'2');
	}

	/*Cambia IMG*/
	if(valor == 'inline')
	{
		WCH.Apply('tr_asunto');
		WCH.Apply('tr_cliente');
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/menos.gif" border="0" title="Ocultar" class="mano_on" onClick="ShowDiv(\''+div+'\',\'none\',\''+dvimg+'\');">';
	}
	else
	{
		WCH.Discard(div);
		img.innerHTML = '<img src="<?=Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\''+div+'\',\'inline\',\''+dvimg+'\');">';
	}
}

/*
AJAX Lista de datos historial
accion -> llama ajax
div -> que hace update
codigo -> codigo del parámetro necesario SQL
div_post -> id div posterior onclick
*/
function Lista(accion, div, codigo, div_post)
{
	var form = document.getElementById('form_editar_trabajo');
	var data = document.getElementById(div);
	hideddrivetip();
	if(accion == 'lista_asuntos')
	{
		form.campo_codigo_cliente.value = codigo;
		SetSelectInputId('campo_codigo_cliente','codigo_cliente');
<?
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			echo "CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos');";
		}
		else
		{
			echo "CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');";
		}
?>
	}
	else if(accion == 'lista_trabajos')
	{
		form.campo_codigo_asunto.value = codigo;
		SetSelectInputId('campo_codigo_asunto','codigo_asunto');
<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
	 { ?>
		CargarSelect('codigo_asunto','codigo_actividad','cargar_actividades');
<? }?>
	}

	var http = getXMLHTTP();

	if(div == 'content_data')
	{
		var right_data = document.getElementById('right_data');
		right_data.innerHTML = '';
	}

		var vurl = 'ajax_historial.php?accion='+accion+'&codigo='+codigo+'&div_post='+div_post+'&div='+div;
    http.open('get', vurl, true);
    http.onreadystatechange = function()
    {
        if(http.readyState == 4)
        {
			var response = http.responseText;
			data.innerHTML = response;
        }
	};
    http.send(null);
}

function CargaIdioma( codigo )
{
	var txt_span = document.getElementById('txt_span');
	if(!codigo)
	{
		txt_span.innerHTML = '';
		return false;
	}
	else
	{
		var accion = 'idioma';
		var http = getXMLHTTP();
		http.open('get','ajax.php?accion='+accion+'&codigo_asunto='+codigo, true);
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;
				var idio = response.split("|");
<?
		if (method_exists('Conf','GetConf'))
		{
			$IdiomaGrande = Conf::GetConf($sesion, 'IdiomaGrande');
		}
		else if(method_exists('Conf','IdiomaGrande'))
		{
			$IdiomaGrande = Conf::IdiomaGrande();
		}
		else
		{
			$IdiomaGrande = false;
		}

		if($IdiomaGrande)
		{
?>
				txt_span.innerHTML = idio[1];
<?
		}
		else
		{
?>
			txt_span.innerHTML = 'Idioma: '+idio[1];

				if(idio[1]=='Español')
					googie2.setCurrentLanguage('es');
				if(idio[1]=='Inglés')
					googie2.setCurrentLanguage('en');
<?
		}
?>
			}
		};
	    http.send(null);
	}
}


function ActualizaCobro(valor)
{
	var codigo_asunto_hide = $('codigo_asunto_hide').value;
	var id_cobro = $('id_cobro').value;
	var id_trabajo = $('id_trabajo').value;
	var fecha_trabajo_hide = $('fecha_trabajo_hide').value;
	var form = $('form_editar_trabajo');

	if(codigo_asunto_hide != valor && id_cobro && id_trabajo)
	{
		var text_window = "<img src='<?=Conf::ImgDir()?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?=__("ALERTA")?></u><br><br>";
		text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?=__('Ud. está modificando un trabajo que pertenece') . " " . __('al cobro')?>:'+id_cobro+' ';
		text_window += '<?=__('. Si acepta, el trabajo se desvinculará de') . " " . __('este cobro') . " " . __('y eventualmente se vinculará a') . " " . __('un cobro') . " " . __('pendiente para el nuevo asunto en caso de que exista')?>.</span><br>';
		text_window += '<br><table><tr>';
		text_window += '</table>';
		Dialog.confirm(text_window,
		{
			top:100, left:80, width:400, okLabel: "<?=__('Aceptar')?>", cancelLabel: "<?=__('Cancelar')?>", buttonClass: "btn", className: "alphacube",
			id: "myDialogId",
			cancel:function(win){ return false; },
			ok:function(win){ if(ActualizarCobroAsunto(valor)) form.submit(); return true; }
		});
	}
	else
	{
		return true;
	}
}

function ActualizarCobroAsunto(valor)
{
		var codigo_asunto_hide = $('codigo_asunto_hide').value;
		var id_cobro = $('id_cobro').value;
		var id_trabajo = $('id_trabajo').value;
		var fecha_trabajo_hide = $('fecha_trabajo_hide').value;
		var http = getXMLHTTP();
		var urlget = 'ajax.php?accion=set_cobro_trabajo&codigo_asunto='+valor+'&id_trabajo='+id_trabajo+'&fecha='+fecha_trabajo_hide+'&id_cobro_actual='+id_cobro;
		http.open('get',urlget, true);
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;
			}
		};
	    http.send(null);
	  return true;
}

function AgregarNuevo(tipo)
{
<? if(( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )){?>
	var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
	var codigo_asunto_secundario = $('codigo_asunto_secundario').value;
<? } else { ?>
	var codigo_cliente = $('codigo_cliente').value;
	var codigo_asunto = $('codigo_asunto').value;
<? } ?>
	if(tipo == 'tramite')
	{
		var urlo = "ingreso_tramite.php?popup=1";
		window.location=urlo;
	}
}

</script>
	
<style> 
A:link,A:visited {font-size:9px;text-decoration: none}
A:hover {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
A:active {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
</style>
<? echo(Autocompletador::CSS()); ?>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<?php
	if( $tramite->fields['tarifa_tramite_individual'] > 0 )
		$monto_modificar = "1";
	else
		$monto_modificar = "0";
?>
<table width="80%">
	<tr>
		<td height="30px" id="tr_contenedor_alerta" style="color: white; vertical-align: middle; text-align: center; font-size: 12px; font_weight: bold;">
			&nbsp;
		</td>
	</tr>
</table>
	
<form id="form_editar_trabajo" name=form_editar_trabajo method="post" action="<?=$_SERVER[PHP_SELF]?>">
<input type=hidden name=opcion value="guardar" />
<input type=hidden name="gIsMouseDown" id="gIsMouseDown" value=false />
<input type=hidden name="gRepeatTimeInMS" id="gRepeatTimeInMS" value=200 />
<input type=hidden name=max_hora id=max_hora value=14 />
<input type=hidden name='codigo_asunto_hide' id=codigo_asunto_hide value="<?=$tramite->fields['codigo_asunto']?>" />
<input type=hidden name='monto_modificar' id='monto_modificar' value="<?=$monto_modificar ?>" />
<?
	if( $opcion != 'nuevo' )
	{
?>
<input type=hidden name='id_tramite' value="<?= $tramite->fields['id_tramite'] ?>" id='id_tramite' />
<input type=hidden name='id_trabajo' value="<?= $t->fields['id_trabajo'] ?>" id='id_trabajo' />
<input type=hidden name='edit' value="<?= $opcion == 'edit' || $edit==1 ? 1 : '' ?>" id='edit' />
<input type=hidden name='fecha_tramite_hide' value="<?= $tramite->fields['fecha'] ?>" id='fecha_trabajo_hide' />
<?
	}
	if($id_trabajo == NULL) // si no tenemos id de trabajo es porque se estÃ¡ agregando uno nuevo.
	{
?>
<input type=hidden name='nuevo' value="1" id='nuevo' />
<?
	}
?>
<input type=hidden name=id_cobro id=id_cobro value="<?=$tramite->fields['id_cobro'] !='NULL' ? $tramite->fields['id_cobro'] : '' ?>" />
<input type=hidden name=popup value='<?=$popup?>' id="popup">

	<? if($id_tramite > 0) { ?>
<table style='border:0px solid black' <?=$txt_opcion ? 'style=display:inline' : 'style=display:none'?> width='90%'>
	<tr>
		<td width='40%' align=right>
			<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('tramite')" title="Ingresar Tramite"><u>Ingresar nuevo Trámite</u></a>
		</td>
	</tr>
</table>
	<? } ?>


<!-- TABLA HISTORIAL -->
<table id="tr_cliente" cellpadding="0" cellspacing="0" width="100%">
	<tr>
        <td colspan="7" class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
    	<td class="td_transparente">&nbsp;</td>
        <td class="td_transparente" colspan="5" align="right">
        	<img style="filter:alpha(opacity=100);" src="<?=Conf::ImgDir()?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_cliente','none','img_historial');">
        </td>
        <td class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td width="5%" class="td_transparente">&nbsp;</td>
        <td width="30%" id="leftcolumn" class="box_historial">
            <div id="titulos">
                <?=__('Cliente') ?>
            </div>
            <div id="left_data" class="span_data"></div>
        </td>
        <td class="td_transparente">
        </td>
        <td width="30%" id="content" class="box_historial">
            <div id="titulos">
                <?=__('Asunto') ?>
            </div>
            <div id="content_data" class="span_data"></div>
        </td>
        <td class="td_transparente">
        </td>
        <td width="30%" id="rightcolumn" class="box_historial">
            <div id="titulos">
                <?=__('Trámite') ?>
            </div>
            <div id="right_data" class="span_data"></div>
        </td>
        <td width="5%" class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="7" class="td_transparente" style="height:190px">&nbsp;</td>
    </tr>
</table>
<!-- TABLA SOBRE ASUNTOS -->
<table id="tr_asunto" cellpadding="0" cellspacing="0" width="100%">
	<tr>
        <td colspan="6" class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td class="td_transparente">&nbsp;</td>
        <td align="right" colspan="4" class="td_transparente">
            <img src="<?=Conf::ImgDir()?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_asunto','none','img_asunto');">
        </td>
        <td class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td width="5%" class="td_transparente">&nbsp;</td>
        <td width="45%" id="content" class="box_historial">
            <div id="titulos">
                <?=__('Asunto') ?>
            </div>
            <div id="content_data2" class="span_data"></div>
        </td>
        <td class="td_transparente">
        </td>
        <td width="45%" id="rightcolumn" class="box_historial">
            <div id="titulos">
                <?=__('Trámite') ?>
            </div>
            <div id="right_data2" class="span_data"></div>
        </td>
        <td class="td_transparente">
        </td>
        <td width="5%" class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="6" class="td_transparente" style="height:190px">&nbsp;</td>
    </tr>
</table>

<table style='border:0px solid black' <?=$txt_opcion ? 'style=display:inline' : 'style=display:none'?> width='90%'>
	<tr>
		<td align=left><span style=font-weight:bold; font-size:9px; backgroundcolor:#c6dead><?=$txt_opcion?></span>
		</td>
	</tr>
</table>
<br>


<table class="border_plomo" id="tbl_trabajo" width=90%>
    <tr>
        <td align=right>
			<?=__('Cliente')?>
        </td>
        <td align=left width="440" nowrap>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario);
		else
			echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,""           ,"CargarMonedaContrato();CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320,$codigo_asunto_secundario);
		}
		else
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarMonedaContrato();CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320,$codigo_asunto);
		}
	}
?>
        </td>
     </tr>
     <tr>
        <td align='right'>
             <?=__('Asunto')?>
        </td>
        <td align=left width="440" nowrap>
<?
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarMonedaContrato();CargaIdioma(this.value);CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $tramite->fields['codigo_asunto'] ? $tramite->fields['codigo_asunto'] : $codigo_asunto ,"","CargarMonedaContrato();CargaIdioma(this.value); CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}

?>
       </td>
    </tr>
<?
    $query="SELECT id_tramite_tipo, glosa_tramite FROM tramite_tipo ORDER BY glosa_tramite";
    ?>
    <tr nowrap>
    	<td align=right nowrap>Tipo Trámite </td><td width="440" align=left id='seleccion_tramite_text_2'>
    		<?=Html::SelectQuery( $sesion, $query, 'lista_tramite',$tramite->fields['id_tramite_tipo'] ? $tramite->fields['id_tramite_tipo'] : $lista_tramite,'onChange="CargarMonedaContrato();SetDuracionDefecto(this.form);" id="lista_tramite"','',320);?>
			</td>
		</tr>
  	<?
    if($fecha == '')
    	$fecha = date('d-m-Y');
    ?>
    <tr>
        <td align=right>
            <?=__('Fecha')?>
        </td>
        <td align=left valign="top">
            <!--<?= Html::PrintCalendar("fecha", $tramite->fields[fecha] ? $tramite->fields[fecha] : $fecha); ?>-->
            <input type="text" name="fecha" value="<?=$tramite->fields['fecha'] ? Utiles::sql2date($tramite->fields['fecha']) : $fecha ?>" id="fecha" size="11" maxlength="10"/>
		        <img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
<?
						if (method_exists('Conf','GetConf'))
						{
							$Ordenado_por = Conf::GetConf($sesion, 'OrdenadoPor');
						}
						else if(method_exists('Conf','Ordenado_por'))
						{
							$Ordenado_por = Conf::Ordenado_por();
						}
						else
						{
							$Ordenado_por = 0;
						}

						if($Ordenado_por==1 || $Ordenado_por==2)
						{
?>
						&nbsp;
						<?=__('Ordenado por')?>
						&nbsp;
						<input type="text" name="solicitante" value="<?=$t->fields['solicitante'] ? $t->fields['solicitante'] : $solicitante?>" id="solicitante" size="32" />
<?
						} //   
?>
        </td>
    </tr>
    <tr>
    	<td></td>
    	<td> <?
		//echo 'el field es '.$tramite->fields['trabajo_si_no'].' y como_trabajo es '.$como_trabajo;
    		if( !isset($tramite->fields['id_tramite'] ) ) {
					$query = "SELECT trabajo_si_no_defecto FROM tramite_tipo ORDER BY glosa_tramite LIMIT 1";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($como_trabajo)=mysql_fetch_array($resp);
					}
		 ?>
		
    		<input type=checkbox name=como_trabajo id="como_trabajo" value="1" onClick="ShowTime(); ToggleCantidad(this.checked);" <?=$tramite->fields['trabajo_si_no'] || $como_trabajo ? 'checked' : '' ?> >Ingresar como trabajo
    	</td>
    </tr>
			<tr id="time_tr" style='display:<?=$tramite->fields['trabajo_si_no'] || $como_trabajo ? '' :'none' ?>;'>
        <td align=right>
            <?=__('Duración')?>
        </td>
        <td align=left>
    <?
    if($tramite->fields['id_tramite_tipo'])
    	{
    		$query = "SELECT duracion_defecto FROM tramite_tipo WHERE id_tramite_tipo=".$tramite->fields['id_tramite_tipo'];
    		$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
    		list($duracion)=mysql_fetch_array($resp);
    	}
    else
    	{
    		$query = "SELECT duracion_defecto FROM tramite_tipo ORDER BY glosa_tramite LIMIT 1";
    		$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
    		list($duracion)=mysql_fetch_array($resp);
    	} // id="tb_time" style='display:<?=$tramite->fields['trabajo_si_no'] || $como_trabajo ? '' : 'none' ? >' 
    ?>
			<table>
			  <tr>
				<td>
<?
	//Revisa el Conf si esta permitido y la función existe
	if( method_exists('Conf','GetConf') )
	{
		if( Conf::GetConf( $sesion,'TipoIngresoHoras')=='selector' )
		{
			if(!$duracion) $duracion='00:00:00';
			echo SelectorHoras::PrintTimeSelector($sesion,"duracion", $tramite->fields['duracion'] ? $tramite->fields['duracion'] : $duracion, 14, '', $nuevo || $sesion->usuario->fields['id_usuario']==$id_usuario || $permisos->fields['permitido'] );
		}
		else if( Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' )
		{
?>
					<input type="text" name="duracion" value="<?=$tramite->fields['duracion'] ? UtilesApp::Time2Decimal($tramite->fields['duracion']) : UtilesApp::Time2Decimal($duracion) ?>" id="duracion" size="6" maxlength=4 <?= ( !$nuevo && $sesion->usuario->fields['id_usuario']!=$id_usuario ) || !$permisos->fields['permitido'] ? 'readonly' : '' ?> onchange="CambiaDuracion(this.form,'duracion');" <?=$como_trabajo==1 ? '' : 'readonly' ?> />
<?
		}
		else if( Conf::GetConf( $sesion,'TipoIngresoHoras')=='java' )
		{
			echo Html::PrintTime("duracion",$duracion,"$como_trabajo==1 || $tramite->fields['trabajo_si_no']==1 ? '' : 'readonly' onchange='CambiaDuracion(this.form ,\"duracion\");'", $nuevo || $sesion->usuario->fields['id_usuario']==$id_usuario);
		}
	}
	else if (method_exists('Conf','TipoIngresoHoras'))
	{
		if(Conf::TipoIngresoHoras()=='selector')
		{
			if(!$duracion) $duracion='00:00:00';
			echo SelectorHoras::PrintTimeSelector($sesion,"duracion", $tramite->fields['duracion'] ? $tramite->fields['duracion'] : $duracion, 14, '', $nuevo || $sesion->usuario->fields['id_usuario']==$id_usuario || $permisos->fields['permitido'] );
		}
		else if(Conf::TipoIngresoHoras()=='decimal')
		{
?>
					<input type="text" name="duracion" value="<?=$tramite->fields['duracion'] ? UtilesApp::Time2Decimal($tramite->fields['duracion']) : UtilesApp::Time2Decimal($duracion) ?>" id="duracion" size="6" maxlength=4 <?= ( !$nuevo && $sesion->usuario->fields['id_usuario']!=$id_usuario ) || !$permisos->fields['permitido'] ? 'readonly' : '' ?> onchange="CambiaDuracion(this.form,'duracion');" <?=$como_trabajo==1 ? '' : 'readonly' ?> />
<?
		}
		else if(Conf::TipoIngresoHoras()=='java')
		{
			echo Html::PrintTime("duracion",$duracion,"$como_trabajo==1 || $tramite->fields['trabajo_si_no']==1 ? '' : 'readonly' onchange='CambiaDuracion(this.form ,\"duracion\");'", $nuevo || $sesion->usuario->fields['id_usuario']==$id_usuario || $permisos->fields['permitido']);
		}
	}
	else
	{
		echo Html::PrintTime('duracion',$tramite->fields['duracion'] ? $tramite->fields['duracion'] : $duracion,"$como_trabajo==1 ? '' : 'readonly' onchange='CambiaDuracion(this.form ,\"duracion\");'", $nuevo || $sesion->usuario->fields['id_usuario']==$id_usuario || $permisos->fields['permitido']);
	}

?>		
			</td>
<?
	if($permisos->fields['permitido'])
		$where = " usuario_permiso.codigo_permiso='PRO' ";
	else
		$where = " (usuario_secretario.id_secretario = '".$sesion->usuario->fields['id_usuario']."'
							OR usuario.id_usuario IN ('$id_usuario','" . $sesion->usuario->fields['id_usuario'] . "') OR usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=".$sesion->usuario->fields[id_usuario].") OR usuario.id_usuario=".$sesion->usuario->fields[id_usuario].") ";
	$where .= " AND usuario.visible=1";
	$select_usuario = Html::SelectQuery($sesion,
		"SELECT usuario.id_usuario,
			CONCAT_WS(' ', apellido1, apellido2,',',nombre)
			as nombre FROM usuario
			JOIN usuario_permiso USING(id_usuario)
			LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional
			WHERE $where GROUP BY id_usuario ORDER BY nombre"
		,"id_usuario",$id_usuario,'','','width="200"');

if( $tramite->fields['id_tramite'] > 0 )
{
	$query = "SELECT prm_moneda.simbolo, prm_moneda.id_moneda, tramite_valor.tarifa 
										FROM contrato 
										JOIN asunto USING( id_contrato ) 
							 LEFT JOIN prm_moneda ON prm_moneda.id_moneda = contrato.id_moneda_tramite 
							 LEFT JOIN tramite_valor ON 
							 					 tramite_valor.id_moneda = contrato.id_moneda_tramite AND 
							 					 tramite_valor.id_tramite_tipo = '".$tramite->fields['id_tramite_tipo']."' AND 
							 					 tramite_valor.id_tramite_tarifa = contrato.id_tramite_tarifa 
									 WHERE asunto.codigo_asunto = '".$tramite->fields['codigo_asunto']."' "; 
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($simbolo_moneda_tramite_contrato, $id_moneda_tramite_contrato, $tarifa_tramite_contrato) = mysql_fetch_array($resp);
}

if( $tramite->fields['tarifa_tramite_individual'] > 0 )
{
	$display_tr_mod = 'style="display: table-row;"';
	$display_buton_modificar_monto = 'style="display: none;"';
	$display_buton_usar_monto_original = 'style="display: inline;"';
}
else
{
	$display_tr_mod = 'style="display: none;"';
	$display_buton_modificar_monto = 'style="display: inline;"';
	$display_buton_usar_monto_original = 'style="display: none;"';
}
?>

			</tr>
		</table>
        </td>
    </tr>
    <tr id="filamultiplicador" style="display: <?php echo ( isset($id_tramite) || $como_trabajo == 1 ) ? 'none' : 'table-row'; ?>">
    	<td align="right"> <?=__('Cantidad de repeticiones')?> 	</td>
    	<td align="left">
    		<input type="text" size="6" name="multiplicador" id="multiplicador" onkeyup="validaCantidad(this.value, 'validandoinput');"   value="<?php echo isset($tramite->fields['multiplicador'])  ? $tramite->fields['multiplicador']  : $multiplicador ?>" />
    	</td>
    </tr>
    <tr>
    	<td align="right">
    		<?=__('Valor según tarifa')?>
    	</td>
    	<td align="left">
    		<input type="text" size="6" name="tarifa_tramite" id="tarifa_tramite" disabled value="<?=$tarifa_tramite_contrato > 0 ? $tarifa_tramite_contrato : '0'?>" />
    		<input type="text" size="2" id="simbolo_moneda_contrato" disabled style="background-color: white; display: inline; border: 0px;" value="<?=$simbolo_moneda_tramite_contrato != '' ? $simbolo_moneda_tramite_contrato : ''?>" />
     		<img id="modificar_monto" <?=$display_buton_modificar_monto ?> src="<?=Conf::ImgDir().'/editar_on.gif'?>" title="<?=__('Modificar Monto')?>" border=0 style="cursor:pointer" onclick="ModificarMonto('modificar');CargarMonedaContrato();">
			  <img id="usar_monto_original" <?=$display_buton_usar_monto_original ?> src="<?=Conf::ImgDir().'/cruz_roja_nuevo.gif'?>" title="<?=__('Usar Monto Original')?>" border=0 style='cursor:pointer' onclick="ModificarMonto('cancelar');CargarMonedaContrato();"/>
			</td>
    </tr>
    <tr id="tr_tarifa_mod" <?=$display_tr_mod ?>>
    	<td align="right">
    		<?=__('Tarifa modificado')?>
    	</td>
    	<td align="left">
    		<input type="text" size="6" name="tarifa_tramite_individual" id="tarifa_tramite_individual" value="<?=$tramite->fields['tarifa_tramite_individual'] ? $tramite->fields['tarifa_tramite_individual'] : '0'?>" />
    		<?=Html::SelectQuery($sesion,"SELECT id_moneda, glosa_moneda FROM prm_moneda","id_moneda_tramite_individual",$tramite->fields['id_moneda_tramite_individual'] ? $tramite->fields['id_moneda_tramite_individual'] : '0','','','70')?>
       </td>
    </tr>
    <input type="hidden" name="id_moneda_contrato" id="id_moneda_contrato" value="1" />
    <tr>
        <td align=right>
        	<?
		if (method_exists('Conf','GetConf'))
		{
			$IdiomaGrande = Conf::GetConf($sesion, 'IdiomaGrande');
		}
		else if(method_exists('Conf','IdiomaGrande'))
		{
			$IdiomaGrande = Conf::IdiomaGrande();
		}
		else
		{
			$IdiomaGrande = false;
		}

		if($IdiomaGrande)
		{
?>
				<?=__('Descripción')?><br/><span id=txt_span style="background-color: #C6FAAD; font-size:18px"></span>
<?
		}
		else
		{
?>
			<?=__('Descripción')?><br/><span id=txt_span style="background-color: #C6FAAD; font-size:9px"></span>
<?
		}
?>
        </td>
        <td align=left>
            <textarea id="descripcion" cols=45 rows=4 name=descripcion><?=$tramite->fields['descripcion'] ? stripslashes($tramite->fields['descripcion']) : $descripcion ?></textarea></td>

		<script type="text/javascript">
			var googie2 = new GoogieSpell("../../fw/js/googiespell/", "sendReq.php?lang=");
			googie2.setLanguages({'es': 'Español', 'en': 'English'});
			googie2.dontUseCloseButtons();
			googie2.setSpellContainer("spell_container");
			googie2.decorateTextarea("descripcion");
		</script>

    </tr>
			<tr>
				<td align="right"><?=__('Cobrable')?></td>
				<td align="left">
				<input type="checkbox" name="cobrable" valor="1" <?=$tramite->fields['cobrable'] || $cobrable ? 'checked' : '' ?> /> &nbsp;&nbsp;
					
<?
	if(strlen($select_usuario) > 164) // Depende de que no cambie la función Html::SelectQuery(...)
	{
		echo(__('Usuario'));;
		echo($select_usuario);
	}
?>
        </td>
    </tr>
<?
    if(isset($tramite) && $tramite->Loaded() && $opcion != 'nuevo')
    {
			echo("<tr><td colspan=4 align=center>");
			echo("<a onclick=\"return confirm('".__('¿Desea eliminar este trámite?')."')\" href=?opcion=eliminar&id_tramite=".$tramite->fields['id_tramite']."&popup=$popup><span style=\"border: 1px solid black; background-color: #ff0000;color:#FFFFFF;\">&nbsp;Eliminar este trámite&nbsp;</span></a>");
			echo("</td></tr>");
    }
?>
    <tr>
        <td colspan='2' align='right'>
        	<? if ($id_tramite > 0)
        			{ ?>
					<input type=submit class=btn value=<?=__('Guardar')?> onclick="return Confirmar(this.form,'<?=$id_trabajo ?>')" />
				  <?  }
				  	 else
				  	 	{ ?>
				  <input type=submit class=btn value=<?=__('Guardar')?> onclick="return Validar(this.form)" />
				  	<?	} ?>
				</td>
    </tr>
</table>
</form>

<? 
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion));
	}
		echo(SelectorHoras::Javascript());
    echo(InputId::Javascript($sesion));
    $pagina->PrintBottom($popup);
    function SplitDuracion($time)
    {
        list($h,$m,$s) = split(":",$time);
        return $h.":".$m;
    }
    function Substring($string)
    {
        if(strlen($string) > 250)
            return substr($string, 0, 250)."...";
        else
            return $string;
    } 
?>
<script language="javascript" type="text/javascript">
<?
if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
	echo "CargaIdioma('".$codigo_asunto_secundario."');";
else
	echo "CargaIdioma('".$t->fields['codigo_asunto']."');";
?>
//datepicker Fecha
Calendar.setup(
	{
		inputField	: "fecha",				// ID of the input field
		ifFormat	: "%d-%m-%Y",			// the date format
<?
	if (!$permiso_cobranza->fields['permitido'])
	{
		echo "minDate			: \"".date('Y-m-d',mktime(0,0,0,date('m'),date('d')-$sesion->usuario->fields['dias_ingreso_trabajo'],date('Y')))."\",\n";
	}
?>
		button			: "img_fecha"	;	// ID of the button
	}
);
</script>
