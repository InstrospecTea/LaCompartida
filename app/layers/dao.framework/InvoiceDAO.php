<?php

class InvoiceDAO extends AbstractDAO implements IInvoiceDAO {

	public function getClass() {
        return 'Invoice';
    }

}