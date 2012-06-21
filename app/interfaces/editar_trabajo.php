<?php
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
	require_once Conf::ServerDir().'/classes/Contrato.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	require_once Conf::ServerDir().'/classes/UsuarioExt.php';

	$sesion = new Sesion(array('PRO','REV','SEC'));
	$pagina = new Pagina($sesion);

	$t = new Trabajo($sesion);
	$params_array['codigo_permiso'] = 'REV';
	$permiso_revisor = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	$params_array['codigo_permiso'] = 'COB';
	$permiso_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	$params_array['codigo_permiso'] = 'PRO';
	$permiso_profesional = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
        $params_array['codigo_permiso'] = 'SEC';
        $permiso_secretaria = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

	$tipo_ingreso = UtilesApp::GetConf($sesion,'TipoIngresoHoras');
	$actualizar_trabajo_tarifa = true;

	if($id_trabajo > 0)
	{
		$actualizar_trabajo_tarifa = false;
		$t->Load($id_trabajo);
		
		if(($t->Estado() == 'Cobrado' || $t->Estado()== __("Cobrado"))&& $opcion != 'nuevo')
		{
		      
			$pagina->AddError(__('Trabajo ya cobrado'));
			$pagina->PrintTop($popup);
			$pagina->PrintBottom($popup);
			exit;
		} elseif(($t->Estado() == 'Revisado' || $t->Estado()== __("Revisado")) && $opcion != 'nuevo')
		{
			if(!$permiso_revisor->fields['permitido'])
			{
				$pagina->AddError(__('Trabajo ya revisado'));
				$pagina->PrintTop($popup);
				$pagina->PrintBottom($popup);
				exit;
			}
		} elseif($opcion=='cambiofecha') {
                    $semana=Utiles::fecha2sql($fecha);
                    $t->Edit('fecha',$semana);
                    $t->Write(true);
                    die('semana|'.$semana);
                    
                }
		if(!$id_usuario)
			$id_usuario = $t->fields['id_usuario'];

		/*
		hemos cambiado el cliente por lo tanto
		este trabajo tomará un cobro CREADO del asunto, sino NULL
		*/
		if(!$codigo_asunto_secundario)
		{
			//se carga el codigo secundario
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigo($t->fields['codigo_asunto']);
			$codigo_asunto_secundario=$asunto->fields['codigo_asunto_secundario'];
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($asunto->fields['codigo_cliente']);
			$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
			$codigo_cliente=$asunto->fields['codigo_cliente'];
		}
		else
		{
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
			$codigo_asunto = $asunto->fields['codigo_asunto'];
		}
		if($codigo_asunto != $t->fields['codigo_asunto'])#revisar para codigo secundario
		{
			$contrato_anterior = new Contrato($sesion);
			$contrato_modificado = new Contrato($sesion);
			
			$contrato_anterior->LoadByCodigoAsunto($t->fields['codigo_asunto']);
			$contrato_modificado->LoadByCodigoAsunto($codigo_asunto);
			
			if( $contrato_anterior->fields['id_tarifa'] != $contrato_modificado->fields['id_tarifa'] ) {
				$actualizar_trabajo_tarifa = true;
			}
			
			$cambio_asunto = true;
		}
	}
	else //Si no se está editando un trabajo
	{
		if(!$id_usuario)
			$id_usuario = $sesion->usuario->fields['id_usuario'];
		$es_trabajo_nuevo=1;
		if($opcion != "guardar")
		{
			$t->fields['cobrable']=1; //para que por defecto aparezcan los trabajos como cobrables
			$t->fields['visible']=0; //para que por defecto aparezcan los trabajos como no visibles cuando sean no cobrables
		}
	}

	/* OPCION -> Guardar else Eliminar */
	if($opcion == "guardar")
	{
                if( UtilesApp::GetConf($sesion,'TipoIngresoHoras') == 'decimal' ) {
                    if( round(10*number_format(str_replace(',','.',$duracion),6,'.','')) != 10*number_format(str_replace(',','.',$duracion),6,'.','') ) 
                        $pagina->AddError(__("Solo se permite ingresar un decimal en el campo ").' <b>'.__('Duración').'</b>');
                    if( round(10*number_format(str_replace(',','.',$duracion_cobrada),6,'.','')) != 10*number_format(str_replace(',','.',$duracion_cobrada),6,'.','') ) 
                        $pagina->AddError(__("Solo se permite ingresar un decimal en el campo ").' <b>'.__('Duración Cobrable').'</b>');
                }
				if($duracion == '00:00:00' )  {
                    $pagina->AddError("Las horas ingresadas deben ser mayor a 0.");
                }
				if(!$codigo_asunto || $codigo_asunto ==''){
					$pagina->AddError("Debe seleccionar un ".__('Asunto'));
				}
				if( UtilesApp::GetConf ($sesion, 'UsarAreaTrabajos') && (! $id_area_trabajo || $id_area_trabajo == '')){
						$pagina->AddError("Debe seleccionar una area de trabajo");
				}	
				if(!$descripcion || $descripcion == ''){
					$pagina->AddError("Debe Agregar una descripcion");
				}
				if(!$codigo_cliente || $codigo_cliente == ''){
					$pagina->AddError("Debe seleccionar un cliente");
				}
                $errores = $pagina->GetErrors();
            
                if( empty($errores) )
                {
                    if(Trabajo::CantHorasDia($duracion - $t->fields['duracion'],Utiles::fecha2sql($fecha),$id_usuario,$sesion))
                    {
                            $valida = true;
                            $asunto = new Asunto($sesion);
                            if (UtilesApp::GetConf($sesion,'CodigoSecundario'))
                            {
                                    $asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
                                    $codigo_asunto=$asunto->fields['codigo_asunto'];
                            }
                            else
                            {
                                    $asunto->LoadByCodigo($codigo_asunto);
                            }

                            /*
                            Ha cambiado el asunto del trabajo se setea nuevo Id_cobo de alguno que esté creado
                            y corresponda al nuevo asunto y esté entre las fechas que corresponda, sino, se setea NULL
                            */
                            if($cambio_asunto)
                            {
                                    $cobro = new Cobro($sesion);
                                    $id_cobro_cambio = $cobro->ObtieneCobroByCodigoAsunto($codigo_asunto, $t->fields['fecha']);
                                    if($id_cobro_cambio)
                                    {
                                            $t->Edit('id_cobro',$id_cobro_cambio);
                                    }
                                    else
                                            $t->Edit('id_cobro','NULL');
                            }

                            $t->Edit("duracion", $tipo_ingreso == 'decimal' ?
                                    UtilesApp::Decimal2Time($duracion) : $duracion);

                            if($duracion_cobrada == '') $duracion_cobrada = $duracion;

                            $t->Edit("duracion_cobrada", $tipo_ingreso == 'decimal' ?
                                    UtilesApp::Decimal2Time($duracion_cobrada) : $duracion_cobrada);

                            $query = "SELECT id_categoria_usuario FROM usuario WHERE id_usuario = '$id_usuario' ";
                            $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
                            list( $id_categoria_usuario ) = mysql_fetch_array($resp);

                            $t->Edit('id_usuario', $id_usuario);
							if( is_numeric($id_usuario) ) {
								$query = "UPDATE usuario SET retraso_max_notificado = 0 WHERE id_usuario = '$id_usuario'";
								mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							}
                            $t->Edit('id_categoria_usuario', !empty($id_categoria_usuario) ? $id_categoria_usuario : "NULL" );
                            $t->Edit('codigo_asunto', $codigo_asunto);

                            if( UtilesApp::GetConf($sesion, 'UsarAreaTrabajos')){
                                    //id_area_trabajo
                                    $t->Edit('id_area_trabajo', empty($id_area_trabajo) ? "NULL": $id_area_trabajo );
                            }

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
                                    $t->Edit('solicitante',addslashes($solicitante));

                            if( UtilesApp::GetConf($sesion,'TodoMayuscula') ) {
                                $t->Edit('descripcion',strtoupper($descripcion));
                            } else {
                            $t->Edit('descripcion',$descripcion);
                            }
                            $t->Edit('fecha',Utiles::fecha2sql($fecha));
                            #$t->Edit('fecha',$fecha);
                            if($codigo_actividad)
                                    $t->Edit('codigo_actividad',$codigo_actividad);
                            if($revisado)
                                    $t->Edit('revisado',1);

                            if(!$cobrable)
                            {
                                    $t->Edit("cobrable",'0');
                                    if(!$visible)
                                            $t->Edit("visible",'0');
                                    else
                                            $t->Edit('visible','1');
                            }
                            else
                            {
                                    $t->Edit('cobrable','1');
                                    $t->Edit('visible','1');
                            }
                            if($asunto->fields['cobrable']==0)//Si el asunto no es cobrable
                            {
                                    $t->Edit("cobrable",'0');
                                    $t->Edit("visible",'0');
                                    $pagina->AddInfo(__('El Trabajo se guardó como NO COBRABLE (Por Maestro).'));
                            }
                            if(!$id_usuario)
                                    $t->Edit("id_usuario",$sesion->usuario->fields['id_usuario']);
                            else
                                    $t->Edit("id_usuario",$id_usuario);

                            // Agregar valores de tarifa
                            $asunto = new Asunto($sesion);
                            $asunto->LoadByCodigo($t->fields['codigo_asunto']);
                            $contrato = new Contrato($sesion);
                            $contrato->Load($asunto->fields['id_contrato']);
                            if(!$t->fields['tarifa_hh'])
                                    $t->Edit('tarifa_hh', Funciones::Tarifa($sesion, $id_usuario, $contrato->fields['id_moneda'], $codigo_asunto));
                            if(!$t->fields['costo_hh'])
                                    $t->Edit('costo_hh', Funciones::TarifaDefecto($sesion, $id_usuario, $contrato->fields['id_moneda']));
							
							if($t->fields['cobrable']==0) $t->fields['duracion_cobrada']='00:00:00';
                            if($t->Write())
                            {
                                    if( $actualizar_trabajo_tarifa )
                                            $t->InsertarTrabajoTarifa();
                                    $pagina->AddInfo(__('Trabajo').' '.($nuevo?__('guardado con éxito'):__('editado con éxito')));
    #refresca el listado de horas.php cuando se graba la informacion desde el popup
    ?>
                                    <script>
                                            if(window.opener)
                                                    window.opener.Refrescar();
                                            /*{

                                                    //window.close();
                                            }*/
                                    </script>
    <?php
                            }
                    }
                    else
                    {
                            $pagina->AddError("No se pueden ingresar mas de 23:59 horas por día.");
                    }
                }

		unset($id_trab);
		if($es_trabajo_nuevo)//Significa que estoy agregando más que editando, así que debo dejar en limpio el formulario
		{
			unset($t);
			unset($codigo_asunto_secundario);
			unset($codigo_cliente_secundario);
			$t = new Trabajo($sesion);
			$t->fields['cobrable']=1; //para que por defecto aparezcan los trabajos como cobrables
			$t->fields['visible']=0; //para que por defecto aparezcan los trabajos como no visibles cuando sean no cobrables
		}

		/*
		Nuevo en el caso de ser llamado desde Resumen semana, para que haga
		refresh al form
		*/
		if($nuevo || $edit)
		{
?>
			<script>
				if(window.opener && window.opener.document.form_semana.submit() )
				{
					window.close();
				}
			</script>
<?php
		}
	}
	else if($opcion == "eliminar") #ELIMINAR TRABAJO
	{
		$t = new Trabajo($sesion);
		$t->Load($id_trabajo);
		if(! $t->Eliminar() )
			$pagina->AddError($t->error);
		else	
			{
?>
				<script>
					if(window.opener)
						window.opener.Refrescar();
				</script>
<?php
			}
		unset($t);
		unset($codigo_asunto_secundario);
		unset($codigo_cliente_secundario);
 		$t = new Trabajo($sesion);
 		$t->fields['cobrable']=1;	//para que por defecto aparezcan los trabajos como cobrables
		$t->fields['visible']=0;	//para que por defecto aparezcan los trabajos como no visibles cuando sean no cobrables
		$pagina->AddInfo(__('Trabajo').' '.__('eliminado con éxito'));
		#$up = 1;
	}
	else if( $opcion == "actualizar_trabajo_tarifa" )
	{ 
		// Actualizar tarifas en tabla trabajo_tarifa
		$valores = array();
		foreach($_POST as $index => $valor) {
			list( $key1, $key2, $id_moneda ) = split('_',$index);
			// echo $key1 . " - " . $key2 . " - " . $id_moneda . "<br /> ---- <br />";
			if( $key1 == 'trabajo' && $key2 == 'tarifa' && $id_moneda > 0 ) {
				if( empty($valor) ) $valor = "0";
				$t->ActualizarTrabajoTarifa($id_moneda,$valor);
				$valores[$id_moneda] = $valor;
			}
		}
		
		// Actualizar campo tarifa_hh de la tabla trabajo
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($t->fields['codigo_asunto']);
		$contrato = new Contrato($sesion);
		$contrato->Load($asunto->fields['id_contrato']);
		
		if( $valores[$contrato->fields['id_moneda']] > 0 ) {
			$t->Edit("tarifa_hh",$valores[$contrato->fields['id_moneda']]);
			$t->Write();
		}
		
?>
		<script>
			if(window.opener)
				window.opener.Refrescar();
		</script>
<?php
		
		$pagina->AddInfo(__('Tarifas').' '.__('guardado con éxito'));
	}

	/* Título opcion */
	if($opcion == '' && $id_trabajo > 0)
		$txt_opcion = __('Modificación de Trabajo');
	else if($id_trabajo == NULL) // si no tenemos id de trabajo es porque se está agregando uno nuevo.
		$txt_opcion = __('Agregando nuevo Trabajo');
	else if($opcion == '')
		$txt_opcion = '';

	$codigo_cliente = $t->get_codigo_cliente();
	if ( UtilesApp::GetConf($sesion,'CodigoSecundario') )
	{
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($codigo_cliente);
		$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
	}
	$pagina->titulo = __('Modificación de').' '.__('Trabajo');
	$pagina->PrintTop($popup);
	
	if(($opcion == 'guardar' || $opcion == 'eliminar') )
	{
?>
<script type="text/javascript">
var str_url = new String(top.location);
if(str_url.search('/trabajo.php') > 0) {//Si la página está siendo llamada desde trabajo.php
	if(top.frames.semana!==undefined)     top.frames.semana.location.reload();
}
if(top.Refrescar!==undefined) top.Refrescar();
	</script>
<?php 
	}
