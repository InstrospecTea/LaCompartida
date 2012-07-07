<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/classes/Semana.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/Asunto.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';

$sesion = new Sesion(array('PRO', 'REV', 'SEC'));
$pagina = new Pagina($sesion);
$pagina->titulo = __('Ingreso/Modificaci�n de') . ' ' . __('Trabajos');
$pagina->PrintTop($popup);

//Permisos
$params_array['codigo_permiso'] = 'PRO';
$p_profesional = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

$params_array['codigo_permiso'] = 'REV'; // permisos de consultor jefe
$p_revisor = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

$params_array['codigo_permiso'] = 'SEC';
$p_secretaria = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

if (!$id_usuario) {
    if ($p_profesional->fields['permitido']) {
        $id_usuario = $sesion->usuario->fields['id_usuario'];
    } else if ($p_secretaria->fields['permitido']) {
        $query = "SELECT usuario.id_usuario,
						CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
						as nombre
						FROM usuario
			          JOIN usuario_permiso USING(id_usuario)
                      JOIN usuario_secretario ON usuario_secretario.id_profesional = usuario.id_usuario 
                      WHERE usuario.visible = 1 AND 
                            usuario_permiso.codigo_permiso='PRO' AND 
                            usuario_secretario.id_secretario='" . $sesion->usuario->fields['id_usuario'] . "'
                      GROUP BY usuario.id_usuario ORDER BY nombre LIMIT 1";
        $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
        $temp = mysql_fetch_array($resp);
        $id_usuario = $temp['id_usuario'];
    }
    if (!$id_usuario) {
        $query = "SELECT usuario.id_usuario,
								CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
								as nombre
								FROM usuario
								JOIN usuario_permiso USING(id_usuario)
								WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'
								GROUP BY id_usuario ORDER BY nombre LIMIT 1";
        $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
        $temp = mysql_fetch_array($resp);
        $id_usuario = $temp['id_usuario'];
    }
}
// El objeto semana contiene la lista de colores por asunto de usuario de quien se define la semana
$objeto_semana = new Semana($sesion, $id_usuario);
if ($semana == "") {
    $semana2 = "CURRENT_DATE()";
    $sql_f = "SELECT DATE_ADD( CURDATE(), INTERVAL -  WEEKDAY(CURDATE())  DAY ) AS semana_inicio";
    $resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f, __FILE__, __LINE__, $sesion->dbh);
    list($semana_actual) = mysql_fetch_array($resp);
    $semana_anterior = date("d-m-Y", strtotime("$semana_actual-7 days"));
    $semana_siguiente = date("d-m-Y", strtotime("$semana_actual+7 days"));
} else {
    $semana2 = "'$semana'";
    $sql_f = "SELECT DATE_ADD( '" . $semana . "', INTERVAL - WEEKDAY('" . $semana . "')  DAY ) AS semana_inicio";

    $resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f, __FILE__, __LINE__, $sesion->dbh);
    list($semana_actual) = mysql_fetch_array($resp);
    $semana_anterior = date("d-m-Y", strtotime("$semana_actual-7 days"));
    $semana_siguiente = date("d-m-Y", strtotime("$semana_actual+7 days"));
}
if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))) {
    $select_codigo = "(SELECT c2.codigo_cliente_secundario FROM cliente as c2 WHERE c2.codigo_cliente=asunto.codigo_cliente) as codigo_cliente,asunto.codigo_asunto_secundario as codigo_asunto,";
} else {
    $select_codigo = "asunto.codigo_cliente,asunto.codigo_asunto,";
}
#se usa yearweek para ver por semana Y a�o cada trabajo esto soluciona el problema de la ultima
#y primera semana del a�o
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

$dias = array(__("Lunes"), __("Martes"), __("Mi�rcoles"), __("Jueves"), __("Viernes"), __("S�bado"), __("Domingo"));
$tip_anterior = Html::Tooltip("<b>" . __('Semana anterior') . ":</b><br>" . Utiles::sql3fecha($semana_anterior, '%d de %B de %Y'));
$tip_siguiente = Html::Tooltip("<b>" . __('Semana siguiente') . ":</b><br>" . Utiles::sql3fecha($semana_siguiente, '%d de %B de %Y'));
?> 	<center> <?php
#agregado para el nuevo select

if ($p_revisor->fields['permitido'])
    $where = "usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'";
else
    $where = "usuario_secretario.id_secretario = '" . $sesion->usuario->fields['id_usuario'] . "'
							OR usuario.id_usuario IN ('$id_usuario','" . $sesion->usuario->fields['id_usuario'] . "')";
