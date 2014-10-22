<?php
use Codeception\Util\Stub;

class ChargeTest extends BaseUnitTest
{
   /**
    * @var \UnitTester
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

    private function fillFromLegacyAndPersistCharge($charge, $legacy_charge) {
      $chargeService = new ChargeService($this->session);
      $charge->fillFromArray($legacy_charge->fields);
      $charge->fillChangedFields($legacy_charge->changes);
      $charge = $chargeService->saveOrUpdate($charge); 
      $charge = $chargeService->get($charge->get($charge->getIdentity()));
      return $charge;
    }

    // tests
    public function testWhenModalidadCalculoIsNullTheAsignedValueIsZero() {
      $charge = new Charge();
      $legacy_charge = new Cobro($this->session);
      
      $modalidad_calculo = null;
      $legacy_charge->Edit("id_cobro", null);
      $legacy_charge->Edit("estado", null);
      $legacy_charge->Edit("codigo_cliente", null);
      $legacy_charge->Edit("modalidad_calculo", $modalidad_calculo);
      $charge = $this->fillFromLegacyAndPersistCharge($charge, $legacy_charge);

      $this->assertEquals($charge->get('modalidad_calculo'), 0);
    }

    public function testWhenModalidadCalculoIsZeroTheAsignedValueIsZero() {
      $charge = new Charge();
      $legacy_charge = new Cobro($this->session);
      
      $modalidad_calculo = 0;
      $legacy_charge->Edit("id_cobro", null);
      $legacy_charge->Edit("estado", null);
      $legacy_charge->Edit("codigo_cliente", null);
      $legacy_charge->Edit("modalidad_calculo", $modalidad_calculo);
      $charge = $this->fillFromLegacyAndPersistCharge($charge, $legacy_charge);      

      $this->assertEquals($charge->get('modalidad_calculo'), 0);
    }

    public function testWhenModalidadCalculoIsOneTheAsignedValueIsOne() {
      $charge = new Charge();
      $legacy_charge = new Cobro($this->session);
      
      $modalidad_calculo = 1;
      $legacy_charge->Edit("id_cobro", null);
      $legacy_charge->Edit("estado", null);
      $legacy_charge->Edit("codigo_cliente", null);
      $legacy_charge->Edit("modalidad_calculo", $modalidad_calculo);
      $charge = $this->fillFromLegacyAndPersistCharge($charge, $legacy_charge);

      $this->assertEquals($charge->get('modalidad_calculo'), 1);
    }

    public function testWhenModalidadCalculoIsNotSettedTheAsignedValueIsOne() {
      $charge = new Charge();
      $legacy_charge = new Cobro($this->session);
      
      $modalidad_calculo = null;
      $legacy_charge->Edit("id_cobro", null);
      $legacy_charge->Edit("codigo_cliente", null);
      $legacy_charge->Edit("estado", null);
      $charge = $this->fillFromLegacyAndPersistCharge($charge, $legacy_charge);

      $this->assertEquals($charge->get('modalidad_calculo'), 1);
    }

}