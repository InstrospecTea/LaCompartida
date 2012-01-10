<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/classes/Semana.php';
    require_once Conf::ServerDir().'/classes/InputId.php';
    require_once Conf::ServerDir().'/classes/Trabajo.php';
    require_once Conf::ServerDir().'/classes/Asunto.php';
		require_once Conf::ServerDir().'/classes/UtilesApp.php';

    $sesion = new Sesion(array('PRO','REV','SEC'));
    $pagina = new Pagina($sesion);
    $pagina->titulo = __('Modificación de').' '.__('Trabajo');
    $pagina->PrintTop($popup);
    
	//Permisos
	$params_array['codigo_permiso'] = 'PRO';
	$p_profesional = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	
	$params_array['codigo_permiso'] = 'REV';// permisos de consultor jefe
	$p_revisor = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
        
        $params_array['codigo_permiso'] = 'SEC';
	$p_secretaria = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	
	if(!$id_usuario)
	{
		if($p_profesional->fields['permitido'])
			$id_usuario = $sesion->usuario->fields['id_usuario'];
                else if($p_secretaria->fields['permitido'])
                {
                        $query = "SELECT usuario.id_usuario,
								CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
								as nombre
								FROM usuario
                                                                JOIN usuario_permiso USING(id_usuario)
                                                                JOIN usuario_secretario ON usuario_secretario.id_profesional = usuario.id_usuario 
                                                                WHERE usuario.visible = 1 AND 
                                                                      usuario_permiso.codigo_permiso='PRO' AND 
                                                                      usuario_secretario.id_secretario='".$sesion->usuario->fields['id_usuario']."'
                                                                GROUP BY usuario.id_usuario ORDER BY nombre LIMIT 1";
                        $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$temp=mysql_fetch_array($resp);
			$id_usuario=$temp['id_usuario'];
                }
		else
		{
			$query = "SELECT usuario.id_usuario,
								CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
								as nombre
								FROM usuario
								JOIN usuario_permiso USING(id_usuario)
								WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'
								GROUP BY id_usuario ORDER BY nombre LIMIT 1";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$temp=mysql_fetch_array($resp);
			$id_usuario=$temp['id_usuario'];
		}
	}
	// El objeto semana contiene la lista de colores por asunto de usuario de quien se define la semana
	$objeto_semana = new Semana($sesion, $id_usuario);
	if($semana == "")
	{
		$semana2 = "CURRENT_DATE()";
		$sql_f = "SELECT DATE_ADD( CURDATE(), INTERVAL -  WEEKDAY(CURDATE())  DAY ) AS semana_inicio";
		$resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f,__FILE__,__LINE__,$sesion->dbh);
		list($semana_actual) = mysql_fetch_array($resp);
		$semana_anterior = date("Y-m-d",strtotime("$semana_actual-7 days"));
		$semana_siguiente = date("Y-m-d",strtotime("$semana_actual+7 days"));
	}
	else
	{
		$semana2 = "'$semana'";
		$sql_f = "SELECT DATE_ADD( '".$semana."', INTERVAL - WEEKDAY('".$semana."')  DAY ) AS semana_inicio";
		
                $resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f,__FILE__,__LINE__,$sesion->dbh);
		list($semana_actual) = mysql_fetch_array($resp);
		$semana_anterior = date("Y-m-d",strtotime("$semana_actual-7 days"));
		$semana_siguiente = date("Y-m-d",strtotime("$semana_actual+7 days"));
	}
	if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
	{
		$select_codigo="(SELECT c2.codigo_cliente_secundario FROM cliente as c2 WHERE c2.codigo_cliente=asunto.codigo_cliente) as codigo_cliente,asunto.codigo_asunto_secundario as codigo_asunto,";
	}
	else
	{
		$select_codigo="asunto.codigo_cliente,asunto.codigo_asunto,";
	}
	#se usa yearweek para ver por semana Y año cada trabajo esto soluciona el problema de la ultima
	#y primera semana del año
	$query = "SELECT $select_codigo asunto.glosa_asunto,trabajo.duracion,trabajo.fecha,trabajo.id_trabajo, trabajo.descripcion
				,(SELECT c1.glosa_cliente FROM cliente AS c1 WHERE c1.codigo_cliente=asunto.codigo_cliente) as glosa_cliente
				, TIME_TO_SEC(duracion)/90 as alto, DAYOFWEEK(fecha) AS dia_semana,trabajo.cobrable
				 FROM trabajo 
				 JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
					WHERE
					trabajo.id_usuario = '$id_usuario' 
					AND YEARWEEK(fecha,1) = YEARWEEK($semana2,1)
					ORDER BY fecha,id_trabajo";
	
        $lista = new ListaTrabajos($sesion, "", $query);

	$dias = array(__("Lunes"), __("Martes"), __("Miércoles"), __("Jueves"), __("Viernes"), __("Sábado"),__("Domingo"));
	$tip_anterior = Html::Tooltip("<b>".__('Semana anterior').":</b><br>".Utiles::sql3fecha($semana_anterior,'%d de %B de %Y'));
	$tip_siguiente = Html::Tooltip("<b>".__('Semana siguiente').":</b><br>".Utiles::sql3fecha($semana_siguiente,'%d de %B de %Y'));
	?> 	<center> <?
	echo("<strong>".__('Haga clic en el botón derecho sobre algún trabajo para modificarlo')."</strong><br />");
	
