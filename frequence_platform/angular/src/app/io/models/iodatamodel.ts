import {Injectable} from "@angular/core";
import {ProductModel} from "./product.model";
import {CreativeModel} from "./creative.model";
import {IOMapperService} from "../services/iomapper.service";
import {OpportunityModel} from "./opportunity.model";
import {UtilityService} from "../../shared/services/utility.service";
import {FlightModel, CPMsModel} from "./flights.model";
import {TrackingModel, OldReferenceModel} from "./tracking.model";
import {CONSTANTS} from "../../shared/constants/builder.constants";
import {IOGetDataModel} from "./io.model";

/**
 * This model is used to hold the IO Data.
 */
declare var _:any;
@Injectable()
export class IODataModel {

    private _uniqueDisplayId : number;
    private _mpqId : number;
    private _isRFP : boolean;
    private _isNew : boolean;
    private _submitAllowed: boolean;
    private _option : any;
    private _products:ProductModel[];
    private _opportunity : OpportunityModel;
    private _demographics:any[];
    private _audienceInterests:any[];

    private _locations:any[];
    private _hasGeoFencing: boolean;
    private _geofencingDefaults: any;
    private _geofenceInventory: number;

    private _tracking: TrackingModel;
    private _oldReference : OldReferenceModel;

    private _flights: FlightModel[];
    private _cpms: CPMsModel[];

    private _notes : any;
    
    private _oAndOEnabledProducts : boolean;
    private _oAndODFPEnabledProducts : boolean;

    private _userId : any;
    private _customRegionData : any;

    constructor(private ioMapperService : IOMapperService){}

    get isNew():boolean {
        return this._isNew;
    }

    set isNew(value:boolean) {
        this._isNew = value;
    }

    get mpqId():number {
        return this._mpqId;
    }

    set mpqId(value:number) {
        this._mpqId = value;
    }

    get uniqueDisplayId() {
        return this._uniqueDisplayId;
    }

    set uniqueDisplayId(udId){
        this._uniqueDisplayId = udId;
    }

    checkUniqueDisplayId() {
        // return Observable.of(this._unique_display_id);
    }

    get submitAllowed():boolean {
        return this._submitAllowed;
    }

    set submitAllowed(value:boolean) {
        this._submitAllowed = value;
    }

    get option() {
        return this._option;
    }

    set option(option){
        if (option.grand_total_dollars) option.grand_total_dollars = UtilityService.toIntOrReturnZero(option.grand_total_dollars);
        this._option = option;
    }

    set products(products:ProductModel[]) {
        this._products = products;
    }

    get products(): ProductModel[] {
        return this._products;
    }

    get opportunity():OpportunityModel {
        return this._opportunity;
    }

    set opportunity(value:OpportunityModel) {
        this._opportunity = value;
    }

    get locations():any[] {
        return this._locations;
    }

    set locations(value:any[]) {
        this._locations = value;
    }

    get hasGeoFencing():boolean {
        return this._hasGeoFencing;
    }

    set hasGeoFencing(value:boolean) {
        this._hasGeoFencing = value;
    }

    get geofencingDefaults():any {
        return this._geofencingDefaults;
    }

    set geofencingDefaults(value:any) {
        this._geofencingDefaults = value;
    }

    get geofenceInventory():number {
        return this._geofenceInventory;
    }

    set geofenceInventory(value:number) {
        this._geofenceInventory = value;
    }

    get demographics():any[] {
        return this._demographics;
    }

    set demographics(value:any[]) {
        this._demographics = value;
    }

    get audienceInterests():any[] {
        return this._audienceInterests;
    }

    set audienceInterests(value:any[]) {
        this._audienceInterests = value;
    }

    get tracking():TrackingModel {
        return this._tracking;
    }

    set tracking(value:TrackingModel) {
        this._tracking = value;
    }

    get oldReference():OldReferenceModel {
        return this._oldReference;
    }

    set oldReference(value:OldReferenceModel) {
        this._oldReference = value;
    }

    get notes():any {
        return this._notes;
    }

    set notes(value:any) {
        this._notes = value;
    }

    get oAndOEnabledProducts():boolean {
        return this._oAndOEnabledProducts;
    }

