<?php
use Codeception\Util\Stub;

class EntityTest extends BaseUnitTest
{
   /**
    * @var \CodeGuy
    */
    protected $unitTester;
    var $session;

    protected function _before() {
        $this->setVerboseErrorHandler();
        $session = new Sesion();
        $user = new Usuario($session, '99511620');
        $session->usuario = $user;
        $this->session = $session;
    }

    protected function _after() {
    }

    public function testThatFillDefaultFieldsHappensWhenTheDefaultableFieldsAreNotDefined() {
        $work = new Work();
        $workService = new WorkService($this->session);
        $work = $workService->saveOrUpdate($work);
        $this->assertEquals(null, $work->get('cobrable'));
    }

}