#agregado para el nuevo select

	if($p_revisor->fields['permitido'])
		$where = "usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'";
	else
		$where = "usuario_secretario.id_secretario = '".$sesion->usuario->fields['id_usuario']."'
							OR usuario.id_usuario IN ('$id_usuario','" . $sesion->usuario->fields['id_usuario'] . "')";
	$where .= " AND usuario.visible=1";


?>
   
   <script type="text/css">
			body {
				background: #E0E0E0;
			}
   </script>
   
<form method='post' name='form_semana' id='form_semana'>
	<table width='90%'>
		<tr>
			<td align='left' width='3%'> <?
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) { ?>
				<img src='<?=Conf::ImgDir()."/izquierda_nuevo.gif"?>' <?=$tip_anterior?> class='mano_on' onclick="CambiaSemana('<?=$semana_anterior?>')">
			<? } else { ?>
				<img src='<?=Conf::ImgDir()."/izquierda.gif"?>' <?=$tip_anterior?> class='mano_on' onclick="CambiaSemana('<?=$semana_anterior?>')">
			<? } ?>
				</td>
			 
<?
if ($p_revisor->fields['permitido'])
{
?>	
	<td align='center' width='45%'>
<?
	echo ( __('Usuario') . "&nbsp;");
	echo Html::SelectQuery($sesion,
						"SELECT usuario.id_usuario, 
							CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
							as nombre FROM usuario 
							JOIN usuario_permiso USING(id_usuario)
							LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional 
							WHERE $where GROUP BY id_usuario ORDER BY nombre"
						,"id_usuario",$id_usuario,"onchange='Refrescar(this.value,form.semana.value);'",'',"170");
?>
	</td>
	<td align='right' width='30%'>
		<?echo Html::PrintCalendar('semana',$semana);?>
	</td>
	<td align ='left' width='19%'>
		<input type='button' class='btn' value="Ver semana" onclick="CambiaSemana(form.semana.value)">
	</td>
	
<?
}
else
{
?>
	<td align='right' width='47%'>
		<?echo Html::PrintCalendar('semana',$semana);?>
	</td>
	<td align ='left' width='47%'>
		<input type='button' class='btn' value='Ver semana' onclick="CambiaSemana(form.semana.value)">
	</td>
<?
}
?>
	<td align='right' width='3%'>
		<? if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) { ?>
			<img src='<?=Conf::ImgDir()."/derecha_nuevo.gif"?>' <?=$tip_siguiente?> class='mano_on' onclick="CambiaSemana('<?=$semana_siguiente?>')">
		<? } else { ?>
			<img src='<?=Conf::ImgDir()."/derecha.gif"?>' <?=$tip_siguiente?> class='mano_on' onclick="CambiaSemana('<?=$semana_siguiente?>')">
		<? } ?>
	</td>
 </tr>
</table>


<?
	echo("<table style='width:600px'>");
	
	$horas_mes_consulta = UtilesApp::GetConf($sesion, 'UsarHorasMesConsulta');
?>
    <tr>
    		<td align='left' colspan='4'>
        	<?=__('Semana del');  ?>:
					<b><?=$semana2 != '' ? Utiles::sql3fecha($semana_actual,'%d de %B de %Y') : Utiles::sql3fecha(date('Y-m-d'),'%d de %B de %Y') ?></b>
        </td>
        
        <td align='right' colspan='2'>
        	<?=$horas_mes_consulta ? __('Total mes') : __('Total mes actual')?>:
        </td>
        <td style="vertical-align: middle">
<?
$horas_trabajadas_mes = $sesion->usuario->HorasTrabajadasEsteMes($id_usuario, 'horas_trabajadas', $horas_mes_consulta ? $semana_actual : '');
?>
            <strong><?=$horas_trabajadas_mes?></strong>
		</td>
    </tr>
