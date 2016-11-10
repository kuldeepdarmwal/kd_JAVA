import {Component, Input, Output, ViewChild, EventEmitter, ElementRef, OnDestroy} from "@angular/core";
import {Subject, Subscription} from 'rxjs/Rx'
import {SERVICE_URL} from "../../../shared/constants/builder.constants";
import {NumberFormat} from "../../../shared/pipes/number_format.pipe";
import { Typecast } from '../../../shared/pipes/typecast.pipe';
import {CurrencyDirective} from "../../../shared/directives/currency.directive";
import {MaterializeDirective} from "angular2-materialize";
import {CampaignSetupMapperService} from "../../services/campaign-setup-mapper.service";
import {PollingService} from "../../../shared/services/polling.service";
import {ReforecastService} from "../../../io/services/reforecast.service";
import {UtilityService} from "../../../shared/services/utility.service";
declare var jQuery:any;
declare var _:any;
declare var Materialize;

@Component({
    selector: '.single-flight',
    templateUrl: '/angular/build/app/views/campaign_setup/single-flight.html',
    directives: [CurrencyDirective, MaterializeDirective],
    providers: [PollingService],
    pipes: [NumberFormat, Typecast]
})
export class SingleFlightComponent implements OnDestroy {
    @Input('flight') flight;
    @Input('hasOwnedAndOperated') hasOwnedAndOperated;
    @Input('hasOwnedAndOperatedDFP') hasOwnedAndOperatedDFP;

    @Input('hasGeofencing') hasGeofencing;
    @Input('editable') editable;

    @Output('edit-flight') editFlightEmitter = new EventEmitter<any>();
    @Output('update-flight') updateFlightEmitter = new EventEmitter<any>();
    @Output('delete-flight') deleteFlightEmitter = new EventEmitter<any>();

    @ViewChild('totalBudgetInput') totalBudgetInput: ElementRef;
    @ViewChild('ooImpressionsInput') ooImpressionsInput: ElementRef;
    @ViewChild('deleteFlightButton') deleteFlightButton: ElementRef;

    private stopPolling;
    private flightComplete;
    private flightStatus: any;

    reforecastSubscription: Subscription;

    constructor(
        private campaignSetupMapperService : CampaignSetupMapperService,
        private pollingService: PollingService,
        private reforecastService: ReforecastService){
    
        this.flightComplete = true;
        this.reforecastSubscription = this.reforecastService.reforecastFlight$.subscribe((status) => {
            this.flightComplete = true;
            //this.startPolling();
        });
        
    }

    ngAfterViewInit(){
        if (this.hasOwnedAndOperatedDFP){
            this.setCompleteStatus();
            this.setFlightStatus();
        } else {
            this.flightComplete = true;
            jQuery(window).trigger('resize');
        }

        this.stopPolling = new Subject();
        if (this.hasOwnedAndOperatedDFP && this.flight.forecast_status !== 'COMPLETE' && this.flight.forecast_status !== 'FAILED'){
            //this.startPolling();
	    this.flightComplete = true;
        }
    }

    editFlightBudget(){
        this.flight.totalBudget = Math.abs(UtilityService.toDollarsOrReturnZero(this.totalBudgetInput.nativeElement.value));
        let flightObj = {
            id: this.flight.id,
            budget: this.flight.totalBudget,
            editType: 'budget'
        };
        this.editFlightEmitter.emit(flightObj);
        if (this.hasOwnedAndOperatedDFP){
            this.flight.forecast_status = 'COMPLETE';
            this.setCompleteStatus();
            //this.startPolling();
        }
    }

    editOOImpressions(){
        this.flight.ownedAndOperatedImpressions = UtilityService.toIntOrReturnZero(this.ooImpressionsInput.nativeElement.value);
        let flightObj = {
            id: this.flight.id,
            budget: this.flight.totalBudget,
            ownedAndOperatedImpressions: this.flight.ownedAndOperatedImpressions,
            editType: 'oo_impressions'
        };
        this.editFlightEmitter.emit(flightObj);
        this.setFlightStatus();
    }

    startPolling(){
       /* let flightId = typeof this.flight.id === 'string' ? [this.flight.id] : this.flight.id;
        this.pollingService.pollData(SERVICE_URL.IO.FLIGHTS.POLL, {flight_id: flightId}, 3000, this.stopPolling)
            .subscribe((res) => {
                if (res.data.forecast_status === 'COMPLETE' || res.data.forecast_status === 'FAILED'){
                    this.updateFlightEmitter.emit(this.campaignSetupMapperService.mapPollResponseToModel(res.data, this.flight.id));
                    this.flightStatus = true;
                    this.flightComplete = true;
                    this.stopPolling.next(true);
                }
                if (res.data.forecast_status === 'FAILED'){
                    this.flightStatus = false;
                    Materialize.toast('Your flight starting on '+this.flight.startDate+' failed to retrieve a forecast.', 4000, 'error-toast');
                }
            });*/
    }

    setCompleteStatus(){
        this.flightComplete = this.flight.forecast_status === 'COMPLETE' || this.flight.forecast_status === 'FAILED';
    }

    setFlightStatus(){
        this.flightStatus = this.flight.forecast_status !== 'FAILED' && this.flight.ownedAndOperatedImpressions <= this.flight.ownedAndOperatedForecastImpressions;
    }

    ngOnDestroy(){
        this.stopPolling.next(true);
        this.reforecastSubscription.unsubscribe();
    }

    deleteFlight(){
        let tooltipId = this.deleteFlightButton.nativeElement.getAttribute('data-tooltip-id');
        let tooltip = document.getElementById(tooltipId);
        tooltip.parentNode.removeChild(tooltip);

        this.deleteFlightEmitter.emit(this.flight);
    }
}