<? 
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	#se copió este archivo del framework para poder poner el intervalo del conf en los minutos.
	
	$sesion = new Sesion();
?>
<script type="text/javascript">
/* 
 * ---------------------------------------------------------------------- 
 * WebDate                                                                
 *                                                                        
 * Version: 1.1                                                           
 * Release date: 2005.01.30                                               
 *                                                                        
 * Copyright (c) Amir Firdus, 2005.                                       
 * All rights reserved.                                                   
 *                                                                        
 * License:                                                               
 * This software is free to use and distribute as long as the             
 * link to Firdus Software web site (www.firdus.com) is not removed.      
 * Removing the link requires author's permission and it may cost money.  
 * ---------------------------------------------------------------------- 
 */
 
/*
 *
 * > PAT      - short for Pick-A-Time.
 * > Receiver - html element receiving the time value.
 * > Trigger  - html element that shows PAT component.
 *
 * > All methods are prefixed with 'pat' to allow 
 *   easy integration with existing code.
 *
 */
 
/* receiver, html element to receive the date */
var receiverRef;

/* date delimiter */
var delimiterRef;

/* flag that indicates that mouse is down */
var active = false;

/* time to sleep before date values start scrolling when mouse is down */
var sleep = 250;

/* counter used to set variable scroll speed */
var count = 100;
 
/*
 * Create PAT component.
 *
 * Date td elements have to be initialized.
 * Have to escape ' inside document.write().
 */
function patCreate(){

  document.write('<div id="fs-pat-div">');
  document.write('<table id="fs-pat-table" cellspacing="1">');
  document.write(' <tr>');
  document.write('  <td class="buttonOut" onMouseDown="javascript:this.className=\'buttonIn\';patSetActive(true);patMoveHour(\'+\');"   onMouseUp="javascript:this.className=\'buttonOut\';patSetActive(false);" onMouseOut="javascript:this.className=\'buttonOut\';patSetActive(false);">H+</td>');
  document.write('  <td class="buttonOut" onMouseDown="javascript:this.className=\'buttonIn\';patSetActive(true);patMoveMinute(\'+\');" onMouseUp="javascript:this.className=\'buttonOut\';patSetActive(false);" onMouseOut="javascript:this.className=\'buttonOut\';patSetActive(false);">M+</td>');
  //document.write('  <td class="buttonOut" onMouseDown="javascript:this.className=\'buttonIn\';patSetActive(true);patMoveSecond(\'+\');" onMouseUp="javascript:this.className=\'buttonOut\';patSetActive(false);" onMouseOut="javascript:this.className=\'buttonOut\';patSetActive(false);">S+</td>');
  document.write('  <td class="task" rowspan="4">');
  document.write('	<a class="taskLink" href="javascript:patOk();">Ok</a><br>');
  document.write('	<a class="taskLink" href="javascript:patCancel();">Cancelar</a><br>');
  document.write('	<a class="taskLink" href="javascript:patClear();">Borrar</a><br>');
  //document.write('	<a class="taskLink" href="javascript:patNow();">Ahora</a><br>');
  document.write('  </td>');
  document.write(' </tr>');
  document.write(' <tr>');
  document.write('  <td class="date">0</td>');
  document.write('  <td class="date">0</td>');
  //document.write('  <td class="date">0</td>');
  document.write(' </tr>');
  document.write(' <tr>');
  document.write('  <td class="buttonOut" onMouseDown="javascript:this.className=\'buttonIn\';patSetActive(true);patMoveHour(\'-\');"   onMouseUp="javascript:this.className=\'buttonOut\';patSetActive(false);" onMouseOut="javascript:this.className=\'buttonOut\';patSetActive(false);">H-</td>');
  document.write('  <td class="buttonOut" onMouseDown="javascript:this.className=\'buttonIn\';patSetActive(true);patMoveMinute(\'-\');" onMouseUp="javascript:this.className=\'buttonOut\';patSetActive(false);" onMouseOut="javascript:this.className=\'buttonOut\';patSetActive(false);">M-</td>');
  //document.write('  <td class="buttonOut" onMouseDown="javascript:this.className=\'buttonIn\';patSetActive(true);patMoveSecond(\'-\');" onMouseUp="javascript:this.className=\'buttonOut\';patSetActive(false);" onMouseOut="javascript:this.className=\'buttonOut\';patSetActive(false);">S-</td>');
  document.write(' </tr>');
  document.write(' <tr>');
  document.write('  <td colspan="3">');
  document.write('');
  document.write('  </td>');
  document.write(' </tr>');
  document.write('</table>');
  document.write('</div>');
}

