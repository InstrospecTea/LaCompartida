<table border="0" cellspacing="0" cellpadding="2" width="90%">
	<tr>
		<?php
		$sufimg = (( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() )) ? '_nuevo' : '';

		$paginas = array('',
			array('cobros2',		__('Selección').'<br/>'.__('Asuntos')),
			array('cobros3',		__('Selección').'<br/>'.__('Trabajos')),
			array('cobros_tramites',__('Selección').'<br/>'.__('Trámites')),
			array('cobros4',		__('Selección').'<br/>'.__('Gastos')),
			array('cobros5',		__('Emisión').'<br/>'),
			array('cobros6',		__('Facturación').'<br/>'.__('Cobranza')));

		//los pasos estan desordenados -_-
		if($paso==6) $paso = 3;
		else if($paso>2) $paso++;

		for($p=1; $p<=6; $p++){
			$textdec = 'line-through';
			if(!$incluye_honorarios && ($p==2 || $p==3))
				$url = "javascript:alert('".__('Este cobro es sólo de gastos, y no incluye honorarios')."')";
			else if(!$incluye_gastos && $p==4)
				$url = "javascript:alert('".__('Este cobro es sólo de honorarios, y no incluye gastos')."')";
			else{
				$url = Conf::RootDir().'/app/interfaces/'.$paginas[$p][0].'.php?id_cobro='.$id_cobro.'&popup=1&contitulo=true';
				$textdec = 'none';
			}
			?>
			<td align="right" style="font-size: 10px; vertical-align: middle;">
				<a href="<?=$url?>">
					<img src="<?=Conf::ImgDir().'/paso'.$p.'_'.($paso==$p ? 'on' : 'off').$sufimg.'.gif'?>" border="0">
				</a>
			</td>
			<td align="left" style="font-size: 10px; vertical-align: middle;">
				<a style="text-decoration:<?=$textdec?>;font-size:11px; color:<?=$paso==$p ? '#000000':'#CCCCCC'?>" href="<?=$url?>">
					<?=$paginas[$p][1]?>
				</a>
			</td>
		<?php } ?>
	</tr>
	<tr>
		<td colspan=10>
			&nbsp;
		</td>
	</tr>
</table>

