var isIE = document.all?true:false;
var isNS = document.layers?true:false;

var isNS4 = (document.layers) ? true : false;
var isIE4 = (document.all && !document.getElementById) ? true : false;
var isIE5 = (document.all && document.getElementById) ? true : false;
var isNS6 = (!document.all && document.getElementById) ? true : false;

var scr_w = screen.availWidth;
var scr_h = screen.availHeight;

//if (document.layers)
//    document.captureEvents(Event.MOUSEMOVE);
//document.onmousemove = captureMousePosition;

var xMousePos = 0;
var yMousePos = 0;
var xMousePosMax = 0;
var yMousePosMax = 0; 

function captureMousePosition(e)
{
	if(!e) e = window.event;

    if (document.layers)
	{
        xMousePos = e.pageX;
        yMousePos = e.pageY;
        xMousePosMax = window.innerWidth+window.pageXOffset;
        yMousePosMax = window.innerHeight+window.pageYOffset;
    }
	else if (document.all)
	{
        xMousePos = e.clientX + document.body.scrollLeft;
        yMousePos = e.y + document.body.scrollTop;
        xMousePosMax = document.body.clientWidth + document.body.scrollLeft;
        yMousePosMax = document.body.clientHeight + document.body.scrollTop;
    }
	else if (document.getElementById)
	{
        xMousePos = e.pageX;
        yMousePos = e.pageY;
        xMousePosMax = window.innerWidth+window.pageXOffset;
        yMousePosMax = window.innerHeight+window.pageYOffset;
    }
}

function aleatorio(inferior,superior)
{
	var dif = superior - inferior + 1;
	var aleat = parseInt( Math.random() * 100000000 ) % dif;
	return parseInt(inferior) + aleat;
}

function nuevaVentana( name, w, h, url, opciones )
{
	if(!opciones) opciones = 'toolbar=no,location=no,directories=no,status=no,scrollbars=yes,resizable=yes,copyhistory=no';

	if( window.open ( url, name, opciones + ',width=' + w + ',height=' + h + '') )
		return true;

	return false;
}

function manoOn( src )
{
	if(isIE)
		src.style.cursor = 'hand';
	else
		src.style.cursor = 'pointer';
}

function manoOff( src )
{
	src.style.cursor = 'default';
}

function irIntranet( url )
{
	window.location = root_dir + url;
}

function loading( msg )
{
	if(msg == null) msg = 'Cargando, por favor espere...';

	promptbox = document.createElement('div'); 
	promptbox.setAttribute ('id' , 'loading') 
	document.getElementsByTagName('body')[0].appendChild(promptbox) 
	promptbox = eval("document.getElementById('loading').style") 
	promptbox.position = 'absolute' 
	promptbox.top = 140
	promptbox.left = 200;//scr_w / 2 - 150;
	promptbox.width = 300

	var titulo = "<table cellspacing='0' cellpadding='0' border='0' width='100%'><tr valign='middle'><td class='titlebar'>Información</td><td align='right' class='titlebar' onClick='offLoading();' onMouseOver='manoOn(this);' onMouseOut='manoOff(this);'>[x]</td></tr></table>";
	var contenido = "<table cellspacing='0' cellpadding='4' border='0' width='100%' class='promptbox'><tr><td class='texto12b' align='center' valign='middle'>" + msg + "</td></tr></table>";
	var ventana = document.getElementById('loading');

	ventana.innerHTML = '<table bgcolor="#000000" cellspacing="0" cellpadding="1" border="0" width="100%"><tr><td>' + titulo + contenido + '</td></tr></table>';
}

function offLoading()
{
	document.getElementsByTagName("body")[0].removeChild(document.getElementById("loading"));

	if(window.stop)
		window.stop();
	else if(document.execCommand)
		document.execCommand("Stop")
}

function findPosX(obj)
{
	var curleft = 0;

	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;

	return curleft;
}

function findPosY(obj)
{
	var curtop = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;

	return curtop;
}

