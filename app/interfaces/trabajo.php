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
    require_once Conf::ServerDir().'/classes/Asunto.php';

    $sesion = new Sesion(array('PRO','REV'));
    $pagina = new Pagina($sesion);
    $pagina->titulo = __('Ingreso/Modificación de').' '.__('Trabajos');
    $pagina->PrintTop($popup);

?>
<script type="text/javascript">
	function calcHeight(idIframe, idMainElm){
    ifr = $(idIframe);
    the_size = ifr.$(idMainElm).offsetHeight + 20;
    if( the_size < 250 ) the_size = 250;
    new Effect.Morph(ifr, {
        style: 'height:'+the_size+'px',
        duration: 0.2
    });
}
</script>

<table cellspacing=0 cellpadding=0 width=100%>
	<tr>
		<td align=center>
			<div id="Iframe" class="tb_base" style="width:750px;">
			<iframe id='asuntos' name='asuntos' target="asuntos" onload="calcHeight(this.id, 'pagina_body');" id='asuntos' scrolling="no" src="editar_trabajo.php?popup=1&id_trabajo=<?=$id_trab?>&opcion=<?=$opcion?>" frameborder="0" style="width:80%; height:352px;"></iframe>
		  </div>
		  <br/>
		</td>
	</tr>
	<tr>
		<td align=center>
			<div class="tb_base" style="width: 750px;">
			<iframe name="semana" id="semana" onload="calcHeight(this.id, 'pagina_body');" src="semana.php?popup=1&semana=<?=$semana?>" frameborder="0" style="width: 80%; height: 600px;"></iframe>
			</div>
		</td>
	</tr>
</table>
<?
    $pagina->PrintBottom();
?>
