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
    $pagina->titulo = __('Ingreso/Modificación de').' '.__('Trabajos');
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
		if($p_profesional->fields['permitido']) {
			$id_usuario = $sesion->usuario->fields['id_usuario'];
		}
		else if($p_secretaria->fields['permitido']) {
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
		if( !$id_usuario ) {
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
	
#agregado para el nuevo select

	if($p_revisor->fields['permitido'])
		$where = "usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'";
	else
		$where = "usuario_secretario.id_secretario = '".$sesion->usuario->fields['id_usuario']."'
							OR usuario.id_usuario IN ('$id_usuario','" . $sesion->usuario->fields['id_usuario'] . "')";
	$where .= " AND usuario.visible=1";


    
    
    
?>
<script type="text/javascript">
    var arr_trabajos = new Array();
	function calcHeight(idIframe, idMainElm){
    ifr = $(idIframe);
    the_size = ifr.$(idMainElm).offsetHeight + 20;
    if( the_size < 250 ) the_size = 250;
    new Effect.Morph(ifr, {
        style: 'height:'+the_size+'px',
        duration: 0.2
    });
}
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
		];
                

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
	];
        
	


		


jQuery(document).ready(function() {
     var mes=parseInt(jQuery('#semana_Month_ID').val())+1;
           if (mes<=9) {
               var mestext='0'+mes.toString();
           } else {
               var  mestext=mes.toString();
           }
       var dia=jQuery('#semana_Day_ID').val();
       if (dia.length==1) dia='0'+dia;
       var semana= jQuery('#semana_Year_ID').val()+'-'+mestext+'-'+dia;
       var usuario= jQuery('#id_usuario').val();
       


    jQuery('.cambiasemana').click(function() {
       var semana=jQuery(this).val(); 
       var usuario= jQuery('#id_usuario').val();
       Refrescasemana(semana,usuario);
    });
        
    jQuery('#versemana').click(function() {
       var mes=parseInt(jQuery('#semana_Month_ID').val())+1;
       if (mes<=9) {
           var mestext='0'+mes.toString();
       } else {
           var  mestext=mes.toString();
       }
       var dia=jQuery('#semana_Day_ID').val();
       if (dia.length==1) dia='0'+dia;
       var semana= jQuery('#semana_Year_ID').val()+'-'+mestext+'-'+dia;
       var usuario= jQuery('#id_usuario').val();
      Refrescasemana(semana,usuario);
    });
        jQuery('#id_usuario').change(function() {
       var mes=parseInt(jQuery('#semana_Month_ID').val())+1;
       if (mes<=9) {
           var mestext='0'+mes.toString();
       } else {
           var  mestext=mes.toString();
       }
       var dia=jQuery('#semana_Day_ID').val();
       if (dia.length==1) dia='0'+dia;
       var semana= jQuery('#semana_Year_ID').val()+'-'+mestext+'-'+dia;
       var usuario= jQuery('#id_usuario').val();
       Refrescasemana(semana,usuario);
    });
    jQuery("#proxsemana").hover(function() {
        ddrivetip('<b>Próxima Semana</b><br/><br/>'+jQuery("#hiddensemanasiguiente").attr('rel'));
    },    function() {        hideddrivetip();    });
    jQuery("#antsemana").hover(function() {
        ddrivetip('<b>Semana Anterior</b><br/><br/>'+jQuery("#hiddensemanaanterior").attr('rel'));
    },    function() {        hideddrivetip();    });
    
     jQuery("#cabecera_dias td").hover(function() {
       jQuery(this).css({'background':'#DF9862'});
    },    function() {      jQuery(this).css({'background':'#FFF'});   });



    jQuery(window).load(function() {
       Refrescasemana(semana,usuario);    
    });
});
function Refrescasemana(semana,usuario) {
var dias=0;    
var diaplus=dias+1;
var fecha='';
    jQuery.get('ajax/semana_ajax.php?popup=1&semana='+semana+'&id_usuario='+usuario, function(data) {
               jQuery('#divsemana').html(data);
           jQuery("#proxsemana").val(jQuery("#hiddensemanasiguiente").val());
           jQuery("#antsemana").val(jQuery("#hiddensemanaanterior").val());
            calendario(semana);
            menues();

         jQuery("#celdastrabajo td").each(function() {
            dias++;
            fecha=jQuery('#dia'+(dias-1)).val();
            jQuery(this).attr({'id':'celda'+dias, 'class':'celdadias','rel':fecha});
          });
          for (diaplus=dias+1;diaplus<=7;diaplus=diaplus+1)             {
               fecha=jQuery('#dia'+(diaplus-1)).val();
                jQuery("#celdastrabajo").append('<td class="celdadias" width="14%" id="celda'+diaplus+'" rel="'+fecha+'"></td>');
          }
          jQuery('.cajatrabajo').draggable({cursor:'move', containment:'#celdastrabajo', revert:'true', helper:'clone'});
          jQuery('.celdadias').droppable({greedy:true, accept:'.cajatrabajo', addClasses:'false',
              drop: function (event,ui) {
                var  cuando=jQuery(this).attr('rel');
                var  idtrabajo= ui.draggable.attr('id');
		jQuery(ui.draggable).children('span').remove();
		jQuery(this).append(ui.draggable);
                jQuery.post('editar_trabajo.php',{id_trabajo:idtrabajo, fecha:cuando, opcion:'cambiofecha',popup:1},function(data){
                   var arreglo=data.split('|');
                   //Refrescasemana(arreglo[1],usuario);
		   
                });
              }
          });
              
     });
}

