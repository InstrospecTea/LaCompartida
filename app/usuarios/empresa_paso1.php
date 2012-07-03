<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Empresa.php';
    require_once Conf::ServerDir().'/fw/classes/Html.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/modulos/noticia/classes/NoticiaAgrupador.php';


	$sesion = new Sesion( array('ADM') );
	
	$pagina = new Pagina($sesion);

	$pagina->titulo = "Administración - Empresas";

	$pagina->PrintHeaders();

    if($desde=="")
        $desde=0;
    if($x_pag=="")
        $x_pag=30;
    if($orden == '')
        $orden= 'glosa_empresa';

	

	if($opc=='agregar')
	{
	if ($glosa_empresa=='' or $foto['tmp_name']=='')
		{
		$pagina->AddError("Debe ingresar el nombre y el logo de la empresa....");

		}
		else
		{
			$empresa = new Empresa($sesion);
		
	 	if($foto['tmp_name'] != "")
           	$empresa->Edit('data_foto',$foto);

       $empresa->Edit('glosa_empresa',$glosa_empresa);

		if($empresa->Write())
			$pagina->AddInfo("Empresa agregada");
		else
			$pagina->AddError($empresa->error);		
		


		}
	

	}

    


	$pagina->PrintTop();
?>
<script language="javascript" type="text/javascript">
<!-- //
function OrdenarLista( tipo )
{
     var form = document.getElementById( 'formProyectos' );
     form.orden.value = tipo;
     form.submit();
     return true;
}
// -->
</script>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
<table width="100%" align="left">
<form action="empresa_paso1.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="opc" value="agregar">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="2">
			 <img border=0 src="<?=Conf::ImgDir()?>/proyectos_16.gif"> Agregar Empresa
			<hr class="subtitulo"/>
			<br>
		</td>
	</tr>
   <tr>
		<td align="right"><strong>Nombre</strong></td>
        <td valign="top" class="subtitulo" align="left" colspan="2">
		<input type="textbox" name="glosa_empresa" value="">
	</tr>
    <tr>
        <td align="right"><strong>Logo Empresa</strong></td>
        <td valign="top" class="subtitulo" align="left" colspan="2">
	    <input type="file" name="foto" size="20"></td>
	</tr>
	
	<tr>
	<td></td>
	<td>
	 <input type="submit" name="boton" value="Agregar">
	</td>
	</tr>
 			</form>
    <tr>
        <td valign="top" align=left valign="top" class="subtitulo" align="left" colspan="2">
			<br>
			 <img border=0 src="<?=Conf::ImgDir()?>/proyectos_16.gif"> Lista de Empresas
			<hr class="subtitulo"/>
		</td>
	</tr>
	<tr>
		<td colspan=2>
<table width="100%" align="left">
 <form id="formProyectos" name="formProyectos" method="post">
 <input type="hidden" name="x_pag" value="<?=$x_pag?>">
 <input type="hidden" name="desde" value="<?=$desde?>">
 <input type="hidden" name="opc" value="buscar">
 <input type="hidden" name="orden" value="<?=$orden?>">
    <tr>
        <td valign="top" align="left" colspan="2"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="10"></td>
    </tr>
    <tr>
        <td valign="top" align="right">
            <strong>Nombre</strong>
        </td>
        <td valign="top" align="left">
            <input type="text" name="nombre" value="<?=$nombre?>" size="10">
        </td>
    </tr>
    <tr>
        <td valign="top" align="right">
            <strong>Fecha Creación</strong>
        </td>
        <td valign="top" align="left">
<?=Html::PrintCalendar('fecha1',$fecha1, 'formProyectos');?>
&nbsp;&nbsp;
<?=Html::PrintCalendar('fecha2',$fecha2, 'formProyectos');?>
        </td>
    <tr>
        <td valign="top" align="right">
            &nbsp;
        </td>
        <td valign="top" align="left">
            <input type="submit" value="Buscar"><br>&nbsp;
        </td>
    </tr>
    <tr>
		<td colspan=2>
		<table width=100%>
		<tr>
        <td valign="top" class="texto_suave" align="left">
            &nbsp;<a href="#" class="texto_suave" onclick="OrdenarLista('glosa_empresa');">Nombre</a>
        </td>
        <td valign="top" class="texto_suave" align="center" width=20%>
            <a href="#" class="texto_suave" onclick="OrdenarLista('fecha_creacion');" align="center" width=20%>Creación</a>
        </td>
        <td valign="top" class="texto_suave" align="center" width=20%>
            Opciones
        </td>
		</tr>
		</table>
		</td>

	</tr>
</table>
</td>
</tr>
<tr><td colspan=4>
<table width=100%>
<?
       $where2= '';

        if( $nombre != '' )
        {
            $nombre = strtr( $nombre, ' ', '%' );
            $where2 = "(glosa_empresa Like '%$nombre%')";
        }
        if( $fecha1 != '' and $fecha2 != '')
        {
            if( $nombre != '' or $descripcion != '')
                $where2 .= " OR ";
            $fech1 = Utiles::fecha2sql($fecha1);
            $fech2 = Utiles::fecha2sql($fecha2);
            $where2 .= "(fecha_creacion BETWEEN '$fech1 00:00:00' AND '$fech2 23:59:59')";
        }

        if( $where2 == '' )
            $where2 = '1';

    $empresas = new ListaEmpresas ( $sesion,'', "SELECT SQL_CALC_FOUND_ROWS empresa . * FROM empresa WHERE ($where2)
                                                                ORDER BY $orden ASC
                                                                LIMIT $desde, $x_pag");
    echo Html::PrintListRows($sesion, $empresas, 'PrintRow');
    echo Html::PrintListPages($empresas, $desde, $x_pag, 'PrintLinkPage');

?>
</table>
<?
    function PrintRow(& $fila)
    {
       $fields = &$fila->fields;
	   $empresa = $fields['id_empresa'];
       $empresa_agrupador = $fields['id_noticia_agrupador'];

		global $sesion;
       $html_glosa = $fields['glosa_empresa'];
	   $fecha_cre = Utiles::sql2fecha($fields['fecha_creacion'],"%d/%m/%Y");
	   $img = Conf::ImgDir();
       $html .= <<<HTML
       <tr>
           <td valign="top" align="left">
            - $html_glosa<br>
			</td>
			<td align="center" width=20%>
				$fecha_cre
			</td>
			<td align="center" width=20%>
			<a href="../modulos/noticia/noticias_empresa.php?id_empresa=$empresa&id_noticia_agrupador=$empresa_agrupador"><img src=$img/add_noticia16.gif title="Agregar Noticia" border=0></a>
            <a href="../modulos/noticia/listar_noticias_empresa.php?id_empresa=$empresa"><img src=$img/noticia16.png title="Ver Noticia" border=0></a>
            <a href="../modulos/encuesta/encuesta_paso1.php?id_empresa=$empresa"><img src=$img/agregar_encuesta16.gif title="Agregar Encuesta" border=0></a>
            <a href="../modulos/encuesta/ver_encuestas.php?id_empresa=$empresa"><img src=$img/ver_encuesta16.gif title="Ver Encuestas" border=0></a>

	       	</td>
       </tr>
  <tr>
    <td  colspan="4">
        <hr size=1>
    </td>
 </tr>

HTML;
        return $html;
    }
?>
</table>
		</td>
	</tr>
</table>

<?
	$pagina->PrintBottom();
?>
