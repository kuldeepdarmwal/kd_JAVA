export interface OpportunityModel {
    opportunityOwner:OpportunityOwnerModel
    advertiser : AdvertiserModel
    advertiserWebsite:string
    orderName:string
    orderId:number
    industry:IndustryModel
}

export interface OpportunityOwnerModel {
    opportunityOwnerId:number
    opportunityOwnerName:string
    opportunityOwnerEmail:string
}

export interface IndustryModel {
    industryId:any
    industryName:string
}

export interface AdvertiserModel {
    advertiserId:number
    advertiserName:string
    externalId?:number
    status?:string
    sourceTable?:string
    userName?:string
    email?:string
    ulId?:number
    eclipseId?:number
}
