<?php
   	require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/classes/Cobro.php';
    require_once Conf::ServerDir().'/../app/classes/Observacion.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';


    $sesion = new Sesion('');
    $pagina = new Pagina($sesion);

	if($id_cobro)
	{
	    $cobro = new Cobro($sesion);
	    if(!$cobro->Load($id_cobro))
    	    $pagina->FatalError(__('Cobro inválido'));
	}

	if(!$id_cobro)
		$pagina->FatalError(__('Debe especificar un cobro'));

    if($opc == "guardar")
    {
        $his = new Observacion($sesion);
        $his->Edit('fecha',$fecha_obs);
        $his->Edit('comentario',$observacion_obs);
        $his->Edit('id_usuario',$usuario_ingreso_obs);
        $his->Edit('id_cobro',$id_cobro);

		if($id_persona)
			$his->Edit('id_persona',$id_persona);

        if($his->Write())
            $pagina->AddInfo(__('Historial ingresado'));
    }


    if($desde=="")
        $desde=0;
    if($x_pag=="")
        $x_pag=5;
    if($orden == '')
        $orden= 'cobro_historial.fecha_creacion DESC';


	$where = '';
	if(!$where)
		$where = 1;

    $query = "SELECT SQL_CALC_FOUND_ROWS id_cobro_historial, fecha, comentario, CONCAT_WS(' ',usuario.nombre, usuario.apellido1) as nombre
											FROM cobro_historial
                                            LEFT JOIN usuario ON usuario.id_usuario = cobro_historial.id_usuario
                                            WHERE id_cobro = '$id_cobro' AND $where";

    $pagina->PrintHeaders();
    $pagina->PrintTop(1);
?>

<table   width="100%">
       <tr>
			<td colspan=4>
<?php
    $buscador = new Buscador($sesion, $query, "Observacion", $desde, $x_pag, $orden);
    $buscador->color_mouse_over = "#ADFF2F";
    $buscador->titulo = "<strong>Historial</strong>";
    $buscador->AgregarEncabezado("fecha",__('Fecha'),"align=center");
    $buscador->AgregarEncabezado("nombre",__('Nombre'),"align=center");
    $buscador->AgregarEncabezado("comentario",__('Observación'),"align=center");
    $buscador->Imprimir();
    
?>
	</td>
</tr>
<script>

function Validar(form)
{
	if(form.observacion_obs.value == '')
	{
		alert("<?php echo __('Debe ingresar observación')?>");
		return false;
	}
	form.opc.value='guardar';
	return true;
}
</script>
</table>
<form name="formulario" method="post" id="formulario">
<input type="hidden" name="opc" id="opcion">
<input type="hidden" name="id_persona" value="<?=$id_persona?>">
<input type="hidden" name="id_proceso" value="<?=$id_proceso?>">
<table width="100%">
<tr>
    <td>
        <img src="https://static.thetimebilling.com/images/pix.gif" />
    </td>
</tr>
<tr>
	<td colspan=4>
		<strong><img src="<?=Conf::ImgDir()?>/agregar.gif"> <?php echo __('Agregar historial')?></strong>
	</td>
</tr>
<tr>
    <td>
        <img src="https://static.thetimebilling.com/images/pix.gif" />
    </td>
</tr>
<tr>
	<td class="cvs">
		 <?php echo __('Fecha')?>
	</td>
	<td>
        <?php echo  Html::PrintCalendar('fecha_obs',''); ?>	
	</td>
</tr>
<tr>
    <td class="cvs">
        <?php echo __('Observaciones')?>
    </td>
    <td>
		<textarea id="historial_observaciones" cols="50" rows="4" name="observacion_obs"></textarea>
    </td>
</tr>
<tr>
    <td class="cvs">
         <?php echo __('Usuario ingreso')?>
    </td>
    <td>
		 <?php echo Html::SelectQuery($sesion,"SELECT id_usuario, CONCAT_WS(', ',apellido1,nombre) FROM usuario WHERE id_usuario <> -1 ORDER BY apellido1",'usuario_ingreso_obs',$sesion->usuario->fields['id_usuario'])?>
    </td>
</tr>
<tr>
	<td colspan=4 align=right>
		<input type=submit value="<?=__('Guardar')?>" onclick="return Validar(this.form);">
	</td>
</tr>
</table>
</form>
<?php
    $pagina->PrintBottom(1);
?>