<?
	echo("<tr>");
	$fecha_dia = Utiles::sql2date($semana_actual);
	
        for($i = 0; $i < 7; $i++)
	{
		$dia_de_mes = date("j",strtotime(Utiles::add_date($semana_actual,$i)));
		//echo $semana_actual.' '.$fecha_dia.' '.$dia_de_mes;
                $mouse_over = 'onmouseover = "this.style.background=\'#DF9862\'"';
		$mouse_out = 'onmouseout = "this.style.background=\'#FFFFFF\'"';
		echo("
			<td width=14% style='border: 1px solid black; text-align:center;' id='dia_$i' ".$mouse_over." ".$mouse_out.">
				<input type=hidden name='dia$i' id='dia$i' value=".$fecha_dia.">
				$dias[$i] $dia_de_mes
			</td>
			");
		$fecha_dia = date("d-m-Y",strtotime("$fecha_dia+1 days"));
	}
	echo("</tr>");
	echo("<tr>");
	$dia_anterior=2;
	for($i = 0; $i < $lista->num; $i++)
	{
		$asunto = new Asunto($sesion);
		if($i == 0) 
			echo("<td width=14%>");  

              
        $img_dir = Conf::ImgDir();
		
		$alto = max($lista->Get($i)->fields[alto],12)."px";
		$cod_asunto = $lista->Get($i)->fields[codigo_asunto];
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
			$cod_asunto_color = $asunto->CodigoSecundarioACodigo($cod_asunto);
		else
			$cod_asunto_color = $cod_asunto;
		$cliente = $lista->Get($i)->fields[codigo_cliente];
		$dia_semana = $lista->Get($i)->fields[dia_semana];
		
		$t = new Trabajo($sesion);
		$t->Load($lista->Get($i)->fields[id_trabajo]);
		if($t->Estado() == 'Cobrado')
			$cobrado = true;
		else
			$cobrado = false;

       // if($dia_semana == 1)             $dia_semana = 8;
           
		$duracion = $lista->Get($i)->fields[duracion];
		//echo $duracion;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) ) 
		{
			list($hh,$mm,$ss) = split(":",$duracion);
			$duracion = UtilesApp::Time2Decimal( $duracion );
		}
		else
		{
			list($hh,$mm,$ss) = split(":",$duracion);
			$duracion = "$hh:$mm";
		}
		$fecha = $lista->Get($i)->fields[fecha];
		
		if($lista->Get($i)->fields[cobrable] == 0 || $lista->Get($i)->fields[cobrable] == 2)
		{
			$no_cobrable = __('No cobrable');
			$color = '#FFFFFF';
		}
		else
		{
			$no_cobrable = '';
			$color = $objeto_semana->colores[$cod_asunto_color];
			if($color == '')
				$color = '#E8E7D9';
		}

		$total[$dia_semana]  += $hh + $mm/60; 
#		$total[$dia_semana] += ($alto/40);

		$descripcion = nl2br(str_replace("'","`",$lista->Get($i)->fields['descripcion']));
		$id_trabajo = $lista->Get($i)->fields[id_trabajo];
		$tooltip = Html::Tooltip("<b>".__('Cliente')."(".$lista->Get($i)->fields[codigo_cliente]."):</b><br>".$lista->Get($i)->fields[glosa_cliente]."<br><b>".__('Asunto')."(".$lista->Get($i)->fields[codigo_asunto]."):</b><br>".$lista->Get($i)->fields[glosa_asunto]."<br /><b>".__('Duración').":</b><br>".$duracion."<br /><b>".__('Descripción').":</b><br>".$descripcion."<br><b>".$no_cobrable."</b>");
		if($dia_anterior != $dia_semana)
		{
			for($q = $dia_anterior+1; $q <= $dia_semana; $q++)
				echo("</td><td width=14%>");
		}	
		#onclick=\"relocate($id_trabajo,'".$semana."')\"
		echo("<div id='".$id_trabajo."' $tooltip onmouseover=\"manoOn(this);\" onmouseout=\"manoOff(0)\"  style='background-color: $color; height: $alto; font-size: 10px; border: 1px solid black'>"); 
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
		if($minutos==60)
		{
			$minutos=0;
			$hora+=1;
		}
		#$minutos = number_format($minutos,0);
        if($minutos < 10)
            $minutos = "0$minutos";
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' ) || ( method_exists('Conf','TipoIngresoHoras') && Conf::TipoIngresoHoras()=='decimal' ) ) 
		{
				$dia_semana_decimal = UtilesApp::Time2Decimal( $hora.':'.$minutos.':00' );
				echo("
					<td width=14% style='border: 1px solid black; text-align:center;'>
						$dia_semana_decimal
					</td>
					");
		}
		else
		{
			echo("
				<td width=14% style='border: 1px solid black; text-align:center;'>
					$hora:$minutos
				</td>
				");
		}
	}
	echo("</tr>");
	?>
	<tr>
	<td align='right' colspan='6'>
        		<?=__('Total semana')?>:
        </td>
        <td style="vertical-align: middle">
<?
$horas_trabajadas_semana = $sesion->usuario->HorasTrabajadasEsteSemana($id_usuario,$semana_actual);
?>
            <strong><?=$horas_trabajadas_semana?></strong>
		</td>
		</tr>
		<?
	echo("</table>");
	
	echo("</form>");
#	echo(InputId::Javascript($sesion));
?>
</center>
<script>
	/* Array de los items del Menú */
	document.observe('dom:loaded', function(){
	var myMenuItems = [
	  {
	  	name: 'Ingresar como nueva hora',
	    className: 'new', 
	    callback: function(e) {
	      OpcionesTrabajo(e.target.id,'nuevo','');
	    }
	  },{
	    name: 'Editar',
	    className: 'edit', 
	    callback: function(e) {
	    	OpcionesTrabajo(e.target.id,'','')
	    }
	  },{	    
	    name: 'Eliminar',
	    disabled: false,
	    className: 'delete',
	    callback: function(e) {
	      if( confirm('<?=__("¿Desea eliminar este trabajo?")?>') )
	      	OpcionesTrabajo(e.target.id,'eliminar','');
	    }
	  },{
	    separator: true
	  },{
	    name: 'Cancelar',
	    className: 'cancel',
	    callback: function(e) {
					OpcionesTrabajo('','cancelar');
	    }
	  }
	]
	
	/* Array para todos los trabajos ingresador */
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
		  className: 'menu desktop',
		  menuItems: myMenuItems
		})
	}
})

