<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Gasto.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	#require_once Conf::ServerDir().'/classes/GastoGeneral.php';

	$sesion = new Sesion(array('OFI'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$gasto = new Gasto($sesion);
	#$gastoGeneral = new GastoGeneral($sesion);

	$ingreso = new Gasto($sesion);

	if($prov == 'false')
	{
		$txt_pagina = $id_gasto ? __('Edición de Gastos') : __('Ingreso de Gastos');
		$txt_tipo = __('Gasto');
	}
	else
	{
		$txt_pagina = $id_gasto ? __('Edición de Provisión') : __('Ingreso de Provisión');
		$txt_tipo = __('Provisión');
	}

	if($id_gasto != "")
	{
		$gasto->Load($id_gasto);

		if($gasto->fields['id_movimiento_pago'] != '')
		{
			$ingreso->Load($gasto->fields['id_movimiento_pago']);
		}
		if($codigo_asunto != $gasto->fields['codigo_asunto'])#revisar para codigo secundario
		{
			$cambio_asunto = true;
		}
	}

	if($opcion == "guardar")
	{
		if($_POST['cobrable']==1)
		{
			$gasto->Edit("cobrable",1);
		}
		else
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
			{	
				$gasto->Edit("cobrable","0");
			}
			else
			{	
				$gasto->Edit("cobrable","1");
			}
		}
		
		/*
		 *  Si el gasto se considera con IVA, 
		 *  se calcula en base al porcentaje impuesto gasto
		 *  del cobro
		 */
		
		if($con_impuesto)
		{
			$gasto->Edit("con_impuesto",$con_impuesto);
		}
		else
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosConSinImpuesto') ) || ( method_exists('Conf','UsarGastosConSinImpuesto') && Conf::UsarGastosConSinImpuesto() ) )
				$gasto->Edit("con_impuesto","NO");
			else
				$gasto->Edit("con_impuesto","SI");
		}
		if(!$codigo_cliente && $codigo_cliente_secundario)
		{
			$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario = '$codigo_cliente_secundario'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($codigo_cliente) = mysql_fetch_array($resp);
		}
		if(!$codigo_asunto && $codigo_asunto_secundario)
		{
			$query = "SELECT codigo_asunto FROM asunto WHERE codigo_asunto_secundario = '$codigo_asunto_secundario'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($codigo_asunto) = mysql_fetch_array($resp);
		}
		$monto=str_replace(',','.',$monto);
		if($prov == 'true')
		{
			$gasto->Edit("ingreso",$monto);
			$gasto->Edit("monto_cobrable",$monto);
		}
		else if($prov=='false')
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
			{
				if($monto <=0)
					$gasto->Edit("egreso",$monto_cobrable);
				else
					$gasto->Edit("egreso",$monto);

				if($monto_cobrable >= 0)
				{
				$monto_cobrable=str_replace(',','.',$monto_cobrable);
				$gasto->Edit("monto_cobrable",$monto_cobrable);
				}
				else
					$gasto->Edit("monto_cobrable",$monto);
			}
			else
			{

			$gasto->Edit("egreso",$monto);
			$gasto->Edit("monto_cobrable",$monto);
			}
		}



		$gasto->Edit("fecha",Utiles::fecha2sql($fecha));
		$gasto->Edit("id_usuario",$id_usuario);
		$gasto->Edit("descripcion",$descripcion);
		$gasto->Edit("id_moneda",$id_moneda);
		$gasto->Edit("codigo_cliente",$codigo_cliente ? $codigo_cliente : "NULL");
		$gasto->Edit("codigo_asunto",$codigo_asunto ? $codigo_asunto : "NULL");
		$gasto->Edit("id_usuario_orden",$id_usuario_orden ? $id_usuario_orden : $sesion->usuario->fields[id_usuario]);
		$gasto->Edit("id_cta_corriente_tipo",$id_cta_corriente_tipo ? $id_cta_corriente_tipo : "NULL");
		$gasto->Edit("numero_documento",$numero_documento ? $numero_documento : "NULL");
		$gasto->Edit("id_factura",$numero_factura_asociada ? $numero_factura_asociada : "NULL");
		$gasto->Edit("fecha_factura",$fecha_factura_asociada ? Utiles::fecha2sql($fecha_factura_asociada) : "NULL");
		$gasto->Edit("numero_ot",$numero_ot ? $numero_ot : "NULL");
		
		
	
		if($pagado && $prov == 'false')
		{
			$ingreso->Edit('fecha',$fecha_pago ? Utiles::fecha2sql($fecha_pago) : "NULL");
			$ingreso->Edit("id_usuario",$id_usuario);
			$ingreso->Edit("descripcion",$descripcion_ingreso);
			$ingreso->Edit("id_moneda",$gasto->fields['id_moneda'] ? $gasto->fields['id_moneda'] : $id_moneda);
			$ingreso->Edit("codigo_cliente",$codigo_cliente ? $codigo_cliente : "NULL");
			$ingreso->Edit("codigo_asunto",$codigo_asunto ? $codigo_asunto : "NULL");
			$ingreso->Edit("id_usuario_orden",$id_usuario_orden ? $id_usuario_orden : $sesion->usuario->fields[id_usuario]);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) ) && $monto_cobrable > 0)
			$ingreso->Edit('ingreso',$monto_pago ? $monto_pago : $monto_cobrable );
			else
			$ingreso->Edit('ingreso',$monto_pago ? $monto_pago : '0');
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
			$ingreso->Edit('monto_cobrable',$monto_cobrable ? $monto_cobrable : $ingreso);
			else
			$ingreso->Edit('monto_cobrable',$ingreso);
			$ingreso->Edit("documento_pago",$documento_pago ? $documento_pago : "NULL");
			if($ingreso->Write())
				$gasto->Edit('id_movimiento_pago',$ingreso->fields['id_movimiento'] ? $ingreso->fields['id_movimiento'] : 'NULL');
		}
		else
		{
			if($elimina_ingreso != '')
			{
				if(!$ingreso->EliminaIngreso($id_gasto))
					$ingreso_eliminado = '<br>'.__('El ingreso no pudo ser eliminado ya que existen otros gastos asociados.');
			}

			$gasto->Edit('id_movimiento_pago',NULL);
		}
		/*
		Ha cambiado el asunto del gasto se setea id_cobro NULL
		*/
		if($cambio_asunto)
			$gasto->Edit('id_cobro','NULL');

		$gasto->Edit('id_proveedor',$id_proveedor ? $id_proveedor : NULL);

		if ($gasto->Write())
		{
			$pagina->AddInfo($txt_tipo.' '.__('Guardado con éxito.').' '.$ingreso_eliminado);
?>
			<script language='javascript'>
				window.opener.Refrescar();
			</script>
<?
		}
	}

	if($gasto->fields[id_usuario_orden] == "")
		$gasto->fields[id_usuario_orden] = $sesion->usuario->fields[id_usuario];

	$pagina->titulo = $txt_pagina;
	$pagina->PrintTop($popup);
