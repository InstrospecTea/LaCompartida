<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Semana.php';
	require_once Conf::ServerDir().'/classes/UsuarioExt.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);

	/*Eliminando trabajo*/
	if($opcion == "eliminar")  #ELIMINAR TRABAJO
	{
		$t = new Trabajo($sesion);
		$t->Load($id_trab);
		if(! $t->Eliminar() )
			$pagina->AddError($t->error);
		else
		{
			$pagina->AddInfo(__('Trabajo').' '.__('eliminado con éxito'));
			unset($t);
		}
	}

	$pagina->titulo = __('Resumen semana');
	$pagina->PrintTop();

	$dias = array("Lunes", "Martes", "Miércoles", "Jueves", "Viernes","Sábado","Domingo");
if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
{?>
	<form method=post id=form_semana name=form_semana>
		<table class="tb_base" width="85%" style="border: 1px solid #BDBDBD;">
			<tr>
				<td align="center">
					<table width="90%">
						<tr>
							<td>

<?}
else
{?>
		<form method=post id=form_semana name=form_semana><table class="tb_base" width="85%">
			<tr>
				<td align="center">
					<table width="90%">
						<tr>
							<td>
<?}?>
		<input type='hidden' name='accion' value=''>
		<input type='hidden' name='opcion' value=''>
		&nbsp;</td>
		<td>
		<?= Html::PrintCalendar("semana",$semana); ?>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right" style="width:35%" >
			<?=__('Área Usuario')?>:
		</td>
		<td valign="top" class="texto" align="left">
			<?=Html::SelectQuery($sesion,'SELECT id, glosa FROM prm_area_usuario ORDER BY glosa','id_area_usuario', $id_area_usuario,'onchange="if(this.selectedIndex > 0) $(\'usuarios[]\').disabled = true; else $(\'usuarios[]\').disabled = false;"', 'Cualquiera')?>
		</td>
	</tr>
	<tr>
		<td align=right><?=__('Usuario')?>:
<?
	// Revisamos si el usuario tiene categorÃ­a de revisor.
	$params_array['codigo_permiso'] = 'REV';
	$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	// Revisamos si el usuario es de cobranza.
	$params_array['codigo_permiso'] = 'COB';
	$permiso_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	if($permisos->fields['permitido'] || $permiso_cobranza->fields['permitido']) // Si el usuario tiene categorÃ­a de revisor o es de cobranza puede ver a todos
		$where = '';
	else // En otro caso solo puede ver a los que tenga asignados para revisar.
		$where = " AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=".$sesion->usuario->fields[id_usuario].") OR usuario.id_usuario=".$sesion->usuario->fields[id_usuario].") ";


?>
	</td><td align=left>