/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;


/**
 * Sets/unsets the pointer and marker in browse mode
 *
 * @param   object    the table row
 * @param   integer  the row number
 * @param   string    the action calling this script (over, out or click)
 * @param   string    the default background color
 * @param   string    the color to use for mouseover
 * @param   string    the color to use for marking a row
 *
 * @return  boolean  whether pointer is set or not
 */
function setPointer(theRow, theRowNum, theAction, theDefaultColor, thePointerColor, theMarkColor)
{
    var theCells = null;

    // 1. Pointer and mark feature are disabled or the browser can't get the
    //    row -> exits
    if ((thePointerColor == '' && theMarkColor == '')
        || typeof(theRow.style) == 'undefined') {
        return false;
    }

    // 2. Gets the current row and exits if the browser can't get it
    if (typeof(document.getElementsByTagName) != 'undefined') {
        theCells = theRow.getElementsByTagName('td');
    }
    else if (typeof(theRow.cells) != 'undefined') {
        theCells = theRow.cells;
    }
    else {
        return false;
    }

    // 3. Gets the current color...
    var rowCellsCnt  = theCells.length;
    var domDetect    = null;
    var currentColor = null;
    var newColor     = null;
    // 3.1 ... with DOM compatible browsers except Opera that does not return
    //         valid values with "getAttribute"
    if (typeof(window.opera) == 'undefined'
        && typeof(theCells[0].getAttribute) != 'undefined') {
        currentColor = theCells[0].getAttribute('bgcolor');
        domDetect    = true;
    }
    // 3.2 ... with other browsers
    else {
        currentColor = theCells[0].style.backgroundColor;
        domDetect    = false;
    } // end 3

    // 3.3 ... Opera changes colors set via HTML to rgb(r,g,b) format so fix it
    if (currentColor.indexOf("rgb") >= 0)
    {
        var rgbStr = currentColor.slice(currentColor.indexOf('(') + 1,
                                     currentColor.indexOf(')'));
        var rgbValues = rgbStr.split(",");
        currentColor = "#";
        var hexChars = "0123456789ABCDEF";
        for (var i = 0; i < 3; i++)
        {
            var v = rgbValues[i].valueOf();
            currentColor += hexChars.charAt(v/16) + hexChars.charAt(v%16);
        }
    }

    // 4. Defines the new color
    // 4.1 Current color is the default one
    if (currentColor == ''
        || currentColor.toLowerCase() == theDefaultColor.toLowerCase()) {
        if (theAction == 'over' && thePointerColor != '') {
            newColor              = thePointerColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
            // Garvin: deactivated onclick marking of the checkbox because it's also executed
            // when an action (like edit/delete) on a single item is performed. Then the checkbox
            // would get deactived, even though we need it activated. Maybe there is a way
            // to detect if the row was clicked, and not an item therein...
            // document.getElementById('id_rows_to_delete' + theRowNum).checked = true;
        }
    }
    // 4.2 Current color is the pointer one
    else if (currentColor.toLowerCase() == thePointerColor.toLowerCase()
             && (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])) {
        if (theAction == 'out') {
            newColor              = theDefaultColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
            // document.getElementById('id_rows_to_delete' + theRowNum).checked = true;
        }
    }
    // 4.3 Current color is the marker one
    else if (currentColor.toLowerCase() == theMarkColor.toLowerCase()) {
        if (theAction == 'click') {
            newColor              = (thePointerColor != '')
                                  ? thePointerColor
                                  : theDefaultColor;
            marked_row[theRowNum] = (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])
                                  ? true
                                  : null;
            // document.getElementById('id_rows_to_delete' + theRowNum).checked = false;
        }
    } // end 4

    // 5. Sets the new color...
    if (newColor) {
        var c = null;
        // 5.1 ... with DOM compatible browsers except Opera
        if (domDetect) {
            for (c = 0; c < rowCellsCnt; c++) {
				if(theCells[c].getAttribute('bgcolor') == currentColor)
					theCells[c].setAttribute('bgcolor', newColor, 0);
            } // end for
        }
        // 5.2 ... with other browsers
        else {
            for (c = 0; c < rowCellsCnt; c++) {
				if(theCells[c].style.backgroundColor == currentColor)
					theCells[c].style.backgroundColor = newColor;
            }
        }
    } // end 5

    return true;
} // end of the 'setPointer()' function


