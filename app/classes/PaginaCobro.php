<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';


class PaginaCobro extends Pagina
{
    function PaginaCobro( $sesion )
    {
        $this->Pagina( $sesion );
    }

  /***
     * PrintPasos
     *
     * PrintPasos, imprime los pasos del ingreso
     */
    function PrintPasos( $sesion, $paso, $cliente=null, $id_cobro=null, $incluye_gastos=1, $incluye_honorarios=1 )
    {
        require Conf::ServerDir().'/templates/'.Conf::Templates().'/top_cobro.php';
    }
}
?>
