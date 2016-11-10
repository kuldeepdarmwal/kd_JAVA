import {Component, Input, Output, EventEmitter} from "@angular/core";
import {Subject} from "rxjs/Subject";
import {BuildFlightsComponent} from "./build-flights.component";
import {FlightsTableComponent} from "./flights-table.component";
import {Select2Directive} from "../../../shared/directives/select2.directive";
import {MaterializeDirective} from "angular2-materialize";
import {ProductFilter} from "../../../shared/pipes/product-filter.pipe";
import {TermTransform} from "../../../shared/pipes/term.pipe";
import { Typecast } from '../../../shared/pipes/typecast.pipe';
import {FlightsSummary} from "../../../shared/pipes/flights-summary.pipe";
import {Join} from "../../../shared/pipes/join.pipe";
import {TagInput} from "../../../shared/directives/taginput.directive";
declare var jQuery:any;
declare var _:any;

@Component({
    selector: 'flights',
    templateUrl: '/angular/build/app/views/campaign_setup/flights.html',
    directives: [BuildFlightsComponent, FlightsTableComponent, Select2Directive, MaterializeDirective, TagInput],
    pipes: [ProductFilter, TermTransform, Typecast, FlightsSummary, Join]
})
export class FlightsComponent {
    private confirmBuildFlights: Subject<boolean>;
    @Input('cpms') cpms;
    @Input('campaign_flights') campaign_flights;
    
    @Input('hasOwnedAndOperated') hasOwnedAndOperated;
    @Input('hasOwnedAndOperatedDFP') hasOwnedAndOperatedDFP;
    @Input('hasGeofencing') hasGeofencing;
    
    @Output('build-flights') buildFlightsEmitter = new EventEmitter<any>();
    @Output('edit-flight') editFlightEmitter = new EventEmitter<any>();
    @Output('update-flight') updateFlightEmitter = new EventEmitter<any>();
    @Output('add-flight') addFlightEmitter = new EventEmitter<any>();
    @Output('edit-cpm') editCPMEmitter = new EventEmitter<any>();
    @Output('delete-flight') deleteFlightEmitter = new EventEmitter<any>();
    @Output('delete-all-flights') deleteAllFlightsEmitter = new EventEmitter<any>();
    @Output('reforecast-flights') reforecastEmitter = new EventEmitter<any>();
    
    constructor(){}
    
    confirmBuildFlightsFn(){
        // this.confirmBuildFlights = new Subject<boolean>();
        // let confirmBuildFlights$ = this.confirmBuildFlights.asObservable();
        //
        // return confirmBuildFlights$;
    }
    
    buildFlights(flightsConfig, regionId){
        // let product = _.findWhere(this.products, { id: flightsConfig.productId});
        //
        // if(regionId === null && product.total_flights.length === 0 && product.flights.length > 0){
        //     jQuery('#io_confirm_overwrite_flights_modal').openModal();
        // }
        //
        // let subscription = this.confirmBuildFlightsFn();
        //
        // subscription.subscribe(() => {
        //     flightsConfig.regionId = regionId;
        //     this.buildFlightsEmitter.emit(flightsConfig);
        //     jQuery('#io_confirm_overwrite_flights_modal').closeModal();
        // });
        //
        // if (regionId !== null || product.total_flights.length > 0 || product.flights.length === 0){
        //     this.confirmBuildFlights.next(true);
        // }
    }
    
    editCPM(cpmObject){
        this.editCPMEmitter.emit(cpmObject);
    }
    
    editFlight(flight){
        this.editFlightEmitter.emit(flight);
    }
    
    updateFlight(flight){
        this.updateFlightEmitter.emit({
            flight: flight
        });
    }
    
    addFlight(flightData){
        this.addFlightEmitter.emit(flightData);
    }
    
    deleteFlight(flightId){
        this.deleteFlightEmitter.emit({
            flightId: flightId
        });
    }
    
    deleteAllFlights(){
        this.deleteAllFlightsEmitter.emit({});
    }
    
}
