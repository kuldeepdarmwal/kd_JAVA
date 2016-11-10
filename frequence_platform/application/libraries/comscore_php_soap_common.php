<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

	//all the classes herea are generated automatically using php2soap tool. These enable as helpers while creating the soap requests to comscore 
	class comscore_php_soap_common 
	{	
		//CONSTANTS
		private $COMSCORE_URI = "http://comscore.com/";
		private $COMSCORE_USERNAME = "van_mrobles";
		private $COMSCORE_PASSWORD = "getfreq123";
		
		public function __construct()
		{
			$this->ci=&get_instance();
		}
		
		public function get_cs_soap_client($wsdl_name) 
		{
			$client=new SoapClient
			(
				$wsdl_name,
				array 
				(
					"trace"      => 1,
					"exceptions" => 1,
					"cache_wsdl" => 0,
					"uri" => $this->COMSCORE_URI,
					"login" => $this->COMSCORE_USERNAME,
					"password" => $this->COMSCORE_PASSWORD
				)
			);	
			return $client;
		}
	}
	
	// Classes below this are generated using wsdltosoap converter. These are generated using the Comscore WSDL. Used as helpers during Soap calls.
	class PingReport 
	{
		public $jobId; // int
	}
	
	class PingReportResponse 
	{
		public $PingReportResult; // PingReportResult
	}
	
	class QueryReport 
	{
		public $query; // query
	}
	
	class QueryReportResponse 
	{
		public $QueryReportResult; // QueryReportResult
	}
	
	class DiscoverParameters 
	{
	}
	
	class DiscoverParametersResponse 
	{
		public $DiscoverParametersResult; // DiscoverParametersResult
	}
	
	class DiscoverParameterValues 
	{
		public $parameterId; // string
		public $query; // query
	}
	
	class DiscoverParameterValuesResponse 
	{
		public $DiscoverParameterValuesResult; // DiscoverParameterValuesResult
	}
	
	class PingReportStatus 
	{
		public $jobId; // int
	}
	
	class PingReportStatusResponse 
	{
		public $PingReportStatusResult; // PingReportStatusResult
	}
	
	class FetchStringReportResponse 
	{
		public $jobId; // int
	}
	
	class FetchStringReportResponseResponse 
	{
		public $FetchStringReportResponseResult; // string
	}
	
	class FetchReport 
	{
		public $jobId; // int
	}
	
	class FetchReportResponse 
	{
		public $FetchReportResult; // FetchReportResult
	}
	
	class FetchMedia 
	{
		public $parameterId; // string
		public $fetchMediaQuery; // fetchMediaQuery
		public $reportQuery; // reportQuery
	}
	
	class FetchMediaResponse 
	{
		public $FetchMediaResult; // FetchMediaResult
	}
	
	class SubmitReport 
	{
		public $query; // query
	}
	
	class SubmitReportResponse 
	{
		public $SubmitReportResult; // SubmitReportResult
	}
	
	class PingReportResult 
	{
		public $Status; // boolean
		public $Errors; // Error
	}
	
	class Error 
	{
		public $Severity; // string
		public $Message; // string
	}
	
	class QueryReportResult 
	{
		public $JobId; // string
		public $Errors; // Error
	}
	
	class PingReportStatusResult 
	{
		public $Status; // Status
		public $Errors; // Error
	}
	
	class Status 
	{
	}
	
	class FetchReportResult 
	{
		public $REPORT; // report
		public $Errors; // Error
	}
	
	class report 
	{
		public $TITLE; // string
		public $SUBTITLE; // string
		public $SUMMARY; // summary
		public $TABLE; // table
	}
	
	class summary 
	{
		public $GEOGRAPHY; // string
		public $LOCATION; // string
		public $TIMEPERIOD; // string
		public $TARGET; // string
		public $MEDIA; // string
		public $MEASURES; // string
		public $BASE; // string
		public $SAMPLEBASE; // string
		public $REACHOPTION; // string
		public $SEARCHTYPE; // string
		public $DATE; // string
		public $USER; // string
	}
	
	class table 
	{
		public $THEAD; // ArrayOfTR
		public $TBODY; // ArrayOfTR
	}
	
	class tr 
	{
		public $TH; // th
		public $TD; // td
	}
	
	class th 
	{
		public $_; // string
		public $web_id; // int
		public $parent_id; // int
		public $depth; // int
		public $media_type; // string
		public $hybrid_type; // string
	}
	
	class td 
	{
		public $_; // string
		public $id; // int
	}
	
	class SubmitReportResult 
	{
		public $JobId; // string
		public $Errors; // Error
	}
	
	class query 
	{
		public $Parameter; // Parameter
		public $Option; // option
		public $RFInput; // RFInput
	}
	
	class Parameter 
	{
		public $KeyId; // string
		public $Value; // string
	}
	
	class RFInput 
	{
		public $MediaId; // string
		public $TargetId; // string
		public $Duration; // int
		public $Impressions; // int
		public $FrequencyCap; // int
		public $ReachFactor; // decimal
		public $CPM; // decimal
	}
	
	class reportQuery 
	{
		public $Parameter; // Parameter
		public $Option; // option
		public $RFInput; // RFInput
	}
	
	class DiscoverParametersResult 
	{
		public $Parameter; // Parameter
		public $Option; // Option
		public $ReportId; // int
		public $ReportName; // string
		public $UsesRFInputs; // boolean
	}
	
	class Parametera 
	{
		public $Dependency; // Dependency
		public $EnumValue; // EnumValue
		public $Option; // Option
		public $Name; // string
		public $Id; // string
		public $Description; // string
		public $Required; // boolean
		public $SupportsMultiple; // boolean
	}
	
	class Dependency 
	{
		public $ParameterId; // string
	}
	
	class EnumValue 
	{
		public $Value; // string
		public $Id; // string
	}
	
	class Option 
	{
		public $Id; // string
		public $Name; // string
		public $Default; // boolean
		public $Value; // boolean
	}
	
	class DiscoverParameterValuesResult 
	{
		public $Dependency; // Dependency
		public $EnumValue; // EnumValue
		public $Option; // Option
		public $Name; // string
		public $Id; // string
		public $Description; // string
		public $Required; // boolean
		public $SupportsMultiple; // boolean
	}
	
	class fetchMediaQuery 
	{
		public $SearchCritera; // SearchCritera
		public $MediaSet; // MediaSet
	}
	
	class SearchCritera 
	{
		public $ExactMatch; // boolean
		public $Critera; // string
	}
	
	class FetchMediaResult 
	{
		public $MediaSet; // MediaSet
		public $MediaItem; // MediaItem
	}
	
	class MediaSet 
	{
		public $MediaSetId; // string
		public $MediaItem; // MediaItem
		public $Name; // string
		public $Id; // string
	}
	
	class MediaItem 
	{
		public $Name; // string
		public $Id; // string
		public $MediaType; // string
	}
?>