?>

<script type="text/javascript">
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


document.observe('dom:loaded', function() {
});

function ShowGastos(valor)
{
	if(valor)
		$('tabla_gastos').style.display = 'inline';
	else
		$('tabla_gastos').style.display = 'none';
}

function Cerrar()
{
	//window.opener.BuscarGastos('','buscar');
	window.close();
}

function CambiaMonto( form )
{
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ComisionGastos') ) || ( method_exists('Conf','ComisionGastos') && Conf::ComisionGastos() ) )
	{
?>
	form.monto_cobrable.value = (form.monto.value * (1+form.porcentajeComision.value/100)).toFixed(2);
<?
	}
	else
	{
?>
	form.monto_cobrable.value = form.monto.value;
<?
	}
?>
}

function Validar(form)
{
	monto = parseFloat(form.monto.value);
	if( form.monto_cobrable )
		monto_cobrable = parseFloat(form.monto_cobrable.value);

	if(monto <= 0 || isNaN(monto))
	monto=monto_cobrable;

	
	if($('codigo_cliente').value == '')
	{
		alert('<?=__('Debe seleccionar un cliente')?>');
		form.codigo_cliente.focus();
    	return false;
	}

	if($('campo_codigo_asunto').value == '')
	{
		alert('<?=__('Ud. debe seleccionar un').' '.__('asunto')?>');
		form.codigo_asunto.focus();
		return false;
	}

<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) ) { ?>
	if((monto <= 0 || isNaN(monto)) && (monto_cobrable <= 0 || isNaN(monto_cobrable)))
	{
		alert('<?=__('Debe ingresar un monto para el gasto')?>');
		form.monto.focus();
		return false;
	}
<? } else { ?>
	if((monto <= 0 || isNaN(monto)))
	{
		alert('<?=__('Debe ingresar un monto para el gasto')?>');
		form.monto.focus();
		return false;
	}
<? } ?> 
	if(form.descripcion.value == "")
	{
		alert('<?=__('Debe ingresar una descripción')?>');
		form.descripcion.focus();
		return false;
	}

	var radio_choice = false;
	for( i=0; i < form.id_moneda.options.length; i++ )
 	{
   		 if( form.id_moneda.options[i].selected == true && form.id_moneda.value != '')
    		{
			radio_choice = true;
    		}
 	}
	if (!radio_choice)
	{
		alert('<?=__('Debe seleccionar una Moneda')?>');
		return false;
	}
	form.submit();
}

