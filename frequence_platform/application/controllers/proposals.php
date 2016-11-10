<?php defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . '/vendor/autoload.php';

use Knp\Snappy\Pdf;

class Proposals extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('proposals_model');
        $this->load->model('mpq_v2_model');
		$this->load->model('siterankpoc_model');
		$this->load->model('proposal_gen_model');
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->helper('html_dom');
        $this->load->helper('vl_ajax_helper');
        $this->load->library('Tank_auth');
        $this->load->library('vl_aws_services');
        $this->load->library('proposal_generator');
        $this->load->library('csv');

        $this->load->library('session');
        $this->load->library('tank_auth');
        $this->load->library('vl_platform');
    }

    public function build($proposal_id = null)
    {
        $this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposal_builder', uri_string());

        if (is_null($proposal_id)) {
            show_404(); // exit
        }

        $data = array('proposal_id' => $proposal_id);

        // TODO v2: Check for the partner that owns the proposal
        $data['templates'] = $this->proposals_model->get_templates();
        $data['saved_template'] = $this->proposals_model->get_template_by_proposal_id($proposal_id);

        $data['pdf_title'] = $this->proposal_generator->get_proposal_pdf_name($proposal_id);

        $this->load->view('proposals/builder_body.php', $data);
    }

    public function html($proposal_id)
    {
        $this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposal_builder', uri_string());

        $template_id = $this->input->get('template', true); // Optional
        $html = $this->proposal_generator->render_html($proposal_id, $template_id);
        $this->output->set_output($html);
    }

    public function save_html()
    {
        $this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposal_builder', uri_string());

        $html = $this->input->post('html');
        $proposal_id = $this->input->post('proposal_id', true);
        $base_template = $this->input->post('base_template', true);

        if ($html && $proposal_id && $base_template) {
            try {
                $proposal_html = $this->proposal_generator->save_proposal_html($html, $proposal_id, $base_template);
                $this->output->set_status_header(200);
                $this->output->set_output(json_encode(array('html' => $proposal_html)));
            } catch (Exception $e) {
                $this->output->set_status_header($e->getCode());
                $this->output->set_output($e->getMessage());
            }
        } else {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(array('html' => 'No proposal HTML supplied in request.')));
        }
    }

    public function pdf($pdf_name, $proposal_id = null)
    {
        $pdf = $this->proposal_generator->generate_pdf($proposal_id);
        header('Content-Type: ' . $pdf['ContentType']);
        echo $pdf['Body'];
    }

    public function download($pdf_name, $proposal_id)
    {
        $version_id = $this->input->get('versionId', true);
        $pdf = $this->proposal_generator->get_proposal_pdf($proposal_id, $version_id);
        header('Content-Type: ' . $pdf['ContentType']);
        echo $pdf['Body'];
    }

    public function upload_template()
    {
        $this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposal_builder', uri_string());

        $template_data = array();
        $template_data['is_landscape'] = $this->input->post('is_landscape', true) == 'on';

        if ($_FILES['template']['type'] !== 'text/html') {
            $this->output->set_status_header(415);
            $this->output->set_output("File must be of type HTML");
        } else {
            $file_object = array(
                'name' => 'templates/' . url_title(strtolower($_FILES['template']['name'])),
                'tmp' => $_FILES['template']['tmp_name'],
                'size' => $_FILES['template']['size'],
                'type' => $_FILES['template']['type']
            );

            try {
                $upload = $this->vl_aws_services->upload_file($file_object, S3_PROPOSAL_TEMPLATE_BUCKET);
                $template_data['s3_bucket'] = $upload['data']['Bucket'];
                $template_data['filename'] = $upload['data']['Key'];

                $template_data['id'] = $this->proposals_model->save_template($template_data);
                $this->output->set_output(json_encode($template_data));
            } catch (Exception $e) {
                $this->output->set_status_header($e->getCode());
                $this->output->set_output($e->getMessage());
            }
        }
    }

    public function submitted_rfps()
    {
        $errors = array();
        $data = array();
        $is_success = true;
        $is_logged_in = false;

        $active_feature_button_id = 'proposals';

        $redirect_string = $this->vl_platform->get_access_permission_redirect('proposals');
        if ($redirect_string == 'login') {
            $this->session->set_userdata('referer', '/proposals');
            redirect($redirect_string);
        } else if ($redirect_string !== '') {
            redirect($redirect_string);
        }

        $io_redirect_string = $this->vl_platform->get_access_permission_redirect('io');
        if ($io_redirect_string === '') {
            $data['io_permitted'] = true;
        } else {
            $data['io_permitted'] = false;
        }

        $user_id = $this->tank_auth->get_user_id();
        $username = $this->tank_auth->get_username();
        $role = $this->tank_auth->get_role($username);
        $data['user_role'] = $role;
        $is_super = $this->tank_auth->get_isGroupSuper($username);

        $data['title'] = 'Submitted Proposals';

        $data['rfps'] = $this->proposals_model->get_submitted_rfps($user_id, $role, $is_super);

        if ($data['rfps'] !== false) {
            foreach ($data['rfps'] as &$rfp) {
                $rfp['creation_time'] = date('m/d/Y H:i:s', strtotime($rfp['creation_time']));

                $rfp['pdf_title'] = $this->proposal_generator->get_proposal_pdf_name($rfp["proposal_id"], $rfp["advertiser_name"], $rfp["creation_time"]);
                $rfp['is_demo_login'] = $this->session->userdata('is_demo_partner');
                if ($rfp['is_demo'] == 1) {
                    $rfp['demo'] = 'demo';
                } else {
                    $rfp['demo'] = 'nodemo';
                }
            }
        }

        $this->vl_platform->show_views(
            $this,
            $data,
            $active_feature_button_id,
            'rfp/proposals_view_html.php',
            'rfp/proposals_view_header.php',
            NULL,
            'rfp/proposals_view_footer.php',
            NULL
        );
    }

    public function get_budget_options_for_create_io()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $return_array = array('is_success' => true, 'errors' => "", 'data' => array());
            $allowed_roles = array('sales', 'admin', 'ops');
            $response = vl_verify_ajax_call($allowed_roles);

            if ($response['is_success']) {
                $mpq_id = $this->input->post('mpq_id');
                if ($mpq_id !== false) {
                    $submitted_product_result = $this->mpq_v2_model->get_submitted_products_by_mpq_id($mpq_id);
                    $options_result = $this->mpq_v2_model->get_mpq_options_by_mpq_id($mpq_id);
                    if ($submitted_product_result !== false and !empty($options_result)) {
                        $product_ids = array();
                        foreach ($submitted_product_result as $submitted_product) {
                            if (!in_array($submitted_product['product_id'], $product_ids)) {
                                $product_ids[] = $submitted_product['product_id'];
                            }
                        }
                        $products_result = $this->mpq_v2_model->get_products_by_product_id_array($product_ids);
                        if ($products_result !== false) {
                            $budget_array = array();

                            $tv_zones = $this->mpq_v2_model->get_rfp_tv_zones_by_mpq_id($mpq_id);
                            if (!empty($tv_zones)) {
                                $zone_ids = array();
                                foreach ($tv_zones as $tv_zone) {
                                    $zone_ids[] = $tv_zone['id'];
                                }
                            }

                            foreach ($options_result as $option) {
                                $budget_array[$option['option_id']] = $option;
                                foreach ($products_result as $product) {
                                    $product_definition = json_decode($product['definition'], true);
                                    $product_array = array('id' => $product['id']);
                                    $product_name = "";
                                    if ($product_definition['first_name'] !== false) {
                                        $product_name .= $product_definition['first_name'] . " ";
                                    }
                                    $product_name .= $product_definition['last_name'];
                                    $product_array['name'] = $product_name;
                                    $product_array['after_discount'] = isset($product_definition['after_discount']) ? $product_definition['after_discount'] : false;
                                    $inventory_value = 0;
                                    $static_unit_value = 0;
                                    foreach ($submitted_product_result as $submitted_product) {
                                        if ($product['id'] == $submitted_product['product_id'] && $submitted_product['option_id'] == $option['option_id']) {
                                            $unit_multiplier = 1000;
                                            if (array_key_exists('options', $product_definition) && array_key_exists('unit_multiplier', $product_definition['options'][$option['option_id']])) {
                                                $unit_multiplier = $product_definition['options'][$option['option_id']]['unit_multiplier'];
                                            }
                                            $product_value = json_decode($submitted_product['submitted_values'], true);
                                            if ($product['product_type'] == "cost_per_unit") {
                                                if (array_key_exists('geofence_unit', $product_value)) {
                                                    $cost = $product_value['geofence_unit'] * $product_value['geofence_cpm'] / $unit_multiplier;
                                                    $cost += ($product_value['unit'] - $product_value['geofence_unit']) * $product_value['cpm'] / $unit_multiplier;
                                                } else {
                                                    $cost = $product_value['unit'] * $product_value['cpm'] / $unit_multiplier;
                                                }
                                                if (isset($product_array['cost'])) {
                                                    $product_array['cost'] += $cost;
                                                } else {
                                                    $product_array['cost'] = $cost;
                                                }

                                            } else if ($product['product_type'] == "cost_per_inventory_unit") {
                                                if (isset($product_array['cost'])) {
                                                    $product_array['cost'] += $product_value['unit'] * $product_value['cpm'] / 1000;
                                                } else {
                                                    $product_array['cost'] = $product_value['unit'] * $product_value['cpm'] / 1000;
                                                    $inventory_value = $product_value['inventory'];
                                                }
                                            } else if ($product['product_type'] == "cost_per_discrete_unit") {
                                                if (isset($product_array['cost'])) {
                                                    $product_array['cost'] += $product_value['unit'] * $product_value['cpc'];
                                                } else {
                                                    $product_array['cost'] = $product_value['unit'] * $product_value['cpc'];
                                                }
                                            } else if ($product['product_type'] == "input_box") {
                                                if (isset($product_array['cost'])) {
                                                    $product_array['cost'] += $product_value['unit'];
                                                } else {
                                                    $product_array['cost'] = $product_value['unit'];
                                                }
                                            } else if ($product['product_type'] == "cost_per_static_unit") {
                                                if (isset($product_array['cost'])) {
                                                    $product_array['cost'] += $product_value['price'];
                                                } else {
                                                    $product_array['cost'] = $product_value['price'];
                                                    $static_unit_value = $product_value['content'];
                                                }
                                            } else if ($product['product_type'] == "cost_per_tv_package") {
                                                $tv_zone_packages = $product_value;
                                            } else if ($product['product_type'] == "tv_scx_upload") {
                                                $product_array['cost'] = $product_value['price'];
                                            } else if ($product['product_type'] == "cost_per_sem_unit") {
                                                if (isset($product_array['cost'])) {
                                                    $product_array['cost'] += $product_value['unit'];
                                                } else {
                                                    $product_array['cost'] = $product_value['unit'];
                                                }
                                            }
                                        }
                                    }
                                    if ($product['product_type'] == 'cost_per_inventory_unit') {
                                        $product_array['cost'] += $inventory_value;
                                    } else if ($product['product_type'] == 'cost_per_static_unit') {
                                        $product_array['cost'] *= $static_unit_value;
                                    } else if ($product['product_type'] == 'cost_per_tv_package') {
                                        if ($tv_zone_packages['unit'] === "custom") {
                                            $product_array['cost'] = $tv_zone_packages['price'];
                                        } else {
                                            $tv_zone_pricing = $this->mpq->get_pricing_by_zones_and_packs_for_tv($zone_ids, array($tv_zone_packages['unit']));
                                            $product_array['cost'] = $tv_zone_pricing[0]['price']; // normally we look for the zone pack we need, but in this case there's only one
                                        }
                                    }
                                    $product_array['cost'] = round($product_array['cost']);
                                    $budget_array[$option['option_id']]['products'][] = $product_array;
                                }

                            }
                            $return_array['data'] = $budget_array;
                        } else {
                            $return_array['is_success'] = false;
                            $return_array['errors'] = "Error 826500: error getting budget data";
                        }
                    } else {
                        $return_array['is_success'] = false;
                        $return_array['errors'] = "Error 825500: error getting budget data";
                    }
                } else {
                    $return_array['is_success'] = false;
                    $return_array['errors'] = "Error 821400: invalid request parameters";
                }
            } else {
                $return_array['is_success'] = false;
                $return_array['errors'] = "Error 820403: User logged out or not permitted";
            }
            echo json_encode($return_array);
        } else {
            show_404();
        }
    }

    public function download_csv()
    {
        if ($this->tank_auth->is_logged_in()) {
            $this->csv->download_posted_inline_csv_data();
        } else {
            echo 'not logged in ';
        }
    }

    public function populate_grand_total_amounts($proposal_id_override = false, $update_existing = false)
    {
        if ($proposal_id_override !== false) {
            $proposal_ids = array($proposal_id_override);
        } else {
            $proposal_ids = $this->proposals_model->get_list_of_proposal_ids_from_visible_rfps($update_existing);
        }
        $echo_count = 0;
        $done_count = 0;
        $total_count = count($proposal_ids);
        echo "updating_proposals... " . $done_count . "/" . $total_count . "\n";
        foreach ($proposal_ids as $proposal_id) {
            $echo_count++;
            $done_count++;
            $proposal_data = $this->proposals_model->get_proposal_by_id($proposal_id);
            $mpq_id = $proposal_data['products'][0]['mpq_id'];
            foreach ($proposal_data['options'] as $key => $option) {
                $this->proposals_model->update_mpq_options_with_grand_total($mpq_id, $key, $option['grand_total']);
            }
            if ($echo_count >= 50) {
                echo "updating proposals... " . $done_count . "/" . $total_count . "\n";
                $echo_count = 0;
            }
        }
    }

    /*
 * New Builder functions
 */

    public function builder($unique_display_id = false)
    {
        if ($unique_display_id === false) {
            redirect('/proposals');
        }

        // TODO: add user / role verification
        $data = array();

        $data['proposal_id'] = $this->proposals_model->get_proposal_id_by_unique_display_id($unique_display_id);

        if (empty($data['proposal_id'])) {
            show_404();
        }

        $data['title'] = 'Proposal Builder';

        $this->vl_platform->show_views(
            $this,
            $data,
            'proposals',
            'proposals/builder/body.php',
            'proposals/builder/header.php',
            NULL,
            'proposals/builder/footer.php',
            NULL
        );
    }

    public function proposal_templates($unique_display_id)
    {
        $proposal_id = $this->proposals_model->get_proposal_id_by_unique_display_id($unique_display_id);
        $data = array();
        $data['canvas'] = $this->pages($proposal_id);
        $canvas_ids = array_column($data['canvas'], 'id');
        $data['library'] = $this->page_templates($proposal_id, $canvas_ids);
		$data['categories'] = $this->get_categories_by_library_data($data['library'], $canvas_ids);
        $data['proposalData'] = $this->data($proposal_id);
        $data['featurePermissions'] = $this->vl_platform -> get_list_of_user_permission_html_ids_for_user();
        $this->output->set_output(json_encode($data));
        $this->output->set_status_header(200);
    }

    public function pages($proposal_id)
    {
        $pages = $this->proposals_model->get_proposal_pages($proposal_id);
        if (empty($pages)) {
            $this->proposals_model->populate_proposal_pages($proposal_id);
            $pages = $this->proposals_model->get_proposal_pages($proposal_id);
        }
        return $pages;
    }

    public function page_templates($proposal_id, $canvas_ids = array())
    {
        $pages = $this->proposals_model->get_proposal_page_templates($proposal_id, $canvas_ids);
        return $pages;
    }

    public function page($unique_display_id)
    {
        $proposal_id = $this->proposals_model->get_proposal_id_by_unique_display_id($unique_display_id);
        $template_id = $this->proposals_model->get_template_id_by_proposal_id($proposal_id);
        $data['includes'] = $this->proposals_model->get_proposal_template_includes($template_id);
        $this->load->view('proposals/page_template', $data);
    }

    public function render_html($html, $proposal_data)
    {
        $m = new Mustache_Engine(array(
            'pragmas' => [Mustache_Engine::PRAGMA_FILTERS],
            'helpers' => array(
                'count' => function (array $value) {
                    return count($value);
                },
                'number_format' => function ($value) {
                    return number_format($value);
                },
                'list_string' => function (array $value) {
                    if (count($value) > 1) {
                        $value[count($value) - 1] = "and " . $value[count($value) - 1];
                        $separator = count($value) > 2 ? ', ' : ' ';
                        $list_string = implode($separator, $value);
                    } else if (count($value) == 1) {
                        $list_string = $value[0];
                    } else {
                        $list_string = '';
                    }

                    return array(
                        'list' => $list_string,
                        'plural' => count($value) != 1
                    );
                },
                'percent_format' => function ($value) {
                    $int = intval($value);
                    $int *= 100;
                    $int = round($int);
                    if (empty($value)) {
                        return '-';
                    }
                    return round($value * 100) . '%';
                },
                'exists' => function ($value) {
                    return !empty($value);
                }
            )
        ));

        try {
            return $m->render($html, $proposal_data);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function data($proposal_id = null)
    {
        $template_id = $this->proposals_model->get_template_id_by_proposal_id($proposal_id);
        $data = $this->proposals_model->get_proposal_data_for_template($proposal_id, $template_id);
        $data['includes'] = $this->proposals_model->get_proposal_template_includes($template_id);
        $data['template'] = $this->load->view('proposals/page_template', $data, true);
        return $data;
    }

    public function add_page()
    {
        $proposal_id = $this->input->post('proposal_id');
        $weight = $this->input->post('weight');
        $template_page_id = $this->input->post('template_id');

        $response = $this->proposals_model->add_proposal_page($proposal_id, $weight, $template_page_id);
        $this->output->set_output(json_encode($response));
        $this->output->set_status_header(200);
    }

    public function remove_page()
    {
        $page_id = $this->input->post('page_id');
        $this->proposals_model->remove_proposal_page($page_id);
        $this->output->set_status_header(200);
    }

    public function save_pages()
    {
        $data = array();
        $template_page_ids = $this->input->post('template_page_ids');
        $unique_display_id = $this->input->post('unique_display_id');
        $mpq_id = $this->input->post('mpq_id');
		$proposal_status = $this->input->post('status');
        if ($template_page_ids !== false && $unique_display_id !== false && $proposal_status !== false) {
            $proposal_id = $this->proposals_model->get_proposal_id_by_unique_display_id($unique_display_id);
            $response = $this->proposals_model->save_all_proposal_pages($proposal_id, $template_page_ids);
            $data['isSuccess'] = $response;
			$proposal_status['is_builder_cleared'] = true;
			if($mpq_id !== false)
			{
				$set_completion_status_response = $this->proposals_model->set_proposal_completion_status($mpq_id, $proposal_status);
			}
            if ($response) {
                $this->output->set_status_header(200);
                $this->output->set_output(json_encode($data));
                return;
            }
        }
        $this->output->set_status_header(400);
    }

    public function get_flights_details()
	{
	    $campaignId = $this->input->post('campaign_id');
	    $result = $this->proposals_model->get_timeseries_data_for_flight_table($campaignId);
	    echo json_encode($result);
	}

    public function delete_proposal_pages()
    {
        $unique_display_id = $this->input->post('unique_display_id');
        if ($unique_display_id !== false) {
            $proposal_id = $this->proposals_model->get_proposal_id_by_unique_display_id($unique_display_id);
            $response = $this->proposals_model->delete_proposal_pages($proposal_id);
            if ($response) {
                $this->output->set_status_header(200);
                return;
            }
        }
        $this->output->set_status_header(400);
    }

    public function get_geo_snapshots($unique_display_id)
    {
        $data = array();
        if ($unique_display_id !== false) {
            $proposal_id = $this->proposals_model->get_proposal_id_by_unique_display_id($unique_display_id);
            if ($proposal_id !== false) {
                $data['geos'] = $this->proposals_model->get_geo_snapshots_data($proposal_id);
                $data['rooftops'] = $this->proposals_model->get_rooftop_snapshots_data($proposal_id);
                $this->output->set_output(json_encode($data));
                $this->output->set_status_header(200);
                return;
            }
        }
        $this->output->set_output(json_encode($data));
        $this->output->set_status_header(400);
    }
	
	private function get_categories_by_library_data($library_data, $canvas_ids)
	{
		$categories = $this->proposals_model->get_categories_by_template_page_ids($canvas_ids);
		$categories[] = array(
			'id' => "-2",
			'name' => 'Default',
			'weight' => "999999999"
			);
		foreach($library_data as $page)
		{
			$exists = false;
			foreach($categories as $category)
			{
				if($page['category_id'] == $category['id'])
				{
					$exists = true;
				}
			}
			if(!$exists)
			{
				$categories[] = array(
					'id' => $page['category_id'],
					'name' => $page['category_name'],
					'weight' => $page['category_weight']
					);
			}
		}
		usort($categories, function($a, $b) {
				return $a['weight'] - $b['weight'];
			});
		return $categories;
	}
	public function render_pages($unique_display_id)
	{
		if(empty($unique_display_id))
		{
			show_404();
		}
	    $proposal_id = $this->proposals_model->get_proposal_id_by_unique_display_id($unique_display_id);
		if($proposal_id)
		{
			$proposal_template = $this->proposals_model->get_proposal_templates_by_strategy($proposal_id);
			$pages = $this->proposals_model->get_proposal_pages($proposal_id);
			$template_page_ids = array_column($pages, 'id');
			$template_includes = $this->proposals_model->get_proposal_template_includes($proposal_template[0]->id);
			$template_id = $this->proposals_model->get_template_id_by_proposal_id($proposal_id);
			$css_includes = array();
			$js_includes = array();
			$custom_js = "";
			$custom_css = "";
			foreach($template_includes as $include)
			{
				if($include['file_type'] == 'css')
				{
					if($include['partner_name'] == 'custom')
					{
						$temp_css_file = file_get_contents($include['file_url']);
						if($temp_css_file !== false)
						{
							$custom_css .= $temp_css_file;
						}
					}
					else
					{
						$css_includes[] = $include['file_url'];
					}
				}
				else if($include['file_type'] == 'js')
				{
					if($include['partner_name'] == 'custom')
					{
						$temp_js_file = file_get_contents($include['file_url']);
						if($temp_js_file !== false)
						{
							$custom_js .= $temp_js_file;
						}
					}
					else
					{
						$js_includes[] = $include['file_url'];
					}
				}
			}
			$data = array(
				'pages' => $pages,
				'css_includes' => $css_includes,
				'js_includes' => $js_includes,
				'custom_js' => $custom_js,
				'custom_css' => $custom_css
				);
			$rendered_html = $this->load->view('proposals/render_pages', $data, true);
			$proposal_data = $this->proposals_model->get_proposal_data_for_template($proposal_id, $template_id);
			$html = $this->render_html($rendered_html, $proposal_data);
			echo $html;
		}
	}

	public function generate_builder_pdf($unique_display_id)
	{
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
		$pdf->setOption('debug-javascript', true);
		$contents = $pdf->getOutput(base_url("proposals/render_pages/".$unique_display_id.""));
		header('Content-Type: application/pdf');
		echo $contents;
		//$this->vl_aws_services->upload_file_stream($contents, 'pdf/'.$proposal_id.'.pdf', S3_PROPOSAL_TEMPLATE_BUCKET);                                                                   
		//echo $proposal_id;                                                                                                                                                                
		//$this->proposals_model->save_proposal_pdf_location($proposal_id, S3_PROPOSAL_TEMPLATE_BUCKET.'/pdf/'.$proposal_id.'.pdf');                                                        
		//echo $this->vl_aws_services->get_file(S3_PROPOSAL_TEMPLATE_BUCKET, '/pdf/'.$proposal_id.'.pdf', null);  
	}

	public function builder_save_targeting()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => array());

		if($verify_ajax_response['is_success'] !== true)
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "User logged out or not permitted";
			echo json_encode($return_array);
			return;
		}

		$raw_mpq_id = $this->input->post('mpq_id');
		$rfp_status = $this->input->post('rfp_status');
		$proposal_status = $this->input->post('status');
		if($proposal_status === false || $proposal_status['is_gate_cleared'] !== "true")
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Must complete gate details";
			echo json_encode($return_array);
			return;
		}
		
		if($raw_mpq_id === false || $rfp_status === false)
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Invalid parameters recieved by server";
			echo json_encode($return_array);
			return;
		}
		
		$is_geo_dependent = $rfp_status['has_geo_products'];
		$is_audience_dependent = $rfp_status['has_audience_products'];
		
		$product_object = $this->input->post('product_object');
		$iab_categories = $this->input->post('iab_categories');
		$rooftops = $this->input->post('rooftops');
		$tv_zones = $this->input->post('tv_zones');
		$tv_selected_networks = $this->input->post('tv_selected_networks');
		$keywords_data = $this->input->post('keywords_data');
		$advertiser_website = $this->input->post('advertiser_website');
		$political_segments = $this->input->post('political_segments');
		$demographics = $this->input->post('demographics');
		$builder_targeting_response = $this->proposals_model->builder_save_targeting_data($raw_mpq_id, $demographics, json_decode($iab_categories), $rooftops, $tv_zones, $tv_selected_networks, $keywords_data, $advertiser_website, $political_segments);
		
		$builder_proposal_response = $this->proposals_model->builder_save_proposal_data($raw_mpq_id);
		$set_submitted_response = $this->proposals_model->builder_set_mpq_submitted($raw_mpq_id);

		if($builder_proposal_response !== false)
		{
			$this->proposals_model->delete_non_overview_snapshots($builder_proposal_response, true);
			$save_location_and_product_response = $this->proposals_model->builder_save_location_and_product_data($raw_mpq_id, $product_object, $builder_proposal_response);
			$this->builder_generate_auto_site_list($raw_mpq_id, $builder_proposal_response);

			if($save_location_and_product_response === false)
			{
				$return_array['is_success'] = false;
				$return_array['errors'][] = "Failed to save location and product data";
			}
			
			$proposal_status['is_targets_cleared'] = true;
			$set_completion_status_response = $this->proposals_model->set_proposal_completion_status($raw_mpq_id, $proposal_status);
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Failed to save proposal data";
		}


		echo json_encode($return_array);
	}

	public function builder_save_budget()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => array());

		if($verify_ajax_response['is_success'] !== true)
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "User logged out or not permitted";
			echo json_encode($return_array);
			return;
		}

		$raw_mpq_id = $this->input->post('mpq_id');
		$rfp_status = $this->input->post('rfp_status');
		$options = $this->input->post('options');
		$discount_text = $this->input->post('discount_text');
		$product_object = $this->input->post('product_object');
		$proposal_status = $this->input->post('status');
		if($proposal_status === false || $proposal_status['is_targets_cleared'] !== "true")
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Must complete targeting details";
			echo json_encode($return_array);
			return;
		}

		if($raw_mpq_id === false || $rfp_status === false || $options === false || $product_object === false)
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Invalid parameters recieved by server";
			echo json_encode($return_array);
			return;
		}

		if($discount_text === false)
		{
			$discount_text = "Discount";
		}
		$option_data = json_decode($options, true);

		$prop_id = $this->proposals_model->get_proposal_id_by_mpq_id($raw_mpq_id);
		if($prop_id === false)
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Unable to find proposal data";
			echo json_encode($return_array);
			return;
		}

		$this->proposals_model->delete_non_overview_snapshots($prop_id, true);
		$this->proposals_model->reset_queued_status($prop_id);

		$mpq_options_response = $this->proposals_model->builder_save_mpq_options($raw_mpq_id, $option_data, $discount_text);
		$prop_gen_options_response = $this->proposals_model->builder_save_prop_gen_options($prop_id, $option_data, $discount_text);
		$save_location_and_product_response = $this->proposals_model->builder_save_location_and_product_data($raw_mpq_id, $product_object, $prop_id);
		$this->builder_generate_auto_site_list($raw_mpq_id, $prop_id);

		$set_submitted_response = $this->proposals_model->builder_set_mpq_submitted($raw_mpq_id);
		$this->generate_original_summary($prop_id);

		if($save_location_and_product_response === false || $mpq_options_response === false || $prop_gen_options_response === false || $set_submitted_response === false)
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = "Failed to save location and product data";
		}
		
		$proposal_status['is_budget_cleared'] = true;
		$set_completion_status_response = $this->proposals_model->set_proposal_completion_status($raw_mpq_id, $proposal_status);

		echo json_encode($return_array);
	}
	
	private function builder_generate_auto_site_list($mpq_id, $prop_id)
	{
		$lap_ids_array = $this->proposal_gen_model->get_lap_ids_from_mpq_id($mpq_id);
		$local_sites_included_flag = true;
		if (count($lap_ids_array) > 1)
		{
			$local_sites_included_flag = false;
		}
		$sites_array = $this->siterankpoc_model->get_sites_for_mpq($mpq_id, null, null, null, null, null, null, null, false, $local_sites_included_flag);
		
		if ($sites_array != "" && count($sites_array) > 0)
		{
			$sites_string = json_encode($sites_array);
			$this->proposal_gen_model->save_proposal_sites($sites_string, $prop_id);
			$this->siterankpoc_model->get_site_rankings_proposal($mpq_id);
		}
		
		$this->siterankpoc_model->generate_save_local_sites_proposal($mpq_id);
	}

    private function generate_original_summary($proposal_id, $is_audience = true, $is_geo = true)
	{
		$data = array();

		$data['proposal'] = $this->proposals_model->get_proposal_by_id($proposal_id, $is_audience, $is_geo);
		if(empty($data['proposal']['geos']))
		{
			$data['proposal']['geos'] = $data['proposal']['submitted_geos']; // only one or the other is defined, to conserve memory
		}

		$data['industry'] = $this->mpq_v2_model->get_industry_by_id($data['proposal']['industry_id']);
		if($data['industry'] == false)
		{
			$data['industry'] = array('freq_industry_tags_id' => '0', 'name' => 'Unknown');
		}

		$data['mpq'] = $this->mpq_v2_model->get_proposal_with_mpq($proposal_id);

		$data['creator'] = $this->users->get_user_by_id($data['mpq']['creator_user_id'], true);

		$data['categories'] = $this->mpq_v2_model->get_submitted_mpq_options($data['proposal']['source_mpq']);

		$data['all_location_term_totals'] = array();

		$data['rooftops'] = json_decode($data['mpq']['rooftops_data'], true);

		$data['no_discount'] = $data['proposal']['no_discount'];

		$data['political'] = $this->proposals_model->format_political_data($data['mpq']['id']);

		$data['tv_zones'] = $this->mpq_v2_model->get_rfp_tv_zones_by_mpq_id($data['mpq']['id']);

		$num_locations = count($data['proposal']['geos']);
		$html_summary = $this->load->view('/generator/submission_summary_html.php', $data, true);
		return $this->proposal_gen_model->save_original_summary_html($data['proposal']['source_mpq'], $html_summary);
	}
}