?>

<script type="text/javascript">

jQuery('document').ready(function() {
	jQuery('#chkCobrable').click(function() {
		if(jQuery(this).is(':checked')) {
			jQuery('#duracion_cobrada, #hora_duracion_cobrada, #minuto_duracion_cobrada').removeAttr('disabled');
			jQuery('#divVisible').hide();
			jQuery('.seccioncobrable').show();
		} else {
			jQuery('#duracion_cobrada, #hora_duracion_cobrada, #minuto_duracion_cobrada').attr('disabled','disabled');
			jQuery('#divVisible').show();
			jQuery('.seccioncobrable').hide();
		}
	});
	if(jQuery('#chkCobrable').is(':checked')) {
			jQuery('#duracion_cobrada, #hora_duracion_cobrada, #minuto_duracion_cobrada').removeAttr('disabled');
			jQuery('#divVisible').hide();
			jQuery('.seccioncobrable').show();
		} else {
			jQuery('#duracion_cobrada, #hora_duracion_cobrada, #minuto_duracion_cobrada').attr('disabled','disabled');
			jQuery('#divVisible').show();
			jQuery('.seccioncobrable').hide();
		}
}) ;

	
function MostrarTrabajoTarifas()
{
	$('TarifaTrabajo').show();
}

function CancelarTrabajoTarifas()
{
	$('TarifaTrabajo').hide();
}