$where .= " AND usuario.visible=1";
?>
	<script src="https://static.thetimebilling.com/contextmenu/jquery.contextMenu.js" type="text/javascript"></script>
		<link  href="https://static.thetimebilling.com/contextmenu/jquery.contextMenu.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript">
		
		
    
        function calcHeight(idIframe, idMainElm){
            ifr = $(idIframe);
            the_size = ifr.$(idMainElm).offsetHeight + 20;
            if( the_size < 250 ) the_size = 250;
            new Effect.Morph(ifr, {
                style: 'height:'+the_size+'px',
                duration: 0.2
            });
        }
 
                
 

	


		var diaid=0;
		var trabajoid=0;


        jQuery(document).ready(function() {
			 
		jQuery.contextMenu({
			selector: '.trabajoabierto', events: {
				show: function(opt) {
					 var $this = this;
					 trabajoid=$this.attr('id');
					}}, 
			callback: function(key, options) {		},
			items: {
				"edit": {	name: "Editar", 		icon: "edit"	, callback: function(key, options) {			OpcionesTrabajo(trabajoid,'','')			}	},
 				"paste": {name: "Ingresar como nueva hora", icon: "paste", callback: function(key, options) {		 OpcionesTrabajo(trabajoid,'nuevo','');		}	},
				"delete": {name: "Eliminar", icon: "delete", callback: function(key, options) {  
						    if( confirm('<?php echo __("�Desea eliminar este trabajo?") ?>') ) OpcionesTrabajo(trabajoid,'eliminar','');
						}	},
				"sep1": "---------",
				"quit": {name: "Cancelar", icon: "quit"}
			}
		});
					 
		jQuery.contextMenu({
			selector: '.trabajoacerrado', 
			events: {		
				show: function(opt) {		 var $this = this;		 trabajoid=$this.attr('id');		}}, 
				callback: function(key, options) {		},	
				items: {
				
 				"paste": {name: "Ingresar como nueva hora", icon: "paste", callback: function(key, options) { 	 OpcionesTrabajo(trabajoid,'nuevo','');			}	},
				"sep1": "---------",
				"quit": {name: "Cancelar", icon: "quit"}
			}
		});
		
			jQuery.contextMenu({
			selector: '#cabecera_dias td', 
			events: {
				show: function(opt) {
					 var $this = this;
					 diaid=$this.attr('id');
					}}, 
			callback: function(key, options) {
				var m = "global: " + key;
				 console.log(options);
					var iddia=diaid.replace('_','');
					console.log(iddia);
					 var fechadia=jQuery('#'+iddia);
					 var f_dia=jQuery(fechadia).val();
					OpcionesTrabajo('','',f_dia);
				},
			items: {
				"add": {
					name: "Nueva  hora", 
					icon: "add"
					
					
				} 
			}
		});
		
		 

	  var semana= jQuery('#semanactual').val();
            var usuario= jQuery('#id_usuario').val();
		 
			
 
            jQuery('.cambiasemana').click(function() {
                var semana=jQuery(this).val(); 
                var usuario= jQuery('#id_usuario').val();
				if(jQuery(this).attr('id')=='antsemana') {
					Refrescasemana(semana,usuario,null, 'left');
				} else {
					Refrescasemana(semana,usuario,null,'right');
				}
                
            });
        
            jQuery('#versemana').click(function() {
               var semana= jQuery('#semanactual').val();
                var usuario= jQuery('#id_usuario').val();
                Refrescasemana(semana,usuario);
            });
            jQuery('#id_usuario').change(function() {
           			  var semana= jQuery('#semanactual').val();
                var usuario= jQuery('#id_usuario').val();
                Refrescasemana(semana,usuario);
            });
            jQuery("#proxsemana").hover(function() {
                ddrivetip('<b>Pr�xima Semana</b><br/><br/>'+jQuery("#hiddensemanasiguiente").attr('rel'));
            },    function() {        hideddrivetip();    });
            jQuery("#antsemana").hover(function() {
                ddrivetip('<b>Semana Anterior</b><br/><br/>'+jQuery("#hiddensemanaanterior").attr('rel'));
            },    function() {        hideddrivetip();    });
    
            jQuery("#cabecera_dias td").hover(function() {
                jQuery(this).css({'background':'#DF9862'});
            },    function() {      jQuery(this).css({'background':'#FFF'});   });



            jQuery(window).load(function() {
				jQuery('#divsemana').html(DivLoading);
                Refrescasemana(semana,usuario); 
					
				
            });
        });
        function Refrescasemana(semana,usuario,eldiv,slide) {
         if(window.console) console.log(semana);
		 semanaplus=semana.split('-');
		 semana=semanaplus[2]+'-'+semanaplus[1]+'-'+semanaplus[0];
	   var dias=0;    
            var diaplus=dias+1;
            var fecha='';
		 var divsemana= eldiv ? jQuery('#'+eldiv) : jQuery('#divsemana');
		 
            jQuery.get('ajax/semana_ajax.php?popup=1&semana='+semana+'&id_usuario='+usuario, function(datos) {
               if(!slide) { 
				   data=datos ;
				   divsemana.html(data);
			   } else if(slide=='left') {
				   divsemana.css('left','-1150px');
				   data=jQuery('#lastweek').html();
				   divsemana.html(data).animate({left:0},1000);
			   } else if (slide=='right') {
				    divsemana.css({'left':'1150px'});
				   data=jQuery('#nextweek').html();
				   divsemana.html(data).animate({left:0},1000);
			   }
			   
					var nextweek=jQuery("#hiddensemanasiguiente").val();
					var lastweek=jQuery("#hiddensemanaanterior").val();
					 
               if(!eldiv) {
				   
				  jQuery("#proxsemana").val(nextweek);
			  	 jQuery("#antsemana").val(lastweek);
               

					jQuery("#celdastrabajo td").each(function() {
						dias++;
						fecha=jQuery('#dia'+(dias-1)).val();
						jQuery(this).attr({'id':'celda'+dias, 'class':'celdadias','rel':fecha});
					});
					for (diaplus=dias+1;diaplus<=7;diaplus=diaplus+1)             {
						fecha=jQuery('#dia'+(diaplus-1)).val();
						jQuery("#celdastrabajo").append('<td class="celdadias" width="14%" id="celda'+diaplus+'" rel="'+fecha+'"></td>');
					
				
				}
						var nextweek=jQuery("#hiddensemanasiguiente").val();
					var lastweek=jQuery("#hiddensemanaanterior").val();
				Refrescasemana(nextweek,usuario,'nextweek');  
				Refrescasemana(lastweek,usuario,'lastweek');
				 calendario(semana);
               
                jQuery('.trabajoabierto').draggable({cursor:'move', containment:'#contienehoras', revert:'true', helper:'clone'});
                jQuery('.trabajoabierto').dblclick(function() {
                    var idtrabajo=jQuery(this).attr('id') ;
                    OpcionesTrabajo(idtrabajo,'','');
					 
                });
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
				if(!eldiv) {
				jQuery('#hiddensemanasiguiente').droppable({ accept:'.cajatrabajo', addClasses:'false',
                  hoverClass: "ui-state-active",
				  drop: function (event,ui) {
                        var  cuando=jQuery(this).attr('title');
                        var  idtrabajo= ui.draggable.attr('id');
                        jQuery(ui.draggable).children('span').remove();
                        
						  
                        jQuery.post('editar_trabajo.php',{id_trabajo:idtrabajo, fecha:cuando, opcion:'cambiofecha',popup:1},function(data){
                            var arreglo=data.split('|');
                       if(window.console) console.log(arreglo);
						 jQuery('#divsemana #celdastrabajo td').first().append(ui.draggable);
						 jQuery('#proxsemana').click();
                        });
						
						
                    }
					 });
					 jQuery('#hiddensemanaanterior').droppable({ accept:'.cajatrabajo', addClasses:'false',
					 hoverClass: "ui-state-active",
					 
                    drop: function (event,ui) {
                       var  cuando=jQuery(this).attr('title');
                        var  idtrabajo= ui.draggable.attr('id');
                        jQuery(ui.draggable).children('span').remove();
                      
                        jQuery.post('editar_trabajo.php',{id_trabajo:idtrabajo, fecha:cuando, opcion:'cambiofecha',popup:1},function(data){
                            var arreglo=data.split('|');
                          if(window.console)  console.log(arreglo);
						     jQuery('#divsemana #celdastrabajo td').first().append(ui.draggable);
							  jQuery('#antsemana').click();
                        });
						
						   
						
                    }
					 });
				}
				
				 
            }
				          /*  jQuery('#lastweek').load('ajax/semana_ajax.php?popup=1&semana='+jQuery("#antsemana").val()+'&id_usuario='+usuario );
							 jQuery('#nextweek').load('ajax/semana_ajax.php?popup=1&semana='+jQuery("#proxsemana").val()+'&id_usuario='+usuario );*/
				 
			
            });
            
        }

        function Refrescar() {
            jQuery('#versemana').click();
        }
        function calendario(semana) {
    
            var arreglo=semana.split('-');
    
			jQuery('#semanactual').val(arreglo[2]+'-'+arreglo[1]+'-'+arreglo[0]);
        }

        function OpcionesTrabajo(id_trabajo, opcion, f_dia ) {
            if(opcion == 'nuevo') {
                jQuery('#asuntos').attr('src', 'editar_trabajo.php?opcion='+opcion+'&id_trabajo='+id_trabajo+'&popup=1');
           } else if(opcion == 'cancelar')  {
                jQuery('#asuntos').attr('src','editar_trabajo.php?id_trabajo=&popup=1');
            } else  {
                jQuery('#asuntos').attr('src','editar_trabajo.php?opcion='+opcion+'&id_trabajo='+id_trabajo+'&popup=1&fecha='+f_dia);
            }
        }


    </script>

    <table cellspacing=0 cellpadding=0 width=100%>
        <tr>
            <td align=center>
                <div id="Iframe" class="tb_base" style="width:750px;">
                    <iframe id='asuntos' name='asuntos' target="asuntos" onload="calcHeight(this.id, 'pagina_body');" id='asuntos' scrolling="no" src="editar_trabajo.php?popup=1&id_trabajo=<?php echo $id_trab ?>&opcion=<?php echo $opcion ?>" frameborder="0" style="width:80%; height:352px;"></iframe>
                </div>
                <br/>
            </td>
        </tr>
        <tr>
            <td align=center>
                <div class="tb_base" id="controlessemana" style="width: 750px;">
                    <table width='90%'>
                        <tr>
                            <td align='left' width='3%'> <?php if (UtilesApp::GetConf($sesion, 'UsaDisenoNuevo')) { ?>
                                    <input type="image" src='<?php echo Conf::ImgDir() . "/izquierda_nuevo.gif" ?>' class='mano_on cambiasemana' id="antsemana" value="">
<?php } else { ?>
                                    <img src='<?php echo Conf::ImgDir() . "/izquierda.gif" ?>' <?php echo $tip_anterior ?> class='mano_on' onclick="CambiaSemana('<?php echo $semana_anterior ?>')">
<?php } ?>
                            </td>
                            <td align='center'>
