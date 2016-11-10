import {Component, Input, Output, EventEmitter, ViewChild, OnInit} from "@angular/core";
import {DatePicker} from "../../../shared/directives/datepicker.directive";
import {MaterializeDirective} from "angular2-materialize";
import {CurrencyDirective} from "../../../shared/directives/currency.directive";
import {RadioControlValueAccessor} from "../../../shared/directives/radio-input.directive";
import {ProductFilter} from "../../../shared/pipes/product-filter.pipe";
import {TermTransform} from "../../../shared/pipes/term.pipe";
import {IODataModel} from "../../models/iodatamodel";
declare var moment:any;
declare var jQuery:any;
declare var _:any;

@Component({
    selector: 'build-flights',
    templateUrl: '/angular/build/app/views/io/build-flights.html',
    directives: [RadioControlValueAccessor, DatePicker, MaterializeDirective, CurrencyDirective],
    pipes: [ProductFilter, TermTransform]
})

export class BuildFlightsComponent implements OnInit {
    private startDate: any = false;
    private endDate: any;
    private flightType: 'BROADCAST_MONTHLY' | 'MONTH_END' | 'FIXED' = 'MONTH_END';
    private pacingType: 'MONTHLY' | 'DAILY' = 'MONTHLY';
    private totalBudget: number;
    private locations: any;


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

    @Input('product') product;
    @Input('region-id') regionId;
    @Input('option') option;
    @Input('flights') flights;
    @Input('budget-allocation') budgetAllocation;
    @Input('is-total') isTotal;

    @Output('build-flights') buildFlightsEmitter = new EventEmitter<any>();

    @ViewChild('startDateInput') startDateInput:DatePicker;
    @ViewChild('endDateInput') endDateInput:DatePicker;

    constructor(
    private ioDataModel : IODataModel
    ){
	this.locations = this.ioDataModel.locations;
    }

    ngOnInit(){
        if (this.flights === undefined) this.flights = [];
        if (this.option){
            if (this.isTotal !== undefined){
                this.totalBudget = this.product.submitted_total;
            }

            if (this.option.term !== 'monthly'){
                this.flightType = 'FIXED';
            }
        }
    }

    buildFlights(){
        this.buildFlightsEmitter.emit({
            startDate: this.startDate,
            endDate: this.endDate,
            flightType: this.flightType,
            pacingType: this.pacingType,
            totalBudget: this.totalBudget,
            budgetAllocation: this.budgetAllocation,
            productId: this.product.id
        });
        this.startDateInput.set('clear', null);
        this.endDateInput.set('clear', null);
        this.flightType = 'MONTH_END';
        this.pacingType = 'MONTHLY';
        this.totalBudget = undefined;
    }

    setStartDate(date){
        if (date !== ''){ // happens when month is changed in datepicker
		let startDateSelect = moment(date).format('YYYY-MM-DD');
		let currDate = moment().format('YYYY-MM-DD');
	if ((this.endDate == undefined || this.endDate == '' ) &&  startDateSelect !== currDate){
		let start = moment(date);
                let duration = 6;
                let term = 'months';
                if (this.option !== undefined){
		    if(this.option.duration !== '' && this.option.duration !== undefined)
		    {
			duration = parseInt(this.option.duration);
		    }
                    if (this.option.term === 'monthly'){
                        term = 'months';
                    } else if (this.option.term === 'weekly'){
                        term = 'weeks';
                    } else if (this.option.term === 'daily'){
                        term = 'days';
                    }
                }
                if (term === 'months'){
                    if (this.flightType === "BROADCAST_MONTHLY"){
                        let endOfThisMonth = moment(start).endOf('month').startOf('isoweek').subtract(1, 'days');
			
                        if (start <= endOfThisMonth){
                            duration--;
                        }

                        let endOfMonth = moment(start).add(duration, 'months').endOf('month');
                        if (endOfMonth.day() > 0){
                            endOfMonth.startOf('isoweek').subtract(1, 'days');
                        }
                        this.endDate = endOfMonth.format('YYYY-MM-DD');
                    } else if(this.flightType === "MONTH_END"){
                        this.endDate = moment(start).startOf('month').add(duration - 1, 'months').endOf('month').format('YYYY-MM-DD');			
                    } else if(this.flightType === "FIXED"){
                        this.endDate = moment(start).add(duration, 'months').subtract(1, 'days').format('YYYY-MM-DD');
                    }
                    this.endDateInput.set('select', this.endDate);
                } else {
                    if(this.flightType === "FIXED"){
                        this.endDate = moment(start).add(duration, term).subtract(1, 'days').format('YYYY-MM-DD');
                        this.endDateInput.set('select', this.endDate);
                    }
                }
            }
            this.endDateInput.setOption({min: date});
        }
    }
}
