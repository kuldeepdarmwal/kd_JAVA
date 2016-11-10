import {Component, Input, Output, OnInit, EventEmitter, ViewChild, ElementRef} from "@angular/core";
import {SingleFlightComponent} from "./single-flight.component";
import {DatePicker} from "../../../shared/directives/datepicker.directive";
import {MaterializeDirective} from "angular2-materialize";
import {CurrencyDirective} from "../../../shared/directives/currency.directive";
import { Typecast } from '../../../shared/pipes/typecast.pipe';
import {Sum} from "../../../shared/pipes/sum.pipe";
import {NumberFormat} from "../../../shared/pipes/number_format.pipe";
import {OrderBy} from "../../../shared/pipes/orderby.pipe";
import {ReforecastService} from "../../../io/services/reforecast.service";
import {UtilityService} from "../../../shared/services/utility.service";
declare var jQuery:any;
declare var moment:any;
declare var _:any;

@Component({
    selector: 'flights-table',
    templateUrl: '/angular/build/app/views/campaign_setup/flights-table.html',
    directives: [SingleFlightComponent, DatePicker, CurrencyDirective, MaterializeDirective],
    providers: [ReforecastService],
    pipes: [Typecast, Sum, NumberFormat, OrderBy]
})
export class FlightsTableComponent implements OnInit {
    private editable: boolean = false;
    private cpms;
    
    private datePickerOptions = {
        format: 'yyyy-mm-dd',
        min: moment().add(1, 'days').format('YYYY-MM-DD'),
        selectMonths: true,
        selectYears: 15,
        container: 'body',
        onSet: function(c){
            if(c.select){
                this.close();
            }
        }
    }
    
    @Input('campaign_flights') campaign_flights;
    @Input('flights') flights;
    @Input('hasOwnedAndOperated') hasOwnedAndOperated;
    @Input('hasOwnedAndOperatedDFP') hasOwnedAndOperatedDFP;
    @Input('hasGeofencing') hasGeofencing;
    
    @Output('edit-flight') editFlightEmitter = new EventEmitter<any>();
    @Output('update-flight') updateFlightEmitter = new EventEmitter<any>();
    @Output('add-flight') addFlightEmitter = new EventEmitter<any>();
    @Output('edit-cpm') editCPMEmitter = new EventEmitter<any>();
    @Output('delete-flight') deleteFlightEmitter = new EventEmitter<any>();
    @Output('delete-all-flights') deleteAllFlightsEmitter = new EventEmitter();
    @Output('reforecast-flights') reforecastEmitter = new EventEmitter<any>();
    
    @ViewChild('startDateInput') startDateInput:DatePicker;
    @ViewChild('endDateInput') endDateInput:DatePicker;
    @ViewChild('totalBudgetInput') totalBudgetInput:ElementRef;
    
    constructor(private reforecastService: ReforecastService){
    }
    
    ngOnInit(){
        if (this.flights === undefined) this.flights = [];
    }
    
    ngAfterViewInit(){
        this.resetCPMs();
    }
    
    switchEditable(){
        this.resetCPMs();
        if (this.editable === false){
            this.disableDates();
        }
    }
    
    resetCPMs(){
        this.cpms = JSON.parse(JSON.stringify(this.campaign_flights.cpms));
    }
    
    cancelCPMs(){
        this.resetCPMs();
    }
    
    saveCPMs(){
        for (let i in this.cpms){
            this.cpms[i] = UtilityService.toFloatOrReturnZero(this.cpms[i]);
        }
        
        this.editCPMEmitter.emit({
            cpms: this.cpms
        });
    
        this.campaign_flights.cpms = this.cpms;
    }
    
    reforecastFlights(){
        this.reforecastEmitter.emit({});
        this.reforecastService.reforecast('PENDING');
    }
    
    addFlight(e){
        e.preventDefault();
        
        let start = this.startDateInput.date;
        let end = this.endDateInput.date;
        let budget = Math.abs(UtilityService.toDollarsOrReturnZero(this.totalBudgetInput.nativeElement.value));
        
        // TODO: validate
        this.addFlightEmitter.emit({
            startDate: start,
            endDate: end,
            totalBudget: budget
        });
        
        this.startDateInput.set('clear', null);
        this.endDateInput.set('clear', null);
        this.totalBudgetInput.nativeElement.value = '';
        
        let dates = [{
            from: moment.parseZone(start).toDate(),
            to: moment.parseZone(end).toDate()
        }];
        this.startDateInput.set('disable', dates);
        this.endDateInput.set('disable', dates);
    }
    
    deleteFlight(flight){
        let dates = [{
            from: moment.parseZone(flight.startDate).toDate(),
            to: moment.parseZone(flight.endDate).toDate()
        }];
        this.startDateInput.set('enable', dates);
        this.endDateInput.set('enable', dates);
        this.deleteFlightEmitter.emit(flight.id);
    }
    
    closeProduct(e, productId){
        e.preventDefault();
        jQuery(`#product_${productId}_header`).trigger('click');
    }
    
    disableDates(){
        if (this.flights !== undefined && this.flights.length > 0){
            let disabledDates = this.flights.map((flight) => {
                return {
                    from: moment.parseZone(flight.startDate).toDate(),
                    to: moment.parseZone(flight.endDate).toDate()
                };
            });
            
            if (this.startDateInput != undefined && this.endDateInput != undefined){
                this.startDateInput.set('disable', false);
                this.endDateInput.set('disable', false);
                this.startDateInput.set('disable', disabledDates);
                this.endDateInput.set('disable', disabledDates);
            }
        }
    }
    
    setStartDate(date){
        if (date !== ''){ // happens when month is changed in datepicker
            this.endDateInput.setOption({min: date});
        }
    }
}