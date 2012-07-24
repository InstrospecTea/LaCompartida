<?php 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';


	$sesion = new Sesion(array('PRO','REV','ADM','COB','SEC'));
	$pagina = new Pagina($sesion);

	$params_array['codigo_permiso'] = 'REV';
	$p_revisor = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

	$params_array['codigo_permiso'] = 'COB';
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	if ($p_cobranza->fields['permitido']) {
		$p_revisor->fields['permitido'] = true;
	}

	$params_array['codigo_permiso'] = 'PRO';
	$p_profesional = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

	if ($p_revisor->fields['permitido'] && $accion == "eliminar") {
		$tramite = new Tramite($sesion);
		$tramite->Load($id_tramite);
		if ($tramite->Estado() == "Abierto") {
			if(!$tramite->Eliminar()) {
				$pagina->AddError($asunto->error);
			} else {
				$pagina->AddInfo(__('Trámite').' '.__('eliminado con éxito'));
			}
		} else {
			$pagina->AddInfo(__('No se puede eliminar este trámite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')); 
	}
	}

	##Seteando FECHAS a formato SQL
	if ($fecha_ini != '') {
		$fecha_ini	= Utiles::fecha2sql($fecha_ini);
	} else {
		$fecha_ini = Utiles::fecha2sql($fecha_ini,'0000-00-00');
	}

	if ($fecha_fin != '') {
		$fecha_fin	= Utiles::fecha2sql($fecha_fin);
	} else {
		$fecha_fin = Utiles::fecha2sql($fecha_fin,'0000-00-00');
	}

	if ($id_cobro == 'Indefinido') {
		$cobro_nulo = true;
		unset($id_cobro);
	}

	#Si estamos en un cobro
	if($id_cobro) {
		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);

		if (!$cobro->Load($id_cobro)) {
			$pagina->FatalError(__('Cobro inválido'));
		} else {
			//En caso de que no estoy buscando debo setear fecha ini y fecha fin
			$fecha_ini = $cobro->fields['fecha_ini'];
			$fecha_fin = $cobro->fields['fecha_fin'];
		}
	}
	

	// Calculado aquÃ­ para que la variable $select_usuario estÃ© disponible al generar la tabla de trabajos.
	if($p_revisor->fields['permitido']) {
		$where_usuario = '';
	} else {
		$where_usuario = "AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=".$sesion->usuario->fields[id_usuario].") OR usuario.id_usuario=".$sesion->usuario->fields[id_usuario].")";
	}
	$select_usuario = Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ".$where_usuario." ORDER BY nombre ASC","id_usuario",$id_usuario,'','Todos','200');

	$where = base64_decode($where);
	if ( $where == '') {
		$where .= 1;
	}
	if ($id_usuario != '') {
		$where .= " AND tramite.id_usuario='$id_usuario' ";
	} else if(!$p_revisor->fields['permitido']) {
		// Se buscan trabajos de los usuarios a los que se puede revisar.
		$where .= " AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=".$sesion->usuario->fields[id_usuario].") OR usuario.id_usuario=".$sesion->usuario->fields[id_usuario].") ";
	}
	
	if ($revisado == 'NO') {
		$where.= " AND tramite.revisado = 0 ";
	}
	if ($revisado == 'SI') {
		$where.= " AND tramite.revisado = 1 ";
	}

	if ($codigo_asunto != '') {
			$where.= " AND tramite.codigo_asunto = '$codigo_asunto' ";
	}
			
	if ($cobrado == 'NO') {
		$where .= " AND tramite.id_cobro is null ";
	}
	if ($cobrado == 'SI') {
		$where .= " AND tramite.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL') ";
	}

	if ($from == 'reporte') {
		if ($id_cobro) {
			$where .= " AND contrato.id_contrato = cobro.id_contrato ";
		}

		if ($mes) {
			$where .= " AND CONCAT(Month(fecha),'-',Year(fecha)) = '$mes' ";
		}

		if ($cobro_nulo) {
			$where .= " AND tramite.id_cobro IS NULL ";
		}

		if ($estado) {
			if ($estado != 'abiertos') {
				if($estado == 'Indefinido') {
				$where .= " AND cobro.id_cobro IS NULL";
				} else {
				$where .= " AND cobro.estado = '$estado' ";
		}
			}
		}

		if ($lis_clientes) {
			$where .= " AND cliente.codigo_cliente IN (".$lis_clientes.") ";
		}
		if ($lis_usuarios) {
			$where .= " AND usuario.id_usuario IN (".$lis_usuarios.") ";
		}

	}

	//Estos filtros son tambien para la pag. mis horas
	if($activo)
	{
		if ($activo== 'SI') {
			$activo = 1;
		} else {
			$activo = 0;
		}

    $where .= " AND a1.activo = $activo ";
	}
	
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) ) {
		if ( $codigo_cliente_secundario != "" ) {
					$where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario' ";
	}
	} else {
		if ($codigo_cliente != "") {
			$where .= " AND cliente.codigo_cliente ='$codigo_cliente' ";
	}
	}

	#SQL FECHAS
	if ($fecha_ini != '' and $fecha_ini != 'NULL' and $fecha_ini != '0000-00-00') {
		$where .= " AND tramite.fecha >= '".$fecha_ini."' ";
	}

	if ($fecha_fin != '' and $fecha_fin != 'NULL' and $fecha_fin != '0000-00-00') {
		$where .= " AND tramite.fecha <= '".$fecha_fin."' ";
	}

	if(isset($cobro)) // Es decir si es que estoy llamando a esta pantalla desde un cobro
	{
		$cobro->LoadAsuntos();
		$query_asuntos = implode("','", $cobro->asuntos);
		$where .= " AND tramite.codigo_asunto IN ('$query_asuntos') ";
		//$where .= " AND tramite.cobrable = 1";
		if ($opc == 'buscar') {
			$where .= " AND (cobro.estado IS NULL OR tramite.id_cobro = '$id_cobro')";
		} else {
			$where .= " AND tramite.id_cobro = '$id_cobro'";
	}
	}

	//Filtros que se mandan desde el reporte Periodico
	if ($trabajo_si_no=='SI') {
		$where .= " AND trabajo_si_no=1 ";
	} else if($trabajo_si_no=='NO') {
		$where .= " AND trabajo_si_no=0 ";
	}	
	
	if ($clientes) {
		$where .= "	AND cliente.codigo_cliente IN ('".base64_decode($clientes)."')";
	}

	if ($usuarios) {
		$where .= "	AND usuario.id_usuario IN (".base64_decode($usuarios).")";
	}	
		

	#TOTAL HORAS

	#BUSCAR
	$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS 
									tramite.id_cobro,
									tramite.id_tramite, 
						tramite.id_moneda_tramite, 
						tramite.fecha, 
									tramite.codigo_asunto, 
									tramite.revisado,
									prm_moneda.simbolo as simbolo,
									asunto.codigo_cliente as codigo_cliente, 
									contrato.id_moneda as id_moneda_asunto, 
									asunto.id_asunto AS id,
									cobro.fecha_cobro as fecha_cobro_orden, 
									IF(tramite.cobrable=1,'SI','NO') as glosa_cobrable, 
									cobro.estado as estado_cobro, 
						usuario.username,
						usuario.nombre,
						usuario.apellido1,
						usuario.apellido2,
									CONCAT_WS(' ',usuario.nombre,usuario.apellido1, usuario.apellido2) as usr_nombre, 
									tramite.id_tramite_tipo, 
									DATE_FORMAT(tramite.fecha,'%e-%c-%x') AS fecha_cobro, 
									cobro.estado, 
									asunto.forma_cobro, 
									asunto.monto, 
									asunto.glosa_asunto, 
									tramite.descripcion, 
									contrato.id_contrato,
	            		contrato.descuento, 
	            		tramite_tipo.glosa_tramite, 
	            		tramite.tarifa_tramite, 
	            		tramite.id_moneda_tramite_individual, 
	            		tramite.tarifa_tramite_individual, 
	            		tramite.duracion, 
	            		prm_idioma.codigo_idioma, 
	            		tramite.cobrable 
	              FROM tramite
	              JOIN asunto ON tramite.codigo_asunto = asunto.codigo_asunto
	              LEFT JOIN prm_idioma ON prm_idioma.id_idioma = asunto.id_idioma 
	              JOIN contrato ON asunto.id_contrato = contrato.id_contrato
	              JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
	              JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
	              LEFT JOIN cobro ON tramite.id_cobro = cobro.id_cobro
	              LEFT JOIN usuario ON tramite.id_usuario = usuario.id_usuario
	              LEFT JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
	              WHERE $where ";
	
	if($check_tramite == 1 && isset($cobro) && !$excel)	//check_tramite vale 1 cuando aprietan boton buscar
	{
		$query2 = "UPDATE tramite SET id_cobro = NULL WHERE id_cobro='$id_cobro'";
		$resp = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
		$lista_tramites = new ListaTramites($sesion,'',$query);
		for ($x=0;$x<$lista_tramites->num;$x++) {
			$tramite = $lista_tramites->Get($x);
			$emitir_tramite = new Tramite($sesion);
			$emitir_tramite->Load($tramite->fields['id_tramite']);
			$emitir_tramite->Edit('id_cobro',$id_cobro);
			$emitir_tramite->Write();
		}
	}
	//Se hace la lista para la edición de TODOS los trabajos del query
	$lista_tramites = new ListaTramites($sesion,'',$query);
	$ids_listado_tramites="";
	for($x=0;$x<$lista_tramites->num;$x++)
	{
		$tramite = $lista_tramites->Get($x);
		$ids_listado_tramites.="t".$tramite->fields['id_tramite'];
	}
	if($orden == "")
	{
		if( $opc_orden=='edit' )
			$orden = "tramite.fecha_modificacion DESC";
		else 
			$orden = "tramite.fecha DESC, tramite.descripcion";
	}
	if(stristr($orden,".") === FALSE)
		$orden = str_replace("codigo_asunto","a1.codigo_asunto",$orden);

	$x_pag = 15;
	$b = new Buscador($sesion, $query, "Tramite", $desde, $x_pag, $orden);
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_gastos";
	$b->titulo = __('Listado de').' '.__('trámites');
	$b->AgregarFuncion("Editar",'Editar',"align=center nowrap");
	$b->AgregarEncabezado("tramite_tipo.glosa_tramite",__('Descrip.'),"align=center");
	$b->AgregarEncabezado("tramite.fecha",__('Fecha'));
	$b->AgregarEncabezado("cliente.glosa_cliente,asunto.codigo_asunto",__('Cliente/Asunto'),"align=center");
	if($p_revisor->fields['permitido'])
		$b->AgregarEncabezado("tramite.cobrable",__('Cobrable'),"align=center");
	if($p_revisor->fields['permitido'])
		$glosa_duracion=__('Hrs Trab.');
	else
		$glosa_duracion=__('Hrs trab.');
	$b->AgregarEncabezado("duracion",$glosa_duracion,"","","SplitDuracion");
	$b->AgregarEncabezado("tramite.id_cobro",__('Cobro'),"align=center");
	#$b->AgregarEncabezado("estado",__('Estado'),"align=left");
	if($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || strlen($select_usuario) > 164)
		$b->AgregarEncabezado("usr_nombre",__('Usuario'),"align=center");
	#if($p_adm->fields['permitido'])
	//$b->AgregarEncabezado("tramite.revisado",'Rev.',"align=center");
	if($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || $p_adm->fields['permitido'] ) 
		$b->AgregarEncabezado("tramite.tarifa_tramite",__('Tarifa'),"align=center");
	$b->AgregarFuncion("Opc.",'Opciones',"align=center nowrap");
	$b->color_mouse_over = "#bcff5c";
	$b->funcionTR = "funcionTR";

	if ($excel)	{
		if ($p_cobranza->fields['permitido']) {
			$orden = "cliente.glosa_cliente,contrato.id_contrato,asunto.glosa_asunto,tramite.fecha,tramite.descripcion";
		}
		$b1 = new Buscador($sesion, $query, "Trabajo", $desde, '', $orden);
		$lista = $b1->lista;

//			require_once Conf::ServerDir().'/interfaces/cobros_generales2.php';
//			exit;

		if ($p_cobranza->fields['permitido'] && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CobranzaExcel') ) || ( method_exists('Conf','CobranzaExcel') && Conf::CobranzaExcel() ) ) ) {
			require_once('cobros_generales_tramites.xls.php');
		} else {
			require_once('cobros3_tramites.xls.php');
		}
		exit;
	}
	
	$pagina->titulo = __('Listado de trámites');
	$pagina->PrintTop($popup);
