import {Component, ViewChild, OnInit, HostListener, Output, EventEmitter, ElementRef} from "@angular/core";
import {ComponentInstruction, CanActivate} from "@angular/router-deprecated";
import {Subject} from "rxjs/Subject";
import {IODataModel} from "../models/iodatamodel";
import {ProductModel} from "../models/product.model";
import {CreativeModel} from "../models/creative.model";
import {HasIOData} from "../../rfp/services/has-rfp-data.service";
import {ProductSelectionComponent} from "./product-selection.component";
import {OpportunityComponent} from "./opportunity.component";
import {NotesComponent} from "./notes.component";
import {AudienceComponent} from "./audience.component";
import {FlightsComponent} from "./flights/flights.component";
import {FlightModel, CPMsModel} from "../models/flights.model";
import {CreativesComponent} from "./creatives.component";
import {OpportunityModel} from "../models/opportunity.model";
import {StatusComponent} from "./status.component";
import {LocationModel} from "../../rfp/models/location.model";
import {GoogleMapsService} from "../../shared/services/google-maps.service";
import {IOService} from "../services/io.service";
import {IOMapperService} from "../services/iomapper.service";
import {RFPService} from "../../rfp/services/rfp.service";
import {ValidationSwitchBoard} from "../services/validationswitch.service";
import {ValidationService} from "../services/validation.service";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";
import {TrackingComponent} from "./tracking.component";
import {TrackingModel, OldReferenceModel} from "../models/tracking.model";
import {CONSTANTS} from "../../shared/constants/builder.constants";
import {IOUtilityService, IOUtilityConfig} from "../services/io.utility.service";
import {Select2Directive} from "../../shared/directives/select2.directive";
import {IOPropertiesBuilder} from "../utils/io-propertiesbuilder.utility";
import {EmitterService} from "../../shared/services/emitter.service";
import {SERVICE_URL, EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {GeographiesComponent} from "../../rfp/components/targets/product-inputs/geographies.component";


declare var _:any;
declare var jQuery:any;
declare var Materialize:any;

@Component({
    selector: 'Io',
    templateUrl: '/angular/build/app/views/io.html',
    directives: [ProductSelectionComponent, AudienceComponent, OpportunityComponent,
        TrackingComponent, FlightsComponent, CreativesComponent, GeographiesComponent, NotesComponent, StatusComponent, Select2Directive],
    providers: [GoogleMapsService, ValidationSwitchBoard, ValidationService],
})
@CanActivate(
    (next:ComponentInstruction, prev:ComponentInstruction) => {
        return HasIOData(next, prev);
    }
)
export class IOComponent implements OnInit {
    private submitAllowed: boolean;
    private option: any;
    private products: ProductModel[];
    private _opportunityObj : OpportunityModel;
    private _notesObj : any;
    private demographics:any[];
    private audienceInterests:any[];
    private tracking : TrackingModel;
    private oldReferenceData : OldReferenceModel;
    private flights : Array<FlightModel>;
    private cpms : Array<CPMsModel>;
    private creatives:CreativeModel[];

    private locations:any[];
    private geofencingDefaults: any;
    private hasGeoFencing: boolean;
    private hasGeofences: boolean;
    private hasOandOEnable: boolean;
    private customRegionOrderIds :  any;

    private userId: any;
    private mpqId;

    private confirmTimeseriesDelete: Subject<boolean>;
    //private confirmTimeseriesDelete$: Observable<boolean>;

    private validated: boolean;
    private validationStatus: ValidationStatusConfigModel = <ValidationStatusConfigModel>{};

    @ViewChild(GeographiesComponent) geographyChild:GeographiesComponent;
    @ViewChild(FlightsComponent) flightsChild:FlightsComponent;
    @ViewChild(CreativesComponent) creativeChild:CreativesComponent;
    @ViewChild(TrackingComponent) trackingChild : TrackingComponent;
    @ViewChild('dfpadvertiser') newdfpadvertiserElem:ElementRef;

    private dfpAdvertisersObj:any = {};
    private enableNewDfpAdvertiser = false;
    private newDfpAdvertiser = "";
    public dfpOrderSummaryObj:any=[];
    private dfpadvertiserSelected;

    constructor(private ioDataModel: IODataModel,
                private ioService: IOService,
                private ioMapper: IOMapperService,
                private rfpService:RFPService,
                private googleMapsService:GoogleMapsService,
                private validationService:ValidationService,
                private ioUtilityService : IOUtilityService,
                private ioPropertiesBuilder:IOPropertiesBuilder){
        this.loadIOData();
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        this.dfpAdvertisersObj = this.ioPropertiesBuilder._buildPropertiesForDFPAdvertiser();
        this.setEventSubscribers();

        let self = this;
        window.onbeforeunload = function(e) {
            self.unlockSession();
        };
    }

    ngOnInit(){
        this.validationStatus = this.validateProposal();
    }

    unlockSession(){
        this.ioService.unlockSession(this.ioDataModel.mpqId)
            .subscribe((res) => { return true; });
    }

    setEventSubscribers() {
     EmitterService.get(EVENTEMITTERS.IO.DFP_ADVERTISERS)
        .subscribe(obj => {
            this.checkDfpAdvertiserSelection(obj);
        });
    }

    checkDfpAdvertiserSelection(eventObj) {
        if (eventObj.id == CONSTANTS.IO.NEW_DFP_ADVERTISER) {
            this.enableNewDfpAdvertiser = true;
        } else {
            this.enableNewDfpAdvertiser = false;
            eventObj.isNew  = false;
            this.dfpadvertiserSelected = eventObj.id;
        }
    }

    createDfpAdvertiser() {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        if (this.newDfpAdvertiser && this.newDfpAdvertiser.length > 0) {
            this.ioService.createNewDfpAdvertiser(this.newDfpAdvertiser)
                .subscribe((response) => {
                    this.populateDfpAdvertiser(response)
                });
        }
    }

    populateDfpAdvertiser(response) {
        jQuery(this.newdfpadvertiserElem.nativeElement).select2('data',
            this.convertResponseToDfpadvertiserSelect2Format(response));
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        this.enableNewDfpAdvertiser = false;
        response.isNew = true;
        this.dfpadvertiserSelected = response.new_advertiser_id;
    }

    convertResponseToDfpadvertiserSelect2Format(response): any{
        var display_text = response.new_advertiser_name;
        return  {
            id: response.new_advertiser_id,
            text: display_text,
        }
    }

    loadOrderSummary() {
        this.ioService.getOrderSummary()
            .subscribe((response) => {
                EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
                this.dfpOrderSummaryObj = response;
            });
    }

    loadIOData(){
        this.submitAllowed = this.ioDataModel.submitAllowed;
        this.option = this.ioDataModel.option;
        this.products = this.ioDataModel.products;
        this._opportunityObj = this.ioDataModel.opportunity;
        this._notesObj = this.ioDataModel.notes;
        this.demographics = this.ioDataModel.demographics;
        this.audienceInterests = this.ioDataModel.audienceInterests;
        this.tracking = this.ioDataModel.tracking;
        this.oldReferenceData = this.ioDataModel.oldReference;
        this.locations = this.ioDataModel.locations;
        this.hasGeoFencing = this.ioDataModel.hasGeoFencing;
        this.geofencingDefaults = this.ioDataModel.geofencingDefaults;
        this.hasGeofences = this.tallyGeofences();
        this.userId = this.ioDataModel.userId;
    	this.hasOandOEnable = this.ioDataModel.oAndOEnabledProducts;
        this.customRegionOrderIds = this.ioDataModel.customRegionData;
        this.mpqId = this.ioDataModel.mpqId;
    }

    loadRegions(selectedRegion:LocationModel) {
        switch (selectedRegion.search_type) {
            case "custom_regions":
                this.rfpService.getZipsFromCustomRegions(selectedRegion, this.ioDataModel.mpqId)
                    .subscribe((regions) => {
                        if (regions.length > 0) {
                            let zips_array = [];
                            regions.forEach((region) => {
                                let zips = JSON.parse(region.regions);
                                zips.forEach((zip) => {
                                    if (zips_array.indexOf(zip) === -1) {
                                        zips_array.push(zip);
                                    }
                                });
                            });
                            zips_array = zips_array.concat(selectedRegion.affected_regions);
                            selectedRegion.ids.zcta = zips_array;
                            this.rfpService.saveZips(selectedRegion, this.ioDataModel.mpqId)
                                .subscribe((res) => this.mapSaveZipsResponse(selectedRegion, res));
                        } else {
                            Materialize.toast('No regions were found for your search. Please try again.', 3000, 'error-toast');
                        }
                    });
                break;
            case "radius":
                this.rfpService.removeCustomRegions(selectedRegion.page, this.ioDataModel.mpqId)
                    .subscribe((res) => {
                        this.googleMapsService.getCoords(selectedRegion.address)
                            .then((result:any) => {
                                this.rfpService.saveZipsFromRadius(
                                    selectedRegion.page,
                                    selectedRegion.counter,
                                    selectedRegion.address,
                                    result.geometry.location.lat(),
                                    result.geometry.location.lng(),
                                    this.ioDataModel.mpqId)
                                    .subscribe((res) => {
                                        selectedRegion.ids.zcta = selectedRegion.ids.zcta.concat(selectedRegion.affected_regions);
                                        this.mapSaveZipsResponse(selectedRegion, res);
                                    });
                            }, (err) => {
                                console.error(err);
                            });
                    });
                break;
            case "known_zips":
                this.rfpService.removeCustomRegions(selectedRegion.page, this.ioDataModel.mpqId)
                    .subscribe((res) => {
                        selectedRegion.ids.zcta = selectedRegion.ids.zcta.concat(selectedRegion.affected_regions);
                        this.rfpService.saveZips(selectedRegion, this.ioDataModel.mpqId)
                            .subscribe((res) => this.mapSaveZipsResponse(selectedRegion, res));
                    });
                break;
        }
    }

    mapSaveZipsResponse(selectedRegion:LocationModel, response) {
        this.locations[selectedRegion.page].user_supplied_name = response.custom_location_name;
        this.locations[selectedRegion.page].ids.zcta = response.zips;
        this.locations[selectedRegion.page].total = response.zips.length;
        (<HTMLIFrameElement> document.querySelector('#region-links iframe')).contentWindow.location.reload(true);
        this.validationStatus = this.validateProposal();
    }

    toggleProduct(product: ProductModel){
        let _product: ProductModel = _.findWhere(this.products, {id: product.id});
        _product.selected = !_product.selected;
        this.ioService.toggleProduct(_product.id, _product.selected, this.ioDataModel.mpqId)
            .subscribe((res) => {});
    }

    opportunityOwnerSelected(oppOwnerObj: any){
        this._opportunityObj.opportunityOwner.opportunityOwnerEmail = oppOwnerObj.email;
        this._opportunityObj.opportunityOwner.opportunityOwnerId = oppOwnerObj.id;
        this._opportunityObj.opportunityOwner.opportunityOwnerName = oppOwnerObj.text;
    }

    advertiserSelected(advObj){
        this.updateAdvOldReferenceData();
        if(!advObj.isNew){
            this._opportunityObj.advertiser.advertiserId = advObj.id;
            this._opportunityObj.advertiser.advertiserName = advObj.adv_name;
            this._opportunityObj.advertiser.eclipseId = advObj.eclipse_id;
            this._opportunityObj.advertiser.email = advObj.email;
            this._opportunityObj.advertiser.externalId = advObj.external_id;
            this._opportunityObj.advertiser.sourceTable = advObj.source_table;
            this._opportunityObj.advertiser.ulId = advObj.ul_id;
            this._opportunityObj.advertiser.userName = advObj.user_name;
        }else{
            this._opportunityObj.advertiser.advertiserId = advObj.advertiser_id;
            this._opportunityObj.advertiser.advertiserName = advObj.advertiser_name;
            this._opportunityObj.advertiser.sourceTable = advObj.source_table;
        }
        this.mapTrackingTagToAdvIfPrevUnVerified();
    }

    updateAdvOldReferenceData(){
        this.oldReferenceData.oldIOAdvertiserId = this._opportunityObj.advertiser.advertiserId;
        this.oldReferenceData.oldSourceTable = this._opportunityObj.advertiser.sourceTable;
    }

    updateTrackingOldReferenceData(){
        this.oldReferenceData.oldTrackingTagFileId = this.tracking.trackingTagFileId;
    }

    mapTrackingTagToAdvIfPrevUnVerified(){
        if(this.oldReferenceData.oldSourceTable == CONSTANTS.IO.UNVERIFIED_ADVERTISERS &&
            this._opportunityObj.advertiser.sourceTable == CONSTANTS.IO.ADVERTISERS){
            this.saveIO();
        }else {
            this.trackingChild.resetTrackingTagOptions();
	    this.ioDataModel.tracking.trackingTagFileName = "";
	    this.ioDataModel.tracking.trackingTagFileId = -1;
	    this.validationStatus = this.validateProposal();
        }
    }

    advertiserIndustrySelected(advIndObj){
        this._opportunityObj.industry.industryId = advIndObj.id;
        this._opportunityObj.industry.industryName = advIndObj.text;
    }

    trackingTagSelected(trackingTagObj){
        this.updateTrackingOldReferenceData();
        if(trackingTagObj.isNew){
            this.tracking.trackingTagFileId = trackingTagObj.id;
            this.tracking.trackingTagFileName = trackingTagObj.name;
        }else{
            this.tracking.trackingTagFileId = trackingTagObj.id;
            this.tracking.trackingTagFileName = trackingTagObj.text;
        }
    }

    addLocation() {
        let subscription = this.confirmRemoveAllTimeseriesCreatives();
        subscription.subscribe(() => {
            let newLocation:LocationModel = this.ioDataModel.emptyLocation;
            this.rfpService.addLocation(newLocation, this.ioDataModel.mpqId)
                .subscribe((res) => {
                    this.locations.push(newLocation);
                    this.geographyChild.cloneGeofences(this.locations.length - 1);
                    this.geographyChild.selectLocation(this.locations.length - 1);
                    this.validationStatus = this.validateProposal();
                    this.removeAllTimeseriesCreatives()
                    this.hasGeofences = this.tallyGeofences();
                });
        });
    }

    removeLocation(location_id:number) {
        let subscription = this.confirmRemoveAllTimeseriesCreatives();
        subscription.subscribe(() => {
            this.rfpService.removeLocation(location_id, this.ioDataModel.mpqId)
                .subscribe((res) => {
                    this.locations.splice(location_id, 1);

                    this.locations.forEach((location, i) => { location.page = i; });

                    this.rfpService.saveGeofences({mpq_id: this.ioDataModel.mpqId, location_id: location_id, geofences: false})
                        .subscribe((res) => {
                            this.geographyChild.removeGeofence(location_id);
                        });

                    if (this.geographyChild.selectedLocationId === location_id) {
                        this.geographyChild.selectLocation(0);
                    }

                    this.removeAllTimeseriesCreatives()

                    this.validationStatus = this.validateProposal();
                    this.hasGeofences = this.tallyGeofences();
                });
        });
    }

    selectLocation(index:number) {
        if (this.locations[index] !== undefined) {
            this.locations.forEach((location) => {
                location.selected = false;
            });
            this.locations[index].selected = true;
        }
    }

    updateCustomRegions(location:LocationModel) {
        this.locations[location.page].custom_regions = location.custom_regions;
    }

    uploadBulkLocations(locations:Array<any>) {
        // Assuming it's a radius-based entry here, because population-based and both no longer work.
        // TODO: get population-based uploads working or remove those options.
        if (locations.length > 0) {
            let new_locations = [];

            locations.forEach((new_location) => {
                let location = this.ioDataModel.emptyLocation;
                location.ids = { zcta: new_location.regions.split(', ') };
                location.page = new_location.location_id;
                location.search_type = "radius";
                location.selected = false;
                location.total = location.ids.zcta.length;
                location.user_supplied_name = new_location.location_name;
                location.counter = new_location.geo_dropdown_options.radius;
                location.address = new_location.geo_dropdown_options.address;
                new_locations.push(location);
            });

            if (this.locations.length === 1 && this.locations[0].ids.zcta.length === 0) {
                this.locations = new_locations;
            } else {
                this.locations.concat(new_locations);
            }

            this.removeAllTimeseriesCreatives()

            this.geographyChild.selectLocation(this.locations.length - 1);
        }
    }

    saveGeofences(location_id: number) {
        let subscription = this.confirmRemoveAllTimeseriesCreatives();
        subscription.subscribe(() => {
            let geofencesObj = {
                geofences: _.map(this.locations[location_id].geofences, (geofence) => {
                    return {
                        search: geofence.address,
                        latlng: geofence.latlng,
                        radius: geofence.type === "proximity" ? geofence.proximity_radius : this.geofencingDefaults.radius.CONQUESTING,
                        type: geofence.type
                    }
                }),
                mpq_id: this.ioDataModel.mpqId,
                location_id: location_id
            }

            if (geofencesObj.geofences.length === 0) {
                geofencesObj.geofences = false;
            }

            this.rfpService.saveGeofences(geofencesObj)
                .subscribe((res: any) => {
                    this.geographyChild.closeModal(location_id);
                    if (res.missing_geofence_regions !== undefined && res.missing_geofence_regions.length > 0) {
                        let missing_zips = res.missing_geofence_regions.join(", ");
                        Materialize.toast('The following zips were added to your location:<br />' + missing_zips, 5000, 'toast-primary');
                        this.locations[location_id].ids.zcta = this.locations[location_id].ids.zcta.concat(res.missing_geofence_regions);
                        this.locations[location_id].search_type = 'known_zips';
                    }
                    if (res.affected_regions !== undefined && res.affected_regions.length > 0) {
                        this.locations[location_id].affected_regions = res.affected_regions;
                    }
                    if (res.geofence_inventory !== undefined) {
                        this.ioDataModel.geofenceInventory = res.geofence_inventory;
                    }
                    (<HTMLIFrameElement>document.querySelector('#region-links iframe')).contentWindow.location.reload(true);
                    this.geographyChild.cloneGeofences(location_id);

                    this.removeAllTimeseriesCreatives()

                    this.hasGeofences = this.tallyGeofences();
                });
        });
    }

    tallyGeofences(){
        return this.locations.reduce((carry, location) => {
            return location.geofences.length > 0 ? true : carry;
        }, false);
    }

    audienceInterestsSelected(audienceInterestsObj){
        this.ioDataModel.audienceInterests = audienceInterestsObj;
    }

    confirmRemoveAllTimeseriesCreatives(){
        this.confirmTimeseriesDelete = new Subject<boolean>();
        let confirmTimeseriesDelete$ = this.confirmTimeseriesDelete.asObservable();

        jQuery('#io_confirm_timeseries_delete_modal').openModal();

        return confirmTimeseriesDelete$;
    }

    removeAllTimeseriesCreatives(){
        this.ioService.deleteAllTimeseriesCreatives(this.ioDataModel.mpqId)
            .subscribe((res) => {
                this.products.forEach((product) => {
                    product.flights = [];
                    product.total_flights = [];
                    product.creatives = [];
                });
                jQuery('#io_confirm_timeseries_delete_modal').closeModal();
            });
    }

    buildFlights(flightsConfig){
        this.ioService.buildFlights(this.ioMapper.mapBuildFlightsDataToRequest(flightsConfig, this.ioDataModel.mpqId))
            .subscribe((res) => {
                this.updateFlightsView(res, flightsConfig.productId, flightsConfig.regionId);
                this.validationStatus = this.validateProposal();
            });
    }

    editCPM(CPMObject){
        this.ioService.editCPM(this.ioMapper.mapEditCPMToRequest(
            CPMObject.cpms,
            CPMObject.productId,
            CPMObject.regionId,
            CPMObject.budgetAllocation,
            this.ioDataModel.mpqId))
            .subscribe((res) => {
                this.updateFlightsView(res, CPMObject.productId, CPMObject.regionId);
                this.validationStatus = this.validateProposal();
            });
    }

    updateFlightsView(response, productId, regionId){
        response = this.ioMapper.mapBuildFlightsResponseToModel(response, productId, regionId);
        let product = _.findWhere(this.products, {id: productId});
        if (regionId !== null){
            product.flights[regionId] = response.flights[0];
        } else {
            product.flights = response.flights;
        }
        product.total_flights = response.total_flights;
        if (response.cpms) product.cpms = response.cpms;
    }

    editFlight(flight){
        flight.budgetAllocation = _.findWhere(this.products, { id: flight.productId }).budget_allocation;
        flight.mpqId = this.ioDataModel.mpqId;
        this.ioService.editFlight(this.ioMapper.mapEditFlightToRequest(flight))
            .subscribe((res:any) => {
                if (res.is_success === false){
                    Materialize.toast(res.errors, 5000, 'error-toast');
                } else {
                    let product = _.findWhere(this.products, { id: flight.productId });
                    product = _.extendOwn(product, this.ioMapper.mapEditFlightResponseToModel(res, product));
                }
            });
    }

    updateFlight(flightData){
        let flight = {};
        let product = _.findWhere(this.products, {id: flightData.productId});
        if (flightData.regionId === null){
            flight = _.findWhere(product.total_flights, {id: flightData.flight.id});
        } else {
            flight = _.findWhere(product.flights[flightData.regionId], {id: flightData.flight.id});
        }
        flight = _.extendOwn(flight, flightData.flight);
    }

    addFlight(flightData){
        flightData.mpqId = this.ioDataModel.mpqId;
        this.ioService.addFlight(this.ioMapper.mapAddFlightToRequest(flightData))
            .subscribe((res) => {
                let product = _.findWhere(this.products, { id: flightData.productId });
                if (res.flights){
                    res.flights.forEach((flight, i) => {
                        product.flights[i].push(this.ioMapper.mapFlightResponseToModel(flight[0]));
                    });
                }
                if (res.total_flights){
                    let newFlight = this.ioMapper.mapFlightResponseToModel(res.total_flights[0]);
                    if (newFlight.totalBudget === undefined) newFlight.totalBudget = flightData.totalBudget;
                    product.total_flights.push(newFlight);
                } else {
                    product.total_flights = [];
                }

                this.validationStatus = this.validateProposal();
            });
    }

    deleteFlight(flightConfig){
        let budget_allocation = _.findWhere(this.products, {id: flightConfig.productId}).budget_allocation;
        this.ioService.removeFlights(flightConfig.flightId, flightConfig.productId, budget_allocation, this.ioDataModel.mpqId)
            .subscribe((res) => {
                let product = _.findWhere(this.products, {id: flightConfig.productId})
                product.flights = product.flights.map((timeseries) => {
                    return timeseries.filter((flight) => {
                        return typeof flightConfig.flightId === 'string' ? flight.id != flightConfig.flightId : flightConfig.flightId.indexOf(flight.id) === -1;
                    });
                })
                product.total_flights = budget_allocation === 'custom' ? [] : _.without(product.total_flights, _.findWhere(product.total_flights, { id: flightConfig.flightId }));
                this.validationStatus = this.validateProposal();
            });
    }

    deleteAllFlights(flightConfig){
        this.ioService.removeAllFlights(flightConfig.productId, flightConfig.regionId, this.ioDataModel.mpqId)
            .subscribe((res) => {
                let product = _.findWhere(this.products, { id: flightConfig.productId });
                product.total_flights = [];

                if (flightConfig.regionId !== null){
                    product.flights[flightConfig.regionId] = [];
                } else {
                    product.flights = [];
                }

                this.validationStatus = this.validateProposal();
            });
    }

    reforecastFlights(configObj){
        this.ioService.reforecastFlights(configObj.productId, configObj.regionId, this.ioDataModel.mpqId)
            .subscribe((res) => {
                let product = _.findWhere(this.products, { id: configObj.productId });
                product.total_flights.forEach((flight) => {
                    flight.forecast_status = 'PENDING';
                });
            });
    }

    tallyFlights(){
        return this.products.reduce((carry, product) => {
            return product.total_flights.length > 0 ? true : carry;
        }, false);
    }

    updateCreatives(creativesObj){
        let adsetIds = _.uniq(creativesObj.updatedCreatives.reduce((adsets, creative) => {
            adsets.push(creative.id);
            return adsets;
        }, []));

        let product = _.findWhere(this.products, { id: creativesObj.product });

        if (creativesObj.region === undefined){
            this.ioService.defineCreativesForProduct(creativesObj.product, adsetIds, this.ioDataModel.mpqId)
                .subscribe((res) => {
                    product.creatives = creativesObj.allCreatives;
                    this.creativeChild.closeModal();
                    this.validationStatus = this.validateProposal();
                });
        } else {
            let query = this.ioService.saveAdsetForProductGeo(creativesObj.product, creativesObj.region, adsetIds, this.ioDataModel.mpqId)
                .subscribe((res) => {
                    product.creatives = creativesObj.allCreatives;
                    this.creativeChild.closeModal();
                    this.validationStatus = this.validateProposal();
                });
        }
    }

    newCreativeRequest(productName: any){
        this.saveIO();
        this.ioService.preloadIO(productName, this.ioDataModel.mpqId)
            .subscribe(() => {
                window.location.href = "/"+SERVICE_URL.CREATIVE_REQUEST;
            });
    }

    validateProposal() {
        let validationMap = this.validationService._startValidation;
        this.validated = this.validationService.validationStatus;
        return validationMap;
    }

    saveIO(){
        this.ioUtilityService.process(this.buildIOUtilityConfig(CONSTANTS.IO.SAVE, false),'');
        this.validationStatus = this.validateProposal();
    }

    openDFPAdvertisersModal(){
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        jQuery('#dfp_advertisers_modal').openModal();
    }

    submitIO(){
        if (this.ioDataModel.hasDFPProducts){
            this.openDFPAdvertisersModal();
            this.loadOrderSummary();
        } else {
            this.ioUtilityService.process(this.buildIOUtilityConfig(CONSTANTS.IO.SUBMIT, true),'');
        }
    }

    submitDFP(){
        this.ioUtilityService.process(this.buildIOUtilityConfig(CONSTANTS.IO.SUBMIT, true), this.dfpadvertiserSelected);
    }

    reviewIO(){
        this.ioUtilityService.process(this.buildIOUtilityConfig(CONSTANTS.IO.SUBMIT_FOR_REVIEW, true),'');
    }

    buildIOUtilityConfig(submissionType: string, shouldNavigate: boolean){
        let ioUtilityConfig = <IOUtilityConfig>{}
        ioUtilityConfig.validateFn = this.validationService._startValidation;
        ioUtilityConfig.validateStatusFn = this.validationService.validationStatus;
        ioUtilityConfig.submissionType = submissionType;
        ioUtilityConfig.allowNavigation = shouldNavigate
        return ioUtilityConfig;
    }
}
