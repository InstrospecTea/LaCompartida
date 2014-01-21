<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);

$id_usuario = $sesion->usuario->fields['id_usuario'];

$pagina->titulo = __('Reportes espec&iacute;ficos');
$modulo_retribuciones_activo = Conf::GetConf($sesion, 'UsarModuloRetribuciones');
$pagina->PrintTop();

?>
<form name="formulario" id="formulario" method="post">
	<br>
	
	<table width="850px" style='border:0px solid #ccc' cellspacing="4" cellpadding="4">
		<tbody>
			<tr>
				<td align="center">
					
					<table width="85%" style='border:0px solid #ccc' cellspacing="2" cellpadding="2">
						<tbody>

							<tr>
								<td width="50%">
									
									<table width="90%" style="border: 1px solid #BDBDBD;" padding="10px" height="100px" class="tb_base">
										<tr>
											<?php if ( Conf::GetConf($sesion,'UsaDisenoNuevo')) { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/usuarios_32_nuevo.gif" alt=''/></td>
											<?php } else { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/usuarios_32.gif" alt=''/></td>
											<?php } ?>
											<td valign="center" style="font-weight:bold; height: 15px;" width="85%"><?php echo __('Clientes') ?></td>
										</tr>
										<tr valign="top" align="left" style="height: 5px;">
											<td><hr width="100%" width="100%"></td>
										</tr>
										<tr>
											<td>&nbsp;</td>
											<td>
												<ul style="list-style-position: outside; text-align:left;">
													<li><a href='reportes_asuntos.php' style="color:#000; text-decoration: none;"><?php echo __('Gr&aacute;fico') ?> <?php echo __('asuntos') ?></a></li>
												</ul>
											</td>
										</tr>
									</table>
									
									<br/>
								
								</td>
								
								<td width="50%">
									
									<table width="90%" style="border: 1px solid #BDBDBD;" padding="10px" height="100px" class="tb_base">
										<tr>
											<?php if ( Conf::GetConf($sesion,'UsaDisenoNuevo')) { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/contact_32_nuevo.gif" alt=''/></td>
											<?php } else { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/contact_32.gif" alt=''/></td>
											<?php } ?>
											<td valign="center" style='font-weight:bold; height: 15px;' width="85%"><?php echo __('Profesionales') ?></td>
										</tr>
										<tr valign="top" align="left" style="height: 5px;">
											<td><hr width="100%" width="100%"></td>
										</tr>
										<tr>
											<td>&nbsp;</td>
											<td>
												<ul style="list-style-position: outside; text-align: left;">
													<li><a href='usuario_vacaciones.php' style="color:#000;text-decoration: none;"><?php echo __('Usuario vacaciones') ?></a></li>
													<li><a href='resumen_abogado.php' style="color:#000;text-decoration: none;"><?php echo __('Rendimiento profesionales') ?></a></li>
													<li><a href='reportes_usuarios.php' style="color:#000;text-decoration: none;"><?php echo __('Gr&aacute;fico profesionales') ?></a></li>
													<li><a href='planillas/planilla_demora_ingreso_horas.php' style="color:#000;text-decoration: none;"><?php echo __('Demora ingreso de horas por profesional') ?></a></li>
												</ul>
											</td>
										</tr>
									</table>
									
									<br/>
								</td>
								
							</tr>

							<tr style='font-weight:bold'>
								<td>

									<table width="90%" style="border: 1px solid #BDBDBD;" padding="10px" height="140px" class="tb_base">
										<tr>
											<?php if ( Conf::GetConf($sesion,'UsaDisenoNuevo')) { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/coins_32_nuevo.gif" alt=''/></td>
											<?php } else { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/coins_32.gif" alt=''/></td>
											<?php } ?>
											<td valign="center" style='font-weight:bold; height: 15px;' width="85%"><?php echo __('Cobranza') ?></td>
										</tr>
										<tr valign=top align="left" style="height: 5px;">
											<td><hr size="1" width="100%"></td>
										</tr>
										<tr>
											<td>&nbsp;</td>
											<td>
												<ul style="list-style-position: outside; text-align: left;">
													<li><a href='resumen_cliente.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte de Ventas') ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_facturacion_pendiente.php' style="color:#000;text-decoration: none;"><?php echo __('Horas por facturar') ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_reporte_hitos.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte Hitos') ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_cobros_por_area.php' style="color:#000;text-decoration: none;"><?php echo __('Cobros por Area') ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/reporte_liq_no_facturadas.php' style="color:#000;text-decoration: none;"><?php echo __('Cobros') . ' por facturar'; ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_resumen_cobranza.php' style="color:#000;text-decoration: none;"><?php echo __('Resumen de Liquidaciones') ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_morosidad.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte Morosidad') ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/tipo_cambio_historico.php' style="color:#000;text-decoration: none;"><?php echo __('Histórico de Tipo de Cambio') ?></a></li>
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_ciclo_facturacion.php' style="color:#000;text-decoration: none;"><?php echo __('Ciclo de Facturación') ?></a></li>
												</ul>
											</td>
										</tr>
									</table>
									
								</td>
								
								<td>
									<table width="90%" style="border: 1px solid #BDBDBD;" padding="10px" height="140px" class="tb_base">
										<tr>
											<?php if ( Conf::GetConf($sesion,'UsaDisenoNuevo')) { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/proyectos_32_nuevo.gif" alt=''/></td>
											<?php } else { ?>
												<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/proyectos_32.gif" alt=''/></td>
											<?php } ?>
											<td valign="center" style='font-weight:bold; height:15px;' width="85%"><?php echo __('Varios') ?></td>
										</tr>
										<tr valign="top" align="left" style="height: 5px;">
											<td><hr size="1" width="100%"></td>
										</tr>
										<tr>
											<td>&nbsp;</td>
											<td>
												<ul style="list-style-position: outside; text-align: left;">
													<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/reportes_horas.php' style="color:#000;text-decoration: none;"><?php echo __('Gráfico por Período') ?></a></li>
													<li><a href='planillas.php' style="color:#000;text-decoration: none;"><?php echo __('Profesional v/s Cliente') ?></a></li>
													<li><a href='olap.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte gen&eacute;rico') ?></a></li>
													<?php if ($modulo_retribuciones_activo && $sesion->usuario->TienePermiso('RET')) { ?>
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/reporte_retribuciones_b.php' style="color:#000;text-decoration: none;"><?php echo 'Detalle de ' . __('Retribuciones') . ' por ' . __('Cobro'); ?></a></li>
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/reporte_retribuciones_resumen_b.php' style="color:#000;text-decoration: none;"><?php echo 'Resumen de ' . __('Retribuciones') . ' por ' . __('Profesional'); ?></a></li>
													<?php } ?>

												</ul>
											</td>
										</tr>
									</table>
									
									<br/>
								</td>
							</tr>
							
							<?php if ( Conf::GetConf($sesion, 'ReportesAvanzados')) { ?>
								<tr style='font-weight:bold'>
									<td>
								
										<table width="90%" style="border: 1px solid #BDBDBD;" padding="10px" height="120px" class="tb_base">
											<tr>
												<?php if ( Conf::GetConf($sesion,'UsaDisenoNuevo')) { ?>
													<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/proyectos_32_nuevo.gif" alt=''/></td>
												<?php } else { ?>
													<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/proyectos_32.gif" alt=''/></td>
												<?php } ?>
												<td valign="center" style='font-weight:bold; height: 15px;' width="85%"><?php echo __('Avanzados') ?></td>
											</tr>
											<tr valign="top" align="left" style="height: 5px;">
												<td><hr size="1" width="100%"></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td>
													<ul style="list-style-position: outside; text-align: left;">
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/reporte_avanzado.php' style="color:#000;text-decoration: none;"><?php echo __('Reportes Avanzados') ?></a></li>
														<li><a href='reporte_costos.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte costos') ?></a></li>
														<li><a href='reporte_financiero.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte financiero') ?></a></li>
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_participacion_abogado.php' style="color:#000;text-decoration: none;"><?php echo __('Participacion Abogado') ?></a></li>
														<li><a href='reporte_consolidado.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte consolidado') ?></a></li>
														<li><a href='reporte_anual.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte anual') ?></a></li>

													</ul>
												</td>

											</tr>
										</table>
										<br/>
									</td>

									<td>
										<table width="90%" style="border: 1px solid #BDBDBD;" padding="10px" height="120px" class="tb_base">
											<tr>
												<?php if ( Conf::GetConf($sesion,'UsaDisenoNuevo')) { ?>
													<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/proyectos_32_nuevo.gif" alt=''/></td>
												<?php } else { ?>
													<td rowspan="2" width="15%"><img src="<?php echo Conf::ImgDir() ?>/proyectos_32.gif" alt=''/></td>
												<?php } ?>
												<td valign="center" style='font-weight:bold; height: 15px;' width="85%"><?php echo __('Experimentales') ?></td>
											</tr>
											<tr valign="top" align="left" style="height: 5px;">
												<td><hr size="1" width="100%"></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td>
													<ul style="list-style-position: outside; text-align: left;">
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/reporte_diario.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte Diario') ?></a></li>
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_alertas_graficas.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte Alertas') ?></a></li>
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_saldo.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte Saldo Clientes') ?></a></li>
														<li><a href='<?php echo Conf::RootDir() ?>/app/interfaces/planillas/planilla_deudas.php' style="color:#000;text-decoration: none;"><?php echo __('Reporte Antigüedad Deudas Clientes') ?></a></li>

													</ul>
												</td>

											</tr>
										</table>
										<br/>
									</td>
								</tr>
								
							<?php } else {
								$pagina->AddInfo(__('Hay un error con ese reporte por favor comunicarse con soporte.'));
							} ?>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
</form>
<?php
$pagina->PrintBottom();
?>
