import {Injectable} from "@angular/core";
import {UtilityService} from "../../shared/services/utility.service";
import {CampaignListModel, CampaignProductModel, FlightsDataModel} from "../models/bulk-download.model";
declare var _: any;
/**
 *  Mapping service to Map Response(Backend) to Models(Interfaces)
 */
@Injectable()
export class CampaignMapperService {

    private _partnerId : any;

    constructor(){}


    set partnerId(partnerId :  any){
        this._partnerId = partnerId;
    }

    get partnerId(){
        return this._partnerId;
    }
    
    mapCampaignsFlightsResponseToModel(flightsResponse) : FlightsDataModel[]{
        let flightsList : FlightsDataModel[] = [];  
        for(let flight of flightsResponse){
            let budget = 0;
            let audienceExtImpressions = 0;
            let audienceExtBudget = 0;
            let oOImpressions = 0;
            let oOBudget = 0;
            let geofencingImpressions = 0;
            let geofencingBudget = 0;            
            
            if(flight.budget){
                budget = flight.budget;
            }
            
            if(flight.audience_ext_impressions){
                audienceExtImpressions = flight.audience_ext_impressions;
            }

            if(flight.audience_ext_budget){
                audienceExtBudget = flight.audience_ext_budget;
            }
            
            if(flight.o_o_impressions){
                oOImpressions = flight.o_o_impressions;
            }
            
            if(flight.o_o_budget){
                oOBudget = flight.o_o_budget;
            }
            
            if(flight.geofencing_impressions){
                geofencingImpressions = flight.geofencing_impressions;
            }
            if(flight.geofencing_budget){
                geofencingBudget = flight.geofencing_budget;
            }
            
            let flightsDataModel : FlightsDataModel = <FlightsDataModel>{};
            flightsDataModel.startDate = flight.start;
	        flightsDataModel.endDate = flight.end; 
            flightsDataModel.totalBudget = budget;
            flightsDataModel.audienceimpressions = audienceExtImpressions;
            flightsDataModel.audienceExtensionBudget = audienceExtBudget;
            flightsDataModel.ooimpressions = oOImpressions;
            flightsDataModel.oobudget = oOBudget;
            flightsDataModel.geofencingimpressions = geofencingImpressions;
            flightsDataModel.geofencingbudget = geofencingBudget;
            flightsList.push(flightsDataModel);
        }
        return flightsList;
    }
        
    mapCampaignsResponseToModel(campaignsResponse) : CampaignListModel[]{
        let campaignsList : CampaignListModel[] = [];  
        for(let campaign of campaignsResponse.campaign_array){
            let campaignListModel : CampaignListModel = <CampaignListModel>{};
            let name = '-';
            let advertiser :string = '';
            let partner :string = '';            
            let startDate :string = '';
            let endDate :string = '';
            let thisFlightStartDate :string = '';
            let thisFlightEndDate :string = '';
            let landingPageURL = '#';
            let scheduleStatus :string = '';
            let orderId :string = '0';
            let isReminder :number = 0;
            let isGeofencing = 0;
            let isGeofencingFlag = ''; 
            
            campaignListModel.id = campaign.id;            
            if(campaign.campaign){
                name = campaign.campaign; 
            }
            if(campaign.is_reminder == "1"){
                isReminder = 1;
            }
            
            if(campaign.order_id_obj){
                orderId = campaign.order_id_obj;
                orderId.toString();
            }
            if(campaign.start_date != ""){
                startDate = campaign.start_date;
            }
            if(campaign.campaign_end_date != ""){
                endDate = campaign.campaign_end_date;
            }
            if(campaign.landing_page){
                landingPageURL = campaign.landing_page;
            }
            if(campaign.schedule){
                scheduleStatus = campaign.schedule;
            }
            if(campaign.advertiser){
                advertiser = campaign.advertiser;
            }
            if(campaign.partner){
                partner = campaign.partner;
            }
            if(campaign.is_geofencing){
                isGeofencing = campaign.is_geofencing;
                isGeofencingFlag = 'dyn';
            }
            
            campaignListModel.name = name;
            campaignListModel.advertiser = advertiser;
            campaignListModel.partner = partner;
            campaignListModel.landingPageURL = landingPageURL;
            campaignListModel.scheduleStatus = scheduleStatus;
            campaignListModel.orderId = orderId;
            campaignListModel.isReminder = isReminder;
            campaignListModel.isGeofencing = isGeofencing;
            campaignListModel.isGeofencingFlag = isGeofencingFlag;
            campaignListModel.allTimeStart = startDate;
            campaignListModel.allTimeEnd = endDate;
            if(campaign.budget_info){
                if(campaign.budget_info.all_time){
                    campaignListModel.allTimeCampaign =  this.mapCampaignToModel(campaign.budget_info.all_time.campaign);
                    campaignListModel.allTimeAudienceExtension = this.mapCampaignToModel(campaign.budget_info.all_time.audience_ext);
                    campaignListModel.allTimeOAndO = this.mapCampaignToModel(campaign.budget_info.all_time.o_and_o);
                 }
                else {
                    campaignListModel = this.setEmptyData(campaignListModel);
                }

                if(campaign.budget_info.this_flight){
                    campaignListModel.thisFlightCampaign =  this.mapCampaignToModel(campaign.budget_info.this_flight.campaign);
                    campaignListModel.thisFlightAudienceExtension = this.mapCampaignToModel(campaign.budget_info.this_flight.audience_ext);
                    campaignListModel.thisFlightOAndO = this.mapCampaignToModel(campaign.budget_info.this_flight.o_and_o);
                   
                    if(campaign.budget_info.this_flight.flight_start_date){
                        thisFlightStartDate = campaign.budget_info.this_flight.flight_start_date;
                    }else{
                        campaignListModel.thisFlightCampaign =  this.mapCampaignToModel(false);
                        campaignListModel.thisFlightAudienceExtension = this.mapCampaignToModel(false);
                        campaignListModel.thisFlightOAndO = this.mapCampaignToModel(false);
                    }
                    
                    if(campaign.budget_info.this_flight.flight_end_date){
                        thisFlightEndDate = campaign.budget_info.this_flight.flight_end_date;
                    }
                }
                else {
                    campaignListModel = this.setEmptyData(campaignListModel);
                }
            }
            else {
                campaignListModel = this.setEmptyData(campaignListModel);
            }
    
            campaignListModel.thisFlightStart = thisFlightStartDate;
            campaignListModel.thisFlightEnd = thisFlightEndDate;
            campaignsList.push(campaignListModel);
        }
        return campaignsList;
    }
    