function ActualizarTrabajosTarifas()
{
	$('opcion').value = "actualizar_trabajo_tarifa";
	$('form_editar_trabajo').submit();
}

function Confirmar(form)
{
			var r=confirm("Está modificando un trabajo, desea continuar?");
			if(r==true)
				{
				Validar(form);
				}
			else
				{
				return false;
				}
}

function Validar(form)
{
<?php
			if(UtilesApp::GetConf($sesion,'CodigoSecundario'))
			{
				echo "if(!form.codigo_asunto_secundario.value){";
			}
			else
			{
				echo "if(!form.codigo_asunto.value){";
			}
?>
			alert("<?php echo __('Debe seleccionar un').' '.__('asunto')?>");
<?php
			if (UtilesApp::GetConf($sesion,'CodigoSecundario'))
			{
				echo "form.codigo_asunto_secundario.focus();";
			}
			else
			{
				echo "form.codigo_asunto.focus();";

				}

			echo 'return false;';echo '}';
    
	?>
    if(!form.fecha.value)
    {
        alert("<?php echo __('Debe ingresar una fecha.')?>");
        form.fecha.focus();
        return false;
    }

    if(!form.duracion.value)
    {
        alert("<?php echo __('Debe establecer la duración')?>");
        form.duracion.focus();
        return false;
    }
	else
	{
		if( form.duracion.value == '00:00:00' ){
			alert("<?php echo __('La duración debe ser mayor a 0')?>");
<?php
	if( method_exists('Conf','GetConf') )
	{
		if(Conf::GetConf($sesion,'TipoIngresoHoras')=='selector'){
			echo "document.getElementById('hora_duracion').focus();";
		}
		else
		{
			echo "form.duracion.focus();";
		}
	}
?>
			return false;
		}
	}
 <?php
	//Revisa el Conf si esta permitido y la función existe
	if($tipo_ingreso=='decimal')
	{
?>
			var dur=form.duracion.value.replace(",",".");
			var dur_cob=form.duracion_cobrada.value.replace(",",".");
			if(isNaN(dur) || isNaN(dur_cob))
			{
				alert("<?php echo __('Solo se aceptan valores numéricos')?>");
				form.duracion.focus();
				return false;
			}
			var decimales=dur.split(".");
			var decimales_cobrada=dur_cob.split(".");
			if(decimales[1].length > 1 || decimales_cobrada[1].length > 1)
			{
				alert("<?php echo __('Solo se permite ingresar un decimal')?>");
				form.duracion.focus();
				return false;
			}
<?php
	}
?>
    if(!form.descripcion.value)
    {
        alert("<?php echo __('Debe ingresar la descripción')?>");
        form.descripcion.focus();
        return false;
    }

<?php
	if  ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarAreaTrabajos') ) {
?>
            if( !form.id_area_trabajo.value ) 
            {
                alert("<?php echo __('Debe seleccionar una area de trabajo')?>");
                form.id_area_trabajo.focus();
                return false;
            }
<?php
        }
