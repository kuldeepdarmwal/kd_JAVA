import {Component, ViewChild, Input, Output, EventEmitter} from "@angular/core";
import "rxjs/add/operator/map";
import "rxjs/add/operator/toPromise";
import {EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {CampaignsService} from "../services/campaign.service";
import {EmitterService} from "../../shared/services/emitter.service";
import {CampaignMapperService} from "../services/campaignmapper.service";
import {NumberFormat} from "../../shared/pipes/number_format.pipe";

declare var jQuery:any;
declare var moment:any;
declare var Materialize:any;

@Component({
    selector: 'view-flights-table',
    templateUrl: '/angular/build/app/views/campaign/view-flights-table.html',
    pipes: [NumberFormat]
})

export class ViewFlightsTableComponent{   
    private flightTable:any;
    public reportDate;
    public enableImpressionsCol;
    public enableBudgetCol;
    public enableAudienceExtImpressionsCol;
    public enableAudienceExtBudgetCol;
    public enableGeofencingBudgetCol;
    public enableGeofencingImpressionsCol;
    public enableooImpressionsCol;
    public enableooBudgetCol;
    public isPrerollCampaign;

    constructor(private campaignsService: CampaignsService, private campaignMapperService : CampaignMapperService) {}

    public openFlightsTable(id,report_Date) {
        this.openModal();
	this.reportDate = report_Date;
        this.flightTable = [];
        this.showFlightsTable(id);
    }
    
    public showFlightsTable(campaign_id){
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        this.campaignsService.showFlightsTableData(campaign_id)
            .subscribe((response) => {
                this.flightTable = this.campaignMapperService.mapCampaignsFlightsResponseToModel(response.results);
                this.enableImpressionsCol = response.col.enable_impressions_col;
                this.enableBudgetCol = response.col.enable_budget_col;
                this.enableAudienceExtImpressionsCol = response.col.enable_audience_ext_impressions_col;
                this.enableAudienceExtBudgetCol = response.col.enable_audience_ext_budget_col;
                this.enableGeofencingBudgetCol = response.col.enable_geofencing_budget_col;
                this.enableGeofencingImpressionsCol = response.col.enable_geofencing_impressions_col;
                this.enableooImpressionsCol = response.col.enable_o_o_impressions_col;
                this.enableooBudgetCol = response.col.enable_o_o_budget_col;
                this.isPrerollCampaign = response.col.is_preroll_campaign;
                EmitterService.get(EVENTEMITTERS.LOADER).emit(false);                
            });
    }
    
    public openModal(){
        jQuery('#view_flights_table_modal').openModal();
    }
    
    public closeModal(){
        jQuery('#view_flights_table_modal').closeModal();
    }
}