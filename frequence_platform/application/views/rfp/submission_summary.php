<style type="text/css">
	.card_header_link {
		float: right;
		padding: 10px 20px;
		line-height: 2rem;
		margin: 1.14rem 0 0.912rem 0;
	}
	.card-content h4 small {
		display: block;
	}
	.card .card-content p, .card-content ul, .card-content ol, .card-content blockquote, .card-content table, .card-content h5 {
		margin-left: 1rem;
	}
	.card .card-content p {
		margin-top: 1rem;
		margin-bottom: 1rem;
	}
	.submission_summary {
		margin-top: 8em;
	}
</style>
<div id="rfp_success_body_container" class="container">
	<div class="card">
		<div class="card-content">
			<a href="/rfp/gate" class="card_header_link"><i class="icon-arrow-left"></i> create another RFP</a>
			<h4 class="card-title grey-text text-darken-1">
				Your proposal has been emailed to <?php echo $proposal['cc_owner'] ? $proposal['submitter_email'] : $creator_user->email; ?>
				<small>If you don't see it arrive in your inbox, please check your spam folder.</small>
			</h4>
			<div class="submission_summary">
				<?php echo $proposal['original_submission_summary_html']; ?>
			</div>
		</div>
	</div>
</div>
