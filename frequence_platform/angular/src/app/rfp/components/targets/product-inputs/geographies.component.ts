import {
    Component,
    Input,
    Output,
    Renderer,
    OnInit,
    ViewChild,
    ElementRef,
    EventEmitter,
    OnDestroy
} from "@angular/core";
import {SERVICE_URL, PLACEHOLDERS, EVENTEMITTERS, CONSTANTS} from "../../../../shared/constants/builder.constants";
import {LocationModel, GeofenceModel} from "../../../models/location.model";
import {Select2Directive} from "../../../../shared/directives/select2.directive";
import {RFPService} from "../../../services/rfp.service";
import {EmitterService} from "../../../../shared/services/emitter.service";
import {GoogleMapsService} from "../../../../shared/services/google-maps.service";
import {PlaceCompleteDirective} from "../../../../shared/directives/placecomplete.directive";
import {ValidationSwitchBoard} from "../../../services/validationswitch.service";
import {AddLocationComponent} from "./add-location.component";
import {Join} from "../../../../shared/pipes/join.pipe";
import {RadioControlValueAccessor} from "../../../../shared/directives/radio-input.directive";
import {MaterializeDirective} from "angular2-materialize";
declare var _: any;
declare var jQuery:any;
declare var Materialize:any;

@Component({
    selector: 'geographies',
    templateUrl: '/angular/build/app/views/rfp/targets/product-inputs/geographies.html',
    directives: [Select2Directive, AddLocationComponent, RadioControlValueAccessor, MaterializeDirective, PlaceCompleteDirective],
    pipes: [Join]
})
export class GeographiesComponent implements OnInit, OnDestroy {
    private selectedLocation:LocationModel;
    private validationStatus:boolean = true;
    private eventSubscription;
    private selectedGeoSubmissionType:string = 'radius';
    private bulk_location_string:string = '';
    private geofences: Array<any> = [];

    @Input('product-names') productNames:string[];
    @Input('locations') locations:any[];
    @Input('geofencing') geofencing:any;
    @Input('has-geofencing') hasGeofencing:boolean;
    @Input('mpq-id') mpqId;
    @ViewChild('customRegions') geographiesElement:ElementRef;
	@Input('unique-display-id') uniqueDisplayId:string;

    @Output('select-location') select_location = new EventEmitter<number>();
    @Output('update-custom-regions') update_custom_regions = new EventEmitter<any>();
    @Output('load-regions') load_regions = new EventEmitter<Object>();
    @Output('add-location') addLocation = new EventEmitter<any>();
    @Output('remove-location') removeLocation = new EventEmitter<number>();
    @Output('upload-bulk-locations') upload_bulk_locations = new EventEmitter<Array<any>>();
    @Output('save-geofences') save_geofences = new EventEmitter<number>();

    showAddLocationMenu = false;
    geographiesObj = {};
    geographyOptions:any[] = [{name: "Regions", value: "custom_regions"}, {
        name: "Radius Search",
        value: "radius"
    }, {name: "Known Zips", value: "known_zips"}];

    constructor(private validationSwitchBoard:ValidationSwitchBoard, renderer:Renderer,
                private rfpService:RFPService, private googleMapsService: GoogleMapsService, elementRef:ElementRef) {
        this.setSelect2PropertiesObject();
        EmitterService.get(EVENTEMITTERS.GEOGRAPHIES).subscribe(obj => {
            // we're not updating the datamodel here because it shouldn't save unless "Load Regions" is clicked
            this.selectedLocation.custom_regions = obj.map((region) => {
                return {
                    geo_name: region.text,
                    id: region.id
                }
            });
        });
        EmitterService.get(EVENTEMITTERS.GEOFENCES).subscribe((obj) => {
			if (obj[0] !== undefined) {
				let geofence = this.geofences[obj.location_id][obj.geofence_id];
				geofence.address = obj[0].text;
				this.googleMapsService.getCoords(geofence.address)
					.then((result: any) => {
						geofence.latlng = [result.geometry.location.lat(), result.geometry.location.lng()];
						this.updateGeocodedCenter(obj.geofence_id, obj.location_id, false);
					})
			}
        });
        this.eventSubscription = validationSwitchBoard.validationDone.subscribe(resp => {
            this.showValidationMessages(resp);
        });

        renderer.listenGlobal('document', 'click', (event) => {
            if (this.showAddLocationMenu) this.showAddLocationMenu = false;
        });
    }

    ngOnInit() {
        this.locations.forEach((location, i) => {
            this.cloneGeofences(i);
        });
        this.selectLocation(0);
    }

    showValidationMessages(config) {
        if (config !== null) {
            this.validationStatus = config.geos.status;
        }
    }

    ngAfterViewInit() {
        this.selectCustomRegions();
    }

