export interface RFPValidationStatusModel{
    audience: boolean
    tvzones: boolean
    geos: boolean
    rooftops: boolean
    budget: boolean
    sem: boolean
}

export interface TargetsValidationStatusModel{
    audience: boolean
    tvzones: boolean
    geos: boolean
    rooftops: boolean
    tv_scx_upload: boolean
    sem: boolean
}

export interface BudgetValidationStatusModel{
    budget: boolean;
}

export interface TargetsValidationConfigModel {
    rooftops: any
    tvzones : any
    interests : any
    tv_scx_upload : any
    geos : any
    sem : any
}

export interface BudgetValidationConfigModel {
    budget: any
}
