import {Observable} from "rxjs/Observable";
import "rxjs/add/operator/map";
import "rxjs/add/operator/catch";
import {Response, Http} from "@angular/http";
import {Injectable} from "@angular/core";
import {SERVICE_URL} from "../../shared/constants/builder.constants";
import {HTTPService} from "../../shared/services/http.service";
import {IOModel} from "../models/io.model";
import {BuildFlightsRequestConfigurationModel} from "../models/flights.model";

@Injectable()
export class IOService extends HTTPService {

    constructor(http:Http) {
        super(http);
    }

    getIOData(uniqueDisplayId) {
        return this.query(`${SERVICE_URL.IO.GET_IO}${uniqueDisplayId}`, '')
            .map((res) => res.json())
            .catch(this.handleError)
    }

    createUnVerifiedAdvertiser(unverifiedAdvertiser) {
        return this.create(SERVICE_URL.IO.CREATE_UNVERIFIED_ADVERTISER, {unverified_advertiser : unverifiedAdvertiser})
            .map((res) => res.json())
            .catch(this.handleError)
    }

    getOrderSummary() {
        return this.create(SERVICE_URL.IO.SHOW_DFP_ORDER_SUMMARY,'')
            .map((res) => res.json())
            .catch(this.handleError)
    }

    createNewDfpAdvertiser(newDfpAdvertiser) {
        return this.create(SERVICE_URL.IO.CREATE_DFP_ADVERTISERS, {new_dfp_advertiser : newDfpAdvertiser})
            .map((res) => res.json())
            .catch(this.handleError)
    }

    deleteAllTimeseriesCreatives(mpqId){
        return this.create(SERVICE_URL.IO.DELETE_ALL_TIMESERIES_AND_CREATIVES, {mpq_id: mpqId})
            .catch(this.handleError);
    }

    getAdvertiserDirectoryName(advObj){
        return this.create(SERVICE_URL.IO.ADVERTISER_DIRECTORY_NAME, advObj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    toggleProduct(productId, selected, mpqId){
        return this.create(SERVICE_URL.IO.CHANGE_PRODUCTS, { product_id: productId, product_status: selected, mpq_id: mpqId })
            .catch(this.handleError);
    }

    createTrackingTagFile(trackingTagObj){
        return this.create(SERVICE_URL.IO.CREATE_TRACKING_TAG, trackingTagObj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    saveIO(ioData : IOModel) {
        return this.create(SERVICE_URL.IO.SAVE_IO, ioData)
            .map((res) => res.json())
            .catch(this.handleError)
    }

    processDFPAdvertisers(ioData : IOModel, dfpAdvertiserId) {
        ioData.dfp_advertiser_id = dfpAdvertiserId;
        return this.create(SERVICE_URL.IO.PROCESS_DFP_ADVERTISERS, ioData)
            .map((res) => res.json())
            .catch(this.handleError)
    }

    defineCreativesForProduct(productId, adsetIds, mpqId){
        return this.create(SERVICE_URL.IO.DEFINE_CREATIVES, {product_id: productId, adset_id: adsetIds, mpq_id: mpqId})
            .catch(this.handleError);
    }

    saveAdsetForProductGeo(productId, regionId, adsetIds, mpqId){
        return this.create(SERVICE_URL.IO.SAVE_ADSET, {product_id: productId, region_id: regionId, adset_id: adsetIds, mpq_id: mpqId})
            .catch(this.handleError);
    }

    buildFlights(flightsConfig: BuildFlightsRequestConfigurationModel){
        return this.create(SERVICE_URL.IO.FLIGHTS.BUILD, flightsConfig)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    removeFlights(flightId, productId, budgetAllocation, mpqId){
        if (typeof flightId === 'string') flightId = [flightId];
        return this.create(SERVICE_URL.IO.FLIGHTS.REMOVE, {
            flight_id: flightId,
            product_id: productId,
            budget_allocation: budgetAllocation,
            mpq_id: mpqId
        })
            .catch(this.handleError);
    }
    
    removeCampaignFlight(flightId, campaignId){
        if (typeof flightId === 'string') flightId = [flightId];
        return this.create(SERVICE_URL.IO.FLIGHTS.REMOVE, {
                    flight_id: flightId,
                    campaign_id: campaignId
                })
                .catch(this.handleError);
    }

    removeAllFlights(productId, regionId, mpqId){
        return this.create(SERVICE_URL.IO.FLIGHTS.REMOVE_ALL, { product_id: productId, region_id: regionId, mpq_id: mpqId })
            .catch(this.handleError);
    }
    
    removeAllCampaignFlights(campaignId){
        return this.create(SERVICE_URL.CAMPAIGN_SETUP.REMOVE_ALL, { campaign_id:campaignId })
                .catch(this.handleError);
    }

    reforecastFlights(productId, regionId, mpqId){
        return this.create(SERVICE_URL.IO.FLIGHTS.REFORECAST, {product_id: productId, region_id: regionId, mpq_id: mpqId})
            .catch(this.handleError);
    }

    editFlight(requestObject){
        return this.create(
            requestObject.editType === 'budget' ? SERVICE_URL.IO.FLIGHTS.EDIT_BUDGET : SERVICE_URL.IO.FLIGHTS.EDIT_OO_IMPRESSIONS,
            requestObject)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    addFlight(requestObject){
        return this.create(SERVICE_URL.IO.FLIGHTS.ADD, requestObject)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    editCPM(requestObject){
        return this.create(SERVICE_URL.IO.FLIGHTS.EDIT_CPM, requestObject)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    unlockSession(mpqId){
        return this.create(SERVICE_URL.IO.UNLOCK_SESSION, { mpq_id: mpqId })
            .catch(this.handleError);
    }

    preloadIO(productName, mpqId){
        return this.create(SERVICE_URL.IO.PRELOAD_FOR_CREATIVE_REQUEST, { mpq_id: mpqId, product: productName })
            .catch(this.handleError);
    }

    checkOandOForecastStatus(mpqId, oAndOEnabledProducts) {
        return this.create(SERVICE_URL.IO.CHECK_O_AND_O_STATUS, {mpq_id : mpqId, o_and_o_enabled : oAndOEnabledProducts})
            .map((res) => res.json())
            .catch(this.handleError)
    }

    saveOAndOOrderId(obj){
        return this.create(SERVICE_URL.IO.SAVE_IO_O_O, obj)
            .catch(this.handleError);
    }

    private handleError(error:Response) {
        return Observable.throw(error.text);
    }
}
