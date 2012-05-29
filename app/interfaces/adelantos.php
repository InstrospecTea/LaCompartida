<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Documento.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	$documento = new Documento($sesion);
	$cliente = new Cliente($sesion);

	$pagina->titulo = __('Revisar Adelantos');

	//Filtros
	$filtros = array('id_documento' => $id_documento, 'codigo_cliente' => $codigo_cliente, 'fecha_inicio' => $fecha1, 'fecha_fin' => $fecha2, 'moneda' => $moneda_adelanto, 'tiene_saldo' => $tiene_saldo);

	if ($opc == "eliminar" and !empty($id_documento_e))
	{
		$sql = "DELETE FROM documento WHERE id_documento = " . mysql_real_escape_string($id_documento_e) . " AND es_adelanto = 1 AND monto = saldo_pago";
		$query = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $sesion->dbh);
		if (mysql_affected_rows($sesion->dbh) > 0)
		{
			$pagina->AddInfo(__('Adelanto') . ' ' . __('eliminado con éxito'));
		}
	}

	$pagina->PrintTop();
	
	$codigo_cliente = empty($codigo_cliente) && $codigo_cliente_secundario ? $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario) : $codigo_cliente;
?>

<script type="text/javascript">
    jQuery(document).ready(function() {
       
       jQuery("#losadelantos").load('ajax/lista_adelantos_ajax.php?ajax=1', function() {
           jQuery('.pagination ul li a').each(function() {
              valrel=jQuery(this).attr('href').replace("javascript:PrintLinkPage('",'').replace("');", '');
              jQuery(this).attr({'href':'#', 'class':'printlinkpage','rel':valrel});
           });
        });
        jQuery('.printlinkpage').live('click',function() {
            multi=jQuery("input[name=x_pag]").val();
            //alert(multi);
            valrel=multi*(jQuery(this).attr('rel')-1);
            jQuery('#xdesde').val(valrel);
            jQuery.post('ajax/lista_adelantos_ajax.php?ajax=1', { xdesde: valrel },
                function(data) {
                jQuery("#losadelantos").html(data);
                
                jQuery('.pagination ul li a').each(function() {
                valrel=jQuery(this).attr('href').replace("javascript:PrintLinkPage('",'').replace("');", '');
                jQuery(this).attr({'href':'#', 'class':'printlinkpage','rel':valrel});
                });
            });
	     jQuery("#losadelantos").html(DivLoading);
        });
        jQuery("#boton_buscar").click(function() {
           // alert('buscando...');
            jQuery.post('ajax/lista_adelantos_ajax.php?ajax=1', jQuery('#form_adelantos').serialize(),
                        function(data) {
                        jQuery("#losadelantos").html(data);

                        jQuery('.pagination ul li a').each(function() {
                        valrel=jQuery(this).attr('href').replace("javascript:PrintLinkPage('",'').replace("');", '');
                        jQuery(this).attr({'href':'#', 'class':'printlinkpage','rel':valrel});
                        });
                    });
		    jQuery("#losadelantos").html(DivLoading);
		    
        });
        jQuery('#codigo_cliente').change(function(){
            jQuery('#loading').remove();
            jQuery('#xdesde').val(0);
        });
        jQuery('#campo_codigo_cliente').change(function(){
            jQuery('#loading').remove();
            jQuery('#xdesde').val(0);
        });
    
    });
    

    function Refrescarse() {
                var desdepg=jQuery('#xdesde').val();
                jQuery.post('ajax/lista_adelantos_ajax.php?ajax=1', { xdesde: desdepg },
                function(data) {
                jQuery("#losadelantos").html(data);
                
                jQuery('.pagination ul li a').each(function() {
                valrel=jQuery(this).attr('href').replace("javascript:PrintLinkPage('",'').replace("');", '');
                jQuery(this).attr({'href':'#', 'class':'printlinkpage','rel':valrel});
                });
            });
	     jQuery("#losadelantos").html(DivLoading);
    }
	function AgregarNuevo(tipo)
	{
		<?php
		if (UtilesApp::GetConf($sesion,'CodigoSecundario')) { ?>
			var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
			var url_extension = "&codigo_cliente_secundario=" + codigo_cliente_secundario;
		<?php } else { ?>
			var codigo_cliente = $('codigo_cliente').value;
			var url_extension = "&codigo_cliente=" + codigo_cliente;
		<?php } ?>
		if(tipo == 'adelanto')
		{
			var urlo = "ingresar_documento_pago.php?popup=1&adelanto=1" + url_extension;
                        return	nuovaFinestra('Agregar_Adelanto', 720, 500, urlo, 'top=100, left=125');
        
                }
    
	}
	function Refrescar()
	{
	<?php
		if($desde)
			echo "var pagina_desde = '&desde=".$desde."';";
		else
			echo "var pagina_desde = '';";
		if($orden)
			echo "var orden = '&orden=".$orden."';";
		else
			echo "var orden = '';";
	?>
		var opc= $('opc').value;
		var codigo_cliente = $('codigo_cliente').value;
		var fecha1 = $('fecha1').value;
		var fecha2 = $('fecha2').value;
		var url = "adelantos.php?opc="+opc+"&codigo_cliente="+codigo_cliente+orden+"&fecha1="+fecha1+"&fecha2="+fecha2+pagina_desde+"&buscar=1"+($F('tiene_saldo') ? '&tiene_saldo=1' : '');
		self.location.href= url;
	}
        
