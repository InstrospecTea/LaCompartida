<?php
	require_once dirname(__FILE__).'/../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	
	
	$sesion = new Sesion(array('ADM'));
        $moneda_base = Utiles::MonedaBase($sesion);
	
        require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	$pagina = new Pagina($sesion);

	

	$pagina->titulo = __('Diferencia Liquidaciones Emitidas vs Facturadas');
	$pagina->PrintTop();
/*$currency=array();
$querycurrency="select * from prm_moneda";
 $respcurrency = mysql_query($querycurrency, $sesion->dbh);
 $i=0;
while($fila= mysql_fetch_assoc($respcurrency)) {
    $currency[++$i]=$fila;
}
foreach($currency as $key=>$value) {
    echo '<br/>'.$currency[$key]['simbolo'].' '.$key;
    print_r($value);
    
}*/
?>
    <style type="text/css">
      @import "//static.thetimebilling.com/css/jquery.dataTables.css";
    </style>
   <script  src="//static.thetimebilling.com/js/jquery.dataTables.min.js"></script>
	
<script type="text/javascript">	

jQuery(document).ready(function() {
    
    jQuery('#buscar').click(function() {
  var Fechai=jQuery('#fechai').val();
  var Fechaf=jQuery('#fechaf').val();
  var Todo=jQuery('#todo').is(':checked');
  var Idmoneda=jQuery('#id_moneda').val();
  var laFuente="ajax/diferencias_liq_fac.php?fechai="+Fechai+"&fechaf="+Fechaf+"&todo="+Todo+"&id_moneda="+Idmoneda;
	if(typeof(console)!==undefined) console.log(laFuente);
	jQuery('#diffs').dataTable({ "bDestroy":true});
	
	     jQuery('#diffs').dataTable({
		  "bDestroy":true,
               
		 		"oLanguage": {   
		    "sProcessing":   "Procesando..." ,
		    "sLengthMenu":   "Mostrar _MENU_ registros",
		    "sZeroRecords":  "No se encontraron resultados",
		    "sInfo":         "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
		    "sInfoEmpty":    "Mostrando desde 0 hasta 0 de 0 registros",
		    "sInfoFiltered": "(filtrado de _MAX_ registros en total)",
		    "sInfoPostFix":  "",
		    "sSearch":       "Buscar:",
		    "sUrl":          "",
		    "oPaginate": {
			"sFirst":    "Primero",
			"sPrevious": "Anterior",
			"sNext":     "Siguiente",
			"sLast":     "Último"
		 }
	 },
	 "bFilter": false,
		   "aoColumnDefs": [
		        { "bVisible": false, "aTargets": [ 0 ] },
	{  "fnRender": function ( o, val ) {
          return "<a style='text-align:left;float:left;' href=\"#\" onclick=\"nuovaFinestra('Editar_Contrato',800,700,'agregar_contrato.php?id_contrato="+o.aData[0]+"&popup=1', '');\">"+o.aData[1]+"</a>";
        },    "aTargets": [ 1 ]   },
	{  "fnRender": function ( o, val ) {
          return "<a href=\"#\" onclick=\"nuevaVentana('Editar_Cobro',1000,700,'cobros6.php?id_cobro="+o.aData[3]+"&popup=1&contitulo=true&id_foco=7', '');\">"+o.aData[3]+"</a>";
        },    "aTargets": [ 3 ]   },
      { "sType": "numeric", "aTargets": [ 3,4,5 ] }
    
    ],
		    "bProcessing": true,
		    "sAjaxSource": laFuente,
		     "bJQueryUI": true,
		     "bDeferRender": true,
		
	 
	    "iDisplayLength": 25,
	    "aLengthMenu": [[25, 50, 100,200, -1], [25, 50, 100,200, "Todo"]],
	    "sPaginationType": "full_numbers",
	    "sDom": '<"top"lp>rt<"bottom"i>'
	   
	  //,"aaSorting": [[ 2, "asc" ]]
	     }).show();
    });
});
 
</script>	

<div style="margin:auto;padding:10px;border:1px solid #CCC;">Buscar <?php echo __('Cobros emitidos');?> con diferencias entre el monto Emitido y el Facturado<br/><br/>
    Emitidos entre el 
    <input type="text" class="fechadiff" id="fechai" style="width:100px;"> 
    &nbsp;y el&nbsp; 
    <input class="fechadiff" type="text" id="fechaf" style="width:100px;"> 
    y desplegar los montos en&nbsp;
    <?php echo Html::SelectQuery($sesion,"SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", '','', 'la moneda del contrato '); ?>
     <br/><input id="buscar" value="buscar" type="button"/>&nbsp;&nbsp;<input type="checkbox" id="todo" name="todo"> Incluir liquidaciones saldadas (Gran cantidad de datos)
   
<br/>	<br/>
<?php print_r($moneda->fields); ?>
</div><br/>
<table cellpadding="0" cellspacing="0" border="0" class="display" id="diffs" style="width:920px;display:none;">
	<thead>
		<tr>
		    <th width="0px">idcontrato</th>
			<th width="315px" style="white-space:nowrap;overflow:hidden;">Cliente</th>
			<th width="100px">Fecha Emitido</th>
			<th width="90px"><?php echo __('Cobro');?></th>
			<th width="90px">$ Emitido</th>
			<th width="90px">$ Facturado</th>
			<th width="90px">Diferencia</th>
			 
			
		</tr>
	</thead>
	<tbody>
		
	</tbody>
	<tfoot style="font-size:10px;">
		<tr>
<th>idcontrato</th>
			<th >Cliente</th>
			<th >Fecha Emitido</th>
			<th><?php echo __('Cobro');?></th>
			<th >$ Emitido</th>
			<th width="90px">$ Facturado</th>
			<th width="90px">Diferencia</th>
			
		</tr>

	</tfoot>
</table>

<?php
	$pagina->PrintBottom();
?>