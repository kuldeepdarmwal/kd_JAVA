<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Adgroups extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->helper('form');
    $this->load->helper('url');
    $this->load->library('tank_auth');
    $this->load->library('session');
    $this->load->library('form_validation');
  }
  public function index()
  {
    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','adgroups');
      redirect(site_url("login"));
    }
    $data['user_id']		= $this->tank_auth->get_user_id();
    $data['username']		= $this->tank_auth->get_username();
    $data['business_name']      = $this->tank_auth->get_biz_name($data['username']);
    $data['role']	        = $this->tank_auth->get_role($data['username']);
    $data['sales_people']       = $this->tank_auth->get_sales_users();

    $data['firstname']	= $this->tank_auth->get_firstname($data['username']);
	$data['lastname']		= $this->tank_auth->get_lastname($data['username']);

	$data['active_menu_item'] = "ad_groups_menu";//the view will make this menu item active
	$data['title'] = "AL4k [Adgroups]";

    if(!($data['role'] == 'ops' or $data['role'] == 'admin'))
		{
			redirect(site_url("director"));
		}
    
    $data['advertisers'] = $this->tank_auth->get_businesses();
    $this->load->view('vlocal-T1/campaign_form.php', $data);
    //$this->load->view('ad_linker/header.php', $data);
    //$this->load->view('ad_linker/campaign_form_body.php', $data);
  }

  public function get_campaign_name($campaign_id)
	{
		$name = $this->tank_auth->get_campaign_name_from_id($campaign_id);
		echo $name;
	}

  public function get_advertiser_name($advertiser_id)
	{
		$name = $this->tank_auth->get_business_name_from_id($advertiser_id);
		echo $name;
	}

  public function get_campaign($business_id = 0)
  {
		if (!$this->tank_auth->is_logged_in()) {
			redirect(site_url('login'));
		}
		$campaigns = $this->tank_auth->get_campaigns_by_id($business_id);
		echo '<label for="campaigns">Campaign Name</label><br>';
		echo '<select name="campaigns" id="cgn_select" style="width:200px;" onchange=handle_campaign_change(this.value)>';
		echo '<option value="none">Select Campaign</option>';
		echo '<option value="none">--------------------</option>';
		echo '<option value="new">New Campaign</option>';
		echo '<option value="none">--------------------</option>';
		foreach($campaigns as $xprinter)
	  {
	    echo '<option value="' .$xprinter['id']. '">' .$xprinter['Name']. '</option>';
	  }
		echo '</select>';
  }

public function get_campaign_v2($business_id = 0)
  {
		if (!$this->tank_auth->is_logged_in()) {
			redirect(site_url('login'));
		}
		$campaigns = $this->tank_auth->get_campaigns_by_id($business_id);
		echo '<select name="campaigns" id="cgn_select" style="width:200px;" onchange=handle_campaign_change(this.value)>';
		echo '<option value="none">Select Campaign</option>';
		echo '<option value="none">--------------------</option>';
		echo '<option value="new">New Campaign</option>';
		echo '<option value="none">--------------------</option>';
		foreach($campaigns as $xprinter)
	  {
	    echo '<option value="' .$xprinter['id']. '">' .$xprinter['Name']. '</option>';
	  }
		echo '</select>';
  }


  public function generate_adgroup($campaign_id = "")
  {
    if (!$this->tank_auth->is_logged_in()) {
      redirect(site_url('login'));}
    $data = $this->tank_auth->get_business_and_campaign_by_id($campaign_id);

    echo 'AdGroupID:<input id="adg_id" type="text" value="" style="width:200px;"/>
      Advertiser:<input id="adg_adv" type="text" value="'.$data['business_name'].'" disabled="disabled" style="width:200px;"/>
      <input id="adg_adv_id" type="hidden" value="'.$data['business_id'].'" />
      Campaign:<input id="adg_cgn" type="text" value="'.$data['campaign_name'].'" disabled="disabled" style="width:200px;"/></td><td>
      <input id="adg_cgn_id" type="hidden" value="'.$data['campaign_id'].'" />
      City: (optional)<input id="adg_cty" type="text" value="" style="width:200px;"/>
      Region: (optional)<input id="adg_rgn" type="text" value="" style="width:200px;"/>
      Source:(TD, FB, GG, MM, SS)<input id="adg_src" type="text" value="" style="width:200px;"/>
      Retargeting:<input id="adg_rtg" type="checkbox" value="retargeting"/>';
  }
  public function campaign_details($campaign_id ="")
  {
    if (!$this->tank_auth->is_logged_in()) {
      redirect(site_url('login'));}
    $temp_campaign_details = $this->tank_auth->get_campaign_details_by_id($campaign_id);
    $campaign_details = implode($temp_campaign_details, "|");
    echo $campaign_details;
  }
  public function sales_user($business_id)
  {
     if (!$this->tank_auth->is_logged_in()) {
      redirect(site_url('login'));}
     $name = $this->tank_auth->get_business_salesperson_by_id($business_id);
		 echo $name;
  }
  public function submit_advertiser()
  {
		$name = $_POST["Name"]; 
    $insert_array = array(
			$name,
			$_POST["sales_person"]
		);
    $affected_rows = $this->tank_auth->insert_advertiser($insert_array);
    if($affected_rows > 0)
		{
			echo $this->tank_auth->get_business_id_from_name($name);
		}
		else 
		{
			echo 0;
		}
  }
  public function submit_campaign()
  {
		$end_date = $_POST["EndDate"];
		if($end_date == "")
		{
			$end_date = NULL;
		}
		else
		{
			$end_date = date("Y-m-d", strtotime($end_date));
		}
    $insert_array = array(
			$_POST["Name"], 
			$_POST["business_id"], 
			$_POST["LandingPage"], 
			$_POST["TargetImpressions"],
			$end_date
		);
    $affected_rows = $this->tank_auth->insert_campaign($insert_array);
    if($affected_rows > 0)
		{
			$campaign_details = $this->tank_auth->get_campaign_details($_POST["Name"], $_POST["business_id"]);
			if($campaign_details != null)
			{
				echo $campaign_details['id'];
			}
			else
			{
				echo "0";
			}
		}
    else 
		{
			echo "0";
		}
  }
  public function submit_adgroup()
  {
    $insert_array = array(
			$_POST["ID"], 
			$_POST["BusinessName"], 
			$_POST["CampaignName"], 
			$_POST["IsRetargeting"], 
			0, 
			$_POST["City"], 
			$_POST["Region"], 
			$_POST["Source"],
			$_POST["campaign_id"]
		);

    $affected_rows = $this->tank_auth->insert_adgroup($insert_array);
    if($affected_rows > 0)
		{
			echo "Insert Successful";
		}
    else 
		{
			echo "Insert Failed";
		}
  }
}
?>
