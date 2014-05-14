<?php
use Codeception\Util\Stub;

class CriteriaTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;
    protected $criteria;

    protected function _before()
    {
        $this->criteria = new Criteria(null);
    }

    protected function _after()
    {
    }

    // tests
    public function testThatWellFormedCriteriaPlainQueryIsString()
    {
        $this->criteria
            ->add_select('nombre')
            ->add_from('usuario');

        $this->assertTrue(is_string($this->criteria->get_plain_query()));

    }

    /**
     * [testCriteriaPlainQueryBadFormed description]
     * @expectedException 
     */
    public function testThatBadFormedCriteriaPlainQueryThrowsException()
    {
        try {
            
            $this->criteria->add_select('nombre');
            $this->criteria->get_plain_query();
            $this->assertTrue(false);
            
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        
    }

}