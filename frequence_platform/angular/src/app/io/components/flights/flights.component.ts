import {Component, Input, Output, EventEmitter, ViewChildren, QueryList} from "@angular/core";
import {Subject} from "rxjs/Subject";
import {DROPDOWN_OPTIONS} from "../../../shared/constants/builder.constants";
import {BuildFlightsComponent} from "./build-flights.component";
import {FlightsTableComponent} from "./flights-table.component";
import {Select2Directive} from "../../../shared/directives/select2.directive";
import {MaterializeDirective} from "angular2-materialize";
import {ProductFilter} from "../../../shared/pipes/product-filter.pipe";
import {TermTransform} from "../../../shared/pipes/term.pipe";
import {Typecast} from "../../../shared/pipes/typecast.pipe";
import {FlightsSummary} from "../../../shared/pipes/flights-summary.pipe";
import {Join} from "../../../shared/pipes/join.pipe";
import {IODataModel} from "../../models/iodatamodel";
import {ValidationStatusConfigModel} from "../../models/validationstatusconfig.model";
import {IOMapperService} from "../../services/iomapper.service";
import {IOService} from "../../services/io.service";
import {TagInput} from "../../../shared/directives/taginput.directive";
declare var jQuery:any;
declare var _:any;

@Component({
    selector: 'flights',
    templateUrl: '/angular/build/app/views/io/flights.html',
    directives: [BuildFlightsComponent, FlightsTableComponent, Select2Directive, MaterializeDirective, TagInput],
    pipes: [ProductFilter, TermTransform, Typecast, FlightsSummary, Join]
})
export class FlightsComponent {
    private budgetAllocationOptions = DROPDOWN_OPTIONS.BUDGET_ALLOCATION;
    private confirmBuildFlights: Subject<boolean>;
    private test;
    private mpqId: any;
    private orderIds;
    private selectedProduct;
    private selectedRegion;
    private isCustomAllocation: boolean;
    @Input('cpms') cpms;
    @Input('products') products;
    @Input('option') option;
    @Input('locations') locations;
    @Input('custom-region-orderids') customRegionOrderIds;
    @Input('hasGeofencing') hasGeofencing;
    @Input('validation') validation: ValidationStatusConfigModel;
    @Input('submitAllowed') submitAllowed: boolean;

    @Output('build-flights') buildFlightsEmitter = new EventEmitter<any>();
    @Output('edit-flight') editFlightEmitter = new EventEmitter<any>();
    @Output('update-flight') updateFlightEmitter = new EventEmitter<any>();
    @Output('add-flight') addFlightEmitter = new EventEmitter<any>();
    @Output('edit-cpm') editCPMEmitter = new EventEmitter<any>();
    @Output('delete-flight') deleteFlightEmitter = new EventEmitter<any>();
    @Output('delete-all-flights') deleteAllFlightsEmitter = new EventEmitter<any>();
    @Output('reforecast-flights') reforecastEmitter = new EventEmitter<any>();

    @ViewChildren(FlightsTableComponent) flightsTables: QueryList<FlightsTableComponent>;

    constructor(
        private ioMapperService : IOMapperService,
	private ioDataModel : IODataModel,
	private ioService: IOService
	){
	    this.mpqId = this.ioDataModel.mpqId;
	}

    confirmBuildFlightsFn(){
        this.confirmBuildFlights = new Subject<boolean>();
        let confirmBuildFlights$ = this.confirmBuildFlights.asObservable();

        return confirmBuildFlights$;
    }

    buildFlights(flightsConfig, regionId){
        let product = _.findWhere(this.products, { id: flightsConfig.productId});

        if(regionId === null && product.total_flights.length === 0 && product.flights.length > 0){
            jQuery('#io_confirm_overwrite_flights_modal').openModal();
        }

        let subscription = this.confirmBuildFlightsFn();

        subscription.subscribe(() => {
            this.flightsTables.forEach((flightsTable) => {
                flightsTable.switchEditable(false);
            })

            flightsConfig.regionId = regionId;
            this.buildFlightsEmitter.emit(flightsConfig);
            jQuery('#io_confirm_overwrite_flights_modal').closeModal();
        });

        if (regionId !== null || product.total_flights.length > 0 || product.flights.length === 0){
            this.confirmBuildFlights.next(true);
        }
    }

    editCPM(cpmObject, productId, regionId){
        cpmObject.productId = productId;
        cpmObject.regionId = regionId;
        this.editCPMEmitter.emit(cpmObject);
    }

    editFlight(flight, productId, regionId){
        flight.productId = productId;
        flight.regionId = regionId;
        this.editFlightEmitter.emit(flight);
    }

    updateFlight(flight, productId, regionId){
        this.updateFlightEmitter.emit({
            productId: productId,
            regionId: regionId, 
            flight: flight
        });
    }

    addFlight(flightData, productId, regionId){
        flightData.productId = productId;
        flightData.regionId = regionId;

        this.addFlightEmitter.emit(flightData);
    }

    deleteFlight(flightId, productId){
        this.deleteFlightEmitter.emit({
            flightId: flightId,
            productId: productId
        });
    }

    deleteAllFlights(productId, regionId){
        this.deleteAllFlightsEmitter.emit({
            productId: productId,
            regionId: regionId
        });
    }

    saveOAndOIds(productID, regionID, o_o_ID){
	let obj = { region_id: regionID, product_id: productID, o_o_id: o_o_ID, mpq_id: this.mpqId}
	this.ioService.saveOAndOOrderId(obj)
                .subscribe((res) => { 		   
		return true; });
    }

    switchCustomAllocation(e, productId){
        if (e.target.value === 'false'){
            _.findWhere(this.products, {id: productId}).budget_allocation = 'custom';
        } else {
            _.findWhere(this.products, {id: productId}).budget_allocation = null;
        }
    }
}
