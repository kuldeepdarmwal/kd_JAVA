import {Injectable} from "@angular/core";
import {ProductModel, DiscountModel} from "./product.model";
import {OptionModel} from "./option.model";
import {TvScxModel} from "./tv-scx-upload.model";
import {CONSTANTS, PRODUCT_TYPE, DROPDOWN_OPTIONS, USER_DATA} from "../../shared/constants/builder.constants";
import {IndustryModel, OpportunityOwnerModel, StrategyModel} from "./gatedatamodel";
import {MapperService} from "../services/mapper.service";
import {SearchKeywordsModel} from "./searchkeywords.model.ts";
import {UtilityService} from "../../shared/services/utility.service";
import {ProductConfigurationModel} from "./configuration.model";
import {StepsCompletionService} from "../services/stepscompletion.service";

/**
 * This model is used to hold the data across the app.
 */
declare var _: any;
@Injectable()
export class RFPDataModel {

    private _user_data: any;
    private _uniqueDisplayId: string;
    private _isNew: boolean = true;
    private _mpqId: number;
    private _proposalId: number;

    private _termDropDown: any[];
    private _durationDropDown: any[];

    private _productConfig: ProductConfigurationModel;

    private _industry: IndustryModel;
    private _opportunityOwner: OpportunityOwnerModel;
    private _advertiserName: string;
    private _proposalName: string;
    private _advertiserSite: string;
    private _presentationDate: string;
    private _strategyId: any;
    private _strategies: StrategyModel[];
    private default_option_names: any;
    private _ccOwner: any;

    private _allProducts: ProductModel[];
    private _products: ProductModel[];
    private _discountProducts: ProductModel[];
    private _afterDiscountProducts: ProductModel[];

    private _discount: DiscountModel;

    private _locations: any[];
    private _geofenceLocationsInventory: number[];
    private _geofencingDefaults: any;
    private _demographics: any[];
    private _politicalData: any[];
    private _audienceInterests: any[];
    private _tvZones: any[];
    private _tvScxData: any = false;
    private _rooftops: any[];
    private _keywords: SearchKeywordsModel;

    private _options: OptionModel[];
    private _loaded: boolean = false;

    constructor(private mapperService: MapperService, private stepsCompletionService : StepsCompletionService) {
        this.mapperService = mapperService;
    }

    get isNew(): boolean {
        return this._isNew;
    }

    set isNew(value: boolean) {
        this._isNew = value;
    }

    get mpqId(): number {
        return this._mpqId;
    }

    set mpqId(value: number) {
        this._mpqId = value;
    }

    get proposalId(): number {
        return this._proposalId;
    }

    set proposalId(value: number) {
        this._proposalId = value;
    }

    get uniqueDisplayId() {
        return this._uniqueDisplayId;
    }

    get termDropDown(): any[] {
        return this._termDropDown;
    }

    set termDropDown(value: any[]) {
        this._termDropDown = value;
    }

    get durationDropDown(): any[] {
        return this._durationDropDown;
    }

    set durationDropDown(value: any[]) {
        this._durationDropDown = value;
    }

    get userData(): any {
        return this._user_data;
    }

    set userData(value: any) {
        this._user_data = value;
    }

    //-------------------------------------------------------------------
    // Setters and Getters for Gate Page
    //-------------------------------------------------------------------
    set currentUserData(currentUser: any) {
        this.opportunityOwner = this.mapperService.mapCurrentUserResponseToModel(currentUser);
    }

    set updateGateData(responseData: any) {
        this.advertiserName = responseData.advertiser_name;
        this.proposalName = responseData.name;
        this.strategyId = responseData.strategy_id;
        this.strategies = this.mapperService.mapStrategyResponseToModel(responseData.strategies);
        this.opportunityOwner = this.mapperService.mapAccountExecResponseToModel(responseData);
        this.industry = this.mapperService.mapIndustryResponseToModel(responseData);
        this.presentationDate = this.mapperService.mapPresentationResponseToDate(responseData);
    }

    get industry(): IndustryModel {
        return this._industry;
    }

    set industry(value: IndustryModel) {
        this._industry = value;
    }

    get opportunityOwner(): OpportunityOwnerModel {
        return this._opportunityOwner;
    }