?>
<script>
function GrabarCampo(accion,id_tramite,cobro,valor)
{
    var http = getXMLHTTP();
    if(valor) {
       valor = '1';
	} else {
      valor = '0';
	}

    loading("Actualizando opciones");
    http.open('get', 'ajax_grabar_campo.php?accion=' + accion + '&id_tramite=' + id_tramite + '&id_cobro=' + cobro + '&valor=' + valor);
    http.onreadystatechange = function() {
        if (http.readyState == 4) {
            var response = http.responseText;
            var update = new Array();
            if (response.indexOf('OK') == -1) {
              alert(response);
            }
            offLoading();
        }
  	};
    http.send(null);
}

function Refrescar()
{
	<?php  
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
				{ ?>
					var codigo_cliente = '&codigo_cliente_secundario='+jQuery('#codigo_cliente_secundario').val();
		<?php 	}
			else
				{ ?>
					var codigo_cliente = '&codigo_cliente_secundario='+jQuery('#campo_codigo_cliente_secundario').val();
		<?php 	} ?>
			var codigo_asunto = '&codigo_asunto_secundario='+jQuery('#campo_codigo_cliente_secundario').val();
<?php 	}
	else
		{ 
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
				{ ?>
					var codigo_cliente = '&codigo_cliente='+jQuery('#codigo_cliente').val();
		<?php 	}
			else
				{ ?>
					var codigo_cliente = '&codigo_cliente='+jQuery('#campo_codigo_cliente').val();
		<?php 	} ?>
			var codigo_asunto = '&codigo_asunto='+jQuery('#campo_codigo_asunto').val();
<?php  } 
?>
	var usuario = '&id_usuario='+jQuery('#id_usuario').val();
	var fecha_ini = '&fecha_ini='+jQuery('#fecha_ini').val();
	var fecha_fin = '&fecah_fin='+jQuery('#fecha_fin').val();
	
	var url = "listar_tramites.php?popup=1&opc=buscar&accion=refrescar"+codigo_cliente+codigo_asunto+usuario+fecha_ini+fecha_fin;
	self.location.href = url;
}


