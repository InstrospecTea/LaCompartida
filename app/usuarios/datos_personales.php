<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';

	$sesion = new Sesion('');
	
	$pagina = new Pagina($sesion);

	$usuario = $sesion->usuario;

	if($opc == 'edit')
    {
        $usuario->Edit('telefono1', $telefono1);
        $usuario->Edit('telefono2', $telefono2);
        $usuario->Edit('dir_calle', $dir_calle);
        $usuario->Edit('dir_numero', $dir_numero);
        $usuario->Edit('dir_depto', $dir_depto);
        $usuario->Edit('dir_comuna', $dir_comuna);
        $usuario->Edit('email', $email);

		if( $usuario->Write() )
			$pagina->AddInfo( __('Usuario editado con éxito.') );
		else
			$pagina->AddError( $usuario->error );

        if( ! $usuario->loaded )
        {
            $new_password = Utiles::NewPassword();

            $usuario->Edit('password', md5( $new_password ) );

            if( $usuario->Write() )
            {
                $pagina->AddInfo( __('Usuario ingresado con éxito, su nuevo password es').' '.$new_password );
            }
            else
            {
                $pagina->AddError( $usuario->error );
            }
        }
    }
    else if($opc == 'pass' and $usuario->loaded)
    {
        if($genpass > 0)
            $new_password = Utiles::NewPassword();

        $usuario->Edit('password', md5( $new_password ) );

        if( $usuario->Write() )
        {
            $pagina->AddInfo( __('Contraseña modificada con éxito') );

            if($genpass > 0)
                $pagina->AddInfo( __('Nueva contraseña:').' '.$new_password );
        }
        else
        {
            $pagina->AddError( $usuario->error );
        }
	}
	else if($opc == 'cancela')
	{
		$pagina->Redirect("../../fw/usuarios/index.php");
	}

	$pagina->titulo = __('Datos personales');

	$pagina->PrintTop();

	$rut_limpio = Utiles::LimpiarRut( $rut );
?>
<script language="javascript" type="text/javascript">
function Validar(form)
{
	if(form.email.value == "")
	{
		alert("<?=__('Debe ingresar una dirección de correo electrónico')?>");
		form.email.focus();
		return false;
	}
	return true;
}
function Cancelar()
{
	var form = document.getElementById('form_datos');
	form.opc.value = 'cancela';
}
</script>

<style>
tr.extendido
{
		display:none;
}
</style>

<form action="datos_personales.php" method="post" enctype="multipart/form-data" onsubmit="return Validar(this);" id="form_datos">
<input type="hidden" name="opc" value="edit" />
<input type="hidden" name="rut" value="<?=$rut?>" />
<input type="hidden" name="dv_rut" value="<?=$dv_rut?>" />

