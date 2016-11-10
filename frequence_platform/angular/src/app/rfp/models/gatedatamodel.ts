import {ProductModel} from "./product.model";

export interface GateModel {
    proposal_name : string
    owner_id : string
    industry_id : string
    strategy_id : string
    advertiser_name: string
    status? : any
}

export interface IndustryModel {
    industryId:any
    industryName:string
}

export interface OpportunityOwnerModel {
    opportunityOwnerId:number
    opportunityOwnerName:string
    opportunityOwnerEmail:string
}

export interface StrategyModel {
    strategyId : number
    strategyName : string
    products : ProductModel[]
    previewImage : string
    description : string
    selected : boolean,
    default_option_names?: any
    cost_per_unit_required : boolean
}
