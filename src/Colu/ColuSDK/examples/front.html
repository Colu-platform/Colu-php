<html>
<head>
	<title>Registration</title>
	<script src="http://code.jquery.com/jquery-1.11.2.min.js"></script>
	<script src="http://code.jquery.com/ui/1.11.3/jquery-ui.min.js"></script>
	
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css">
	<style type="text/css">
		* {margin: 0; padding: 0;}
		body {font-family: helvetica;}
		td {padding: 5px;}
		p {padding: 5px;}
	</style>
</head>
<body>
	<script>
	$(function (){
		// make sure requests dont time out while user is approving on the client app
		$.ajaxSetup({
			  timeout: 0
		});
		
		$("#send").on("click", function() {
			// show the sending modal
			$("#dialog").dialog({
				  modal: true,
				  minWidth: 500,
				  minHeight: 300,
				  width:'auto',
				  close: function( event, ui ) {
					  $( "#dialog" ).dialog( "destroy" );
				  }
			});

			$("#dialog").html("<p>Sending request to server and waiting for user confirmation</p>");
			
			// send the request
			var username = $("#username").val();
			var phone = $("#phone").val();
			
			$.getJSON( "register_user.php", { username: username, phone: phone } )
			  .done(function( json ) {
				if (json.success) {
					// update the modal with the user ID
					var userId = json.userId;
					$("#dialog").html("<p>Success: User Id is:<br> "+userId+"</p>");
				}

				if (json.code) {
					// show QR image and start a new request
					$("#dialog").html("<p>Scan QR with your phone<br><br><img src='"+json.qr+"'></p>");

					// start new request for the scan
					$.getJSON( "register_user.php", { qr: json.code, msg: json.message } )
					  .done(function( json ) {
						  $("#dialog").html("<p>Registration complete<br>User Id is: "+json.userId+"</p>");
					  })
					  .fail(function( jqxhr, textStatus, error ) {
							// show error on modal
						    var err = textStatus + ", " + error;
						    $("#dialog").html("Error: "+error);
						    console.log( "Request Failed: " + err );
					});
				}
				
				if (json.error) {
					//update the modal with the error returned
					var error = json.Error;
					$("#dialog").html("Error: "+error);
				}	
			  })
			  .fail(function( jqxhr, textStatus, error ) {
				// show error on modal
			    var err = textStatus + ", " + error;
			    $("#dialog").html("Error: "+error);
			    console.log( "Request Failed: " + err );
			});
		});
	});
	</script>
	<div id="dialog" style="display: none; width: 500px; height: 300px;"></div>
	<div id="wrapper" style="width: 900px; margin: 0 auto;">
		<h1>Colu Registration Test Page</h1>
		<br>
		
		<table>
		<tr>
			<td width="180">Enter Username</td>
			<td><input type="text" id="username" placeholder="Enter Username">(Required)</td>
		</tr>
		<tr>
			<td>Enter Phonenumber</td>
			<td><input type="text" id="phone" placeholder="Phone Number"></td>
		</tr>
		<tr>
			<td colspan="2">
				<p>If a Phonenumber is supplied the server will send a push alert directly to the application</p>
				<p>If a Phonenumber is not supplied a QR code will be generated and the application will have to scan it.</p>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center"><button id="send">SEND</button></td>
		</tr>
		</table>
	</div>
</body>
</html>