?>

	//Valida si el asunto ha cambiado para este trabajo que es parte de un cobro, si ha cambiado se emite un mensaje indicandole lo ki pa
	if(form.id_cobro.value != '' && $('id_trabajo').value != '')
	{
	<?php
		if( UtilesApp::GetConf($sesion,'CodigoSecundario') ) 
			{ ?>
				if(ActualizaCobro(form.codigo_asunto_secundario.value))
					return true;
				else
					return false;
	<?php 	} 
		else 
			{ ?>
				if(ActualizaCobro(form.codigo_asunto.value))
					return true;
				else
					return false;
	<?php } ?>
	}
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

	if($Ordenado_por==1)
	{
?>
	if(form.solicitante.value=='')
	{
		alert("<?php echo __('Debe ingresar la persona que solicitó el trabajo')?>");
		form.solicitante.focus();
		return false;
	}
<?php
	}
	//Se pasa todo a mayúscula por conf
	if( UtilesApp::GetConf($sesion,'TodoMayuscula') )
	{
		echo "form.descripcion.value=form.descripcion.value.toUpperCase();";
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
			echo "form.solicitante.value=form.solicitante.value.toUpperCase();";
	}
	
	// Si el usuario no tiene permiso de cobranza validamos la fecha del trabajo
	if (!$permiso_cobranza->fields['permitido'] && $sesion->usuario->fields['dias_ingreso_trabajo'] > 0)
	{
?>
	temp = $('fecha').value.split("-");
	fecha = new Date(temp[2]+'//'+temp[1]+'//'+temp[0]);
	hoy = new Date();
	fecha_tope = new Date(hoy.getTime()-(<?php echo ($sesion->usuario->fields['dias_ingreso_trabajo']+1) ?>*24*60*60*1000));
	if (fecha_tope > fecha)
	{
		var dia = fecha_tope.getDate();
		var mes = fecha_tope.getMonth()+1;
		var anio = fecha_tope.getFullYear();
		alert("No se pueden ingresar trabajos anteriores a "+ dia + "-" + mes + "-" + anio);
		$('fecha').focus;
		return false;
	}
<?php
	}
	//Si esta editando desde la página de ingreso de trabajo le pide confirmación para realizar los cambios
	if(isset($t) && $t->Loaded() && $opcion != 'nuevo')
	{
?>
	var string = new String(top.location);
	if(string.search('/trabajo.php') > 0)//revisa que esté en la página de ingreso de trabajo
		if(!confirm('Está modificando un trabajo, desea continuar?'))
			return false;
<?php
	}
?>

	return true;
}

function MontoValido( id_campo )
{
	var monto = document.getElementById( id_campo ).value.replace('\,','.');
	var arr_monto = monto.split('\.');
	var monto = arr_monto[0];
	for($i=1;$i<arr_monto.length-1;$i++)
		monto += arr_monto[$i];
	if( arr_monto.length > 1 )
		monto += '.' + arr_monto[arr_monto.length-1];
	
	document.getElementById( id_campo ).value = monto;
}

function CargarTarifa()
{
	if( !$('tarifa_trabajo') )
		return true;
		
		var id_usuario = $('id_usuario').value;
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
	var vurl = 'ajax.php?accion=cargar_tarifa_trabajo&id_usuario='+id_usuario+'&codigo_asunto='+codigo_asunto+'&codigo_cliente='+codigo_cliente;
	
	cargando = true;
	http.open('get', vurl, true);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			if( $('tarifa_trabajo') )
				$('tarifa_trabajo').value = response;
			else 
				return false;
		}
		cargando = false;
	};
	http.send(null);
	return true;
}

function IngresarNuevo(form)
{
	form.opcion.value = 'nuevo';
	form.id_trabajo.value = '';
	var url="semana.php?popup=1&semana="+form.semana.value+"&id_usuario="+<?php echo $id_usuario?>+"&opcion=nuevo";
	self.location.href = url;
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
		img.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_asunto\',\'inline\',\'img_asunto\');">';
	}
	else
	{
		var img = document.getElementById( 'img_historial' );
		img.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" title="Mostrar" class="mano_on" onClick="ShowDiv(\'tr_cliente\',\'inline\',\'img_historial\');">';
	}
}

