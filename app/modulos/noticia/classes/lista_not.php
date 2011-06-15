<?  require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/fw/classes/lista.php';


    class ListaProyectos extends Lista
    {
        function ListaProyectos($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Proyecto',$params,$query);
        }
    }

    class ListaArchivosProyectos extends Lista
    {
        function ListaArchivosProyectos($sesion,$params,$query)
        {
            $this->Lista($sesion, 'ArchivoProyecto',$params,$query);
        }
    }

?>

