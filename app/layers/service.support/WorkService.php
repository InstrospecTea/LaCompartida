<?php

class WorkService extends AbstractService implements IChargeService {

    public function getDaoLayer() {
        return 'WorkDAO';
    }


    public function getClass() {
        return 'Work';
    }

}