import {Component, Input, Output, OnInit, EventEmitter} from "@angular/core";
import {MaterializeDirective} from "angular2-materialize";
import {RFPService} from "../../../services/rfp.service";
declare var jQuery: any;

@Component({
	selector: 'add-location',
	templateUrl: '/angular/build/app/views/rfp/targets/product-inputs/add-location.html',
	directives: [MaterializeDirective]
})
export class AddLocationComponent implements OnInit {
	private selectedLocation: any;

	@Input('locations') locations: any[];
	@Input('has-geofencing') hasGeofencing: boolean;

    @Output('select-location') selectLocation = new EventEmitter<number>();
    @Output('add-location') addLocation = new EventEmitter<any>();
    @Output('remove-location') removeLocation = new EventEmitter<number>();

	constructor(private rfpService: RFPService) { }

	ngOnInit() {}

	ngAfterViewInit() {
		jQuery('.rename_location').keypress(function(e) {
			if (e.keyCode == 13) {
				jQuery(this).blur();
			}
		});
	}

	saveLocationName(i) {
		this.rfpService.saveLocationName(i, this.locations[i].user_supplied_name)
			.subscribe();
	}

	blurLocationName(i: number) {
		this.locations[i].editable = false;
	}

	locationNameEditable(i: number, e) {
		this.locations.forEach((location) => location.editable = false);
        this.locations[i].editable = this.locations[i].editable === undefined ? true : !this.locations[i].editable;
        jQuery('#location_'+i+'_name').focus();
    }

    hasFilledLocations(location) {
    	return location.total > 0;
    }
}
