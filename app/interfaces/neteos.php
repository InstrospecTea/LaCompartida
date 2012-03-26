<?php
	require_once dirname(__FILE__).'/../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	

	$sesion = new Sesion(array('ADM'));
        
        require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	$pagina = new Pagina($sesion);

	

	$pagina->titulo = __('Neteo de Documentos');
	$pagina->PrintTop();

?>
   <script  src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js"></script>
	
<script type="text/javascript">	
jQuery(document).ready(function() {
	 jQuery('#neteos').dataTable( {
		"bProcessing": true,
		"sAjaxSource": "ajax/ajax_neteos.php",
		 "bJQueryUI": true,
        "sPaginationType": "full_numbers"
	});
});

</script>	
<br>	
<table cellpadding="0" cellspacing="0" border="0" class="display" id="neteos">
	<thead>
		<tr>
			<th width="10%">Comentario</th>
			<th width="5%">Tipocobro</th>
			<th width="5%">iddoccobro</th>

			<th width="15%">Total Cobrado</th>
			<th width="15%">Total Pagado</th>
			<th width="15%">Doc Pago</th>
			<th width="15%">Adelanto</th>
			<th width="15%">Tipo Pago</th>
			<th width="15%">Tipo Pago</th>
			<th width="15%">Glosa</th>
		</tr>
	</thead>
	<tbody>
		
	</tbody>
	<tfoot>
		<tr>

			<th width="20%">Comentario</th>
			<th width="25%">Tipocobro</th>
			<th width="25%">iddoccobro</th>

			<th width="15%">Total Cobrado</th>
			<th width="15%">Total Pagado</th>
			<th width="15%">Doc Pago</th>
			<th width="15%">Adelanto</th>
			<th width="15%">Tipo Pago</th>
			<th width="15%">Tipo Pago</th>
			<th width="15%">Glosa</th>
		</tr>

	</tfoot>
</table>

<?php
	$pagina->PrintBottom();
?>