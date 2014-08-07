<?php

abstract class AbstractService implements BaseService{

    var $sesion;

    public function __construct(Sesion $sesion) {
        $this->sesion = $sesion;
    }

    public function saveOrUpdate($object) {
        $this->checkNullity($object);
        $this->checkClass($object, $this->getClass());
        $daoClass = $this->getDaoLayer();
        $dao = new $daoClass($this->sesion);
        try {
            return $dao->saveOrUpdate($object);
        } catch (Exception $ex) {
            throw new Exception($ex);
        }

    }

    public function get($id) {
        $this->checkNullity($id);
        $daoClass = $this->getDaoLayer();
        $dao = new $daoClass($this->sesion);
        try {
            return $dao->get($id);
        } catch (Exception $ex) {
            throw new Exception($ex);
        }
    }

    public function findAll() {
        $daoClass = $this->getDaoLayer();
        $dao = new $daoClass($this->sesion);
        try {
            return $dao->findAll();
        } catch (Exception $ex) {
            throw new Exception($ex);
        }
    }

    public function delete($object) {
        $this->checkNullity($object);
        $this->checkClass($object, $this->getClass());
        $daoClass = $this->getDaoLayer($this->sesion);
        $dao = new $daoClass($this->sesion);
        try{
            $dao->delete($object);
        } catch (Exception $ex) {
	        print_r('upsi');
            throw new Exception($ex);
        }
    }

    /**
     * Comprueba si un objeto es parte de la jerarquía de clases definida en la capa.
     * @param $object Objeto que se comprobará.
     * @param $className string Jerarquía de clases a la que debe pertenecer.
     * @throws Exception Cuando no pertenece a la jerarquía de clases correspondiente.
     */
    protected function checkClass($object, $className) {
        if (!is_a($object, $className)) {
            throw new Exception('Dao Exception: El objeto entregado no pertenece ni hereda a la clase definida en DAO.');
        }
    }

    /**
     * Comprueba si un objeto es nulo o está vacío. Esto es necesario para arrojar la excepción correspondiente para cuando
     * sean realizadas operaciones de CRUD.
     * @param $object
     * @throws Exception
     */
    protected function checkNullity($object) {
        if (empty($object)) {
            throw new Exception('El identificador que está siendo utilizado para obtener el objeto '.$this->getClass().' está vacío o es nulo.');
        }
    }

}