function Refrescar() {
 jQuery('#versemana').click();
}
function calendario(semana) {
    
    var arreglo=semana.split('-');
    
    jQuery('#semana_Year_ID').val(arreglo[0]);
    jQuery('#semana_Day_ID').val(parseInt(arreglo[2]));
    jQuery('#semana_Month_ID').val(parseInt(arreglo[1])-1).change();
}

function menues() {
    var indice=0;
    jQuery('.cajatrabajo').each(function() {
       arr_trabajos[indice]=jQuery(this).attr('id');
       indice++;
       
       
    });
    for(i=0;i<indice;i++)
	{
			new Proto.Menu({
		  selector: '#'+arr_trabajos[i], // context menu will be shown when element with id of "contextArea" is clicked
		  className: 'menu desktop',
		  menuItems: myMenuItems
		})
	}
    for(i=0;i<7;i++)
	{
			new Proto.Menu({
		  selector: '#dia_'+i, // context menu will be shown when element with id of "contextArea" is clicked
		  className: 'menu desktop', // this is a class which will be attached to menu container (used for css styling)
		  menuItems: MenuDias // array of menu items
		})
	}
    
}

function OpcionesTrabajo(id_trabajo, opcion, f_dia ) {
	if(opcion == 'nuevo')
		jQuery('#asuntos').attr('src', 'editar_trabajo.php?opcion='+opcion+'&id_trabajo='+id_trabajo+'&popup=1');
	else if(opcion == 'cancelar')
		jQuery('#asuntos').attr('src','editar_trabajo.php?id_trabajo=&popup=1');
	else
	{
		jQuery('#asuntos').attr('src','editar_trabajo.php?opcion='+opcion+'&id_trabajo='+id_trabajo+'&popup=1&fecha='+f_dia);
	}
}


</script>

<table cellspacing=0 cellpadding=0 width=100%>
	<tr>
		<td align=center>
			<div id="Iframe" class="tb_base" style="width:750px;">
			<iframe id='asuntos' name='asuntos' target="asuntos" onload="calcHeight(this.id, 'pagina_body');" id='asuntos' scrolling="no" src="editar_trabajo.php?popup=1&id_trabajo=<?=$id_trab?>&opcion=<?=$opcion?>" frameborder="0" style="width:80%; height:352px;"></iframe>
		  </div>
		  <br/>
		</td>
	</tr>
        <tr>
            <td align=center>
			<div class="tb_base" id="controlessemana" style="width: 750px;">
			<table width='90%'>
		<tr>
			<td align='left' width='3%'> <?
				if( UtilesApp::GetConf($sesion,'UsaDisenoNuevo')) { ?>
				<input type="image" src='<?=Conf::ImgDir()."/izquierda_nuevo.gif"?>' class='mano_on cambiasemana' id="antsemana" value="">
			<? } else { ?>
				<img src='<?=Conf::ImgDir()."/izquierda.gif"?>' <?=$tip_anterior?> class='mano_on' onclick="CambiaSemana('<?=$semana_anterior?>')">
			<? } ?>
				</td>
			 <td align='center'>
<?php if ($p_revisor->fields['permitido']) {

	echo ( __('Usuario') . "&nbsp;");
	echo Html::SelectQuery($sesion,
						"SELECT usuario.id_usuario, 
							CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
							as nombre FROM usuario 
							JOIN usuario_permiso USING(id_usuario)
							LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional 
							WHERE $where GROUP BY id_usuario ORDER BY nombre"
						,"id_usuario",$id_usuario,"onchange='Refrescar();'",'',"170");
} else {
    
  echo '<input type="hidden" id="id_usuario" value="'.$id_usuario.'"/>';    
}
?>
	</td>
	<td align='right' id="printcalendar">
		<?php echo Html::PrintCalendar('semana',$semana); ?>
	</td>
	<td align ='left' width='19%'>
		<input type='button' class='btn' value="Ver semana" id="versemana" >
	</td>
	

	

	<td align='right' width='3%'>
		<? if(UtilesApp::GetConf($sesion,'UsaDisenoNuevo') ) { ?>
			<input type="image" src='<?=Conf::ImgDir()."/derecha_nuevo.gif"?>'  class='mano_on cambiasemana'  id="proxsemana" value="">
		<? } else { ?>
			<img src='<?=Conf::ImgDir()."/derecha.gif"?>' <?=$tip_siguiente?> class='mano_on' onclick="CambiaSemana('<?=$semana_siguiente?>')">
		<? } ?>
	</td>
 </tr>
</table>
			</div>
		</td>
        </tr>
        <tr><td style="text-align:center;font-weight:bold;padding:10px;">Haga click con el botón derecho sobre algún trabajo para modificarlo</td></tr>
	<tr>
		<td align=center>
			<div class="tb_base" id="divsemana" style="width: 750px;">
			
			</div>
		</td>
	</tr>
</table>
<?
    $pagina->PrintBottom();
?>
