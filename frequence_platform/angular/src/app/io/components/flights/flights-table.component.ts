import {Component, Input, Output, OnInit, EventEmitter, ViewChild, ElementRef, OnDestroy, SimpleChange} from "@angular/core";
import {SERVICE_URL, PLACEHOLDERS, DROPDOWN_OPTIONS, EVENTEMITTERS, ERRORS} from "../../../shared/constants/builder.constants";
import {EmitterService} from "../../../shared/services/emitter.service";
import {SingleFlightComponent} from "./single-flight.component";
import {Select2Directive} from "../../../shared/directives/select2.directive";
import {DatePicker} from "../../../shared/directives/datepicker.directive";
import {MaterializeDirective} from "angular2-materialize";
import {CurrencyDirective} from "../../../shared/directives/currency.directive";
import { Typecast } from '../../../shared/pipes/typecast.pipe';
import {Sum} from "../../../shared/pipes/sum.pipe";
import {NumberFormat} from "../../../shared/pipes/number_format.pipe";
import {OrderBy} from "../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../models/product.model";
import {ValidationStatusConfigModel} from "../../models/validationstatusconfig.model";
import {IOMapperService} from "../../services/iomapper.service";
import {ReforecastService} from "../../services/reforecast.service";
import {UtilityService} from "../../../shared/services/utility.service";
import {RestoreService} from "../../../shared/services/restore.service";
declare var jQuery:any;
declare var moment:any;
declare var Materialize:any;
declare var _:any;

@Component({
    selector: 'flights-table',
    templateUrl: '/angular/build/app/views/io/flights-table.html',
    directives: [SingleFlightComponent, DatePicker, CurrencyDirective, MaterializeDirective],
    providers: [ReforecastService],
    pipes: [Typecast, Sum, NumberFormat, OrderBy]
})
export class FlightsTableComponent implements OnInit {
    private editable: boolean = false;
    private disabled: boolean = false;

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

    @Input('flights') flights;
    @Input('product') product;
    @Input('option') option;
    @Input('hasOwnedAndOperated') hasOwnedAndOperated;
    @Input('hasOwnedAndOperatedDFP') hasOwnedAndOperatedDFP;
    @Input('geofencingEnabled') geofencingEnabled;
    @Input('hasGeofencing') hasGeofencing;
    @Input('readonly') readonly;
    @Input('submitAllowed') submitAllowed: boolean;

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

    constructor(
        private ioMapperService: IOMapperService, 
        private reforecastService: ReforecastService){ }

    ngOnInit(){
        if (this.flights === undefined) this.flights = [];
    }

    ngAfterViewInit(){
        this.resetCPMs();
    }

    switchEditable(status: boolean){
        if (status !== undefined) this.editable = status;

        this.resetCPMs();
        if (this.editable === false){
            this.disableDates();
        }
    }

    resetCPMs(){
        this.cpms = JSON.parse(JSON.stringify(this.product.cpms));
    }

    cancelCPMs(){
        this.resetCPMs();
    }

    saveCPMs(){
        for (let i in this.cpms){
            if (this.cpms[i] !== null) this.cpms[i] = UtilityService.toFloatOrReturnZero(this.cpms[i]);
        }
        this.editCPMEmitter.emit({
            cpms: this.cpms,
            budgetAllocation: this.product.budget_allocation
        });
        this.product.cpms = this.cpms;
    }

    reforecastFlights(productId, regionId){
        this.reforecastEmitter.emit({productId: productId, regionId: regionId});
        this.reforecastService.reforecast('PENDING');
    }

    addFlight(e){
        e.preventDefault();

        let start = this.startDateInput.date;
        let end = this.endDateInput.date;
        let budget = Math.abs(UtilityService.toDollarsOrReturnZero(this.totalBudgetInput.nativeElement.value));

        if (start == '' || end == '' || budget == 0) return false;

        // TODO: validate
        this.addFlightEmitter.emit({
            startDate: start,
            endDate: end,
            totalBudget: budget,
            budgetAllocation: this.product.budget_allocation
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

    deleteAllFlights(){
        this.editable = false;
        this.deleteAllFlightsEmitter.emit(true);
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

    checkDates(date){
        if (date !== ''){
            let status = true;

            this.flights.forEach((flight) => {
                if (moment(date).isSameOrAfter(flight.startDate) && moment(date).isSameOrBefore(flight.endDate)) {
                    status = false;
                }

                if (this.startDateInput.date !== '' && this.endDateInput.date !== ''){
                    if (moment(this.startDateInput.date).isSameOrBefore(flight.startDate) && moment(this.endDateInput.date).isSameOrAfter(flight.endDate)){
                        status = false;
                    }
                }
            });

            if (status === false){
                Materialize.toast('Your dates overlap with an existing flight. Please delete that flight or change your start and end dates.', 5000, 'error-toast');
                this.endDateInput.set('clear', null);
            }

            return status;
        }
    }
}