    set opportunityOwner(value: OpportunityOwnerModel) {
        this._opportunityOwner = value;
    }

    get advertiserName(): string {
        return this._advertiserName;
    }

    set advertiserName(value: string) {
        this._advertiserName = value;
    }

    get proposalName(): string {
        return this._proposalName;
    }

    set proposalName(value: string) {
        this._proposalName = value;
    }

    get presentationDate(): string {
        return this._presentationDate;
    }

    set presentationDate(value: string) {
        this._presentationDate = value;
    }

    get advertiserSite(): string {
        return this._advertiserSite;
    }

    set advertiserSite(value: string) {
        this._advertiserSite = value;
    }

    get strategyId(): any {
        return this._strategyId;
    }

    set strategyId(value: any) {
        this._strategyId = value;
    }

    get strategies(): StrategyModel[] {
        return this._strategies;
    }

    set strategies(strategies: StrategyModel[]) {
        this._strategies = strategies;
    }

    get ccOwner(): any {
        return this._ccOwner;
    }

    set ccOwner(ccOwner: any) {
        this._ccOwner = ccOwner;
    }

    get productConfig(): ProductConfigurationModel {
        return this._productConfig;
    }

    set productConfig(productConfig: ProductConfigurationModel) {
        this._productConfig = productConfig;
    }

    //-------------------------------------------------------------------
    // Setters and Getters for Proposal Page
    //-------------------------------------------------------------------
    //Divides products depending on type and assign them
    set updateAllProductTypes(products: ProductModel[]) {
        this.allProducts = products;
        let products$ = _.clone(products);
        let discountProducts$ = [];
        let afterDiscountProducts$ = [];
        for (let i = products$.length - 1; i >= 0; i--) {
            if (products$[i].definition.after_discount) {
                var product = products$[i];
                afterDiscountProducts$.push(product);
            } else if (products$[i].product_type === 'discount') {
                discountProducts$.push(products$.splice(i, 1)[0]);
            }
        }
        this.products = products$;
        this.afterDiscountProducts = afterDiscountProducts$;
        this.discountProducts = discountProducts$;
    }

    get allProducts(): ProductModel[] {
        return this._allProducts;
    }

    set allProducts(value: ProductModel[]) {
        this._allProducts = value;
    }

    set products(products: ProductModel[]) {
        this._products = products;
    }

    get products() {
        return this._products;
    }

    get discountProducts(): ProductModel[] {
        return this._discountProducts;
    }

    set discountProducts(value: ProductModel[]) {
        this._discountProducts = value;
    }

    get afterDiscountProducts(): ProductModel[] {
        return this._afterDiscountProducts;
    }

    set afterDiscountProducts(value: ProductModel[]) {
        this._afterDiscountProducts = value;
    }

    get discount(): DiscountModel {
        return this._discount;
    }

    set discount(value: DiscountModel) {
        this._discount = value;
    }

    get options(): OptionModel[] {
        return this._options;
    }

    set options(value: OptionModel[]) {
        this._options = value;
    }

    get locations(): any[] {
        return this._locations;
    }

    set locations(value: any[]) {
        this._locations = value;
    }

    get geofenceLocationsInventory(): number[] {
        return this._geofenceLocationsInventory;
    }

    set geofenceLocationsInventory(value: number[]) {
        this._geofenceLocationsInventory = value;
    }

    get geofencingDefaults(): any {
        return this._geofencingDefaults;
    }

    set geofencingDefaults(value: any) {
        this._geofencingDefaults = value;
    }

    get demographics(): any[] {
        return this._demographics;
    }

    set demographics(value: any[]) {
        this._demographics = value;
    }

    get politicalData(): any[] {
        return this._politicalData;
    }

    set politicalData(value: any[]) {
        this._politicalData = value;
    }

    get audienceInterests(): any[] {
        return this._audienceInterests;
    }

    set audienceInterests(value: any[]) {
        this._audienceInterests = value;
    }

    get tvZones(): any[] {
        return this._tvZones;
    }

    set tvZones(value: any[]) {
        this._tvZones = value;
    }

    get tvScxData(): TvScxModel {
        return this._tvScxData;
    }

