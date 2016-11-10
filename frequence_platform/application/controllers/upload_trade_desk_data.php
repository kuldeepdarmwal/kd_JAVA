<?php
// Filters the list for only files
function filter_file_list($arr)
{
  return array_values(array_filter(array_map('file_path', $arr)));
}

// Callback used by filter_file_list()
function file_path($file)
{
  return !is_dir($file) ? realpath($file) : null;
}

class Upload_Trade_Desk_Data extends CI_Controller
{
  function __construct()
  {
    parent::__construct();

    $this->load->model('upload_placement_data_model');

    $this->load->helper('url');
    $this->load->helper('form');

    $this->load->library('tank_auth');
    $this->load->library('session');
    $this->load->library('AmazonWebServicesSdk');
  }

  function index()
  {
    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','upload_trade_desk_data');
      redirect(site_url("login"));
    }
    $viewData = array();
    $this->load->view('upload_placement_data/trade_desk_data_upload', $viewData);
  }

  function GetListOfFilesToProcess()
  {
  }

  function DownloadFile()
  {
  }

  function UnzipFile()
  {
  }

  function OptimizeIntermediateTables()
  {
    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','upload_trade_desk_data');
      redirect(site_url("login"));
    }
    $this->upload_placement_data_model->OptimizeIntermediateTables();
    $this->load->view('upload_placement_data/optimize_intermediate_tables');
  }

  function LoadRawDataFeed($s3, $destinationTableType, $sourceFilesList, &$progressInfo)
  {
    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','upload_trade_desk_data');
      redirect(site_url("login"));
    }
    foreach($sourceFilesList as $hourBucket)
      {
	$progressInfo[] = $hourBucket;

	$tempFileName = '/tmp/ttdFileToDecompress.log.gz';
	$objectResponse = $s3->get_object('thetradedesk-uswest-partners-vantagelocal', 
					  $hourBucket, 
					  array('fileDownload' => $tempFileName));

	if($objectResponse->isOK())
	  {
	    $fileData = gzfile($tempFileName);

	    if($fileData)
	      {
		if($destinationTableType == "impressions")
		  {
		    foreach($fileData as $fileLine)
		      {
			$cells = str_getcsv($fileLine, "\t");

			$this->upload_placement_data_model->UploadImpressionRowToRawDataFeed($cells);
		      }
		  }
		elseif($destinationTableType == "clicks")
		  {
		    foreach($fileData as $fileLine)
		      {
			$cells = str_getcsv($fileLine, "\t");

			$this->upload_placement_data_model->UploadClickRowToRawDataFeed($cells);
		      }
		  }
		else
		  {
		    die("Unknown destinationTableType: ".$destinationTableType);
		  }
	      }
	    else
	      {
		die("Failed to open or gunzip for bucket: ".$hourBucket);
	      }
	  }
	else
	  {
	    die("Failed to get response from Amazon s3 object.");
	  }
      }
  }

  function ProcessAndTransferData() //$date)
  {
    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','upload_trade_desk_data');
      redirect(site_url("login"));
    }
    $viewData = array();
    $progressInfo = array();

    $dateToProcess = $this->input->post('date');

    if($dateToProcess)
      {
	$progressInfo[] = 'process and transfer '.$dateToProcess;
	$sitesAggregateResponse = $this->upload_placement_data_model->AggregateImpressionAndClickData("sites", $dateToProcess);
	$this->upload_placement_data_model->UploadImpressionAndClickData("sites", $sitesAggregateResponse, $dateToProcess);

	$citiesAggregateResponse = $this->upload_placement_data_model->AggregateImpressionAndClickData("cities", $dateToProcess);
	$this->upload_placement_data_model->UploadImpressionAndClickData("cities", $citiesAggregateResponse, $dateToProcess);
      }
    else
      {
	$progressInfo[] = 'no post data';
      }

    $viewData['progressInfo'] = $progressInfo;
    $this->load->view('upload_placement_data/process_and_transfer_data', $viewData);
  }

  function LoadData() //$date)
  {
    // 	get a list of the 'hours' folders in the date's folder
    // 	for each 'hour' folder
    //		upload impressions, clicks, and conversions files to ttd_* database tables
    //	generate rows for CityTable and SitesTable from the uploaded data
    // clear out ttd_* tables
    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','upload_trade_desk_data');
      redirect(site_url("login"));
    }
    //$dataFeedDb = $this->load->database('localhost', TRUE);
    $progressInfo = array();

    $startDateTimePost = $this->input->post('startDateTime');
    $endDateTimePost = $this->input->post('endDateTime');

    if($startDateTimePost && $endDateTimePost)
      {
	$progressInfo[] = 'Post: '.$startDateTimePost.' : '.$endDateTimePost;

	//$startDateTime = new DataTime($startDateTimePost);
	$startDateTime = new DateTime($startDateTimePost); //('2012-06-04 00:00:00');
	$startDateHour = new DateTime($startDateTime->format("Y-m-d H:00:00"));
	$startDate = new DateTime($startDateTime->format("Y-m-d"));
	$currentDateHour = clone $startDateHour;
	$timeInterval = new DateInterval('PT1H0M0S');
	$endDateTime = new DateTime($endDateTimePost);

	$s3 = new AmazonS3();

	//$bucketByDate = "2012/06/04/";
	//$bucketByDateAndHour = "2012/06/04/01/";

	while($currentDateHour->getTimestamp() < $endDateTime->getTimestamp())
	  {
	    $progressInfo[] = $currentDateHour->format('Y-m-d H:i:s')."\n";

	    $bucketByDateAndHour = $currentDateHour->format('Y/m/d/H/');

	    $impressionBucketsByHour = $s3->get_object_list("thetradedesk-uswest-partners-vantagelocal", 
							    array(
								  "prefix" => $bucketByDateAndHour,
								  "pcre" => "/impressions/"
								  ));
	    $this->LoadRawDataFeed($s3, "impressions", $impressionBucketsByHour, $progressInfo);

	    $clickBucketsByHour = $s3->get_object_list("thetradedesk-uswest-partners-vantagelocal", 
						       array(
							     "prefix" => $bucketByDateAndHour,
							     "pcre" => "/clicks/"
							     ));
	    $this->LoadRawDataFeed($s3, "clicks", $clickBucketsByHour, $progressInfo);

	    $currentDateHour->add($timeInterval);
	  }
      }
    else
      {
	$progressInfo[] = 'no post data: '.$startDateTimePost.' : '.$endDateTimePost;
      }

    //$viewData = array();
    $viewData['progressInfo'] = $progressInfo;
    //$viewData['bucketsByHour'] = $clickBucketsByHour;

    $this->load->view('upload_placement_data/load_data', $viewData);
  }

  function index_test()
  {
    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','upload_trade_desk_data');
      redirect(site_url("login"));
    }
    $data = array();
    $data['aTest'] = "A test.";

    $progressInfo = array();

    // Instantiate the AmazonS3 class
    $s3 = new AmazonS3();
    $bucketList = array();
    //$bucketList = $s3->get_bucket_list();
    $data['bucketList'] = $bucketList;

    $testObjectList = array();
    $testObjectList = $s3->get_object_list("thetradedesk-uswest-partners-vantagelocal", array());
    foreach($testObjectList as $testObject)
      {
	$progressInfo[] = "testObject: ".$testObject;
      }

    //$objectResponse = array();
    $objectResponse = $s3->get_object('thetradedesk-uswest-partners-vantagelocal', '2012/06/03/02/impressions_nc31odz_v2_2012-06-03T02_2012-06-03T03.log.gz');
    if($objectResponse->isOK())
      {
	$progressInfo[] = "objectResponse is OK";
      }
    $data['objectResponse'] = $objectResponse;

    $fileUrls = array();

    $data['fileUrls'] = $fileUrls;
    $data['progressInfo'] = $progressInfo;
    $this->load->view('upload_placement_data/index', $data);
    //$this->load->view('upload_placement_data/sdk_compatibility_test');
  }
}

?>
