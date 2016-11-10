import {Injectable} from "@angular/core";
import {MapperService} from "../services/mapper.service";
import {RFPDataModel} from "./rfpdatamodel";
import {BuilderUtilityService} from "../services/builder.utility.service";
import {BuilderPermissionsModel} from "./builderpermissions.model";
declare var _: any;

/**
 * This model is used to hold the data across the app.
 */
declare var _: any;
@Injectable()
export class PermissionsDataModel {

    private _builderPermissions: BuilderPermissionsModel;
    private _enablePreviewText: boolean;


    constructor(private mapperService: MapperService, private rfpDataModel: RFPDataModel, private builderUtilityService: BuilderUtilityService) {
        this.mapperService = mapperService;
    }

    get builderPermissions(): BuilderPermissionsModel {
        return this._builderPermissions;
    }

    set builderPermissions(value: BuilderPermissionsModel) {
        this._builderPermissions = value;
    }

    get enablePreviewText(): boolean {
        return this._enablePreviewText;
    }

    set enablePreviewText(value: boolean) {
        this._enablePreviewText = value;
    }

    set permissionsData(builderDataResponse: any) {
        this.builderPermissions = this.mapperService.getBuilderPermissionsFromModel(builderDataResponse.featurePermissions);
        this.enablePreviewText = !(_.indexOf(_.values(this.builderPermissions), false) == -1);
    }

}