    set tvScxData(value: TvScxModel) {
        this._tvScxData = value;
    }

    get rooftops(): any[] {
        return this._rooftops;
    }

    set rooftops(value: any[]) {
        this._rooftops = value;
    }

    get keywords(): SearchKeywordsModel {
        return this._keywords;
    }

    set keywords(value: SearchKeywordsModel) {
        this._keywords = value;
    }

    set data(object) {
        this.userData = object.user_data;
        this.mpqId = object.mpq_id;
        this.ccOwner = object.cc_owner === "1";
        this.uniqueDisplayId = object.unique_display_id;
        this.updateGateData = object;
        this.default_option_names = _.findWhere(this.strategies, {strategyId: this.strategyId}).default_option_names;
        if (this.default_option_names !== null) {
            this.default_option_names = this.default_option_names.split(',');
        }
        this.updateAllProductTypes = this.mapperService.mapProductsResponseToModel(object.products);
        this.productConfig = this.mapperService.mapProductConfigToConfig(this.products);
        this.updateTermDropDown = this.products;
        this.durationDropDown = DROPDOWN_OPTIONS.DURATION;
        this.discount = this.mapperService.mapResponseToDiscountModel(object);
        this.demographics = object.demographics;
        this.politicalData = this.mapperService.mapPoliticalResponseToModel(object.political_segment_data);
        this.audienceInterests = object.iab_category_data;
        this.locations = object.existing_locations;
        this.geofenceLocationsInventory = object.geofence_inventory;
        let geofencing_product = _.findWhere(this.products, {has_geofencing: "1"});
        this.geofencingDefaults = (geofencing_product === undefined) ? {} : geofencing_product.definition.geofencing;
        this.options = this.buildOptionsFromProducts(object.options);
        this.rooftops = object.rooftops_data;
        this.tvZones = object.rfp_tv_zones_data;
        this.tvScxData = object.tv_scx_data;
        if (this.tvScxData !== false && _.contains(_.pluck(this.tvScxData.networks, 'selected'), true) === false) {
            this.tvScxData.networks.forEach((network, i) => {
                network.selected = i < CONSTANTS.MAX_TOP_NETWORKS ? true : false;
            });
        }
        this.keywords = this.mapperService.mapKeywordsResponseToModel(object.rfp_keywords_data, object.rfp_keywords_clicks, object.advertiser_website);
        if (this.locations.length === 0) {
            this.locations.push(this.emptyLocation);
        }
        this.stepsCompletionService.RFPSteps = object.proposal_status;
        this.loaded = true;
    }

    get data(): any {
        return {
            "products": _.where(this._allProducts, {selected: true}),
            "options": _.where(this._options, {selected: true}),
            "audience_interests": this._audienceInterests,
            "demographics": this._demographics,
            "rooftops": this._rooftops,
            "tv_zones": this._tvZones,
            "tv_scx_data": this._tvScxData,
            "political": this.politicalData,
            "keywords": this.keywords,
            "mpq_id": this.mpqId,
            "cc_owner": this.ccOwner,
            "discountName": this.discountProducts.length > 0 ? this.discountProducts[0].discountName : "",
            "creativeFeeName": this.afterDiscountProducts.length > 0 ? this.afterDiscountProducts[0].friendly_name : "",
            "has_geofences": this.hasGeofences()
        }
    }

    set updateTermDropDown(products: ProductModel[]) {
        var terms = [];
        var dropDownTerms = [];
        for (let product of products) {
            if (product.definition.term) terms.push(product.definition.term)
        }
        terms = _.intersection.apply(_, terms);
        if (terms.length > 0) {
            for (let term of terms) dropDownTerms.push(_.findWhere(DROPDOWN_OPTIONS.TERM, {value: term}));
            this.termDropDown = dropDownTerms;
        } else {
            this.termDropDown = DROPDOWN_OPTIONS.TERM;
        }
    }

    get loaded(): boolean {
        return this._loaded;
    }

    set loaded(status: boolean) {
        this._loaded = status;
    }

    set uniqueDisplayId(unique_display_id: string) {
        this._uniqueDisplayId = unique_display_id;
    }