function findPos(obj) 
{
	var curleft = curtop = 0;
	if (obj.offsetParent) 
	{
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent) 
		{
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
	return [curleft,curtop];
}

/*
 * Set mouse down flag. 
 */ 
function patSetActive(value)
{
   active = value;
}

/*
 * Show PAT component.
 *
 * note:
 * - style property is only for inline style elements (defined in style element attribute)
 */ 
function patShow(receiver, trigger, delimiter){
  
  var divRef = document.getElementById("fs-pat-div");
  var triggerRef = document.getElementById(trigger);  
  
  // set component position over the trigger element
  divRef.style.top = findOffsetTop(triggerRef) + "px";
  divRef.style.left = findOffsetLeft(triggerRef) + "px";
  
  // make it visible
  divRef.style.visibility = "visible";
    
  // set receiver reference (required for ok and cancel events)
  receiverRef = document.getElementById(receiver);

  // initialize component
  // if no value -> now
  // otherwise parse existing value from the receiver based on hh-mm-ss format
  if (receiverRef.value == ""){  
    patSetHour(patZeroLeftPad(0, 2));
    patSetMinute("00");
    patSetSecond("00");
    //patNow();
  }else{
    var date = new String(receiverRef.value);
    patSetHour(date.substring(0,2));
    patSetMinute(date.substring(3,5));
    patSetSecond(date.substring(6,8));
  }
  
  // set delimiter reference
  delimiterRef = delimiter;
}

/*
 * Ok click, update receiver element with picked date and hide PAT component.
 */ 
function patOk(){
  document.getElementById("fs-pat-div").style.visibility = "hidden";
  receiverRef.value = patGetHour() + delimiterRef + patGetMinute() + delimiterRef + patGetSecond();
  if(receiverRef.onchange)
	  receiverRef.onchange();
}

/*
 * Cancel click, hide PAT component.
 */ 
function patCancel(){
  document.getElementById("fs-pat-div").style.visibility = "hidden";  
}

/*
 * Clear click, clear the receiver and hide PAT component.
 */ 
function patClear(){
  document.getElementById("fs-pat-div").style.visibility = "hidden";
  receiverRef.value = "";
}

/*
 * Set PAT time values to now.
 */ 
function patNow(){
  var now = new Date();
  patSetHour(now.getHours());
  patSetMinute(now.getMinutes()); 
  patSetSecond(now.getSeconds());
}

/*
 * Move hour value.
 * If direction is + than hour is increased.
 * If direction is - than hour is decreased.
 */ 
function patMoveHour(direction){
  
  if(active) {
  
    // calc new hour value
    var newHour;
    if (direction == "+"){
      newHour = eval(patGetHour()) + 1;
    }else if (direction == "-"){
      newHour = eval(patGetHour()) - 1;
    }else{
      alert("Invalid argument for patMoveHour().");
    }
     
    // create new date, date is hardcoded - only time matters
    var newTime = new Date(2000, 1, 1, newHour, patGetMinute(), patGetSecond());
  
    // update the component
    patSetHour(newTime.getHours());
    patSetMinute(newTime.getMinutes());
    patSetSecond(newTime.getSeconds());
 
    // calculate sleep period for variable scroll speed.
    var currentSpeed;
    if (count < 3){
      currentSpeed = sleep;
    } else if (count >= 3 && count < 10) {
      currentSpeed = 0.7 * sleep;
    } else if (count >= 10){
      currentSpeed = 0.4 * sleep;
    }
    count = count + 1;    

    // recursive timer call, scrolls year when mouse is down   
    setTimeout("patMoveHour('" + direction + "')",currentSpeed); 
    
  } else {
    // mouse not active (mouse up)
    count = 0;
  }    
  
}

/*
 * Move minute value.
 * If direction is + than year is increased.
 * If direction is - than year is decreased.
 */ 
function patMoveMinute(direction){
  
  <? if( method_exists('Conf','GetConf') ) { ?>
  		intervalo = <?= Conf::GetConf($sesion,'Intervalo'); ?>;
  <? } else if( method_exists('Conf','Intervalo') ) { ?>
			intervalo = <?= Conf::Intervalo(); ?>;
	<? } ?>
  if(active) {
  
    // calc new month value
    var newMinute;
    if (direction == "+"){
      newMinute = eval(patGetMinute()) + intervalo;
    }else if (direction == "-"){
      newMinute = eval(patGetMinute()) - intervalo;
    }else{
      alert("Invalid argument for patMoveMinute().");
    }
  
    // create new date, date is hardcoded - only time matters
    var newTime = new Date(2000, 1, 1, patGetHour(), newMinute, patGetSecond());
  
    // update the component
    patSetHour(newTime.getHours());
    patSetMinute(newTime.getMinutes());
    patSetSecond(newTime.getSeconds());
   
    // calculate sleep period for variable scroll speed.
    var currentSpeed;
    if (count < 3){
      currentSpeed = sleep;
    } else if (count >= 3 && count < 10) {
      currentSpeed = 0.7 * sleep;
    } else if (count >= 10){
      currentSpeed = 0.4 * sleep;
    }
    count = count + 1;    

    // recursive timer call, scrolls month when mouse is down   
    setTimeout("patMoveMinute('" + direction + "')",currentSpeed); 
    
  } else {
    // mouse not active (mouse up)
    count = 0;
  }    
}

/*
 * Move second value.
 * If direction is + than year is increased.
 * If direction is - than year is decreased.
 */ 
function patMoveSecond(direction){
  
  if(active) {

    // calc new day value
    var newSecond;
    if (direction == "+"){
      newSecond = eval(patGetSecond()) + 1;
    }else if (direction == "-"){
      newSecond = eval(patGetSecond()) - 1;
    }else{
      alert("Invalid argument for patMoveSecond().");
    }
  
    // create new date, date is hardcoded - only time matters
    var newTime = new Date(2000, 1, 1, patGetHour(), patGetMinute(), newSecond);
  
    // update the component
    patSetHour(newTime.getHours());
    patSetMinute(newTime.getMinutes());
    patSetSecond(newTime.getSeconds());
 
    // calculate sleep period for variable scroll speed.
    var currentSpeed;
    if (count < 3){
      currentSpeed = sleep;
    } else if (count >= 3 && count < 10) {
      currentSpeed = 0.7 * sleep;
    } else if (count >= 10){
      currentSpeed = 0.4 * sleep;
    }
    count = count + 1;    

    // recursive timer call, scrolls day when mouse is down    
    setTimeout("patMoveSecond('" + direction + "')",currentSpeed); 
  
  } else {
    // mouse not active (mouse up)
    count = 0;
  }  
}

/*
 * Get hour value.
 */ 
function patGetHour(){
  var table = document.getElementById("fs-pat-table");
  var row = table.rows[1];
  var cell = row.cells[0];
  return cell.firstChild.nodeValue;
}

/*
 * Set year value.
 */ 
function patSetHour(hour){
  var table = document.getElementById("fs-pat-table");
  var row = table.rows[1];
  var cell = row.cells[0];
  cell.firstChild.nodeValue = patZeroLeftPad(hour, 2);
}

/*
 * Get minute value.
 */ 
function patGetMinute(){
  var table = document.getElementById("fs-pat-table");
  var row = table.rows[1];
  var cell = row.cells[1];
  return cell.firstChild.nodeValue;
}

/*
 * Set minute value.
 */ 
function patSetMinute(minute){
  var table = document.getElementById("fs-pat-table");
  var row = table.rows[1];
  var cell = row.cells[1];
  cell.firstChild.nodeValue = patZeroLeftPad(minute, 2);
}

/*
 * Get second value.
 */ 
function patGetSecond(){
	return "00";
  var table = document.getElementById("fs-pat-table");
  var row = table.rows[1];
  var cell = row.cells[2];
  return cell.firstChild.nodeValue;
}

/*
 * Set second value.
 */ 
function patSetSecond(second){
	return;
  var table = document.getElementById("fs-pat-table");
  var row = table.rows[1];
  var cell = row.cells[2];
  //cell.firstChild.nodeValue = patZeroLeftPad(second, 2);
  cell.firstChild.nodeValue = patZeroLeftPad(0, 2);
}

/*
 * Leftpad the value with zeros.
 */ 
function patZeroLeftPad(value, length) {
  
  // build the zeros string
  var zeros = new String();
  for (i=0; i<length; i++) {
    zeros = zeros + "0";
  }

  // prepend zeros to the value
  var longValue = zeros + value;

  // return the last length characters
  return longValue.substring(longValue.length - length, longValue.length);
}


/*
 * Find total left offset.
 */ 
function findOffsetLeft(obj){
  var curleft = 0;
  if (obj.offsetParent){
    while (obj.offsetParent){
      curleft += obj.offsetLeft
        obj = obj.offsetParent;
    }
  }else if (obj.x){
    curleft += obj.x;
  }
  
  return curleft;
}

/*
 * Find total top offset.
 */ 
function findOffsetTop(obj){
  var curtop = 0;
  if (obj.offsetParent)	{
    while (obj.offsetParent){
      curtop += obj.offsetTop
      obj = obj.offsetParent;
    }
  }else if (obj.y){
    curtop += obj.y;
  }
		
  return curtop;
}
</script>