function revisarDigito( dvr )
{
	var dv = dvr + "";

	if ( dv != '0' && dv != '1' && dv != '2' && dv != '3' && dv != '4' && dv != '5' && dv != '6' && dv != '7' && dv != '8' && dv != '9' && dv != 'k' && dv != 'K')
		return false;
	return true;
}

function revisarDigito2( crut )
{
	var i, rut, dv, dvi;
	var dvr = '0';

	var largo = crut.length;

	if ( largo < 2 )
		return false;
	if ( largo > 2 )
		rut = crut.substring(0, largo - 1);
	else
		rut = crut.charAt(0);

	dv = crut.charAt(largo-1);
	revisarDigito( dv );

	if ( rut == null || dv == null )
		return 0

	var suma = 0;
	var mul  = 2;
	for (i= rut.length -1 ; i >= 0; i--)
	{
		suma = suma + rut.charAt(i) * mul;
		if (mul == 7)
			mul = 2;
		else    
			mul++;
	}

	var res = suma % 11;

	if (res==1)
		dvr = 'k';
	else if (res==0)
		dvr = '0';
	else
	{
		dvi = 11-res;
		dvr = dvi + "";
	}
	if ( dvr != dv.toLowerCase() )
		return false;

	return true;
}

function Rut( rut, dvrut )
{
	var tmpstr = "";
	var i, j, letra, texto, largo;

	if ( rut.length < 6 )
		return false;

	texto = rut + '-' + dvrut;

	for ( i=0; i < texto.length ; i++ )
	{
		if ( texto.charAt(i) != ' ' && texto.charAt(i) != '.' && texto.charAt(i) != '-' )
		{
			if(texto.charAt(i) == 'k')
				letra = 'K';
			else
				letra =  texto.charAt(i);

			tmpstr = tmpstr + letra;
		}
	}

	texto = tmpstr;
	largo = texto.length;

	if ( largo < 2 )
		return false;

	for (i=0; i < largo ; i++ )
	{
		if ( texto.charAt(i) !="0" && texto.charAt(i) != "1" && texto.charAt(i) !="2" && texto.charAt(i) != "3" && texto.charAt(i) != "4" && texto.charAt(i) !="5" && texto.charAt(i) != "6" && texto.charAt(i) != "7" && texto.charAt(i) !="8" && texto.charAt(i) != "9" && texto.charAt(i) !="k" && texto.charAt(i) != "K" )
			return false;
	}

	var invertido = "";

	for ( i=(largo-1),j=0; i>=0; i--,j++ )
		invertido = invertido + texto.charAt(i);

	var dtexto = "";

	dtexto = dtexto + invertido.charAt(0);
	dtexto = dtexto + '-';
	var cnt = 0;

	for ( i=1,j=2; i<largo; i++,j++ )
	{
		if ( cnt == 3 )
		{
			dtexto = dtexto + '.';
			j++;
			dtexto = dtexto + invertido.charAt(i);
			cnt = 1;
		}
		else
		{
			dtexto = dtexto + invertido.charAt(i);
			cnt++;
		}
	}

	invertido = "";

	for ( i=(dtexto.length-1),j=0; i>=0; i--,j++ )
		invertido = invertido + dtexto.charAt(i);
	if ( revisarDigito2(texto) )
		return true;

	return false;
}

function ValidarRut(rut, dvrut)
{
	if(!Rut(rut, dvrut))
	{
		alert('El RUT ingresado es inválido.');
		return false;
	}

	return true;
}

