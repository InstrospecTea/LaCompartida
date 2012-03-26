<?
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

    $sesion = new Sesion(array('PRO', 'REV', 'COB','SEC'));
    $pagina = new Pagina($sesion);

    $t = new Trabajo($sesion);

    if($id_trab > 0)
        $t->Load($id_trab);

    if($opcion == "eliminar")
    {
        $t = new Trabajo($sesion);
        $t->Load($id_trabajo);
        if($t->Estado() == "Abierto")
            if(! $t->Eliminar() )
                $pagina->AddError($t->error);
    }
    
    if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists( 'Conf','CodigoSecundario' ) && Conf::CodigoSecundario() ) ) {
    	$codigo_cliente_url = 'codigo_cliente_secundario';
    	$codigo_asunto_url = 'codigo_asunto_secundario';
    }
    else {
    	$codigo_cliente_url = 'codigo_cliente';
    	$codigo_asunto_url = 'codigo_asunto';
    }

    $pagina->titulo = __('Revisar horas');
    $pagina->PrintTop();
    

    if($estado == "")
        $estado = "abiertos";
    
    if($from == 'cliente')
    	$url_iframe = 'trabajos.php?popup=1&id_usuario='.$id_usuario.'&'.$codigo_cliente_url.'='.$codigo_cliente.'&opc=buscar&fecha_ini='.Utiles::sql2date($fecha_ini).'&fecha_fin='.Utiles::sql2date($fecha_fin).'&id_grupo='.$id_grupo.'&clientes='.$clientes.'&usuarios='.$usuarios;
    elseif($from == 'asunto')
    	$url_iframe = 'trabajos.php?popup=1&id_usuario='.$id_usuario.'&'.$codigo_cliente_url.'='.$codigo_cliente.'&'.$codigo_asunto_url.'='.$codigo_asunto.'&opc=buscar&fecha_ini='.Utiles::sql2date($fecha_ini).'&fecha_fin='.Utiles::sql2date($fecha_fin);
	elseif($from == 'reporte')
	{
    	$url_iframe = 'trabajos.php?popup=1&opc=buscar&from=reporte';
		$url_iframe .= $id_usuario? "&id_usuario=".$id_usuario:'';
		$url_iframe .= $usuarios? "&usuarios=".$usuarios:'';
		$url_iframe .= $fecha_ini? "&fecha_ini=".$fecha_ini:'';
		$url_iframe .= $fecha_fin? "&fecha_fin=".$fecha_fin:'';
		$url_iframe .= $codigo_cliente? "&codigo_cliente=".$codigo_cliente:'';
		$url_iframe .= $codigo_asunto? "&codigo_asunto=".$codigo_asunto:'';
		$url_iframe .= $id_grupo_cliente? "&id_grupo_cliente=".$id_grupo_cliente:'';		
		$url_iframe .= $mes? "&mes=".$mes:'';	
		$url_iframe .= $estado? "&estado=".$estado:'';		
		$url_iframe .= $lis_usuarios? "&lis_usuarios=".$lis_usuarios:'';
		$url_iframe .= $lis_clientes? "&lis_clientes=".$lis_clientes:'';
		$url_iframe .= $campo_fecha? "&campo_fecha=".$campo_fecha:'';

		if($id_cobro)
			if($id_cobro != 'Indefinido')
		$url_iframe .= "&id_cobro=".$id_cobro;
	}
    elseif($from == 'horas')
    {
    	$id_usuario = $sesion->usuario->fields['id_usuario'];
    	$url_iframe = "trabajos.php?popup=1&id_usuario=".$id_usuario."&codigo_cliente=".$codigo_cliente."&opc=buscar";
    }
	else
	{
    	$id_usuario = $sesion->usuario->fields['id_usuario'];
    	$url_iframe = "trabajos.php?popup=1&id_usuario=".$id_usuario."&motivo=horas";
    }
	
	if (UtilesApp::GetConf($sesion, 'UsoActividades')) {
		$url_iframe .= "&glosa_actividad=".$glosa_actividad;
	}

	
//echo "<iframe name=trabajos onload=\"calcHeight(this.id, 'pagina_body');\" id='trabajos' src='".$url_iframe."' frameborder=0 width=100% height=2000px></iframe>";




?>
    <script type="text/javascript">
    <?php echo "var url_iframe='$url_iframe';"; ?>
	function calcHeight(idIframe, idMainElm){
    ifr = $(idIframe);
    the_size = ifr.$(idMainElm).offsetHeight + 20;
    if( the_size < 500 ) the_size = 500;
    new Effect.Morph(ifr, {
        style: 'height:'+the_size+'px',
        duration: 0.2
    });
}

  /*jQuery(document).ready(function() {
      
    
	 jQuery('#divhoras').load(url_iframe+'   #form_trabajos');
	 jQuery('#trabajos').attr('src',url_iframe+'&esajax=1');
	     jQuery('#boton_buscar').live('click',function() {
	     jQuery('#form_trabajos').append('<input type="hidden" id="esajax" name="esajax" value="1"/>');
	     jQuery('#form_trabajos').attr({'action': url_iframe+'&esajax=1','target':'trabajos' }).submit();
	     calcHeight('trabajos', 'pagina_body');
	    return false;
	 });
	 jQuery('#trabajos').load(function() {
	      calcHeight('trabajos', 'pagina_body');
	      
	 });
	
  });*/
     
  
//<div style="width:100%;height:100%;border: 0 none;" id="divhoras">&nbsp;</div><iframe name='trabajos'  id='trabajos' frameborder=0 width=100% height=100px></iframe>
</script>

<?
echo "<iframe name=trabajos onload=\"calcHeight(this.id, 'pagina_body');\" id='trabajos' src='".$url_iframe."' frameborder=0 width=100% height=2000px></iframe>";
$pagina->PrintBottom();
?>
