<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class mailgun_example extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('mailgun');
    $this->load->library('tank_auth');
	}

	private function is_access_allowed()
	{
    if($this->tank_auth->is_logged_in()) 
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			if(strtolower($role) == 'admin')
			{
				return true;
			}
		}

		return false;
	}

	public function index()
	{
		if(!$this->is_access_allowed())
		{
			show_404();
		}

		echo '
			<!DOCTYPE html>
			<html>
				<head>
					<title>mailgun examples</title>
				</head>
				<body>
					example use of mailgun emails
					<form id="email_form" target="_blank" method="post">
						<div>
							<label for="from_email">From email address</label>
							<input id="from_email" name="from_email" type="text" value="scott.huber@vantagelocal.com"></input>
							<label for="to_email">To email address</label>
							<input id="to_email" name="to_email" type="text" value="scott.huber@vantagelocal.com"></input>
							<label for="bcc_email">BCC email address (for advanced mail)</label>
							<input id="bcc_email" name="bcc_email" type="text" value="ScottHuber@gmail.com"></input>
						</div>
						<div>
							<input type="submit" form="email_form" name="simple_email_button" formmethod="post" formtarget="_blank" formaction="/mailgun_example/send_simple_email" value="/mailgun_example/send_simple_email"></input>
						</div>
						<div>
							<input type="submit" form="email_form" name="advanced_email_button" formaction="/mailgun_example/send_advanced_email" value="/mailgun_example/send_advanced_email"></input>
						</div>
						<div>
							<input type="submit" form="email_form" name="error_email_button" formaction="/mailgun_example/send_email_with_plain_error" value="/mailgun_example/send_email_with_plain_error"></input>
						</div>
						<div>
							<input type="submit" form="email_form" name="error_email_button" formaction="/mailgun_example/send_email_with_mailgun_error" value="/mailgun_example/send_email_with_mailgun_error"></input>
						</div>
						<div>
							<input type="submit" form="email_form" name="error_email_button" formaction="/mailgun_example/send_email_with_curl_error" value="/mailgun_example/send_email_with_curl_error"></input>
						</div>
					</form>
		';

		echo '
				</body>
			</html>
		';
	}

	private function show_mailgun_errors($result)
	{
		if(!$this->is_access_allowed())
		{
			show_404();
		}

		if($result !== true)
		{
			echo '<br />got some errors';
			if($result === false)
			{
					echo '<br />'.'error of false';
			}
			if(is_array($result))
			{
				if(empty($result))
				{
					echo '<br /> empty errors array';
				}
				foreach($result as $error)
				{
					echo '<br />'.$error;
				}
			}
		}
	}

	public function send_simple_email()
	{
		if(!$this->is_access_allowed())
		{
			show_404();
		}
		
		$from = $this->input->post('from_email');
		$to = $this->input->post('to_email');

		$result = mailgun(
			$from,
			$to,
			"test simple email with plain text",
			"some \n text \n ok?"
		);

		echo 'sent simple email';
		$this->show_mailgun_errors($result);
	}

	public function send_advanced_email()
	{
		if(!$this->is_access_allowed())
		{
			show_404();
		}

		$from = $this->input->post('from_email');
		$to = $this->input->post('to_email');
		$bcc = $this->input->post('bcc_email');

		$post_overrides = array(
			'from' => $from,
			'bcc' => $bcc
		);
		$result = mailgun(
			'scott.huber@vantagelocal.com', // overriden by $post_overrides
			$to, 
			"test advanced email with html and bcc",
			"<h1>some html header</h1> and regular stuff",
			"html",
			$post_overrides
		);
		echo 'sent advanced email';

		$this->show_mailgun_errors($result);
	}

	public function send_email_with_plain_error()
	{
		if(!$this->is_access_allowed())
		{
			show_404();
		}

		$from = $this->input->post('from_email');
		$to = $this->input->post('to_email');

		$result = mailgun(
			$from,
			$to, 
			"test error email 3, this shouldn't be received",
			"<h1>some html header</h1> and regular stuff",
			"html",
			"bad string"
		);
		echo 'sent email with errors';

		$this->show_mailgun_errors($result);
	}

	public function send_email_with_mailgun_error()
	{
		if(!$this->is_access_allowed())
		{
			show_404();
		}

		$from = $this->input->post('from_email');
		$to = $this->input->post('to_email');

		$curl_overrides = array(
			CURLOPT_USERPWD => ''
		);

		$result = mailgun(
			$from,
			$to, 
			"test error email 3, this shouldn't be received",
			"<h1>some html header</h1> and regular stuff",
			"html",
			array(),
			$curl_overrides
		);
		echo 'sent email with errors';

		$this->show_mailgun_errors($result);
	}

	public function send_email_with_curl_error()
	{
		if(!$this->is_access_allowed())
		{
			show_404();
		}

		$from = $this->input->post('from_email');
		$to = $this->input->post('to_email');

		$curl_overrides = array(
			CURLOPT_URL => ''
		);

		$result = mailgun(
			$from,
			$to, 
			"test error email 3, this shouldn't be received",
			"<h1>some html header</h1> and regular stuff",
			"html",
			array(),
			$curl_overrides
		);
		echo 'sent email with errors';

		$this->show_mailgun_errors($result);
	}
}
