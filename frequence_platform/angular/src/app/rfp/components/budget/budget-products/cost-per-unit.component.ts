import {Component, Input, Output, EventEmitter} from "@angular/core";
import {MaterializeDirective} from "angular2-materialize";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";
import {RadioControlValueAccessor} from "../../../../shared/directives/radio-input.directive";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";
import {EVENTEMITTERS, USER_DATA} from "../../../../shared/constants/builder.constants";
import {EmitterService} from "../../../../shared/services/emitter.service";
declare var _ : any;

@Component({
	selector: 'cost-per-unit',
	templateUrl: '/angular/build/app/views/rfp/budget/budget-products/cost-per-unit.html',
    directives: [RadioControlValueAccessor, MaterializeDirective, CurrencyDirective],
	pipes: [OrderBy],
    properties: ['options']
})
export class CostPerUnitComponent{

	@Input() product: ProductModel;
    @Input() options: OptionModel;
	@Input() locations: any[];
	@Input() has_geofencing: any;
	@Input() userData: any;

    @Output('change-impressions-type') typeChange = new EventEmitter<Object>();

	constructor() {
        EmitterService.get(EVENTEMITTERS.DURATION_CHANGED).subscribe(optionId => this.updateCPM(optionId))
    }

    hasGeofences() {
        return this.locations.reduce((carry, location) => {
            return carry || location.geofences.length > 0;
        }, false) && this.has_geofencing && this.product.has_geofencing === "1";
    }

	changeImpressionsType(e, option_id: number) {
        if (e.target.value === this.options[option_id].config[this.product.id].data.type) return false;
        this.options[option_id].config[this.product.id].data.convert_unit(e.target.value);
	}

    updateCPM(optionId){
        let option : OptionModel= _.findWhere(this.options, {optionId : optionId});
        let duration: number = parseInt(option.duration);
        let cpmPeriods = this.product.definition.cpm_periods;
        if(cpmPeriods){
            for(var key in cpmPeriods){
                if(duration <= parseInt(key)){
                    option.config[this.product.id].data.cpm = cpmPeriods[key];
                    break;
                }
            }
        }
    }

	cpmFloorChange(e, optionId: number, isGeofenceCpm){
		let option : OptionModel= _.findWhere(this.options, {optionId : optionId});
		let isSuper = this.userData.is_super;
		let role = this.userData.role;
		let duration = option.duration;
		let cpmPeriods = this.product.definition.cpm_periods;
		if(isGeofenceCpm && typeof this.product.definition.geofencing !== "undefined")
		{
			let geofenceDefault = this.product.definition.geofencing.default_cpm;
			if(typeof this.product.definition.cpm_editable !== "undefined" && this.product.definition.cpm_editable == false && (role == USER_DATA.ROLE_SALES_UPPER || role == USER_DATA.ROLE_SALES_LOWER) && isSuper != "1")
			{
				if(parseFloat(option.config[this.product.id].data.geofence_cpm) < parseFloat(geofenceDefault))
				{
					option.config[this.product.id].data.geofence_cpm = geofenceDefault;
				}
			}
		}
		else if(cpmPeriods)
		{
			let minCpm = "-1";
			for(var key in cpmPeriods)
			{
				if(duration <= parseInt(key))
				{
					minCpm = cpmPeriods[key];
					break;
				}
			}
			if(typeof this.product.definition.cpm_editable !== "undefined" && this.product.definition.cpm_editable == false && (((role == USER_DATA.ROLE_SALES_UPPER || role == USER_DATA.ROLE_SALES_LOWER) && isSuper != "1") || isNaN(parseFloat(option.config[this.product.id].data.cpm)))  && minCpm !== "-1")
			{
				if(isNaN(parseFloat(option.config[this.product.id].data.cpm)) || parseFloat(option.config[this.product.id].data.cpm) < parseFloat(minCpm))
				{
					option.config[this.product.id].data.cpm = minCpm;
				}
			}
		}

		let temp_value = option.config[this.product.id].data.cpm;
		if(isGeofenceCpm)
		{
			temp_value = option.config[this.product.id].data.geofence_cpm;
		}
		temp_value = String(temp_value).replace(/[^\d\.]/g, '');
		let i = 0;
		temp_value = temp_value.replace(/\./g, function(all, match) { return i++===0 ? '.' : ''; });
		let temp_value_array = temp_value.split(".");
		if(temp_value_array.length == 1)
		{
			if(temp_value_array[0].length == 0)
			{
				if(isGeofenceCpm)
				{
					option.config[this.product.id].data.geofence_cpm = this.product.definition.geofencing.default_cpm;
				}
			}
			else
			{
				if(isGeofenceCpm)
				{
					option.config[this.product.id].data.geofence_cpm = parseFloat(temp_value).toFixed(2);
				}
				else
				{
					option.config[this.product.id].data.cpm = parseFloat(temp_value).toFixed(2);
				}
			}
		}
		else
		{
			let temp_value_decimal_length = temp_value_array[1].length;
			if(temp_value_decimal_length < 3)
			{
				if(isGeofenceCpm)
				{
					option.config[this.product.id].data.geofence_cpm = parseFloat(temp_value).toFixed(2);
				}
				else
				{
					option.config[this.product.id].data.cpm = parseFloat(temp_value).toFixed(2);
				}
			}
			else
			{
				if(isGeofenceCpm)
				{
					option.config[this.product.id].data.geofence_cpm = temp_value;
				}
				else
				{
					option.config[this.product.id].data.cpm = temp_value;
				}
			}
		}
	}
	checkCpmUserAccess(cpmEditable) {
		return (!cpmEditable && (this.userData.role == USER_DATA.ROLE_SALES_UPPER || this.userData.role == USER_DATA.ROLE_SALES_LOWER) && this.userData.is_super == '0');
	}
}
