import {Injectable} from "@angular/core";
import {RFPDataModel} from "../models/rfpdatamodel";
import {ValidationSwitchBoard} from "./validationswitch.service";
import {UtilityService} from "../../shared/services/utility.service";
import {
    TargetsValidationStatusModel,
    BudgetValidationStatusModel,
    TargetsValidationConfigModel,
    BudgetValidationConfigModel
} from "../models/validationconfig.model";
import {ERRORS, PRODUCT_TYPE} from "../../shared/constants/builder.constants";
import {StepsCompletionService} from "./stepscompletion.service";

declare var _:any;
declare var Materialize:any;

@Injectable()
export class ValidationService {

    private _rfpDataModel: RFPDataModel;
    private _validationSwitch: ValidationSwitchBoard;

    private _targetValidationStatusMap: TargetsValidationStatusModel;
    private _budgetValidationStatusMap: BudgetValidationStatusModel;

    private _targetValidationConfig: TargetsValidationConfigModel;
    private _budgetValidationConfig: BudgetValidationConfigModel;


    constructor(private rfpDataModel:RFPDataModel, private validationSwitch:ValidationSwitchBoard, private stepsCompletionService : StepsCompletionService) {
        this._targetValidationStatusMap = <TargetsValidationStatusModel>{};
        this._targetValidationConfig = <TargetsValidationConfigModel>{};

        this._budgetValidationStatusMap = <BudgetValidationStatusModel>{};
        this._budgetValidationConfig = <BudgetValidationConfigModel>{};

        this._rfpDataModel = rfpDataModel;
        this._validationSwitch = validationSwitch;
    }

    private resetTargetValidationObj() {
        this._targetValidationConfig = <TargetsValidationConfigModel>{};
        this._targetValidationStatusMap = <TargetsValidationStatusModel>{};
    }

    private resetBudgetValidationObj() {
        this._budgetValidationConfig = <BudgetValidationConfigModel>{};
        this._budgetValidationStatusMap = <BudgetValidationStatusModel>{};
    }

    private _validateGeos() {
        var status = true;

        for (let i = 0; i < this._rfpDataModel.locations.length; i++) {
            if (this._rfpDataModel.locations[i].ids.zcta.length === 0 || this._rfpDataModel.locations[i].total === 0) {
                status = false;
                break;
            }
        }

        var message = this._rfpDataModel.locations.length === 1 ?
            ERRORS.GEOS.SINGLE :
            ERRORS.GEOS.MULTI;

        this._targetValidationStatusMap.geos = status;
        this._targetValidationConfig.geos = {
            status: this._targetValidationStatusMap.geos,
            messages: status ? "" : message
        }
    }

    private _validateAudience() {
        this._targetValidationStatusMap.audience = this._rfpDataModel.audienceInterests.length > 2;

        this._targetValidationConfig.interests = {
            status: this._targetValidationStatusMap.audience,
            messages: this._targetValidationStatusMap.audience ? "" : ERRORS.INTERESTS
        };
    }

    private _validateRooftops() {
        this._targetValidationStatusMap.rooftops = this._rfpDataModel.rooftops.length > 0;

        this._targetValidationConfig.rooftops = {
            status: this._targetValidationStatusMap.rooftops,
            messages: this._targetValidationStatusMap.rooftops ? "" : ERRORS.ROOFTOPS
        };
    }

    private _validateSEM() {
        this._targetValidationStatusMap.sem = true;
        let messages = [];

        if (this._rfpDataModel.keywords.keywords.length === 0) {
            this._targetValidationStatusMap.sem = false;
            messages.push(ERRORS.SEM.KEYWORDS);
        }

        if (this._rfpDataModel.keywords.clicks) {
            let clicks = UtilityService.toIntOrReturnZero(this._rfpDataModel.keywords.clicks);
            if (isNaN(clicks) || clicks == 0) {
                this._targetValidationStatusMap.sem = false;
                messages.push(ERRORS.SEM.CLICKS);
            }
        } else {
            this._targetValidationStatusMap.sem = false;
            messages.push(ERRORS.SEM.EMPTY_CLICKS);
        }

        if (!this._rfpDataModel.keywords.advertiser_website) {
        	this._targetValidationStatusMap.sem = false;
            messages.push(ERRORS.SEM.WEBSITE);
        }

        this._targetValidationConfig.sem = {
            status: this._targetValidationStatusMap.sem,
            messages: this._targetValidationStatusMap.sem ? "" : messages
        }
    }

