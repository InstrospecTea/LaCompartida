<?php 
	require_once dirname(__FILE__).'/../classes/Utiles.php';
	require_once dirname(__FILE__).'/../classes/Html.php';
//	require_once dirname(__FILE__).'/../../conf.php';

class InputDiaMes
{
	function InputDiaMes( $nombre_input, $seleccionado)
	{
		list($y,$m,$d) = split("-",$seleccionado);
		$this->nombre_input = $nombre_input;
        $this->seleccionado = $seleccionado;
		$this->mes_seleccionado = $m;
		$this->ano_seleccionado = $y;

		$this->random_string = Utiles::RandomString();

		$this->ImprimirInput();
	}

	function ImprimirInput()
	{
		$id_oculto = "oculto_".$this->random_string;
		$id_campo = "campo_".$this->random_string;

		$meses = array('','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic');
		
        $select_mes = "<select onchange=\"Cambiar(this.value,'$id_oculto','mes');\">";
        for($x=1;$x <= 12;$x++)
        {
            if($x == $this->mes_seleccionado)
                $select_mes .= "<option value='$x' selected>$meses[$x]</option>\n";
            else
                $select_mes .= "<option value='$x'>$meses[$x]</option>\n";
        }
        $select_mes .= "</select>";

        $ano_actual = date('Y');
		$desde = $ano_actual - 10;
        $hasta = $ano_actual + 10;

		$select_ano = "<select onchange=\"Cambiar(this.value,'$id_oculto','ano');\">";
        for($x=$desde;$x <= $hasta;$x++)
        {
            if($x == $this->ano_seleccionado)
                $select_ano .= "<option value='$x' selected>$x</option>\n";
            else
                $select_ano .= "<option value='$x'>$x</option>\n";
        }
        $select_ano .= "</select>";

		echo $select_mes;
		echo $select_ano;
		echo("<input id=\"$id_oculto\" type=hidden name=".$this->nombre_input." value=".$this->seleccionado.">");
	}

	function JavascriptGlobal()
	{
		echo<<<HTML
		<script>
		function Cambiar(valor,id,opc)
		{
			var campo_fecha = document.getElementById( id );
		    var arreglo = campo_fecha.value.split("-");
			var ano_mes_dia;
			if(opc == 'mes')
				ano_mes_dia =  arreglo[0] +"-"+ valor +"-"+ arreglo[2];
			else if(opc == 'ano')
                ano_mes_dia =  valor +"-"+ arreglo[1] +"-"+ arreglo[2];
			campo_fecha.value = ano_mes_dia;
		}		
		</script>
HTML;
	}
}
