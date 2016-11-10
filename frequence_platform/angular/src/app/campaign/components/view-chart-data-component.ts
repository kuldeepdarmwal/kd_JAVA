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
    selector: 'view-chart',
    templateUrl: '/angular/build/app/views/campaign/view-chart.html'
})

export class ViewChartDataComponent{   
    public campaignName:any;
    public modalDetailBody:any;
    public mainUrl:any;

    constructor(private campaignsService:CampaignsService, private window: Window) {}

    public openViewChartData(campaignId, reportDate, campaignName) {    
        this.openModal();
        this.campaignName = campaignName;
        this.mainUrl = 'http://' + this.window.location.hostname + '/campaigns_main/chart?cid='+campaignId+'&reportDate='+reportDate;
    }
    
    public openModal(){
        jQuery('#view_chart_modal').openModal();
    }
    
    public closeModal(){
        jQuery('#view_chart_modal').closeModal();
    }
}