function ShowDiv(div, valor, dvimg)
{
	var div_id = document.getElementById(div);
	var img = document.getElementById(dvimg);
	var form = document.getElementById('form_editar_trabajo');
	<?php if (UtilesApp::GetConf($sesion, "TipoSelectCliente") == "autocompletador") { ?>
	var codigo = document.getElementById('codigo_cliente');
	<?php } else { ?>
	var codigo = document.getElementById('campo_codigo_cliente');
	<?php } ?>
	var tr = document.getElementById('tr_cliente');
	var tr2 = document.getElementById('tr_asunto');
	var al = document.getElementById('al');
	//var tbl_trabajo = document.getElementById('tbl_trabajo');

	DivClear(div, dvimg);

	codigo = (codigo == null) ? "" : codigo.value;

	if( div == 'tr_asunto' && codigo == '')
	{
		tr.style['display'] = 'none';
		alert("<?php echo __('Debe seleccionar un cliente')?>");
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
		img.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/menos.gif" border="0" title="Ocultar" class="mano_on" onClick="ShowDiv(\''+div+'\',\'none\',\''+dvimg+'\');">';
	}
	else
	{
		WCH.Discard(div);
		img.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\''+div+'\',\'inline\',\''+dvimg+'\');">';
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
		<?php if (UtilesApp::GetConf($sesion, "TipoSelectCliente") == "autocompletador") { ?>
		//form.codigo_cliente.value = codigo;
		SetSelectInputId('codigo_cliente','glosa_cliente');
		<?php } else { ?>
		form.campo_codigo_cliente.value = codigo;
		SetSelectInputId('campo_codigo_cliente','codigo_cliente');	
		<?php } ?>
<?
		if( UtilesApp::GetConf($sesion,'CodigoSecundario') )
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
<?php if( UtilesApp::GetConf($sesion,'UsoActividades') ) echo "CargarSelect('codigo_asunto','codigo_actividad','cargar_actividades');";?>

	}

	var http = getXMLHTTP();

	if(div == 'content_data')
	{
		var right_data = document.getElementById('right_data');
		right_data.innerHTML = '';
	}

	var vurl = 'ajax_historial.php?accion='+accion+'&codigo='+codigo+'&div_post='+div_post+'&div='+div;
    http.open('get', vurl, false);
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

function UpdateTrabajo(id_trabajo, descripcion, codigo_actividad, duracion, duracion_cobrada, cobrable, visible, fecha)
{
	var form = document.getElementById('form_editar_trabajo');
	form.campo_codigo_actividad.value = codigo_actividad;
	SetSelectInputId('campo_codigo_actividad','codigo_actividad');

	form.duracion.value = duracion;
	if( document.getElementById('duracion_cobrada') )
		form.duracion_cobrada.value = duracion_cobrada;
	form.cobrable.checked = cobrable > 0 ? true : false;
	form.visible.checked = visible > 0 ? true : false;
	form.descripcion.value = descripcion;

	/*
	var fecha_arr = fecha.split('-',3);
	
	var m = document.getElementById('fecha_Month_ID');
	var d = document.getElementById('fecha_Day_ID');
	var a = document.getElementById('fecha_Year_ID');
	for( i=0; i<m.options.length; i++ )
	{
		if( parseInt(m.options[i].value) == parseInt(fecha_arr[1]-1) )
		{
			m.options[i].selected = true;
			fecha_Object.changeMonth(m);
		}
	}
	for(i=0; i<d.options.length; i++)
	{
		if( parseInt(d.options[i].text) == fecha_arr[2] )
		{
			d.options[i].selected = true;
			fecha_Object.changeDay(d);
		}
	}
	if(fecha_arr[0])
	{
		a.value = fecha_arr[0];
		fecha_Object.fixYear(a);
		fecha_Object.checkYear(a);
	}*/

	form.fecha.value = fecha;
	var tr = document.getElementById('tr_cliente');
	var tr2 = document.getElementById('tr_asunto');
	var img = document.getElementById('img_historial');
	var img2 = document.getElementById('img_asunto');

	WCH.Discard('tr_asunto');
	WCH.Discard('tr_cliente');
	tr.style['display'] = 'none';
	tr2.style['display'] = 'none';

	img.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'tr_cliente\',\'inline\',\'img_historial\');">';

	img2.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'tr_asunto\',\'inline\',\'img_asunto\');">';
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
<?
				}
?>
				if(idio[1]=='Español')
					googie2.setCurrentLanguage('es');
				if(idio[1]=='Inglés')
					googie2.setCurrentLanguage('en');
				
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
		var text_window = "<img src='<?php echo Conf::ImgDir()?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA")?></u><br><br>";
		text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __('Ud. está modificando un trabajo que pertenece al cobro')?>:'+id_cobro+' ';
		text_window += '<?php echo __('. Si acepta, el trabajo se desvinculará de ') . __('este cobro') . __(' y eventualmente se vinculará a ') . __('un cobro') . __(' pendiente para el nuevo '. __('asunto') .'en caso de que exista')?>.</span><br>';
		text_window += '<br><table><tr>';
		text_window += '</table>';
		Dialog.confirm(text_window,
		{
			top:100, left:80, width:400, okLabel: "<?php echo __('Aceptar')?>", cancelLabel: "<?php echo __('Cancelar')?>", buttonClass: "btn", className: "alphacube",
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

//Cuando se le saca el check de cobrable se hace visible = 0
function CheckVisible()
{
	if(!$('chkCobrable').checked)
	{
		<?php if($permiso_revisor->fields['permitido'] || UtilesApp::GetConf($sesion,'AbogadoVeDuracionCobrable')) { ?>
			$('chkVisible').checked=false;
		<?php
			}
			else
			{
		?>
			$('hiddenVisible').value=0;
		<?}?>
	}
}

function AgregarNuevo(tipo)
{
<?php if(UtilesApp::GetConf($sesion,'CodigoSecundario')){?>
	var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
	var codigo_asunto_secundario = $('codigo_asunto_secundario').value;
<?php } else { ?>
	var codigo_cliente = $('codigo_cliente').value;
	var codigo_asunto = $('codigo_asunto').value;
<?php } ?>
	if(tipo == 'trabajo')
	{
		var urlo = "editar_trabajo.php?popup=1";
		window.location=urlo;
	}
}

</script>
<style>
A:link,A:visited {font-size:9px;text-decoration: none}
A:hover {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
A:active {font-size:9px;text-decoration:none; color:#990000; background-color:#D9F5D3}
</style>
<?php echo(Autocompletador::CSS()); ?>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<form id="form_editar_trabajo" name="form_editar_trabajo" method="post" action="<?php echo $_SERVER[PHP_SELF]?>">
    
<input type="hidden" id="opcion" name="opcion" value="guardar" />
<input type="hidden" name="gIsMouseDown" id="gIsMouseDown" value=false />
<input type="hidden" name="gRepeatTimeInMS" id="gRepeatTimeInMS" value=200 />
<input type="hidden" name="max_hora" id="max_hora" value=<?php echo UtilesApp::GetConf($sesion,'MaxDuracionTrabajo')?> />
<input type="hidden" name='codigo_asunto_hide' id='codigo_asunto_hide' value="<?php echo $t->fields['codigo_asunto']?>" />
<?
	if( $opcion != 'nuevo' )
	{
?>
<input type="hidden" name='id_trabajo' value="<?php echo  $t->fields['id_trabajo'] ?>" id='id_trabajo' />
<input type="hidden" name='edit' value="<?php echo  $opcion == 'edit' ? 1 : '' ?>" id='edit' />
<input type="hidden" name='fecha_trabajo_hide' value="<?php echo  $t->fields['fecha'] ?>" id='fecha_trabajo_hide' />
<?
	}
	if($id_trabajo == NULL) // si no tenemos id de trabajo es porque se estÃ¡ agregando uno nuevo.
	{
?>
<input type="hidden" name='nuevo' value="1" id='nuevo' />
<?
	}
?>
<input type="hidden" name=id_cobro id=id_cobro value="<?php echo $t->fields['id_cobro'] !='NULL' ? $t->fields['id_cobro'] : '' ?>" />
<input type="hidden" name=popup value='<?php echo $popup?>' id="popup">

<!-- TABLA HISTORIAL -->
<?php  if( UtilesApp::GetConf($sesion,'UsaDisenoNuevo') )
	          $display_none = 'style="display: none;"';
		else
		  $display_none = ''; ?>
			
<table id="tr_cliente" cellpadding="0" cellspacing="0" width="100%" <?php echo $display_none?>>
	<tr>
        <td colspan="7" class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
    	<td class="td_transparente">&nbsp;</td>
        <td class="td_transparente" colspan="5" align="right">
        	<img style="filter:alpha(opacity=100);" src="<?php echo Conf::ImgDir()?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_cliente','none','img_historial');">
        </td>
        <td class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td width="5%" class="td_transparente">&nbsp;</td>
        <td width="30%" id="leftcolumn" class="box_historial">
            <div id="titulos">
                <?php echo __('Cliente') ?>
            </div>
            <div id="left_data" class="span_data"></div>
        </td>
        <td class="td_transparente">
        </td>
        <td width="30%" id="content" class="box_historial">
            <div id="titulos">
                <?php echo __('Asunto') ?>
            </div>
            <div id="content_data" class="span_data"></div>
        </td>
        <td class="td_transparente">
        </td>
        <td width="30%" id="rightcolumn" class="box_historial">
            <div id="titulos">
                <?php echo __('Trabajo') ?>
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
<table id="tr_asunto" cellpadding="0" cellspacing="0" width="100%" <?php echo $display_none?>>
	<tr>
        <td colspan="6" class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td class="td_transparente">&nbsp;</td>
        <td align="right" colspan="4" class="td_transparente">
            <img src="<?php echo Conf::ImgDir()?>/cruz_roja_13.gif" border="0" class="mano_on" alt="Ocultar" onClick="ShowDiv('tr_asunto','none','img_asunto');">
        </td>
        <td class="td_transparente">&nbsp;</td>
    </tr>
    <tr>
        <td width="5%" class="td_transparente">&nbsp;</td>
        <td width="45%" id="content" class="box_historial">
            <div id="titulos">
                <?php echo __('Asunto') ?>
            </div>
            <div id="content_data2" class="span_data"></div>
        </td>
        <td class="td_transparente">
        </td>
        <td width="45%" id="rightcolumn" class="box_historial">
            <div id="titulos">
                <?php echo __('Trabajo') ?>
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

<table style='border:0px solid black' <?php echo $txt_opcion ? 'style=display:inline' : 'style=display:none'?> width='90%'>
	<tr>
		<td align=left><span style=font-weight:bold; font-size:11px; backgroundcolor:#c6dead><?php echo $txt_opcion?></span></td>
	</td>
	<?php if($id_trabajo > 0) { ?>
		<td width='40%' align=right>
			<img src="<?php echo Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('trabajo')" title="Ingresar Trabajo"><u>Ingresar nuevo Trabajo</u></a>
		</td>
	<?php } ?>
	</tr>
</table>
<br>

<center>
<table class="border_plomo" id="tbl_trabajo">
    <tr>
        <td align=center>
        	<span <?php echo UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ? 'style="display:none"' : ''?> id="img_historial" onMouseover="ddrivetip('Historial de trabajos ingresados')" onMouseout="hideddrivetip()"><img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" class="mano_on" id="img_historial" onClick="ShowDiv('tr_cliente','inline','img_historial');"></span>&nbsp;&nbsp;&nbsp;&nbsp;
        </td>
        <td align=right>
			<?php echo __('Cliente')?>
        </td>
        <td align=left width="440" nowrap>
<?
	if( UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador' )
	{
		if( UtilesApp::GetConf($sesion,'CodigoSecundario') )
			echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario, true, '', "CargarTarifa();");
		else
			echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente,'', true, '', "CargarTarifa();");
	}
	else
	{
		if( UtilesApp::GetConf($sesion,'CodigoSecundario') )
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario, "","CargarTarifa();CargarSelect('campo_codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320,$codigo_asunto_secundario);
		else
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarTarifa();CargarSelect('campo_codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320,$codigo_asunto);
	}
?>
        </td>
     </tr>
     <tr>
        <td align='center'>
        	<span <?php echo UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ? 'style="display:none"' : ''?> id="img_asunto"><img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" id="img_asunto" class="mano_on" onMouseover="ddrivetip('Historial de trabajos ingresados')" onMouseout="hideddrivetip()" onClick="ShowDiv('tr_asunto','inline','img_asunto');"></span>&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
        <td align='right'>
             <?php echo __('Asunto')?>
        </td>
        <td align=left width="440" nowrap>
<?

					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarTarifa();CargaIdioma(this.value);CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $t->fields['codigo_asunto'],"","CargarTarifa();CargaIdioma(this.value);CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}

?>
       </td>
    </tr>
<?php
	if  ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarAreaTrabajos') ) {
?>
	 <tr>
        <td align='center'>
        	<span id="img_asunto">&nbsp;&nbsp;&nbsp;</span>
		</td>
        <td align='right'>
             <?php echo __('Área Trabajo')?>
        </td>
        <td align=left width="440" nowrap>
<?php
					//$sesion, $query, $name, $selected='', $opciones='',$titulo='',$width='150'
					echo Html::SelectQuery($sesion,"SELECT * FROM prm_area_trabajo ORDER BY id_area_trabajo ASC",'id_area_trabajo', $t->fields['id_area_trabajo'],'', 'Elegir', '400' );
?>
       </td>
    </tr>
<?php
	}
?>	
    <?php if( UtilesApp::GetConf($sesion,'UsoActividades') ){ ?>
    <tr>
        <td colspan="2" align=right>
            <?php echo __('Actividad')?>
        </td>
        <td align=left width="440" nowrap>
            <?php echo  InputId::Imprimir($sesion,"actividad","codigo_actividad","glosa_actividad", "codigo_actividad", $t->fields[codigo_actividad]) ?>
        </td>
    </tr>
    <?php }else{ ?>
    <input type="hidden" name="codigo_actividad" id="codigo_actividad">
    <input type="hidden" name="campo_codigo_actividad" id="campo_codigo_actividad">
    <?php }
    if($fecha == '')
    {
			$date = new DateTime();
			$fecha=date('d-m-Y',mktime(0,0,0,$date->format('m'),$date->format('d'),$date->format('Y')));
    }
    ?>
    <tr>
        <td colspan="2" align=right>
            <?php echo __('Fecha')?>
        </td>
        <td align=left valign="top">
            <!--<?php echo  Html::PrintCalendar("fecha", $t->fields[fecha] ? $t->fields[fecha] : $fecha); ?>-->
            <input type="text" name="fecha" value="<?php echo $t->fields['fecha'] ? Utiles::sql2date($t->fields['fecha']) : $fecha ?>" id="fecha" size="11" maxlength="10"/>
		        <img src="<?php echo Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
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
						<?php echo __('Ordenado por')?>
						&nbsp;
						<input type="text" name="solicitante" value="<?php echo $t->fields['solicitante'] ? $t->fields['solicitante'] : ''?>" id="solicitante" size="32" />
<?
						}
?>
        </td>
    </tr>
    <tr>
        <td colspan="2" align=right>
            <?php echo __('Duración')?>
        </td>
        <td align=left>
    <?
    $duracion = '';
    $duracion_cobrada = '';
    ?>
			<table>
			  <tr>
				<td>
<?
	$duracion_editable = $nuevo || $sesion->usuario->fields['id_usuario']==$id_usuario;
	if(!$duracion_editable){
		$usuario = new UsuarioExt($sesion);
		$duracion_editable = $usuario->LoadSecretario($id_usuario, $sesion->usuario->fields['id_usuario']);
	}

	if( $tipo_ingreso=='selector' )
	{ 
		if(!$duracion) $duracion = '00:00:00';
		echo SelectorHoras::PrintTimeSelector($sesion,"duracion", $t->fields['duracion'] ? $t->fields['duracion'] : $duracion, Conf::GetConf($sesion,'MaxDuracionTrabajo'), '', $duracion_editable );
	}
	else if( $tipo_ingreso=='decimal' )
	{
?>
		<input type="text" name="duracion" value="<?php echo $t->fields['duracion'] ? UtilesApp::Time2Decimal($t->fields['duracion']) : $duracion ?>" id="duracion" size="6" maxlength=4 <?php echo  !$duracion_editable ? 'readonly' : '' ?> onchange="CambiaDuracion(this.form,'duracion');"/>
<?
	}
	else if( $tipo_ingreso=='java')
	{
		echo Html::PrintTime("duracion",$t->fields[duracion],"onchange='CambiaDuracion(this.form ,\"duracion\");'", $duracion_editable);
	}
	else
	{
		echo Html::PrintTime("duracion",$t->fields[duracion],"onchange='CambiaDuracion(this.form ,\"duracion\");'", $duracion_editable);
	}

echo '</td>';


	if($permiso_revisor->fields['permitido'])
		$where = " usuario_permiso.codigo_permiso='PRO' AND ( ";
	else {
		$where = " usuario_permiso.codigo_permiso='PRO' 
							AND ( usuario_secretario.id_secretario = '".$sesion->usuario->fields['id_usuario']."'
									OR usuario.id_usuario IN ('$id_usuario','{$sesion->usuario->fields['id_usuario']}') 
									OR usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=".$sesion->usuario->fields['id_usuario'].") ) AND ( ";
	}
	$where .= " usuario.visible=1 OR usuario.id_usuario = '$id_usuario' ) ";
	
	$query = "SELECT SQL_CALC_FOUND_ROWS usuario.id_usuario, 
							CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
							as nombre 
					FROM usuario 
					JOIN usuario_permiso USING(id_usuario)
					LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional
					WHERE $where GROUP BY id_usuario ORDER BY nombre";
	
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($cantidad_usuarios) = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS();",$sesion->dbh));
	$select_usuario = Html::SelectResultado($sesion,$resp,"id_usuario", $id_usuario ,'onchange="CargarTarifa();" id="id_usuario"','','width="200"');

	if($permiso_revisor->fields['permitido'] || UtilesApp::GetConf($sesion,'AbogadoVeDuracionCobrable'))
	{

		echo '<td class="seccioncobrable">&nbsp;&nbsp;'. __('Duración Cobrable') .'</td><td  class="seccioncobrable">';

		if($tipo_ingreso=='selector')
		{
			$duracion_cobrada = '00:00:00';
			echo SelectorHoras::PrintTimeSelector($sesion,"duracion_cobrada", $t->fields['duracion_cobrada'] ? $t->fields['duracion_cobrada'] : $duracion_cobrada, Conf::GetConf($sesion,'MaxDuracionTrabajo'));
		}
		else if($tipo_ingreso=='decimal')
		{
?>
			<input type="text" name="duracion_cobrada" value="<?php echo $t->fields['duracion_cobrada'] ? UtilesApp::Time2Decimal($t->fields['duracion_cobrada']) : $duracion_cobrada ?>" id="duracion_cobrada" size="6" maxlength=4 />
<?
		}
		else if($tipo_ingreso=='java')
		{
			echo Html::PrintTime("duracion_cobrada",$t->fields['duracion_cobrada']);
		}
		else
		{
			echo Html::PrintTime("duracion_cobrada",$t->fields['duracion_cobrada']);
		}
?>
		</td>
<?
	}
?>
			</tr>
		</table>
        </td>
    </tr>
    <tr>
        <td colspan="2" align=right>
        	<?php
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
				<?php echo __('Descripción')?><br/><span id=txt_span style="background-color: #C6FAAD; font-size:18px"></span>
<?
		}
		else
		{
?>
			<?php echo __('Descripción')?><br/><span id=txt_span style="background-color: #C6FAAD; font-size:9px"></span>
<?
		}
?>
        </td>
        <td align=left>
            <textarea id="descripcion" cols=45 rows=4 name=descripcion><?php echo  stripslashes($t->fields[descripcion]) ?></textarea></td>

		<script type="text/javascript">
			var googie2 = new GoogieSpell("../../fw/js/googiespell/", "sendReq.php?lang=");
			googie2.setLanguages({'es': 'Español', 'en': 'English'});
			googie2.dontUseCloseButtons();
			googie2.setSpellContainer("spell_container");
			googie2.decorateTextarea("descripcion");
		</script>

    </tr>
			<tr>
				<?php
					$mostrar_cobrable=true;
					if(!UtilesApp::GetConf($sesion,'PermitirCampoCobrableAProfesional') && $permiso_profesional->fields['permitido'] && !$permiso_revisor->fields['permitido'] && !UtilesApp::GetConf($sesion,'AbogadoVeDuracionCobrable')) {
						$mostrar_cobrable=false;
					}
				?>
				<td colspan="2" align=right>
					<?php	if($mostrar_cobrable) { ?>
					<?php echo __('Cobrable')?><br/>
					<?php } ?>
				</td>
				<td align=left>
					<?php  if($mostrar_cobrable) { 		 ?>
					
					<input type="checkbox" name="cobrable" <?php echo  ($t->fields['cobrable'] == 1 ? " checked='checked'  value='1'" : ""); ?> id="chkCobrable" onClick="CheckVisible();">
					<?php } 	else { ?>
					<input type="hidden" name="cobrable" id="chkCobrable" value='1' >
					<?php } ?>
					&nbsp;&nbsp;
					<div id="divVisible" style="display:inline-block;">
					<?php if($permiso_revisor->fields['permitido'] || UtilesApp::GetConf($sesion,'AbogadoVeDuracionCobrable')) { 
						echo __('Visible');
						echo "<input type=\"checkbox\" name=\"visible\" value=\"1\" checked=". (($t->fields['visible'] == 1)? '"checked"' : '""') ." id=\"chkVisible\" onMouseover=\"ddrivetip('Trabajo será visible en la ". __('Nota de Cobro')."')\" onMouseout=\"hideddrivetip()\"/>";
					 } else {
					
						echo "<input type=\"hidden\" name=\"visible\" value=\"". (($t->fields['visible']) ? $t->fields['visible'] : 1) ."\" id=\"hiddenVisible\" />";
					 }
					?>
					</div>
					&nbsp;&nbsp;&nbsp;&nbsp;
<?php 
	if( $cantidad_usuarios > 1 || $permiso_secretaria->fields['permitido'] ) // Depende de que no cambie la función Html::SelectQuery(...)
	{
		echo(__('Usuario'));
		echo($select_usuario);
	}
	else 
		echo("<input type='hidden' id='id_usuario' name='id_usuario' value='".$sesion->usuario->fields['id_usuario']."' />");
?>
			</td>
		</tr>
<?
		if( UtilesApp::GetConf($sesion,'GuardarTarifaAlIngresoDeHora') && $permiso_revisor->fields['permitido'] ) {
			if( $t->fields['id_trabajo'] > 0 ) {
				if( $t->fields['id_cobro'] > 0 ) {
					$cobro = new Cobro($sesion);
					$cobro->Load( $t->fields['id_cobro'] );
					$id_moneda_trabajo = $cobro->fields['id_moneda'];
				}
				else {
					$contrato = new Contrato($sesion);
					$contrato->LoadByCodigoAsunto( $t->fields['codigo_asunto'] );
					$id_moneda_trabajo = $contrato->fields['id_moneda'];
				}
				$tarifa_trabajo = Moneda::GetSimboloMoneda( $sesion, $id_moneda_trabajo );
				$tarifa_trabajo .= " ".$t->GetTrabajoTarifa($id_moneda_trabajo);
			}
?>
		<tr>
			<td colspan="2" align="right">
				<?php echo __('Tarifa por hora')?>
			</td>
			<td align="left">
				<input type="text" size="10" id="tarifa_trabajo" disabled style="background-color: white; display: inline; border: 0px; color:black; vertical-align:middle;" value="<?php echo $tarifa_trabajo != '' ? $tarifa_trabajo : ''?>" />
				&nbsp;&nbsp;&nbsp;
				<?php if( $t->fields['id_trabajo'] > 0 ) { ?>
				<img src="<?php echo Conf::ImgDir()?>/money_16.gif" border=0 /><a href='javascript:void(0)' onclick="MostrarTrabajoTarifas()"><?php echo __('Modificar tarifa del trabajo')?></a>
				<?php } ?>
			</td>
		</tr>
<?php if( $t->fields['id_trabajo'] > 0 ) { ?>
		<tr>
			<td>
				<input type="hidden" id="id_moneda_trabajo" value="<?php echo $id_moneda_trabajo ?>" />
				<div id="TarifaTrabajo" style="display:none; left: 50px; top: 250px; background-color: white; position:absolute; z-index: 4;">
				<fieldset style="background-color:white;">
				<legend><?php echo __('Tarifas por hora')?></legend>
				<div id="contenedor_tipo_load">&nbsp;</div>
				<div id="contenedor_tipo_cambio">
				<table style='border-collapse:collapse;' cellpadding='3'>
					<tr>
						<?
						$query = "SELECT 
													prm_moneda.id_moneda, 
													glosa_moneda, 
													( SELECT valor FROM trabajo_tarifa WHERE id_trabajo = '".$t->fields['id_trabajo']."' 
																					AND trabajo_tarifa.id_moneda = prm_moneda.id_moneda ) 
												FROM prm_moneda";
						$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						$num_monedas=0; 
						while(list($id_moneda,$glosa_moneda,$valor) = mysql_fetch_array($resp))
						{
						?>
							<td>
									<span><b><?php echo $glosa_moneda?></b></span><br>
									<input type='text' size=9 id='trabajo_tarifa_<?php echo $id_moneda?>' name='trabajo_tarifa_<?php echo $id_moneda?>' onkeyup="MontoValido(this.id);" value='<?php echo $valor?>' />
							</td>
						<?
							$num_monedas++;
						}
						?>
					<tr>
						<td colspan=<?php echo $num_monedas?> align=center>
							<input type=button onclick="ActualizarTrabajosTarifas();" value="<?php echo __('Guardar')?>" />
							<input type=button onclick="CancelarTrabajoTarifas();" value="<?php echo __('Cancelar')?>" />
						</td>
					</tr>
			</table>
			</div>
			</fieldset>
			
			</div>
			</td>
		</tr> 
<?php 
		}
	}
		if(isset($t) && $t->Loaded() && $opcion != 'nuevo') 
		{
			echo("<tr><td colspan=5 align=center>"); 
			echo("<a onclick=\"return confirm('".__('¿Desea eliminar este trabajo?')."')\" href=?opcion=eliminar&id_trabajo=".$t->fields['id_trabajo']."&popup=$popup><span style=\"border: 1px solid black; background-color: #ff0000;color:#FFFFFF;\">&nbsp;Eliminar este trabajo&nbsp;</span></a>"); 
			echo("</td></tr>");                       
		}
?>
		<tr>
		<td colspan='3' align='right'>
					<?php if ($id_tabajo > 0)
							{ ?>
					<input type=submit class=btn value=<?php echo __('Guardar')?> onclick="return Confirmar(this.form)" />
					<?php  }
						else
							{ ?>
					<input type=submit class=btn value=<?php echo __('Guardar')?> onclick="return Validar(this.form)" />
						<?	} ?>
				</td>
		</tr>
</table>
</center>
</form>

<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
		{
			echo(Autocompletador::Javascript($sesion));
		}
		echo(InputId::Javascript($sesion));
    echo(SelectorHoras::Javascript());
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

var formObj = $('form_editar_trabajo');
<?
if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
{
	echo "CargaIdioma('".$codigo_asunto_secundario."');";
}
else
{
	echo "CargaIdioma('".$t->fields['codigo_asunto']."');";
}
?>
//datepicker Fecha
Calendar.setup(
	{
		inputField	: "fecha",				// ID of the input field
		ifFormat	: "%d-%m-%Y",			// the date format
<?php
	if (!$permiso_cobranza->fields['permitido'] && $sesion->usuario->fields['dias_ingreso_trabajo'] > 0)
	{
		echo "minDate			: \"".date('Y-m-d',mktime(0,0,0,date('m'),date('d')-$sesion->usuario->fields['dias_ingreso_trabajo'],date('Y')))."\",\n";
	}
?>
		button			: "img_fecha"		// ID of the button
	}
);

<?php if(empty($id_trabajo) &&
	(( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'LimpiarTrabajo') ) ||
	( method_exists('Conf','LimpiarTrabajo') && Conf::LimpiarTrabajo() )) ) { ?>

	$$('#codigo_asunto_hide, #id_cobro, #campo_codigo_cliente, #codigo_cliente, #campo_codigo_cliente_secundario, #codigo_cliente_secundario, #campo_codigo_asunto_secundario, #codigo_asunto_secundario, #codigo_actividad, #campo_codigo_actividad, #descripcion, #solicitante').each(function(elem){ elem.value = ''; });

<?php 
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{ ?>
		$$('#glosa_cliente').each(function(elem){ elem.value = ''; });
	<?php
	}
} ?>

<?php
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PrellenarTrabajoConActividad') ) || ( method_exists('Conf','PrellenarTrabajoConActividad') && Conf::PrellenarTrabajoConActividad() ) )
	{
?>
	$('codigo_actividad').observe('change', function(evento){
		actividad_seleccionada = this.options[this.selectedIndex];
		if(actividad_seleccionada.value != '')
		{
			descripcion_textarea = document.getElementById('descripcion');
			descripcion_textarea.value = actividad_seleccionada.text + '\n' + descripcion_textarea.value;
		}
	});
	
<?php
	}
?>
//	jQuery('#chkCobrable').click();
</script>
