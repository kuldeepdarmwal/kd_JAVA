import {Injectable} from "@angular/core";
import {MapperService} from "../services/mapper.service";
import {TemplateModel, IncludedFilesModel, CategoryModel} from "./template.model";
import {EXTENSIONS} from "../../shared/constants/builder.constants";
import {RFPDataModel} from "./rfpdatamodel";
import {BuilderUtilityService} from "../services/builder.utility.service";
import {ProductConfigurationModel} from "./configuration.model";
import {BuilderPermissionsModel} from "./builderpermissions.model";
declare var _:any;

/**
 * This model is used to hold the data across the app.
 */
declare var _:any;
@Injectable()
export class BuilderDataModel {

    private _libraryTemplates:TemplateModel[];
    private _slideSorterTemplates:TemplateModel[];
    private _proposalData : any;
    private _categories : CategoryModel[];
    private _categoryCollator : any;
    private _builderPermissions : BuilderPermissionsModel;

    private _logoURL : string;
    private _includesMap : {};
    private _styles : IncludedFilesModel[];
    private _scripts : IncludedFilesModel[];
    private _customScript :IncludedFilesModel;
    private _uniqueDisplayId : any;
    private _isSnapshotsAvailable : boolean;
    private _productConfig : ProductConfigurationModel;

    constructor(private mapperService:MapperService, private rfpDataModel : RFPDataModel, private builderUtilityService : BuilderUtilityService) {
        this.mapperService = mapperService;
    }

    get uniqueDisplayId(): any {
        return this._uniqueDisplayId;
    }

    set uniqueDisplayId(value: any) {
        this._uniqueDisplayId = value;
    }

    get libraryTemplates():TemplateModel[] {
        return this._libraryTemplates;
    }

    set libraryTemplates(value:TemplateModel[]) {
        this._libraryTemplates = value;
    }

    get slideSorterTemplates():TemplateModel[] {
        return this._slideSorterTemplates;
    }

    set slideSorterTemplates(value:TemplateModel[]) {
        this._slideSorterTemplates = value;
    }

    get proposalData():any {
        return this._proposalData;
    }

    set proposalData(value:any) {
        this._proposalData = value;
    }

    get categories(): CategoryModel[] {
        return this._categories;
    }

    set categories(value: CategoryModel[] ) {
        this._categories = value;
    }

    get categoryCollator(): any {
        return this._categoryCollator;
    }

    set categoryCollator(value: any) {
        this._categoryCollator = value;
    }

    get builderPermissions(): BuilderPermissionsModel {
        return this._builderPermissions;
    }

    set builderPermissions(value: BuilderPermissionsModel) {
        this._builderPermissions = value;
    }

    get logoURL():string {
        return this._logoURL;
    }

    set logoURL(value:string) {
        this._logoURL = value;
    }

    get includesMap():{} {
        return this._includesMap;
    }

    set includesMap(value:{}) {
        this._includesMap = value;
    }

    get styles():IncludedFilesModel[] {
        return this._styles;
    }

    set styles(value:IncludedFilesModel[]) {
        this._styles = value;
    }

    get scripts():IncludedFilesModel[] {
        return this._scripts;
    }

    set scripts(value:IncludedFilesModel[]) {
        this._scripts = value;
    }

    get customScript():IncludedFilesModel {
        return this._customScript;
    }

    set customScript(value:IncludedFilesModel) {
        this._customScript = value;
    }

    get isSnapshotsAvailable(): boolean {
        return this._isSnapshotsAvailable;
    }

    set isSnapshotsAvailable(value: boolean) {
        this._isSnapshotsAvailable = value;
    }

    get productConfig(): ProductConfigurationModel {
        return this._productConfig;
    }

    set productConfig(value: ProductConfigurationModel) {
        this._productConfig = value;
    }

    set builderData(builderDataResponse:any){
        let mapperResponse = this.mapperService.mapTemplateResponseToModel(builderDataResponse);
        this.productConfig = this.rfpDataModel.productConfig;
        this.libraryTemplates = this.builderUtilityService.filterTemplates(this.productConfig, mapperResponse.library);
        this.slideSorterTemplates = this.builderUtilityService.filterTemplates(this.productConfig, mapperResponse.canvas);
        this.categories = this.mapperService.mapCategoriesResponseToModel(builderDataResponse.categories);
        this.categoryCollator = this.builderUtilityService.collateTemplatesByCategory(this.libraryTemplates, this.categories);
        this.sortLibraryByType();
        this.proposalData = builderDataResponse.proposalData;
        this.uniqueDisplayId = this.proposalData.unique_display_id;
        this.logoURL = this.proposalData.header_img;
        this.isSnapshotsAvailable = this.builderUtilityService.getSnapShotsStatus(this.productConfig,
            this.proposalData.geo_overview_link, this.proposalData.geos,  this.proposalData.rooftops_snapshot);
        let includes = this.mapperService.mapIncludesToModel(this.proposalData.includes);
        this.externalLinks = this.transformIncludesToObj(includes);
        this.builderPermissions = this.mapperService.getBuilderPermissionsFromModel(builderDataResponse.featurePermissions);
    }

    set externalLinks(linksMap){
        this.includesMap = linksMap;
        this.scripts = linksMap.scripts;
        this.styles = linksMap.styles;
        this.customScript = linksMap.custom;
    }

    transformIncludesToObj(includes : IncludedFilesModel[]){
        let linksMap : any = {};
        let jsFiles: [any] = _.where(includes, {fileType : EXTENSIONS.JS});
        let cssFiles: [any] = _.where(includes, {fileType : EXTENSIONS.CSS});
        //Separate Custom Script from JS Files
        let customScript = _.findWhere(jsFiles, {partnerName : "custom"});
        //remove custom script from JS Files
        jsFiles.splice(_.indexOf(jsFiles, customScript), 1)
        linksMap.scripts  = jsFiles;
        linksMap.styles = cssFiles;
        linksMap.custom = customScript;
        return linksMap;
    }

    sortLibraryByType(){
        let defaultTemplates: TemplateModel[] = _.where(this.libraryTemplates, {default: true});
        let notDefaultTemplates: TemplateModel[] = _.where(this.libraryTemplates, {default: false});
        let sortedTemplates: TemplateModel[] = _.union(notDefaultTemplates, defaultTemplates);
        this.libraryTemplates = sortedTemplates;
    }

}