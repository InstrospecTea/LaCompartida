<html>
	<head>
		 <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	</head>
	<body>
		<div id="output"></div>
		<script type="text/javascript">
		$(function() {
			$.ajaxSetup({
				headers: {'AUTH_TOKEN' : 'afc40cc03e8739fc6e9c003990e71260edd4c607'}
			});

			$.ajax({
				//url: 'login',
				url: 'users/1/works',
				//url: 'clients/002538/matters',
				//data: {'user' : '99511620', 'password' : 'admin.asdwsx', 'app_key' : 'https://lemontech.thetimebilling.com'},
				data: {'before' : '1346209200', 'after' : '1364785200'},
				//type: 'post'
				type: 'get'
			}).done(function(data) {
				//console.log(data);
				//$('#output').html(data);
				$('#output').html(JSON.stringify(data));
			});
		});
		</script>
	</body>
</html>
