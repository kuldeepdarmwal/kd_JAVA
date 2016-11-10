import {Component, ViewChild} from "@angular/core";
import {DatePipe} from "@angular/common";
import {DatePicker} from "../../shared/directives/datepicker.directive";
import "rxjs/add/operator/map";
import "rxjs/add/operator/toPromise";
import {MaterializeDirective} from "angular2-materialize";
import {CampaignsService} from "../services/campaign.service";
import {BulkDownloadComponent} from "./bulk-download.component";
import {ViewCreativeDataComponent} from "./view-creative-data-component";
import {ViewChartDataComponent} from "./view-chart-data-component";
import {ViewCreateHelpdeskTicketComponent} from "./view-create_helpdesk_ticket-component";
import {ViewFlightsTableComponent} from "./view-flights-table-component";
import {DataTableDirectives} from "angular2-datatable/datatable";
import {TOOLTIP,EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {HasCampaignsData} from "../services/has-campaign-data.service";
import {EmitterService} from "../../shared/services/emitter.service";
import {CanActivate, ComponentInstruction} from "@angular/router-deprecated";
import {CampaignDataModel} from "../models/campaigndatamodel";
import {SearchPipe} from '../pipes/search-pipe';
import {NotificationDirective} from "../../shared/directives/notification.directive";
import {NotificationModel} from "../models/notification.model";
import {NumberFormat} from "../../shared/pipes/number_format.pipe";

declare var _: any;
declare var jQuery: any;
declare var Materialize: any;

declare var moment: any;

@Component({
    selector: 'campaign',
    templateUrl: '/angular/build/app/views/campaign/campaign.html',
    directives: [DataTableDirectives, BulkDownloadComponent, ViewCreativeDataComponent, ViewChartDataComponent, ViewCreateHelpdeskTicketComponent, ViewFlightsTableComponent, NotificationDirective, DatePicker, MaterializeDirective],
    pipes: [DatePipe, SearchPipe, NumberFormat]
})
@CanActivate(
    (next: ComponentInstruction, prev: ComponentInstruction) => {
        return HasCampaignsData(next, prev);
    }
)
export class CampaignComponent {
    public data;
    public partnerId;
    public query: any;
    public reportDate;
    private salesPersonId;
    public geofenceTooltip: any;
    public allTimeTotal: number= 0;
    public allTimeAE: number = 0;
    public allTimeOO: number = 0;
    public thisFlightTotal: number = 0;
    public thisFlightAE: number = 0;
    public thisFlightOO: number = 0;
    public allTimeColumns: number;
    public thisFlightsColumns: number;
    public totalColumns: number;
    public TOOLTIP:any;
    public islifeTimeMode:any;



    @ViewChild(ViewCreativeDataComponent) viewCreativeDataChild: ViewCreativeDataComponent;
    @ViewChild(ViewChartDataComponent) viewChartDataChild: ViewChartDataComponent;
    @ViewChild(ViewCreateHelpdeskTicketComponent) viewCreateHelpdeskTicketChild: ViewCreateHelpdeskTicketComponent;
    @ViewChild(ViewFlightsTableComponent) viewFlightsTableChild: ViewFlightsTableComponent;

    @ViewChild(BulkDownloadComponent) bulkDownloadComponentChild: BulkDownloadComponent;
    @ViewChild(NotificationDirective) notificationDirective: NotificationDirective;

    constructor(private campaignsService: CampaignsService, private campaignDataModel: CampaignDataModel) {
        this.hideColumns(this.campaignDataModel.campaigns);
        this.loadCampaignData();
        this.TOOLTIP = TOOLTIP.CAMPAIGN;
        this.geofenceTooltip = '<a class="custom-tooltip-link">dyn.</a><div class="custom-tooltip-body vright"><div class="header"><h6>'+TOOLTIP.CAMPAIGN.GEOFENCE_HEADER+'</h6></div><div class="body"><p>'+TOOLTIP.CAMPAIGN.GEOFENCE_BODY+'</p></div></div>';
        this.islifeTimeMode = true;
    }
    
    hideColumns(campaigns){
        let ato = 1; 
        
        for(let campaign of campaigns){
            if(campaign.allTimeCampaign.budget != 0 || campaign.allTimeCampaign.oti != 0 || campaign.allTimeCampaign.realized != 0){
                this.allTimeTotal = 1;
           }

           if(campaign.allTimeAudienceExtension.budget != 0 || campaign.allTimeAudienceExtension.oti != 0 || campaign.allTimeAudienceExtension.realized != 0){
                this.allTimeAE = 1;
           }

           if(campaign.allTimeOAndO.budget != 0 || campaign.allTimeOAndO.oti != 0 || campaign.allTimeOAndO.realized != 0){
                this.allTimeOO = 1;
                ato = 0;
           }

            if(campaign.thisFlightCampaign.budget != 0 || campaign.thisFlightCampaign.oti != 0 || campaign.thisFlightCampaign.realized != 0){
                this.thisFlightTotal = 1;
            }

            if(campaign.thisFlightAudienceExtension.budget != 0 || campaign.thisFlightAudienceExtension.oti != 0 || campaign.thisFlightAudienceExtension.realized != 0){
                this.thisFlightAE = 1;
            }

            if(campaign.thisFlightOAndO.budget != 0 || campaign.thisFlightOAndO.oti != 0 || campaign.thisFlightOAndO.realized != 0){
                this.thisFlightOO = 1;
            } 
        }
        this.totalColumns = 15;
        if(ato){
            this.totalColumns = 9;
        }
    }
    
    searchCampaignData($event){
        this.query=$event.target.value;
        let type = jQuery("#campaigns .campaigns-list .campaigns-list-mode input").is(":checked");
        this.changeCampaignListMode(type);
        jQuery("#campaigns .campaigns-list .pagination:first-child li:first-child").click();
    }
    changeMode($event) {
        this.query = '';
        let type = $event.target.checked;        
        this.changeCampaignListMode(type);
    }

    changeCampaignListMode(type) {
        this.totalColumns = 15;
        if (type) {            
            if(this.thisFlightOO == 0){
                this.totalColumns = 9;
            }
            this.islifeTimeMode = false;
            jQuery('#campaigns .campaigns-list table .all_time').css("display", 'none');
            jQuery('#campaigns .campaigns-list table .this_flight').css("display", 'table-cell');
        } else {
            if(this.allTimeOO == 0){
                this.totalColumns = 9;
            }
            this.islifeTimeMode = true;
            jQuery('#campaigns .campaigns-list table .all_time').css("display", 'table-cell');
            jQuery('#campaigns .campaigns-list table .this_flight').css("display", 'none');
        }
    }

    viewCreativeData($event) {
        $event.preventDefault();
        $event.stopPropagation();
        let campaignId = $event.target.id;
        let campaignName = $event.target.name;
        this.viewCreativeDataChild.openViewCreativeData(campaignId, campaignName);
    }

    viewChartData($event) {
        $event.preventDefault();
        $event.stopPropagation();
        let campaignId = $event.target.id;
        let campaignName = $event.target.name;
        this.viewChartDataChild.openViewChartData(campaignId, this.reportDate, campaignName);
    }

    viewSupportTicket($event) {
        $event.preventDefault();
        $event.stopPropagation();
        let campaignId = $event.target.id;
        this.viewCreateHelpdeskTicketChild.openCreateHelpdeskTicket(campaignId);
    }

    viewFlightsTable(campaignId) {
        this.viewFlightsTableChild.openFlightsTable(campaignId,this.reportDate);
    }

    addToFavorite($event, campaignId, reminderStatus) {
        $event.preventDefault();
        $event.stopPropagation();
        this.campaignsService.updateFavoriteUnfavorite(campaignId, reminderStatus)
            .subscribe((response) => {
                let obj = {"campaignId": campaignId, "reminderStatus": reminderStatus}
                this.campaignDataModel.updateCampaignListData = obj;
                this.data = this.campaignDataModel.campaigns;
            });
    }

    loadCampaignData() {
        this.data = this.campaignDataModel.campaigns;
        this.partnerId = this.campaignDataModel.partnerId;
        this.reportDate = this.campaignDataModel.reportDate;
    }

    bulkDownloadCampaignData() {
        this.bulkDownloadComponentChild.openBulkCampaignData();
    }

    partnerSelected(eventObj) {
        this.bulkDownloadComponentChild.selectedPartner(eventObj.partner_id);
        this.campaignDataModel.partnerId = eventObj.partner_id;
    }

    accExecutiveSelected(eventObj) {
        this.bulkDownloadComponentChild.selectedAccountExecutive(eventObj.id);
        this.salesPersonId = eventObj.id;
    }

    buildBulkDownload(bulkDownloadConfig) {
        this.campaignsService.bulkDownload(bulkDownloadConfig, this.salesPersonId);
        this.bulkDownloadComponentChild.closeModal();
        EmitterService.get(EVENTEMITTERS.NOTIFICATION).emit(this.getNotificationObjectForBulkDownload())
    }

    getNotificationObjectForBulkDownload() {
        let notificationObj: NotificationModel = <NotificationModel>{};
        notificationObj.message = "An email has been sent to your address with the bulk campaign data you requested";
        notificationObj.showNotification = true;
        notificationObj.isError = false;
        return notificationObj;
    }
} 
