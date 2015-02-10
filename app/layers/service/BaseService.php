<?php

interface BaseService {

    /**
     * Persiste un objeto. Crea un nuevo registro si el objeto no lleva id. Si lleva id, se actualiza el objeto existente.
     * @param Entity $object
     * @param boolean $writeLog Define si se escribe o no el historial de movimientos.
     * @throws Exception
     */
    public function saveOrUpdate($object, $writeLog);

    /**
     * Obtiene una instancia del objeto manejado por la capa, a partir de su identificador primario.
     * @param $id
     */
    public function get($id);

    /**
     * Obtiene un array con todas las instancias del objeto manejado por la capa.
     */
    public function findAll();

    /**
     * Elimina un objeto desde el medio persistente en base a la columna definida como identificador primario.
     * @param $object
     */
    public function delete($object);

    /**
     * Retorna el nombre de la clase del objeto que es persistido por la implementaci�n del abstracto. Ejemplo, si
     * la capa de persistencia es definida para el objeto 'trabajo' y la clase que representa a los trabajos en la
     * aplicaci�n tiene por nombre 'Trabajo', entonces este m�todo debe retornar 'Trabajo'.
     * @return string
     */
    public function getClass();

    /**
     * Retorna el nombre de la clase que maneja las operaciones de persistencia o el acceso a capa de DAO.
     * @return string
     */
    public function getDaoLayer();



}