    hasGeofences() {
        return this.locations.reduce((carry, location) => {
                return carry || location.geofences.length > 0;
            }, false) && this.productConfig.showGeofencingComponent;
    }

    /**
     * Building Options from Products to separate Options Entity Completely so that it works for future changes
     * Here Is everything happens with options, adding additional functionalities and pre-filling options data
     * Call this method When Budget Component Initializes, That's when we want the Options built
     * Can be used for updating budget component if any of the cards is updated
     *
     * Also, Builds default options for add/remove option functionality
     * */
    buildOptionsFromProducts(selectedOptions) {
        let options = [];
        let products = this.allProducts;
        for (let product of products) {
            let count = 1;

            if (product.product_type === PRODUCT_TYPE.TV_UPLOAD) {
                product.definition.options = [null, null, null];
            }

            if (product.definition.options) {
                product.definition.options.forEach((option: OptionModel, index: number) => {
                    let config = new Object();
                    let configOption: any = PRODUCT_TYPE.TV_UNIT == product.product_type ?
                        this.formatOptionsForTVObject(option, product.options ? product.options[index] : undefined) : this.formatOptionsObject(option, product.options ? product.options[index] : undefined);
                    let additionalConfigCanEditCpm = true;
                    if ((this.userData.role == USER_DATA.ROLE_SALES_UPPER || this.userData.role == USER_DATA.ROLE_SALES_LOWER) && this.userData.is_super == '0') {
                        additionalConfigCanEditCpm = false;
                    }
                    let additionalConfigDuration = CONSTANTS.OPTION_DEFAULT_DURATION;
                    if (typeof selectedOptions[index] !== "undefined") {
                        additionalConfigDuration = selectedOptions[index].duration;
                    }
                    this.addAdditionalConfigurations(configOption, product, additionalConfigDuration, additionalConfigCanEditCpm, index);
                    let productId: number = product.id;
                    if (product.definition.after_discount) configOption.excludes.push("after_discount");
                    config[productId] = configOption;
                    configOption.productType = product.product_type;
                    configOption.afterDiscount = product.definition.after_discount;
                    configOption.selected = product.selected;
                    configOption.cpmEditable = !UtilityService.isUndefined(product.definition.cpm_editable) ? product.definition.cpm_editable : true;
                    this.buildTotalFunctions(product.product_type, product, configOption.data);
                    if (!options[index]) {
                        let temp = <OptionModel>{};
                        if (this.default_option_names !== null) {
                            temp.optionName = this.default_option_names[index];
                        } else {
                            temp.optionName = CONSTANTS.OPTION_DEFAULT_NAME + " " + count;
                        }
                        temp.config = config;
                        temp.optionId = index;
                        temp.selected = false;
                        temp.term = CONSTANTS.OPTION_DEFAULT_TERM;
                        temp.duration = CONSTANTS.OPTION_DEFAULT_DURATION;
                        //Set Selected true if options has an entry || check if options is empty then set selected to true
                        if (selectedOptions.length != 0 && selectedOptions[index]) {
                            temp.selected = selectedOptions[index].selected;
                            temp.optionName = selectedOptions[index].option_name;
                            temp.term = selectedOptions[index].term;
                            temp.duration = selectedOptions[index].duration;
                        } else if (selectedOptions.length === 0) {
                            temp.selected = true;
                        }
                        options.push(temp);
                    }
                    else {
                        options[index].config = Object.assign(options[index].config, config);
                    }
                    count++;
                });

            } else {
                options.forEach((option: OptionModel, index: number) => {
                    var config = option.config;
                    let productId: number = product.id;
                    var tempOption = <OptionModel> {};
                    tempOption["productType"] = product.product_type;
                    tempOption["cpmEditable"] = product.definition.cpm_editable ? product.definition.cpm_editable : true;

                    //PreFill Discount Option With already selected option data
                    if (selectedOptions[index]) {
                        product["discountName"] = selectedOptions[index].discount_name;
                        tempOption["discount"] = parseInt(selectedOptions[index].discount);
                    } else {
                        product["discountName"] = this.discount.name
                        tempOption["discount"] = product.definition.discount_percent;
                    }
                    tempOption["excludes"] = ["discount"];

                    this.buildTotalFunctions(product.product_type, product, tempOption);
                    config[productId] = tempOption;
                });
            }
        }

        // Set up option total functions
        options.forEach((option: OptionModel, index: number) => {
            option.total = (excludes?: string[]) => {
                excludes = excludes === undefined ? [] : excludes;
                let tally = 0, one_time_tally = 0, discount = 0;

                for (let i in option.config) {
                    let excluded_array = excludes.filter((exclude: string) => {
                        return option.config[i].excludes.indexOf(exclude) != -1;
                    });

                    if (excluded_array.length == 0) {
                        if (option.config[i].productType == "discount") {
                            discount = option.config[i].discount; // in practice we never have more than one discount
                        } else if (option.config[i].excludes.indexOf("after_discount") != -1) {
                            let product = _.findWhere(this.allProducts, {id: i});
                            if (product !== undefined && product.selected) {
                                let total = option.config[i].data.total();
                                one_time_tally += isNaN(total) ? 0 : total;
                            }
                        } else {
                            let product = _.findWhere(this._products, {id: i});
                            if (product !== undefined && product.selected) {
                                let total = option.config[i].data.total();
                                tally += isNaN(total) ? 0 : total;
                            }
                        }
                    }
                }

                if (excludes.indexOf("discount") == -1 && discount > 0) {
                    tally -= Math.round(tally * (discount / 100));
                }

                if (excludes.indexOf("after_discount") == -1) {
                    tally = (tally * option.duration) + one_time_tally;
                }

                return tally;
            }
        });
        return options;
    }

