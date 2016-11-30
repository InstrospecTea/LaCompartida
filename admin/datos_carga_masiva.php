<?php
require_once dirname(__FILE__) . '/../app/conf.php';
$Html = new \TTB\Html();
$Form = new Form();
$sesion = new Sesion(array('ADM'));

$pagina = new Pagina($sesion);
$pagina->titulo = __("Carga masiva de $clase");
$pagina->PrintTop();

$CargaMasiva = new CargaMasiva($sesion);

$listados = $CargaMasiva->ObtenerListados($clase);

$campos_clase = $CargaMasiva->ObtenerCampos($clase);
$titulos_campos = array_map(function($campo) {
		return $campo['titulo'];
	}, $campos_clase);

if (isset($raw_data)) {
	$data = $CargaMasiva->ParsearData($raw_data);
	if (!empty($data)) {
		$llaves_campos = array_keys($campos_clase);
		$campos = array();
		foreach (array_keys($data[0]) as $idx) {
			$campos[] = $llaves_campos[$idx];
		}
	} else {
		$campos = array_keys($campos_clase);
	}
}

if (empty($data)) {
	$data[] = array_fill(0, count($campos), '');
} else if (count($campos) > count($data[0])) {
	$campos = array_slice($campos, 0, count($data[0]));
} else {
	while (count($campos) < count($data[0])) {
		$campos[] = '';
	}
}
?>
<style type="text/css">
	#data thead tr{
		height: 40px;
		background-color: #42A62B;
	}
	#data tbody td {
		padding: 5px;
	}
	#data tbody tr:nth-child(odd){
		background-color: #eee;
	}
	#data select{
		max-width: 100px;
	}
	.ok{
		background-color: #cfc !important;
	}
	.warning{
		background-color: #ffc !important;
	}
	.error{
		background-color: #fcc !important;
	}
	.procesando{
		background-color: #ccf !important;
	}
</style>

<button id="btn_agregar_columna">Agregar Columna</button>
<button id="btn_agregar_fila">Agregar Fila</button>
<table id="data">
	<thead>
		<tr>
			<?php foreach ($campos as $c => $campo) { ?>
				<th>
					<?php echo Html::SelectArrayDecente($titulos_campos, "campos[$c]", $campo, '', '(ignorar)'); ?>
					<button class="btn_eliminar_columna">X</button>
				</th>
			<?php } ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($data as $idx => $fila) { ?>
			<tr <?php echo isset($errores[$idx]) ? 'class="error" title="' . $errores[$idx] . '"' : ''; ?>>
				<?php
				$c = 0;
				foreach ($fila as $col) {
					?>
					<td>
						<input id="<?php echo "data_{$idx}_{$c}"; ?>" name="<?php echo "data[$idx][$c]"; ?>" value="<?php echo $col; ?>" class="col_<?php echo $c; ?>"/>
						<div class="extra"/>
					</td>
					<?php
					$c++;
				}
				?>
				<td><button class="btn_eliminar_fila">X</button></td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<input type="hidden" name="clase" value="<?php echo $clase; ?>"/>
<button id="btn_enviar">Enviar</button>

<div style="display: none">
<?php
foreach ($listados as $tipo => $lista) {
	echo $Form->select("select-{$tipo}", $lista, null, array('name' => null, 'empty' => 'Select...', 'translate' => false));
}
?>
</div>

<?= $Html->scriptVarBlock(array(
	'clase' => $clase,
	'llave' => $CargaMasiva->LlaveUnica($clase),
	/**
	 * listados[nombre][id] = glosa
	 * @type json
	 */
	'listados' => $listados,
	/**
	 * campos_clase[campo] = {titulo, tipo, unico, relacion, creable}
	 * @type json
	 */
	'campos_clase' => $campos_clase
)); ?>

<?= $Html->script(Conf::RootDir() . '/app/layers/assets/js/carga_masiva.js'); ?>
