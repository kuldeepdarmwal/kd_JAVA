<?php

class Spec_ad_model extends CI_Model {
	private $m_crypt_cipher = MCRYPT_RIJNDAEL_128;
	private $m_crypt_key = 'h89Zdfy2lYik';
	private $m_crypt_mode = MCRYPT_MODE_CFB;
	private $m_crypt_initialization_value = 'fdy08Z0712nAnrtM';  // 16 block size for MCRYPT_RIJNDAEL_128 + MCRYPT_MODE_CFB

	public function __construct()
	{
		$this->load->database();
		$this->load->model('cup_model');
	}

	public function encode_ad_set_data_for_url($ad_set_version_id)
	{
		$encoding_version = 1;
		$data_array = array(
			'ver' => $encoding_version,
			'id' => $ad_set_version_id
		);
		$data_json = json_encode($data_array);
		$encrypted_data = mcrypt_encrypt(
			$this->m_crypt_cipher,
			$this->m_crypt_key,
			$data_json,
			$this->m_crypt_mode,
			$this->m_crypt_initialization_value 
		);
		$base64_encoded_data = base64_encode($encrypted_data);
		$url_encoded_data = urlencode($base64_encoded_data);
		$path_encoded_data = str_replace('%', '@', $url_encoded_data);
		return $path_encoded_data;
	}

	public function decode_ad_set_data_from_url($path_encoded_ad_set_data)
	{
		$url_encoded_data = str_replace('@', '%', $path_encoded_ad_set_data);
		$base64_encoded_data = urldecode($url_encoded_data);
		$encrypted_ad_set_data = base64_decode($base64_encoded_data);
		$decrypted_data_json = mcrypt_decrypt(
			$this->m_crypt_cipher,
			$this->m_crypt_key,
			$encrypted_ad_set_data,
			$this->m_crypt_mode,
			$this->m_crypt_initialization_value 
		);

		 $data_array = json_decode($decrypted_data_json, true);
		 return $data_array;
	}

	public function encode_ad_data_for_url($ad_set_version_id, $creative_size)
	{
	}

	public function decode_ad_data_for_url($encoded_ad_data)
	{
	}

	private function get_spec_ad_url_config_data($cup_version_id)
	{
		$url_config = false;

		$sql = "
			SELECT
				wpd.cname AS cname,
				cv.builder_version AS builder_version
			FROM
				cup_versions AS cv
				JOIN cup_adsets AS cas ON (cv.adset_id = cas.id)
				JOIN Campaigns AS camp ON (cas.campaign_id = camp.id)
				JOIN Advertisers AS adv ON (camp.business_id = adv.id)
				JOIN users AS us ON (adv.sales_person = us.id)
				JOIN wl_partner_details AS wpd ON (us.partner_id = wpd.id)
			WHERE
				cv.id = ?
		";
		$bindings = array($cup_version_id);
		$response = $this->db->query($sql, $bindings);
		if($response->num_rows() > 0)
		{
			$url_config = $response->row();
		}

		return $url_config;
	}

	private function get_ad_builder_version($cup_version_id)
	{
		$builder_version = false;

		$sql = "
			SELECT
				cv.builder_version AS builder_version
			FROM
				cup_versions AS cv
			WHERE
				cv.id = ?
		";
		$bindings = array($cup_version_id);
		$response = $this->db->query($sql, $bindings);
		if($response->num_rows() > 0)
		{
			$row = $response->row();
			$builder_version = $row->builder_version;
		}

		return $builder_version;
	}

	public function get_spec_ad_url($cup_version_id)
	{
		$url = false;

		$builder_version = $this->get_ad_builder_version($cup_version_id);
		if(
			$builder_version !== false && 
			$builder_version >= 500 && 
			$builder_version < 600 
			//&& !empty($url_config->cname) // TODO: enable after making relationship between AdSet and partner
		)
		{
			$encoded_url_data = $this->encode_ad_set_data_for_url($cup_version_id);
			if($encoded_url_data !== false)
			{
				/* TODO: enable after making relationship between AdSet and partner
				$domain = $url_config->cname.'.brandcdn.com';
				if(ENVIRONMENT != 'production' && ENVIRONMENT != 'staging')
				{
					$domain = $_SERVER['HTTP_HOST'];
				}
				*/
				$domain = $_SERVER['HTTP_HOST'];

				$url = 'http://'.$domain.'/sample_ad/'.$encoded_url_data;
			}
		}

		return $url;
	}
}

?>
