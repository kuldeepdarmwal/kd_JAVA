export interface TrackingModel{
    trackingTagFileId : number
    trackingTagFileName : string
    sourceTable : string
    includeReTargeting : boolean;
}

export interface OldReferenceModel{
    oldTrackingTagFileId : number
    oldIOAdvertiserId : number
    oldSourceTable : string
}