<?
	require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
    require_once Conf::ServerDir().'/fw/classes/Empresa.php';


	$sesion = new Sesion( array('ADM') );

	$pagina = new Pagina($sesion);

    $rut = $sesion->usuario->fields['rut'];


    $emp = new Empresa($sesion);
    if(!$emp->Load($id_empresa))
	    $pagina->FatalError("Empresa inválida");


    if ($opc=='siguiente')
    {
        $encuesta = new Encuesta($sesion);
        $encuesta->Edit('titulo',$titulo);
        $encuesta->Edit('id_empresa',$id_empresa);
        $encuesta->Edit('rut_creador',$rut);
		$encuesta->Edit('estado','CREADA');
        if($encuesta->Write())
            $pagina->Redirect( 'encuesta_paso2.php?id_encuesta='.$encuesta->fields['id_encuesta'] );
        else
            $pagina->AddError("no se pudo guardar la encuesta");
    }

    	    
	$pagina->titulo ="Nueva Encuesta";

	$pagina->PrintHeaders();

	$pagina->PrintTop();

?>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
		<table width="100%" align="left">
		 <form id="form" name="Encuesta" method="post">
		 <input type="hidden" name="opc" value="siguiente">
         <input type="hidden" name="id_empresa" value="<?=$id_empresa?>">

			<tr>
				<td colspan=2 align=center>
				<strong>Ingrese el nombre de la encuesta:</strong>
				</td>
			<tr>
			<tr>
				<td align=center>
				<textarea name="titulo" cols=50></textarea><br><br>
				 <input type="submit" value="Siguiente">	
				</td>
			</tr>
		</form>
		</table>
        </td>
    </tr>
</table>
<?
    $pagina->PrintBottom();
?>

