<?php
	require_once dirname(__FILE__) . '/../conf.php';

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);
	$pagina->titulo = __('Administración') . ' ' . __('Usuarios');

	$esRut = strtolower(Conf::GetConf($sesion, 'NombreIdentificador')) == 'rut';

	if ($desde == '') {
		$desde = 0;
	}

	if ($x_pag == '') {
		$x_pag = 20;
	}

	if ($orden == '') {
		$orden = 'apellido1';
	}

	if ($opc == 'eliminado') {
		$pagina->AddInfo(__('Usuario') . ' ' . __('eliminado con éxito'));
	} else if ($opc == 'cancelar') { // Opción cancelar
		$pagina->Redirect('usuario_paso1.php');
	} else if ($opc == 'edit') { // Opción editar
		if ($cambiar_alerta_diaria == 'on') {
			if ($alerta_diaria == 'on') {
				$alerta_diaria = 1;
			} else {
				$alerta_diaria = 0;
			}

			if ($retraso_max == '') {
				$retraso_max = 0;
			} else {
				$retraso_max = str_replace(',', '.', $retraso_max);
				$retraso_max = number_format($retraso_max, Conf::GetConf($sesion, 'CantidadDecimalesIngresoHoras'), '.', '');
			}
		}

		if ($cambiar_restriccion_diario == 'on') {
			if ($restriccion_diario == '') {
				$restriccion_diario = 0;
			} else {
				$restriccion_diario = str_replace(',', '.', $restriccion_diario);
				$restriccion_diario = number_format($restriccion_diario, Conf::GetConf($sesion, 'CantidadDecimalesIngresoHoras'), '.', '');
			}
		}

		if ($cambiar_alerta_semanal == 'on') {
			if ($alerta_semanal == 'on') {
				$alerta_semanal = 1;
			} else {
				$alerta_semanal = 0;
			}
			if ($restriccion_max == '') {
				$restriccion_max = 0;
			}
			if ($restriccion_min == '') {
				$restriccion_min = 0;
			}
		}

		if ($cambiar_restriccion_mensual == 'on') {
			if ($restriccion_mensual == '') {
				$restriccion_mensual = 120;
			}
		}

		if ($cambiar_dias_ingreso_trabajo == 'on') {
			if ($dias_ingreso_trabajo == '') {
				$dias_ingreso_trabajo = 7;
			}
		}

		//Actualizar alerta de Usuario
		$datos = compact('alerta_diaria', 'alerta_semanal', 'retraso_max', 'restriccion_max', 'restriccion_min', 'restriccion_mensual', 'dias_ingreso_trabajo', 'restriccion_diario');
		$query3 = 'SELECT id_usuario FROM usuario';
		$usuarios = $sesion->pdodbh->query($query3);

		$usuario = new UsuarioExt($sesion, $rut_limpio);
		while ($usr = $usuarios->fetch(PDO::FETCH_ASSOC)) {
			$usuario->LoadId($usr['id_usuario']);
			$usuario->Guardar($datos, $pagina);
		}

		if ($alerta_diaria == 0) {
			$alerta_diaria = '';
		}
		if ($alerta_semanal == 0) {
			$alerta_semanal = '';
		}
	}

	$pagina->PrintTop();
	$tooltip_text = __('Para agregar un nuevo usuario ingresa su ' . Conf::GetConf($sesion, 'NombreIdentificador') . ' aquí.');
	$Html = new \TTB\Html();
?>

<style type="text/css">
	@import "//static.thetimebilling.com/css/jquery.dataTables.css";

	#tablapermiso {
		border-spacing: 0;
		border-collapse: collapse;
	}

	#tablapermiso th {
		font-size: 10px;
	}

	.dataTables_paginate {
		clear: both;
		margin: -20px 350px 15px 0;
		width: 370px;
		vertical-align: middle;
	}

	.dttnombres, .dttactivo {
		text-align: left;
		font-size: 10px;
		white-space: nowrap;
	}

	.dttpermisos .DataTables_sort_icon, .dttactivo .DataTables_sort_icon {
		display: none;
	}

	.activo, .usuarioinactivo, .usuarioactivo {
		float: left;
		display: inline;
	}

	.inactivo {
		opacity: 0.4;
	}

	.inactivo td {
		background: #F0F0F0;
	}

	#contienefiltro {
		display: inline-block;
		margin-bottom: -8px;
	}

	#tablapermiso_paginate .fg-button {
		padding: 0 5px;
	}

	#tablapermiso_paginate .first, #tablapermiso_paginate .last {
		display: none;
	}

	#tablapermiso  tbody tr.odd {
		background-color: #fff !important;
	}

	#tablapermiso  tbody tr.even {
		background-color: #EFE !important;
	}

	td.sorting_1 {
		background: transparent !important;
	}
