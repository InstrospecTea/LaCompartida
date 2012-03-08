<?php
    require_once dirname(__FILE__).'/../conf.php';

class DocGenerator
{
	var $appName = 'Lemontech';
	var $appVersion = '0.1';
	var $isDebugging = false;

	var $leftMargin;
	var $rightMargin;
	var $topMargin;
	var $bottomMargin;
	var $pageOrientation;
	var $pageType;
	var $pageNums;
	var $headerMargin;
	var $footerMargin;

	var $documentLang;
	var $documentCharset;
	var $fontFamily;
	var $fontSize;

	var $documentBuffer;
	var $formatBuffer;
	var $cssData;
	var $lastSessionNumber;
	var $lastPageNumber;
	var $atualPageWidth;
	var $atualPageHeight;

	var $tableIsOpen;
	var $tableLastRow;
	var $tableBorderAlt;
	var $tablePaddingAltRight;
	var $tablePaddingAltLeft;
	var $tableBorderInsideH;
	var $tableBorderInsideV;

	var $numImages;
	var $estado;


	/**
	 * constructor DocGenerator(const $pageOrientation = 'PORTRAIT', const $pageType = 'A4',  string $cssData = '', int $topMargin = 3.0, int $rightMargin = 2.5, int $bottomMargin = 3.0, int $leftMargin = 2.5)
	 * @param $html: HTML code of the document
	 * @param $pageOrientation: The orientation of the pages of the initial session, 'PORTRAIT' or 'LANDSCAPE'
	 * @param $pageType: The initial type of the paper of the pages of the session
	 * @param $cssData: extra file with formating configurations, in css file format
	 * @param $topMargin: top margin of the document
	 * @param $rightMargin: right margin of the document
	 * @param $bottomMargin: bottom margin of the document
	 * @param $leftMargin: left margin of the document
	 * @param $estado: ni idea para que sirve este parametro preguntar al que lo agregó
	 * @param $id_format: ?????????
	 * @param $configuracion: ????????
	 * @param $headerMargin: margen del encabezado del documento, en centímetros
	 * @param $footerMargin: margen del pie de página del documento, en centímetros
	 */
	function DocGenerator($html='', $cssData = '', $pageType = 'LETTER', $pageNums = false, $pageOrientation = 'PORTRAIT', $topMargin = 1.5, $rightMargin = 1.5, $bottomMargin = 2.0, $leftMargin = 1.5, $estado='EMITIDO', $id_formato='', $configuracion = array(), $headerMargin = 1.25, $footerMargin = 1.25, $lang = null)
	{
		global $desde;

		$this->documentBuffer = '';
		$this->formatBuffer = '';
		$this->cssData = $cssData;
		$this->lastSessionNumber = 0;
		$this->lastPageNumber = 0;
		$this->atualPageWidth = 0;
		$this->atualPageHeight = 0;
		$this->pageNums = $pageNums;

		$this->tableIsOpen = false;
		$this->tableLastRow = 0;
		$this->tableBorderAlt = 0.5;
		$this->tablePaddingAltRight = 5.4;
		$this->tablePaddingAltLeft = 5.4;
		$this->tableBorderInsideH = 0.5;
		$this->tableBorderInsideV = 0.5;

		$this->documentLang = 'ES-CL';
		$this->documentCharset = 'windows-1252';
		$this->fontFamily = 'Arial';
		$this->fontSize = '12';

		$this->pageOrientation = $pageOrientation;
		$this->pageType = $pageType;

		$this->topMargin = $topMargin;
		$this->rightMargin = $rightMargin;
		$this->bottomMargin = $bottomMargin;
		$this->leftMargin = $leftMargin;
		$this->headerMargin = $headerMargin;
		$this->footerMargin = $footerMargin;

		$this->numImages =0;
		$this->estado=$estado;
		$this->lang=$lang;

		$this->configuracion=$configuracion;

		$this->newSession($html, $this->pageOrientation, $this->pageType, $this->topMargin, $this->rightMargin, $this->bottomMargin, $this->leftMargin,$this->estado, $id_formato, $this->headerMargin, $this->footerMargin);
		$this->newPage();
	}//end DocGenerator()

