<html>
	<head>
		 <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	</head>
	<body>
		<div id="output"></div>
		<script type="text/javascript">
		$(function() {
			$.ajaxSetup({
				headers: {'AUTH_TOKEN' : 'f6a9469b6f52aff3f6a6af67a4823cbe68488a3c'}
			});

			$.ajax({
				//url: 'login',
				url: 'users/1/works',
				//url: 'clients/002538/matters',
				/*data: {
					'user' : '99511620',
					'password' : 'admin.asdwsx',
					'app_key' : 'https://lemontech.thetimebilling.com'
				},*/
				//data: {'before' : '1346209200', 'after' : '1364785200'},
				data: {
					'date' : 1364871600,
					'duration' : 1439,
					'notes' : 'test',
					'rate' : 1,
					'requester' : 'test test test',
					'activity_code' : 'A2013',
					'area_code' : 1,
					'matter_code' : '0001-01',
					'task_code' : 1,
					'user_id' : 1,
					'billable' : 1,
					'visible' : 1
				},
				//type: 'post'
				//type: 'get'
				type: 'put'
			}).done(function(data) {
				//console.log(data);
				//$('#output').html(data);
				$('#output').html(JSON.stringify(data));
			});
		});
		</script>
	</body>
</html>