function AgregarNuevo( name )
{
	var usuario = jQuery('#id_usuario').length>0?   jQuery('#id_usuario').val() : <?php echo $sesion->usuario->fields[id_usuario];?> ;
	<?php  if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
			{ ?>
			var cliente = jQuery('#codigo_cliente_secundario').val();
			var asunto = jQuery('#codigo_asunto_secundario').val();
			urlo='ingreso_tramite.php?popup=1&codigo_cliente_secundario=' + cliente + '&codigo_asunto_secundario=' + asunto + '&id_usuario=' +usuario;
	<?php 	}
		else
			{ ?>
			var cliente = jQuery('#codigo_cliente').val();
			var asunto = jQuery('#codigo_asunto').val();
			urlo='ingreso_tramite.php?popup=1&codigo_cliente=' + cliente + '&codigo_asunto=' + asunto + '&id_usuario=' +usuario;
	<?php 	} ?>
	nuovaFinestra('Agregar_Tramite',750,470,urlo,'top=100, left=125');
}


function EliminaTramite( id )
{
	self.location.href="listar_tramites.php?accion=eliminar&popup=1&opc=buscar&id_tramite=" + id;
	return true;
}

function GuardarCampoTrabajo(id,campo,valor)
{
	var http = getXMLHTTP();
    var url = '_ajax.php?accion=actualizar_trabajo&id=' + id + '&campo=' + campo + '&valor=' + valor;

    loading("Actualizando campo");
    http.open('get', url);
    http.onreadystatechange = function()
    {
       if(http.readyState == 4)
       {
          var response = http.responseText;

                /*if(response.indexOf('OK') == -1)
                {
                    alert(response);
                }*/

          offLoading();
       }
    };
    http.send(null);
}

	