</script>

<?php echo Autocompletador::CSS(); ?>

<table width="90%">
	<tr>
		<td>
			<form method='post' name="form_adelantos" action='adelantos.php' id="form_adelantos">
                            <input  id="xdesde"  name="xdesde" type="hidden" value="">
				<input type='hidden' name='opc' id='opc' value=buscar>
				<!-- Calendario DIV -->
				<div id="calendar-container" style="width:221px; position:absolute; display:none;">
					<div class="floating" id="calendar"></div>
				</div>
				<!-- Fin calendario DIV -->
				<fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
					<legend><?=__('Filtros')?></legend>
					<table style="border: 0px solid black" width='720px'>
						<tr>
							<td align="right"><label for="id_documento">N° Adelanto</laber>
							<td align="left">
								<input type="text" size="6" name="id_documento" id="id_documento" value="<?php echo $id_documento ?>">
							</td>
						</tr>
						<tr>
	    					<td align="right" width="30%"><?php echo __('Nombre Cliente') ?></td>
	    					<td colspan="3" align="left">
							<?php
							if (UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador') {
									if (UtilesApp::GetConf($sesion,'CodigoSecundario') ):
										echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario);
									else:
										echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
                                                                        endif;
								} else {
									if(UtilesApp::GetConf($sesion,'CodigoSecundario') ):
										echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto_secundario);
									else:
										echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
                                                                        endif;
								}
							?>
	  						</td>
						</tr>
						<tr>
							<td align=right><?php echo __('Fecha Desde') ?></td>
							<td align="left">
								<input type="text" name="fecha1" value="<?=$fecha1 ?>" id="fecha1" size="11" maxlength="10" />
								<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha1" style="cursor:pointer" />
							</td>
							<td align="left" colspan="2">
								<?php echo __('Fecha Hasta')?>
								<input type="text" name="fecha2" value="<?=$fecha2 ?>" id="fecha2" size="11" maxlength="10" />
								<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha2" style="cursor:pointer" />
							</td>
						</tr>
						<tr>
							<td align=right>
								<?php echo __('Moneda') ?>
							</td>
							<td colspan="2" align="left">
								<?= Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_adelanto", $moneda_adelanto, "", __('Todas'),''); ?>
							</td>
							<td></td>
						</tr>
						<tr>
							<td align=right>
								<?php echo __('Sólo Adelantos con Saldo') ?>
							</td>
							<td colspan="2" align="left">
								<input type="checkbox" id="tiene_saldo" name="tiene_saldo" value="1" <?=$tiene_saldo ? 'checked' : ''?>/>
							</td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td colspan=2 align=left>
								<input name="boton_buscar" id="boton_buscar" type="button" value="<?php echo __('Buscar') ?>" class="btn">
                                                                
							</td>
							<td width='40%' align="right">
								<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('adelanto')" title="Agregar Adelanto"><?=__('Agregar')?> <?php echo __('adelanto') ?></a>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</td>
	</tr>
</table><div id="losadelantos">
<?php
	//Lista de adelantos
	//include("lista_adelantos.php");
       echo '</div>';

	if (UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador')
	{
		echo(Autocompletador::Javascript($sesion));
	}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
<script type="text/javascript">
Calendar.setup({ inputField	: "fecha1", ifFormat : "%d-%m-%Y", button : "img_fecha1" });
Calendar.setup({ inputField	: "fecha2", ifFormat : "%d-%m-%Y", button : "img_fecha2" });
</script>
