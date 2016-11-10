import {Component, ViewChild, Input, Output, EventEmitter, OnInit} from "@angular/core";
import {DatePicker} from "../../shared/directives/datepicker.directive";
import {CampaignsService} from "../services/campaign.service";
import "rxjs/add/operator/map";
import "rxjs/add/operator/toPromise";
import {EmitterService} from "../../shared/services/emitter.service";
import {EVENTEMITTERS, SERVICE_URL, PLACEHOLDERS} from "../../shared/constants/builder.constants";
import {Select2Directive} from "../../shared/directives/select2.directive";
import {CampaignDataModel} from "../models/campaigndatamodel";

declare var jQuery:any;
declare var moment:any;
declare var Materialize:any;


@Component({
    selector: 'bulk-download',
    templateUrl: '/angular/build/app/views/campaign/bulk-download.html',
    directives: [DatePicker,Select2Directive]
})

export class BulkDownloadComponent  implements OnInit{
    private startDate:any;
    private endDate:any;
    private bulkPendingFlagObj:any;
    private salesPeopleObj:any;
    private partnerPropertiesObj : any;
    private partnerKey: any;
    private accExecutivesPropertiesObj: any;
    public selectedPartnerId;
    public selectedAccountExecutiveId;


    @Input("partner-id") partnerId:any;

    @Output("partner-selected") partnerSelected = new EventEmitter<any>();
    @Output("accExecutive-selected") accExecutiveSelected = new EventEmitter<any>();
    @Output("build-bulkDownload") buildBulkDownloadEmitter = new EventEmitter<any>();

    @ViewChild('startDateInput') startDateInput:DatePicker;
    @ViewChild('endDateInput') endDateInput:DatePicker;

    private datePickerOptions = {
        format: 'yyyy-mm-dd',
        selectMonths: true,
        selectYears: 15,
        container: 'body',
        onSet: function (c) {
            if (c.select) {
                this.close();
            }
        }
    }

    constructor(private campaignsService:CampaignsService, private campaignDataModel : CampaignDataModel) {
        this.setEventSubscribers();
    }

    ngOnInit(){
        this.partnerPropertiesObj = this._buildPropertiesForPartner();
        this.accExecutivesPropertiesObj = this._buildPropertiesForAccExecutives();
    }

    ngAfterViewInit() {
	let startDate = moment(new Date()).subtract(1, 'months');
	this.startDateInput.set('select', startDate.format('YYYY-MM-DD'));
	this.endDateInput.set('select', new Date());
    }

    buildBulkDownload(){
        this.buildBulkDownloadEmitter.emit({
            start_date: this.startDate,
            end_date: this.endDate,
            selected_partner: this.campaignDataModel.partnerId,
        });
    }

    setEventSubscribers() {
        EmitterService.get(EVENTEMITTERS.CAMPAIGN.PARTNER)
            .subscribe((response) => {
                this.partnerSelected.emit({partner_id : response.id});
        });
        EmitterService.get(EVENTEMITTERS.CAMPAIGN.ACCOUNT_EXECUTIVES)
            .subscribe(obj => {
                this.accExecutiveSelected.emit(obj);
        });
    }

    public openBulkCampaignData() {
        jQuery('#bulk_download_modal').openModal();
        this.checkUserBulkPendingFlag();
    }

    selectedPartner(selected_partner_id){
        this.selectedPartnerId = selected_partner_id;
    }

    selectedAccountExecutive(selected_account_executive){
	this.selectedAccountExecutiveId = selected_account_executive;
    }

    public closeModal(){
        jQuery('#bulk_download_modal').closeModal();
    }

    emitPartnerSelectedEvent(partnerId){
        this.partnerKey = partnerId.key;
        this.partnerSelected.emit({partnerKey : partnerId.key});
    }

    setStartDate(date) {
        if (date !== '') { // happens when month is changed in datepicker
            this.endDateInput.setOption({min: date});
        }
    }

    public checkUserBulkPendingFlag() {
        this.campaignsService.userBulkFlag()
            .subscribe((response) => {
                this.bulkPendingFlagObj = response;
            });
    }

    public getBulkDownloadSalesPeople(partnerId) {
        this.campaignsService.bulkDownloadSalesPeople(partnerId)
            .subscribe((response) => {
                this.salesPeopleObj = response;
            });
    }

    _buildPropertiesForPartner = ():{} => {
        return {
            url: SERVICE_URL.CAMPAIGN.GET_PARTNER,
            placeHolder: PLACEHOLDERS.CAMPAIGN.PARTNERS,
            resultFormatFn: this._formatResultsPartnerFn,
            emitter: EmitterService.get(EVENTEMITTERS.CAMPAIGN.PARTNER),
            dataFn: this._dataPartnerFn,
            allowClear: false,
            allowMultiple: false,
            minLength: 0
        };

    }

    private _dataPartnerFn = (term, page):{} => {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page
        };
    }

    private _formatResultsPartnerFn = (obj):{} => {
        return obj.text;
    }

    _buildPropertiesForAccExecutives = ():{} =>{
        return {
            url: SERVICE_URL.CAMPAIGN.GET_BULK_DOWNLOAD_SALES_PEOPLE,
            placeHolder: PLACEHOLDERS.CAMPAIGN.ACCOUNT_EXECUTIVES,
            resultFormatFn: this._formatResultsAccExecutiveFn,
            emitter: EmitterService.get(EVENTEMITTERS.CAMPAIGN.ACCOUNT_EXECUTIVES),
            dataFn: this._dataAccExecutiveFn,
            allowClear: false,
            allowMultiple: false,
            minLength: 0
        };

    }

    private _dataAccExecutiveFn = (term, page):{} => {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page,
            selected_partner: this.campaignDataModel.partnerId,
            start_date : this.startDate,
            end_date : this.endDate
        };
    }

    private _formatResultsAccExecutiveFn = (obj):{} => {
        return '<small class="grey-text">' + obj.partner + '</small>' +
            '<br>' + obj.username;
    }

}
