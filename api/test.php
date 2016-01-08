<!DOCTYPE html>
<html>
  <head>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <style type="text/css">
      pre { outline: 1px solid #ccc; padding: 5px; margin: 5px; }
      .string { color: green; }
      .number { color: darkorange; }
      .boolean { color: blue; }
      .null { color: magenta; }
      .key { color: red; }
    </style>
  </head>
  <body>
    <div id="output"></div>
    <script type="text/javascript">
      $(function() {
        $.ajaxSetup({
          headers: {'AUTHTOKEN' : '9fededa520d69aeda7293c8b8ecccd81de3cd720'}
        });

        $.ajax({
          beforeSend: function() {
            $('#output').html('<pre>Cargando</pre>');
          },
          statusCode: {
            400: function(data) {
              var str = JSON.stringify($.parseJSON(data.responseText), undefined, 4);
              $('#output').html('<pre>' + syntaxHighlight(str) + '</pre>');
            }
          },

          // url: 'login',
          // url: 'clients',
          // url: 'clients/002538/matters',
          // url: 'users/1',
          // url: 'users/1/works',
          // url: 'users/1/works/23643',
          // url: 'users/1/device',
          // url: 'users/1/device/bb9b7afc 4b246f19 f202b96d 5e70f59b a916cfbf',
          url: 'activities',

          // data: { 'user' : '99511620', 'password' : 'Etropos2015', 'app_key' : 'ttb-desktop' },
          // data: { 'after' : '1356998400', 'before' : '1388534400' },
          // data: {
          //   'created_date' : 1365193560.328282,
          //   'date' : 1365134400,
          //   'duration' : 111,
          //   'notes' : 'creación en latin1',
          //   //'rate' : 1,
          //   //'requester' : 'test1 test2 test3',
          //   //'activity_code' : 'A2013',
          //   //'area_code' : 1,
          //   'matter_code' : '000006-0002',
          //   //'task_code' : 1,
          //   'user_id' : 1,
          //   'billable' : 1,
          //   'visible' : 1
          // },
          // data: { 'token' : 'bb9b7afc 4b246f19 f202b96d 5e70f59b a916cfbf' },
          // data: { 'receive_alerts' : 1, 'alert_hour' :  1357070400 },
          // data: { 'include' : 'all' },

          type: 'get'
          // type: 'post'
          // type: 'put'
          // type: 'delete'

        }).done(function(data) {
          var str = JSON.stringify(data, undefined, 4);
          $('#output').html('<pre>' + syntaxHighlight(str) + '</pre>');
          // $('#output').html(data);
        });
      });

      function syntaxHighlight(json) {
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
          var cls = 'number';
          if (/^"/.test(match)) {
            if (/:$/.test(match)) {
              cls = 'key';
            } else {
              cls = 'string';
            }
          } else if (/true|false/.test(match)) {
            cls = 'boolean';
          } else if (/null/.test(match)) {
            cls = 'null';
          }
          return '<span class="' + cls + '">' + match + '</span>';
        });
      }
    </script>
  </body>
</html>