    toggleAddLocationMenu(e) {
        e.stopImmediatePropagation();
        this.showAddLocationMenu = !this.showAddLocationMenu;
    }

    selectLocation(index:number) {
        if (this.locations[index] !== undefined) {
            this.selectedLocation = this.locations[index];
            this.select_location.emit(index);
            this.selectCustomRegions();
        }
    }

    get selectedLocationId() {
        return this.selectedLocation.page;
    }

    selectCustomRegions() {
        let formattedLocations = [];

        if (this.selectedLocation.custom_regions.length > 0) {
            formattedLocations = this.selectedLocation.custom_regions.map((location) => {
                return {id: location.id, text: location.geo_name};
            });
        }
        if (this.geographiesElement !== undefined) {
            jQuery(this.geographiesElement.nativeElement).select2('data', formattedLocations);
        }
    }

    formatKnownZips(e) {
        this.selectedLocation.ids.zcta = e.target.value.split(', ');
    }

    loadRegions() {
        let subscription = false;

        if (this.selectedLocation.search_type === "radius") {
            let radius = parseInt(this.selectedLocation.counter);
            if (radius > CONSTANTS.GEOS.MAX_RADIUS){
                Materialize.toast('Your radius is too large. Please enter a radius under 300 miles.', 5000, 'error-toast');
                return false;
            } else if (radius >= CONSTANTS.GEOS.WARN_RADIUS){
                jQuery('#geos_warn_radius_search').openModal();
                return false;
            }
        } else if (this.selectedLocation.search_type === "known_zips"){
            if (this.selectedLocation.ids.zcta.length > CONSTANTS.GEOS.MAX_ZIPS_PER_LOCATION){
                Materialize.toast('You have entered too many zips.', 5000, 'error-toast');
                return false;
            } else if (this.selectedLocation.ids.zcta.length >= CONSTANTS.GEOS.WARN_ZIPS_PER_LOCATION){
                jQuery('#geos_warn_known_zips_search').openModal();
                return false;
            }
        }
        this.load_regions.emit(this.selectedLocation);
    }

    setSelect2PropertiesObject(){
        this.geographiesObj = this.select2PropertiesForGeographies;
    }

    uploadBulkLocations() {
        let location_array = this.bulk_location_string.replace(/\t/gi, ';')
            .split('\n')
            .filter(Boolean)
            .map((location) => {
                return location.split(';');
            });

        if (this.locations.length + location_array.length > 200) {
            Materialize.toast('<div>You cannot have more than 200 active locations.</div>', 2000, 'error-toast');
            return false;
        } else if (location_array.length < 1) {
            Materialize.toast('<div>You must upload at least one location.</div>', 2000, 'error-toast');
            return false;
        }

        var starting_location_id = this.locations.length === 1 && this.locations[0].ids.zcta.length === 0 ?
            0 : this.locations.length;

        this.submitLocationsRecursive(
            location_array,
            this.selectedGeoSubmissionType,
            starting_location_id,
            0);
    }

    submitLocationsRecursive(
    	locations:Array<Array<string>>,
		submission_type:string,
		starting_location_id:number,
		line_number:number,
		carry ?:Array<any>) {
        let queryObj = {
            locations: JSON.stringify([locations[line_number]]),
            submission_type: submission_type,
            starting_location_id: starting_location_id,
            line_number: line_number
        }

        if (carry === undefined) carry = [];

        this.rfpService.uploadBulkLocations(queryObj)
            .subscribe((res:any) => {
                if (res.errors.length === 0) {
                    carry.push({
                        errors: [],
                        new_location: res.successful_locations[0],
                        location_id: starting_location_id,
                        original_location: locations[line_number]
                    });
                    starting_location_id++;
                } else {
                    carry.push({
                        errors: res.errors,
                        original_location: locations[line_number]
                    });
                }
                line_number++;

                if (line_number < locations.length) {
                    this.submitLocationsRecursive(
                        locations,
                        submission_type,
                        starting_location_id,
                        line_number,
                        carry
                    );
                } else {
                    this.handleBulkUploadResponse(carry);
                }
            });
    }