<?php
if ($p_revisor->fields['permitido']) {

    echo ( __('Usuario') . "&nbsp;");
    echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, 
							CONCAT_WS(' ', apellido1, apellido2,',',nombre) 
							as nombre FROM usuario 
							JOIN usuario_permiso USING(id_usuario)
							LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional 
							WHERE $where GROUP BY id_usuario ORDER BY nombre"
            , "id_usuario", $id_usuario, "onchange='Refrescar();'", '', "170");
} else {

    echo '<input type="hidden" id="id_usuario" value="' . $id_usuario . '"/>';
}
?>
                            </td>
                            <td align='right' id="printcalendar">
                                <input type="text" class="fechadiff" value="<?php echo ( $semana ? $semana : date('d-m-Y')); ?>" name="semana" id="semanactual" />
                            </td>
                            <td align ='left' width='19%'>
                                <input type='button' class='btn' value="Ver semana" id="versemana" >
                            </td>




                            <td align='right' width='3%'>
                                <?php if (UtilesApp::GetConf($sesion, 'UsaDisenoNuevo')) { ?>
                                    <input type="image" src='<?php echo Conf::ImgDir() . "/derecha_nuevo.gif" ?>'  class='mano_on cambiasemana'  id="proxsemana" value="">
                                <?php } else { ?>
                                    <img src='<?php echo Conf::ImgDir() . "/derecha.gif" ?>' <?php echo $tip_siguiente ?> class='mano_on' onclick="CambiaSemana('<?php echo $semana_siguiente ?>')">
                                <?php } ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <tr><td style="text-align:center;font-weight:bold;padding:10px;">Haga click con el bot�n derecho sobre alg�n trabajo para modificarlo</td></tr>
        <tr>
            <td style="text-align:center;">
				<div id="contienehoras" style="margin:auto;position:relative;width:750px;overflow-x: hidden;">
				<div class="tb_base" id="divsemana" style="width: 750px;position:relative;right:0;left:0;">
                    <div class="divloading">&nbsp;</div>
                </div>
				<div class="tb_base" id="nextweek" style=" position:absolute; right:-550px;top:0px;display:none;"></div>
				 <div class="tb_base" id="lastweek" style="position:absolute;left:-550px;top:0px;display:none;"></div>
				</div>
            </td>
        </tr>
    </table>
	
 
<?php
$pagina->PrintBottom();
