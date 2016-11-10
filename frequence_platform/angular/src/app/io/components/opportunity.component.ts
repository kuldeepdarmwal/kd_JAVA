import {Component, Input, EventEmitter, AfterViewInit, ElementRef, ViewChild, Output} from "@angular/core";
import {OpportunityModel} from "../models/opportunity.model";
import {EVENTEMITTERS, CONSTANTS} from "../../shared/constants/builder.constants";
import {EmitterService} from "../../shared/services/emitter.service";
import {Select2Directive} from "../../shared/directives/select2.directive";
import {IODataModel} from "../models/iodatamodel";
import {IOService} from "../services/io.service";
import {IOPropertiesBuilder} from "../utils/io-propertiesbuilder.utility";
declare var jQuery:any;

@Component({
    selector: 'opportunity',
    templateUrl: '/angular/build/app/views/io/opportunity.html',
    directives: [Select2Directive]
})
export class OpportunityComponent implements AfterViewInit {

    @Input('opportunity') _opportunityObj:OpportunityModel;

    @Output('opp-selected') oppOwnerSelected = new EventEmitter<any>();
    @Output('adv-selected') advertiserSelected = new EventEmitter<any>();
    @Output('ind-selected') industrySelected = new EventEmitter<any>();

    @ViewChild('oppOwner') oppOwnerElem:ElementRef;
    @ViewChild('advInd') advIndElem:ElementRef;
    @ViewChild('advertiser') advertiserElem:ElementRef;

    private accountExecObj:any = {};
    private advertiserObj:any = {};
    private advertiserIndObj:any = {};
    private uniqueDisplayId:number;
    private enableUnVerifiedAdvertiser = false;
    private unVerifiedAdvertiser = "";

    constructor(private ioDataModel:IODataModel, private ioService:IOService,
                private ioPropertiesBuilder:IOPropertiesBuilder) {
        this.uniqueDisplayId = this.ioDataModel.uniqueDisplayId;
        this.setEventSubscribers();
        this.buildProperties();
    }

    buildProperties() {
        this.accountExecObj = this.ioPropertiesBuilder._buildPropertiesForOppOwner();
        this.advertiserObj = this.ioPropertiesBuilder._buildPropertiesForAdvertiser();
        this.advertiserIndObj = this.ioPropertiesBuilder._buildPropertiesForAdvertiserIndustry();
    }

    ngAfterViewInit() {
        jQuery(this.oppOwnerElem.nativeElement).select2('data', this.ioDataModel.opportunityOwnerSelect2Format);
        if (this.uniqueDisplayId) {
            jQuery(this.advertiserElem.nativeElement).select2('data', this.ioDataModel.advertiserSelect2Format);

            if (this._opportunityObj.industry.industryId){
                jQuery(this.advIndElem.nativeElement).select2('data', this.ioDataModel.industrySelect2Format);
            }
        }
    }

    setEventSubscribers() {
        EmitterService.get(EVENTEMITTERS.IO.OPPORTUNITY_OWNER).subscribe((obj) => {
            this.oppOwnerSelected.emit(obj);
        });
        EmitterService.get(EVENTEMITTERS.IO.ADVERTISER_INDUSTRY).subscribe(obj => {
            this.industrySelected.emit(obj);
        });
        EmitterService.get(EVENTEMITTERS.IO.ADVERTISERS).subscribe(obj => {
            this.checkAdvertiserSelection(obj);
        });
    }


    checkAdvertiserSelection(eventObj) {
        if (eventObj.id == CONSTANTS.IO.UNVERIFIED_ADVERTISER_NAME) {
            this.enableUnVerifiedAdvertiser = true;
        } else {
            this.enableUnVerifiedAdvertiser = false;
            eventObj.isNew  = false;
            this.advertiserSelected.emit(eventObj);
        }
    }

    createUnverifiedAdvertiser() {
        if (this.unVerifiedAdvertiser && this.unVerifiedAdvertiser.length > 0) {
            this.ioService.createUnVerifiedAdvertiser(this.unVerifiedAdvertiser)
                .subscribe((response) => {
                    this.populateUnVerifiedAdvertiser(response.result)
                });
        }
    }

    populateUnVerifiedAdvertiser(response) {
        jQuery(this.advertiserElem.nativeElement).select2('data',
            this.ioDataModel.convertResponseToadvertiserSelect2Format(response));
        this.unVerifiedAdvertiser = "";
        this.enableUnVerifiedAdvertiser = false;
        response.isNew = true;
        this.advertiserSelected.emit(response);
    }

}