// Basado en http://snipplr.com/view/1696/get-elements-by-class-name/
function getElementsByClassName(classname)
{
	node = document.getElementsByTagName("body")[0];
	var a = [];
	var re = new RegExp('\\b' + classname + '\\b');
	var els = node.getElementsByTagName("*");
	for(var i=0,j=els.length; i<j; i++)
		if(re.test(els[i].className))a.push(els[i]);
	return a;
}
// Función para seleccionar todos las filas para editar, basada en la de phpMyAdmin
function seleccionarTodo(valor)
{
	var rows = getElementsByClassName('buscador')[0].getElementsByTagName('tr');
	var checkbox;
	// Se selecciona fila por medio porque cada trabajo ocupa dos filas de la tabla y el checkbox para editar está en la primera fila de cada trabajo.
	for (var i=0; i<rows.length; i++)
	{
		checkbox = rows[i].getElementsByTagName( 'input' )[0];
		if ( checkbox && checkbox.type == 'checkbox' && checkbox.disabled == false) {
			checkbox.checked = valor;
		}
	}
	return true;
}
// Encuentra los id de los trabajos seleccionados para editar, depende del id del primer <tr> que contiene al trabajo.
// Los id quedan en un string separados por el caracter 't'.
function getIdTrabajosSeleccionados()
{
	var rows = getElementsByClassName('buscador')[0].getElementsByTagName('tr');
	var checkbox;
	var ids = '';
	// Se revisa fila por medio porque cada trabajo ocupa dos filas de la tabla y el checkbox para editar está en la primera fila de cada trabajo.
	for (var i=0; i<rows.length; i++)
	{
		checkbox = rows[i].getElementsByTagName( 'input' )[0];
		
		if(checkbox)
			{
			if (checkbox.checked == true) {
				ids += rows[i].id;
			}
		}
	}
	return ids;
}
// Intenta editar múltiples trabajos, genera un error si no hay trabajos seleccionados.
function editarMultiplesArchivos()
{
	// Los id de los trabajos seleccionados están en un solo string separados por el caracter 't'.
	// La página editar_multiples_trabajos.php se encarga de parsear este string.
	var ids = getIdTrabajosSeleccionados();
	if(ids != '')
		nuovaFinestra('Editar_múltiples_trámites', 700, 450, 'editar_multiples_tramites.php?ids='+ids+'&popup=1','');
	else
		alert('Debe seleccionar por lo menos un trabajo para editar.');
}
</script>

