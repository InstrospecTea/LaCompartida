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
    $pagina->titulo = __('Ingreso/Modificación de').' '.__('Trámites');
    $pagina->PrintTop($popup);

?>
<script type="text/javascript">
	if (top.location != self.location) //if page is not in its frameset
	{
		top.location.href = self.location; // relocate the page to the container frame, use the absolute path
		// for example: top.location.href = '/folder/page1.html';
	}
	
	function resizeCaller()
	{
		document.getElementById('Iframe').style.height='372px';
		document.getElementById('Iframe').style.height=asuntos.document.body.scrollHeight+20+'px';
	}
</script>
<table cellspacing=0 cellpadding=0 width=100%>
	<tr>
		<td align=center>
			<div id="Iframe" style=" width:620px;height:372px; ">
			<iframe id='asuntos' name='asuntos' target="asuntos" onload="resizeCaller();" id='asuntos' scrolling="no" src="ingreso_tramite.php?popup=1&id_tramite_tipo=<?=$id_tramite_tipo?>&opcion=<?=$opcion?>" frameborder="0" style="width:620px; height:100%"></iframe>
		  </div>
		</td>
	</tr>
	<tr>
		<td align=center>
			<iframe name="semana" id="semana" src="semana.php?popup=1&semana=<?=$semana?>" frameborder="0" width="700px" height="1000px"></iframe>
		</td>
	</tr>
</table>
<?
    $pagina->PrintBottom();
?>
