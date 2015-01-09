<h1>Sandbox!</h1>
<p>
	Cantidad de cobros: <?php echo $Pagination->total_rows(); ?>
</p>

<?php
echo $this->Paginator->pages($Pagination);
echo $this->Paginator->pages($Pagination, true, false, 6);
echo $this->Paginator->pages($Pagination, true, true, 6);
$this->EntitiesListator->loadEntities($results);
$this->EntitiesListator->addColumn('# Cobro', 'id_cobro');
$this->EntitiesListator->addColumn('Cliente', 'codigo_cliente');
$this->EntitiesListator->addColumn('Estado', 'estado');
echo $this->EntitiesListator->render();