    //Moved this to a separate function to separate the mess
    addAdditionalConfigurations(option: any, product: ProductModel, duration, canEditCpm, optionIndex) {
        switch (product.product_type) {
            case PRODUCT_TYPE.COST_UNIT:
                if (typeof product.definition.cpm_editable !== "undefined" && product.definition.cpm_editable == false && typeof product.definition.cpm_periods !== "undefined" && canEditCpm == false) {
                    for (var key in product.definition.cpm_periods) {
                        if (duration <= parseInt(key) && parseFloat(option.data.cpm) < parseFloat(product.definition.cpm_periods[key])) {
                            option.data.cpm = product.definition.cpm_periods[key];
                        }
                    }
                }
                let display_cpm = String(option.data.cpm);
                let display_cpm_array = display_cpm.split('.');
                if (display_cpm.indexOf('.') === -1 || display_cpm_array[1].length < 3) {
                    option.data.cpm = parseFloat(option.data.cpm).toFixed(2);
                }
                if (product.has_geofencing === "1") {
                    option.data.geofence_cpm = this.geofencingDefaults.default_cpm;
                    if (typeof product.options !== "undefined" && typeof product.options[optionIndex].geofence_cpm !== "undefined") {
                        option.data.geofence_cpm = product.options[optionIndex].geofence_cpm;
                    }
                    if (typeof product.definition.cpm_editable !== "undefined" && product.definition.cpm_editable == false && (this.userData.role == USER_DATA.ROLE_SALES_UPPER || this.userData.role == USER_DATA.ROLE_SALES_LOWER) && this.userData.is_super != "1") {
                        if (parseFloat(option.data.geofence_cpm) < parseFloat(this.geofencingDefaults.default_cpm)) {
                            option.data.geofence_cpm = this.geofencingDefaults.default_cpm;
                        }
                    }
                }

                var selected = _.uniq(_.pluck(product.options, 'budget_allocation'))[0];
                product.budget_allocation = selected ? selected : product.definition.allocation_method;

                break;

            case PRODUCT_TYPE.INVENTORY_UNIT:
                let inventory: any = new Object();
                //set the default inventory first
                inventory = product.definition.inventory;
                //set selected and custom values from inventory
                var selected = _.uniq(_.pluck(product.options, 'raw_inventory'))[0];
                var custom = _.uniq(_ .pluck(product.options, 'inventory'))[0];
                //set selected to default to select the correct value from dropdown
                inventory.default = parseInt(selected ? selected : inventory.default);
                //set custom to either default or custom to set the user-entered value
                inventory.custom = parseInt(custom ? custom : inventory.default);
                product.inventory = inventory;
                break;

            case PRODUCT_TYPE.STATIC_UNIT:
                let content: any = new Object();
                //set the default inventory first
                content = product.definition.content;
                var custom = _.uniq(_.pluck(product.options, 'content'))[0];
                content.default = custom ? custom : content.default;
                product.content = content;
                break;
        }

    }

