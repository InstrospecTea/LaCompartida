<?php
use Codeception\Util\Stub;

class SolicitudAdelantoTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;
    protected $object;
    protected $default_fixture;
    protected $estados;


    protected function _before()
    {
        require_once dirname(__FILE__) . '/../../classes/SolicitudAdelanto.php';
        $Sesion = $this->getMock('Sesion');
        $this->object = new SolicitudAdelanto($Sesion);
        $this->default_fixture = array(
            'id_solicitud_adelanto' => 1,
            'monto' => 100.00,
            'id_moneda' => 1,
            'descripcion' => 'Descripción de prueba',
            'fecha' => '01-02-2012',
            'codigo_cliente' => '0001-0001',
            'estado' => 'CREADO',
            'id_usuario_solicitante' => 1,
            'id_usuario_ingreso' => 1
        );
        
        $this->estados = array(
            'CREADO',
            'SOLICITADO',
            'DEPOSITADO'
        );
    }

    protected function _after()
    {
    }

    // tests
    public function testSolicitudAdelanto()
    {
        
    }

    

}