</style>

<table width="96%" align="left">
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">
			<table class="info" style="width:100%">
				<tr>
					<td colspan="2" style="text-align:left">
						<b><?php echo __('Administración de cupos para usuarios activos en el sistema'); ?>:</b>
					</td>
				</tr>
				<tr>
					<td width="20">&nbsp;</td>
					<td style="text-align:left">
						<?php echo __('Estimado') . ' ' . $sesion->usuario->fields['nombre'] . ' ' . $sesion->usuario->fields['apellido1']; ?>,
						<?php echo __('a continuación se detalla su cupo actual de usuarios contratados en el sistema'); ?>.
					</td>
				</tr>
				<tr>
					<td width="20">&nbsp;</td>
					<td style="text-align:left">
						<ul>
							<li><?php echo __('Usuarios activos con perfil'); ?> <b><?php echo __('Profesional'); ?></b>: <?php echo Conf::GetConf($sesion, 'CupoUsuariosProfesionales'); ?></li>
							<li><?php echo __('Usuarios activos con perfil'); ?> <b><?php echo __('Administrativos'); ?></b>: <?php echo Conf::GetConf($sesion, 'CupoUsuariosAdministrativos'); ?></li>
						</ul>
					</td>
				</tr>
				<tr>
					<td width="20">&nbsp;</td>
					<td style="text-align:left">
						<?php echo __('Si desea aumentar su cupo debe contactarse con <a href="mailto:areacomercial@lemontech.cl">areacomercial@lemontech.cl</a> o en su defecto puede desactivar usuarios para habilitar cupos'); ?>.
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">
			<form action="usuario_paso2.php" method="post" <?php if ($esRut) { echo 'onsubmit="return ValidarRut(this.rut.value, this.dv_rut.value);"'; } ?> >
				<br class="clearfix"/>
				<br>
				<table  width="100%" class="tb_base">
					<tr>
						<td valign="top" class="subtitulo" align="left" colspan="2">
							<?php echo __('Ingrese') . ' ' . Conf::GetConf($sesion, 'NombreIdentificador') . ' ' . __('del usuario'); ?>:
							<hr class="subtitulo_linea_plomo"/>
						</td>
					</tr>
					<tr>
						<td valign="top" class="texto" align="right">
							<strong><?php echo Conf::GetConf($sesion, 'NombreIdentificador'); ?></strong>
						</td>
						<td valign="top" class="texto" align="left">
							<?php if ($esRut) { ?>
								<input type="text" name="rut" value="" size="10" onMouseover="ddrivetip('<?php echo $tooltip_text ?>')" onMouseout="hideddrivetip()" />-<input type="text" name="dv_rut" value="" maxlength=1 size="1" />
							<?php } else { ?>
								<input type="text" name="rut" value="" size="17" onMouseover="ddrivetip('<?php echo $tooltip_text ?>')" onMouseout="hideddrivetip()" />
							<?php } ?>
							&nbsp;&nbsp;&nbsp;
							<?php if ($sesion->usuario->fields['id_visitante'] == 0) { ?>
								<input type="submit" class="botonizame" name="boton" value="<?php echo __('Aceptar'); ?>" />
							<?php } else { ?>
								<input type="button" class="botonizame" name="boton" value="<?php echo __('Aceptar'); ?>" onclick="alert('Usted no tiene derecho para agegar un usuario nuevo');" />
							<?php } ?>
						</td>
					</tr>
				</table>
				<br class="clearfix"/>
			</form>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>
			<table width="100%" class="tb_base">
				<tr>
					<td>
						<table width="100%">
							<tr>
								<td valign="top" class="subtitulo" align="left" colspan="2">
									<?php echo __('Modificacion de Datos para todos los usuarios') ?>:
									<hr class="subtitulo_linea_plomo"/>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<form name="form_usuario" method="post" enctype="multipart/form-data">
							<input type="hidden" name="opc" value="edit" />
							<fieldset class="table_blanco">
								<legend><?php echo __('Restricciones y alertas') ?></legend>
								<table width="100%">
									<tr>
										<td width=38% align="right"></td>
										<td width=10% align="right"></td>
										<td width=15% align="left"></td>
										<td width=20% align="right"></td>
										<td width=17% align="center">
											<?php echo __('Cambiar valores'); ?>
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="alerta_diaria"><?php echo __('Alerta Diaria') ?></label> <input type="checkbox" id="alerta_diaria" name="alerta_diaria" <?php echo $cambiar_alerta_diaria == 'on' ? '' : disabled ?> <?php echo $alerta_diaria != '' ? "checked" : "" ?> />
										</td>
										<td align="right">
											<?php echo __('Retraso max.') ?>
										</td>
										<td align="left">
											<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $retraso_max > 0 ? $retraso_max : '' ?>" id="retraso_max" name="retraso_max" <?php echo $cambiar_alerta_diaria == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_alerta_diaria" name="cambiar_alerta_diaria" <?php echo $cambiar_alerta_diaria != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'alerta_diaria');"/>
										</td>
									</tr>
									<tr>
										<td align="right">
											&nbsp;
										</td>
										<td align="right">
											<?php echo __('Min HH.') ?>
										</td>
										<td align="left">
											<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_diario > 0 ? $restriccion_diario : '' ?>" id="restriccion_diario" name="restriccion_diario" <?php echo $cambiar_restriccion_diario == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_restriccion_diario" name="cambiar_restriccion_diario" <?php echo $cambiar_restriccion_diario != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'restriccion_diario');"/>
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="alerta_semanal"><?php echo __('Alerta Semanal') ?></label> <input type="checkbox" id="alerta_semanal" name="alerta_semanal" <?php echo $cambiar_alerta_semanal == 'on' ? '' : disabled ?> <?php echo $alerta_semanal != '' ? "checked" : "" ?> />
										</td>
										<td align="right">
											<?php echo __('Mín. HH') ?>
										</td>
										<td align="left">
											<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_min > 0 ? $restriccion_min : '' ?>" id="restriccion_min" name="restriccion_min" <?php echo $cambiar_alerta_semanal == 'on' ? '' : disabled ?> />
										</td>
										<td align="left">
											<?php echo __('Máx. HH') ?> <input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_max > 0 ? $restriccion_max : '' ?>" id="restriccion_max" name="restriccion_max" <?php echo $cambiar_alerta_semanal == 'on' ? '' : disabled ?> />
										</td>
										<td align="center">
											<input type="checkbox" id="cambiar_alerta_semanal" name="cambiar_alerta_semanal" <?php echo $cambiar_alerta_semanal != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'alerta_semanal');"/>
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="restriccion_mensual"><?php echo __('Mínimo mensual de horas') ?></label>
										</td>
										<td>&nbsp;</td>
										<td align="left">
											<input type="text" size="10" style="background-color: #EEEEEE;" <?php echo Html::Tooltip("Para no recibir alertas mensuales ingrese 0.") ?> value="<?php echo $restriccion_mensual > 0 ? $restriccion_mensual : '' ?>" id="restriccion_mensual" name="restriccion_mensual" <?php echo $cambiar_restriccion_mensual == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_restriccion_mensual" name="cambiar_restriccion_mensual" <?php echo $cambiar_restriccion_mensual != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'alerta_mensual');" />
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="dias_ingreso_trabajo"><?php echo __('Plazo máximo (en días) para ingreso de trabajos') ?></label>
										</td>
										<td>&nbsp;</td>
										<td align="left">
											<input type="text" size="10" style="background-color: #EEEEEE;" value="<?php echo $dias_ingreso_trabajo > 0 ? $dias_ingreso_trabajo : '' ?>" id="dias_ingreso_trabajo" name="dias_ingreso_trabajo" <?php echo $cambiar_dias_ingreso_trabajo == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_dias_ingreso_trabajo" name="cambiar_dias_ingreso_trabajo" <?php echo $cambiar_dias_ingreso_trabajo != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'dias_ingreso');"/>
										</td>
									</tr>
								</table>
							</fieldset>
							<fieldset class="table_blanco">
								<legend><?php echo __('Guardar datos'); ?></legend>
								<table width="100%">
									<tr><td align="center">
											<?php
											if ($sesion->usuario->fields['id_visitante'] == 0)
												echo "<input type=\"button\" value=\"" . __('Guardar') . "\" class='botonizame' onclick=\"ModificaTodos(this.form);\"  /> &nbsp;&nbsp;";
											else
												echo "<input type=\"button\" value=\"" . __('Guardar') . "\" class='botonizame' onclick=\"alert('Usted no tiene derecho para modificar estos valores.');\" /> &nbsp;&nbsp;";
											?>
											<input type="button" value="<?php echo __('Cancelar') ?>" onclick="Cancelar(this.form);" class='botonizame' />
										</td></tr>
								</table>
							</fieldset>
						</form>
					</td>
				</tr>
			</table>
			<br/>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>
			<table width="100%" class="tb_base">
				<tr>
					<td valign="top" class="subtitulo" align="left" colspan="2">
						<?php echo __('Lista de Usuarios') ?>:
						<hr class="subtitulo_linea_plomo"/>
					</td>
				</tr>
				<tr>
					<td valign="top" align="left" colspan="2"><img src="https://files.thetimebilling.com/templates/default/img/pix.gif" border="0" width="1" height="10"></td>
				</tr>
				<tr>
					<td valign="top" align="center" style="white-space:nowrap">
						<form name="act"  method="post">
							<label>
								<input type="checkbox" name="activo" id="activo" <?php if (!$activo) echo 'value="1" checked="checked"'; ?> />
								<?php echo __('sólo activos'); ?>
							</label>
							&nbsp;&nbsp;&nbsp;
							<span id="contienefiltro"></span>
							&nbsp;&nbsp;
							&nbsp; <a href="#" id="btnbuscar" style="display:none;" class="u1 botonizame" icon="ui-icon-search" rel="buscar"><?php echo __('Buscar Nombre'); ?></a>
							&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls"><?php echo __('Descargar Listado'); ?></a>
							&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls_vacacion"><?php echo __('Descargar Vacaciones'); ?></a>
							&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls_modificaciones"><?php echo __('Descargar Modificaciones'); ?></a>
						</form>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<br /><?php echo __('Para buscar ingrese el nombre del usuario o parte de él') ?>.
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<br />
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table id="tablapermiso">
	<thead>
		<tr>
			<td class="encabezado"><?php echo __('RUT'); ?></td>
			<th><?php echo __('ID'); ?></th>
			<th><?php echo __('Nombre'); ?></th>
			<th><?php echo __('Usuario'); ?></th>
			<th><?php echo __('Admin'); ?></th>
			<th><?php echo __('Admin<br>Datos'); ?></th>
			<th width="23"><?php echo __('Cobranza'); ?></th>
			<th><?php echo __('Editar<br/>Biblioteca'); ?></th>
			<th><?php echo __('Lectura'); ?></th>
			<th><?php echo __('Oficina'); ?></th>
			<th><?php echo __('Profesional'); ?></th>
			<th><?php echo __('Reportes'); ?></th>
			<th><?php echo __('Revisión'); ?></th>
			<th><?php echo __('Secretaría'); ?></th>
			<th><?php echo __('Socio'); ?></th>
			<th><?php echo __('Tarifa'); ?></th>
			<th><?php echo __('Retribuciones'); ?></th>
			<th width="25"><?php echo __('Activo'); ?></th>
		</tr>
	</thead>
	<tbody></tbody>
