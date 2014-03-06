<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$Sesion = new Sesion(array('ADM'));
$Pagina = new Pagina($Sesion);
$Pagina->titulo = __('Librofresco');
$Pagina->PrintTop();

if (!$Sesion->usuario->TienePermiso('SADM')) {
	die('No Autorizado');
}

$servidores = array('192.168.1.24', '192.168.2.101', '192.168.2.102', 'rdsdb1.thetimebilling.com', 'rdsdb2.thetimebilling.com', 'rdsdb3.thetimebilling.com', 'rdsdb4.thetimebilling.com', 'rdsdb5.thetimebilling.com', 'rdsdb6.thetimebilling.com');
$dbhost = isset($_POST['dbhost']) ? $_POST['dbhost'] : Conf::dbHost();
$error_coneccion = '';

try {
	$dbhost = $_POST['dbhost'] ? $_POST['dbhost'] : $servidores[0];
	$connection = "mysql:dbname=phpmyadmin;host={$dbhost}";

	switch ($dbhost) {
		case '192.168.1.24':
			$Sesion->pdodbh2 = new PDO($connection, 'root', 'asdwsx');
			$database_filter = 'lemontest_%';
			break;
		case '192.168.2.101':
		case '192.168.2.102':
			$Sesion->pdodbh2 = new PDO($connection, 'root', 'admin.asdwsx');
			$database_filter = '%_timetracking';
			break;
		default:
			$Sesion->pdodbh2 = new PDO($connection, 'admin', 'admin1awdx');
			$database_filter = '%_timetracking';
			break;
	}

	$Sesion->pdodbh2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$rs_bases = $Sesion->pdodbh2->query("SHOW DATABASES LIKE '{$database_filter}'");
	$bases = $rs_bases->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
	$error_coneccion = "Error Connection: " . $e->getMessage();
}

function fieldExist(&$pdodbh, $table, $field) {
	$rs = $pdodbh->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
	$_field = $rs->fetchAll(PDO::FETCH_COLUMN, 0);
	return empty($_field) ? false : true;
}
?>

<style>
	.cont_tabla {
		padding: 0 30px !important;
	}
	table {
		width: 100%;
	}
	table th {
		text-align: left;
		background-color: #ccc;
		padding: 5px;
		font-size: 12px;
	}
	table td {
		text-align: left;
	}
	table th.cliente {
		width: 50%;
	}
	table td.usuarios {
		width: 10%;
		text-align: right;
	}
	table td.actualizar {
		width: 10%;
		text-align: center;
	}
	a.error {
		color: red;
	}
	div.error {
		padding: 5px;
		margin-top: 10px;
		border: 1px solid red;
		color: red;
	}
	div.actualizar_todos {
		padding: 5px;
		margin-top: 10px;
		text-align: right;
	}
	a.actualizar_todos {
		font-size: 12px;
		font-weight: bold !important;
	}
	form label {
		font-weight: bold !important;
	}
	form select {
		width: 200px !important;
	}
</style>

<form method="POST">
	<table>
		<tr>
			<td>
				<label>Host de Base de Datos: </label>
				<?php echo Html::SelectArray($servidores, 'dbhost', $dbhost); ?>
				<input type="submit" value="Filtrar">
			</td>
		</tr>
	</table>
</form>

<?php if (empty($error_coneccion)) { ?>
	<div class="actualizar_todos"><a href="#" class="actualizar_todos">Actualizar a todos los clientes</a></div>
	<table>
		<thead>
			<tr>
				<th class="cliente">Cliente</th>
				<th>Timekeeper</th>
				<th>Adminitrative</th>
				<th>Casetracking</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$_database_filter = str_replace('%', '', $database_filter);
				$_database_filter = str_replace('_', '', $_database_filter);

				foreach ($bases as $base) {
					$_base = str_replace($_database_filter, '', $base);
					$_base = str_replace('_', '', $_base);

					try {
						$Sesion->pdodbh2->exec("USE `{$base}`");
						$query = "SELECT count(*) AS `total` FROM `usuario`";

						if (fieldExist($Sesion->pdodbh2, 'usuario', 'activo_juicio')) {
							$usuario_juicio = "IF(`usuario`.`activo_juicio` = 0, 0, 1) AS 'casetracking'";
						} else {
							$usuario_juicio = "0 AS 'casetracking'";
						}

						$query = "SELECT
							COUNT(*) AS 'usuarios', SUM(timekeeper) AS 'timekeeper',
							(COUNT(*) - SUM(timekeeper)) AS 'administrative',
							SUM(casetracking) AS 'casetracking'
							FROM (
								SELECT
									`usuario`.`id_usuario`,
									IF(`usuario_permiso`.`id_usuario` IS NULL, 0, 1) AS 'timekeeper',
									{$usuario_juicio}
								FROM `usuario`
									LEFT JOIN  `usuario_permiso` ON `usuario_permiso`.`id_usuario` = `usuario`.`id_usuario` AND `usuario_permiso`.`codigo_permiso` = 'PRO'
								WHERE `usuario`.`nombre` != 'Admin' AND `usuario`.`activo` = 1
								GROUP BY `usuario`.`id_usuario`
							) AS tmp";

						$rs_clientes = $Sesion->pdodbh2->query($query);
						$clientes = $rs_clientes->fetchAll(PDO::FETCH_ASSOC);
						foreach ($clientes as $cliente) {
							?>
							<tr>
								<td><?php echo $_base; ?></td>
								<td class="usuarios"><?php echo $cliente['timekeeper']; ?></td>
								<td class="usuarios"><?php echo $cliente['administrative']; ?></td>
								<td class="usuarios"><?php echo $cliente['casetracking']; ?></td>
								<td class="actualizar">
									<a href="javascript:void(0)"
										class="actualizar"
										data-client="<?php echo $_base; ?>"
										data-timekeeper="<?php echo $cliente['timekeeper']; ?>"
										data-administrative="<?php echo $cliente['administrative']; ?>"
										data-casetracking="<?php echo $cliente['casetracking']; ?>"
										data-subdomain="<?php echo base64_encode("http://{$_base}.thetimebilling.com"); ?>">
										Actualizar
									</a>
								</td>
							</tr>
							<?php
						}
					} catch (PDOException $e) {
						?>
						<tr>
							<td><?php echo $_base; ?></td>
							<td class="usuarios">0</td>
							<td class="usuarios">0</td>
							<td class="usuarios">0</td>
							<td class="actualizar">
								<a href="javascript:void(0)" class="error" data-error="<?php echo addslashes($e->getMessage()); ?>">Error!</a>
							</td>
						</tr>
						<?php
					}
				}
			?>
		</tbody>
	</table>

	<script type="text/javascript">
		jQuery(function() {
			jQuery('a.error').on('click', function() {
				alert(jQuery(this).attr('data-error').replace(/\\/gi, ''));
			});

			jQuery('a.actualizar').on('click', function() {
				jQuery.ajax({
					url: 'librofresco_api.php',
					type: 'POST',
					dataType: 'json',
					data: {
						client: jQuery(this).attr('data-client'),
						timekeeper: jQuery(this).attr('data-timekeeper'),
						administrative: jQuery(this).attr('data-administrative'),
						casetracking: jQuery(this).attr('data-casetracking'),
						subdomain: jQuery(this).attr('data-subdomain')
					},
					success: function (data) {
						console.log(data);
					},
					error: function (data) {
						console.log(data);
					}
				});
			});

		});
	</script>
<?php } else { ?>
	<div class="error">
		<b>Error</b>: <?php echo $error_coneccion; ?>
	</div>
<?php } ?>

<?php $Pagina->PrintBottom();
