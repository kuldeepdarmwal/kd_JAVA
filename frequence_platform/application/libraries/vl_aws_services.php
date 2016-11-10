<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

require FCPATH . '/vendor/autoload.php';
use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Guzzle\Http\EntityBody;
use Aws\S3\Exception\S3Exception;

/**
 * Methods for interacting with AWS services.
 * Basically just makes them easier to use for 
 * our specific use case.
 *
 * @package vl_aws_services
 */
class vl_aws_services
{

	function __construct()
	{
		$this->s3Client = S3Client::factory(array(
 			'key' => AWS_KEY,
 			'secret' => AWS_SECRET
		));

		$this->s3Client->registerStreamWrapper();
	}

	/**
	 * Get a list of files from the specified S3 bucket / prefix.
	 *
	 * @param string $bucket - name of the S3 bucket
	 * @param string $prefix - name of the prefix/subfolder
	 * @return object - ResourceIteratorInterface (AWS)
	 */
	public function get_file_list($bucket, $prefix = null)
	{
		if (empty($bucket))
		{
			throw new Exception('Bucket name is required', 400);
		}

		$iterator_config = array(
			'Bucket' => $bucket
		);

		if (!empty($prefix))
		{
			$iterator_config['Prefix'] = $prefix;
			$iterator_config['Marker'] = $prefix;
		}

		return $this->s3Client->getIterator('ListObjects', $iterator_config);
	}

	/**
	 * Get an array of file keys from the specified S3 bucket / prefix
	 *
	 * @param string $bucket - name of the S3 bucket
	 * @param string $prefix - name of the prefix/subfolder
	 * @return array - file keys
	 */
	public function get_file_keys($bucket, $prefix = null)
	{
		$result = [];

		if($result_iterator = $this->get_file_list($bucket, $prefix))
		{
			foreach($result_iterator as $object)
			{
				$result[] = $object['Key'];
			}
		}

		return $result;
	}



	/**
	 * Get the contents of a file in S3.
	 *
	 * @param string $file - name of the file to be accessed
	 * @return string html - content of the file
	 */
	public function get_file($bucket, $file, $version_id = null)
	{
		$config = array(
			'Bucket' => $bucket,
			'Key' => $file
		);
		if (!empty($version_id))
		{
			$config['VersionId'] = $version_id;
		}

		try 
		{
			return $this->s3Client->getObject($config);	
		}
		catch (S3Exception $e) 
		{
		    return $e->getMessage() . "\n";
		}
	}

	/**
	 * Save contents of S3 file to local file system at save_path
	 *
	 * @param string $file - name of the file to be accessed
	 * @param string $save_path - path to where file is going to be saved
	 * @return string html - content of the file
	 */
	public function save_file_to_path($bucket, $file, $save_path, $version_id = null)
	{
		$config = array(
			'Bucket' => $bucket,
			'Key' => $file,
			'SaveAs' => $save_path
		);
		if (!empty($version_id))
		{
			$config['VersionId'] = $version_id;
		}

		try 
		{
			return $this->s3Client->getObject($config);	
		}
		catch (S3Exception $e) 
		{
		    return $e->getMessage() . "\n";
		}
	}

	/**
	 * Get the contents of a file in S3.
	 *
	 * @param string $file - name of the file to be accessed
	 * @return string html - content of the file
	 */
	public function get_file_stream($file)
	{
		try 
		{
			return file_get_contents('s3://'.$file, 'r');
		}
		catch (S3Exception $e) 
		{
		    return $e->getMessage() . "\n";
		}
	}

	/**
	 * Get a list of files from the specified S3 bucket / prefix.
	 *
	 * @param string $bucket - name of the S3 bucket
	 * @param string $file - name of the file
	 * @return string - current file version
	 */
	public function get_current_file_version($bucket, $file)
	{
		if (empty($bucket))
		{
			throw new Exception('Bucket name is required', 400);
		}

		$config = array(
			'Bucket' => $bucket,
			'Prefix' => $file
		);

		$versions = $this->s3Client->listObjectVersions($config);
		if (empty($versions['Versions']))
		{
			return null;
		}
		return $versions['Versions'][0]['VersionId'];
	}

