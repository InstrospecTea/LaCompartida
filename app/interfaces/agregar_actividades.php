<?php 

require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../fw/classes/Html.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/classes/Actividad.php';
require_once Conf::ServerDir().'/classes/InputId.php';
require_once Conf::ServerDir().'/classes/Funciones.php';
require_once Conf::ServerDir().'/classes/Autocompletador.php';


$sesion = new Sesion(array('DAT'));
$pagina = new Pagina($sesion);

$id_usuario = $sesion->usuario->fields['id_usuario'];

$actividad = new Actividad($sesion);

if ($id_actividad != '') {
	$actividad->Load($id_actividad);
}
if ($opcion2 == "guardar") {

	if ($actividad->Check() ) {
		
		if ($opcion == '') {
			if ($actividad->Editar()) {

				$id_actividad = $actividad->fields['id_actividad'];
				$pagina->AddInfo($txt_tipo . ' ' . __('Guardado con éxito.') );
				$actividad->Load($id_actividad);
			}
		} else if ($opcion == 'agregar') {

			echo '--A-- ';
			if ($actividad->Agregar()) {
				echo 'A ';
				$id_actividad = $actividad->fields['id_actividad'];
				$pagina->AddInfo($txt_tipo . ' ' . __('Guardado con éxito.') );
				echo 'B ';
				$actividad->Load($id_actividad);
				echo 'C ';
			}
		}
		
		echo '<script type="text/javascript">
			if (window.opener !== undefined && window.opener.Refrescar) {
				window.opener.Refrescar();
			}
			</script>';
	} else {  

	}
}

$pagina->titulo = __('Ingreso de actividad');
$pagina->PrintTop($popup);

?>

<script type="text/javascript">

	if(parent.window.Refrescarse) {
		parent.window.Refrescarse();
	} else if( window.opener.Refrescar ) {
		window.opener.Refrescar();
	}

	jQuery(document).ready(function() {
		startP = window.location.search.indexOf('codact');
		hrefPart2 = window.location.search.substring(startP + 7);
		endP = hrefPart2.indexOf('&');
		campo_codact = hrefPart2.substring(0, endP);
		


		if  (campo_codact != '') {
			// load values to the form
			document.getElementById('codigo_actividad').value = campo_codact;
			document.getElementById('codigo_actividad').value = campo_codact;
			document.getElementById('codigo_actividad').value = campo_codact;
			document.getElementById('codigo_actividad').value = campo_codact;
		}
    });

	function Validar(p) {

		if( document.getElementById('codigo_actividad').value=='' ) {
			alert( 'Debe ingresar un código.' );
			document.getElementById('codigo_actividad').focus();
			return false;
		}
		if( document.getElementById('glosa_actividad').value=='' ) {
			alert( 'Debe ingresar un título.' );
			document.getElementById('glosa_actividad').focus();
			return false;
		} else {
			var form = document.getElementById('form_actividades');
			var codact = document.getElementById('codigo_actividad').value;
			var titact = document.getElementById('glosa_actividad').value;
			var codcliente = document.getElementById('codigo_cliente').value;
			var codasunto = document.getElementById('campo_codigo_asunto').value;
			
			var startP = window.location.search.indexOf('opcion');
			var opc = window.location.search.substring(startP + 7, startP + 14);
			
			form.action =  "agregar_actividades.php?buscar=1&popup=1&codact="+codact+"&titact="+titact+"&codcliente="+codcliente+"&codasunto="+codasunto;
			if (opc == 'agregar') {
				form.action += "&opcion=agregar";
			}
			console.log('formaction value: ' + form.action ); 
			form.submit();

			//perfecto:  solo agregar y ordenar
		}
		return true;
	}

	function preparevalues(p) {

	}

	function __Guardar(p) {
		console.log('func guardar is executing');
	}

</script>


<?php echo Autocompletador::CSS(); ?>

