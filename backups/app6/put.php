<html>
  <head>
    <title>Amazon SimpleDBScratchpad</title>
    <script src="js/hmacsha1.js"></script>
    <script src="js/awssigner.js"></script>
    <script src="js/scratchpad.js"></script>
    <script>
        function invokeRequest() {
            var form = document.forms[0];
            var accessKeyId =  parent.navbar.getAccessKeyId();
            var secretKey =  parent.navbar.getSecretAccessKey();
            var url = generateSignedURL("PutAttributes",form, accessKeyId, secretKey, "https://sdb.amazonaws.com", "2009-04-15");
            var postFormArea = document.getElementById("PostFormArea");
            postFormArea.innerHTML = getFormFieldsFromUrl(url);
            var postForm = document.getElementById("PostForm");
            postForm.submit();
        }

        function displayUrl() {
            var form = document.forms[0];
            var accessKeyId =  parent.navbar.getAccessKeyId();
            var secretKey =  parent.navbar.getSecretAccessKey();
            var url = generateSignedURL("PutAttributes",form, accessKeyId, secretKey, "https://sdb.amazonaws.com", "2009-04-15");
            document.getElementById("preview").innerHTML = "<b>Signed URL:</b><p/>" + url + "<p/>";
            document.getElementById("preview").style.display = "block";
        }

        function displayStringToSign() {
            var form = document.forms[0];
            var accessKeyId =  parent.navbar.getAccessKeyId();
            var secretKey =  parent.navbar.getSecretAccessKey();
            var url = generateSignedURL("PutAttributes",form, accessKeyId, secretKey, "https://sdb.amazonaws.com", "2009-04-15");
            var stringToSign = getStringToSign(url);
            document.getElementById("preview").innerHTML = "<b>String To Sign:</b><p/>" + stringToSign + "<p/>";
            document.getElementById("preview").style.display = "block";
        }
    </script>

    <link rel="stylesheet" type="text/css" href="css/scratchpad.css"/>
  </head>

  <body marginheight="0" marginwidth="0" bottommargin="0" rightmargin="0" leftmargin="0" topmargin="0">
    <form name="myform" action="" enctype="application/x-www-form-urlencoded" method="get">
      <table border="0" align="center" cellpadding="0" cellspacing="0" style="width: 100%">
        <tr>
          <td width="50%"><img height="1" src="images/spacer.gif" width="100"></td>
          <td valign="top">
            <table width="100%">
              <tr>
                <td valign="top" nowrap><font class="header">PutAttributes</font>
                  <br/><br/>
                  <div style="padding-bottom: 2px; border-bottom: 1px dashed #cccccc; width: 100%"></div>
                  <table cellspacing="10" width="100%" style="background-color: #eeeeee;  padding: 10px;">
                    <tr><td>
                        <table width="100%" align="center"><tr><td>
                              <table border="0" width="100%" style="padding: 0px; margin: 0px; border: 2px solid #D5D5D5; background: url(images/bg-table.png) left top repeat-x;">
   <tr>
      <td>
         <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
            <tr>
               <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Domain Name</span></td>
               <td nowrap><input class="input" type="text" name="DomainName"></td>
            </tr>
         </table>
         <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
            <tr>
               <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Item Name</span></td>
               <td nowrap><input class="input" type="text" name="ItemName"></td>
            </tr>
         </table>
         <table style="margin: 20px" cellpadding="0" width="90%" cellspacing="2" border="0" bgcolor="#eeeeee" id="Attribute.1">
            <tr>
               <td style="border: 2px dashed #cccccc;">
                  <table width="100%">
                     <tr>
                        <td><input align="right" hspace="5" vspace="5" type="image" src="images/add.gif" onclick="addContainer(this, 8);return false;" id="Attribute.1.Add"><input align="right" type="image" style="display:''" src="images/delete.gif" hspace="5" vspace="5" onclick="deleteContainer(this, 8);return false;" id="Attribute.1.Delete"><div class="legend"><img src="images/bluearrow.gif" align="absmiddle" hspace="10">Attribute
                           </div>
                        </td>
                     </tr>
                  </table>
                  <fieldset style="padding-left: 30px;padding-right: 30px;padding-bottom: 30px; border: 0px;">
                     <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
                        <tr>
                           <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Name</span></td>
                           <td nowrap><input class="input" type="text" name="Attribute.1.Name"></td>
                        </tr>
                     </table>
                     <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
                        <tr>
                           <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Value</span></td>
                           <td nowrap><input class="input" type="text" name="Attribute.1.Value"></td>
                        </tr>
                     </table>
                     <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
                        <tr>
                           <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Replace</span></td>
                           <td nowrap><input class="input" type="text" name="Attribute.1.Replace"></td>
                        </tr>
                     </table>
                  </fieldset>
               </td>
            </tr>
         </table>
         <table style="margin: 20px" cellpadding="0" width="95%" cellspacing="4" border="0" bgcolor="#eeeeee" id="Expected">
            <tr>
               <td style="border: 2px dashed #cccccc;">
                  <div class="legend"><img src="images/bluearrow.gif" align="absmiddle" hspace="10">Expected
                  </div>
                  <fieldset style="padding-left: 30px;padding-right: 30px;padding-bottom: 30px; border: 0px;">
                     <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
                        <tr>
                           <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Name</span></td>
                           <td nowrap><input class="input" type="text" name="Expected.Name"></td>
                        </tr>
                     </table>
                     <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
                        <tr>
                           <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Value</span></td>
                           <td nowrap><input class="input" type="text" name="Expected.Value"></td>
                        </tr>
                     </table>
                     <table cellpadding="0" cellspacing="3" border="0" bgcolor="#eeeeee" width="100%">
                        <tr>
                           <td align="right" style="padding-right:25px;padding-left:0px" width="250"><span class="label">Exists</span></td>
                           <td nowrap><input class="input" type="text" name="Expected.Exists"></td>
                        </tr>
                     </table>
                  </fieldset>
               </td>
            </tr>
         </table>
      </td>
   </tr>
</table>
                        </td></tr></table>
                    </td></tr>
                  </table>
                </td>
              </tr>

            </table>
            <div class="blurb" id="preview" style="margin: 20 1 1 1;overflow: auto;display:none"></div>
            <img height="15" src="images/spacer.gif" width="700">
            <table width="100%">
              <tr>
                <td><a href="javascript:invokeRequest();"><img   border="0" src="images/button-invokerequest.png"></a>&nbsp;&nbsp;&nbsp;
                    <a href="javascript:displayUrl();"><img   border="0" src="images/button-displayurl.png"></a>&nbsp;&nbsp;&nbsp;
                    <a href="javascript:document.forms[0].reset()"><img border="0" src="images/button-resetform.png"></a>
                </td>
              </tr>
            </table>
          </td>
          <td width="50%"><img height="1" src="images/spacer.gif" width="100"></td>
        </tr>
      </table>

      <br/><br/>
      <div style="padding-bottom: 2px; border-bottom: 1px dashed #cccccc; width: 100%"></div>
      <center><div style="padding: 15px" class="smallLabel">Amazon SimpleDB API Version: 2009-04-15. Scratchpad generated: Thu Jul 15 15:51:04 PDT 2010  </div></center>
    </form>
    <form id="PostForm" name="PostForm" target="_new" action="https://sdb.amazonaws.com" enctype="application/x-www-form-urlencoded" method="get">
      <div id="PostFormArea"></div>
    </form>
  </body>
</html>




