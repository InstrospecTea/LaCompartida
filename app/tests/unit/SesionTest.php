<?php
use Codeception\Util\Stub;

// include_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/app/classes/Sesion.php');


class SesionTest extends BaseUnitTest
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;
    protected $sesion;

    protected function _before() {
        $this->setVerboseErrorHandler();
    }

    protected function _after() {
    }

    // tests
    public function testSesionStubGenerator() {
        $this->sesion = Stub::makeEmpty('Sesion');
    }

}