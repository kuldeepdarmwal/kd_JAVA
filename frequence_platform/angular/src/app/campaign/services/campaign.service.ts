import 'rxjs/add/operator/map';
import 'rxjs/add/operator/toPromise';
import {Response, Http} from "@angular/http";
import {Injectable} from "@angular/core";
import {EmitterService} from "../../shared/services/emitter.service";
import {SERVICE_URL, EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {HTTPService} from "../../shared/services/http.service";
import {Observable} from "rxjs/Rx";
import {CampaignDataModel} from "../models/campaigndatamodel";


@Injectable()
export class CampaignsService extends HTTPService {

    constructor(http:Http,private campaignDataModel : CampaignDataModel) {
        super(http);
    }

    userBulkFlag() {
        return this.create(SERVICE_URL.CAMPAIGN.CHECK_USER_BULK_FLAG,'')
            .map((res) => res.json())
            .catch(this.handleError);
    }

    bulkDownloadSalesPeople(partnerId) {
	let partnerObj = {"selected_partner": partnerId}
        return this.create(SERVICE_URL.CAMPAIGN.GET_BULK_DOWNLOAD_SALES_PEOPLE,partnerObj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    getCreativeListOfCampaign(campaignId) {
	let Obj = {"c_id": campaignId}
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        return this.create(SERVICE_URL.CAMPAIGN.GET_CAMPAIGN_CREATIVE_LIST,Obj)
            .map((res) => res.json())
            .catch(this.handleError);
    }
    
    updateFavoriteUnfavorite(campaignId, reminderStatus){
        let Obj = {"campaign_id": campaignId, "reminder_status": reminderStatus}
        return this.create(SERVICE_URL.CAMPAIGN.UPDATE_REMINDER_STATUS,Obj)
            .map((res) => res.json())
            .catch(this.handleError);
    }
    
    createHelpdeskTicket(campaignId, subject, description){
        let Obj = {"subject": subject, "body": description, "campaign_id": campaignId}
        return this.create(SERVICE_URL.CAMPAIGN.CREATE_HELPDESK_TICKET,Obj)
            .map((res) => res.json())
            .catch(this.handleError);
    }
    
    showFlightsTableData(campaignId){
        let Obj = {"campaign_id": campaignId}
        return this.create(SERVICE_URL.CAMPAIGN.GET_FLIGHTS_DETAILS,Obj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    bulkDownload(bulkDownloadData, salesPersonId){
        this.userBulkFlag().subscribe((response) => {
            if(response.is_success === true && response.is_pending !== true) {
                bulkDownloadData.selected_sales_person = salesPersonId;
                this.startBulkDownload(bulkDownloadData).subscribe((response) => {});
            }
        });
    }
    
    startBulkDownload(bulkDownloadData){
        return this.create(SERVICE_URL.CAMPAIGN.GET_BULK_DOWNLOAD_DATA, bulkDownloadData)
            .map((res) => res.json())
            .catch(this.handleError);
    }
    
    private handleError(error:Response) {
        return Observable.throw(error.text);
    }
}
