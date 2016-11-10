<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Image_scraper extends CI_Controller
{

	static $PATTERN_GLOBAL_URL = '/^(https?:)?\/\//'; // "HTTP" or "HTTPS", but always "//"
	static $PATTERN_ABSOLUTE_PATH = '/^\/[^\/]/'; // begins with one and only one "/"
	static $PATTERN_PATH_DIR_END = '/\/[^\/]*$/'; // matches anything in a URL path after the last slash
	static $PATTERN_NON_URL_CHARS = '/[{}]/'; // non-URL-encoded characters that signal a template or incomplete URL
	static $PATTERN_NEGATED_DIR_PAIRS = '/([^\/]+\/([^\/]|(?R))*\.\.\/)/'; // matches negated directories (e.g. "styles/css/../../" in  "/styles/css/../../images/logo.png")
	static $MAX_BYTES_IN_DOCUMENT_REQUEST = 512000; // limit to 500kB
	static $CURLE_ABORTED_BY_CALLBACK_STRING = 'Callback aborted'; // curl_error returns this when php.net says to expect CURLE_ABORTED_BY_CALLBACK

	public $options = [
		'url'              => '',   // URL to start crawling from
		'depth_limit'      => 1,    // value of 0 crawls only the given page, 1 crawls that page's links, etc.
		'same_domain'      => true, // only follow HTML links on the same domain and subdomains
		'crawl_limit_html' => 10,   // limit the total HTML files crawled
		'crawl_limit_css'  => 10,   // limit the total stylesheets crawled
	];

	public $result = [
		'domain'         => null,
		'urls_crawled'   => [],
		'urls_checked'   => [],
		'urls_discarded' => [],
		'page_limit_met' => false,
		'time'           => 0,
		'error'          => [],
		'urls' => [
			'html'  => [],
			'css'   => [],
			'image' => [],
			'video' => [],
			'audio' => [],
			'other' => [],
		],
	];

	public $start_mtime = null;
	public $previous_mtime = null;

	function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->library('tank_auth');
	}

	public function index()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','image_scraper');
			redirect('login');
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin' or $role == 'creative')
		{
			$data['username'] = $username;
			$data['firstname'] = $this->tank_auth->get_firstname($data['username']);
			$data['lastname'] = $this->tank_auth->get_lastname($data['username']);
			$data['user_id'] = $this->tank_auth->get_user_id();
			$data['title'] = "Image Scraper";
			$this->load->view('ad_linker/header', $data);

			$this->load->view('vl_platform_2/ui_core/js_error_handling',$data);

			$this->load->view('image_scraper/index', $data);
		}
		else
		{
			redirect('director');
		}
	}

	public function crawl()
	{
		$image_scraper_options = [];
		$url = $this->input->get('url');
		$global_url_match = preg_match(self::$PATTERN_GLOBAL_URL, $url);
		if(!$global_url_match)
		{
			$url = 'http://' . $url; // add protocol to input of hostname only
		}
		$depth = $this->input->get('depth');
		if(!empty($url))
		{
			$image_scraper_options['url'] = $url;
			$image_scraper_options['depth_limit'] = (!empty($depth) ? 1 : 0);
		}

		$this->init_crawl($image_scraper_options);

		$response = $this->result;

		if(function_exists('json_encode'))
		{
			if(!$response)
			{
				$response = [
					'error' => 'empty response from Image Scraper'
				];
			}
			echo json_encode($response);
		}
		else
		{
			echo '{"error":"missing json_encode"}';
		}
	}

	private function init_crawl($options = array())
	{
		$this->timer();

		$this->options = array_merge($this->options, $options);
		if(empty($this->options['url']))
		{
			$this->result['error'][] = 'Please specify a URL to be scraped.';
		}
		else
		{
			$this->crawl_html_for_urls($this->options['url']);
		}

		$this->result['time'] = $this->timer();
	}

	/*
	 * Only HTML documents are crawled recursively
	 */
	private function crawl_html_for_urls($url, $depth = 0)
	{
		if(count($this->result['urls_crawled']) >= $this->options['crawl_limit_html'])
		{
			$this->result['page_limit_met'] = true;
			return;
		}

		$url_parts = parse_url($url);

		if($depth == 0)
		{
			$this->result['domain'] = $url_parts['host'];
		}
		else if($this->options['same_domain'])
		{
			// allow subdomains
			$subdomain_match_pattern = '/\.?' . $this->result['domain'] . '$/';
			if(!preg_match($subdomain_match_pattern, $url_parts['host']))
			{
				$this->mark_checked($url, false, 'host mismatch');
				return false;
			}
		}

		if($response = $this->request_document($url))
		{
			if(isset($response['error']))
			{
				$this->mark_checked($url, false, $response['error']);
				if($depth == 0)
				{
					$this->result['error'][] = "There was an error getting the URL you entered: {$response['error']}";
				}
				return false;
			}

			if(strpos($response['content_type'], 'text/html') !== 0)
			{
				$this->mark_checked($url, false, $response['content_type']);
				if($depth == 0)
				{
					$this->result['error'][] = "The URL you entered responded with an unsupported content type: {$response['content_type']}.";
				}
				return false;
			}
			if(isset($response['header_parsed']['Location']))
			{
				$this->mark_checked($url, false, 'redirect');
				if($new_url = $this->heal_url($response['header_parsed']['Location'], $url))
				{
					$url = $new_url;
					if($depth == 0)
					{
						// our first request has redirected: update for future comparison
						$url_parts = parse_url($url);
						$this->result['domain'] = $url_parts['host'];
					}
				}
				else
				{
					if($depth == 0)
					{
						$this->result['error'][] = "The URL you entered ended in a bad redirect: {$response['header_parsed']['Location']}.";
					}
					return false;
				}
			}
			$this->mark_checked($url, true);

			if(isset($response['body']) && $response['body'])
			{
				$this->scrape_linked_documents_from_html($url, $response['body']);
				if($depth < $this->options['depth_limit'])
				{
					foreach($this->result['urls']['html'] as $sub_url_data)
					{
						if(!in_array($sub_url_data['url'], $this->result['urls_checked']))
						{
							$this->crawl_html_for_urls($sub_url_data['url'], $depth + 1);
						}
					}
				}

				// put no limit on CSS requests
				foreach($this->result['urls']['css'] as $sub_url_data)
				{
					if(!in_array($sub_url_data['url'], $this->result['urls_checked']))
					{
						$this->scrape_linked_documents_from_css($sub_url_data['url']);
					}
				}
			}
		}
		else if($depth = 0)
		{
			$this->result['error'][] = 'First request got a bad response.';
		}
	}

	private function scrape_linked_documents_from_html($scrape_url, $markup)
	{
		$link_matches = [];

		preg_match_all("/(<(a|img|link))([^>]+)?(src|href)=['\"]([^'\"]+?)['\"]([^>]*)/i", $markup, $link_matches);
		//               1 2            3       4               5              6

		if($link_matches && count($link_matches[0]) > 0)
		{
			$tag_names    = $link_matches[2];
			$attrs_before = $link_matches[3];
			$urls         = $link_matches[5];
			$attrs_after  = $link_matches[6];

			foreach($urls as $index => $raw_url)
			{
				if(!$link_url = $this->heal_url($raw_url, $scrape_url))
				{
					continue;
				}

				$link_data = [
					'url' => $link_url,
					'page' => $scrape_url,
				];

				// guess at what kind of link to expect
				$url_list_name = null;
				switch($tag_names[$index])
				{
					case 'a':
						$url_list_name = 'html';
						break;
					case 'link':
						if(
							($attrs_before[$index] && strpos($attrs_before[$index], 'stylesheet') > -1)
							|| ($attrs_after[$index] && strpos($attrs_after[$index], 'stylesheet') > -1)
						)
						{
							$url_list_name = 'css';
						}
						else if(
							($attrs_before[$index] && strpos($attrs_before[$index], 'icon') > -1)
							|| ($attrs_after[$index] && strpos($attrs_after[$index], 'icon') > -1)
						)
						{
							$url_list_name = 'image';
						}
						break;
					case 'img':
						$url_list_name = 'image';
						break;
					default:
						$url_list_name = 'other';
				}

				if($url_list_name !== null)
				{
					$this->append_unique_url($url_list_name, $link_data);
				}
			}
		}
	}

	private function heal_url($url, $referrer_url = null, $discard_fragment = true)
	{
		$url_parts = parse_url($url);

		if($discard_fragment && array_key_exists('fragment', $url_parts))
		{
			unset($url_parts['fragment']);
			$url = $this->rebuild_parsed_url($url_parts);
		}

		$output_url = $url;

		// link is some other protocol, such as "javascript" or "data"
		$is_non_http_url = ($url_parts && array_key_exists('scheme', $url_parts) && ($url_parts['scheme'] !== 'http' && $url_parts['scheme'] !== 'https'));

		// parse_url doesn't complain about "{{template vars}}" in a URL, but I don't want them (I'm looking at you, hyundaiusa.com)
		$is_template_url = preg_match(self::$PATTERN_NON_URL_CHARS, $url) === 1;

		if($is_non_http_url || $is_template_url)
		{
			return false;
		}

		$is_global_url = preg_match(self::$PATTERN_GLOBAL_URL, $url) === 1;
		if(!$is_global_url)
		{
			if(empty($referrer_url))
			{
				return false;
			}

			$referrer_url_parts = parse_url($referrer_url);

			$is_absolute_path = preg_match(self::$PATTERN_ABSOLUTE_PATH, $url) === 1;
			if($is_absolute_path)
			{
				$referrer_host_parts_to_keep = ['scheme', 'host'];
				$referrer_host_url = $this->rebuild_parsed_url($referrer_url_parts, $referrer_host_parts_to_keep);

				$output_url = $referrer_host_url . $url;
			}
			else
			{
				$referrer_directory_parts_to_keep = ['scheme', 'host', 'path'];
				$referrer_directory_parts = $referrer_url_parts;
				if(array_key_exists('path', $referrer_url_parts))
				{
					$referrer_url_parts['path'] = preg_replace(self::$PATTERN_PATH_DIR_END, '', $referrer_url_parts['path']);
				}
				$referrer_directory = $this->rebuild_parsed_url($referrer_url_parts, $referrer_directory_parts_to_keep);

				$output_url = $referrer_directory . '/' . $url;
			}
		}

		// simplify URLs containing "../"
		if($simplified_url = preg_replace(self::$PATTERN_NEGATED_DIR_PAIRS, '', $output_url))
		{
			$output_url = $simplified_url;
		}

		return $output_url;
	}

	private function scrape_linked_documents_from_css($stylesheet_url)
	{
		$this->mark_checked($stylesheet_url);
		if($response = $this->request_document($stylesheet_url))
		{
			if(isset($response['error']))
			{
				$this->mark_checked($stylesheet_url, false, $response['error']);
				return false;
			}

			$background_url_matches = [];

			preg_match_all("/background.*url\(['\"]?([^'\")]+)/i", $response['body'], $background_url_matches);

			if($background_url_matches && count($background_url_matches[0]) > 0)
			{
				foreach($background_url_matches[1] as $raw_url)
				{
					if(!$image_url = $this->heal_url($raw_url, $stylesheet_url))
					{
						continue;
					}

					$image_data = [
						'url' => $image_url,
						'page' => $stylesheet_url,
					];
					$this->append_unique_url('image', $image_data);
				}
			}
		}
	}

	private function request_document($url)
	{
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_NOPROGRESS, false); // php.net says `false` is for debugging only...but I need it to trigger the CURLOPT_PROGRESSFUNCTION
		curl_setopt($c, CURLOPT_PROGRESSFUNCTION, ['Image_scraper', 'monitor_curl_progress']);

		$response = [];

		if($result = curl_exec($c))
		{
			$header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
			if(strlen($result) === $header_size)
			{
				$response['error'] = 'Response body is empty';
			}
			else
			{
				$response['header'] = substr($result, 0, $header_size);
				$response['content_type'] = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
				$response['body'] = substr($result, $header_size);
				$response['header_parsed'] = $this->parse_http_head($response['header']);
			}
		} else {
			$error = curl_error($c);
			if($error === self::$CURLE_ABORTED_BY_CALLBACK_STRING)
			{
				$response['error'] = 'document too large';
			}
			else
			{
				$response['error'] = $error . ' (HTTP status ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . ')';
			}
		}

		return $response;
	}

	private function append_unique_url($list_name, $data)
	{
		$list = &$this->result['urls'][$list_name];
		foreach($list as $comparison_value)
		{
			if($comparison_value['url'] == $data['url'])
			{
				return false;
			}
		}

		$list[] = $data;

		return true;
	}

	private function mark_checked($url, $count_as_crawled = false, $comment = null)
	{
		$this->result['urls_checked'][] = $url;

		if($count_as_crawled)
		{
			$this->result['urls_crawled'][] = $url;
		}
		else if($comment !== null)
		{
			$this->result['urls_discarded'][] = [$url, $comment];
		}
	}

	private function parse_http_head($header)
	{
		// FIXME: use only the last header in a series of redirects (i.e. don't keep old header lines)
		$header_array = [];
		foreach(explode("\r\n", $header) as $row) // key headers, and overwrite duplicates with later values
		{
			$row_array = explode(': ', $row);
			if(count($row_array) < 2)
			{
				continue;
			}
			$header_array[$row_array[0]] = $row_array[1];
		}
		return $header_array;
	}

	/*
	 * optional @param parts_to_keep (array) parse_url() result keys to include; null allows all keys to remain
	 *
	 * Intended to work like PECL http_build_url(), with the addition of the optional keys.
	 * http://php.net/manual/en/function.http-build-url.php
	 */
	private function rebuild_parsed_url($url_parts, $parts_to_keep = null)
	{
		if($parts_to_keep !== null)
		{
			foreach($url_parts as $key => $value)
			{
				if(!in_array($key, $parts_to_keep))
				{
					unset($url_parts[$key]);
				}
			}
		}

		$url = '';
		array_key_exists('scheme', $url_parts)   && $url .= $url_parts['scheme'] . ':';
		array_key_exists('host', $url_parts)     && $url .= '//' . $url_parts['host'];
		array_key_exists('path', $url_parts)     && $url .= $url_parts['path'];
		array_key_exists('query', $url_parts)    && $url .= '?' . $url_parts['query'];
		array_key_exists('fragment', $url_parts) && $url .= '#' . $url_parts['fragment'];

		return $url;
	}

	private function timer()
	{
		$now_mtime = microtime(true);
		if($this->start_mtime === null)
		{
			$this->start_mtime = $now_mtime;
			$this->previous_mtime = $now_mtime;
			return 0;
		}
		$lap_seconds = round(($now_mtime - $this->previous_mtime) * 1000) / 1000;
		$total_seconds = round(($now_mtime - $this->start_mtime) * 1000) / 1000;

		$this->previous_mtime = $now_mtime;

		$response = [
			'lap'   => $lap_seconds,
			'total' => $total_seconds,
		];

		return $response;
	}

	static function monitor_curl_progress($c, $target_bytes_down, $bytes_down, $target_bytes_up, $bytes_up)
	{
		if($target_bytes_down > self::$MAX_BYTES_IN_DOCUMENT_REQUEST)
		{
			// abort curl
			return 1;
		}
		return 0;
	}

}