    handleBulkUploadResponse(locations:Array<any>) {
        let success = true;
        let errors = [];
        let new_bulk_location_string = '';
        let new_locations = [];

        locations.forEach((location) => {
            if (location.errors.length > 0) {
                success = false;
                errors = errors.concat(location.errors);
                new_bulk_location_string += `${location.original_location.join(';')}\n`;
            } else {
                location.new_location.location_id = location.location_id;
                new_locations.push(location.new_location);
            }
        });

        if (success === false) {
            this.bulk_location_string = new_bulk_location_string;
            let error_html = "";
            errors.forEach((error) => {
                error_html += `<div>${error}</div>`
            });
            Materialize.toast(error_html, 2000, 'error-toast');
        } else {
            jQuery('#bulk_locations_upload_modal').closeModal();
            this.bulk_location_string = '';
        }

        if (new_locations.length > 0) {
            new_locations = new_locations.map((new_location) => {
                let return_location = {
                    custom_regions: [],
                    ids: {zcta: new_location.regions.split(', ')},
                    page: new_location.location_id,
                    search_type: "radius",
                    selected: false,
                    total: 0,
                    user_supplied_name: new_location.location_name,
                    counter: new_location.geo_dropdown_options.radius,
                    address: new_location.geo_dropdown_options.address,
                    location_population: new_location.location_population,
                }
                return_location.total = return_location.ids.zcta.length;
                return return_location;
            });

            if (this.locations.length === 1 && this.locations[0].ids.zcta.length === 0) {
                this.locations = new_locations;
            } else {
                this.locations = this.locations.concat(new_locations);
            }

            this.selectLocation(this.locations.length - 1);
        }
    }

    // Geofencing
    geofenceObj(location_id, geofence_id) {
		return {
            placeHolder: PLACEHOLDERS.GEOFENCES,
            resultFormatFn: this._formatResultsGeofenceFn,
            emitter: EmitterService.get(EVENTEMITTERS.GEOFENCES),
            dataFn: this._dataGeofenceFn,
            allowClear: true,
            allowMultiple: true,
            minLength: 3,
            maximumSelectionSize: 1,
            requestParams: {
                types: ["geocode", "establishment"],
                componentRestrictions: { country: "us" }
            },
            location_id: location_id,
            geofence_id: geofence_id
        };
    }

    _formatResultsGeofenceFn(obj) {
        return obj.text;
    }

    _dataGeofenceFn(term, page) {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page
        };
    }

    cloneGeofences(location_id: number) {
        var geofences = [];

        this.locations[location_id].geofences.forEach((geofence, i) => {
            geofences[i] = _.clone(geofence); // poor deep clone
        });

        this.geofences[location_id] = geofences;
    }

    removeGeofence(location_id:number, geofence_id?:number) {
    	if (geofence_id !== undefined) {
    		let removed = this.geofences[location_id].splice(geofence_id, 1);
    	} else {
	        this.geofences[location_id] = [];
    	}
    }

    addGeofence(location_id) {
        this.geofences[location_id].push({
            address: '',
            latlng: null,
            type: 'proximity',
            proximity_radius: this.geofencing.radius.SUBURBAN
        });
    }

    saveGeofences(location_id: number) {
		if (this.geofences[location_id].length === 0) {
			this.submitGeofences(location_id);
		} else {
			this.updateGeocodedCenter(0, location_id, true);
		}
    }

    submitGeofences(location_id: number) {
        this.locations[location_id].geofences = _.clone(this.geofences[location_id]);
        this.save_geofences.emit(location_id);
    }

    closeModal(location_id) {
    	jQuery('#add_geofencing_modal_'+location_id).closeModal();
    }

    updateGeocodedCenter(geofence_id:number, location_id:number, recursive?: boolean) {
        let geofence = this.geofences[location_id][geofence_id];
        this.rfpService.getGeofenceRadius(geofence.latlng)
            .subscribe((res: any) => {
                geofence.proximity_radius = this.geofencing.radius[res.point_info.zcta_type];
                geofence_id++;
                if (recursive) {
                	if (geofence_id < this.geofences[location_id].length) {
                		this.updateGeocodedCenter(geofence_id, location_id, true);
                	} else {
                		this.submitGeofences(location_id);
                	}
                }
            });
    }

    geofenceRadius(geofence: GeofenceModel) {
		return geofence.type === "proximity" ? geofence.proximity_radius : this.geofencing.radius.CONQUESTING;
    }


    get select2PropertiesForGeographies(): {} {
        return {
            url: SERVICE_URL.RFP.GEOGRAPHIES.GET_CUSTOM_REGIONS,
            placeHolder: PLACEHOLDERS.GEOGRAPHIES,
            resultFormatFn: (obj): {} => {
                return obj.text;
            },
            emitter: EmitterService.get(EVENTEMITTERS.GEOGRAPHIES),
            dataFn: (term, page): {} => {
                term = (typeof term === "undefined" || term == "") ? "%" : term;
                return {
                    q: term,
                    page_limit: 50,
                    page: page
                }
            },
            allowClear: true,
            allowMultiple: true,
            minLength: 4,
            delay: 250,
            resultFn: (data): {} => {
                return {results: data.result, more: data.more};
            }
        };
    }


    ngOnDestroy() {
        this.eventSubscription.unsubscribe();
    }
}
