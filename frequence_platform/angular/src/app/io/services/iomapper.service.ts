import {Injectable} from "@angular/core";
import {CreativeModel} from "../models/creative.model";
import {OpportunityOwnerModel, IndustryModel, OpportunityModel, AdvertiserModel} from "../models/opportunity.model";
import {TrackingModel, OldReferenceModel} from "../models/tracking.model";
import {IOModel, IOGetDataModel} from "../models/io.model";
import {BuildFlightsConfigurationModel, BuildFlightsRequestConfigurationModel, FlightModel, CPMsModel} from "../models/flights.model";
import {UtilityService} from "../../shared/services/utility.service";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";
import {IO_VALIDATION, CONSTANTS} from "../../shared/constants/builder.constants";
declare var _: any;
/**
 *  Mapping service to Map Response(Backend) to Models(Interfaces)
 */
@Injectable()
export class IOMapperService {

    private cpmMapping = {
        audienceExtension: 'ax',
        geofencing: 'gf',
        ownedAndOperated: 'o_o'
    }

    constructor(){}


    mapResponseToOpportunity(responseObj: any): OpportunityModel{
        let _opportunity:OpportunityModel = <OpportunityModel>{};
        _opportunity.opportunityOwner = this.mapOpportunityOwnerResponseToModel(responseObj);
        _opportunity.advertiser = this.mapAdvertiserResponseToModel(responseObj);
        _opportunity.advertiserWebsite = responseObj.advertiser_website;
        _opportunity.orderId = responseObj.order_id;
        _opportunity.orderName = responseObj.order_name;
        _opportunity.industry = this.mapIndustryResponseToModel(responseObj);
        return _opportunity;
    }

    mapOpportunityOwnerResponseToModel(opportunityOwnerResp: any) : OpportunityOwnerModel{
        let _opportunityOwnerObj:OpportunityOwnerModel = <OpportunityOwnerModel>{};
        _opportunityOwnerObj.opportunityOwnerId = opportunityOwnerResp.owner_id;
        _opportunityOwnerObj.opportunityOwnerName = opportunityOwnerResp.owner_name;
        _opportunityOwnerObj.opportunityOwnerEmail = opportunityOwnerResp.owner_email;
        return _opportunityOwnerObj;
    }

    mapIndustryResponseToModel(industryResp: any) : IndustryModel{
        let _industryObj:IndustryModel = <IndustryModel>{};
        _industryObj.industryId = industryResp.industry_data.id;
        _industryObj.industryName = industryResp.industry_data.text;
        return _industryObj;
    }

    mapAdvertiserResponseToModel(advertiserResp: any) : AdvertiserModel{
        let _advertiserObj:AdvertiserModel = <AdvertiserModel>{};
        _advertiserObj.advertiserId = advertiserResp.io_advertiser_id;
        _advertiserObj.advertiserName = advertiserResp.io_advertiser_name;
        _advertiserObj.sourceTable = advertiserResp.source_table;
        return _advertiserObj;
    }

    mapTrackingResponseToModel(response: any) : TrackingModel{
        let _trackingObj : TrackingModel = <TrackingModel>{};
        _trackingObj.trackingTagFileId = response.tracking_tag_file_id;
        _trackingObj.trackingTagFileName = response.tracking_tag_file_name;
        _trackingObj.sourceTable = response.source_table;
        _trackingObj.includeReTargeting = UtilityService.toTrueOrFalse(response.include_retargeting);
        return _trackingObj;
    }

    mapOldReferenceResponseToModel(response: any): OldReferenceModel{
        let _oldReferenceObj: OldReferenceModel = <OldReferenceModel>{};
        _oldReferenceObj.oldTrackingTagFileId = response.old_tracking_tag_file_id;
        _oldReferenceObj.oldIOAdvertiserId = response.old_io_advertiser_id;
        _oldReferenceObj.oldSourceTable = response.old_source_table;
        return _oldReferenceObj;
    }

