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
    require_once Conf::ServerDir().'/classes/Tramite.php';
    require_once Conf::ServerDir().'/classes/Funciones.php';

    $sesion = new Sesion(array('PRO', 'REV', 'COB','SEC'));
    $pagina = new Pagina($sesion);

    $t = new Tramite($sesion);

    if($id_tram > 0)
        $t->Load($id_tram);

    if($opcion == "eliminar")
    {
        $t = new Tramite($sesion);
        $t->Load($id_tramite);
        if($t->Estado() == "Abierto")
            if(! $t->Eliminar() )
                $pagina->AddError($t->error);
    }

    $pagina->titulo = __('Revisar trámites');
$pagina->PrintTop();

   ?>
    
    <script type="text/javascript">
	function calcHeight(idIframe, idMainElm){
    ifr = $(idIframe);
    the_size = ifr.$(idMainElm).offsetHeight + 20;
    new Effect.Morph(ifr, {
        style: 'height:'+the_size+'px',
        duration: 0.2
    });
}
</script>
<?php 

    if ($estado == "") {
        $estado = "abiertos";
	}
    
    if ($from == 'cliente') {
    	$url_iframe = 'listar_tramites.php?popup=1&id_usuario='.$id_usuario.'&codigo_cliente='.$codigo_cliente.'&opc=buscar&fecha_ini='.Utiles::sql2date($fecha_ini).'&fecha_fin='.Utiles::sql2date($fecha_fin).'&id_grupo='.$id_grupo.'&clientes='.$clientes.'&usuarios='.$usuarios;
	} else if ($from == 'asunto') {
    	$url_iframe = 'listar_tramites.php?popup=1&id_usuario='.$id_usuario.'&codigo_cliente='.$codigo_cliente.'&codigo_asunto='.$codigo_asunto.'&opc=buscar&fecha_ini='.Utiles::sql2date($fecha_ini).'&fecha_fin='.Utiles::sql2date($fecha_fin);
	} else if ($from == 'reporte') {
    	$url_iframe = 'listar_tramites.php?popup=1&opc=buscar&from=reporte';
		$url_iframe .= $id_usuario? "&id_usuario=".$id_usuario:'';
		$url_iframe .= $usuarios? "&usuarios=".$usuarios:'';
		$url_iframe .= $fecha_ini? "&fecha_ini=".$fecha_ini:'';
		$url_iframe .= $fecha_fin? "&fecha_fin=".$fecha_fin:'';
		$url_iframe .= $codigo_cliente? "&codigo_cliente=".$codigo_cliente:'';
		$url_iframe .= $codigo_asunto? "&codigo_asunto=".$codigo_asunto:'';
		$url_iframe .= $id_grupo_cliente? "&id_grupo_cliente=".$id_grupo_cliente:'';		
		$url_iframe .= $id_cobro? "&id_cobro=".$id_cobro:'';	
		$url_iframe .= $mes? "&mes=".$mes:'';	
		$url_iframe .= $estado? "&estado=".$estado:'';		
		$url_iframe .= $lis_usuarios? "&lis_usuarios=".$lis_usuarios:'';
		$url_iframe .= $lis_clientes? "&lis_clientes=".$lis_clientes:'';
		$url_iframe .= $campo_fecha? "&campo_fecha=".$campo_fecha:'';
	} else if ($from == 'horas') {
    	$id_usuario = $sesion->usuario->fields['id_usuario'];
    	$url_iframe = "listar_tramites.php?popup=1&id_usuario=".$id_usuario."&codigo_cliente=".$codigo_cliente."&opc=buscar";
    } else {
    	$id_usuario = $sesion->usuario->fields['id_usuario'];
    	$url_iframe = "listar_tramites.php?popup=1&id_usuario=".$id_usuario."&motivo=horas";
    }

?>
<iframe name=tramites id=tramites onload="calcHeight(this.id, 'pagina_body');" src='<?php echo $url_iframe ?>' frameborder=0 width=100% height=2000px></iframe>
<?php 
    $pagina->PrintBottom();
?>
