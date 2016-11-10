<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Message Setup Page</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" />
		<link rel="stylesheet" href="/libraries/external/font-awesome/css/font-awesome.min.css">
		<link rel="stylesheet" href="/css/geo_in_ads/fileinput.min.css">
		<style type="text/css">
			.page-header h1 small
			{
				font-size: 60%;
			}
			.container a
			{
				outline:0;
			}
			.container .panel-title a
			{
				text-decoration: none;
			}
			.container .panel-title a:hover
			{
				text-decoration: underline;
			}
			.container .alert-success, .container .alert-danger
			{
			    display: none;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="page-header">
				<h1>Message Setup Page <small>A page to set messages for zip code and ad_set id combination</small></h1>
			</div>
			<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
				<div class="panel panel-primary">
					<div class="panel-heading" role="tab" id="headingOne">
						<h4 class="panel-title">
							<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
								Set Messages In Bulk
							</a>
						</h4>
					</div>
					<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
						<div class="panel-body">
							<a href="/geo_in_ads/download_csv_template" target="_blank"><i class="glyphicon glyphicon-download"></i> Download Sample Template</a>
							<br/><br/><br/>
							<form enctype="multipart/form-data">
								<input id="message_file" name="message_file" type="file" class="file-loading" accept=".csv">
							</form>
						</div>
					</div>
				</div>
				<div class="panel panel-primary">
					<div class="panel-heading" role="tab" id="headingTwo">
						<h4 class="panel-title">
							<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
								Set Single Message
							</a>
						</h4>
					</div>
					<div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
						<div class="panel-body">
							<div class="row">
								<form role="form" method="post">
									<div class="col-lg-6">
										<div class="well well-sm"><strong><span class="glyphicon glyphicon-asterisk"></span>Required Field</strong></div>
										<div class="form-group">
											<label for="VersionID">Enter Version ID</label>
											<div class="input-group">
												<input type="text" class="form-control" name="VersionID" id="VersionID" placeholder="Enter Version ID" required>
												<span class="input-group-addon"><span class="glyphicon glyphicon-asterisk"></span></span>
											</div>
										</div>
										<div class="form-group">
											<label for="Message">Enter Message</label>
											<div class="input-group">
												<input type="text" class="form-control" name="Message" id="Message" placeholder="Enter Message" required>
												<span class="input-group-addon"><span class="glyphicon glyphicon-asterisk"></span></span>
											</div>
										</div>
										<div class="form-group">
											<label for="ZipCode">Enter Zip Code</label>
											<div class="input-group">
												<input type="text" class="form-control" name="ZipCode" id="ZipCode" placeholder="Enter Zip Code" required>
												<span class="input-group-addon"><span class="glyphicon glyphicon-asterisk"></span></span>
											</div>
										</div>
										<input type="submit" name="submit" id="submit" value="Submit" class="btn btn-info pull-right" data-loading-text="Loading..." >
									</div>
								</form>
								<div class="col-lg-5 col-md-push-1">
									<div class="col-md-12">
										<div id="error_msg" class="alert alert-danger">
											<span class="glyphicon glyphicon-remove"></span>&nbsp;<strong id="error_msg">Error Message</strong>
										</div>
										<div id="success_msg" class="alert alert-success">
											<strong><span class="glyphicon glyphicon-ok"></span>&nbsp;Message setup successful !!</strong>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-primary">
					<div class="panel-heading" role="tab" id="headingThree">
						<h4 class="panel-title">
							<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
								Delete Messages For Adset Version
							</a>
						</h4>
					</div>
					<div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
						<div class="panel-body">
							<div class="row">
								<form role="form" method="post">
									<div class="col-lg-6">
										<div class="well well-sm"><strong><span class="glyphicon glyphicon-asterisk"></span>Required Field</strong></div>
										<div class="form-group">
											<label for="VersionIDForDelete">Enter Version ID</label>
											<div class="input-group">
												<input type="text" class="form-control" name="VersionIDForDelete" id="VersionIDForDelete" placeholder="Enter Version ID" required>
												<span class="input-group-addon"><span class="glyphicon glyphicon-asterisk"></span></span>
											</div>
										</div>
										<input type="submit" name="deletemsg" id="deletemsg" value="Delete" class="btn btn-info pull-right" data-loading-text="Loading..." >
									</div>
								</form>
								<div class="col-lg-5 col-md-push-1">
									<div class="col-md-12">
										<div id="error_msg_del"  class="alert alert-danger">
											<span class="glyphicon glyphicon-remove"></span>&nbsp;<strong>Error Message</strong>
										</div>
										<div id="success_msg_del"  class="alert alert-success">
											<strong><span class="glyphicon glyphicon-ok"></span>&nbsp;Messages deletion successful !!</strong>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>			    
			</div>
		</div>
		<script src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
		<script src="/js/geo_in_ads/fileinput.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		<script>
			$(function(){
				$("#message_file").fileinput({
					uploadUrl: "/geo_in_ads/message_csv_upload",
					autoReplace: true,
					maxFileCount: 1,
					allowedFileExtensions: ["csv"]
				});
				
				$("#submit").click(function(event){
					var version_id = $("#VersionID").val();
					var zip_code = $("#ZipCode").val();
					var message = $("#Message").val();
					
					if (version_id !== '' && zip_code !== '' && message !== '')
					{
						event.preventDefault();
						$(this).button('loading');
						var alert_danger = $("#error_msg").hide();
						var alert_success = $("#success_msg").hide();

						$.ajax({
							method: "POST",
							url: "/geo_in_ads/single_message_upload",
							data: { VersionID: version_id, ZipCode: zip_code, Message: message}
						}).fail(function() {
							alert( "error" );
						}).done(function( resp ) {
							var data_resp = $.parseJSON(resp);
							if (data_resp != null && data_resp.error != null)
							{
								$(alert_danger).children("strong").html(data_resp.error);
								$(alert_success).hide();
								$(alert_danger).show();
							}
							else
							{
								$(alert_danger).hide();
								$(alert_success).show();
							}
							$("#submit").button('reset');
						});
					}
				});
				
				$("#deletemsg").click(function(event){
					var version_id = $("#VersionIDForDelete").val();
					
					if (version_id !== '')
					{
						event.preventDefault();
						$(this).button('loading');
						var alert_danger = $("#error_msg_del").hide();
						var alert_success = $("#success_msg_del").hide();

						$.ajax({
							method: "POST",
							url: "/geo_in_ads/delete_messages_for_version",
							data: { VersionID: version_id }
						}).fail(function() {
							alert( "error" );
						}).done(function( resp ) {
							var data_resp = $.parseJSON(resp);
							if (data_resp != null && data_resp.error != null)
							{
								$(alert_danger).children("strong").html(data_resp.error);
								$(alert_success).hide();
								$(alert_danger).show();
							}
							else
							{
								$(alert_danger).hide();
								$(alert_success).show();
							}
							$("#deletemsg").button('reset');
						});
					}
				});
			});
		</script>
	</body>
</html>