    mapProductResponseToModel(products, customRegionsData){
        products.forEach((product) => {
            if (product.total_flights == null) product.total_flights = [];
            if (product.total_flights.length > 0){
                product.total_flights = product.total_flights.map((flight) => {
                    return {
                        id: flight.id,
                        startDate: flight.start_date,
                        endDate: flight.end_date,
                        totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
                        audienceExtensionBudget: parseFloat(flight.ax_budget),
                        audienceExtensionImpressions: parseFloat(flight.ax_impressions),
                        geofencingBudget: parseFloat(flight.gf_budget),
                        geofencingImpressions: parseFloat(flight.gf_impressions),
                        ownedAndOperatedBudget: parseFloat(flight.o_o_budget),
                        ownedAndOperatedImpressions: parseFloat(flight.o_o_impressions),
                        ownedAndOperatedForecastImpressions: parseFloat(flight.o_o_forecast_impressions),
                        forecast_status: flight.dfp_status,
                        regionId: null
                    };
                });
            }

            if (product.flights == null) product.flights = [];
            if (product.flights.length > 0){
                product.flights = product.flights.map((region) => {
                    return region.map((flight) => {
                        return {
                            id: flight.id,
                            startDate: flight.start_date,
                            endDate: flight.end_date,
                            totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
                            audienceExtensionBudget: parseFloat(flight.ax_budget),
                            audienceExtensionImpressions: parseFloat(flight.ax_impressions),
                            geofencingBudget: parseFloat(flight.gf_budget),
                            geofencingImpressions: parseFloat(flight.gf_impressions),
                            ownedAndOperatedBudget: parseFloat(flight.o_o_budget),
                            ownedAndOperatedImpressions: parseFloat(flight.o_o_impressions),
                            ownedAndOperatedForecastImpressions: parseFloat(flight.o_o_forecast_impressions),
                            forecast_status: flight.dfp_status,
                            regionId: flight.region_index
                        };
                    });
                });
            }

            if (product.cpms.ax !== undefined){
                product.cpms = {
                    audienceExtension: product.cpms.ax !== null ? UtilityService.toFloatOrReturnZero(product.cpms.ax) : 0,
                    geofencing: product.cpms.gf !== null ? UtilityService.toFloatOrReturnZero(product.cpms.gf) : 0,
                    ownedAndOperated: product.cpms.o_o !== null ? UtilityService.toFloatOrReturnZero(product.cpms.o_o) : 0
                }
            }

            // TODO: use actual order IDs data
	    product.orderIds = (customRegionsData.length > 0) ? [customRegionsData[0].o_o_ids] : [];

            product.creatives = product.creatives.map((creative) => {
                return {
                    id: `${creative.creative_status};${creative.creative_id}`,
                    creative_id: creative.creative_id,
                    text: creative.creative_name,
                    status: creative.creative_status,
                    landing_page: creative.landing_page,
                    regionId: parseInt(creative.region_id),
                    submitted_product_id: creative.submitted_product_id
                }
            });
        });

        return products;
    }

    mapOrderIdsByRegionId(customRegionsResponse: any[]){
        let regionOrderIdMap = new Object();
        for(let customRegion of customRegionsResponse){
            regionOrderIdMap[customRegion.id] = customRegion.o_o_ids;
        }
        return regionOrderIdMap;
    }

    mapFlightResponseToModel(flight){
        let _flight: any = {
            id: flight.id,
            audienceExtensionBudget: flight.ax_budget,
            audienceExtensionImpressions: flight.ax_impressions,
            geofencingBudget: flight.gf_budget,
            geofencingImpressions: flight.gf_impressions,
            ownedAndOperatedBudget: flight.o_o_budget,
            ownedAndOperatedImpressions: flight.o_o_impressions,
            regionId: flight.region_index
        };

        if (flight.total_budget !== undefined){
            _flight.totalBudget = flight.total_budget;
        }
        if (flight.start_date){
            _flight.startDate = flight.start_date;
        }
        if (flight.end_date){
            _flight.endDate = flight.end_date;
        }
        return _flight;
    }

    mapBuildFlightsDataToRequest(flightsConfig: BuildFlightsConfigurationModel, mpqId){
        let _flightsConfigObj: BuildFlightsRequestConfigurationModel = <BuildFlightsRequestConfigurationModel>{};
        _flightsConfigObj.start_date = flightsConfig.startDate;
        _flightsConfigObj.end_date = flightsConfig.endDate;
        _flightsConfigObj.flight_type = flightsConfig.flightType;
        _flightsConfigObj.pacing_type = flightsConfig.pacingType;
        _flightsConfigObj.budget = UtilityService.toIntOrReturnZero(flightsConfig.totalBudget);
        _flightsConfigObj.budget_allocation = flightsConfig.budgetAllocation;
        _flightsConfigObj.mpq_id = mpqId;
        _flightsConfigObj.product_id = flightsConfig.productId;
        _flightsConfigObj.region_id = flightsConfig.regionId;
        return _flightsConfigObj;
    }

