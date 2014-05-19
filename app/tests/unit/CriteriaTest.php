<?php
use Codeception\Util\Stub;

// include_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/app/classes/Criteria.php');

class CriteriaTest extends BaseUnitTest
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;
    protected $criteria;

    protected function _before()
    {
        $this->setVerboseErrorHandler();
        $this->criteria = new Criteria(Stub::makeEmpty('Sesion'));

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