function CheckEliminaIngreso(chk)
{
	var form = $('form_gastos');
	if(chk)
		form.elimina_ingreso.value = 1;
	else
		form.elimina_ingreso.value = '';

	return true;
}

function ActualizarDescripcion()
{
	$('descripcion').value = $('glosa_gasto').value;
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
			}
		};
	    http.send(null);
	}
}
function AgregarNuevo(tipo, prov)
{
	<? 
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
	 { ?>
			var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
			var codigo_asunto_secundario = $('codigo_asunto_secundario').value;
			if(tipo == 'gasto')
			{
				var urlo = "agregar_gasto.php?popup=1&prov="+prov+"&codigo_cliente_secundario="+codigo_cliente_secundario+"&codigo_asunto_secundario="+codigo_asunto_secundario;
				window.location=urlo;
			}
<? }
	else
	 { ?>
			var codigo_cliente = $('codigo_cliente').value;
			var codigo_asunto = $('codigo_asunto').value;
			if(tipo == 'gasto')
			{
				var urlo = "agregar_gasto.php?popup=1&prov="+prov+"&codigo_cliente="+codigo_cliente+"&codigo_asunto="+codigo_asunto;
				window.location=urlo;
			}
<? } ?>
}

function AgregarProveedor()
{
	var urlo = 'agregar_proveedor.php?popup=1';
	nuevaVentana('Agregar_Proveedor',430,370,urlo,'top=100, left=125');
}
</script>
<? echo(Autocompletador::CSS()); ?>
<form method=post action="<?= $SERVER[PHP_SELF] ?>"  id="form_gastos" autocomplete='off'>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=id_gasto value="<?= $gasto->fields['id_movimiento'] ?>" />
<input type=hidden name=id_gasto_general value="<?= $gasto->fields['id_gasto_general'] ?>" />
<input type=hidden name='prov' value='<?=$prov?>'>
<input type=hidden name=id_movimiento_pago id=id_movimiento_pago value=<?=$gasto->fields['id_movimiento_pago']?>>
<input type=hidden name=elimina_ingreso id=elimina_ingreso value=''>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<br>
<table width='90%'>
	<tr>
		<td align=left><b><?=$txt_pagina ?></b></td>
	</tr>
</table>
<br>

<?
if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
{
	if( !$codigo_cliente_secundario )
		{
			if( $gasto->fields['codigo_cliente'] )
			 	$codigo_cliente=$gasto->fields['codigo_cliente'];
			 	
			$query = "SELECT codigo_cliente_secundario FROM cliente WHERE codigo_cliente='$codigo_cliente'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($codigo_cliente_secundario)=mysql_fetch_array($resp);
		}
		
	if( !$codigo_asunto_secundario )
		{
			if($gasto->fields['codigo_asunto'])
				$codigo_asunto=$gasto->fields['codigo_asunto'];
				
			$query = "SELECT codigo_asunto_secundario FROM asunto WHERE codigo_asunto='$codigo_asunto'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($codigo_asunto_secundario)=mysql_fetch_array($resp);
		}
}
else
{
	if( !$codigo_cliente )
			$codigo_cliente = $gasto->fields['codigo_cliente'];
			
	if( !$codigo_asunto )
			$codigo_asunto = $gasto->fields['codigo_asunto'];
}
?>

