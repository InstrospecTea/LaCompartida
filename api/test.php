<html>
	<head>
		 <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	</head>
	<body>
		<div id="output"></div>
		<script type="text/javascript">
		$(function() {
			$.ajaxSetup({
				headers: { "AUTH_TOKEN" : "lemontech#99511620" }
			});

			$.ajax({
				url: "users/333/works",
				type: 'get',
				data: {}
			}).done(function(data) {
				$('#output').html(data);
			});
		});
		</script>
	</body>
</html>