</table>

<table width="96%">
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<?php if (Conf::GetConf($sesion, 'ReportesAvanzados')) { ?>
		<tr>
			<td></td>
			<td>
				<table width="100%">
					<tr>
						<td valign="top" class="subtitulo" align="left" colspan="2">
							<?php echo __('Costo por usuario'); ?>:
							<hr class="subtitulo"/>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<a id="costos" class="botonizame" icon="ui-icon-search" style="margin:auto;width:230px;" href="<?php echo Conf::RootDir() . '/app/interfaces/costos.php'; ?>">
								<?php echo __('Editar costo mensual por usuario'); ?>
							</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	<?php } ?>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
</table>

<script src="//static.thetimebilling.com/js/jquery.dataTables.min.js"></script>
<?php
$_tanslations = json_encode(UtilesApp::utf8izar(array(
		'Atención' => __('Atención'),
		'Se desactivará al usuario seleccionado' => __('Se desactivará al usuario seleccionado'),
		'el cual está asociado a' => __('el cual está asociado a'),
		'acuerdos comerciales' => __('acuerdos comerciales'),
		'como' => __('como'),
		'Encargado Comercial' => __('Encargado Comercial'),
		'Clientes' => __('Clientes'),
		'Asuntos' => __('Asuntos'),
		'Desea continuar' => __('Desea continuar')
	)));
$JS = <<<JS
	var _tanslations = {$_tanslations};
	function __(text) {
		return _tanslations[text];
	}
JS;

echo $Html->script_block($JS);
echo $Html->script(Conf::RootDir() . '/app/layers/assets/js/users_data_table.js');

$pagina->PrintBottom();