<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left>
			<b><?=__('Información de') ?> <?=$prov == 'true' ? __('provisión') : __('gasto')?></b>
		</td>
		<td width='40%' align=right>
			<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0 > <a href='javascript:void(0)' onclick="AgregarNuevo('gasto',<?=$prov ?>);" title="Agregar Gasto"><?=$prov == 'true' ? __('Nueva provisión') : __('Nuevo gasto')?></a>
		</td>
	</tr>
</table>
<table class="border_plomo" style="background-color: #FFFFFF;" width='90%'>
	<tr>
		<td align=right>
			<?=__('Fecha')?>
		</td>
		<td align=left>
			<input type="text" name="fecha" value="<?=$gasto->fields[fecha] ? Utiles::sql2date($gasto->fields[fecha]) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left>
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
					echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente","codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos');",320);
				}
				else
				{
					echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $gasto->fields['codigo_cliente'] ? $gasto->fields['codigo_cliente'] : $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');", 320);
				} 
			}	?>
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Asunto')?>
		</td>
		<td align=left> 
				<?
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
				{
					echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto","codigo_asunto_secundario", $codigo_asunto_secundario,"", "CargaIdioma(this.value);CargarSelectCliente(this.value, 'gastos');", 320, $codigo_cliente_secundario); 
				}
				else
				{
					echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $gasto->fields['codigo_asunto'] ? $gasto->fields['codigo_asunto'] : $codigo_asunto,"", "CargaIdioma(this.value);CargarSelectCliente(this.value, 'gastos');", 320, $gasto->fields['codigo_cliente'] ? $gasto->fields['codigo_cliente'] : $codigo_cliente);
				} ?>
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
<?
	if( method_exists('Conf','GetConf') )
	{
		if( Conf::GetConf($sesion,'TipoGasto') && $prov=='false')
		{
?>
	<tr>
		<td align=right>
			<?=__('Tipo de Gasto')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo","id_cta_corriente_tipo", $gasto->fields['id_cta_corriente_tipo'] ? $gasto->fields['id_cta_corriente_tipo'] : '1', '','',"160"); ?>
		</td>
	</tr>
<?
		}
	}
	else if (method_exists('Conf','TipoGasto'))
	{
		if(Conf::TipoGasto() && $prov=='false')
		{
?>
	<tr>
		<td align=right>
			<?=__('Tipo de Gasto')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo","id_cta_corriente_tipo", $gasto->fields['id_cta_corriente_tipo'] ? $gasto->fields['id_cta_corriente_tipo'] : '1', '','',"160"); ?>
		</td>
	</tr>
<?
		}
	}
?>

	<tr>
		<td align=right>
			<?=__('Proveedor')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_proveedor, glosa FROM prm_proveedor","id_proveedor", $gasto->fields['id_proveedor'] ? $gasto->fields['id_proveedor'] : '0', '','Cualquiera',"160"); ?>
			<a href='javascript:void(0)' onclick="AgregarProveedor();" title="Agregar Proveedor"><img src="<?=Conf::ImgDir()?>/agregar.gif" border=0 ></a>
		</td>
	</tr>

	<tr>
		<td align=right>
			<?=__('Monto')?>
		</td>
		<td align=left>
			<input name=monto size=10 onchange="CambiaMonto(this.form);" value="<?=$gasto->fields['egreso'] ? $gasto->fields['egreso'] : $gasto->fields['ingreso'] ?>" />
			<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<?=__('Moneda')?>&nbsp;
			<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $gasto->fields['id_moneda'] ? $gasto->fields['id_moneda'] : '', '','',"80"); ?>
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>

