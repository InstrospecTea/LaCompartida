<?php

class ChargeService extends AbstractService implements IChargeService {

    public function getDaoLayer() {
        return 'ChargeDAO';
    }


    public function getClass() {
        return 'Charge';
    }

}