    mapEditFlightToRequest(flightObject){
        return {
            budget: flightObject.budget,
            o_o_impressions: flightObject.ownedAndOperatedImpressions,
            flight_id: Array.isArray(flightObject.id) ? flightObject.id : [flightObject.id], 
            product_id: flightObject.productId,
            region_id: flightObject.regionId,
            budget_allocation: flightObject.budgetAllocation,
            mpq_id: flightObject.mpqId,
            editType: flightObject.editType
        }
    }

    mapEditFlightResponseToModel(response, product){
        if (response.total_flights){
            let flight = this.mapFlightResponseToModel(response.total_flights);
            let oldFlight = _.find(product.total_flights, function(oldFlight) { return _.isEqual(oldFlight.id, flight.id); });
            product.total_flights[_.indexOf(product.total_flights, oldFlight)] = _.extendOwn(oldFlight, flight);
        }

        if (response.flights){
            response.flights.forEach((flight) => {
                flight = this.mapFlightResponseToModel(flight);
                let flights = product.flights[parseInt(flight.regionId)];
                let oldFlight = _.findWhere(flights, { id: flight.id });
                product.flights[parseInt(flight.regionId)][_.indexOf(product.flights[parseInt(flight.regionId)], oldFlight)] = _.extendOwn(oldFlight, flight);
            });
        }

        return product;
    }

    mapAddFlightToRequest(flightObject){
        return {
            budget: flightObject.totalBudget,
            start_date: flightObject.startDate,
            end_date: flightObject.endDate,
            product_id: flightObject.productId,
            region_id: flightObject.regionId,
            budget_allocation: flightObject.budgetAllocation,
            mpq_id: flightObject.mpqId
        }
    }

    mapEditCPMToRequest(cpms, productId, regionId, budgetAllocation, mpqId){
    	let mappedCPMs = {};
    	for (let i in cpms){
    		if (cpms.hasOwnProperty(i) && cpms[i] !== 0 && cpms[i] !== null){
                    mappedCPMs[this.cpmMapping[i]] = cpms[i];
    		}
    	}
        return {
            cpm_value: mappedCPMs,
            product_id: productId,
            region_id: regionId,
            budget_allocation: budgetAllocation,
            mpq_id: mpqId
        };
    }

    mapBuildFlightsResponseToModel(response: any, productId, regionId){
        // TODO: return per-region flights
        let _totalFlightsObj = response.total_flights.map((flight) => {
            return {
                id: flight.id,
                startDate: flight.start_date,
                endDate: flight.end_date,
                totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
                audienceExtensionImpressions: UtilityService.toIntOrReturnZero(flight.ax_impressions),
                audienceExtensionBudget: UtilityService.toFloatOrReturnZero(flight.ax_budget),
                geofencingImpressions: UtilityService.toIntOrReturnZero(flight.gf_impressions),
                ownedAndOperatedBudget: UtilityService.toFloatOrReturnZero(flight.o_o_budget),
                ownedAndOperatedImpressions: UtilityService.toIntOrReturnZero(flight.o_o_impressions),
                forecast_status: flight.o_o_budget ? 'PENDING' : 'COMPLETE',
                productId: productId
            };
        });

        let _flightsObj = response.flights.map((flightGroup) => {
            return flightGroup.map((flight) => {
                return {
                    id: flight.id,
                    startDate: flight.start_date,
                    endDate: flight.end_date,
                    totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
                    audienceExtensionImpressions: UtilityService.toIntOrReturnZero(flight.ax_impressions),
                    audienceExtensionBudget: UtilityService.toFloatOrReturnZero(flight.ax_budget),
                    geofencingImpressions: UtilityService.toIntOrReturnZero(flight.gf_impressions),
                    ownedAndOperatedBudget: UtilityService.toFloatOrReturnZero(flight.o_o_budget),
                    ownedAndOperatedImpressions: UtilityService.toIntOrReturnZero(flight.o_o_impressions),
                    forecast_status: flight.o_o_budget ? 'PENDING' : 'COMPLETE',
                    productId: productId,
                    regionId: regionId
                }
            });
        });

        let _cpmsObj = response.cpms ? {
            audienceExtension: response.cpms.ax,
            geofencing: response.cpms.gf,
            geofencingMaxDollarPercentage: response.cpms.gf_max_dollar_pct,
            ownedAndOperated: response.cpms.o_o
        } : false;

        return {
            flights: _flightsObj,
            total_flights: _totalFlightsObj,
            cpms: _cpmsObj
        };
    }

