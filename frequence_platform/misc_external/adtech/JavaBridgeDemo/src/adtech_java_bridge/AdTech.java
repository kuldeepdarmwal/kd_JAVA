package adtech_java_bridge;
import de.adtech.helios.AttributeOperatorValueExpression;
import de.adtech.helios.BoolExpression;
import de.adtech.helios.HeliosPersistenceException;
import de.adtech.helios.CampaignManagement.Campaign;
import de.adtech.helios.CampaignManagement.DateRange;
import de.adtech.helios.CampaignManagement.DeliveryGoal;
import de.adtech.helios.CampaignManagement.PricingConfig;
import de.adtech.helios.CampaignManagement.WSCampaignAdminException;
import de.adtech.helios.CustomerManagement.Advertiser;
import de.adtech.helios.CustomerManagement.Customer;
import de.adtech.helios.CustomerManagement.WSCustomerAdminException;
import de.adtech.helios.PushManagement.PushManagementException;
import de.adtech.helios.PushManagement.WSPushServiceException;
import de.adtech.helios.webservice.security.SecurityException;
import de.adtech.helios.BannerManagement.Banner;
import de.adtech.helios.BannerManagement.BannerInfo;
import de.adtech.helios.BannerManagement.WSBannerAdminException;
import de.adtech.webservices.helios.client.HeliosWSClientSystem;
import de.adtech.webservices.helios.lowLevel.constants.IArchivableEntitiy;
import de.adtech.webservices.helios.lowLevel.constants.IAttributeOperatorValueExpression;
import de.adtech.webservices.helios.lowLevel.constants.IBanner;
import de.adtech.webservices.helios.lowLevel.constants.ICustomer;

import java.security.NoSuchProviderException;

import java.io.ByteArrayOutputStream;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.Collection;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.Vector;


import org.systinet.wasp.webservice.LookupException;

import de.adtech.webservices.helios.client.HeliosWSClientSystem;
import de.adtech.webservices.helios.lowLevel.constants.IAttributeOperatorValueExpression;
import java.util.Collection;
import java.util.Vector;


import adtech_java_bridge.VlResultFromJavaBridge;

import com.google.gson.Gson;
import com.idoox.transport.util.URLDecoder;

public class AdTech 
{
		private static String username = "prod-at-api.vantagelocal.com";
		private static String password = "1qa2ws3ed!@#";	
		private static String serverURL = "https://ws.us-ec.adtechus.com";
		private static String wsdl_url = "http://ws.us-ec.adtechus.com";
		private static String clientSystemPath = "";//"/var/www_vl_tt/misc_external/adtech/phpjava/HeliosWSClientSystem_1.16.1a";
		private HeliosWSClientSystem helios_ws;
		
		public AdTech() /*throws NoSuchProviderException, LookupException, Exception*/
		{
				String classpath = System.getProperty("java.class.path");
				String[] classpathEntries = classpath.split(java.io.File.pathSeparator);
				
				final String kHeliosClassPath = "HeliosWSClientSystem_1.16.1a"; 
				final String kHeliosClassPathWithSepartor = "HeliosWSClientSystem_1.16.1a" + java.io.File.separator; 
				for(String myPath : classpathEntries)
				{
					if(myPath.contains(kHeliosClassPath))
					{
						if(!myPath.contains(kHeliosClassPathWithSepartor))
						{
							clientSystemPath = myPath;
						}
					}
				}
			
			System.out.print("INIT ONE");
			//helios_ws.initServices(serverURL, wsdl_url, clientSystemPath, username, password);
			System.out.print("INITED ONE");
		}

		private String internal_connect() throws NoSuchProviderException, LookupException, Exception
		{
				helios_ws = new HeliosWSClientSystem();
				
				System.out.print("INIT ONE");
				helios_ws.initServices(serverURL, wsdl_url, clientSystemPath, username, password);
				System.out.print("INITED ONE");
				
				Customer[] customer_list = helios_ws.customerService.getCustomerList(null, null, null, null);
				String customers = new String();
				for(int i = 0; i < customer_list.length; i++)
				{
						customers += customer_list[i].getId() + "," + customer_list[i].getName() + "|";
				}

				return customers;
		}