<form method="post" name="form_actividades" id="form_actividades">
	<input type="hidden"  name="opcion2" id="opcion2" value="guardar">
	<input type="hidden" name="id_actividad" value="<?= $actividad->fields['id_actividad'] ?>" />

	<table>
		<tr>
			<td>
				
			<td>
		</tr>
	</table>
	<fieldset class="border_plomo tb_base">
		<legend>Ingreso de Actividades</legend>
	<?php 

		$codigo_asunto = $actividad->fields['codigo_asunto'];
		$cod_actividad =  Utiles::Glosa($sesion, $id_actividad, 'codigo_actividad', 'actividad', 'id_actividad');
		$glosa_asunto = Utiles::Glosa($sesion, $glosa_asunto, 'glosa_asunto', 'asunto', 'codigo_asunto');
		$codigo_cliente = Utiles::Glosa($sesion, $codigo_asunto, 'codigo_cliente', 'asunto', 'codigo_asunto');
		$glosa_cliente = Utiles::Glosa($sesion, $codigo_cliente, 'glosa_cliente', 'cliente', 'codigo_cliente');

		if ($cod_actividad == 'No existe información') {
			$cod_actividad = '';
		} 
		if (strstr($glosa_asunto, 'No existe información') == true) {
			$glosa_asunto = '';
		} 
		if ($codigo_cliente == 'No existe información') {
			$codigo_cliente = '';
		}
		if ($glosa_cliente == 'No existe información') {
			$glosa_cliente = '';
		}	
	?>

	<table style="border: 1px solid #BDBDBD;" class="" width="80%">
		<tr>
			<td align="right">
				<?php echo __('Código actividad')?>
			</td>
			<td align="left">
				<input id="codigo_actividad" name="codigo_actividad" size="5" maxlength="5" value="<?php echo $cod_actividad; ?>" />
			</td>
		</tr>
		
		<tr>
			<td align="right">
				<?php echo __('Título')?>
			</td>
			<td align="left">
				<input id='glosa_actividad' name='glosa_actividad' size='35' value="<? echo $actividad->fields['glosa_actividad']; ?>" />
			</td>
		</tr>
		
		<tr>
			<td align="right">
				<?php echo __('Cliente')?>
			</td>
			<td align="left">

			<?php
				if( Conf::GetConf($sesion,'TipoSelectCliente') == 'autocompletador' ) {
					if( Conf::GetConf($sesion,'CodigoSecundario') )  {
						echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario);						
					} else {
						echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);	
					}	
				} else {
					if( Conf::GetConf($sesion,'CodigoSecundario') )  {
						echo InputId::Imprimir($sesion, "cliente", "codigo_cliente_secundario", "glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,""           ,"CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320,$codigo_asunto_secundario);
					} else {
						echo InputId::Imprimir($sesion, "cliente", "codigo_cliente", "glosa_cliente", "codigo_cliente", $codigo_cliente, "", "CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320,$codigo_asunto);
					}
				}
			?>
			</td>
		</tr>

		<tr>
			<td align="right">
				<?php echo __('Asunto')?>
			</td>
			<td align="left">
				
				<?php
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )) {
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargaIdioma(this.value);CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					} else {
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto ? $actividad->fields['codigo_asunto'] : $codigo_asunto ,"","CargaIdioma(this.value); CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}
				?>

			</td>
		</tr>		
	</table>
	</fieldset>
	<br>
		<div class="fl">																			
			<a class="btn botonizame" href="javascript:void(0);" icon="ui-icon-save" onclick="Validar(jQuery('#form_actividades').get(0))"><?php echo  __('Guardar') ?></a>
			<a class="btn botonizame" href="javascript:void(0);" icon="ui-icon-exit" onclick="window.close();" ><?php echo  __('Cancelar') ?></a>
		</div>
</form>

<?php 
echo(InputId::Javascript($sesion)); 
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) ) {
	echo(Autocompletador::Javascript($sesion));
}
$pagina->PrintBottom($popup);

