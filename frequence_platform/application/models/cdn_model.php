<?php

require_once $_SERVER["DOCUMENT_ROOT"].'/cdn/cloudfiles.php';

class Cdn_model extends CI_Model
{

	private $rackspace_user;
	private $rackspace_api_key;
	private $rackspace_container;

	private $authentication = null;
	private $connection = null;
	private $container = null;

	public function load_asset($full_file_source, $friendly_name)
	{
		$this->get_assets_container();

		// upload file to Rackspace
		$object = $this->container->create_object("CDN_API_".uniqid()."_".$friendly_name);
		$object->load_from_filename($full_file_source);

		// thats it you are done.
		return array('open_uri'=>$object->public_uri(),'ssl_uri'=>$object->public_ssl_uri());
	}

	public function load_asset_with_directory_structure($full_file_source, $cdn_path)
	{
		$this->get_assets_container();

		$object = $this->container->create_object($cdn_path);

		if(preg_match('/\.css$/', $cdn_path))
		{
			$object->content_type = 'text/css';
		}
		$object->headers['Access-Control-Allow-Origin'] = '*';

		$object->load_from_filename($full_file_source);

		return array('open_uri'=>$object->public_uri(),'ssl_uri'=>$object->public_ssl_uri());
	}

	private function connect()
	{
		if(!$this->connection)
		{
			// Let's connect to Rackspace
			$this->authentication = new CF_Authentication($this->config->config['display_ad_rackspace_user'], $this->config->config['display_ad_rackspace_api_key']);
			$this->authentication->authenticate();
			try {
				$this->connection = new CF_Connection($this->authentication);
			}
			catch(AuthenticationException $e) {
				echo "Unable to authenticate ".$e->getMessage();
				die(0);
			}
		}
	}

	private function get_assets_container($container_name = null)
	{
		if($container_name === null)
		{
			$container_name = $this->config->config['display_ad_rackspace_container'];
		}
		$this->connect();
		$this->container = null;

		try
		{
			$this->container = $this->connection->get_container($container_name);
			$this->container->make_public();
		}
		catch(NoSuchContainerException $e) {
			$this->container = new CF_Container($this->authentication,$this->connection,"images"); // FIXME: two different container names? -CL
		}
		catch(InvalidResponseException $res) {
			// let your users know or try again or just store the file locally and try again later to push it to the Cloud
		}
	}

}

?>