<table width="90%"><tr><td>
<fieldset class="border_plomo tb_base">
<legend><?=__('Datos personales')?></legend>
<table width=100%>
          <tr> 
            <td width="147" align="right" valign="top" class="texto"> <strong><?=__('RUT personal')?></strong> 
            </td>
            <td width="223" align="left" valign="top" class="texto"> <strong> 
              <?=$sesion->usuario->fields['rut']?>
              - 
              <?=$sesion->usuario->fields['dv_rut']?>
              </strong> </td>
          </tr>
          <tr> 
            <td valign="top" class="texto" align="right"> <?=__('Nombre Completo')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="nombre" value="<?=$usuario->fields['nombre']?>" size="30" style="" readonly /> 
              <span class="req">*</span> </td>
          </tr>
          <tr> 
            <td valign="top" class="texto" align="right"> <?=__('Apellido Paterno')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="apellido1" value="<?=$usuario->fields['apellido1']?>" size="20" style="" readonly /> 
              <span class="req">*</span> </td>
          </tr>
          <tr> 
            <td valign="top" class="texto" align="right"> <?=__('Apellido Materno')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="apellido2" value="<?=$usuario->fields['apellido2']?>" size="20" style="" readonly /> 
              <span class="req">*</span> </td>
          </tr>
          <tr> 
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
          <!-- spacer -->
          <tr class='extendido'> 
            <td valign="top" class="texto" align="right"><?=__('Dirección')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="dir_calle" value="<?=$usuario->fields['dir_calle']?>" size="30"/> 
              </td>
          </tr>
          <tr class='extendido'> 
            <td valign="top" class="texto" align="right"> <?=__('Número')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="dir_numero" value="<?=$usuario->fields['dir_numero']?>" size="8"/> 
              </td>
          </tr>
          <tr class='extendido'> 
            <td valign="top" class="texto" align="right"> <?=__('Departamento')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="dir_depto" value="<?=$usuario->fields['dir_depto']?>" size="8"/> 
            </td>
          </tr>
          <tr class='extendido'> 
            <td valign="top" class="texto" align="right"> <?=__('Comuna')?> </td>
            <td valign="top" class="texto" align="left"> 
              <?=Html::SelectQuery($sesion,'SELECT id_comuna,glosa_comuna FROM prm_comuna ORDER BY glosa_comuna','dir_comuna', $usuario->fields['dir_comuna'])?>
              </td>
          </tr>
          <tr> 
            <td valign="top" class="texto" align="right"> <?=__('Teléfono')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="telefono1" value="<?=$usuario->fields['telefono1']?>" size="16"/> 
              </td>
          </tr>
          <tr class='extendido'> 
            <td valign="top" class="texto" align="right"> <?=__('Teléfono')?> 2 </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="telefono2" value="<?=$usuario->fields['telefono2']?>" size="16"/> 
            </td>
          </tr>
          <tr> 
            <td valign="top" class="texto" align="right"> <?=__('E-Mail')?> </td>
            <td valign="top" class="texto" align="left"> <input type="text" name="email" value="<?=$usuario->fields['email']?>" size="30"/> 
            <span class="req">*</span> </td>
          </tr>

          <tr> 
			<td>&nbsp;</td>
            <td valign="top" align="left" class="texto10" colspan="2"> 
              <span style="font-size:9px;"><?=__('Los campos marcados con un asterisco rojo son obligatorios.')?></span>
            </td>
          </tr>
          <tr> 
            <td valign="top" class="texto" align="center" colspan="2"> 
				<br/>
				<input type="submit" class=btn value="<?=__('Aceptar')?>" /><input type="submit" class=btn value="<?=__('Cancelar')?>" onclick="return Cancelar();" /> 
            </td>
          </tr>
      </table>
		</fieldset>
        </form>


<?
    if($usuario->loaded)
    {
?>

<form  method="post" action="<?= $SERVER[PHP_SELF] ?>">
  <input type="hidden" name="opc" value="pass" />
  <input type="hidden" name="rut" value="<?= $rut ?>" />
  <input type="hidden" name="dv_rut" value="<?= $dv_rut ?>" />
<fieldset class="border_plomo tb_base">
<legend><?=__('Cambio de contraseña')?></legend>
<table width="100%">
    <tr>
        <td colspan="2" class="texto" align="left">
            <strong><?=__('Atención')?>:</strong><?=__('La contraseña anterior será reemplazada e imposible de recuperar.')?><br/>
        </td>
    </tr>
    <tr>
        <td width="20">&nbsp;</td>
        <td class="texto" align="left">
            <input type="radio" name="genpass" value="0" id="new_pass" />
            <label for="new_pass"><?=__('Contraseña nueva')?></label>
            <input type="text" name="new_password" value="" size="16" onclick="javascript:document.getElementById('new_pass').checked='checked'"/><br/>
            <input type="radio" name="genpass" value="1" checked="checked" id="rand_pass" />
            <label for="rand_pass"><?=__('Generar contraseña aleatoria')?></label>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2">
            <input type="submit" class=btn value="<?=('Cambiar Contraseña')?>" size="16"/>
        </td>
    </tr>
</table>
</fieldset>
</td></tr></table>
</form>


<?
    }
	$pagina->PrintBottom();
?>
