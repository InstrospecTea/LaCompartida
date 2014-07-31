<?php
require_once dirname(__FILE__) . '/../../conf.php';
?>
<body id="pagina_body" onload="SetFocoPrimerElemento();">
	<div id="mainttb" style="padding: 10px 0 5px ;" >

		<?php if ($this->num_infos > 0) { ?>

			<table width="100%" class="info">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<?php echo $this->GetInfos(); ?>
					</td>
				</tr>
			</table>
		<?php
		}
		if ($this->num_errors > 0) {
			?>
			<table width="100%" class="alerta">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<strong>Se han encontrado los siguientes errores:</strong><br/>
						<?php echo $this->GetErrors(); ?>
					</td>
				</tr>
			</table>
			<?php
		}
