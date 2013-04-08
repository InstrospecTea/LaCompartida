<html>
	<head>
		 <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	</head>
	<body>
		<div id="output"></div>
		<script type="text/javascript">
		$(function() {
			$.ajaxSetup({
				headers: {'AUTH_TOKEN' : '89aad45ae6394b6bac819d95592fe1b87d403313'}
			});

			$.ajax({
				url: 'users/1/works/6610',
				/*data: {
					'user' : '99511620',
					'password' : 'admin.asdwsx',
					'app_key' : 'https://lemontech.thetimebilling.com'
				},*/
				//data: {'after' : '1364871600', 'before' : '1364871600'},
				data: {
					'created_date' : 1365193560.328282,
					'date' : 1365134400,
					'duration' : 100,
					'notes' : 'AAAAA BBBBB',
					//'rate' : 1,
					//'requester' : 'test1 test2 test3',
					//'activity_code' : 'A2013',
					//'area_code' : 1,
					'matter_code' : '0315-0003',
					//'task_code' : 1,
					//'user_id' : 1,
					'billable' : 1,
					'visible' : 1
				},
				//type: 'get'
				type: 'post'
				//type: 'put'
			}).done(function(data) {
				//console.log(data);
				//$('#output').html(data);
				$('#output').html(JSON.stringify(data));
			});
		});
		</script>
	</body>
</html>
