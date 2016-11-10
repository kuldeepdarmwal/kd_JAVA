import {Injectable} from "@angular/core";
import {UtilityService} from "../../shared/services/utility.service";
declare var _: any;
/**
 *  Mapping service to Map Response(Backend) to Models(Interfaces)
 */
@Injectable()
export class CampaignSetupMapperService {
	
	private cpmMapping = {
		audienceExtension: 'ax',
		geofencing: 'gf',
		ownedAndOperated: 'o_o'
	}
	
	constructor(){}
	
	mapBuildFlightsResponseToModel(response: any) {
		// TODO: return per-region flights
		let _totalFlightsObj = [];
		
		if (response.total_flights != null){
			_totalFlightsObj = response.total_flights.map((flight) => {
				return {
					id: flight.id,
					startDate: flight.start_date,
					endDate: flight.end_date,
					totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
					audienceExtensionBudget: parseFloat(flight.ax_budget),
					audienceExtensionImpressions: parseFloat(flight.ax_impressions),
					geofencingBudget: parseFloat(flight.gf_budget),
					geofencingImpressions: parseFloat(flight.gf_impressions),
					ownedAndOperatedBudget: parseFloat(flight.o_o_budget),
					ownedAndOperatedImpressions: parseFloat(flight.o_o_impressions),
					ownedAndOperatedForecastImpressions: parseFloat(flight.o_o_forecast_impressions),
					forecast_status: flight.dfp_status,
					regionId: null
				};
			});
		}
		
		let _flightsObj = [];
		if (response.flights != null) {
			_flightsObj = response.flights.map((flight) => {
				return {
					id: flight.id,
					startDate: flight.start_date,
					endDate: flight.end_date,
					totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
					audienceExtensionBudget: parseFloat(flight.ax_budget),
					audienceExtensionImpressions: parseFloat(flight.ax_impressions),
					geofencingBudget: parseFloat(flight.gf_budget),
					geofencingImpressions: parseFloat(flight.gf_impressions),
					ownedAndOperatedBudget: parseFloat(flight.o_o_budget),
					ownedAndOperatedImpressions: parseFloat(flight.o_o_impressions),
					ownedAndOperatedForecastImpressions: parseFloat(flight.o_o_forecast_impressions),
					forecast_status: flight.dfp_status,
					regionId: flight.region_index
				};
			});
			
		}
		
		let _cpmsObj = response.cpms ? {
			audienceExtension: response.cpms.ax,
			geofencing: response.cpms.gf,
			geofencingMaxDollarPercentage: response.cpms.gf_max_dollar_pct,
			ownedAndOperated: response.cpms.o_o
		} : false;
		
		return {
			flights: _flightsObj,
			total_flights: _totalFlightsObj,
			cpms: _cpmsObj
		};
	}
	
	mapCampaignSetupFlightsResponseToModel(flights_response){
		if (flights_response.total_flights == null) flights_response.total_flights = [];
		if (flights_response.total_flights.length > 0){
			flights_response.total_flights = flights_response.total_flights.map((flight) => {
				return {
					id: flight.id,
					startDate: flight.start_date,
					endDate: flight.end_date,
					totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
					audienceExtensionBudget: parseFloat(flight.ax_budget),
					audienceExtensionImpressions: parseFloat(flight.ax_impressions),
					geofencingBudget: parseFloat(flight.gf_budget),
					geofencingImpressions: parseFloat(flight.gf_impressions),
					ownedAndOperatedBudget: parseFloat(flight.o_o_budget),
					ownedAndOperatedImpressions: parseFloat(flight.o_o_impressions),
					ownedAndOperatedForecastImpressions: parseFloat(flight.o_o_forecast_impressions),
					forecast_status: flight.dfp_status,
					regionId: null
				};
			});
		}
		
		if (flights_response.flights == null) flights_response.flights = [];
		if (flights_response.flights.length > 0){
			flights_response.flights = flights_response.flights.map((region) => {
				return region.map((flight) => {
					return {
						id: flight.id,
						startDate: flight.start_date,
						endDate: flight.end_date,
						totalBudget: UtilityService.toFloatOrReturnZero(flight.total_budget),
						audienceExtensionBudget: parseFloat(flight.ax_budget),
						audienceExtensionImpressions: parseFloat(flight.ax_impressions),
						geofencingBudget: parseFloat(flight.gf_budget),
						geofencingImpressions: parseFloat(flight.gf_impressions),
						ownedAndOperatedBudget: parseFloat(flight.o_o_budget),
						ownedAndOperatedImpressions: parseFloat(flight.o_o_impressions),
						ownedAndOperatedForecastImpressions: parseFloat(flight.o_o_forecast_impressions),
						forecast_status: flight.dfp_status,
						regionId: flight.region_index
					};
				});
			});
		}
		
		if (flights_response.cpms.ax !== undefined){
			flights_response.cpms = {
				audienceExtension: flights_response.cpms.ax !== null ? UtilityService.toFloatOrReturnZero(flights_response.cpms.ax) : 0,
				geofencing: flights_response.cpms.gf !== null ? UtilityService.toFloatOrReturnZero(flights_response.cpms.gf) : 0,
				ownedAndOperated: flights_response.cpms.o_o !== null ? UtilityService.toFloatOrReturnZero(flights_response.cpms.o_o) : 0
			}
		}
		
		flights_response.oandoDFP = (flights_response.o_o_dfp == "1" && flights_response.o_o_enabled == "1") ? true : false;
		flights_response.oandoEnabled = flights_response.o_o_enabled == "1" ? true : false;
		flights_response.hasGeofencing = flights_response.has_geofencing == "1" ? true : false;
		
		return flights_response;
	}
	
