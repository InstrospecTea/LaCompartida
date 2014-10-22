<?php
// This is global bootstrap for autoloading 

\Codeception\Util\Autoload::registerSuffix('Page', __DIR__.DIRECTORY_SEPARATOR.'_pages');
date_default_timezone_set('America/Santiago');