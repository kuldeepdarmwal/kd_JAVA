import {ProductModel} from "./product.model";
import {OpportunityModel} from "./opportunity.model";
import {TrackingModel, OldReferenceModel} from "./tracking.model";
import {LocationModel} from "../../rfp/models/location.model";
export interface IOModel{
    dfp_advertiser_id: any;
    advertiser_id : number
    advertiser_name : string
    source_table : string
    website_name : string
    order_name : string
    order_id : number
    industry : number
    selected_user_id : number
    iab_categories : any
    demographics : string
    io_status : {}
    notes : string
    submission_type : string
    tracking_tag_file_id : number
    include_retargeting : boolean
    old_source_table : string
    old_tracking_tag_file_id : number
    mpq_id : number
    custom_region_data : any
}

export interface IOGetDataModel {
    products : ProductModel[]
    demographics : any[]
    audienceInterests : any[]
    opportunity : OpportunityModel
    tracking : TrackingModel
    oldReference : OldReferenceModel
    notes : string
    customRegionData : any
    locations : LocationModel[]
}