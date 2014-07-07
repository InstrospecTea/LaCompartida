<?php
use Codeception\Util\Stub;

class ExampleTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;

    protected function _before()
    {

    }

    protected function _after()
    {

    }

    // tests
    public function testASimpleAndNaiveSituation()
    {
        $html = '<strong>Hello World</strong>';
        $this->assertEquals('<strong>Hello World</strong>', $html);
    }

}