<?
if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ComisionGastos') ) || ( method_exists('Conf','ComisionGastos') && Conf::ComisionGastos() ) ) && $prov=='false')
{
?>
	<tr>
		<td align="right">
			<?=__('Porcentaje comisión')?>
		</td>
		<td align="left">
			<input name="porcentajeComision" size="10" onchange="CambiaMonto(this.form);" value="<?=method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ComisionGastos'):Conf::ComisionGastos()?>" /> %
		</td>
	</tr>
<?
}
?>
	<? if($prov=='false' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) ) ) { ?>
	<tr>
		<td align=right>
			<?=__('Monto cobrable')?>&nbsp;
		</td>
		<td align=left>
			<input name=monto_cobrable size=10 value="<?=$gasto->fields['monto_cobrable']?>" />
		</td>
	</tr>
<?	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PrmGastos') ) || ( method_exists('Conf','PrmGastos') && Conf::PrmGastos() ) )
	{
?>
	<tr>
		<td align=right>
			<?=__('Descripción Parametrizada')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT glosa_gasto,glosa_gasto FROM prm_glosa_gasto ORDER BY id_glosa_gasto","glosa_gasto", $gasto->fields['descripcion'] ? $gasto->fields['descripcion'] : '', 'onchange="ActualizarDescripcion()"','',"300"); ?>
		</td>
	</tr>
<?
	}
?>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
	{
?>
	<tr>
		<td align=right>
			<?=__('N° Documento')?>
		</td>
		<td align=left>
			<input name=numero_documento size=10 value="<?=($gasto->fields['numero_documento'] && $gasto->fields['numero_documento'] != 'NULL') ? $gasto->fields['numero_documento'] : '' ?>" />
		</td>
	</tr>
<?
	}
?>
	<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') ) || ( method_exists('Conf','FacturaAsociada') && Conf::FacturaAsociada() ) )
	{
?>
	<tr>
		<td align=right>
			<?=__('Factura asociada')?>
		</td>
		<td align=left>
			<input name="numero_factura_asociada" size=10 value="<?=($gasto->fields['id_factura'] && $gasto->fields['id_factura'] != 'NULL') ? $gasto->fields['id_factura'] : '' ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<?=__('Fecha Factura')?> <input type="text" name="fecha_factura_asociada" value="<?=$gasto->fields['fecha_factura'] ? Utiles::sql2date($gasto->fields['fecha_factura']) : date('d-m-Y') ?>" id="fecha_factura_asociada" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_factura_asociada" style="cursor:pointer" />
		</td>
	</tr>
<?
	}
?>
<?
//la OT es la orden de trabajo de las notarías
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) ) && $prov=='false')
	{
?>
	<tr>
		<td align=right>
			<?=__('N° OT')?>
		</td>
		<td align=left>
			<input name=numero_ot size=10 value="<?=($gasto->fields['numero_ot'] && $gasto->fields['numero_ot'] != 'NULL') ? $gasto->fields['numero_ot'] : '' ?>" />
		</td>
	</tr>
<?
	}
?>
	<tr id='descripcion_gastos'>
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
			<?=__('Descripción')?>
<?
		}
?>
		</td>
		<td align=left>
			<textarea id='descripcion' name=descripcion cols="45" rows="3"><?=$checked_general == '' ? $gasto->fields['descripcion'] : $gastoGeneral->fields['descripcion'];?></textarea>
		</td>
	</tr>
	<? 
		// Por definicion las provisiones no deben tener Impuestos
		if ($prov == 'false')
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )
			{ 
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosConSinImpuesto') ) || ( method_exists('Conf','UsarGastosConSinImpuesto') && Conf::UsarGastosConSinImpuesto() ) )
				{ ?>
	<tr>
		<td align=right>
			<?=__('Con Impuesto')?>
			<?php
			if($gasto->fields['con_impuesto'] == 'SI') {
				$con_impuesto_check = 'checked';
			}
			else {
				$con_impuesto_check = '';
			}
			?>
		</td>
		<td align=left>
			<input type="checkbox" id="con_impuesto" name="con_impuesto" value="SI" <?php echo $con_impuesto_check; ?>>
 		</td>
	</tr>
			<?	} 
			}
		} ?>
	<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
			{ 
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
				{ ?>
	<tr>
		<td align=right>
			<?=__('Cobrable')?>
			<?php
			$cobrable_checked = 'checked';
			
			if($id_gasto>0)
			{
				if($gasto->fields['cobrable'] == 1) {
					$cobrable_checked = 'checked';
				}
				else{
					$cobrable_checked = '';
				}
			}
			?>
		</td>
		<td align=left>
			<input type="checkbox" id="cobrable" name="cobrable" value="1" <?php echo $cobrable_checked; ?>>
 		</td>
	</tr>
			<?	} 
			} ?>		
	<tr>
		<td align=right colspan="2">&nbsp;</td>
	</tr>
