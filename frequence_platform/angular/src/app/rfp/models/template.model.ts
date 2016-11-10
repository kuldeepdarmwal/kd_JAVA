import {ProductConfigurationModel} from "./configuration.model";
export interface TemplateModel {
    id: number
    template: string
    default: boolean
    class: string
    weight: number
    repaint?: boolean
    loaded?: boolean
    selected: boolean
    templateHTML: any
    hasHTML?: boolean
    isGeneric?: boolean
    isStacked: boolean
    isNotDeletable : boolean
    categoryId : any
    config: ProductConfigurationModel
}

export interface IncludedFilesModel {
    id: number
    fileUrl: string
    fileType: string
    weight: number
    templateId: number
    partnerName: string
}

export interface TemplateConfig {
    isGeoDependent: boolean
    hasGeoFencing: boolean
    isAudienceDependent: boolean
    isRooftopsDependent: boolean
    isZonesDependent: boolean
    hasSCX: boolean
    isSEMDependent: boolean
    isStacked: boolean
}

export interface CategoryModel{
    id : number
    name : string
    weight : number
}