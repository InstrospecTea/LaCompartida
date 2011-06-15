<?
    require_once dirname(__FILE__).'/../../../../conf.php';
    require_once Conf::ServerDir().'/fw/classes/Lista.php';

    class ListaPreguntas extends Lista
    {
        function ListaPreguntas($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Pregunta',$params,$query);
        }
    }

    class ListaPreguntasAlternativas extends Lista
    {
        function ListaPreguntasAlternativas($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Alternativa',$params,$query);
        }
    }
    class ListaEncuestas extends Lista
    {
        function ListaEncuestas($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Encuesta',$params,$query);
        }
    }
    class ListaAlternativas extends Lista
    {
        function ListaAlternativas($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Alternativa',$params,$query);
        }
    }
?>