/* Opciones menu para los días */
	document.observe('dom:loaded', function()
	{
		var MenuDias = [
	  	{
	  		name: 'Nueva hora',
	    	className: 'new', 
	    	callback: function(e) {
	    		var fecha = e.target.id.split('_',2);
	    		var fecha_id = fecha[0]+''+fecha[1];
	    		var f_dia = $F(fecha_id);
					OpcionesTrabajo('','',f_dia);
	    	}
	  	}
		]
		
		/*Menu para cada día de la semana*/
		for(i=0;i<7;i++)
		{
				new Proto.Menu({
			  selector: '#dia_'+i, // context menu will be shown when element with id of "contextArea" is clicked
			  className: 'menu desktop', // this is a class which will be attached to menu container (used for css styling)
			  menuItems: MenuDias // array of menu items
			})
		}
	})
</script>
<script>
function relocate(id_trabajo,semana)
{
	var string = new String(top.location);
	if(string.search('trabajo.php') > 0)//Si la página está siendo llamada desde trabajo.php 
		top.location='trabajo.php?opcion=editar&id_trab='+id_trabajo+'&semana='+semana;
	else
		self.location='trabajo.php?opcion=editar&id_trab='+id_trabajo+'&semana='+semana;
}
//La funcion Refrescar solo debe estar presente cuando el usuario sea revisor 
<?
if ($p_revisor->fields['permitido'])
{
?>
	function Refrescar(id_usu ,semana)
	{
		var form = $('form_semana');
		form.semana.value = semana;
		//alert(semana);
		//alert("semana.php?popup=1&id_usuario=" + id_usu + "&semana=");
		self.location.href='semana.php?popup=1&id_usuario='+ id_usu+'&semana='+semana;	


	}
<?
}
?>

/* 
	Opcion menu lateral 
	opcion->elimina; nuevo o '' ('' editar)
	f_dia->fecha para menu sobre dias semana
*/
function OpcionesTrabajo(id_trabajo, opcion, f_dia )
{
	//nuevaVentana('Editar_Trabajo',550,450,'editar_trabajo.php?id_trab='+id_trabajo+'&popup=1&opcion='+opcion,'');
	if(opcion == 'nuevo')
		top.asuntos.location = 'editar_trabajo.php?opcion='+opcion+'&id_trabajo='+id_trabajo+'&popup=1';
	else if(opcion == 'cancelar')
		top.asuntos.location = 'editar_trabajo.php?id_trabajo=&popup=1';
	else
	{
		top.asuntos.location = 'editar_trabajo.php?opcion='+opcion+'&id_trabajo='+id_trabajo+'&popup=1&fecha='+f_dia;
	}
}

/* Cambia semana */
function CambiaSemana( fecha )
{
	var form = $('form_semana');
	form.semana.value = fecha;
<?
if ($p_revisor->fields['permitido'])
{
?>
	var sel_usu = document.getElementById('id_usuario');
	//alert(sel_usu);
	//var index = sel_usu.selectedIndex;
	var sel_usu_val = sel_usu.value;
	//alert(sel_usu_val);
	var url = "semana.php?popup=1&semana="+fecha+"&id_usuario="+sel_usu_val;
	/*var accion = 'semana.php?popup=1';
	form.action = accion;
	form.target = '_self';
	form.submit();*/
<?
}
else
{
?>
	var url="semana.php?popup=1&semana="+fecha+"&id_usuario="+<?=$id_usuario?>;
<?
}
?>
self.location.href = url;
}
</script>
<?
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