    mapPollResponseToModel(response: any, flightId){
        return {
            id: flightId,
            audienceExtensionImpressions: UtilityService.toIntOrReturnZero(response.ax_impressions),
            audienceExtensionBudget: UtilityService.toFloatOrReturnZero(response.ax_budget),
            geofencingImpressions: UtilityService.toIntOrReturnZero(response.gf_impressions),
            ownedAndOperatedBudget: UtilityService.toFloatOrReturnZero(response.o_o_budget),
            ownedAndOperatedImpressions: UtilityService.toIntOrReturnZero(response.o_o_impressions),
            ownedAndOperatedForecastImpressions: UtilityService.toIntOrReturnZero(response.o_o_forecast_impressions),
            forecast_status: response.forecast_status
        }
    }

    mapResponseToCreatives(creatives: any) : Array<CreativeModel>{
        let _creatives = creatives.map((creative) => {
            return {
                id: `${creative.creative_status};${creative.creative_id}`,
                creative_id: creative.creative_id,
                text: creative.creative_name,
                status: creative.creative_status,
                landing_page: creative.landing_page,
                productId: creative.product_id,
                regionId: parseInt(creative.region_id),
                submitted_product_id: creative.submitted_product_id
            }
        });

        return _creatives;
    }

    mapSelect2ResponseToCreative(creative: any) : CreativeModel{
        return {
            id: creative.id,
            creative_id: creative.adset_id,
            text: creative.text,
            status: creative.id.charAt(0),
            landing_page: creative.landing_page,
            productId: null,
            regionId: null,
            submitted_product_id: null
        }
    }

    mapIODataToSubmission = (ioData : IOGetDataModel, validationStatus: ValidationStatusConfigModel) : IOModel =>{
        let ioModel: IOModel = <IOModel>{};

        let mapOpportunityData = () => {
            ioModel.advertiser_id = ioData.opportunity.advertiser.advertiserId
            ioModel.advertiser_name = ioData.opportunity.advertiser.advertiserName;
            ioModel.source_table = ioData.opportunity.advertiser.sourceTable;
            ioModel.website_name = ioData.opportunity.advertiserWebsite;
            ioModel.order_id = ioData.opportunity.orderId;
            ioModel.order_name = ioData.opportunity.orderName;
            ioModel.industry = ioData.opportunity.industry.industryId;
            ioModel.selected_user_id = ioData.opportunity.opportunityOwner.opportunityOwnerId;
        }

        let mapDemographicsData = () => {
            ioModel.demographics = ioData.demographics.reduce((demos, demo_group) => {
                    return demos.concat(demo_group.demographic_elements.reduce((demos, demo) => {
                        demos.push(demo.is_checked ? 1 : 0);
                        return demos;
                    }, []));
                }, []).join('_') + "_1_1_1_1_1_75_All_unusedstring";
        }

        let mapIABCategories = () => {
            ioModel.iab_categories = !ioData.audienceInterests ? "[]" : JSON.stringify(ioData.audienceInterests.map((interest) => {
                return interest.id;
            }));
        }

        let mapOldReferenceData = () => {
            ioModel.old_source_table = ioData.oldReference.oldSourceTable;
            ioModel.old_tracking_tag_file_id = ioData.oldReference.oldTrackingTagFileId;
        }

        let mapTrackingdata = () => {
            ioModel.tracking_tag_file_id = ioData.tracking.trackingTagFileId;
            ioModel.include_retargeting = ioData.tracking.includeReTargeting;
        }

        let mapNotesData = () => {
            ioModel.notes = ioData.notes;
        }

        let mapCustomRegionOrderId = () => {
            let customRegionOrderIds = ioData.customRegionData;
            let products = ioData.products;
            let productId = _.findWhere(products, {banner_intake_id : "Display"}).id;
            let customRegionData = new Object();
            let temp = new Object();
            for(let regionId in customRegionOrderIds){
                temp[regionId] = customRegionOrderIds[regionId].toString();
            }
            customRegionData[productId] = temp;
            ioModel.custom_region_data = customRegionData;

        }

        let mapIOValidationStatus = () => {
            let ioStatus : any= {};
            let sections: any[] = IO_VALIDATION;

            for(let section of sections){
                let temp : any= {};
                temp.status = validationStatus[section.id] ? CONSTANTS.IO.DONE : CONSTANTS.IO.NOT_STARTED;
                temp.active = false;
                temp.friendly = section.title;
                ioStatus[section.id] = temp;
            }
            ioModel.io_status = ioStatus;
        }

        mapOpportunityData();
        mapDemographicsData();
        mapIABCategories();
        mapOldReferenceData();
        mapTrackingdata();
        mapNotesData();
        mapCustomRegionOrderId()
        mapIOValidationStatus();

        return ioModel;
    }

}