	mapFlightResponseToModel(flight){
		let _flight: any = {
			id: flight.id,
			audienceExtensionBudget: parseFloat(flight.ax_budget),
			audienceExtensionImpressions: parseFloat(flight.ax_impressions),
			geofencingBudget: parseFloat(flight.gf_budget),
			geofencingImpressions: parseFloat(flight.gf_impressions),
			ownedAndOperatedBudget: parseFloat(flight.o_o_budget),
			ownedAndOperatedImpressions: parseFloat(flight.o_o_impressions),
			regionId: flight.region_index
		};

		if (flight.total_budget !== undefined){
			_flight.totalBudget = flight.total_budget;
		}
		if (flight.start_date){
			_flight.startDate = flight.start_date;
		}
		if (flight.end_date){
			_flight.endDate = flight.end_date;
		}
		return _flight;
	}

	mapEditFlightToRequest(flightObject, campaignId){
		return {
			budget: flightObject.budget,
			o_o_impressions: flightObject.ownedAndOperatedImpressions,
			flight_id: Array.isArray(flightObject.id) ? flightObject.id : [flightObject.id],
			editType: flightObject.editType,
			campaign_id: campaignId
		}
	}

	mapEditFlightResponseToModel(response, campaign_flights){
		if (response.total_flights){
			let flight = this.mapFlightResponseToModel(response.total_flights);
			let oldFlight = _.find(campaign_flights.total_flights, function(oldFlight) { return _.isEqual(oldFlight.id, flight.id); });
			campaign_flights.total_flights[_.indexOf(campaign_flights.total_flights, oldFlight)] = _.extendOwn(oldFlight, flight);
		}

		if (response.flights){
			response.flights.forEach((flight) => {
				flight = this.mapFlightResponseToModel(flight);
				let flights = campaign_flights.flights[parseInt(flight.regionId)];
				let oldFlight = _.findWhere(flights, { id: flight.id });
				campaign_flights.flights[parseInt(flight.regionId)][_.indexOf(campaign_flights.flights[parseInt(flight.regionId)], oldFlight)] = _.extendOwn(oldFlight, flight);
			});
		}

		return campaign_flights;
	}
	
	mapAddFlightToRequest(flightObject, campaignId){
		return {
			budget: flightObject.totalBudget,
			start_date: flightObject.startDate,
			end_date: flightObject.endDate,
			campaign_id: campaignId
		}
	}

	mapEditCPMToRequest(cpms, campaignId){
		let mappedCPMs = {};
		for (let i in cpms){
			if (cpms.hasOwnProperty(i) && cpms[i] !== 0){
				mappedCPMs[this.cpmMapping[i]] = cpms[i];
			}
		}
		return {
			cpm_value: mappedCPMs,
			campaign_id: campaignId
		};
	}

	mapPollResponseToModel(response: any, flightId){
		return {
			id: flightId,
			audienceExtensionImpressions: UtilityService.toIntOrReturnZero(response.ax_impressions),
			audienceExtensionBudget: UtilityService.toFloatOrReturnZero(response.ax_budget),
			geofencingImpressions: UtilityService.toIntOrReturnZero(response.gf_impressions),
			ownedAndOperatedBudget: UtilityService.toFloatOrReturnZero(response.o_o_budget),
			ownedAndOperatedImpressions: UtilityService.toIntOrReturnZero(response.o_o_impressions),
			ownedAndOperatedForecastImpressions: UtilityService.toIntOrReturnZero(response.o_o_forecast_impressions),
			forecast_status: response.forecast_status
		}
	}
	
	
}