<?
	echo(Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ".$where." ORDER BY nombre ASC", "usuarios[]",$usuarios,$id_area_usuario? " disabled=true multiple size=5":"multiple size=5","","200"));

?>
						</td>
					</tr>
					<tr>
						<td align=right>
							<?=__('Tipo de Dato')?>:
						</td>
						<td align=left>
							<select name='tipo_dato'>
								<option value='horas_trabajadas'><?=__('Horas Trabajadas')?></option>
								<option value='horas_cobrables' <?= $tipo_dato=='horas_cobrables'? 'selected':''?>><?=__('Horas Cobrables')?></option>
								<option value='horas_castigadas'<?= $tipo_dato=='horas_castigadas'? 'selected':''?>><?=__('Horas Castigadas')?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td align=left>
							<input type=submit class=btn value="<?=__('Ver semana')?>">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<br />

<?
	
	if( is_array($usuarios) )
		$objeto_semana = new Semana($sesion,"",$usuarios);
  
	$usr = new Usuario($sesion);

	if($id_area_usuario)
	{
		$usuarios = array();
		$query = "SELECT id_usuario FROM usuario WHERE id_area_usuario = '".mysql_real_escape_string($id_area_usuario)."'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while( list($id_usuario) = mysql_fetch_array($resp))
			$usuarios[] = $id_usuario;
	}

	for($j=0;$j<count($usuarios);$j++)
	{

		$id_usuario = $usuarios[$j];
		$usr->loadId($id_usuario);
		
		if( $j==0 & ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
			echo "<table class=\"tb_base\" width=\"85%\" style=\"border: 1px solid #BDBDBD;\"><tr><td align=\"center\">";
		
		if($semana == "")
		{
			$semana2 = "CURRENT_DATE()";
			$sql_f = "SELECT DATE_ADD( CURDATE(), INTERVAL - ( DAYOFWEEK(CURDATE()) - 2 ) DAY ) AS semana_inicio";
			$resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f,__FILE__,__LINE__,$sesion->dbh);
			list($semana_actual) = mysql_fetch_array($resp);
			$semana_anterior = date("Y-m-d",strtotime("$semana_actual-7 days"));
			$semana_siguiente = date("Y-m-d",strtotime("$semana_actual+7 days"));
		}
		else
		{
			$semana2 = "'$semana'";
			$sql_f = "SELECT DATE_ADD( '".$semana."', INTERVAL - ( DAYOFWEEK('".$semana."') - 2 ) DAY ) AS semana_inicio";
			$resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f,__FILE__,__LINE__,$sesion->dbh);
			list($semana_actual) = mysql_fetch_array($resp);
			$semana_anterior = date("Y-m-d",strtotime("$semana_actual-7 days"));
			$semana_siguiente = date("Y-m-d",strtotime("$semana_actual+7 days"));
		}

		switch($tipo_dato)
		{
			case 'horas_castigadas':
				$td = ' ( TIME_TO_SEC(duracion) - IFNULL( TIME_TO_SEC(duracion_cobrada), 0) ) ';
			break;
			case 'horas_cobrables':
				$td = 'duracion_cobrada';
			break;
			default:
				$td = 'duracion';
		}

		$query = "SELECT *, 
								TIME_TO_SEC($td)/90 as alto, 
								$td as duracion_pedida,
								DAYOFWEEK(fecha) AS dia_semana, 
								trabajo.cobrable
							FROM trabajo
							JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
					 		JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
					 		WHERE
							trabajo.id_usuario = $id_usuario
							AND YEARWEEK(fecha,1) = YEARWEEK($semana2,1)
							ORDER BY trabajo.fecha,trabajo.id_trabajo";
		$lista = new ListaTrabajos($sesion, "", $query);

		echo("<strong>".__('Haga clic en el botón derecho sobre algún trabajo para modificarlo')."</strong><br /><br />");
		/* Semana del */
		$semana_del = $semana_actual != '' ? Utiles::sql3fecha($semana_actual,'%d de %B de %Y') : Utiles::sql3fecha(date('Y-m-d'),'%d de %B de %Y');

		echo("<table style='width:500px'>");
		if($j==0)
		{
			/* Semana siguiente/anterior */
			$tip_anterior = Html::Tooltip("<b>".__('Semana anterior').":</b><br>".Utiles::sql3fecha($semana_anterior,'%d de %B de %Y'));
			$tip_siguiente = Html::Tooltip("<b>".__('Semana siguiente').":</b><br>".Utiles::sql3fecha($semana_siguiente,'%d de %B de %Y'));
			echo("<tr>");
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
				echo("<td style='width: 100px; text-align:left;'><img src='".Conf::ImgDir()."/izquierda_nuevo.gif' $tip_anterior class='mano_on' onclick=\"CambiaSemana('".$semana_anterior."')\"></td>");
			else	
				echo("<td style='width: 100px; text-align:left;'><img src='".Conf::ImgDir()."/izquierda.gif' $tip_anterior class='mano_on' onclick=\"CambiaSemana('".$semana_anterior."')\"></td>");
			echo("<td colspan='5' align='center' style='width: 500px;'><b>".__('Semana del').":</b> ".$semana_del."</td>");
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
				echo("<td style='width: 100px; text-align:right;'><img src='".Conf::ImgDir()."/derecha_nuevo.gif' $tip_siguiente class='mano_on' onclick=\"CambiaSemana('".$semana_siguiente."')\"></td>");
			else
				echo("<td style='width: 100px; text-align:right;'><img src='".Conf::ImgDir()."/derecha.gif' $tip_siguiente class='mano_on' onclick=\"CambiaSemana('".$semana_siguiente."')\"></td>");
			echo("</tr>");
			/* fin semanas */
		}

		/* NOmbre Usuario */
		echo("<tr>");
		$nombre_usuario = $usr->fields['apellido1'].' '.$usr->fields['apellido2'].', '.$usr->fields['nombre'];
		echo("<td style='width: 700px; text-align:left;' colspan='4'>".__('Usuario').": ".$nombre_usuario."</td>");
		echo("<td align='right' colspan='2'>"._('Total mes actual').":</td>");
		echo("<td style='vertical-align: middle'><strong>".$sesion->usuario->HorasTrabajadasEsteMes($id_usuario,$tipo_dato)."</strong></td>");
		echo("</tr>");

		/* Listando días */
		echo("<tr>");
		for($i = 0; $i < 7; $i++)
		{
			$dia_de_mes = date("j",strtotime(Utiles::add_date($semana_actual,$i)));
			echo("
			<td style='width: 100px; border: 1px solid black; text-align:center;' nowrap>
				$dias[$i] $dia_de_mes
			</td>
			");
		}
		echo("</tr>");
		echo("<tr>");
		$dia_anterior=2;
		$total[0]=0;//Dejo en 0 todos los días de la semana
		$total[1]=0;
		$total[2]=0;
		$total[3]=0;
		$total[4]=0;
		$total[5]=0;
		$total[6]=0;

		for($i = 0; $i < $lista->num; $i++)
		{
			$asunto = new Asunto($sesion);
			if($i == 0)
				echo("<td style='width: 100px'>");

			$alto = max($lista->Get($i)->fields[alto],12)."px";
			if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
			{
				$cod_asunto = $lista->Get($i)->fields[codigo_asunto_secundario];
			}
			else
			{
				$cod_asunto = $lista->Get($i)->fields[codigo_asunto];
			}
			$dia_semana = $lista->Get($i)->fields[dia_semana];
			if($dia_semana == 1)
				$dia_semana = 8;

			$duracion = $lista->Get($i)->fields['duracion_pedida'];
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) )
			{
				if(strpos($duracion,":"))
					list($hh,$mm,$ss) = split(":",$duracion);
				else
				{
					$hh = floor($duracion/3600);
					$mm = floor(($duracion-$hh*3600)/60);
					$ss = $duracion-$hh*3600-$mm*60;
					$hh = $hh < 10 ? '0'.$hh : $hh;
					$mm = $mm < 10 ? '0'.$mm : $mm;
					$ss = $ss < 10 ? '0'.$ss : $ss;
					$duracion = $hh.':'.$mm.':'.$ss;
				}
				$duracion = UtilesApp::Time2Decimal( $duracion );
			}
			else
			{
				if(strpos($duracion,":"))
					list($hh,$mm,$ss) = split(":",$duracion);
				else
				{
					$hh = floor($duracion/3600);
					$mm = floor(($duracion-$hh*3600)/60);
					$ss = $duracion-$hh*3600-$mm*60;
					$hh = $hh < 10 ? '0'.$hh : $hh;
					$mm = $mm < 10 ? '0'.$mm : $mm;
					$ss = $ss < 10 ? '0'.$ss : $ss;
				}
				$duracion = "$hh:$mm";
			}
			$fecha = $lista->Get($i)->fields[fecha];
			$asunto->LoadByCodigo($cod_asunto);
			$cliente = $asunto->fields[codigo_cliente];

			if($lista->Get($i)->fields[cobrable] == 0 || $lista->Get($i)->fields[cobrable] == 2)
			{
				$no_cobrable = 'No cobrable';
				$color = '#FFFFFF';
			}
			else
			{
				$no_cobrable = '';
				$color = $objeto_semana->colores[$cod_asunto];
				if($color == '')
					$color = '#E8E7D9';
			}

			$total[$dia_semana]  += $hh + $mm/60;
			#$total[$dia_semana] += ($alto/40);

			$id_trabajo = $lista->Get($i)->fields[id_trabajo];
			if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
			{
				$tooltip = Html::Tooltip("<b>".__('Cliente')."(".$lista->Get($i)->fields[codigo_cliente_secundario]."):</b><br>".$lista->Get($i)->fields[glosa_cliente]."<br><b>".__('Asunto')."(".$lista->Get($i)->fields[codigo_asunto_secundario]."):</b><br>".$lista->Get($i)->fields[glosa_asunto]."<br /><b>".__('Duración').":</b><br>".$duracion."<br /><b>".__('Descripción').":</b><br>".nl2br($lista->Get($i)->fields[descripcion])."<br><b>".$no_cobrable."</b>");
			}
			else
			{
				$tooltip = Html::Tooltip("<b>".__('Cliente')."(".$lista->Get($i)->fields[codigo_cliente]."):</b><br>".$lista->Get($i)->fields[glosa_cliente]."<br><b>".__('Asunto')."(".$lista->Get($i)->fields[codigo_asunto]."):</b><br>".$lista->Get($i)->fields[glosa_asunto]."<br /><b>".__('Duración').":</b><br>".$duracion."<br /><b>".__('Descripción').":</b><br>".nl2br($lista->Get($i)->fields[descripcion])."<br><b>".$no_cobrable."</b>");
			}
			if($dia_anterior != $dia_semana)
			{
				for($q = $dia_anterior+1; $q <= $dia_semana; $q++)
					echo("</td><td style='width: 100px'>");
			}
			echo("<div id='".$id_trabajo."' $tooltip onmouseover=\"manoOn(this);\" onmouseout=\"manoOff(0)\" style='background-color: $color; height: $alto; font-size: 10px; border: 1px solid black'>");
			echo("<b id='".$id_trabajo."'>$cod_asunto</b>");
			if($alto > 24)
				echo("<br />Hr:$duracion");
			echo("</div>");
			$dia_anterior  = $dia_semana;
		}
		echo("</td>");
		echo("</tr><tr>");
		for($i = 2; $i <= 8; $i++)
		{
		#$total[$i] = number_format($total[$i],2);
    	    $hora = floor($total[$i]);
			$minutos = number_format(($total[$i] - $hora)*60,0);
			#$minutos = number_format($minutos,0);
    	    if($minutos < 10)
        	    $minutos = "0$minutos";
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras() ) )
			{
				$dia_semana_decimal = UtilesApp::Time2Decimal( $hora.':'.$minutos.':00' );
					echo("
						<td style='width: 20%; border: 1px solid black; text-align:center;'>
						$dia_semana_decimal
						</td>
						");
			}
			else
			{ 
				echo("
					<td style='width: 20%; border: 1px solid black; text-align:center;'>
					$hora:$minutos
					</td>
					");
			}
		}
		echo("</tr>");
		echo("</table><br/>");
?>
<script>
	/* Array de los items del Menú */
	document.observe('dom:loaded', function(){
		var myMenuItems = [
		  {
		    name: 'Editar',
		    className: 'edit',
		    callback: function(e) {
		    	OpcionesTrabajo(e.target.id,'edit')
		    }
		  },{
		    name: 'Eliminar',
		    disabled: false,
		    className: 'delete',
		    callback: function(e) {
		      if( confirm('<?=__("¿Desea eliminar este trabajo?")?>') )
		      	OpcionesTrabajo(e.target.id,'eliminar');
		    }
		  },{
		    separator: true
		  },{
		    name: 'Cancelar',
		    className: 'cancel',
		    callback: function(e) {

		    }
		  }
		]

	/* Array para todos los trabajos ingresados */
	var arr_trabajos = new Array();
<?
	for($i = 0; $i < $lista->num; $i++)
	{
?>
		arr_trabajos[<?=$i?>] = <?=$lista->Get($i)->fields[id_trabajo]?>;
<?
	}
?>
	/*
		Inicializando Menú
		creando cada menú según cantidad de trabajos hayan ingresados
	*/
	var list_div = parseInt(<?=$lista->num;?>);
	for(i=0;i<list_div;i++)
	{
		new Proto.Menu({
	  selector: '#'+arr_trabajos[i], // context menu will be shown when element with id of "contextArea" is clicked
	  className: 'menu desktop', // this is a class which will be attached to menu container (used for css styling)
	  menuItems: myMenuItems // array of menu items
		})
	}
})
</script>
<?
	if( $j == count($usuarios)-1 && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
		echo "</td></tr></table>";
	} #END FOR Clientes
	
	
	$pagina->PrintBottom();

?>

<script>
/* Cambia semana */
function CambiaSemana( fecha )
{
	var form = $('form_semana');
	form.semana.value = fecha;
	var accion = 'resumen_semana.php?semana='+fecha;
	form.action = accion;
	form.target = '_self';
	form.submit();
}

/*
	Opcion menu lateral
	opcion->elimina; nuevo o '' ('' editar)
*/
function OpcionesTrabajo(id_trabajo, opcion )
{
	if(opcion == 'nuevo' || opcion == 'edit')
	{
		var id_edicion_trabajo = id_trabajo.split('-',1);
		nuevaVentana('Editar_Trabajo',550,350,'editar_trabajo.php?id_trabajo='+id_edicion_trabajo+'&popup=1&opcion='+opcion,'');
	}
	else
	{
		var form = document.getElementById('form_semana');
		if(id_trabajo)
		{
			form.opcion.value = opcion;
			form.action = '?id_trab='+id_trabajo;
			form.submit();
		}
	}
}
</script>
