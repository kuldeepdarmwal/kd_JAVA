export interface BulkDownloadModel{
    selected_partner : number
    start_date : any
    end_date : any
}

export interface CampaignListModel{
    id :  number
    name: string
    landingPageURL : string
    scheduleStatus : string
    orderId : string
    isReminder : number
    isGeofencing : number
    isGeofencingFlag: string
    advertiser : string
    partner : string
    allTimeStart : string
    allTimeEnd : string
    allTimeCampaign : CampaignProductModel
    allTimeAudienceExtension : CampaignProductModel
    allTimeOAndO : CampaignProductModel
    allTimeTotal: any
    allTimeAE : any
    allTimeOO : any
    thisFlightStart : string
    thisFlightEnd : string
    thisFlightCampaign : CampaignProductModel
    thisFlightAudienceExtension : CampaignProductModel
    thisFlightOAndO : CampaignProductModel
    thisFlightTotal: any
    thisFlightAE : any
    thisFlightOO : any
    
}

export interface CampaignProductModel{
    realized : number
    budget : number
    oti : number
    budgetImpression : number
    realizedImpression : number
}

export interface FlightsDataModel{
    startDate : any
    endDate : any
    totalBudget : number
    audienceimpressions : number
    audienceExtensionBudget : number
    ooimpressions : number
    oobudget : number
    geofencingimpressions : number
    geofencingbudget : number
}