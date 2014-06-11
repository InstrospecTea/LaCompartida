<?php
/**
* 	
*/
class BaseUnitTest extends \Codeception\TestCase\Test
{
	protected function setVerboseErrorHandler()
    {
        $handler = function($errorNumber, $errorString, $errorFile, $errorLine) {
            echo "ERROR INFO\nMessage: $errorString\nFile: $errorFile\nLine: $errorLine\n";
        };
        set_error_handler($handler);        
    }
}
?>