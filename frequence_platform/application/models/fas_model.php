<?php
class Fas_model extends CI_Model {
	public function __construct()
	{
		$this->load->database();
		$this->load->library('vl_aws_services');
		$this->load->model('cup_model');
		$this->load->helper('ad_server_type_helper');
	}
	public function push_fas_creatives($creative, $creative_size, $version_id, $is_bulk = FALSE, $landing_page = NULL)
	{
		$creative_size_split = explode("x",$creative_size);
		$creative_width = $creative_size_split[0];
		$creative_height = $creative_size_split[1];		
		$html_content = $this->cup_model->get_ad_html($version_id, $creative_size, true, k_ad_server_type::fas_id, false);
		$js_content = $this->cup_model->get_ad_html($version_id, $creative_size, true, k_ad_server_type::fas_id, true);
		$unique_id = uniqid();
		$html_file_name = 'html/'.$version_id.'_'.$creative_size.'_'.$unique_id.'.html';
		$js_file_name = 'scripts/'.$version_id.'_'.$creative_size.'_'.$unique_id.'.js';		
		$this->vl_aws_services->upload_file_stream($html_content, $html_file_name, S3_FREQUENCE_ADSERVER_BUCKET);
		$this->vl_aws_services->upload_file_stream($js_content, $js_file_name, S3_FREQUENCE_ADSERVER_BUCKET);	
		$landing_page = urlencode($landing_page);
		$html_file_path = S3_FREQUENCE_CLOUDFRONT_URL."/".$html_file_name."?fas_candu=".$landing_page."&fas_c=";
		$js_file_path = S3_FREQUENCE_CLOUDFRONT_URL."/".$js_file_name;
			
		$tag =	'<SCRIPT language="JavaScript1.1">'				
				. 'var fas_m=location.hostname;'
				. 'var fas_r="";'
				. 'if(typeof document.referrer != "undefined" && document.referrer != "")'
					. 'fas_r = document.referrer.split("/")[2];'
				. 'document.write(\''
					. '<IFRAME SRC="'.$html_file_path.'&fas_m=\'+fas_m+\'&fas_r=\'+fas_r+\'" WIDTH='.$creative_width.' HEIGHT='.$creative_height.' MARGINWIDTH=0 '
						. 'MARGINHEIGHT=0 HSPACE=0 VSPACE=0 FRAMEBORDER=0 SCROLLING=no BORDERCOLOR="#000000">'
							. '<SCR\'+\'IPT language="JavaScript1.1">'
								. 'var fas_c_for_js="";'
								. 'var fas_candu_for_js="'.$landing_page.'";'								
							. '</SCR\'+\'IPT>'							
							. '<SCR\'+\'IPT language="JavaScript1.1" SRC="'.$js_file_path.'"></SCR\'+\'IPT>'
					. '</IFRAME>\');'.
			'</SCRIPT>'. 
			'<NOSCRIPT>'
				. '<A HREF="'.$landing_page.'">'
					. '<IMG SRC="'.$creative['ssl_backup_image'].'" BORDER=0 WIDTH='.$creative_width.' HEIGHT='.$creative_height.' ALT="Advertisement">'
				. '</A>'. 
			'</NOSCRIPT>';
		
		$updated_success = $this->cup_model->update_creative_fas_tag(array(
					'ad_tag'=>$tag,
					'published_ad_server'=>k_ad_server_type::fas_id,
					'size'=>$creative_size,
					'adset_id'=>$version_id
					));
		if (!$updated_success)
		{
			$return_array['err_msg'] = "Failed to update VL creative table";
			return $return_array;
		}		
		
		$return_array['is_success'] = TRUE;
		$return_array['ad_tag'] = $tag;
		return $return_array;
	}
}