function KeyPress(obj, e)
{
	var tecla = (document.all) ? e.keyCode : e.which;

	if (tecla == 13)
	{
		obj.blur();
		return false;
	}

	return true;
}

function myShowDiv( strDivName )
{
	if (isNS4)
		objElement = document.layers[strDivName];
	else if (isIE4)
		objElement = document.all[strDivName].style;
	else if (isIE5 || isNS6)
		objElement = document.getElementById(strDivName).style;

	if(isNS4 || isIE4)
	{
		objElement.visibility = 'visible';
		objElement.position = 'relative';
	}
	else if (isIE5 || isNS6)
	{
		objElement.display = 'inline';
	}
}

function myHideDiv( strDivName )
{
	if (isNS4)
		objElement = document.layers[strDivName];
	else if (isIE4)
		objElement = document.all[strDivName].style;
	else if (isIE5 || isNS6)
		objElement = document.getElementById(strDivName).style;

	if(isNS4 || isIE4)
	{
		objElement.visibility = 'hidden';
		objElement.position = 'absolute';
	}
	else if (isIE5 || isNS6)
	{
		objElement.display = 'none';
	}
}


function SetField( form, name, value )
{
	var i, field, old_value;

	if( form.elements[ name ] )
	{
		field = form.elements[ name ];

		switch( field.type )
		{
		case 'text':
			field.value = value;
			break;

		case 'select-one':
		case 'select-multiple':
			for( i=0; i<field.options.length; i++ )
			{
				if( field.options[i].value == value )
				{
					field.options[i].selected = true;
				}
			}
			break;

		case 'checkbox':
			if( field.value )
				field.checked = true;
			else
				field.checked = false;
			break;

		case 'radio':
			if( field.value == value )
				field.checked = true;
			else
				field.checked = false;
			break;

		}
	}
}


//Setea foco primer elemento de los formularios
function SetFocoPrimerElemento()
{
  /*var bFound = false; 

  for (f=0; f < document.forms.length; f++) 
  {
	document.forms[f].setAttribute("autocomplete", "off");
    for(i=0; i < document.forms[f].length; i++)
    {
		if(document.forms[f][i].name)
		{
    		if (document.forms[f][i].type != "hidden" && document.forms[f][i].disabled != true) 
		    { 
    	    	try {
	    	    	document.forms[f][i].focus();
    	    	    var bFound = true;
	       		}
		        catch(er) {
    		    }
	      	}
		}
      	
		if (bFound == true)
        	break;
    }
    
	if (bFound == true)
    	break;
  }
	*/
}




/* Resize IFRAME */
/***********************************************
* IFrame SSI script II- © Dynamic Drive DHTML code library (http://www.dynamicdrive.com)
* Visit DynamicDrive.com for hundreds of original DHTML scripts
* This notice must stay intact for legal use
***********************************************/
//Input the IDs of the IFRAMES you wish to dynamically resize to match its content height:
//Separate each ID with a comma. Examples: ["myframe1", "myframe2"] or ["myframe"] or [] for none:
/*
ifrm -> id del iframe
seziff -> tamaño INT que se considera para que se adecue el IFRAME en Firefox (FF)
*/
function resizeCaller(ifrm, sizeff )
{
	var iframeids=[ifrm];
	//Should script hide iframe from browsers that don't support this script (non IE5+/NS6+ browsers. Recommended):
	var getFFVersion = navigator.userAgent.substring(navigator.userAgent.indexOf("Firefox")).split("/")[1];
	var iframehide="no";
	//extra height in px to add to iframe in FireFox 1.0+ browsers
	var FFextraHeight = parseFloat(getFFVersion)>=0.1 ? parseInt(sizeff) : 0;
	var dyniframe = new Array();
	
	for (i=0; i<iframeids.length; i++)
	{
		if (document.getElementById)
		{
			var size_ff = parseFloat(FFextraHeight);
			resizeIframe(iframeids[i],size_ff);
		}
		//reveal iframe for lower end browsers? (see var above):
		if ((document.all || document.getElementById) && iframehide=="no")
		{
			var tempobj = document.all? document.all[iframeids[i]] : document.getElementById(iframeids[i]);
			tempobj.style['display'] = "block";
		}
	}
}

