<?php

class ChargeDAO extends AbstractDAO implements IChargeDAO{

		public $log_update = true;

    public function getClass() {
        return 'Charge';
    }



}