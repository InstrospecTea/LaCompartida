<?php

use Codeception\Util\Stub;

// include_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/app/classes/Criteria.php');

class CriteriaTest extends BaseUnitTest {

	/**
	 * @var \CodeGuy
	 */
	protected $codeGuy;
	protected $criteria;

	protected function _before() {
		$this->setVerboseErrorHandler();
		$this->criteria = new Criteria(Stub::makeEmpty('Sesion'));
	}

	protected function _after() {

	}

	// tests
	public function testThatWellFormedCriteriaPlainQueryIsString() {
		$this->criteria
			->add_select('nombre')
			->add_from('usuario');

		$this->assertTrue(is_string($this->criteria->get_plain_query()));
	}

	/**
	 * [testCriteriaPlainQueryBadFormed description]
	 */
	public function testThatBadFormedCriteriaPlainQueryThrowsException() {
		try {

			$this->criteria->add_select('nombre');
			$this->criteria->get_plain_query();
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	public function testThatCriteriaWithOrClauseIsWellFormedWithOneParameter() {
		try {

			$this->criteria->add_select('nombre');
			$this->criteria->add_from('usuario');
			$this->criteria->add_restriction(
				CriteriaRestriction::or_clause(
					CriteriaRestriction::equals('activo', 1)
				)
			);
			$this->criteria->get_plain_query();
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	public function testThatCriteriaWithOrClauseIsWellFormedWithMoreThanOneParameter() {
		try {
			$this->criteria->add_select('nombre');
			$this->criteria->add_from('usuario');
			$this->criteria->add_restriction(
				CriteriaRestriction::or_clause(
					CriteriaRestriction::equals('activo', 1), CriteriaRestriction::equals('fecha_creacion', "'01-01-2014'")
				)
			);
			$this->criteria->get_plain_query();
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	public function testThatCriteriaWithOrClauseIsWellBuiltWithMoreThanOneParameter() {
		try {
			$this->criteria->add_select('nombre');
			$this->criteria->add_from('usuario');
			$this->criteria->add_restriction(
				CriteriaRestriction::or_clause(
					CriteriaRestriction::equals('activo', 1), CriteriaRestriction::equals('fecha_creacion', "'01-01-2014'")
				)
			);
			$query = "SELECT nombre FROM usuario WHERE (activo = 1 OR fecha_creacion = '01-01-2014')";
			$return = $query === $this->criteria->get_plain_query();
			$this->assertTrue($return);
		} catch (Exception $e) {
			$this->assertTrue(false);
		}
	}

	public function testThatCriteriaWithAndClauseIsWellFormedWithOneParameter() {
		try {

			$this->criteria->add_select('nombre');
			$this->criteria->add_from('usuario');
			$this->criteria->add_restriction(
				CriteriaRestriction::or_clause(
					CriteriaRestriction::equals('activo', 1)
				)
			);
			$this->criteria->get_plain_query();
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	public function testThatCriteriaWithAndClauseIsWellFormedWithMoreThanOneParameter() {
		try {
			$this->criteria->add_select('nombre');
			$this->criteria->add_from('usuario');
			$this->criteria->add_restriction(
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('activo', 1), CriteriaRestriction::equals('fecha_creacion', "'01-01-2014'")
				)
			);
			$this->criteria->get_plain_query();
			$this->assertTrue(false);
		} catch (Exception $e) {
			$this->assertTrue(true);
		}
	}

	public function testThatCriteriaWithAndClauseIsWellBuiltWithMoreThanOneParameter() {
		try {
			$this->criteria->add_select('nombre');
			$this->criteria->add_from('usuario');
			$this->criteria->add_restriction(
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('activo', 1), CriteriaRestriction::equals('fecha_creacion', "'01-01-2014'"), array(
					CriteriaRestriction::is_null('email'),
					CriteriaRestriction::is_not_null('centro_de_costo')
					)
				)
			);

			$query = "SELECT nombre FROM usuario WHERE (activo = 1 AND fecha_creacion = '01-01-2014' AND (email IS NULL AND centro_de_costo IS NOT NULL))";
			$return = $query === $this->criteria->get_plain_query();
			$this->assertTrue($return);
		} catch (Exception $e) {
			$this->assertTrue(false);
		}
	}

}
