<?
	require_once Conf::RutaPDF();

class PDFcobro extends FPDF
{
    var $fecha_actual = '';
    var $titulo = '';
    var $titulo_texto = '';
    var $titulo_pie = '';
	var $direccion = '';

    function Header()
    {
        $x = $this->GetX();
        $y = $this->GetY();
		$x_titulo = $this->GetX();

		if($this->titulo_texto)
		{
        	$this->SetFont( 'Times', 'B', 18);
  	  	    $this->Cell(550, 25, $this->titulo_texto,0, 1, 'C' );
	        if($this->titulo_pie)
			{
	           $this->SetFont( 'Times', 'B', 12);
  	           $this->Cell(550, 20, $this->titulo_pie,'0', '0', 'C' );
			}
		}
		if($this->direccion)
		{
		    $x = $this->GetX();
	        $y = $this->GetY();
			$this->SetY( $x_titulo+3 );
			$this->SetX( 500 );
        	$this->SetFont( 'Arial', '', 6);
            $this->MultiCell(100, 10,$this->direccion,'0', '0', 'L' );
            $this->SetY( $x );
            $this->SetX( $y );

		}

        $y += 10;
        $this->SetY( $y );

        $this->Cell( 0, 12, $this->titulo, 0, 2, 'R' );

        $y += 28;
        $this->SetY( $y );

		$y += 22;
        $this->SetY( $y );

        $this->Cell( 0, 1, '', 'B' );

        $y += 20;
        $this->SetY( $y );
    }

    function Footer()
    {
        $this->SetY( -40 );
        $y = $this->getY();

        $this->SetDrawColor( 0, 0, 0 );
        $this->Cell( 0, 1, '', 'T' );

        $y += 2;
        $this->SetY( $y );

        $this->SetFont( 'Arial', '', 7 );
        $this->Cell( 0, 8, __('Cobro') . ' :: Impreso el '.$this->fecha_actual );
        $this->Cell( 0, 8, 'Página '.$this->PageNo(), '', '', 0, 'R' );
    }
}
?>
