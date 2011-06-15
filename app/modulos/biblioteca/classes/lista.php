<?
    require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/fw/classes/lista.php';

    class ListaArchivosBiblio extends Lista
    {
        function ListaArchivosBiblio($sesion,$params,$query)
        {
            $this->Lista($sesion, 'ArchivoBiblio',$params,$query);
        }
    }
?>