		public String connect()
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String customers = internal_connect();
				result.m_result = customers;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}
    
    private String internal_get_adtech_customers() throws Exception
    {
			Customer[] customer_list = helios_ws.customerService.getCustomerList(null, null, null, null);
			String return_string = "";
			for(int i = 0; i < customer_list.length; i++)
			{
					return_string += customer_list[i].getId() + "|" + customer_list[i].getName() + "#";
			}
			
			return return_string;
    }
		
    public String get_adtech_customers()
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_get_adtech_customers();
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}

    private String internal_get_adtech_advertisers(String customer_id) throws Exception
    {
			Advertiser[] advertiser_list = helios_ws.customerService.getAdvertiserList(null, null, null, null);
			String return_string = "";
			for(int i = 0; i < advertiser_list.length; i++)
			{
					return_string += advertiser_list[i].getId() + "|" + advertiser_list[i].getName();
					if(i != advertiser_list.length -1)
						return_string += "#";
			}
			
			return return_string;
    }
    
    public String get_adtech_advertisers(String customer_id)
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_get_adtech_advertisers(customer_id);
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}

    private String internal_get_advertisers_for_customer(String customer_id) throws NoSuchProviderException, LookupException, Exception
    {
			System.out.print("RETREIVING CAMPAIGN LIST");
			String[] ids = new String[1];
			ids[0] = customer_id;
			BoolExpression boolExpression = build_filter("id", IAttributeOperatorValueExpression.OP_LIKE, ids, false);
			
			Customer[] customerList = helios_ws.customerService.getCustomerList(null, null, boolExpression, null);
			Advertiser[] advertisers = helios_ws.customerService.getAdvertiserList(null, null, null, null);
			
			System.out.print(customerList.length);

			System.out.print("GOT IT");
			
			for(int i = 0; i < customerList.length;i++)
			{
				System.out.print("\n");
				System.out.print("NAME("+i+ "): " + customerList[i].getName() + "\n");
				System.out.print("ID: " + customerList[i].getId() + "\n");
				System.out.print("Advertiser: "+customerList[i].getAdvertiser().size() + "\n");
			}
			
			Long[] advertisers_for_customer = new Long[customerList[0].getAdvertiser().size()];
			
			for(int i = 0; i < customerList[0].getAdvertiser().size(); i++)
			{
				advertisers_for_customer[i] = (Long) customerList[0].getAdvertiser().toArray()[i];
			}
			
			String output_string = "";
			if (advertisers != null)
			{
				for(int i = 0; i < advertisers_for_customer.length ;i++)
				{
					for(int j = 0; j < advertisers.length; j++)
					{
						System.out.print("\n IS ("+advertisers_for_customer[i].getClass()+")"+advertisers_for_customer[i]+" = ("+advertisers[j].getId().getClass()+")"+advertisers[j].getId()+"?\n");
						if((float)advertisers_for_customer[i] == (float)advertisers[j].getId())
						{
							System.out.print("\n");
							System.out.print("NAME("+i+ "): " + advertisers[j].getName() + "\n");
							System.out.print("ID: " + advertisers[j].getId() + "\n");
							output_string += advertisers[j].getId()+"|"+advertisers[j].getName();
							if(i != advertisers_for_customer.length -1)
								output_string += "#";
						}
					}
				}
			}

			return output_string;
    }
    
    public String get_advertisers_for_customer(String customer_id) 
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_get_advertisers_for_customer(customer_id);
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}
    
    private String internal_get_campaigns_for_advertiser(String advertiser_id) throws WSCampaignAdminException
    {
		String[] ids = new String[1];
		ids[0] = (String)advertiser_id;
		System.out.print(ids[0]);
		BoolExpression filter_thing = build_filter("advertiserId", IAttributeOperatorValueExpression.OP_LIKE, ids, true);
		Campaign[] campaigns = helios_ws.campaignService.getCampaignList(null,null, filter_thing, null);
		String output_string = "";
		if(campaigns != null)
		{
				for(int i = 0; i < campaigns.length;i++)
				{
					output_string += campaigns[i].getId()+"|"+campaigns[i].getName();
					if(i != campaigns.length -1)
						output_string += "#";
				}
		}
		return output_string;
    }
    
    public String get_campaigns_for_advertiser(String advertiser_id)
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_get_campaigns_for_advertiser(advertiser_id);
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}
    
    private String internal_create_new_advertiser_from_template_advertiser(String advertiser_name, String customer_id) throws WSCustomerAdminException
    {
		String[] names = new String[1];
		names[0] = "Template Advertiser - Do Not Delete";
		BoolExpression boolExpression = build_filter("name", IAttributeOperatorValueExpression.OP_EQUAL, names, false);
		System.out.print("MAKING A THING");
		Advertiser[] advertisers = helios_ws.customerService.getAdvertiserList(null, null, boolExpression, null);
		
		if(advertisers.length == 1)
		{
				Advertiser new_advertiser = advertisers[0];
				new_advertiser.setName(advertiser_name);
				Advertiser dat_advertiser = helios_ws.customerService.createAdvertiser(new_advertiser);
				System.out.print("NEWADVERTISER ID: "+ dat_advertiser.getId());
				link_customer_to_advertiser(dat_advertiser.getId(), customer_id);
				return ""+dat_advertiser.getId();
		}
		return "FAILURE";
	
    }

    public String create_new_advertiser_from_template_advertiser(String advertiser_name, String customer_id)
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_create_new_advertiser_from_template_advertiser(advertiser_name, customer_id);
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}
    
    
    private String internal_create_new_campaign_from_template_campaign(String campaign_name, String landing_page, String advertiser_id, String customer_id) throws WSCustomerAdminException, WSCampaignAdminException
    {
    	    	
		String[] names = new String[1];
		names[0] = "%Template Campaign - Please No Delete%";
		BoolExpression boolExpression = build_filter("name", IAttributeOperatorValueExpression.OP_LIKE, names, true);
		System.out.print("GETTING A THING");
		Campaign[] campaigns = helios_ws.campaignService.getCampaignList(null, null, boolExpression, null);
		System.out.print("MAKING A THING");
		System.out.print("\n Advertiser: "+advertiser_id);
		System.out.print("\n Customer: "+customer_id);
		if(campaigns.length == 1)
		{
				Campaign new_campaign = campaigns[0];
				new_campaign.setName(campaign_name);
				new_campaign.setAdvertiserId(new Long(advertiser_id));
				new_campaign.setCustomerId(new Long(customer_id));
				new_campaign.setDefaultLinkUrl(URLDecoder.decode(landing_page));
				System.out.print("New: "+new_campaign.getId());
				System.out.print(new_campaign.getName());
				System.out.print("MAKING A THING");
				Campaign dat_campaign = helios_ws.campaignService.createCampaign(new_campaign);
				System.out.print("NEWCAMPAIGN ID: "+ dat_campaign.getId());
				return ""+dat_campaign.getId();
		}
		return "FAILURE";
	
    }
    
    public String create_new_campaign_from_template_campaign(String campaign_name, String landing_page, String advertiser_id, String customer_id)
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_create_new_campaign_from_template_campaign(
					campaign_name,
					landing_page,
					advertiser_id,
					customer_id
				);
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}
    
    
    private void internal_link_customer_to_advertiser(Long advertiser_id, String customer_id) throws WSCustomerAdminException
    {
		String[] names = new String[1];
		names[0] = customer_id;
		BoolExpression boolExpression = build_filter("id", IAttributeOperatorValueExpression.OP_LIKE, names, false);
		
		Customer[] customers = helios_ws.customerService.getCustomerList(null, null, boolExpression, null);
		
		Customer new_customer = customers[0];
		Collection<Long> advertisers = new_customer.getAdvertiser();
		advertisers.add(advertiser_id);
		new_customer.setAdvertiser(advertisers);
		helios_ws.customerService.updateCustomer(new_customer);
		
    }

    public String link_customer_to_advertiser(Long advertiser_id, String customer_id)
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				internal_link_customer_to_advertiser(advertiser_id, customer_id);
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}
    
    private static BoolExpression build_filter (String filter_by, String filter_like, String[] filter_terms, boolean is_campaign_search)
    {
			Vector filter = new Vector();
			for(int i = 0; i < filter_terms.length; i++){
				AttributeOperatorValueExpression this_adv = new AttributeOperatorValueExpression();
				this_adv.setAttribute(filter_by);
				this_adv.setOperator(filter_like);
				this_adv.setValue(filter_terms[i]);
				filter.add(this_adv);
			}
			
			if(is_campaign_search)
			{
			AttributeOperatorValueExpression this_adv = new AttributeOperatorValueExpression();
			this_adv.setAttribute("campaignTypeId");
			this_adv.setOperator(filter_like);
			this_adv.setValue("27365");
			filter.add(this_adv);
			}
			
			BoolExpression boolExpression = new BoolExpression();
			boolExpression.setExpressions(filter);
			return boolExpression;
    }
    
    
    private String internal_create_new_banner_for_campaign(long campaign_id, String banner_name, String file_path_or_something, int size_type_id, String landing_page_url) throws IOException, WSBannerAdminException, HeliosPersistenceException, PushManagementException, WSPushServiceException, SecurityException
	{
		
		//728x90 - 225
		//300x250 - 170
		//160x600 - 154
		//336x280 - 171
		
		System.out.print("CREATING A NEW BANNER");
		Banner new_banner = new Banner();
		new_banner.setName(banner_name);
		new_banner.setId(new Long(-1));
		new_banner.setCampaignId(campaign_id);
		new_banner.setSizeTypeId(size_type_id);
		
		String file_name = file_path_or_something.substring(file_path_or_something.lastIndexOf('/')+1);
	
		if(file_path_or_something.endsWith("zip"))
		{
			new_banner.setFileType(IBanner.FILE_TYPE_ZIP);
			new_banner.setStyleTypeId(3);
			new_banner.setMainFileName("index.html");
		}
		else if(file_path_or_something.endsWith("jpg"))
		{
			new_banner.setFileType(IBanner.FILE_TYPE_JPG);
			new_banner.setStyleTypeId(IBanner.STYLE_IMAGE);
			new_banner.setMainFileName(file_name);
		}
		else if(file_path_or_something.endsWith("png"))
		{
			new_banner.setFileType(IBanner.FILE_TYPE_PNG);
			new_banner.setStyleTypeId(IBanner.STYLE_IMAGE);
			new_banner.setMainFileName(file_name);
		}
		else if(file_path_or_something.endsWith("gif"))
		{
			new_banner.setFileType(IBanner.FILE_TYPE_GIF);
			new_banner.setStyleTypeId(IBanner.STYLE_IMAGE);
			new_banner.setMainFileName(file_name);
		}
		
		
		new_banner.setLinkUrl(landing_page_url);
		
		new_banner.setAltText("Please click here!");
		new_banner.setBannerNumber(new Long(1));
		new_banner.setCreatedAt(new Date());
		new_banner.setDeleted(new Boolean(false));
		new_banner.setDescription("This is a test");
		new_banner.setMaturityLevelId(new Long(-1));
		new_banner.setStatusId(IBanner.STATUS_ACTIVE);
		new_banner.setRedirectUrl(landing_page_url);
		new_banner.setUseCampaignAltText(false);
		new_banner.setExtId("");
		
		
		
		FileInputStream fis = new FileInputStream(file_path_or_something);
		ByteArrayOutputStream bos = new ByteArrayOutputStream();
		while(fis.available() > 0)
		{
			bos.write(fis.read());
		}
		
		new_banner.setData(bos.toByteArray());
		new_banner.setOriginalData(new_banner.getData());
		
		BannerInfo new_banner_info = new BannerInfo();
		new_banner_info.setName("test banner info");
		new_banner_info.setStatusId(IBanner.STATUS_ACTIVE);
		new_banner_info.setBannerReferenceId(new_banner.getId());
		fis.close();
		Banner created_banner = helios_ws.bannerService.createBanner(new_banner, new_banner.getCampaignId(), new_banner_info, new Long(1));
		System.out.print("ID(CREATED):" + created_banner.getId() + "\n");
		if(created_banner.getId() != null)
		{
			String new_id = ""+created_banner.getId();
			
			//helios_ws.pushService.startCampaignById(campaign_id);
			
			return new_id;
		} else {
			return "FAILURE";
		}
		
	}
    
    private String internal_update_banner_for_campaign(long campaign_id, String banner_name, String file_path_or_something, int size_type_id, String landing_page_url) throws IOException, WSBannerAdminException, HeliosPersistenceException, PushManagementException, WSPushServiceException, SecurityException
    {
		
		//728x90 - 225
		//300x250 - 170
		//160x600 - 154
		//336x280 - 171
		System.out.print("UPDATING A BANNER");
		String[] ids = new String[1];
		ids[0] = String.valueOf(campaign_id);
		BoolExpression boolExpression = build_filter("campaignId", IAttributeOperatorValueExpression.OP_LIKE, ids, false);
		Banner[] banners = helios_ws.bannerService.getBannerList(null, null, boolExpression, null);
		if(banners == null)
		{
			return "FAILURE";
		}
		String file_type = "";
		if(file_path_or_something.endsWith("zip"))
		{
			file_type = IBanner.FILE_TYPE_ZIP;
		}
		else if(file_path_or_something.endsWith("jpg"))
		{
			file_type = IBanner.FILE_TYPE_JPG;
		}
		else if(file_path_or_something.endsWith("png"))
		{
			file_type = IBanner.FILE_TYPE_PNG;
		}
		else if(file_path_or_something.endsWith("gif"))
		{
			file_type = IBanner.FILE_TYPE_GIF;
		}
		
		Banner updated_banner = banners[0];
		for(int i = 0; i < banners.length; i++)
		{
			String banner_file_type = banners[i].getFileType();
			if(banner_file_type.equals(file_type))
			{
				updated_banner = banners[i];
			}
		}
		
		updated_banner.setRedirectUrl(landing_page_url);
		
		FileInputStream fis = new FileInputStream(file_path_or_something);
		ByteArrayOutputStream bos = new ByteArrayOutputStream();
		
		while(fis.available() > 0)
		{
			bos.write(fis.read());
		}
		
		updated_banner.setData(bos.toByteArray());
		updated_banner.setOriginalData(updated_banner.getData());
		fis.close();
		
		updated_banner = helios_ws.bannerService.updateBanner(updated_banner);
		System.out.print("ID(UPDATED):" + updated_banner.getId() + "\n");
		if(updated_banner.getId() != null)
		{
			String new_id = ""+updated_banner.getId();
			
			//helios_ws.pushService.startCampaignById(campaign_id);
			
			return new_id;
		} else {
			return "FAILURE";
		}
    }
    
    public String create_new_banner_for_campaign(
			long campaign_id,
			String banner_name,
			String file_path_or_something,
			int size_type_id,
			String landing_page_url
		)
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_create_new_banner_for_campaign(
					campaign_id,
					banner_name,
					file_path_or_something,
					size_type_id,
					landing_page_url
				);
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}
    
    public String update_banner_for_campaign(
			long campaign_id,
			String banner_name,
			String file_path_or_something,
			int size_type_id,
			String landing_page_url
		)
		{
			VlResultFromJavaBridge result = new VlResultFromJavaBridge();
			try {
				String internal_result = internal_update_banner_for_campaign(
					campaign_id,
					banner_name,
					file_path_or_something,
					size_type_id,
					landing_page_url
				);
				result.m_result = internal_result;
			}
			catch(Exception exception)
			{
				String error_message = exception.getMessage();
				result.m_errors.add(error_message);
			}

			Gson json_encoder = new Gson();
			String result_json = json_encoder.toJson(result);
			return result_json;
		}

	private String internal_create_new_campaign_flight_from_template(String master_campaign_id, String placement_id, String new_flight_name, String customer_id, String advertiser_id) throws WSCampaignAdminException
	{
		String[] ids = new String[1];
		ids[0] = "%Template Flight - Do Not Delete%";
		System.out.print(ids[0]);
		BoolExpression filter_thing = build_filter("name", IAttributeOperatorValueExpression.OP_LIKE, ids, false);
		System.out.print("GETTING CAMPAIGNS");
		Campaign[] campaigns = helios_ws.campaignService.getCampaignList(null,null, filter_thing, null);
		System.out.print("GOT CAMPAIGNS");
		if (campaigns == null){
			System.out.print("Nothin'");
			return null;
		} else {
			System.out.print("Makin' it!");
			Campaign new_flight = campaigns[0];
			new_flight.setName(new_flight_name);
			
			
			Calendar calendar = GregorianCalendar.getInstance();
			calendar.setTime(new Date());
			calendar.add(Calendar.HOUR_OF_DAY, 1);
			
			new_flight.setAbsoluteStartDate(new Date(calendar.getTimeInMillis()));
			new_flight.setId(null);
			new_flight.setCustomerId(new Long(customer_id));
			new_flight.setMasterCampaignId(new Long(master_campaign_id));
			new_flight.setAdvertiserId(new Long(advertiser_id));
			new_flight.setOptimizerTypeId(new Long(6));
			
			
			Vector<DateRange> ranges = new Vector<DateRange>();
			DateRange newdaterange = new DateRange();
			
			DeliveryGoal dg = new DeliveryGoal();
			dg.setClicks(0);
			dg.setDesiredImpressions(new Long(1));
			dg.setGuaranteedImpressions(0);
			newdaterange.setDeliveryGoal(dg);
			
			newdaterange.setStartDate(new_flight.getAbsoluteStartDate());
			newdaterange.setEndDate(new_flight.getAbsoluteEndDate());
			ranges.add(newdaterange);
			new_flight.setDateRangeList(ranges);
			new_flight.setBannerTimeRangeList(null);
			new_flight.setStatusTypeId(0);

			
			Collection<Long> placement_list = new Vector<Long>();
			placement_list.add(new Long(placement_id));
			new_flight.setPlacementIdList(placement_list);
			
		
			
			System.out.print("GOING!");
			Campaign created_flight = helios_ws.campaignService.createCampaign(new_flight);
			System.out.print("FINISHED");
			if(created_flight.getId() != null)
			{
				System.out.print("AWESOME:" + created_flight.getId());
				String new_flight_id = ""+created_flight.getId();
				return new_flight_id;
			} else {
				System.out.print("NOOOO");
				return null;
			}
		}
	}
	
	public String create_new_campaign_flight_from_template(
		String master_campaign_id,
		String placement_id,
		String new_flight_name,
		String customer_id,
		String advertiser_id
	)
	{
		VlResultFromJavaBridge result = new VlResultFromJavaBridge();
		try {
			String internal_result = internal_create_new_campaign_flight_from_template(
				master_campaign_id,
				placement_id,
				new_flight_name,
				customer_id,
				advertiser_id
			);
			result.m_result = internal_result;
		}
		catch(Exception exception)
		{
			String error_message = exception.getMessage();
			result.m_errors.add(error_message);
		}

		Gson json_encoder = new Gson();
		String result_json = json_encoder.toJson(result);
		return result_json;
	}

	private String internal_retrieve_tags_from_flight(String flight_id, String campaign_id) throws NumberFormatException, Exception
	{
		System.out.print("TAG GRAB\n");
		System.out.print("flight:"+flight_id+" campaign:"+campaign_id);
		ArrayList<Long> flight_id_list = new ArrayList<Long>();
		flight_id_list.add(new Long(flight_id));
		Collection<?> tags = helios_ws.tagService.getAgencyTags(new Long(30014), new Long(campaign_id), flight_id_list);
		String tag = tags.toArray()[0].toString();
		System.out.print(tag);
		return tag;
	}

	public String retrieve_tags_from_flight(String flight_id, String campaign_id)
	{
		VlResultFromJavaBridge result = new VlResultFromJavaBridge();
		try {
			System.out.print("AS");
			String internal_result = internal_retrieve_tags_from_flight(flight_id, campaign_id);
			result.m_result = internal_result;
		}
		catch(Exception exception)
		{
			String error_message = exception.getMessage();
			result.m_errors.add(error_message);
		}

		Gson json_encoder = new Gson();
		String result_json = json_encoder.toJson(result);
		return result_json;
	}
	
	public String retrieve_landing_page_for_campaign (String campaign_id)
	{
		VlResultFromJavaBridge result = new VlResultFromJavaBridge();
		try {
			System.out.print("AS");
			String internal_result = internal_retrieve_landing_page_for_campaign(campaign_id);
			result.m_result = internal_result;
		}
		catch(Exception exception)
		{
			String error_message = exception.getMessage();
			result.m_errors.add(error_message);
		}

		Gson json_encoder = new Gson();
		String result_json = json_encoder.toJson(result);
		return result_json;
	}
	
	private String internal_retrieve_landing_page_for_campaign(String campaign_id) throws NumberFormatException, Exception
	{
		String[] ids = new String[1];
		ids[0] = String.valueOf(campaign_id);
		BoolExpression boolExpression = build_filter("id", IAttributeOperatorValueExpression.OP_LIKE, ids, false);
		Campaign[] campaigns = helios_ws.campaignService.getCampaignList(null,null, boolExpression, null);
		if(campaigns.length == 1)
		{
			
			return campaigns[0].getDefaultLinkUrl();
		}
		return "FAILURE";
		
	}
	public String start_campaign_with_id(String campaign_id)
	{
		VlResultFromJavaBridge result = new VlResultFromJavaBridge();
		try {
			System.out.print("AS");
			String internal_result = internal_start_campaign_with_id(campaign_id);
			result.m_result = internal_result;
		}
		catch(Exception exception)
		{
			String error_message = exception.getMessage();
			result.m_errors.add(error_message);
		}

		Gson json_encoder = new Gson();
		String result_json = json_encoder.toJson(result);
		return result_json;
	}
	private String internal_start_campaign_with_id(String campaign_id) throws NumberFormatException, HeliosPersistenceException, PushManagementException, WSPushServiceException, SecurityException
	{
		helios_ws.pushService.startCampaignById(new Long(campaign_id));
		return "success";
	}
}
    
