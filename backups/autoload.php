<?php

function autoload_backup($class_name) {
	if (is_readable("classes/{$class_name}.php")) {
		require_once "classes/{$class_name}.php";
	}
}

spl_autoload_register('autoload_backup');
require_once dirname(__DIR__) . '/ttbloader.php';