	/**
	 * public int newPage(void)
	 * @return int: the number of the new page
	 */
	function newPage(){
		$this->lastPageNumber++;
		if($this->lastPageNumber != 1)
			$this->documentBuffer .= "<br clear=\"all\" style=\"page-break-before: always;\">";
		return $this->lastPageNumber;
	}//end newPage()

	/**
	 * public int newSession(const $pageOrientation = NULL, const $pageType = NULL, int $topMargin = NULL, int $rightMargin = NULL, int $bottomMargin = NULL, int $leftMargin = NULL)
	 * @param $html: HTML code of the session
	 * @param $pageOrientation: The orientation of the pages of the this session, 'PORTRAIT' or 'LANDSCAPE'
	 * @param $pageType: The type of the paper of the pages of the this session
	 * @param $topMargin: top margin of the this session
	 * @param $rightMargin: right margin of the this session
	 * @param $bottomMargin: bottom margin of the this session
	 * @param $leftMargin: left margin of the this session
	 * @param $estado: ni idea quien agregó este parametro y no le puso que era
	 * @param $id_formato: ni idea quien agregó este parametro y no le puso que era
	 * @param $headerMargin: margin of the header of the document
	 * @param $footerMargin: margin of the footer of the document
	 * @return int: the number of the new session
	 */
	function newSession($html='', $pageOrientation = NULL, $pageType = NULL, $topMargin = NULL, $rightMargin = NULL, $bottomMargin = NULL, $leftMargin = NULL, $estado = NULL, $id_formato = '', $headerMargin = NULL, $footerMargin = NULL){
		setlocale(LC_ALL,'en_EN');

		//don't setted now? then use document start values
		$pageOrientation = $pageOrientation === NULL ? $this->pageOrientation : $pageOrientation;
		$pageType = $pageType === NULL ? $this->pageType : $pageType;
		$topMargin = $topMargin === NULL ? $this->topMargin : $topMargin;
		$rightMargin = $rightMargin === NULL ? $this->rightMargin : $rightMargin;
		$bottomMargin = $bottomMargin === NULL ? $this->bottomMargin : $bottomMargin;
		$leftMargin = $leftMargin === NULL ? $this->leftMargin : $leftMargin;
		$headerMargin = $headerMargin == NULL ? $this->headerMargin : $headerMargin;
		$footerMargin = $footerMargin == NULL ? $this->footerMargin : $footerMargin;

		$this->lastSessionNumber++;

		if($this->lastSessionNumber != 1){
			$this->endSession();
			$this->documentBuffer .= "<br clear=\"all\" style=\"page-break-before: always; mso-break-type: section-break\">\r\n";
		}

		switch($pageOrientation){
			case 'PORTRAIT' :
				switch($pageType){
					case 'A4' :
						$this->atualPageWidth = A4_WIDTH * One_Cent;
						$this->atualPageHeight = A4_HEIGHT * One_Cent;
						break;
					case 'A5' :
						$this->atualPageWidth = A5_WIDTH * One_Cent;
						$this->atualPageHeight = A5_HEIGHT * One_Cent;
						break;
					case 'LETTER' :
						$this->atualPageWidth = LETTER_WIDTH * One_Cent;
						$this->atualPageHeight = LETTER_HEIGHT * One_Cent;
						break;
					case 'LEGAL' :
						$this->atualPageWidth = LEGAL_WIDTH * One_Cent;
						$this->atualPageHeight = LEGAL_HEIGHT * One_Cent;
						break;
					default:
						die("ERROR: PAGE TYPE ($pageType) IS NOT DEFINED");
				}
				$msoPageOrientation = 'portrait';
				break;
			case 'LANDSCAPE' :
				switch($pageType){
					case 'A4' :
						$this->atualPageWidth = A4_HEIGHT * One_Cent;
						$this->atualPageHeight = A4_WIDTH * One_Cent;
						break;
					case 'A5' :
						$this->atualPageWidth = A5_HEIGHT * One_Cent;
						$this->atualPageHeight = A5_WIDTH * One_Cent;
						break;
					case 'LETTER' :
						$this->atualPageWidth = LETTER_HEIGHT * One_Cent;
						$this->atualPageHeight = LETTER_WIDTH * One_Cent;
						break;
					case 'LEGAL' :
						$this->atualPageWidth = LEGAL_HEIGHT * One_Cent;
						$this->atualPageHeight = LEGAL_WIDTH * One_Cent;
						break;
					default:
						die("ERROR: PAGE TYPE ($pageType) IS NOT DEFINED");
				}
				$msoPageOrientation = 'landscape';
				break;
			default :
				die("ERROR: INVALID PAGE ORIENTATION ($pageOrientation)");
		}

		$pageSize = number_format($this->atualPageWidth,4,'.','').'pt '.number_format($this->atualPageHeight,4,'.','').'pt';
		$pageMargins = number_format($topMargin,1,'.','').'cm '.number_format($rightMargin,1,'.','').'cm '.number_format($bottomMargin,1,'.','').'cm '.number_format($leftMargin,1,'.','').'cm';
		$headerMargins = $headerMargin . 'cm';
		$footerMargins = $footerMargin . 'cm';
		$sessionName = "Section" . $this->lastSessionNumber;

		$this->formatBuffer .= "@page $sessionName\r\n";
		$this->formatBuffer .= "   {size: $pageSize;\r\n";
		$this->formatBuffer .= "   mso-page-orientation: $msoPageOrientation;\r\n";
		$this->formatBuffer .= "   margin: $pageMargins;\r\n";
		$this->formatBuffer .= "   mso-header-margin: $headerMargins;\r\n";
		$this->formatBuffer .= "   mso-footer-margin: $footerMargins;\r\n";
		$this->formatBuffer .= "   mso-paper-source: 0;\r\n";
		if( $this->pageNums && Conf::dbUser() != 'ebmo' && Conf::dbUser() != 'otero' && Conf::dbUser() != 'vergara' && Conf::dbUser() != 'blr' && Conf::dbUser() != 'barros' && Conf::dbUser() != 'kastpinochet' && $this->estado != 'CREADO' && $this->estado != 'EN REVISION')
		{
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/pie.htm') f1;\r\n";
		}
		if( Conf::dbUser() == 'barros' )
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/barros.htm') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/barros.htm') f1;\r\n";
		}
		else if( Conf::dbUser() == 'vergara' )
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/vergara.htm') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/vergara.htm') f1;\r\n";
		}
		else if( Conf::dbUser() == 'blr' )
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/blr.htm') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/blr.htm') f1;\r\n";
		}
		else if( Conf::dbUser() == 'ebmo' )
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/ebmo.htm') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/ebmo.htm') f1;\r\n";
		}
		else if( Conf::dbUser() == 'otero' )
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/otero.htm') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/otero.htm') f1;\r\n";
		}
		else if( Conf::dbUser() == 'kastpinochet' )
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/kastpinochet.htm') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/kastpinochet.htm') f1;\r\n";
		}
		else if (( $this->estado == 'CREADO' || $this->estado == 'EN REVISION' ) && ( Conf::dbUser() != 'jjr'))
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/pie_de_pagina_borrador.php?id_formato=$id_formato') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/pie_de_pagina_borrador.php?id_formato=$id_formato') f1;\r\n";
		}
		else
		{
			$this->formatBuffer .= "   mso-header: url('".Conf::Host()."app/templates/default/css/pie_de_pagina.php?id_formato=$id_formato') h1;\r\n";
			$this->formatBuffer .= "   mso-footer: url('".Conf::Host()."app/templates/default/css/pie_de_pagina.php?id_formato=$id_formato&lang=" . $this->lang . "') f1;\r\n";
		}
		$this->formatBuffer .= "}\r\n";
		$this->formatBuffer .= "div.$sessionName\r\n";
		$this->formatBuffer .= "  {page: $sessionName;}\r\n\r\n";

		$this->documentBuffer .= "<div class=\"$sessionName\">\r\n";
		$this->documentBuffer .= $html;

		setlocale(LC_ALL,Conf::Locale());

		return $this->lastSessionNumber;
	}//end newSession()

	/**
	 * public void output(string $fileName = '', string $saveInPath = '')
	 * @param $fileName: the file name of document
	 * @param $saveInPath: if not empty will be the path to save document otherwise show
	 */
	function output($fileName = '', $saveInPath = '', $desde = ''){
		$this->endSession();

		$outputCode = '';
		$outputCode .= "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\r\n";
		$outputCode .= "   xmlns:w=\"urn:schemas-microsoft-com:office:word\"\r\n";
		$outputCode .= "   xmlns=\"http://www.w3.org/TR/REC-html40\">\r\n";

		$outputCode .= $this->getHeader( $desde );

		$outputCode .= $this->getBody();

		$outputCode .= "</html>\r\n";

		$fileName = $fileName != '' ? $fileName : basename($_SERVER['PHP_SELF'], '.php') . '.doc';

		if($saveInPath == ''){
			if($this->isDebugging){
				return $outputCode;
			}else{
				header("Content-Type: application/msword; charset=\$this->documentCharset\"");
				header("Content-Disposition: attachment; filename=\"$fileName\"");
				echo $outputCode;
			}
		}else{
			if(substr($saveInPath,-1) <> "/")
				$saveInPath = $saveInPath."/";
			file_put_contents($saveInPath . $fileName, $outputCode);
		}
	}//end output()

	/**
	 * public void setDocumentLang(string $lang)
	 * @param $lang: document lang
	 */
	function setDocumentLang($lang){
		$this->documentLang = $lang;
	}//end setDocumentLang()

	/**
	 * public void setDocumentCharset(string $charset)
	 * @param $charset: document charset
	 */
	function setDocumentCharset($charset){
		$this->documentCharset = $charset;
	}//end setDocumentCharset()

	/**
	 * public void setFontFamily(string $fontFamily)
	 * @param $fontFamily: default document font family
	 */
	function setFontFamily($fontFamily){
		$this->fontFamily = $fontFamily;
	}//end setFontFamily()

	/**
	 * public void setFontSize(string $fontSize)
	 * @param $fontSize: default document font Size
	 */
	function setFontSize($fontSize){
		$this->fontSize = $fontSize;
	}//end setFontSize()

	/****************************************************
	 * begin private functions
	 ***************************************************/

	/**
	 * private void endSession(void)
	 */
	function endSession(){
		$this->documentBuffer .= "</div>\r\n";
	}//end newSession()

	/**
	 * private float endSession(int $pixels)
	 * @param $pixels: number of pixels to convert
	 */
	function pixelsToPoints($pixels){
		$points = 0.75 * floatval($pixels);
		return number_format($points,2);
	}//end pixelsToPoints()

	/**
	 * private void prepareDefaultHeader(void)
	 */
	function prepareDefaultHeader(){
		$this->formatBuffer .= "p.normalText, li.normalText, div.normalText{\r\n";
		$this->formatBuffer .= "   mso-style-parent: \"\";\r\n";
		$this->formatBuffer .= "   margin: 0cm;\r\n";
		$this->formatBuffer .= "   margin-bottom: 6pt;\r\n";
		$this->formatBuffer .= "   mso-pagination: widow-orphan;\r\n";
		$this->formatBuffer .= "   font-size: {$this->fontSize}pt;\r\n";
		$this->formatBuffer .= "   font-family: \"{$this->fontFamily}\";\r\n";
		$this->formatBuffer .= "   mso-fareast-font-family: \"{$this->fontFamily}\";\r\n";
		$this->formatBuffer .= "}\r\n\r\n";

		$this->formatBuffer .= "table.normalTable{\r\n";
		$this->formatBuffer .= "   mso-style-name: \"Tabela com grade\";\r\n";
		$this->formatBuffer .= "   mso-tstyle-rowband-size: 0;\r\n";
		$this->formatBuffer .= "   mso-tstyle-colband-size: 0;\r\n";
		$this->formatBuffer .= "   border-collapse: collapse;\r\n";
		$this->formatBuffer .= "   mso-border-alt: solid windowtext {$this->tableBorderAlt}pt;\r\n";
		$this->formatBuffer .= "   mso-yfti-tbllook: 480;\r\n";
		$this->formatBuffer .= "   mso-padding-alt: 0cm {$this->tablePaddingAltRight}pt 0cm {$this->tablePaddingAltLeft}pt;\r\n";
		$this->formatBuffer .= "   mso-border-insideh: {$this->tableBorderInsideH}pt solid windowtext;\r\n";
		$this->formatBuffer .= "   mso-border-insidev: {$this->tableBorderInsideV}pt solid windowtext;\r\n";
		$this->formatBuffer .= "   mso-para-margin: 0cm;\r\n";
		$this->formatBuffer .= "   mso-para-margin-bottom: .0001pt;\r\n";
		$this->formatBuffer .= "   mso-pagination: widow-orphan;\r\n";
		$this->formatBuffer .= "   font-size: {$this->fontSize}pt;\r\n";
		$this->formatBuffer .= "   font-family: \"{$this->fontFamily}\";\r\n";
		$this->formatBuffer .= "}\r\n";
		$this->formatBuffer .= "table.normalTable td{\r\n";
		$this->formatBuffer .= "   border: solid windowtext 1.0pt;\r\n";
		$this->formatBuffer .= "   border-left: none;\r\n";
		$this->formatBuffer .= "   mso-border-left-alt: solid windowtext .5pt;\r\n";
		$this->formatBuffer .= "   mso-border-alt: solid windowtext .5pt;\r\n";
		$this->formatBuffer .= "   padding: 0cm 5.4pt 0cm 5.4pt;\r\n";
		$this->formatBuffer .= "}\r\n\r\n";

		$this->formatBuffer .= "table.tableWithoutGrid{\r\n";
		$this->formatBuffer .= "   mso-style-name: \"Tabela sem grade\";\r\n";
		$this->formatBuffer .= "   mso-tstyle-rowband-size: 0;\r\n";
		$this->formatBuffer .= "   mso-tstyle-colband-size: 0;\r\n";
		$this->formatBuffer .= "   border-collapse: collapse;\r\n";
		$this->formatBuffer .= "   border: none;\r\n";
		$this->formatBuffer .= "   mso-border-alt: none;\r\n";
		$this->formatBuffer .= "   mso-yfti-tbllook: 480;\r\n";
		$this->formatBuffer .= "   mso-padding-alt: 0cm {$this->tablePaddingAltRight}pt 0cm {$this->tablePaddingAltLeft}pt;\r\n";
		$this->formatBuffer .= "   mso-border-insideh: {$this->tableBorderInsideH}pt solid windowtext;\r\n";
		$this->formatBuffer .= "   mso-border-insidev: {$this->tableBorderInsideV}pt solid windowtext;\r\n";
		$this->formatBuffer .= "   mso-para-margin: 0cm;\r\n";
		$this->formatBuffer .= "   mso-para-margin-bottom: .0001pt;\r\n";
		$this->formatBuffer .= "   mso-pagination: widow-orphan;\r\n";
		$this->formatBuffer .= "   font-size: {$this->fontSize}pt;\r\n";
		$this->formatBuffer .= "   font-family: \"{$this->fontFamily}\";\r\n";
		$this->formatBuffer .= "}\r\n\r\n";

		if($this->cssData != ''){
			$this->formatBuffer .= $this->cssData;
		}
	}//end prepareDefaultHeader()

	/**
	 * private string getHeader(void)
	 */
	function getHeader( $desde = '' ){
		$header = '';
		$header .= "<head>\r\n";
		$header .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$this->documentCharset\">\r\n";
		$header .= "<meta name=\"ProgId\" content=\"Word.Document\">\r\n";
		$header .= "<meta name=\"Generator\" content=\"$this->appName $this->appVersion\">\r\n";
		$header .= "<meta name=\"Originator\" content=\"$this->appName $this->appVersion\">\r\n";
		$header .= "<!--[if !mso]>\r\n";
		$header .= "<style>\r\n";
		$header .= "v\:* {behavior:url(#default#VML);}\r\n";
		$header .= "o\:* {behavior:url(#default#VML);}\r\n";
		$header .= "w\:* {behavior:url(#default#VML);}\r\n";
		$header .= ".shape {behavior:url(#default#VML);}\r\n";
		$header .= "</style>\r\n";
		$header .= "<![endif]-->\r\n";
        $header .= "<!--[if gte mso 9]><xml>\r\n";
        $header .= "<w:WordDocument>\r\n";
		if( $desde == 'factura' && $this->configuracion['desactivar_clave_rtf']!=1 )
      	{
			$header .= "<w:DocumentProtection>ReadOnly</w:DocumentProtection>";
			$header .= "<w:UnprotectPassword>12345</w:UnprotectPassword>";
			$header .= "<w:StyleLock/>";
			$header .= "<w:StyleLockEnforced/>";
		}
        $header .= "<w:View>Print</w:View>\r\n";
        $header .= "<w:Zoom>100</w:Zoom>\r\n";
        $header .= "</w:WordDocument>\r\n";
        $header .= "</xml><![endif]-->\r\n";

		$header .= "<style>\r\n";
		$header .= "<!--\r\n";
		$header .= "/* Style Definitions */\r\n";

		$this->prepareDefaultHeader();

		$header .= $this->formatBuffer ."\r\n";

		$header .= "-->\r\n";
		$header .= "</style>\r\n";
		$header .= "</head>\r\n";

		return $header;
	}//end getHeader()

	/**
	 * private string getBody(void)
	 */
	function getBody(){
		$body = '';
		$body .= "<body lang=\"$this->documentLang\" style=\"tab-interval: 35.4pt\">\r\n";

		$body .= $this->documentBuffer . "\r\n";

		$body .= "</body>\r\n";

		return $body;
	}//end getBody()
        function outputxml($xml,$filename) {
         $this->endSession();
           header("Content-Type: application/msword; charset=ISO-8859-1");
           header("Content-Disposition: attachment; filename=\"$filename\"");
           echo $xml;
       }
}//end class DocGenerator


/****************************************************
 * constant definition
 ***************************************************/
define('One_Cent', 28.35);//1cm = 28.35pt

//paper sizes in cm
define('A4_WIDTH', 21.0);
define('A4_HEIGHT', 29.7);
define('A5_WIDTH', 14.8);
define('A5_HEIGHT', 21.0);
define('LETTER_WIDTH', 21.59);
define('LETTER_HEIGHT', 27.94);
define('LEGAL_WIDTH', 21.59);
define('LEGAL_HEIGHT', 35.56);


/****************************************************
 * functions definition
 ***************************************************/

if(! function_exists('file_get_contents')){
  function file_get_contents($filename, $useIncludePath = '', $context = ''){
    if(empty($useIncludePath)){
      return implode('',file($filename));
    }elseif(empty($content)){
      return implode('',file($filename, $useIncludePath));
    }else{
      return implode('',file($filename, $useIncludePath, $content));
    }
  }//end file_get_contents()
}//end if

if(! function_exists('file_put_contents')){
  function file_put_contents($filename, $data){
    $file = fopen($filename, 'wb');
    $return = fwrite($file, $data);
    fclose($file);
	return $return;
  }//end file_put_contents()
}//end if
?>
