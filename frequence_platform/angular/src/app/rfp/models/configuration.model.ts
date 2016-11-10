export interface ProductConfigurationModel {
    showTvComponent: boolean
    showTvUploadComponent: boolean
    showGeoComponent: boolean
    showGeofencingComponent: boolean
    showAudienceComponent: boolean
    showRoofTopsComponent: boolean
    showPoliticalComponent: boolean
    showSEMComponent: boolean
    showBothGeoAudience?: boolean
}

export interface ProductNamesModel {
    audience: string[]
    geos: string[]
    tvzones: string[]
    tv_upload: string[]
    rooftops: string[]
    political: string[]
    keywords: string[]
}

export interface RFPValidationStatusModel {
    audience: boolean
    tvzones: boolean
    tv_scx_upload: boolean
    geos: boolean
    rooftops: boolean
    budget: boolean
    sem: boolean
}