    formatOptionsObject(defaultOption: OptionModel, option: any) {
        var optionObj = {},
            dataObj = {},
            excludesArray = [];
        optionObj["setUpData"] = defaultOption;
        optionObj["excludes"] = excludesArray;

        if (option) {
            var keys = _.keys(defaultOption);
            for (var key of keys) {
                dataObj[key] = option[key];
            }
        } else {
            for (let key in defaultOption) {
                dataObj[key] = defaultOption[key].default;
            }
        }
        optionObj["data"] = dataObj;
        return optionObj;
    }

    formatOptionsForTVObject(defOption: OptionModel, option: any) {
        var optionObj = {},
            dataObj = {customEnabled: false, price: 0},
            excludesArray = [];
        optionObj["setUpData"] = defOption;
        optionObj["data"] = dataObj;
        optionObj["excludes"] = excludesArray;
        if (option) {
            for (var key in option) {
                if (key === "unit") {
                    if (this.isNumber(option[key])) {
                        dataObj[key] = option[key] + " Network";
                        if (option[key] === 0) dataObj["customEnabled"] = true;
                    }
                    else dataObj[key] = option[key];
                }
                if (key === "spots")  dataObj[key] = parseInt(option[key]);
                if (key === "price")  dataObj[key] = parseInt(option[key]);
            }
        } else {
            for (var key in defOption) {
                dataObj[key] = defOption[key].default;
            }
        }
        return optionObj;
    }

    isNumber(o) {
        return !isNaN(o - 0) && o != null;
    }

