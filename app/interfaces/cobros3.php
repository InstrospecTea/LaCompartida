<?php 
	
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/classes/PaginaCobro.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../fw/classes/Html.php';
require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/classes/InputId.php';
require_once Conf::ServerDir().'/classes/Trabajo.php';
require_once Conf::ServerDir().'/classes/Funciones.php';
require_once Conf::ServerDir().'/classes/Cobro.php';
require_once Conf::ServerDir().'/classes/Cliente.php';

$sesion = new Sesion(array('COB'));
$pagina = new PaginaCobro($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];

$cobro = new Cobro($sesion);
$cobro->Load($id_cobro);

if(!$cobro->Load($id_cobro)) {
	$pagina->FatalError(__('Cobro inválido'));
}

$cliente = new Cliente($sesion);
$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
$nombre_cliente = $cliente->fields['glosa_cliente'];
$pagina->titulo = __('Emitir') . ' ' . __('Cobro') . __(' :: Selección de trabajos #').$id_cobro.__(' ').$nombre_cliente;

if($cobro->fields['estado'] <> 'CREADO' && $cobro->fields['estado'] <> 'EN REVISION'){
    $pagina->Redirect("cobros6.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");	
}

$cobro->Edit('etapa_cobro','2');
$cobro->Write();

if($opc=="siguiente"){
	$pagina->Redirect("cobros_tramites.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
} else if($opc=="anterior") {
	$pagina->Redirect("cobros2.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
}

$cobro->LoadAsuntos();

$comma_separated = implode("','", $cobro->asuntos);
 
$pagina->PrintTop($popup);
	
if($popup) {
?>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td valign="top" align="left" class="titulo" bgcolor="<?php echo (method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'ColorTituloPagina'):Conf::ColorTituloPagina())?>">
				<?php echo __('Emitir') . ' ' . __('Cobro') . __(' :: Selección de trabajos #').$id_cobro.__(' ').$nombre_cliente;?>
			</td>
		</tr>
	</table>
<br>
<?php
	}

?>
<form method="post">
	<input type="hidden" name="opc">
    <input type="hidden" name="id_cobro" value="<?php echo $id_cobro ?>">
	<?php
		$pagina->PrintPasos($sesion,2,'',$id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']);
	?>
	
	<table width=100%>
	    <tr>
	        <td align=left><input type=button class=btn value="<?php echo __('<< Anterior')?>" onclick="this.form.opc.value = 'anterior'; this.form.submit();">
			<td align=center>
			</td>
			<td align=right>
				<input type=button class=btn value="<?php echo __('Siguiente >>')?>" onclick="this.form.opc.value = 'siguiente'; this.form.submit();">
			</td>
	    </tr>
    </table>
 	<table width=100%>
	    <tr>
			<?php     	
				
				$codigo_cliente_query_string = "codigo_cliente={$codigo_cliente}";
				
				if (Conf::GetConf($sesion, 'CodigoSecundario')) {
	  				$codigo_cliente_query_string = "codigo_cliente_secundario={$codigo_cliente_secundario}";
				}

			?>
	    	<td class="cvs" align="center" colspan="2">
	            <iframe name="trabajos" id="asuntos" src="trabajos.php?$codigo_cliente_query_string?>&id_cobro=<?php echo $id_cobro?>&motivo=cobros&opc=buscar&popup=1" frameborder="0" width="800px" height="1500px"></iframe>
	        </td>
	    </tr>
	</table>
</form>

<?php echo InputId::Javascript($sesion) ?>

<script src="guardar_campo_trabajo.js"></script>
<?php

	$pagina->PrintBottom($popup);

	function funcionTR(& $trabajo)
	{
		global $sesion;
        global $id_cobro;
		static $i = 0;

		if($i % 2 == 0) {
			$color = "#dddddd";
		} else {
			$color = "#ffffff";
		}

        $img_dir = Conf::ImgDir();
        $tarifa = Funciones::Tarifa($sesion,$trabajo->fields[id_usuario],$trabajo->fields[id_moneda],$trabajo->fields[codigo_asunto]);
        
		list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']); 

		$duracion = $h + ($m > 0 ? ($m / 60) :'0');
        $total = round($tarifa * $duracion, 2);
        $dur_cob = "$h:$m";

		list($h,$m,$s) = split(":",$trabajo->fields['duracion']); 

        $dur = "$h:$m";
		$formato_fecha = "%d/%m/%y";
		$fecha = Utiles::sql2fecha($trabajo->fields[fecha],$formato_fecha);
	 	$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
	 	$html .= "<td>$fecha</td>";
	 	$html .= "<td>".$trabajo->fields[glosa_cliente]."</td>";
	 	$html .= "<td nowrap>".  $trabajo->fields['glosa_asunto'] . "</td>";
	 	$html .= "<td nowrap>". $trabajo->fields['glosa_actividad']."</td>";
	 	$html .= "<td>". $dur."</td>";
	 	$html .= "<td>". $dur_cob ."</td>";
	 	$html .= "<td align=center>";
		$html .= $trabajo->fields[cobrable] == 1 ? "SI" : "NO" ;
		$html .= "</td>";
	 	$html .= "<td>".$trabajo->Estado()."</td>";
	 	$html .= "<td><a href=editar_trabajo.php?id_cobro=$id_cobro&id_trabajo=".$trabajo->fields[id_trabajo]."><img src=$img_dir/editar_on.gif border=0></td>";
	 	$html .= "</tr>";
	 	$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";
	 	$html .= "<td><strong>Desc.</strong></td><td colspan=4>".$trabajo->fields['descripcion']."</td>";
	 	$html .= "<td colspan=2><strong>Profesional.</strong><br>".substr($trabajo->fields[nombre],0,1).". ".$trabajo->fields[apellido1]."</td>";
	 	$html .= "<td colspan=2><strong>Tarifa</strong><br>".$trabajo->fields[id_moneda]." ".$total."</td>";
		$html .= "</tr>";
		$i++;

		return $html;
	}
?>
