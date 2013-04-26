<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

if ($_REQUEST['codigo_cliente'] == '') {
	return "";
}

$ClienteSeguimiento = new ClienteSeguimiento($Sesion);

if ($_REQUEST['opcion'] == 'guardar') {
	$ClienteSeguimiento->Fill($_REQUEST, true);
	$ClienteSeguimiento->Edit('id_usuario', $Sesion->usuario->fields['id_usuario']);

	if ($ClienteSeguimiento->Write()) {
		// todo ok
	}
} else {
	$ClienteSeguimiento->Fill($_REQUEST);
}

$seguimientos = $ClienteSeguimiento->FindAll();
?>
<div class="seguimiento_container">
	<div class="seguimiento_table">
		<table>
			<thead>
				<th>Usuario</th>
				<th>Comentario</th>
			</thead>
			<tbody>
				<?php foreach ($seguimientos as $s) { ?>
				<tr>
					<td><?php echo $s['username']; ?></td>
					<td><?php echo $s['comentario']; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<form action="#" method="POST">
		<textarea name="comentario"></textarea>
		<input type="hidden" name="codigo_cliente" value="<?php echo $ClienteSeguimiento->fields['codigo_cliente']; ?>" />
		<input type="hidden" name="opcion" value="guardar" />
		<input type="submit" value="Guardar" />
	</form>
</div>

<!-- Archivo /Users/morellan/Documents/Proyectos/Lemontech/ttb/fw/classes/Objeto.php
 Linea 146
 Mensaje SQL SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`cdrabogados_timetracking`.`cliente_seguimiento`, CONSTRAINT `cliente_seguimiento_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`))
 Mensaje Adicional  (Traza PDO)  #0 /Users/morellan/Documents/Proyectos/Lemontech/ttb/fw/classes/Objeto.php(146): PDO->query('INSERT INTO cli...')
#1 /Users/morellan/Documents/Proyectos/Lemontech/ttb/app/interfaces/ajax/ajax_seguimiento.php(16): Objeto->Write()
#2 {main}\n-->