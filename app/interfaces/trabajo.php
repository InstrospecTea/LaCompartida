<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('PRO', 'REV', 'SEC'));
$pagina = new Pagina($sesion);
$Form = new Form;
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
		$query = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2, ',', nombre) AS nombre
			FROM usuario
				JOIN usuario_permiso USING(id_usuario)
				JOIN usuario_secretario ON usuario_secretario.id_profesional = usuario.id_usuario
			WHERE usuario.visible = 1 AND
				usuario_permiso.codigo_permiso = 'PRO' AND
				usuario_secretario.id_secretario = '{$sesion->usuario->fields['id_usuario']}'
			GROUP BY usuario.id_usuario ORDER BY nombre LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$temp = mysql_fetch_array($resp);
		$id_usuario = $temp['id_usuario'];
	}

	if (!$id_usuario) {
		$query = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2, ',', nombre) AS nombre
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
//$objeto_semana = new Semana($sesion, $id_usuario);
if ($semana == "") {
	$sql_f = "SELECT DATE_ADD(CURDATE(), INTERVAL - WEEKDAY(CURDATE()) DAY) AS semana_inicio";
	$resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f, __FILE__, __LINE__, $sesion->dbh);
	list($semana_actual) = mysql_fetch_array($resp);
	$semana_anterior = date("d-m-Y", strtotime("$semana_actual-7 days"));
	$semana_siguiente = date("d-m-Y", strtotime("$semana_actual+7 days"));
} else {
	$sql_f = "SELECT DATE_ADD('{$semana}', INTERVAL - WEEKDAY('{$semana}') DAY) AS semana_inicio";
	$resp = mysql_query($sql_f, $sesion->dbh) or Utiles::errorSQL($sql_f, __FILE__, __LINE__, $sesion->dbh);
	list($semana_actual) = mysql_fetch_array($resp);
	$semana_anterior = date("d-m-Y", strtotime("$semana_actual-7 days"));
	$semana_siguiente = date("d-m-Y", strtotime("$semana_actual+7 days"));
}

$dias = array(__("Lunes"), __("Martes"), __("Mi�rcoles"), __("Jueves"), __("Viernes"), __("S�bado"), __("Domingo"));
$tip_anterior = Html::Tooltip("<b>" . __('Semana anterior') . ":</b><br>" . Utiles::sql3fecha($semana_anterior, '%d de %B de %Y'));
$tip_siguiente = Html::Tooltip("<b>" . __('Semana siguiente') . ":</b><br>" . Utiles::sql3fecha($semana_siguiente, '%d de %B de %Y'));
?>

<style type="text/css">
.diasemana {
	float: left;
	display: inline-block;
	width: 13.6%;
	margin: 1px;
	border: 1px solid black;
	text-align: center;
}

.celdadias {
	float: left;
	display: inline-block;
	width: 13.93%;
	margin: 1px 0;
	border: 1px solid white;
	text-align: center;
	position: relative;
}

.totaldia {
	width: 98%;
	border: 1px solid black;
	text-align: center;
	position: absolute;
	bottom: -20px;
}
</style>

<link href="//static.thetimebilling.com/contextmenu/jquery.contextMenu.css" rel="stylesheet" type="text/css" />
<script src="//static.thetimebilling.com/contextmenu/jquery.contextMenu.js" type="text/javascript"></script>
<script src="//static.thetimebilling.com/js/bootstrap.min.js" type="text/javascript"></script>

