import {Observable} from "rxjs/Observable";
import "rxjs/add/operator/map";
import "rxjs/add/operator/catch";
import {Response, Http} from "@angular/http";
import {Injectable} from "@angular/core";
import {SERVICE_URL} from "../../shared/constants/builder.constants";
import {LocationModel} from "../models/location.model";
import {HTTPService} from "../../shared/services/http.service";
import {GateModel} from "../models/gatedatamodel";

@Injectable()
export class RFPService extends HTTPService {

    constructor(http: Http) {
        super(http);
    }

    getRFPData(uniqueDisplayId) {
        return this.query(`${SERVICE_URL.RFP.GET_PROPOSAL}${uniqueDisplayId}`, '')
            .map((res) => res.json())
            .catch(this.handleError)
    }

    updateGate(gateModel: GateModel, uniqueDisplayId): Observable<any> {
        return this.create(`${SERVICE_URL.RFP.UPDATE_GATE}${uniqueDisplayId}`, gateModel)
            .map(res => <any> res.json())
            .catch(this.handleError)
    }

    getFilteredStrategy(model) {
        return this.create(SERVICE_URL.RFP.FILTERED_STRATEGY, model)
            .map(res => <any> res.json())
            .catch(this.handleError);
    }

    getTVPricingByZones(model) {
        return this.create(SERVICE_URL.RFP.PRICES_BY_TV_ZONE, model)
            .map(res => <any> res.json())
            .catch(this.handleError);
    }

    removeCustomRegions(location_id: number, mpq_id) {
        let queryObj = {
            location_id: location_id,
            mpq_id: mpq_id
        }

        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.REMOVE_CUSTOM_REGIONS, queryObj)
            .catch(this.handleError);
    }

    getZipsFromCustomRegions(location: LocationModel, mpqId) {
        let custom_regions = location.custom_regions.map((region) => {
            return region.id;
        });

        let regionData = {
            custom_region_ids: custom_regions.join(','),
            location_id: location.page,
            mpq_id: mpqId
        }

        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.GET_ZIPS_FROM_CUSTOM_REGIONS, regionData)
            .map(res => <any>res.json().response)
            .catch(this.handleError);
    }

    saveZips(location: LocationModel, mpq_id) {
        let zips_string = location.ids.zcta.join(', ')
            .replace(/,/g, ' ')
            .trim()
            .split(/\s+/);

        let queryObj = {
            zips_json: JSON.stringify(zips_string),
            is_custom_regions: location.search_type === "custom_regions",
            location_id: location.page,
            location_name: '',
            mpq_id: mpq_id,
            is_builder: true
        }

        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.SAVE_ZIPS, queryObj)
            .map(res => {
                let response = res.json();
                return {
                    custom_location_name: response.custom_location_name,
                    zips: response.successful_zips.zcta
                }
            })
            .catch(this.handleError);
    }

    saveZipsFromRadius(location_id: number,
                       radius: any,
                       address: string,
                       lat: number,
                       lng: number,
                       mpq_id) {

        let queryObj = {
            location_id: location_id,
            location_name: `${address} - ${radius} mile radius`,
            address: address,
            mpq_id: mpq_id
        }

        let url_suffix = `ZIP_${radius}_${lat}_${lng}`;

        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.RADIUS_SEARCH + url_suffix, queryObj)
            .map(res => {
                let response = res.json();
                return {
                    custom_location_name: queryObj.location_name,
                    zips: response.result_regions
                }
            })
            .catch(this.handleError);
    }

    addLocation(location: LocationModel, mpq_id) {
        let queryObj = {
            location_id: location.page,
            location_name: location.user_supplied_name,
            mpq_id: mpq_id
        }

        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.ADD_LOCATION, queryObj)
            .catch(this.handleError);
    }

    removeLocation(location_id: number, mpq_id) {
        let queryObj = {
            location_id: location_id,
            mpq_id: mpq_id
        }

        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.REMOVE_LOCATION, queryObj)
            .catch(this.handleError);
    }

    saveLocationName(location_id: number, name: string) {
        let queryObj = {
            location_id: location_id,
            location_name: name,
            manually_renamed: true
        }

        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.RENAME_LOCATION, queryObj)
            .catch(this.handleError);
    }

    uploadBulkLocations(locationObj: any) {
        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.BULK_UPLOAD, locationObj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    saveGeofences(geofencesObj: any) {
        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.GEOFENCING.SAVE_GEOFENCES, geofencesObj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    getGeofenceRadius(geofenceObj) {
        return this.create(SERVICE_URL.RFP.GEOGRAPHIES.GEOFENCING.GET_RADIUS, {point_center: geofenceObj})
            .map((res) => res.json())
            .catch(this.handleError);
    }

    submitRFP(rfpData) {
		rfpData.is_builder = true;
        return this.create(SERVICE_URL.RFP.SUBMIT_RFP, rfpData)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    saveTargets(rfpData) {
		rfpData.is_builder = true;
        return this.create(SERVICE_URL.RFP.SUBMIT_TARGETING, rfpData)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    saveBudget(rfpData) {
		rfpData.is_builder = true;
        return this.create(SERVICE_URL.RFP.SUBMIT_BUDGET, rfpData)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    private handleError(error: Response) {
        return Observable.throw(error.text);
    }

}
