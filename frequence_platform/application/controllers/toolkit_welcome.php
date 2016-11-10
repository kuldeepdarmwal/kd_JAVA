<?php
class toolkit_welcome extends CI_Controller {

	public function index()
	{
		echo 'Hello World!';
                if(isset($_POST)){
                   echo '<br/>POST IS SET'; 
                }
	}
}
?>