function resizeIframe(frameid,size_ff)
{
	var currentfr = document.getElementById(frameid);
	currentfr.height = 150;
	if (currentfr && !window.opera)
	{
		currentfr.style['display'] = "block";
		if (currentfr.contentDocument && currentfr.contentDocument.body.offsetHeight) //ns6 syntax
		{	
			currentfr.height = currentfr.contentDocument.body.offsetHeight+parseInt(size_ff);
		}
		else if (currentfr.Document && currentfr.Document.body.scrollHeight) //ie5+ syntax
		{
		  currentfr.height = currentfr.Document.body.scrollHeight;
		}
	}
}



/*
Calendario Scal C/Prototype
http://scal.fieldguidetoprogrammers.com/
*/ 
Object.extend(scal.prototype,
{
  toggleCalendar: function()
  {
      var element = $(this.options.wrapper) || this.element;
      this.options[element.visible() ? 'onclose' : 'onopen'](element);
      this.options[element.visible() ? 'closeeffect' : 'openeffect'](element, {duration: 0.5});
  },

  isOpen: function()
  { 
      return ( $(this.options.wrapper) || this.element).visible();
  }
});


/*
Ventana de alerta autoclose luego de 2 segundos
*/
var timeout;
function VentanaAlerta(html)
{
 	var texto_msg = "<span style='font-size:12px; color:red; font-weight:bold; ' align=center>"+html+"</span>";
 	Dialog.info(texto_msg,
	{
		top:100, left:100, width:400, className: "alphacube", id: "ventanaalerta"
	});
	timeout=2;
	setTimeout(infoTimeout, 1000) 
}
function infoTimeout()
{
	timeout--; 
	if (timeout > 0)
	{ 
		//Dialog.setInfoMessage("Test of info panel, it will close <br>in " + timeout + "s ...")
		setTimeout(infoTimeout, 1000)
	}
	else
		Dialog.closeInfo() 
}


/*Calendario Prototype*/
var calendar = null;
function showCalendar(element, input, container, source)            
{
    if (!calendar)
    {
      container = $(container);
      //the Draggable handle is hard coded to "rtop" to avoid other parameter.
      new Draggable(container, {handle: "rtop", starteffect: Prototype.emptyFunction, endeffect: Prototype.emptyFunction});
      
      //The singleton calendar is created.
      calendar = new scal(element, $(input), 
      {
          updateformat: 'dd-mm-yyyy',
          closebutton: '&nbsp;', 
          wrapper: container
      }); 
    }
    else
    {
			calendar.updateelement = $(input);
    }

    var date = new Date($F(input));
    calendar.setCurrentDate(isNaN(date) ? new Date() : date);
    
    var bname = navigator.appName;
        
    //Locates the calendar over the calling control  (in this example the "img").
    if (source = $(source))
		{
			if (bname == "Microsoft Internet Explorer")
			{
				/* fix bug http://scal.fieldguidetoprogrammers.com/forums/scal-forums/scal-bug-reports/floating-example-broken-ie7-if-protoscriptaculous-updated */
				containerPos = Element.cumulativeOffset(source);
				$(container).setStyle({top:containerPos.top,left:containerPos.left+22});
			}
			else
			{
				Position.clone($(source), container, {setWidth: false, setHeight: false, offsetLeft: source.getWidth() + 2});
			}
		}
		
		/* Fixed BUG --> http://scal.fieldguidetoprogrammers.com/forums/scal-forums/scal-bug-reports/floating-example-broken-ie7-if-protoscriptaculous-updated */
    /*if (source = $(source))
    {
        Position.clone($(source), container, {setWidth: false, setHeight: false, offsetLeft: source.getWidth() + 2});
    }*/
    
    calendar.openCalendar();
};