 <body onLoad="startTimer();" onMousOver="stopTimer();" onMouseOut="startTimer();">
 <div class="wrapper">
	<div class="header">
		<div class="headwrap">
			<a href="/dashboard">
				<img src="/images/vl_logo.gif" width="393" height="86" />
			</a>
			<div class="login">
				<?php include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/member-index.php';?>
			</div>
		</div>
	</div>
	<div class="indexflags">
		<div class="container">
			<?php include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/index_menu.php'; ?>
			<div class="content" id="ajaxContent">
			</div>
			<div class="clearBoth"></div>
		</div><div class="clearBoth"></div>
	</div>
	<div class="push"></div>
</div>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/dashboard_functions/footer.php'; 
?>
</body>
</html>
