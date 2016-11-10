export interface BudgetOptionModel {
    id: number
    productId? : number
    name: string
    term: string
    duration: number
    discount: number
    discount_name: string
}

export interface OptionModel{
    optionId : number
    productId : number
    optionName : string
    term : string
    duration : any
    productType : string
    selected : boolean
    config? : any
    total? : any
    type? : any
    unit? : any
    cpm? : any
    discount? : any
    inventory? : any
    cpc? : any
    price? : any
    content? : any
    budget_allocation? : any
    geofence_dollars_total?: any
    impressions_total?: any
    geofence_impressions_total?: any
    geofence_cpm?: any
    vanilla_impressions_total?: any
    vanilla_dollars_total?: any
    convert_unit?: any
}
