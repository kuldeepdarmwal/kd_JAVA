import {Injectable} from "@angular/core";
import {IODataModel} from "../models/iodatamodel";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";
import {ERRORS} from "../../shared/constants/builder.constants";
import {ValidationSwitchBoard} from "./validationswitch.service";
import {IOService} from "./io.service";

declare var _:any;
declare var Materialize:any;

@Injectable()
export class ValidationService {

    private _ioValidationMap:ValidationStatusConfigModel;
    private _ioDataModel:IODataModel;
    private _validationSwitch:ValidationSwitchBoard;
    private _ioValidationObj:ValidationStatusConfigModel;

    constructor(private ioDataModel:IODataModel, private validationSwitch:ValidationSwitchBoard, private ioService:IOService) {
        this._ioValidationMap = <ValidationStatusConfigModel>{};
        this._ioValidationObj = <ValidationStatusConfigModel>{};
        this._ioDataModel = ioDataModel;
        this._validationSwitch = validationSwitch;
    }

    get _startValidation(): ValidationStatusConfigModel {
        this.resetValidationObj();
        this._validateOpportunity();
        this._validateProducts();
        this._validateAudience();
        this._validateGeos();
        this._validateTracking();
        this._validateFlights();
        this._validateCreatives();
        this._validateNotes();
	this._validateForecast();
        return this._ioValidationMap;
    }

    resetValidationObj(){
        this._ioValidationObj = <ValidationStatusConfigModel>{};
        this._ioValidationMap = <ValidationStatusConfigModel>{};
    }

    _validateOpportunity() {
        var status = (
            this.ioDataModel.opportunity.opportunityOwner.opportunityOwnerId !== null &&
            this.ioDataModel.opportunity.advertiser.advertiserId !== null &&
            this.ioDataModel.opportunity.advertiserWebsite !== null &&
            this.ioDataModel.opportunity.orderName !== null &&
            this.ioDataModel.opportunity.industry.industryId !== null
        );

        this._ioValidationMap.opportunity = status;
        this._ioValidationObj.opportunity = {
            status: this._ioValidationMap.opportunity,
            messages: status ? "" : "opportunity invalid" // TODO: use constants file
        };
    }

    _validateProducts() {
        var status = this.ioDataModel.products.reduce((status, product) => {
            return product.selected ? true : status;
        }, false);

        this._ioValidationMap.product = status;
        this._ioValidationObj.product = {
            status: this._ioValidationMap.product,
            messages: status ? "" : "products invalid" // TODO: use constants file
        };
    }

    _validateGeos() {
        var status = true;

        for (let i = 0; i < this._ioDataModel.locations.length; i++) {
            if (this._ioDataModel.locations[i].ids.zcta.length === 0 || this._ioDataModel.locations[i].total === 0) {
                status = false;
                break;
            }
        }

        var message = this._ioDataModel.locations.length === 1 ?
            ERRORS.GEOS.SINGLE :
            ERRORS.GEOS.MULTI;

        this._ioValidationMap.geo = status;
        this._ioValidationObj.geo = {
            status: this._ioValidationMap.geo,
            messages: status ? "" : message
        };
    }

    _validateAudience() {
        this._ioValidationMap.audience = this._ioDataModel.audienceInterests.length > 2;

        this._ioValidationObj.audience = {
            status: this._ioValidationMap.audience,
            messages: this._ioValidationMap.audience ? "" : ERRORS.INTERESTS
        };
    }

    _validateTracking() {
        this._ioValidationMap.tracking = (this._ioDataModel.tracking.trackingTagFileName != "" && this._ioDataModel.tracking.trackingTagFileName != null) ? true : false;
        
	this._ioValidationObj.tracking = {
            status: this._ioValidationMap.tracking,
            messages: this._ioValidationMap.tracking ? "" : ERRORS.INTERESTS
        };
    }

    _validateCreatives() {
        this._ioValidationMap.creative = true; // TODO: every product/geo combination needs a creative
        this.ioDataModel.locations.forEach((location, i) => {
            let status = this.ioDataModel.products.reduce((carry, product) => {
                if (product.selected === false) return carry;
                return _.some(product.creatives, { regionId: i }) ? carry : false;
            }, true);
            this._ioValidationMap.creative = status ? this._ioValidationMap.creative : false;
        });

        this._ioValidationObj.creative = {
            status: this._ioValidationMap.creative,
            messages: this._ioValidationMap.creative ? "" : ERRORS.INTERESTS
        };
    }

    _validateFlights() {
        this._ioValidationMap.flights = this.ioDataModel.products.reduce((status, product) => {
            if (product.selected){
                this.ioDataModel.locations.forEach((location, i) => {
                    if (product.flights[i] === undefined || product.flights[i].length === 0) status = false;
                });
            }
            return status;
        }, true);

        this._ioValidationObj.flights = {
            status: this._ioValidationMap.flights,
            messages: this._ioValidationMap.flights ? "" : ERRORS.INTERESTS
        };
    }

    _validateNotes() {
        this._ioValidationMap.notes = true;

        this._ioValidationObj.notes = {
            status: this._ioValidationMap.notes,
            messages: this._ioValidationMap.notes ? "" : ERRORS.INTERESTS
        };
    }

    _validateForecast() {
	if(this._ioDataModel.submitAllowed && this._ioDataModel.oAndOEnabledProducts)
	{
		var intrvl = setInterval(() => 
			{				
				this.ioService.checkOandOForecastStatus(this._ioDataModel.mpqId, this._ioDataModel.oAndOEnabledProducts)
					.subscribe((response: any) => {
						
						if(response.o_and_o_forecast_status || response.stop_ping)
						{
							clearInterval(intrvl);
						}

						this._ioValidationMap.forecast = response.o_and_o_forecast_status;
						this._ioValidationObj.forecast = {
						    status: this._ioValidationMap.forecast,
						    messages: this._ioValidationMap.forecast ? "" : ERRORS.FORECAST
						};
					})				
			}, 1000);
	}else
	{
		this._ioValidationMap.forecast = true;
	}
    }

    get validationStatus(): boolean{
        let status = _.contains(_.values(this._ioValidationMap), false);
        return !status;
    }
}