<script type="text/javascript">
	function SecToTime(sec_numb) {
		var hours   = Math.floor(sec_numb / 3600);
		var minutes = Math.floor((sec_numb - (hours * 3600)) / 60);
		var seconds = sec_numb - (hours * 3600) - (minutes * 60);
		if (minutes < 10) {
			minutes = "0" + minutes;
		}

		var time = hours + ':' + minutes;
		return time;
	}

	function calcHeight(idIframe, idMainElm){
		ifr = $(idIframe);
		try {
			the_size = ifr.$(idMainElm).offsetHeight + 20;
			if (the_size < 250) the_size = 250;
			new Effect.Morph(ifr, {
				style: 'height:' + the_size + 'px',
				duration: 0.2
			});
		} catch(e) {
			// console.log(e);
		}
	}

	var diaid = 0;
	var trabajoid = 0;

	jQuery(document).ready(function() {
		jQuery.contextMenu({
			selector: '.trabajoabierto', events: {
				show: function(opt) {
					var $this = this;
					trabajoid = $this.attr('id');
				}
			},
			callback: function(key, options) {},
			items: {
				"edit": {
					name: "<?php echo __('Editar') ?>",
					icon: "edit",
					callback: function(key, options) {
						OpcionesTrabajo(trabajoid, '', '')
					}
				},
				"paste": {
					name: "<?php echo __('Ingresar como nueva hora') ?>",
					icon: "paste",
					callback: function(key, options) {
						OpcionesTrabajo(trabajoid,'nuevo','');
					}
				},
				"delete": {
					name: "<?php echo __('Eliminar') ?>",
					icon: "delete",
					callback: function(key, options) {
						if (confirm('<?php echo __("�Desea eliminar este trabajo?") ?>')) {
							OpcionesTrabajo(trabajoid,'eliminar','');
						}
					}
				},
				"sep1": "---------",
				"quit": {
					name: "<?php echo __('Cancelar') ?>",
					icon: "quit"
				}
			}
		});

		jQuery.contextMenu({
			selector: '.trabajoacerrado',
			events: {
				show: function(opt) {
					var $this = this;
					trabajoid = $this.attr('id');
				}
			},
			callback: function(key, options) {},
			items: {
				"paste": {
					name: "<?php echo __('Ingresar como nueva hora') ?>",
					icon: "paste",
					callback: function(key, options) {
						OpcionesTrabajo(trabajoid, 'nuevo', '');
					}
				},
				"sep1": "---------",
				"quit": {
					name: "Cancelar",
					icon: "quit"
				}
			}
		});

		jQuery.contextMenu({
			selector: '#cabecera_dias div',
			events: {
				show: function(opt) {
					var $this = this;
					diaid = $this.attr('id');
				}
			},
			callback: function(key, options) {
				var m = "global: " + key;
				var iddia = diaid.replace('_', '');
				var fechadia = jQuery('#' + iddia);
				var f_dia = jQuery(fechadia).val();
				OpcionesTrabajo('', '', f_dia);
			},
			items: {
				"add": {
					name: "<?php echo __('Nueva hora') ?>",
					icon: "add"
				}
			}
		});

		jQuery('#divsemana').on('dblclick', '.trabajoabierto', function() {
			var idtrabajo = jQuery(this).attr('id');
			OpcionesTrabajo(idtrabajo,'','');
		});

		var semana = jQuery('#semanactual').val();
		var usuario = jQuery('#id_usuario').val();

		jQuery('.cambiasemana').click(function() {
			var semana = jQuery(this).val();
			var usuario = jQuery('#id_usuario').val();
			if (jQuery(this).attr('id') == 'antsemana') {
				Refrescasemana(semana, usuario, null, 'left');
			} else {
				Refrescasemana(semana, usuario, null, 'right');
			}
		});

		jQuery('#versemana').click(function() {
			var semana = jQuery('#semanactual').val();
			var usuario = jQuery('#id_usuario').val();
			Refrescasemana(semana, usuario);
		});

		jQuery('#id_usuario').change(function() {
			var semana = jQuery('#semanactual').val();
			var usuario = jQuery('#id_usuario').val();
			Refrescasemana(semana, usuario);
		});

		jQuery("#proxsemana").hover(function() {
			ddrivetip('<b>Pr�xima Semana</b><br/><br/>' + jQuery("#hiddensemanasiguiente").attr('rel'));
		}, function() {
			hideddrivetip();
		});

		jQuery("#antsemana").hover(function() {
			ddrivetip('<b>Semana Anterior</b><br/><br/>' + jQuery("#hiddensemanaanterior").attr('rel'));
		}, function() {
			hideddrivetip();
		});

		jQuery("#cabecera_dias div").hover(function() {
			jQuery(this).css({'background':'#DF9862'});
		}, function() {
			jQuery(this).css({'background':'#FFF'});
		});

		jQuery('#asuntos').load(function() {
			ResizeFrame();
		});
	});
	function ResizeFrame() {
		frame = jQuery('#asuntos');
		frame.css('height', frame[0].contentWindow.document.body.offsetHeight + 'px');
	}

	function Refrescasemana(semana, usuario, eldiv, slide) {
		semanaplus = semana.split('-');
		semana = semanaplus[2] + '-' + semanaplus[1] + '-' + semanaplus[0];
		var dias = 0;
		var diaplus = dias + 1;
		var fecha = '';
		var divsemana = eldiv ? jQuery('#' + eldiv) : jQuery('#divsemana');

		jQuery.get('ajax/semana_ajax.php?popup=1&semana=' + semana + '&id_usuario=' + usuario, function(datos) {
			if (!slide) {
				divsemana.html('').append(datos);
			} else if (slide == 'left') {
				divsemana.html('').css('left', '-1150px').append(datos).animate({left: 0}, 1000);
				data = jQuery('#lastweek').html();
			} else if (slide=='right') {
				divsemana.html('').css({'left': '1150px'}).append(datos).animate({left: 0}, 1000);
				data = jQuery('#nextweek').html();
			}

			var nextweek = jQuery("#hiddensemanasiguiente").val();
			var lastweek = jQuery("#hiddensemanaanterior").val();
			var maxaltura = 0;

			jQuery('.semanacompleta').each(function() {
				var altura = jQuery(this).height() - 73;
				maxaltura = altura > maxaltura ? altura : maxaltura;
				jQuery(".celdadias", jQuery(this)).css({'height': altura});
			});

			jQuery("#contienehoras").css({'height': (maxaltura + 73)});

			for (diaplus = dias + 1; diaplus <= 7; diaplus = diaplus + 1) {
				fecha = jQuery('#dia' + (diaplus - 1)).val();
				jQuery("#celdadia" + (diaplus + 1)).attr('rel', fecha);
			}

			if (!eldiv) {
				jQuery("#proxsemana").val(nextweek);
				jQuery("#antsemana").val(lastweek);

				var nextweek = jQuery("#hiddensemanasiguiente").val();
				var lastweek = jQuery("#hiddensemanaanterior").val();

				Refrescasemana(nextweek,usuario,'nextweek');
				Refrescasemana(lastweek,usuario,'lastweek');
				calendario(semana);

				jQueryUI.done(function() {
					jQuery('.trabajoabierto').draggable({
						cursor: 'move',
						containment: '#contienehoras',
						revert: 'true',
						helper:'clone'
					});

					jQuery('.celdadias').droppable({
						greedy: true,
						accept: '.cajatrabajo',
						addClasses: 'false',
						drop: function (event,ui) {
							var cuadrodestino = jQuery(this);
							var cuando = cuadrodestino.attr('rel');
							var idtrabajo = ui.draggable.attr('id');
							jQuery(ui.draggable).children('span').remove();

							if (event.ctrlKey || event.altKey) {
								ui.draggable.addClass('clon');
								var Option = 'clonar';
							} else {
								var Option = 'cambiofecha';
							}

							jQuery.ajax({
								url: 'editar_trabajo.php',
								data: {
									id_trabajo: idtrabajo,
									fecha: cuando,
									opcion: Option,
									popup: 1
								},
								type:'POST'
							}).complete(function(dataresponse) {
								var data = dataresponse.responseText;
								var status = dataresponse.status;
								if (status == 200) {
									var arreglo = data.split('|');
									if (event.ctrlKey || event.altKey) {
										cuadrodestino.append(ui.draggable.clone());
										jQuery('.clon').draggable({
											cursor: 'move',
											containment: '#contienehoras',
											revert: 'true',
											helper: 'clone'
										}).attr({
											'alt': 'clonado',
											'id': arreglo[1]
										}).removeClass('clon');
									} else {
										cuadrodestino.append(ui.draggable);
									}

									var maxaltura = 0;

									jQuery('.totaldia').each(function() {
										var time = 0;
										var sumaaltura = 0;
										jQuery('.cajatrabajo', jQuery(this).parent()).each(function() {
											time = 1 * time + 1 * jQuery(this).attr('duracion');
											sumaaltura = 1 * sumaaltura + 1 * jQuery(this).height();
										});

										maxaltura = sumaaltura > maxaltura ? sumaaltura : maxaltura;
										jQuery(this).attr('duracion', time).attr('altura', sumaaltura).html(SecToTime(time));
									});

									jQuery(".celdadias").css({'height': maxaltura + 20});
									jQuery("#contienehoras").css({'height': maxaltura + 120});

									if (Option == 'clonar') {
										jQuery('#totalsemana').html(arreglo[2]);
										jQuery('#totalmes').html(arreglo[3]);
									}
								} else {
									jQuery('#respuesta').html('<div  class="alert alert-error">' + dataresponse.responseText + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
								}
							});
						}
					});

					jQuery('#hiddensemanasiguiente,#hiddensemanaanterior').droppable({
						accept: '.cajatrabajo',
						addClasses: 'false',
						hoverClass: "ui-state-active",
						drop: function (event,ui) {
							var cuando = jQuery(this).attr('title');
							var idtrabajo = ui.draggable.attr('id');
							jQuery(ui.draggable).children('span').remove();
							if(event.ctrlKey || event.altKey) {
								var Option = 'clonar';
							} else {
							jQuery(this).append(ui.draggable);
								var Option = 'cambiofecha';
							}
							jQuery.post('editar_trabajo.php', {
								id_trabajo: idtrabajo,
								fecha: cuando,
								opcion: Option,
								popup: 1
							}, function(data) {
								var arreglo = data.split('|');
								jQuery('#semanactual').val(cuando);
								jQuery('#versemana').click();
							});
						}
					});
				});
			}
		});

		jQuery('.pintame').each(function() {
			jQuery(this).css('background-color', window.top.s2c(jQuery(this).attr('rel')));
		});
	}

	function Refrescar() {
		jQuery('#versemana').click();
		jQuery('.pintame').each(function() {
			jQuery(this).css('background-color', window.top.s2c(jQuery(this).attr('rel')));
		});
	}

	function calendario(semana) {
		var arreglo=semana.split('-');
		jQuery('#semanactual').val(arreglo[2] + '-' + arreglo[1] + '-' + arreglo[0]);
	}

	function OpcionesTrabajo(id_trabajo, opcion, f_dia) {
		if (opcion == 'nuevo') {
			jQuery('#asuntos').attr('src', 'editar_trabajo.php?opcion=' + opcion + '&id_trabajo=' + id_trabajo + '&popup=1');
		} else if (opcion == 'cancelar') {
			jQuery('#asuntos').attr('src', 'editar_trabajo.php?id_trabajo=&popup=1');
		} else {
			jQuery('#asuntos').attr('src', 'editar_trabajo.php?opcion=' + opcion + '&id_trabajo=' + id_trabajo + '&popup=1&fecha=' + f_dia);
		}
	}
</script>

<table cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td align=center>
			<div id="Iframe" class="tb_base" style="width:750px;">
				<iframe id="asuntos" name="asuntos" target="asuntos" class="" scrolling="no" src="editar_trabajo.php?popup=1&id_trabajo=<?php echo $id_trab; ?>&opcion=<?php echo $opcion; ?>" frameborder="0" style="width:90%; height:370px;"></iframe>
			</div>
			<br/>
		</td>
	</tr>
		<tr>
			<td align=center>
				<div class="tb_base" id="controlessemana" style="width: 750px;">
					<table width='90%'>
						<tr>
							<td align='left' width='3%'>
								<?php if (Conf::GetConf($sesion, 'UsaDisenoNuevo')) { ?>
									<input type="image" src='<?php echo Conf::ImgDir() . "/izquierda_nuevo.gif"; ?>' class='mano_on cambiasemana' id="antsemana" value="">
								<?php } else { ?>
									<img src='<?php echo Conf::ImgDir() . "/izquierda.gif"; ?>' <?php echo $tip_anterior; ?> class='mano_on' onclick="CambiaSemana('<?php echo $semana_anterior; ?>')">
								<?php } ?>
							</td>
							<td align='center'><!-- Nuevo Select -->
								<?php
									if ($p_revisor->fields['permitido']) {
										$usuario = new UsuarioExt($sesion);
										echo (__('Usuario') . "&nbsp;");
										echo $Form->select('id_usuario', $usuario->get_usuarios_trabajo($id_usuario, $p_revisor->fields['permitido']), $id_usuario, array('empty' => FALSE, 'style' => 'width: 170px', 'onchange' => 'Refrescar()'));
									} else {
										echo '<input type="hidden" id="id_usuario" value="' . $id_usuario . '"/>';
									}
								?>
							</td>
							<td align='right' id="printcalendar">
								<input type="text" class="fechadiff" value="<?php echo ($semana ? $semana : date('d-m-Y')); ?>" name="semana" id="semanactual">
							</td>
							<td align ='left' width='19%'>
								<input type='button' class='btn' value="<?php echo __('Ver semana'); ?>" id="versemana">
							</td>
							<td align='right' width='3%'>
								<?php if (Conf::GetConf($sesion, 'UsaDisenoNuevo')) { ?>
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
	<tr>
		<td style="text-align: center; font-weight: bold; padding: 10px;">
			<?php echo __('Haga click con el bot�n derecho sobre alg�n trabajo para modificarlo'); ?>
		</td>
	</tr>
</table>

<div id="contienehoras" style="margin: auto; position: relative; width: 750px; overflow-x: hidden; text-align: center;">
	<div class="tb_base" id="divsemana" style="width: 750px; position: relative; right: 0; left: 0;">
		<div class="divloading">&nbsp;</div>
	</div>
	<div class="tb_base" id="nextweek" style="position: absolute; right: -550px; top: 0px; visibility: hidden; float: right;"></div>
	<div class="tb_base" id="lastweek" style="position: absolute; left: -550px; top: 0px; visibility: hidden; float: left;"></div>
</div>

<div id="respuesta"></div>

<?php $pagina->PrintBottom(); ?>
