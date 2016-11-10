<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once FCPATH . '/vendor/autoload.php';
use OpenCloud\Rackspace;
use OpenCloud\ObjectStore\Constants\UrlType;

/**
 * Methods for interacting with Rackspace CDN services.
 *
 * @package vl_rackspace_cdn
 */
class vl_rackspace_cdn
{

	private $client;
	private $cdn_service;
	private $container;

	function __construct()
	{
		$this->client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
			'username' => RACKSPACE_USER,
			'apiKey' => RACKSPACE_API_KEY,
		));
	}

	/**
	 * Get a list of files from the specified Rackspace container / path.
	 *
	 * @param string $container_name - name of the Rackspace CDN container
	 * @param string $path - name of the prefix/subfolder
	 * @return array - file URLs
	 */
	public function get_file_info($container_name, $path)
	{
		$this->use_container($container_name);

		$object = NULL;

		try
		{
			$object = $this->container->getObject($path);
		}
		catch(OpenCloud\ObjectStore\Exception\ObjectNotFoundException $exception)
		{
			// ignore exception, leave $object NULL
		}

		return $object;
	}

	/**
	 * Get a list of files from the specified Rackspace container / path.
	 *
	 * @param string $container_name - name of the Rackspace CDN container
	 * @param string $path - name of the prefix/subfolder
	 * @return array - file URLs
	 */
	public function get_file_list($container_name, $path = '')
	{
		$this->use_container($container_name);

		// TODO: get list of container contents
		$object_list_filter = array(
			'marker' => $path, // start from the path, without including it
			'prefix' => $path, // show everything else that starts with the path
		);

		return $this->container->objectList($object_list_filter);

		return [];
	}

	/**
	 * Check if a file exists in Rackspace CDN
	 *
	 * @param string $container_name - name of the Rackspace container
	 * @param string $path - name of the file to be checked
	 * @return boolean - whether file exists
	 */
	public function file_exists($container_name, $path)
	{
		$this->use_container($container_name);

		return $this->container->objectExists($path);
	}

	/**
	 * Upload file. Fails if file exists.
	 *
	 * @param string $container_name - name of the Rackspace container
	 * @param array $file_object - uploaded file object. Includes 'name', 'local_file_path', 'size', 'type'.
	 * @return object - Response from Rackspace.
	 */
	public function upload_file($container_name, $file_object)
	{
		// TODO: catch failure if bad container
		$this->use_container($container_name);

		$handle = fopen($file_object['local_file_path'], 'r');
		$object = $this->container->uploadObject($file_object['name'], $handle);

		return $object;
	}

	/**
	 * Delete file
	 *
	 * @param string $container_name - Name of the Rackspace container
	 * @param string $file_name - Name of the uploaded file.
	 * @return array - Response from Rackspace.
	 */
	public function delete_file($container_name, $file_name)
	{
		$this->use_container($container_name);

		$response = array(
			'is_success' => false,
			'errors' => array()
		);

		$object = $this->container->getObject($file_name);
		if($object)
		{
			$delete_result = $object->delete();
			// var_dump($delete_result); // XXX
			$response['is_success'] = !empty($delete_result);
		}

		return $response;
	}

	/**
	 * get the CDN URL for a file
	 *
	 * @param object $file_object - Rackspace file object, as returned by get_file
	 * @param boolean $secure - SSL or not
	 * returns string - URL
	 */
	public function get_cdn_url($file_object, $secure = false)
	{
		$url_type = ($secure ? UrlType::SSL : UrlType::CDN);
		return (string) $file_object->getPublicUrl($url_type);
	}

	/**
	 * get the CDN URL for several file
	 * see vl_rackspace_cdn::get_cdn_url()
	 *
	 * @param array $file_object_list - array of Rackspace file objects
	 * @param boolean $secure - SSL or not
	 * returns array - URLs
	 */
	public function get_cdn_urls($file_object_list, $secure = false)
	{
		// TODO: catch errors
		$url_type = ($secure ? UrlType::SSL : UrlType::CDN);
		$urls = array();
		foreach($file_object_list as $object)
		{
			$url_data = $object->getPublicUrl($url_type);
			$urls[] = (string) $url_data;
		}
		return $urls;
	}

	/**
	 * Begin using a given Rackspace container, with interface at $this->container.
	 * If it fails, $this->container remains unmodified.
	 *
	 * @param string $container_name
	 * returns boolean - success of operation
	 */
	private function use_container($container_name)
	{
		if (empty($container_name))
		{
			throw new Exception('Container name is required', 400);
		}

		if(!$this->container || $this->container->name !== $container_name)
		{
			$region = 'DFW';
			$object_store_service = $this->client->objectStoreService(null, $region);
			$new_container = $object_store_service->getContainer($container_name);
			if($new_container)
			{
				$this->container = $new_container;
			}
			else
			{
				return false;
			}
		}

		return true;
	}

}
