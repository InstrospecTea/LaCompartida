<?php

interface IUserManager extends BaseManager {
	/**
	 * Obtiene el total de horas trabajadas de un usuario
	 * @param 	string $client_id
	 * @param 	string $init_date (Y-m-d)
	 * @param 	string $end_date (Y-m-d)
	 * @return 	String
	 */
	public function getHoursWorked($user_id, $init_date = null, $end_date = null);

	/**
	 * Consulta si un usuario es activo
	 * @param 	string $user_id
	 * @return 	boolean
	 */
	public function isActive($user_id);

	/**
	 * Obtiene el total de horas trabajadas de un usuario con filtros
	 * @param 	string $user_id
	 * @param 	string $client_id
	 * @param 	array $areas
	 * @param 	array $categories
	 * @param 	string $init_date (Y-m-d)
	 * @param 	string $end_date (Y-m-d)
	 * @return 	String
	 */
	public function getHoursWorkedByFilters($user_id, $client_id = null, $areas = null, $categories = null, $init_date = null, $end_date = null);
}
