import {Injectable} from "@angular/core";
import {BulkDownloadModel, CampaignListModel} from "./bulk-download.model";
import {CampaignMapperService} from "../services/campaignmapper.service";

/**
 * This model is used to hold the Campaign Data.
 */
declare var _:any;
@Injectable()
export class CampaignDataModel {

    private _selectedPartner : number;
    private _bulkDownload : BulkDownloadModel;


    private _campaigns : CampaignListModel[];
    private _partnerId : any;
    private _reportDate : any;

    constructor(private campaignMapperService : CampaignMapperService){}

    get selectedPartner():number {
        return this._selectedPartner;
    }

    set selectedPartner(value:number) {
        this._selectedPartner = value;
    }

    get bulkDownload():BulkDownloadModel {
        return this._bulkDownload;
    }

    set bulkDownload(value:BulkDownloadModel) {
        this._bulkDownload = value;
    }

    get campaigns():CampaignListModel[] {
        return this._campaigns;
    }

    set campaigns(value:CampaignListModel[]) {
        this._campaigns = value;
    }

    get partnerId():any {
        return this._partnerId;
    }

    set partnerId(value:any) {
        this._partnerId = value;
    }
    
    get reportDate():any {
        return this._reportDate;
    }

    set reportDate(value:any) {
        this._reportDate = value;
    }

    set data(obj : any){
        this.selectedPartner = obj.selectedPartner;
    }

    set responseData(response : any){
        this.campaigns = this.campaignMapperService.mapCampaignsResponseToModel(response);
        this.partnerId = this.campaignMapperService.partnerId;
        this.reportDate = response.report_date;
    }
    
    set updateCampaignListData(isReminderObj:any){
        this.campaigns = this.campaignMapperService.updateMapCampaignsResponseToModel(this.campaigns, isReminderObj);
    }    
}
