<?php

require_once dirname(__FILE__).'/../conf.php';

$sesion = new Sesion(array('COB'));
$pagina = new PaginaCobro($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];

$cobro = new Cobro($sesion);
$cobro->Load($id_cobro);

if(!$cobro->Load($id_cobro)){
	$pagina->FatalError(__('Cobro inválido'));
}

$cliente = new Cliente($sesion);
$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
$nombre_cliente = $cliente->fields['glosa_cliente'];
$pagina->titulo = __('Emitir') . " " . __('Cobro') . __(' :: Selección de trámites #').$id_cobro.__(' ').$nombre_cliente;

if($cobro->fields['estado'] != 'CREADO' && $cobro->fields['estado'] != 'EN REVISION'){
	$pagina->Redirect("cobros6.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");	
}

$cobro->Edit('etapa_cobro','2');
$cobro->Write();

if($opc=="siguiente"){
	
	if(!empty($cobro->fields['incluye_gastos'])) {
		$pagina->Redirect("cobros4.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
	} else {
		$pagina->Redirect("cobros5.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
	}
		
} else if($opc=="anterior"){
	$pagina->Redirect("cobros3.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
}
	
$cobro->LoadAsuntos();

$comma_separated = implode("','", $cobro->asuntos);
$titulo_emitir_cobro = __('Emitir') . " " . __('Cobro') . __(' :: Selección de trámites #').$id_cobro.__(' ').$nombre_cliente;

if($popup) {

	echo '<table width="100%" border="0" cellspacing="0" cellpadding="2">';
		echo '<tr>';

			echo '<td valign="top" align="left" class="titulo" bgcolor="#A3D55C">';

			 echo $titulo_emitir_cobro;
			echo '</td>';
		echo '</tr>';
	echo '</table>';

	echo '<br>';
}
$pagina->PrintTop($popup);

?>
<form method="post">

    <input type="hidden" name="opc">
    <input type="hidden" name="id_cobro" value=<?php echo $id_cobro?>>
	
	<?php $pagina->PrintPasos($sesion,6,'',$id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']); ?>

	<table width="100%">
		<tr>
			<td align="left">
				<input type="button" class="btn" value="<?php echo __('<< Anterior')?>" onclick="this.form.opc.value = 'anterior'; this.form.submit();">
			</td>
			
			<td align="center">&nbsp;</td>
			
			<td align="right">
				<input type="button" class="btn" value="<?php echo __('Siguiente >>')?>" onclick="this.form.opc.value = 'siguiente'; this.form.submit();">
			</td>
		</tr>
    </table>
	
 	<table width="100%">
		<tr>
			<td class="cvs" align="center" colspan="2">
				<iframe name="tramites" id="asuntos" src="listar_tramites.php?id_cobro=<?=$id_cobro?>&opc=buscar&motivo=cobros&popup=1" frameborder="0" width="800px" height="1500px"></iframe>
			</td>
		</tr>
	</table>
	
</form>
		
<?php echo  InputId::Javascript($sesion);
echo '<script src="guardar_campo_trabajo.js"></script>';

$pagina->PrintBottom($popup);

function funcionTR(& $tramite)
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
    $tarifa = Funciones::TramiteTarifa($sesion, $trabajo->fields['id_tramite_tipo'],$trabajo->fields['id_moneda_asunto'],$trabajo->fields['codigo_asunto']);
	
	list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
	
	$duracion = $h + ($m > 0 ? ($m / 60) :'0');
    $total = round($tarifa, 2);
    $dur_cob = "$h:$m";
	
	list($h,$m,$s) = split(":",$trabajo->fields['duracion']); 
    
	$dur = "$h:$m";
	$formato_fecha = "%d/%m/%y";
	$fecha = Utiles::sql2fecha($trabajo->fields[fecha],$formato_fecha);
	
	$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
	$html .= "<td colspan=9><strong>".$glosa_tramite."</strong></td></tr>";
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

