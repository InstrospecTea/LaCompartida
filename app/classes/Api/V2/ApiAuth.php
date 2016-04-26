<?php

namespace Api\V2;

/**
 *
 * Auth methods
 *
 */
class ApiAuth {

	static $DEFAULT_PERMISSIONS = array(
		'projects' => array('read'),
		'activities' => array('read'),
		'settings' => array('read'),
		'users' => array('read', 'update'),
		'clients' => array('read'),
		'time_entries' => array('read', 'create', 'update', 'destroy'),
		'time_reviews' => array('read', 'create', 'update', 'destroy'),
		'roles' => array('read'),
		'user_categories' => array('read'),
		'holdings' => array('read'),
		'currencies' => array('read'),
		'countries' => array('read'),
		'areas' => array('read'),
		'tasks' => array('read'),
		'languages' => array('read'),
		'translations' => array('read'),
		'reports' => array('read')
	);

	static $ROLES = array(
		'PRO' => 'normalPermissions',
		'REP' => 'normalPermissions',
		'REV' => 'reviewerPermissions',
		'ADM' => 'adminPermissions'
	);

	public static function normalPermissions() {
		return self::$DEFAULT_PERMISSIONS;
	}

	public static function reviewerPermissions() {
		$permissions = self::$DEFAULT_PERMISSIONS;
		$permissions['reviews'] = array('read');
		$permissions['time_entries'][] = 'review';
		$permissions['users'][] = 'read_others';
		return $permissions;
	}

	public static function adminPermissions() {
		$permissions = self::$DEFAULT_PERMISSIONS;
		$cudPermission = array('create', 'update', 'destroy');
		$rcudPermission = array('read', 'create', 'update', 'destroy');

		$permissions['projects'] = array_merge(
			$permissions['projects'], $cudPermission
		);
		$permissions['activities'] = array_merge(
			$permissions['activities'], $cudPermission
		);
		$permissions['settings'] = array_merge(
			$permissions['settings'], $cudPermission
		);
		$permissions['tasks'] = array_merge(
			$permissions['tasks'], $cudPermission
		);
		$permissions['users'] = array_merge(
			$permissions['users'],
			array('create', 'destroy', 'read_others', 'update_others')
		);
		$permissions['clients'] = $rcudPermission;
		$permissions['languages'] = $rcudPermission;
		$permissions['time_entries'][] = 'review';

		return $permissions;
	}

	public static function userPermissions($roles) {
		$permissions = array();
		foreach ($roles as $role) {
			if (array_key_exists($role, self::$ROLES)) {
				$roleMethod = self::$ROLES[$role];
				$permissions = array_replace_recursive($permissions, self::$roleMethod());
			}
		}
		return $permissions;
	}

	public static function hasPermission($roles, $apiNamespace, $permission) {
		$permissions = self::userPermissions($roles);
		$endpoint = $permissions[$apiNamespace];
		if (is_null($endpoint)) {
			return false;
		}
		return in_array($permission, $endpoint);
	}

	public static function methodMap($method) {
		$permission = '';
		switch ($method) {
			case 'GET':
				$permission = 'read';
				break;
			case 'POST':
				$permission = 'create';
				break;
			case 'PUT':
				$permission = 'create';
				break;
				case 'DELETE':
				$permission = 'destroy';
				break;
			default:
				$permission = 'read';
				break;
		}
		return $permission;
	}

}
