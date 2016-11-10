import {Component, ViewChild, Input, Output, EventEmitter} from "@angular/core";
import {CampaignsService} from "../services/campaign.service";
import "rxjs/add/operator/map";
import "rxjs/add/operator/toPromise";
import {EmitterService} from "../../shared/services/emitter.service";
import {EVENTEMITTERS, SERVICE_URL, PLACEHOLDERS} from "../../shared/constants/builder.constants";


declare var jQuery:any;
declare var moment:any;
declare var Materialize:any;


@Component({
    selector: 'view-creative',
    templateUrl: '/angular/build/app/views/campaign/view-creative.html'
})

export class ViewCreativeDataComponent{   

    private campainId:any;
    private campainName:any;
    public creativeObj:any;
    public creativeObjStatus:any;
    public creativeObjError:any;
    constructor(private campaignsService:CampaignsService) {}

    public openViewCreativeData(campainId, campainName) {
        this.campainId = campainId;
        this.campainName = campainName;
        this.openModal();        
        this.getCreativelist(this.campainId);
    }
    
    public openModal(){
        jQuery('#view_creative_modal').openModal();
    }
    
    public closeModal(){
        jQuery('#view_creative_modal').closeModal();
    }

    public getCreativelist(campaignId) {
        this.creativeObj = [];
        this.campaignsService.getCreativeListOfCampaign(campaignId)
            .subscribe((response) => {
                this.creativeObj = response.versions;
                this.creativeObjError = response.errors;
                this.creativeObjStatus = response.is_success;
                EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
            });
    }
}