	buildTotalFunctions(productType:string, product:ProductModel, option:OptionModel) {

		switch (productType) {
			case "cost_per_unit":
				if (product.has_geofencing == "1") {
					option.geofence_impressions_total = () => {
						let geofence_cpm = parseFloat(option.geofence_cpm) || 0;
						return (geofence_cpm == 0) ? 0 : Math.round(option.geofence_dollars_total() / (geofence_cpm / 1000));
					}
					option.geofence_dollars_total = () => {
						let unit = typeof option.unit === "string" ? parseInt(option.unit.replace(new RegExp('\\,', 'g'), '')) : option.unit;
						let geofence_cpm = parseFloat(option.geofence_cpm) || 0;

						if(geofence_cpm == 0)
						{
							return 0;
						}

						let per_location_multiplier = (1 / (this._locations.length || 1));
						let total_population = 0;
						let dollars_value = 0;
						let inventory = 0;

						if(product.budget_allocation == "per_pop")
						{
							for(let index = 0; index < this._locations.length; index++)
							{
								total_population += (this._locations[index].location_population || 0);
							}
						}
						if(total_population == 0)
						{
							return 0;
						}

						let type_multiplier = 0;
						for(let location_id = 0; location_id < this._locations.length; location_id++)
						{
							type_multiplier = (product.budget_allocation == "per_pop") ?
								(this._locations[location_id].location_population / total_population) :
								per_location_multiplier;
							inventory = this.geofenceLocationsInventory[location_id] || 0;
							dollars_value += Math.min((this.geofencingDefaults.max_percent / 100) * unit * type_multiplier, inventory * (geofence_cpm / 1000) * type_multiplier);
						}

						return Math.round(dollars_value) || 0;
					}
					option.vanilla_impressions_total = () => {
						if(parseFloat(option.cpm) == 0)
						{
							return 0;
						}
						return Math.round(1000 * (option.total() - option.geofence_dollars_total()) / option.cpm) || 0;
					}
					option.vanilla_dollars_total = () => {
						let unit = typeof option.unit === "string" ? parseInt(option.unit.replace(new RegExp('\\,', 'g'), '')) : option.unit;
						return unit - option.geofence_dollars_total();
					}
					option.convert_unit = () => {
						let unit = UtilityService.formatNumber(option.unit);
						option.unit = Math.round(option.geofence_impressions_total() + option.vanilla_impressions_total());
					}
					option.impressions_total = () => {
						let unit = typeof option.unit === "string" ? parseInt(option.unit.replace(new RegExp('\\,', 'g'), '')) : option.unit;
						return Math.round((option.geofence_impressions_total() || 0) + (option.vanilla_impressions_total() || 0));
					}
					option.total = () => {
						return UtilityService.toIntOrReturnZero(option.unit);
					};
				} else {
					option.convert_unit = () => {
						let unit = UtilityService.formatNumber(option.unit);
						option.unit = Math.round(unit * 1000 / UtilityService.formatNumber(option.cpm));
					}
					option.impressions_total = () => {
						let unit = typeof option.unit === "string" ? parseInt(option.unit.replace(new RegExp('\\,', 'g'), '')) : option.unit;
						return Math.round(unit * 1000 / option.cpm);
					}
					option.total = () => {
						return UtilityService.toIntOrReturnZero(option.unit);
					};
				}
				break;
            case "cost_per_discrete_unit":
                option.total = () => {
                    return UtilityService.toIntOrReturnZero(option.unit) * UtilityService.toDollarsOrReturnZero(option.cpc);
                };
                break;
            case "cost_per_inventory_unit":
                option.total = () => {
                    return (Math.round(UtilityService.toIntOrReturnZero(option.unit) * option.cpm / 1000) +
                        UtilityService.toIntOrReturnZero(product.inventory.custom)) * this.multiplier(product);
                };
                break;
            case "cost_per_static_unit":
                option.total = () => {
                    return UtilityService.toIntOrReturnZero(option.price) * UtilityService.toIntOrReturnZero(product.content.default);
                };
                break;
            case "cost_per_sem_unit":
                option.total = () => {
                    return UtilityService.toIntOrReturnZero(option.unit);
                };
                break;
            case "input_box":
                option.total = () => {
                    return UtilityService.toIntOrReturnZero(option.unit) * this.multiplier(product);
                };
                break;
            case "discount":
                option.total = (subtotal: number) => {
                    return Math.round((option.discount / 100) * subtotal)
                };
                break;
            case PRODUCT_TYPE.TV_UNIT:
                option.total = () => {
                    return UtilityService.toIntOrReturnZero(option.price);
                }
                break;
            case PRODUCT_TYPE.TV_UPLOAD:
                option.total = () => {
                    // TODO: return total from SCX upload
                    return this.tvScxData ? this.tvScxData.budget.dollars : 0;
                };
                break;
            default :
                option.total = () => {
                    return 1000;
                }
                break;
        }
    }

    multiplier(product) {
        let multiplier = 1;
        if (product !== undefined) {
            if (product.is_geo_dependent === "1") {
                multiplier = this._locations.length || 1;
            }
            if (product.is_rooftops_dependent === "1") {
                multiplier = this._rooftops.length || 1;
            }
        }
        return multiplier;
    }

    //-------------------------------------------------------------------
    // Utility Functions Used by Various Components. Mostly different type of response objects
    // based on functionality.
    //-------------------------------------------------------------------
    get formData(): any {
        return {
            advertiser_name: this.advertiserName,
            proposal_name: this.proposalName,
            owner_id: this.opportunityOwner.opportunityOwnerId,
            industry_id: this.industry.industryId,
            strategy_id: this.strategyId,
            presentation_date : this.presentationDate
        }
    }

    get emptyLocation(): any {
        return {
            custom_regions: [],
            ids: {
                zcta: []
            },
            page: this.locations.length,
            search_type: "custom_regions",
            total: 0,
            user_supplied_name: "",
            selected: false,
            geofences: [],
            location_population: 0
        };
    }

    get filteredStrategyObj(): any {
        return {
            owner_id: this.opportunityOwner.opportunityOwnerId,
            industry_id: this.industry ? this.industry.industryId : "",
            strategy_id: this.strategyId ? this.strategyId : ""
        }
    }

    get opportunityOwnerSelect2Format(): any {
        return {
            id: this._opportunityOwner.opportunityOwnerId,
            text: this._opportunityOwner.opportunityOwnerName
        }
    }

    get industrySelect2Format(): any {
        return {
            id: this._industry.industryId,
            text: this._industry.industryName
        }
    }
}
