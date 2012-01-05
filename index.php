<?
/*$useragent=$_SERVER['HTTP_USER_AGENT'];
if(preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
header('Location: ./m/');*/

	require_once dirname(__FILE__).'/fw/classes/Sesion.php';
	require_once dirname(__FILE__).'/fw/classes/Pagina.php';
 
	$sesion = new Sesion(null, true);
	
	$sesion->CheckLogin(); #Chequea cookies y hace login
	
	$pagina = new Pagina($sesion, true);

	$_SESSION['ERROR'] = '';

	$pagina->PrintHeaders();
?>

<table width="100%" height="100%">
	<tr>
		<td align="center">
			<br><br><br><br><br><br>

<table cellspacing="0" cellpadding="0" style="border: 1px solid #999999;">
	<tr>
		<td align="center" style="padding: 8px;" bgcolor="#efefef">
		<?= Conf::AppName() ?>
	</td>
	</tr>
	<tr>
		<td align="center" bgcolor="#999999"></td>
	</tr>
	<tr>
		<td align="center" style="padding: 8px;">
<table width="100%" cellspacing="2" cellpadding="2">
 <form action="fw/usuarios/login.php" method="post">
	<tr>
		<td align="right" rowspan="3">
			<? 
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists( 'Conf', 'UsaDisenoNuevo' ) && Conf::UsaDisenoNuevo() ) ) 
				{ ?>
					<img src="<?=Conf::ImgDir()?>/logo_lemontech_ttb.jpg" width="175" height="70" />
		<?	}
			else 
				{ ?>
					<img src="<?= Conf::Logo() ?>" /> 
		<?	} ?>
		</td>
		<td align="right">
			<?=( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'NombreIdentificador') : Conf::NombreIdentificador() )?>:
		</td>
		<td align="left" nowrap>
			<? if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NombreIdentificador')=='Cédula' ) || ( method_exists('Conf','NombreIdentificador') && Conf::NombreIdentificador()=='Cédula' ) )
			        || ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NombreIdentificador')=='CNI' ) || ( method_exists('Conf','NombreIdentificador') && Conf::NombreIdentificador()=='CNI' ) ) 
					|| ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NombreIdentificador')=='DNI' ) || ( method_exists('Conf','NombreIdentificador') && Conf::NombreIdentificador()=='DNI' ) )) { ?>
				<input type="text" name="rut" value="" size="17">
			<? } else { ?>
				<input type="text" name="rut" value="" size="10">-<input type="text"  name="dvrut" value="" size="1">
			<? } ?>
				<br>
		</td>
	</tr>
	<tr>
		<td align="right">
			Password:
		</td>
		<td align="left">
			<input type="password" name="password" value="" size="17">
		</td>
	</tr>
<?
	//Revisa el Conf si esta permitido y la función existe
	if( method_exists( 'Conf','GetConf' ) )
		$RecordarSesion = Conf::GetConf( $sesion, 'RecordarSesion');
	else if( method_exists( 'Conf', 'RecordarSesion' ) )
		$RecordarSesion = Conf::RecordarSesion();
	else
		$RecordarSesion = false;
		
	if( $RecordarSesion )
	{
?>
	<tr>
		<td colspan=2 align=right style='vertical-align:top; font-size:10px'>
			Recordar en este equipo&nbsp;&nbsp;<input type=checkbox name='recordar' id='recordar' value=1 />
		</td>
	</tr>
<?
	}
	else
	{
?>
	<tr>
		<td colspan=2 align=right style='vertical-align:top; font-size:10px'>
			Recordar en este equipo&nbsp;&nbsp;<input type=checkbox name='recordar' id='recordar' value=1 />
		</td>
	</tr>
<?
	}
?>
	<tr>
		<td align="right">
			&nbsp;
		</td>
		<td align="left">
			<input type="submit" class=btn value="Entrar">
		</td>
	</tr>
 </form>
</table>

		</td>
	</tr>
	<tr>
		<td align="center">

<?
	if($sesion->error_msg != '')
	{
?>

<table width="80%" class="alerta">
	<tr>
		<td valign="top" align="left" style="font-size: 12px;">
			<?=$sesion->error_msg?>
		</td>
	</tr>
</table>

			<br>

<?
	}
?>

		</td>
	</tr>
</table>

		</td>
	</tr>
</table>
