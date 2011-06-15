<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/SelectorHoras.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/TramiteTipo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';


	$sesion = new Sesion(array('DAT','PRO'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	# solo se muestran las opciones al admin de datos
	
	$tramite = new TramiteTipo($sesion);
	if($id_tramite_tipo)
	 $tramite->LoadId($id_tramite_tipo);
	 	

	if( $accion=='guardar' )
	{
		if( $opcion=='agregar' )
			{
				$tramite->Edit('glosa_tramite',$glosa_tramite);
				$tramite->Edit('trabajo_si_no_defecto',$trabajo_si_no_defecto);
				if($trabajo_si_no_defecto==1)
					$tramite->Edit('duracion_defecto',$duracion);
					
					$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto=1";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($id_tramite_tarifa)=mysql_fetch_array($resp);
					
					$query = "SELECT id_moneda FROM prm_moneda ORDER BY id_moneda";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					
				if($tramite->Write())
					{
						$pagina->AddInfo(__('Trámite').' '.__('guardado con exito'));
						$setvalores=true;
					}
				else
						$setvalores=false;
						
				if($setvalores)
					{
					while( list($id_moneda)=mysql_fetch_array($resp))
					{
						if( $tarifa_tramite[$id_moneda] )
						{
							$query2 = "INSERT INTO tramite_valor(id_tramite_tipo, id_moneda, id_tramite_tarifa, tarifa) VALUES(".$tramite->fields['id_tramite_tipo'].",".$id_moneda.",".$id_tramite_tarifa.",".$tarifa_tramite[$id_moneda].")";
							mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
						}
					}
				}
			}
		else if( $opcion=='editar' )
			{
				$tramite->Edit('glosa_tramite',$glosa_tramite);
				$tramite->Edit('trabajo_si_no_defecto',$trabajo_si_no_defecto);
				if($trabajo_si_no_defecto==1)
					{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
						$tramite->Edit('duracion_defecto',UtilesApp::Decimal2Time($duracion));
					else
						$tramite->Edit('duracion_defecto',$duracion);
					}
					
					
					$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto=1";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($id_tramite_tarifa)=mysql_fetch_array($resp);
					
					$query = "SELECT id_moneda FROM prm_moneda ORDER BY id_moneda";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					
					if($tramite->Write())
					{
						$pagina->AddInfo(__('Trámite').' '.__('editado con exito'));
						$setvalores=true;
					}
				else
						$setvalores=false;
					
			if($setvalores)
				{
					while( list($id_moneda)=mysql_fetch_array($resp))
					{
						if( $tarifa_tramite[$id_moneda] )
						{
							$query2 = "INSERT INTO tramite_valor( id_tramite_tipo, id_moneda, id_tramite_tarifa )  
															VALUES( ".$tramite->fields['id_tramite_tipo'].", ".$id_moneda.", ".$id_tramite_tarifa." ) 
															ON DUPLICATE KEY UPDATE tarifa=".$tarifa_tramite[$id_moneda];
							mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
						}
					}
				}
			}
	}

	if($accion == "eliminar")
	{
		$tramite_eliminar = new TramiteTipo($sesion);

		$tramite_eliminar->LoadId($id_tramite_tipo);
		if(!$tramite_eliminar->Eliminar())
			$pagina->AddError($tramite_eliminar->error);
		else
			$pagina->AddInfo(__('Trámite').' '.__('eliminado con éxito'));
			
			unset($tramite);
	}
	$pagina->titulo = __('Trámites');
$pagina->PrintTop( $popup );
?>


<script type="text/javascript">
	function IsNumeric(strString)
   //  check for valid numeric strings	
   {
   var strValidChars = "0123456789.-";
   var strChar;
   var blnResult = true;

   if (strString.length == 0) return false;

   //  test strString consists of valid characters listed above
   for (i = 0; i < strString.length && blnResult == true; i++)
      {
      strChar = strString.charAt(i);
      if (strValidChars.indexOf(strChar) == -1)
         {
         blnResult = false;
         }
      }
   return blnResult;
   }


function Validar(form, desde, ids)
{
	var arrayids=new Array;
	arrayids=ids.split(',');

	if(!form.glosa_tramite.value)
	{
		alert("<?=__('Debe ingresar un nombre del trámite')?>");
		form.glosa_tramite.focus();
		return false;
	}
	
	for(var i=0; i<arrayids.length; i++)
	{
		if(document.getElementById('tarifa_tramite['+arrayids[i]+']').value)
			{
			if(!IsNumeric(document.getElementById('tarifa_tramite['+arrayids[i]+']').value))
				{
					document.getElementById('tarifa_tramite['+arrayids[i]+']').value=0;
				}
			}
	} 
	
	if( form.trabajo_si_no_defecto.checked )
	{
		if(!form.duracion.value )
		{
			alert("<?=__('Debe ingresar la duracion')?>");
			form.duracion.focus();
			return false;
		}
	}
	if( desde=='agregar' )
	form.action = 'tramites.php?accion=guardar&opcion=agregar&popup=1';
	else if(desde=='editar')
	form.action = 'tramites.php?accion=guardar&opcion=editar&popup=1';
	
	form.submit();
	return true;
}

function CambiaHora( horas, campo, max_horas )
{
		var tiempo = $(campo).value;
	tiempo = tiempo.split(':');
	$(campo).value = PongaCero(horas)+':'+tiempo[1]+':00';
	if( horas == max_horas )
		CambiaMinuto( '0', campo, 'limit' );
}

function CambiaMinuto( minutos, campo, max_horas )
{
	var tiempo = $(campo).value;
	tiempo = tiempo.split(':');
	if( tiempo[0] == max_horas || max_horas == 'limit' )
		{
		minutos = '0';
		$('minuto_'+campo).value = minutos;
		}
	$(campo).value = tiempo[0]+':'+PongaCero(minutos)+':00';
}

function PongaCero( numero )
{
 		if( numero < 10 )
		numero = '0'+numero;
 		return numero;
}


function SubeTiempo( campo, direccion, intervalo, cont )
{
	var gRepeatTimeInMS = $('gRepeatTimeInMS').value;
	var gIsMouseDown = $('gIsMouseDown').value;
	
	if( !cont )
		var cont=0;
		
	if( gIsMouseDown=='true' )
	{
		cont++;
		if(cont==5)
			$('gRepeatTimeInMS').value = 100;
		else if(cont==10)
			$('gRepeatTimeInMS').value = 50;
		else if(cont==20)
			$('gRepeatTimeInMS').value = 25;
		var tiempo = $(campo).value;
		tiempo = tiempo.split(':');
		if( direccion == 'subir' && tiempo[0] < $('max_hora').value )
			{
			var minutos = (tiempo[1]-0)+intervalo;
			if(minutos > 59)
				{
					$(campo).value = PongaCero((tiempo[0]-0)+1)+':'+PongaCero(minutos-60)+':00';
					$('hora_'+campo).value = (tiempo[0]-0)+1;
					$('minuto_'+campo).value = minutos-60;
				}
			else
				{
					$(campo).value = tiempo[0]+':'+PongaCero(minutos)+':00';
					$('minuto_'+campo).value = minutos;
				}
			}
		else if( direccion == 'bajar' && ( tiempo[0] > 0 || tiempo[1] > 0 ) )
			{
				var minutos = tiempo[1]-intervalo;
				if( minutos < 0 && $('hora_'+campo).value > 0 )
					{
						$(campo).value = PongaCero(tiempo[0]-1)+':'+PongaCero(minutos+60)+':00';
						$('hora_'+campo).value = tiempo[0]-1;
						$('minuto_'+campo).value = minutos+60;
					}
				else
					{
						$(campo).value = tiempo[0]+':'+PongaCero(minutos)+':00';
						$('minuto_'+campo).value = minutos;
					}
			}
		setTimeout("SubeTiempo('"+campo+"', '"+direccion+"', "+intervalo+", "+cont+" );", gRepeatTimeInMS);
	}
	else
		$('gRepeatTimeInMS').value = 200;
}



function setMouseDown( campo, direccion, intervalo )
{
$('gIsMouseDown').value = true;
SubeTiempo( campo, direccion, intervalo );
}

function setMouseUp()
{
$('gIsMouseDown').value = false;
}

function ShowTime(form, valor)
{
	var check = $(valor);
	var tr = $('time');

	if(check.checked)
	{
		tr.style['display'] = '';
	}
	else
	{
		tr.style['display'] = 'none';
	}
}

function Listar( form, from )
{
	if(from == 'buscar')
		form.action = 'tramites.php?buscar=1';
	else
		return false;

	form.submit();
	return true;
}
//funcion java para eliminar
function EliminaTramite(id_tramite_tipo)
{
	var desde = <?=($desde)? $desde : '0'?>;
	form = document.getElementById('form_tramite');
	self.location.href = "tramites.php?id_tramite_tipo="+id_tramite_tipo+"&accion=eliminar&buscar=1&desde="+desde;
	return true;
}

function EditarTramite( id_tramite_tipo ) 
{
	var urlo = "tramites.php?id_tramite_tipo=" + id_tramite_tipo + "&popup=1&opcion=editar";
	nuevaVentana('Editar_Tramite',730,470,urlo,'top=100, left=125');
}

function AgregarNuevo( tipo )
{
	var nombre = document.getElementById('glosa_tramite').value;
	
	var urlo = "tramites.php?popup=1&opcion=agregar&glosa_tramite=" + nombre;
	nuevaVentana('Agregar_Tramite',730,470,urlo,'top=100, left=125');
}

function Refrescar()
{
	var url = "tramites.php?buscar=1&accion=refrescar";
	self.location.href= url;
}
</script>

<form method=post name="form_tramite" id="form_tramite">
<!--<input type=hidden name=opcion value="Buscar" />-->
<input type=hidden name=id_tramite_tipo value="<?=$tramite->fields['id_tramite_tipo'] ? $tramite->fields['id_tramite_tipo'] : $id_tramite_tipo ?>" />
<input type=hidden name="gIsMouseDown" id="gIsMouseDown" value=false />
<input type=hidden name="gRepeatTimeInMS" id="gRepeatTimeInMS" value=200 />
<input type=hidden name=max_hora id=max_hora value=14 />
<?
	if($p_admin)
	{
?>
	<table width='90%' cellspacing=3 cellpadding=3>
		<tr>
			<td></td>
			<td align=right>
				<a href="tramites.php"><img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <?=__('Nuevo Trámite')?></a>
			</td>
		</tr>
	</table>
<?
	}
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
	{
		if($popup) {
			$width_table = 'width="450px"';
			$clase_fieldset = 'class="tb_base border_plomo"';
		}
		else {
			$clase_fieldset = 'class="tb_base border_plomo"';
			$width_table = 'width="90%"';
		}
	}
	else
	{
		$clase_fieldset = '';
		$width_table = 'width=100%';
	}
	?>
	<table <?=$width_table ?>><tr><td>
	<fieldset <?=$clase_fieldset ?> width="100%">
		<legend><?=$opcion=='agregar' ? __('Agregar Trámite') : __('Trámites')?></legend>
		<table width='90%' cellspacing=3 cellpadding=3>
			<tr>
				<td colspan="2" align=right width=35% class=cvs>
					<?=__('Nombre Trámite')?>
				</td>
				<td colspan="4" align=left>
					<input type="text" name="glosa_tramite" id="glosa_tramite" size="35" value="<?=$tramite->fields['glosa_tramite'] ? $tramite->fields['glosa_tramite'] : $glosa_tramite ?>">
				</td>
			</tr>
			<?
			if( $opcion == 'agregar' || $opcion == 'editar' ) {
			?>
			<tr>
				<td colspan="2"></td>
				<td colspan="4" align="left">
					<input type="checkbox" name=trabajo_si_no_defecto id="trabajo_si_no_defecto" value="1" <?=$tramite->fields['trabajo_si_no_defecto'] || $trabajo_si_no_defecto ? 'checked' : '' ?> onClick="ShowTime(this.form, this)" >Ingresar como trabajo
				</td>
			</tr>
    <tr id="time" style='display:<?=$tramite->fields['trabajo_si_no_defecto'] || $trabajo_si_no_defecto ? '' : 'none' ?>;' >
        <td colspan="2" align=right width="35%" class=cvs>
            <?=__('Duración')?>
        </td>
        <td colspan="4" align=left>
    <?
    
     
	//Revisa el Conf si esta permitido y la función existe
	if( method_exists('Conf','GetConf') )
	{
		if(Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal')
		{
?>
					<input type="text" name="duracion" value="<?=$tramite->fields['duracion_defecto'] ? UtilesApp::Time2Decimal($tramite->fields['duracion_defecto']) : $duracion ?>" id="duracion" size="6" maxlength=4 <?= !$nuevo && $sesion->usuario->fields['id_usuario']!=$id_usuario ? 'readonly' : '' ?> onchange="CambiaDuracion(this.form,'duracion');"/>
<?
		}
		else if(Conf::GetConf($sesion,'TipoIngresoHoras')=='java')
		{
			echo Html::PrintTime("duracion",$tramite->fields['duracion_defecto'] ? $tramite->fields['duracion_defecto'] : $duracion);
		}
		else if(Conf::GetConf($sesion,'TipoIngresoHoras')=='selector')
		{
			if(!$duracion) $duracion = '00:00:00';
			echo SelectorHoras::PrintTimeSelector($sesion,"duracion", $tramite->fields['duracion_defecto'] ? $t->fields['duracion_defecto'] : $duracion, 14);
		}
	}
	else if (method_exists('Conf','TipoIngresoHoras'))
	{
		if(Conf::TipoIngresoHoras()=='decimal')
		{
?>
					<input type="text" name="duracion" value="<?=$tramite->fields['duracion_defecto'] ? UtilesApp::Time2Decimal($tramite->fields['duracion_defecto']) : $duracion ?>" id="duracion" size="6" maxlength=4 <?= !$nuevo && $sesion->usuario->fields['id_usuario']!=$id_usuario ? 'readonly' : '' ?> onchange="CambiaDuracion(this.form,'duracion');"/>
<?
		}
		else if(Conf::TipoIngresoHoras()=='java')
		{
			echo Html::PrintTime("duracion",$tramite->fields['duracion_defecto'] ? $tramite->fields['duracion_defecto'] : $duracion);
		}
		else if(Conf::TipoIngresoHoras()=='selector')
		{
			if(!$duracion) $duracion = '00:00:00';
			echo SelectorHoras::PrintTimeSelector($sesion,"duracion", $tramite->fields['duracion_defecto'] ? $t->fields['duracion_defecto'] : $duracion, 14);
		}
	}
	else
	{
		echo Html::PrintTime("duracion",$tramite->fields['duracion_defecto'] ? $tramite->fields['duracion_defecto'] : $duracion);
	}
?>
				</td>
			</tr>
			<tr>
				<td colspan="2"><b><?=__('Valores Tarifa Standard:')?></b></td><td colspan="4"></td>
			</tr>
			<tr>
					<?
					$query = "SELECT count(*) FROM prm_moneda";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($cont_monedas)=mysql_fetch_array($resp);
					
					$query = "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda ASC";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					
					$ids=array();
					while(list($id, $glosa_moneda)=mysql_fetch_array($resp))
					{
						$ids[]=$id;
						if($tramite->fields['id_tramite_tipo'])
							{
								$query2= "SELECT tarifa 
														FROM tramite_valor 
														JOIN tramite_tarifa USING( id_tramite_tarifa ) 
														WHERE tarifa_defecto=1
															AND id_tramite_tipo=".$tramite->fields['id_tramite_tipo']." 
															AND id_moneda=".$id;
								$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion);
								list($tarifa)=mysql_fetch_array($resp2);
							} 
						echo '<td width="'.$width.'" align="'.$align.'">'.$glosa_moneda.'<br><input type=\"text\" id="tarifa_tramite['.$id.']" name="tarifa_tramite['.$id.']"  size=\"10\" value="'.$tarifa.'" /></td>';
					}
					$ids=implode(',',$ids);
					?>
			</tr>
		<? } 
			else
			{ ?>
				<tr>
					<td colspan="2" align="right" class=cvs>
			<?=__('Tarifa en')?>&nbsp;
					</td><td colspan="4" align="left">
			<?=Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda",$id_moneda, '','',"80");?>
					</td>
				</tr>
			<? } ?>
			<tr>
				<td colspan="2"></td>
				<td colspan="2" align=left>
					<? 
					if( $opcion != 'agregar' && $opcion != 'editar' ) { ?>
					<input type=button class=btn name=buscar value=<?=__('Buscar')?> onclick="Listar(this.form, 'buscar')">
			<?	}	else { 
					 if( $opcion == 'agregar' )  {?>
					<input type=button class=btn value="<?=__('Guardar')?>" onclick="Validar(this.form, 'agregar', '<?=$ids ?>')" >
				<? } else if( $opcion == 'editar' ) { ?>
					<input type=button class=btn value="<?=__('Guardar')?>" onclick="Validar(this.form, 'editar', '<?=$ids ?>')" >
				<? } } ?>
				</td> 
		<?	if( !($opcion=='agregar') || $accion=='guardar' )  { ?>
				<td colspan="2" align="right"> <img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('tramite')" title="Agregar Tramite"><?=$opcion=='editar' || $accion=='guardar' ? __('Nuevo') : __('Agregar')?> <?=__('trámite')?></a></td>
		<?	} ?>
			</tr>
		</table>
	</fieldset>
</td></tr></table>
</form>


<?
	if($buscar)
	{
		if(!$id_moneda) $id_moneda=1;
		$query = "SELECT SQL_CALC_FOUND_ROWS tramite_tipo.*, tramite_tipo.id_tramite_tipo, prm_moneda.simbolo as simbolo, glosa_tramite, tramite_valor.tarifa as tarifa_standard, duracion_defecto
								FROM tramite_tipo
								LEFT JOIN tramite_valor ON (tramite_tipo.id_tramite_tipo=tramite_valor.id_tramite_tipo AND tramite_valor.id_moneda='$id_moneda' AND tramite_valor.id_tramite_tarifa=1)
								LEFT JOIN prm_moneda ON tramite_valor.id_moneda=prm_moneda.id_moneda
								WHERE glosa_tramite LIKE '%$glosa_tramite%'";
		if($orden == "") {
			if($accion=='refrescar')
				$orden = " glosa_tramite";
			else
				$orden = " glosa_tramite";
			}
		$x_pag = 20;

		$b = new Buscador($sesion, $query, "TramiteTipo", $desde, $x_pag, $orden);
		$b->AgregarEncabezado("glosa_tramite",__('Nombre Trámite'),"align=left");
		$b->AgregarEncabezado("duracion_defecto",__('Duración'),"align=center");
		$b->AgregarEncabezado("simbolo",__('Mon.'),"align=center");
		$b->AgregarEncabezado("tarifa_standard",__('Tarifa Standard'),"align=center");
			$b->AgregarFuncion("","Opciones","align=center nowrap");
		$b->color_mouse_over = "#bcff5c";
		$b->Imprimir();
	}
	function Opciones(& $fila)
	{
		global $sesion;
		global $desde;
		$id_tramite_tipo=$fila->fields['id_tramite_tipo'];
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) 
		{
		$txt .= " <a href='javascript:void(0);' onclick=\"EditarTramite($id_tramite_tipo);\" title='".__('Editar Trámite')."'><img src='".Conf::ImgDir()."/editar_on.gif' border=0 alt='Editar tramite' /></a>"
			. "<a href='javascript:void(0);' onclick=\"if (confirm('¿".__('Est&aacute; seguro de eliminar el')." ".__('trámite')."?'))EliminaTramite($id_tramite_tipo);\"><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
		}
		else
		{
			$txt .= " <a href='javascript:void(0);' onclick=\"EditarTramite($id_tramite_tipo);\" title='".__('Editar Trámite')."'><img src='".Conf::ImgDir()."/editar_on.gif' border=0 alt='Editar tramite' /></a>"
			. "<a href='javascript:void(0);' onclick=\"if (confirm('¿".__('Est&aacute; seguro de eliminar el')." ".__('trámite')."?'))EliminaTramite($id_tramite_tipo);\"><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
		}
		return $txt;
	}
	
	
	if(($opcion == 'agregar' || $opcion == 'editar' ) && $accion=='guardar')
	{ ?>
			<script language='javascript'>
				window.opener.Refrescar();
			</script>
<?} 
echo SelectorHoras::Javascript();
$pagina->PrintBottom($popup);
?>
	