    updateMapCampaignsResponseToModel(campaignsResponse, obj) : CampaignListModel[]{
        let campaignsList : CampaignListModel[] = [];  
        for(let campaign of campaignsResponse){
            let campaignListModel : CampaignListModel = <CampaignListModel>{};
            
            campaignListModel.id = campaign.id;             
            campaignListModel.name = campaign.name;
            campaignListModel.advertiser = campaign.advertiser;
            campaignListModel.partner = campaign.partner;
            campaignListModel.isGeofencing = campaign.isGeofencing;
            campaignListModel.isGeofencingFlag = campaign.isGeofencingFlag;
            campaignListModel.landingPageURL = campaign.landingPageURL;
            campaignListModel.scheduleStatus = campaign.scheduleStatus;
            campaignListModel.allTimeStart = campaign.allTimeStart;
            campaignListModel.allTimeEnd = campaign.allTimeEnd;
            campaignListModel.thisFlightStart = campaign.thisFlightStart;
            campaignListModel.thisFlightEnd = campaign.thisFlightEnd;  
            campaignListModel.orderId = campaign.orderId;
            if(obj.campaignId == campaign.id){
               campaignListModel.isReminder = obj.reminderStatus;
            } else {
                campaignListModel.isReminder = campaign.isReminder;
            }
            campaignListModel.allTimeCampaign =  campaign.allTimeCampaign;
            campaignListModel.allTimeAudienceExtension =  campaign.allTimeAudienceExtension;
            campaignListModel.allTimeOAndO =  campaign.allTimeOAndO;
            
            campaignListModel.thisFlightCampaign =  campaign.thisFlightCampaign;
            campaignListModel.thisFlightAudienceExtension = campaign.thisFlightAudienceExtension;
            campaignListModel.thisFlightOAndO = campaign.thisFlightOAndO;
            
            campaignsList.push(campaignListModel);
        }
        return campaignsList;
    }
    
    
    setEmptyData(campaignListModel){
        campaignListModel.allTimeCampaign =  this.mapCampaignToModel('');
        campaignListModel.allTimeAudienceExtension = this.mapCampaignToModel('');
        campaignListModel.allTimeOAndO = this.mapCampaignToModel('');
        campaignListModel.thisFlightCampaign =  this.mapCampaignToModel('');
        campaignListModel.thisFlightAudienceExtension = this.mapCampaignToModel('');
        campaignListModel.thisFlightOAndO = this.mapCampaignToModel('');
        return campaignListModel;
    }

    mapCampaignToModel(response) : CampaignProductModel{
        let productDetails : CampaignProductModel = <CampaignProductModel>{};
            if(response){
                productDetails.budget = Math.round(response.budget);
                
                let oti = 0;
                if(response.oti){
                    oti = response.oti;
                }
                productDetails.oti = oti;
                productDetails.realized = Math.round(response.realized);
                productDetails.budgetImpression = response.total_impressions;
                productDetails.realizedImpression = response.realized_impressions;
            }
            else {
                productDetails.budget = 0;
                productDetails.oti = 0;
                productDetails.realized = 0;
                productDetails.budgetImpression = 0;
                productDetails.realizedImpression = 0;
            }
        return productDetails;
    } 
}