	/**
	 * Get the contents of a file in S3.
	 *
	 * @param string $bucket - name of the S3 bucket
	 * @param string $file - name of the file to be accessed
	 * @return string html - content of the file
	 */
	public function file_exists($file)
	{
		try 
		{
			return file_exists('s3://'.$file);
		}
		catch (S3Exception $e) 
		{
		    return $e->getMessage() . "\n";
		}
	}

	/**
	 * Upload file. Fails if file exists.
	 *
	 * @param array $file_object - uploaded file object. Includes 'name', 'tmp', 'size', 'type'.
	 * @param string $bucket - name of the S3 bucket
	 * @param boolean $should_overwrite
	 * @return array - Response from S3.
	 */
	public function upload_file($file_object, $bucket, $should_overwrite = false)
	{
		if (!$should_overwrite && $this->file_exists($bucket.'/'.$file_object['name']))
		{
			throw new Exception('File already exists', 409);
		}

		$options = array(
			'Bucket'			=>	$bucket,
			'Key'				=>	$file_object['name'],
			'Body'				=>	EntityBody::factory(fopen($file_object['tmp'], 'r'), $file_object['size']),
			'ContentType'		=>	$file_object['type']
		);

		try
		{
			$upload = $this->s3Client->putObject($options);
			return array('data' => $options, 'response' => $upload->getAll());
		}
		catch (S3Exception $e)
		{
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Upload file stream.
	 * Allows updating existing file.
	 *
	 * @param array $contents - contents of the file.
	 * @param string $file - the filename
	 * @param string $bucket - name of the S3 bucket
	 * @return array - Response from S3.
	 */
	public function upload_file_stream($contents, $file, $bucket)
	{
		try 
		{
			file_put_contents('s3://'.$bucket.'/'.$file, $contents);
			return $this->get_current_file_version($bucket, $file);
		}
		catch (S3Exception $e) 
		{
		    return $e->getMessage() . "\n";
		}
	}

	/**
	 * Delete file
	 *
	 * @param string $file_name - Name of the uploaded file.
	 * @param string $bucket - Name of the S3 bucket
	 * @return array - Response from S3.
	 */
	public function delete_file($file_name, $bucket)
	{
		$response = array(
			'is_success' => true,
			'errors' => array()
		);

		if ($this->s3Client->doesObjectExist($bucket, $file_name))
		{
			$options = array(
				'Bucket'			=>	$bucket,
				'Key'				=>	$file_name
			);

			$result = $this->s3Client->deleteObject($options);

			$response['data'] = $options;
			$response['response'] = json_encode(print_r($result, true));
		}
		else
		{
			$response['is_success'] = false;
			$response['errors'][] = 'Object does not exist';
		}
	}

	/**
	 * Used to create temporary, pre-signed URLs so that Admin/Ops/Creative users
	 * can view uploaded assets. Please only use this method after authenticating
	 * a user with one of these roles.
	 *
	 * @param array $files - Array of file objects to be accessed, each contains 'Bucket' and 'Key'.
	 * @param $exipres - The time at which the URL should expire. This can be a Unix timestamp, a PHP DateTime object, or a string that can be evaluated by strtotime
	 * @return array - list of uploaded files
	 */
	public function grant_temporary_access($files = array(), $expires)
	{
		$sts = StsClient::factory(array(
		    'key'    => AWS_KEY,
		    'secret' => AWS_SECRET
		));

		$credentials = $sts->getSessionToken()->get('Credentials');
		$s3 = S3Client::factory(array(
		    'key'    => $credentials['AccessKeyId'],
		    'secret' => $credentials['SecretAccessKey'],
		    'token'  => $credentials['SessionToken']
		));

		try 
		{
			foreach($files as &$file)
			{
				$command = $s3->getCommand('GetObject', array(
			        'Bucket' => $file['bucket'],
			        'Key' => $file['name']
			    ));

			    $file['signedUrl'] = $command->createPresignedUrl($expires);
			}
		    return $files;

		} 
		catch (S3Exception $e) 
		{
		    return $e->getMessage() . "\n";
		}
	}
 
}
