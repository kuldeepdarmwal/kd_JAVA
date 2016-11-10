<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require FCPATH.'/vendor/autoload.php';

use Knp\Snappy\Pdf;

/**
 * Methods for creating proposals.
 *
 * @package proposal_generator
 */
class proposal_generator
{

	function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->model('proposals_model');
		$this->ci->load->library('vl_aws_services');
	}

	/**
	 * Save the HTML for a proposal for later PDF generation.
	 *
	 * @param string $html - html string
	 * @param int $proposal_id - ID of the generated proposal
	 * @param int $base_template_id - the id of the template the proposal is generated from. used to get orientation
	 */
	public function save_proposal_html($html, $proposal_id, $template_id)
	{
		$this->ci->proposals_model->save_proposal_html($proposal_id, $html, $template_id);
	}

    /**
     * Render a template with proposal data.
     *
     * @param int $proposal_id - ID of the proposal
     * @param int $template_id - The id of the template to be rendered
     * @return string - Rendered HTML
     */
    public function render_html($proposal_id, $template_id)
    {
        if ($template_id)
        {
            $template = $this->ci->proposals_model->get_template_by_template_id($template_id);
            try
            {
                $proposal_data = $this->ci->proposals_model->get_proposal_data_for_template($proposal_id, $template_id);
            }
            catch (Exception $e)
            {
                return $e->getMessage();
            }
            $m = new Mustache_Engine(array(
                'pragmas' => [Mustache_Engine::PRAGMA_FILTERS],
                'helpers' => array(
                    'count' => function(array $value)
                    {
                        return count($value);
                    },
                    'number_format' => function($value)
                    {
                        return number_format($value);
                    },
                    'list_string' => function(array $value)
                    {
                        if (count($value) > 1)
                        {
                            $value[count($value) - 1] = "and " . $value[count($value) - 1];
                            $separator = count($value) > 2 ? ', ' : ' ';
                            $list_string = implode($separator, $value);
                        }
                        else if (count($value) == 1)
                        {
                            $list_string = $value[0];
                        }
                        else
                        {
                            $list_string = '';
                        }
                        return array(
                            'list' => $list_string,
                            'plural' => count($value) != 1
                        );
                    },
                    'percent_format' => function($value)
                    {
                        $int = intval($value);
                        $int *= 100;
                        $int = round($int);
                        if (empty($value))
                        {
                            return '-';
                        }
                        return round($value * 100) . '%';
                    },
                    'exists' => function($value)
                    {
                        return !empty($value);
                    }
                )
            ));
            $template = $this->ci->vl_aws_services->get_file_stream($template['s3_bucket'].'/'.$template['filename']);
            try
            {
                return $m->render($template, $proposal_data);
            }
            catch (Exception $e)
            {
                return $e->getMessage();
            }
        }
        else {
            $template = $this->ci->proposals_model->get_template_by_proposal_id($proposal_id);
            return $template ? $template['html'] : 'Template not found!';
        }
    }


	/**
	 * Generate a PDF from an HTML string.
	 *
	 * @param string $proposal_id - ID of the proposal being generated
	 * @return string - PDF string
	 */
	public function generate_pdf($proposal_id)
	{
		$template_id = $this->ci->proposals_model->get_template_id_by_proposal_id($proposal_id);

    	// TODO: replace with actual template file
    	$html = '
			<!DOCTYPE html>
			<html>
			  <head>
			    <meta charset="utf-8">
			    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
			    <link rel="stylesheet" href="https://s3.amazonaws.com/brandcdn-assets/partners/frequence/proposal_pdf.css">';

		$includes = $this->ci->proposals_model->get_proposal_template_includes($template_id);

		foreach($includes as $include)
		{
			switch($include['file_type'])
			{
				case 'css':
					$html .= "<link rel=\"stylesheet\" href=\"{$include['file_url']}\"/>";
					break;


				case 'js':
					$html .= "<script type=\"text.javascript\" src=\"{$include['file_url']}\"></script>";
					break;
			}
		}

		$html .= '
			  </head>
			  <body class="landscape">';

		$pages = $this->ci->proposals_model->get_proposal_pages($proposal_id);

		foreach($pages as $page)
		{
			$html .= $page['template'];
		}

		$html .= '
			  </body></html>
    	';
    	
    	$data = $this->ci->proposals_model->get_proposal_data_for_template($proposal_id, $template_id);

    	$html = $this->render_html($html, $data);


		$pdf = new Pdf('wkhtmltopdf');

		$pdf->setOption('orientation', 'landscape');
		$pdf->setOption('margin-top', '0px');
		$pdf->setOption('margin-right', '0px');
		$pdf->setOption('margin-left', '0px');
		$pdf->setOption('margin-bottom', '0px');
		$pdf->setOption('footer-right', '[page]');
		$pdf->setOption('footer-font-size', 10);
		$pdf->setOption('print-media-type', true);
		$pdf->setOption('lowquality', true);

		$contents = $pdf->getOutputFromHtml($html);

		$this->ci->vl_aws_services->upload_file_stream($contents, 'pdf/'.$proposal_id.'.pdf', S3_PROPOSAL_TEMPLATE_BUCKET);
		$this->ci->proposals_model->save_proposal_pdf_location($proposal_id, S3_PROPOSAL_TEMPLATE_BUCKET.'/pdf/'.$proposal_id.'.pdf');

		return $this->get_proposal_pdf($proposal_id);
	}

	/**
	 * Generate a PDF from a saved proposal template.
	 *
	 * @param string $proposal_id - ID of the proposal being generated
	 * @param string $version (Optional) - Version of the PDF being returned
	 * @return string - PDF string
	 */
	public function get_proposal_pdf($proposal_id, $version_id = null)
	{
		return $this->ci->vl_aws_services->get_file(S3_PROPOSAL_TEMPLATE_BUCKET, '/pdf/'.$proposal_id.'.pdf', $version_id);
	}

	/**
	 * Generate a PDF from a saved proposal template.
	 *
	 * @param string $proposal_id - ID of the proposal being generated
	 * @param string $version (Optional) - Version of the PDF being returned
	 * @return string - PDF string
	 */
	public function get_proposal_pdf_name($proposal_id, $advertiser = null, $date = null)
	{
		if (empty($advertiser) || empty($date))
		{
			$proposal_name_details = $this->ci->proposals_model->get_proposal_name_details($proposal_id);
			$advertiser = $advertiser ?: $proposal_name_details['advertiser'];
			$date = $date ?: $proposal_name_details['date'];
		}

		$pdf_name = 'rfp-';

		if ($advertiser)
		{
			$pdf_name .= $advertiser;
		}

		if ($date)
		{
			$proposal_date = new DateTime($date);
			$pdf_name .= " " . $proposal_date->format("Y-m-d-H-i");
		}

		$pdf_name = str_replace([" ", ":"], ["-"], $pdf_name);
		return preg_replace('/[^A-Za-z0-9\-]/', '', $pdf_name) . ".pdf";
	}

	/**
	 * Automatically generate a proposal after RFP submit.
	 *
	 * @param string $proposal_id - ID of the proposal being generated
	 * @return string - signed URL for proposal PDF
	 */
	public function auto_generate_proposal($proposal_id, $partner_id)
	{
		$template = $this->ci->proposals_model->get_template_by_proposal_strategy($proposal_id) ?: $this->ci->proposals_model->get_default_template($partner_id, $proposal_id);
		$html = $this->render_html($proposal_id, $template['id']);
		$this->save_proposal_html($html, $proposal_id, $template['id']);
		$this->generate_pdf($proposal_id);
		$version_id = $this->ci->vl_aws_services->get_current_file_version(S3_PROPOSAL_TEMPLATE_BUCKET, 'pdf/'.$proposal_id);
		$this->ci->proposals_model->save_proposal_pdf_location($proposal_id, S3_PROPOSAL_TEMPLATE_BUCKET.'/pdf/'.$proposal_id.'.pdf');

		$pdf_title = $this->get_proposal_pdf_name($proposal_id);
		$protocol = ENABLE_HOOKS ? 'https://' : 'http://';

		return $protocol . BASE_URL . '/proposals/'.$proposal_id.'/download/'. $pdf_title .'?versionId='.$version_id;
	}

	/*
	 * New Builder functions
	 */

	/**
	 * Render a proposal template by page.
	 *
	 * @param int $proposal_id - ID of the proposal being generated
	 * @param int $proposal_template_id (Optional) - Version of the PDF being returned
	 * @return object - rendered proposal object, including array of pages
	 */

	public function render_proposal_html($proposal_id, $proposal_template_id)
	{
		
	}

}
