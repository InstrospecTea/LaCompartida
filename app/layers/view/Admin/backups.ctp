<?php
if (!defined('BACKUPDIR')) {
	die('Consulte con soporte para acceder a sus respaldos mediante esta pantalla');
}
echo $mensajedr;
?>
<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />

<div class="alert alert-warning"><b>V3:<b/> Estos son los respaldos disponibles para su sistema. Los enlaces de descarga sólo serán válidos por dos horas</div>

<form id="form_respaldo" method="post">
	<input type="hidden" id="dropname"/>
</form>

<div class="container-fluid">
	<div class="row-fluid">
		<table width="750px" class="table table-hover table-bordered table-striped">
			<thead>
				<tr>
					<th>Archivo</th>
					<th>Tamaño</th>
					<th>Fecha Modificación</th>
					<th style="width: 45px;">Dropbox</th>
				</tr></thead>
			<tbody>
				<?php foreach ($list as $file): ?>
					<?php $dropname = str_replace(SUBDOMAIN . '/', '', $file['name']); ?>
					<tr>
						<td>
							<?=
							$this->Html->link($dropname, "downloadBackup/{$dropname}", [
								'class' => 'iconzip',
								'style' => 'float:left; font-size:14px;']
							);
							?>
						</td>
						<td><?= round($file['size'] / (1024 * 1024), 2) . ' MB'; ?></td>
						<td><?= date('d-m-Y', $file['time']); ?></td>
						<td>
							<a class="dropbox" rel="<?= $dropname; ?>" href="#">
								<img src='https://static.thetimebilling.com/cartas/img/dropbox_ico.png'/>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			//			
			$('.dropbox').click(function (event) {
				event.preventDefault();
				$('#dropname')
					.attr('name', 'dropname')
					.val($(this).attr('rel'));
				$('#form_respaldo').submit();
			});
		});
	})(jQuery);
</script>