    private _validateTVZones() {
        this._targetValidationStatusMap.tvzones = this._rfpDataModel.tvZones.length > 0;

        this._targetValidationConfig.tvzones = {
            status: this._targetValidationStatusMap.tvzones,
            messages: this._targetValidationStatusMap.tvzones ? "" : ERRORS.TV_ZONES
        };
    }

    private _validateTvUpload() {
        this._targetValidationStatusMap.tv_scx_upload = this._rfpDataModel.tvScxData !== false;

        this._targetValidationConfig.tv_scx_upload = {
            status: this._targetValidationStatusMap.tv_scx_upload,
            messages: this._targetValidationStatusMap.tv_scx_upload ? "" : ERRORS.TV_SCX_UPLOAD
        };
    }

    private _validateBudget() {
        this._budgetValidationStatusMap.budget = true;

        let sem_units = [];
        let messages = [];
        this._rfpDataModel.options.forEach((option, option_id) => {

            if (option.selected) {
                for (var product_id in option.config) {
                    if (option.config.hasOwnProperty(product_id)) {
                        let product = option.config[product_id];

                        if (product.productType === PRODUCT_TYPE.SEM_UNIT && _.findWhere(this.rfpDataModel.products, {id: product_id}).selected) {
                            let cpm = UtilityService.formatNumber(product.data.cpm);
                            let unit = UtilityService.formatNumber(product.data.unit);
                            sem_units[option_id] = parseInt(cpm);

                            if (this._rfpDataModel.keywords.clicks) {
                                let clicks = UtilityService.formatNumber(this._rfpDataModel.keywords.clicks);
                                if (parseInt(cpm) > parseInt(clicks)) {
                                    this._budgetValidationStatusMap.budget = false;
                                    messages.push(ERRORS.BUDGET.SEM.LARGER_THAN_INVENTORY);
                                }
                            }

                            if ((unit == 0 && cpm != 0) || (unit != 0 && cpm == 0)) {
                                this._budgetValidationStatusMap.budget = false;
                                messages.push(ERRORS.BUDGET.SEM.UNITS_MISMATCH);
                            }
                        }
                    }
                }
            }
        });

        let clicks_total = sem_units.reduce((total, unit) => total += unit, 0);
        if (clicks_total == 0 && sem_units.length > 0) {
            this._budgetValidationStatusMap.budget = false;
            messages.push(ERRORS.BUDGET.SEM.BUDGETS_ALL_ZERO);
        }

        this._budgetValidationConfig.budget = {
            status: this._budgetValidationStatusMap.budget,
            messages: this._budgetValidationStatusMap.budget ? "" : messages
        };
    }

    get validateBudget(): boolean {
        this.resetBudgetValidationObj();
        this._validateBudget();
        return this.budgetValidationStatus
    }

    get validateTargeting():boolean {
        this.resetTargetValidationObj();
        let config = this.rfpDataModel.productConfig;
        if (config.showAudienceComponent) this._validateAudience();
        if (config.showGeoComponent) this._validateGeos();
        if (config.showRoofTopsComponent) this._validateRooftops();
        if (config.showTvComponent) this._validateTVZones();
        if (config.showTvUploadComponent) this._validateTvUpload();
        if (config.showSEMComponent) this._validateSEM();
        return this.targetValidationStatus;
    }

    get budgetValidationStatus(): boolean {
        return this.getValidationStatus(this._budgetValidationStatusMap, this._budgetValidationConfig);
    }

    get targetValidationStatus():boolean {
            return this.getValidationStatus(this._targetValidationStatusMap, this._targetValidationConfig);
    }

    private getValidationStatus(validationStatusMap, validationConfigObj): boolean {
        this._validationSwitch.validationDone.next(validationConfigObj);
        let status = _.contains(_.values(validationStatusMap), false);
        if (status) this.showErrorMessages(validationConfigObj)
        return !status;
    }

    private showErrorMessages(validationObj) {
        let messages: string[] = _.uniq(_.flatten(_.pluck(validationObj, 'messages')));
        let messageHTML: string = "";

        for(let message of messages){
            if(message != "") messageHTML += `<p>` + message + `</p>`;
        }
        Materialize.toast(messageHTML, 10000, 'error-toast');
    }

}
