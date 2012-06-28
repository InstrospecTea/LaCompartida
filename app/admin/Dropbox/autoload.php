<?php

/**
 * This file registers a new autoload function using spl_autoload_register. 
 *
 * @package Dropbox 
 * @copyright Copyright (C) 2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/dropbox-php/wiki/License MIT
 */

/**
 * Autoloader function
 *
 * @param $className string
 * @return void
 */
function Dropbox_autoload($className) {

    if(strpos($className,'Dropbox_')===0) {

        include dirname(__FILE__) . '/' . str_replace('_','/',substr($className,8)) . '.php';

    } else {
	
	$class = str_replace('\\', '/', $className);
	if(file_exists( dirname(__FILE__).'/'. $class . '.php'      ) ) require_once( dirname(__FILE__).'/'. $class . '.php');
	
	}

}

spl_autoload_register('Dropbox_autoload');

$key      = '5jys56prote7pyq';
$secret   = 'dmv6lidqcm039wc';


// Check whether to use HTTPS and set the callback URL
$protocol = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
$callback = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Instantiate the required Dropbox objects
$encrypter = new \Dropbox\OAuth\Storage\Encrypter('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
$storage = new \Dropbox\OAuth\Storage\Session($encrypter);
$OAuth = new \Dropbox\OAuth\Consumer\Curl($key, $secret, $storage, $callback);
$dropbox = new \Dropbox\API($OAuth);

$oauth = new Dropbox_OAuth_PEAR($key , $secret);

