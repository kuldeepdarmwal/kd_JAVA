import {Component, ViewChild, HostListener} from "@angular/core";
import {ComponentInstruction, CanActivate} from "@angular/router-deprecated";
import {HasCampaignSetupFlightsData} from "../services/has-campaign-setup-data.service";
import {FlightsComponent} from "./flights/flights.component";
import {IOService} from "../../io/services/io.service";
import {CampaignSetupMapperService} from "../services/campaign-setup-mapper.service";
import {Select2Directive} from "../../shared/directives/select2.directive";
import {EmitterService} from "../../shared/services/emitter.service";
import {EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {CampaignSetupDataModel} from "../models/campaign-setup-data-model";
import {GeographiesComponent} from "../../rfp/components/targets/product-inputs/geographies.component";


declare var _:any;
declare var jQuery:any;
declare var Materialize:any;

@Component({
        selector: 'Campaign_setup',
        templateUrl: '/angular/build/app/views/campaign_setup.html',
        directives: [FlightsComponent, GeographiesComponent, Select2Directive],
})
@CanActivate(
        (next:ComponentInstruction, prev:ComponentInstruction) => {
                return HasCampaignSetupFlightsData(next, prev);
        }
)
export class CampaignSetupFlightsComponent{
        private campaign_flights: any;
        private hasOwnedAndOperated;
        private hasOwnedAndOperatedDFP;
        private hasGeofencing;
        private campaignId;
        
        @ViewChild(FlightsComponent) flightsChild:FlightsComponent;
        
        constructor(private campaignSetupDataModel: CampaignSetupDataModel, private ioService: IOService, private campaignSetupMapper: CampaignSetupMapperService){
                this.loadCampaignSetupData();
                console.log(this.campaign_flights);
                EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        }
        
        loadCampaignSetupData(){
                this.campaign_flights = this.campaignSetupDataModel.campaignSetupFlightsData;
                this.campaignId = this.campaign_flights.campainId;
                this.hasOwnedAndOperated = this.campaign_flights.oandoEnabled;
                this.hasGeofencing = this.campaign_flights.hasGeofencing;
                this.hasOwnedAndOperatedDFP = this.campaign_flights.oandoDFP;
        }
        
        editCPM(CPMObject){
                this.ioService.editCPM(this.campaignSetupMapper.mapEditCPMToRequest(CPMObject.cpms, this.campaign_flights.campaignId))
                        .subscribe((res) => {
                                this.updateFlightsView(res);
                        });
        }
        
        editFlight(flight){
                this.ioService.editFlight(this.campaignSetupMapper.mapEditFlightToRequest(flight, this.campaign_flights.campaignId))
                        .subscribe((res) => {
                                if (res.is_success === false){
                                        Materialize.toast(res.errors, 5000, 'error-toast');
                                } else {
                                        _.extendOwn(this.campaign_flights, this.campaignSetupMapper.mapEditFlightResponseToModel(res, this.campaign_flights));
                                }
                        });
        }
        
        addFlight(flightData){
                this.ioService.addFlight(this.campaignSetupMapper.mapAddFlightToRequest(flightData, this.campaign_flights.campaignId))
                        .subscribe((res) => {
                                if (res.flights){
                                        res.flights.forEach((flight, i) => {
                                                this.campaign_flights.flights[i].push(this.campaignSetupMapper.mapFlightResponseToModel(flight[0]));
                                        });
                                }
                                if (res.total_flights && res.total_flights.length > 0){
                                        let newFlight = this.campaignSetupMapper.mapFlightResponseToModel(res.total_flights[0]);
                                        if (newFlight.totalBudget === undefined) newFlight.totalBudget = flightData.totalBudget;
                                        this.campaign_flights.total_flights.push(newFlight);
                                } else {
                                        this.campaign_flights.total_flights = [];
                                }
                        });
        }
        
        deleteFlight(flightConfig){
                this.ioService.removeCampaignFlight(flightConfig.flightId, this.campaign_flights.campaignId)
                        .subscribe((res) => {
                                this.campaign_flights.flights = this.campaign_flights.flights.map((timeseries) => {
                                        return timeseries.filter((flight) => {
                                                return typeof flightConfig.flightId === 'string' ? flight.id != flightConfig.flightId : flightConfig.flightId.indexOf(flight.id) === -1;
                                        });
                                })
                                this.campaign_flights.total_flights = _.without(this.campaign_flights.total_flights, _.findWhere(this.campaign_flights.total_flights, { id: flightConfig.flightId }));
                        });
        }
        
        deleteAllFlights(flightConfig){
                this.ioService.removeAllCampaignFlights(this.campaign_flights.campaignId)
                        .subscribe((res) => {
                                this.campaign_flights.total_flights = [];
                                this.campaign_flights.flights[0] = [];
                        });
        }
        
        updateFlightsView(response){
                response = this.campaignSetupMapper.mapBuildFlightsResponseToModel(response);
        
                if (response.flights !== null ){
                        this.campaign_flights.flights[0] = response.flights;
                }
        
                this.campaign_flights.total_flights = response.total_flights;
                
                if (response.cpms) this.campaign_flights.cpms = response.cpms;
        }
        
        
        buildFlights(flightsConfig){
                // this.ioService.buildFlights(this.ioMapper.mapBuildFlightsDataToRequest(flightsConfig, this.ioDataModel.mpqId))
                // .subscribe((res) => {
                //     this.updateFlightsView(res, flightsConfig.productId, flightsConfig.regionId);
                //     this.validationStatus = this.validateProposal();
                // });
        }
        
        
        
        updateFlight(flightData){
                // let flight = {};
                // let product = _.findWhere(this.products, {id: flightData.productId});
                // if (flightData.regionId === null){
                //     flight = _.findWhere(product.total_flights, {id: flightData.flight.id});
                // } else {
                //     flight = _.findWhere(product.flights[flightData.regionId], {id: flightData.flight.id});
                // }
                // flight = _.extendOwn(flight, flightData.flight);
        }
        
        
        reforecastFlights(configObj){
                // this.ioService.reforecastFlights(configObj.productId, configObj.regionId, this.ioDataModel.mpqId)
                //     .subscribe((res) => {
                //         let product = _.findWhere(this.products, { id: configObj.productId });
                //         product.total_flights.forEach((flight) => {
                //             flight.forecast_status = 'PENDING';
                //         });
                //     });
        }

}