<form method='get' name="form_tramites" id="form_tramites">
<input type='hidden' name='opc' id='opc' value='buscar'>
<input type='hidden' name='id_cobro' id='id_cobro' value='<?php echo $id_cobro ?>'>
<input type='hidden' name='popup' id='popup' value='<?php echo $popup?>'>
<input type='hidden' name='motivo' id='motivo' value='<?php echo $motivo?>'>
<input type='hidden' name='check_tramite' id='check_tramite' value=''>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<?php 
	if($motivo != "cobros")
	{
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
		$width_tabla = 'width="90%"';
	else 
		$width_tabla = 'width="100%"';
?>
<!-- Fin calendario DIV -->
<center>
<table <?php echo $width_tabla?>><tr><td>
<fieldset class="tb_base" style="border: 1px solid #BDBDBD;" width="100%">
<legend><?php echo __('Filtros')?></legend>
<table style="border: 0px solid black;" >
<?php 


		if( $p_revisor->fields['permitido'])
		{
 ?>
	<tr>
		<td align=right>
			<?php echo __('Trabajo')?>
		</td>
		<td align='left'> 
			<?php echo Html::SelectQuery($sesion,"SELECT codigo_si_no, codigo_si_no FROM prm_si_no ORDER BY id_codigo_si_no","trabajo_si_no",$trabajo_si_no,'','Todos','60')  ?>
			</td>
	</tr>
<?php  
		}
?>
   	<tr>
        <td align=right>
            <?php echo __('Nombre Cliente')?>
        </td>
        <td nowrap align='left' colspan=3>
<?php UtilesApp::CampoCliente($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>

				</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Asunto')?>
		</td>
		<td nowrap align='left' colspan=3>
                                <?php   UtilesApp::CampoAsunto($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>

		</td>
	</tr>
<?php 
		if(strlen($select_usuario) > 164) // Depende de que no cambie la funciÃ³n Html::SelectQuery(...)
		{
?>
	<tr>
		<td align=right>
			<?php echo __('Usuario')?>
		</td>
		<td align='left' colspan=3>
			<?php echo $select_usuario?>
		</td>
	</tr>
<?php 
		}
	
  	### Validando fecha
  	$hoy = date('Y-m-d');
   	$fecha_ini = Utiles::sql2date($fecha_ini);
   	$fecha_fin = Utiles::sql2date($fecha_fin);
?>
		<tr>
			<td align=right colspan=1>
					<?php echo __('Fecha desde')?>:
			</td>
			<td align=left colspan=3>
				<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo $fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
 				<?php echo __('Fecha hasta')?>:&nbsp;
				<input type="text" name="fecha_fin" class="fechadiff"  value="<?php echo $fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
 			</td>
		</tr>
		<tr>
			<td></td>
			<td colspan='3'  align=left>
				<input name='boton_buscar' id='boton_buscar' type='submit' class=btn onclick="this.form.check_tramite.value=1"  value=<?php echo __('Buscar')?>>
			</td>
			<td> <img src="<?php echo Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('tramite')" title="Agregar Tramite"><?php echo __('Agregar')?> <?php echo __('trámite')?></a> </td>
		</tr>
</table>
</fieldset>
</td></tr></table>
</center>
<?php 
}
?>
</form>

<?php 
	if(isset($cobro) || $opc == 'buscar')
	{
		echo "<center>";
		$b->Imprimir('', array('check_tramite')); //Excluyo Checktramite);
?>
		<a href="#" onclick="seleccionarTodo(true); return false;">Seleccionar todo</a>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a href="#" onclick="seleccionarTodo(false); return false;">Desmarcar todo</a>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a href="#" onclick="editarMultiplesArchivos(); return false;" title="Editar múltiples trámites">Editar seleccionados</a>
		<br />

		<a href="#" onclick="nuovaFinestra('Editar_listado_trámites',700,450,'editar_multiples_tramites.php?ids=<?php echo $ids_listado_tramites ?>&popup=1&listado_completo=1','');" title="Editar trabajos de todo el listado">Editar trabajos de todo el listado</a>
		 
		<br /> 
		<input type=button class=btn value="<?php echo __('Descargar listado a Excel')?>" onclick="window.open('listar_tramites.php?id_cobro=<?php echo $id_cobro?>&excel=1&motivo=<?php echo $motivo?>&where=<?php echo urlencode(base64_encode($where))?>')">
		<br />
	</center>
		<!--<input type=button class=btn value="<?php echo __('Descargar Archivo a Word')?>" onclick="window.open('trabajos.php?id_cobro=<?php echo $id_cobro?>&word=1&motivo=<?php echo $motivo?>&where=<?php echo urlencode(base64_encode($where))?>')">-->
<?php  
	}
	function Cobrable(& $fila)
	{
		global $id_cobro;
		#$checked = "";
		#$checked = "checked";
		
			if($fila->fields['id_cobro'] == $id_cobro)
				$checked = "checked";
			else
				$checked = "";

			#if($fila->fields['id_cobro'] == $id_cobro)
			#	$checked = "checked";
	#		if($checked == "checked")
				$Check = "<input type='checkbox' $checked onclick=GrabarCampo('cobrar_tramite','".$fila->fields['id_tramite']."',$id_cobro,'');>";
	#		else
	#			$Check = "<input type='checkbox' onchange=GrabarCampo('cobrar_trabajo','".$fila->fields['id_trabajo']."','','')>";
		return $Check;
	}
	function Revisado(& $fila)
	{
		#$checked = "";
		#$checked = "checked";
		if($fila->fields['revisado'] == 1)
			$checked = "checked";
		else
			$checked = "";

		#if($fila->fields['id_cobro'] == $id_cobro)
		#   $checked = "checked";

		$Check = "<input type='checkbox' $checked onmouseover=\"ddrivetip('Para marcar un trámite como revisado haga click aquí.&lt;br&gt;Los trámites revisados no se desplegarán en este listado la próxima vez.')\" onmouseout=\"hideddrivetip();\" onchange=\"GuardarCampoTrabajo(".$fila->fields['id_trabajo'].",'revisado',this.checked ? 1 : 0)\">";
		return $Check;
	}

	function Opciones(& $tramite)
	{
		$img_dir = Conf::ImgDir();
		global $motivo;
		$id_cobro = $tramite->fields['id_cobro'];
		global $sesion;
		global $p_profesional;
		global $p_revisor;

		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);

		if($motivo == 'cobros')
		{
			$opc_html = Cobrable($tramite);
		}

		if($p_revisor->fields['permitido'])
		{
			if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro']))
				$opc_html.= "<a href=# onclick=\"nuovaFinestra('Editar_Trámite',650,450,'ingreso_tramite.php?id_cobro=".$id_cobro."&id_tramite=".$tramite->fields['id_tramite']."&popup=1&opcion=edit','');\" title=".__('Editar')."><img src=$img_dir/editar_on.gif border=0></a>";
			else
				$opc_html.= "<a href=# onclick=\"alert('".__('No se puede modificar este trámite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')."');\" title=\"".__('Cobro ya Emitido al Cliente')."\"><img src=$img_dir/editar_off.gif border=0></a>";

		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
			{
			if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro']))
				$opc_html.= "<a href='javascript:void(0);' onclick=\"if (confirm('¿".__('Est&aacute; seguro de eliminar el')." ".__('trámite')."?'))EliminaTramite(".$tramite->fields['id_tramite'].");\"><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
			else
				$opc_html.= "<a href=# onclick=\"alert('".__('No se puede eliminar este trámite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')."');\" title=\"".__('Cobro ya Emitido al Cliente')."\"><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
			}
		else
			{
			if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro']))
				$opc_html.= "<a href='javascript:void(0);' onclick=\"if (confirm('¿".__('Est&aacute; seguro de eliminar el')." ".__('trámite')."?'))EliminaTramite(".$tramite->fields['id_tramite'].");\"><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
			else
				$opc_html.= "<a href=# onclick=\"alert('".__('No se puede eliminar este trámite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')."');\" title=\"".__('Cobro ya Emitido al Cliente')."\"><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
			}
		}
		elseif($p_profesional->fields['permitido'])
		{
			if($tramite->Estado()== 'Revisado')
				$opc_html .= "<img src=$img_dir/candado_16.gif border=0 title='".__('Este trabajo ya ha sido revisado')."'>";
			else
			{
				if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro']))
					$opc_html.= "<a href=# onclick=\"nuovaFinestra('Editar_Trámite',550,450,'ingreso_tramite.php?id_cobro=".$id_cobro."&id_tramite=".$tramite->fields['id_tramite']."&popup=1opcion=edit','');\" title=".__('Editar')."><img src=$img_dir/editar_on.gif border=0></a>";
				else
					$opc_html.= "<a href=# onclick=\"alert('".__('No se puede modificar este trámite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')."');\" title=\"".__('Cobro ya Emitido al Cliente')."\" ><img src=$img_dir/editar_off.gif border=0></a>";
			
				if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro']))
					$opc_html.= "<a href='javascript:void(0);' onclick=\"if (confirm('¿".__('Est&aacute; seguro de eliminar el')." ".__('trámite')."?'))EliminaTramite(".$tramite->fields['id_tramite'].");\"><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
				else
					$opc_html.= "<a href=# onclick=\"alert('".__('No se puede eliminar este trámite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.')."');\" title=\"".__('Cobro ya Emitido al Cliente')."\"><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
			}
		}
		else
			$opc_html .= "<img src=$img_dir/candado_16.gif border=0 title='".__('Usted no tiene permiso de Revisor')."'>";

		return $opc_html;
	}
	function SplitDuracion($time)
	{
		list($h,$m,$s) = split(":",$time);
		if($h > 0 || $s > 0)
			return $h.":".$m;
	}
	function funcionTR(& $tramite)
	{
		global $sesion;
		global $id_cobro;
		global $p_revisor;
		global $p_cobranza;
		global $select_usuario;
		static $i = 0;
		
		
		if($i % 2 == 0) {
			$color = "#dddddd";
		} else {
			$color = "#ffffff";
		}
		
		$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
		if( $tramite->fields['codigo_idioma'] != '' ) {
			$idioma->Load($tramite->fields['codigo_idioma']);
		}
		else {
			$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
		}
		
		if( $tramite->fields['tarifa_tramite_individual'] > 0 )
			$tarifa = $tramite->fields['tarifa_tramite_individual'];
		else
		$tarifa = $tramite->fields['tarifa_tramite']; 
		list($h,$m,$s) = split(":",$tramite->fields['duracion_defecto']);
		$duracion = $h + ($m > 0 ? ($m / 60) :'0');
		$total = round($tarifa, 2);
		$total_horas += $duracion;
		#	if(substr($h,0,1)=='0')
		#		$h=substr($h,1);
		$queryformato = "SELECT pi.formato_fecha FROM prm_idioma pi JOIN cobro c ON (  pi.codigo_idioma = c.codigo_idioma) WHERE c.id_cobro='" . $id_cobro . "' LIMIT 1";
		$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
		
		$fecha = Utiles::sql2fecha($tramite->fields['fecha'],$formato_fecha);
		$html .= "<tr id=\"t".$tramite->fields[id_tramite]."\" bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B;\">";
		$html .= '<td><input type="checkbox" onmouseover="ddrivetip(\'Para editar múltiples trámites haga click aquí.\')" onmouseout="hideddrivetip();" ></td>';
		$glosa_tramite = $tramite->fields['glosa_tramite'];
		$descripcion = $tramite->fields['descripcion'];
		if(strlen($glosa_tramite) > 18 && strlen($descripcion)>18 )
			$html .= "<td width=120 nowrap><div onmouseover=\"ddrivetip('<b>$glosa_tramite</b><br>$descripcion');\" onmouseout=\"hideddrivetip();\" style=\"max-width: 100px;\"><strong>".substr($glosa_tramite,0,16)."..</strong><br>".substr($descripcion,0,16)."..</div></td>";
		else if( strlen($glosa_tramite) > 18 )
			$html .= "<td width=120 nowrap><div onmouseover=\"ddrivetip('<b>$glosa_tramite</b><br>$descripcion');\" onmouseout=\"hideddrivetip();\" style=\"max-width: 100px;\"><strong>".substr($glosa_tramite,0,16)."..</strong><br>".$descripcion."</div></td>";
		else if( strlen($descripcion) > 18 )
			$html .= "<td width=120 nowrap><div onmouseover=\"ddrivetip('<b>$glosa_tramite</b><br>$descripcion');\" onmouseout=\"hideddrivetip();\" style=\"max-width: 100px;\"><strong>".$glosa_tramite."</strong><br>".substr($descripcion,0,16)."..</div></td>";
		else
			$html .= "<td width=120 nowrap><div style=\"max-width: 100px;\"><strong>".$glosa_tramite."</strong><br>".$descripcion."</div></td>";
		$html .= "<td>$fecha</td>";
		$html .= "<td>".$tramite->fields['glosa_cliente']."<br>".$tramite->fields['glosa_asunto']."</td>";
		
		if ($p_revisor->fields['permitido']) {
			if($tramite->fields['cobrable']==1) {
				$html .= "<td align=center>SI</td>";
			} else {
				$html .= "<td align=center>NO</td>";
			}
		}
			
			$duracion = $tramite->fields['duracion'];
		//echo $duracion;
		if(!$p_revisor->fields['permitido'])  {
				$duracion_trabajada = $tramite->fields['duracion'];
			
				$duracion = $duracion_trabajada;
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
			{
				$duracion = UtilesApp::Time2Decimal($duracion_trabajada);
			}
		}
		else
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
			{
				$duracion_trabajada = $tramite->fields['duracion'];
				$duracion = UtilesApp::Time2Decimal($duracion_trabajada);
			}
		}
		//echo $duracion."fin<br>";
		if($p_cobranza->fields['permitido'])
		{
			$editar_cobro = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Generar Cobro',750,660,'cobros5.php?popup=1&id_cobro=".$tramite->fields['id_cobro']."');\"'>".$tramite->fields['id_cobro']."</a>";
		}
		else
		{
			$editar_cobro = $tramite->fields['id_cobro'];
		}
		
		$moneda_tramite = new Moneda($sesion);
		$moneda_tramite->Load($tramite->fields['id_moneda_tramite']);

        $html .= "<td align=center>".$duracion."</td>";
        $html .= "<td>".$editar_cobro."</td>";
        #$html .= "<td>".$tramite->Estado()."</td>";
        if($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || strlen($select_usuario) > 164) {
        	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') ) {
        			$html .= "<td align=center>".$tramite->fields['username']."</td>";
			} else {
        		$html .= "<td align=center>".substr($tramite->fields['nombre'],0,1).substr($tramite->fields['apellido1'],0,1).substr($tramite->fields['apellido2'],0,1)."</td>";
        	}
		}
        if( $p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || $p_adm->fields['permitido'] ) {
					//$html .= '<td>Rev.'.Revisado(& $tramite).'</td>';
					$html .= "<td align=center><strong>".__('Tarifa')."</strong><br>".$moneda_tramite->fields['simbolo']." ".number_format($tarifa, $moneda_tramite->fields['cifras_decimales'], $idioma->fields['separador_decimales'],$idioma->fields['separador_miles'])."</td>";
					}
		$html .= '<td align=center nowrap>'.Opciones($tramite).'</td>';
        $html .= "</tr>";
        
        $i++;
        return $html;
	}



	$pagina->PrintBottom($popup);