<?
	if($prov == 'false')
	{
?>
	<tr>
		<td align=right>
			<?=__('Ordenado por')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario ORDER BY apellido1", "id_usuario_orden", $gasto->fields[id_usuario_orden], "", __('Ninguno'),'170'); ?>
		</td>
	</tr>
<?
	}
?>
</table>

<?
	if($prov == 'false')
	{
?>
<br>


<div id='tabla_gastos' style="display:<?=$gasto->fields['id_movimiento_pago'] > 0 ? 'inline' : 'none'?>">
<table style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right>
			<?=__('Fecha')?>
		</td>
		<td align=left>
			<input type="text" name="fecha_pago" value="<?=$ingreso->fields['fecha'] ? Utiles::sql2date($ingreso->fields['fecha']) : date('d-m-Y') ?>" id="fecha_pago" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_pago" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Documento')?>
		</td>
		<td align=left>
			<input type="text" name="documento_pago" id=documento_pago value="<?=$ingreso->fields['documento_pago']?>">
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Monto')?> <label><?=$ingreso->fields['id_moneda'] ? Utiles::Glosa($sesion, $ingreso->fields['id_moneda'], 'simbolo', 'prm_moneda', 'id_moneda') : ''?></label>
		</td>
		<td align=left>
			<input type="text" name="monto_pago" id=monto_pago value="<?=$ingreso->fields['ingreso']?>" style="text-align:right" size=8>
			<input type=hidden name=tipo_moneda value=<?=Utiles::Glosa($sesion,$ingreso->fields['id_moneda'],'simbolo','prm_moneda','id_moneda' )?>>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left>
			<textarea name=descripcion_ingreso cols="45" rows="3"><?=$ingreso->fields['descripcion']?></textarea>
		</td>
	</tr>
	<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )
		{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosConSinImpuesto') ) || ( method_exists('Conf','UsarGastosConSinImpuesto') && Conf::UsarGastosConSinImpuesto() ) )
			{ ?>
	<tr>
		<td align=right>
			<?=__('Con Impuesto')?>
			<?php
			if($gasto->fields['con_impuesto'] == 'SI') {
				$con_impuesto_check = 'checked';
			}
			else {
				$con_impuesto_check = '';
			}
			?>
		</td>
		<td align=left>
			<input type="checkbox" id="con_impuesto" name="con_impuesto" value="SI" <?php echo $con_impuesto_check; ?>>
		</td>
	</tr>
	<?	 }	
	} ?>
	
	
	
	
</table>
</div>
<?
	}
?>

<br>
<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left>
			<input type=button class=btn value="<?=__('Guardar')?>" onclick="return Validar(this.form);" /> <input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
		</td>
	</tr>
</table>

</form>
<script type="text/javascript">
if (document.getElementById('img_fecha'))
{
	Calendar.setup(
		{
			inputField	: "fecha",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_fecha"		// ID of the button
		}
	);
}
if (document.getElementById('fecha_factura_asociada'))
{
	Calendar.setup(
		{
			inputField	: "fecha_factura_asociada",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_fecha_factura_asociada"		// ID of the button
		}
	);
}
if (document.getElementById('img_fecha_pago'))
{
	Calendar.setup(
		{
			inputField	: "fecha_pago",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_fecha_pago"		// ID of the button
		}
	);
}
<?
if ( ( ( method_exists('Conf','IdiomaGrande') && Conf::IdiomaGrande() ) || ( method_exists( 'Conf','GetConf' ) && Conf::GetConf( $sesion, 'IdiomaGrande' ) ) ) && $codigo_asunto )
{
?>
CargaIdioma("<?= $codigo_asunto ?>");
<?
}
?>
</script>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
		{
			echo Autocompletador::Javascript($sesion);
		}
	echo InputId::Javascript($sesion);
	$pagina->PrintBottom($popup);
?>