    set oAndOEnabledProducts(value:boolean) {
        this._oAndOEnabledProducts = value;
    }

    get userId(): any {
        return this._userId;
    }

    set userId(id: any){
        this._userId = parseInt(id);
    }

    get customRegionData(): any {
        return this._customRegionData;
    }

    set customRegionData(value: any) {
        this._customRegionData = value;
    }

    get ioData() : IOGetDataModel{
        return {
            products : this.products,
            demographics : this.demographics,
            audienceInterests : this.audienceInterests,
            opportunity : this.opportunity,
            tracking : this.tracking,
            oldReference : this.oldReference,
            notes : this.notes,
            customRegionData : this.customRegionData,
            locations : this.locations
        };
    }

    set data(obj : any){
        //logic to load data
	this.products = this.ioMapperService.mapProductResponseToModel(obj.products, obj.custom_regions_data);
        this.uniqueDisplayId = obj.unique_display_id;
        this.mpqId = obj.mpq_id;
        this.submitAllowed = obj.io_submit_allowed;
        this.option = obj.option;
        this.opportunity = this.ioMapperService.mapResponseToOpportunity(obj);
        this.demographics = obj.demographics;
        this.audienceInterests = obj.iab_category_data;
        this.tracking = this.ioMapperService.mapTrackingResponseToModel(obj);
        this.oldReference = this.ioMapperService.mapOldReferenceResponseToModel(obj);
        this.notes = obj.notes;
        this.locations = obj.existing_locations;
        if (this.locations.length === 0) {
            this.locations.push(this.emptyLocation);
        }
        this.hasGeoFencing = obj.has_geofencing;
        this.geofenceInventory = obj.geofence_inventory;
        this.geofencingDefaults = !obj.has_geofencing ? {} : obj.geofencing_data;
       	this._oAndOEnabledProducts = obj.o_and_o_enabled_products;
        this._oAndODFPEnabledProducts = obj.o_and_o_dfp_enabled_products;
        this.userId = obj.user_id;
        this.customRegionData = this.ioMapperService.mapOrderIdsByRegionId(obj.custom_regions_data)
    }

    get hasDFPProducts(): boolean {
        return this._oAndODFPEnabledProducts;
    }

    get opportunityOwnerSelect2Format():any {
        return {
            id: this._opportunity.opportunityOwner.opportunityOwnerId,
            text: this._opportunity.opportunityOwner.opportunityOwnerName
        }
    }

    get industrySelect2Format():any {
        return {
            id: this._opportunity.industry.industryId,
            text: this._opportunity.industry.industryName
        }
    }

    get advertiserSelect2Format():any {
        return {
            id: this.opportunity.advertiser.advertiserId,
            text: this.opportunity.advertiser.advertiserName
        }
    }

    get trackingSelect2Format():any{
        return {
            id : this.tracking.trackingTagFileId,
            text : this.tracking.trackingTagFileName
        }
    }

    get emptyLocation():any {
        return {
            custom_regions: [],
            ids: {
                zcta: []
            },
            page: this.locations.length,
            search_type: "custom_regions",
            total: 0,
            user_supplied_name: "",
            selected: false,
            geofences: []
        }
    }

    get advertiserObjForDir(){
        return {
            adv_id : this.opportunity.advertiser.advertiserId,
            source_table : this.opportunity.advertiser.sourceTable
        }
    }

    convertResponseToadvertiserSelect2Format(response): any{
        var display_text = response.advertiser_name;
        if (response.source_table && response.source_table === CONSTANTS.IO.ADVERTISERS) {
            display_text = display_text + '&nbsp;<i class="material-icons io_icon_done" style="position:relative;top:7px;left:10px;margin-right:10px;">&#xE8E8;</i>';
        }
        return  {
            id: response.advertiser_id,
            text: display_text,
            adv_name: response.advertiser_name,
            source_table: response.source_table
        }
    }

    getTrackingObjectForCreatingFile(trackingTagFileName){
        return {
            io_advertiser_id : this.opportunity.advertiser.advertiserId,
            source_table : this.opportunity.advertiser.sourceTable,
            tracking_tag_file_name : trackingTagFileName
        }
    }
}
