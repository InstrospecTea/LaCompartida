<?php

// autoload.php @generated by Composer
require_once __DIR__ . '/vendor/autoload.php';

function autocargattb($class_name) {
		$class_name = explode('\\', $class_name);
		$class_name = str_replace('_', DIRECTORY_SEPARATOR,  end($class_name));

    if (is_readable(dirname(__FILE__) . '/app/layers/dao/' . $class_name . '.php')) {

        require_once dirname(__FILE__) . '/app/layers/dao/' . $class_name . '.php';

    } elseif (is_readable(dirname(__FILE__) . '/app/layers/dao.framework/' . $class_name . '.php')){

        require_once dirname(__FILE__) . '/app/layers/dao.framework/' . $class_name . '.php';

    } elseif (is_readable(dirname(__FILE__) . '/app/layers/dto/' . $class_name . '.php')) {

        require_once dirname(__FILE__) . '/app/layers/dto/' . $class_name . '.php';

    } elseif (is_readable(dirname(__FILE__) . '/app/layers/service/' . $class_name . '.php')) {

        require_once dirname(__FILE__) . '/app/layers/service/' . $class_name . '.php';

    } elseif (is_readable(dirname(__FILE__) . '/app/layers/service.support/' . $class_name . '.php')) {

        require_once dirname(__FILE__) . '/app/layers/service.support/' . $class_name . '.php';

    } elseif (is_readable(dirname(__FILE__) . '/app/classes/' . $class_name . '.php')) {

		require_once dirname(__FILE__) . '/app/classes/' . $class_name . '.php';

	} else if (is_readable(dirname(__FILE__) . '/fw/classes/' . $class_name . '.php')) {

		require_once dirname(__FILE__) . '/fw/classes/' . $class_name . '.php';

	} else {

		return false;

	}
}

spl_autoload_register('autocargattb');

if (!class_exists('Slim') && is_readable(dirname(__FILE__) . '/fw/classes/Slim/Slim.php')) {
	require_once dirname(__FILE__) . '/fw/classes/Slim/Slim.php';
}