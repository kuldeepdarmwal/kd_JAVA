import {Injectable} from "@angular/core";
import {StrategyModel, OpportunityOwnerModel, IndustryModel} from "../models/gatedatamodel";
import {ProductModel, DiscountModel} from "../models/product.model";
import {SearchKeywordsModel} from "../models/searchkeywords.model";
import {UtilityService} from "../../shared/services/utility.service";
import {ProductConfigurationModel} from "../models/configuration.model";
import {TemplateModel, IncludedFilesModel, TemplateConfig, CategoryModel} from "../models/template.model";
import {RFPSteps} from "../models/stepscompletion.model";
import {PRODUCT_TYPE, BUILDER_PERMISSIONS} from "../../shared/constants/builder.constants";
import {BuilderPermissionsModel} from "../models/builderpermissions.model";
declare var _: any;
/**
 *  Mapping service to Map Response(Backend) to Models(Interfaces)
 */
@Injectable()
export class MapperService {

    mapTemplateResponseToModel(templateObj: any) {
        let mapperResponse: any = {};
        let libraryTemplateResponse: any[] = templateObj.library;
        let canvasTemplateResponse: any[] = templateObj.canvas;

        mapperResponse.library = [];
        mapperResponse.library = this.buildTemplatesArray(libraryTemplateResponse);
        mapperResponse.canvas = this.buildTemplatesArray(canvasTemplateResponse);
        return mapperResponse;
    }

    mapCategoriesResponseToModel(categoriesResponse: any[]) {
        let categories: CategoryModel[] = [];
        for (let categoryResp of categoriesResponse) {
            let category: CategoryModel = <CategoryModel> categoryResp;
            categories.push(category);
        }
        return categories;
    }

    buildTemplatesArray(templateResponse: any[]): TemplateModel[] {
        let templates: TemplateModel[] = [];
        for (let template of templateResponse) {
            templates.push(this.mapTemplateToModel(template));
        }
        return templates;
    }

    mapTemplateToModel(templateResp: any): TemplateModel {
        let templateModel: TemplateModel = <TemplateModel>{};
        templateModel.default = !!UtilityService.toInt(templateResp.is_default_page);
        templateModel.class = templateModel.default ? "default" : "not-default";
        templateModel.id = UtilityService.toInt(templateResp.id);
        templateModel.loaded = false;
        templateModel.weight = templateResp.weight;
        templateModel.template = templateResp.raw_html;
        templateModel.selected = false;
        templateModel.hasHTML = false;
        templateModel.isGeneric = UtilityService.toTrueOrFalse(templateResp.is_generic);
        templateModel.isStacked = UtilityService.toTrueOrFalse(templateResp.is_stacked);
        templateModel.isNotDeletable = UtilityService.toTrueOrFalse(templateResp.is_not_deletable);
        templateModel.config = this.mapTemplateConfigToModel(templateResp);
        templateModel.categoryId = templateResp.category_id;
        return templateModel;
    }

    mapTemplateConfigToModel(templateResp: any): ProductConfigurationModel {
        let config: ProductConfigurationModel = <ProductConfigurationModel>{};
        config.showRoofTopsComponent = UtilityService.toTrueOrFalse(templateResp.is_rooftops);
        config.showGeoComponent = UtilityService.toTrueOrFalse(templateResp.is_geo_dependent);
        config.showAudienceComponent = UtilityService.toTrueOrFalse(templateResp.is_audience_dependent);
        config.showTvComponent = UtilityService.toTrueOrFalse(templateResp.is_zones_dependent);
        config.showSEMComponent = UtilityService.toTrueOrFalse(templateResp.is_keywords_dependent);
        config.showGeofencingComponent = UtilityService.toTrueOrFalse(templateResp.has_geofencing);
        config.showTvUploadComponent = UtilityService.toTrueOrFalse(templateResp.has_scx_upload);
        config.showBothGeoAudience = config.showAudienceComponent && config.showGeoComponent
        return config;
    }

    mapIncludesToModel(includes: [any]): IncludedFilesModel[] {
        let includedFiles: IncludedFilesModel[] = [];
        for (let include of includes) {
            let includeObj: IncludedFilesModel = <IncludedFilesModel>{};
            includeObj.id = include.id;
            includeObj.fileUrl = include.file_url;
            includeObj.fileType = include.file_type;
            includeObj.weight = include.weight;
            includeObj.templateId = include.proposal_templates_id;
            includeObj.partnerName = include.partner_name;
            includedFiles.push(includeObj);
        }
        return includedFiles;
    }

    getBuilderPermissionsFromModel(permissionsResp : any) : BuilderPermissionsModel{
        let builderPermissionsModel : BuilderPermissionsModel = <BuilderPermissionsModel> {};
        builderPermissionsModel.hasAddSlide = permissionsResp[BUILDER_PERMISSIONS.ADD_SLIDE] ? true : false;
        builderPermissionsModel.hasRemoveSlide = permissionsResp[BUILDER_PERMISSIONS.REMOVE_SLIDE] ? true : false;
        builderPermissionsModel.hasDragSlide = permissionsResp[BUILDER_PERMISSIONS.DRAG_SLIDE] ? true : false;
        return builderPermissionsModel;
    }

    mapProductConfigToConfig(products) {
        var productConfig = <ProductConfigurationModel>{};
        var selectedProducts = _.where(products, {selected: true});

        productConfig.showAudienceComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'is_audience_dependent')), "1");
        productConfig.showGeoComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'is_geo_dependent')), "1");
        productConfig.showGeofencingComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'has_geofencing')), "1");
        productConfig.showTvComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'is_zones_dependent')), "1");
        productConfig.showTvUploadComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'product_type')), "tv_scx_upload");
        productConfig.showRoofTopsComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'is_rooftops_dependent')), "1");
        productConfig.showPoliticalComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'is_political')), "1");
        productConfig.showSEMComponent = _.contains(_.uniq(_.pluck(selectedProducts, 'is_keywords_dependent')), "1");
        productConfig.showBothGeoAudience = productConfig.showAudienceComponent && productConfig.showGeoComponent;
        return productConfig;
    }

    mapCurrentUserResponseToModel(currentUser) {
        let _opportunityOwnerObj: OpportunityOwnerModel = <OpportunityOwnerModel>{};
        _opportunityOwnerObj.opportunityOwnerId = currentUser.user_id;
        _opportunityOwnerObj.opportunityOwnerName = currentUser.user_full_name;
        _opportunityOwnerObj.opportunityOwnerEmail = currentUser.user_email;
        return _opportunityOwnerObj;
    }

    mapAccountExecResponseToModel(accountExecutiveResp) {
        let _opportunityOwnerObj: OpportunityOwnerModel = <OpportunityOwnerModel>{};
        _opportunityOwnerObj.opportunityOwnerId = accountExecutiveResp.account_executive_data.id;
        _opportunityOwnerObj.opportunityOwnerName = accountExecutiveResp.account_executive_data.text;
        _opportunityOwnerObj.opportunityOwnerEmail = accountExecutiveResp.account_executive_data.email;
        return _opportunityOwnerObj;
    }

    mapIndustryResponseToModel(industryResp) {
        let _industryObj: IndustryModel = <IndustryModel>{};
        _industryObj.industryId = industryResp.industry_data.id;
        _industryObj.industryName = industryResp.industry_data.text;
        return _industryObj;
    }

    mapStrategyResponseToModel(strategies) {
        let _strategies = [];
        for (let strategy of strategies) {
            let strategyObj: StrategyModel = <StrategyModel>{};
            strategyObj.strategyId = strategy.id;
            strategyObj.strategyName = strategy.name;
            strategyObj.default_option_names = strategy.default_option_names;
            strategyObj.previewImage = strategy.preview_image;
            strategyObj.description = strategy.description;
            strategyObj.cost_per_unit_required = strategy.cost_per_unit_required;
            strategyObj.products = this.removeNonDisplayProductsFromStrategies(strategy.products);
            strategyObj.selected = strategies.length === 1 ? true : false;
            _strategies.push(strategyObj);
        }
        return _strategies;
    }

    mapPresentationResponseToDate(responseData: any){
        let presentationDate = responseData.presentation_date;
        if(presentationDate == null){
            presentationDate = UtilityService.getCurrentDate();
        }
        return presentationDate;
    }

    removeNonDisplayProductsFromStrategies(products: ProductModel[]) {
        for (var i = products.length - 1; i >= 0; i--) {
            if (products[i].product_type === 'discount' || products[i].selectable === "0")
                products.splice(i, 1);
        }
        return products;
    }

    mapKeywordsResponseToModel(keywordsResp, clicksResp, advertiserWebsite) {
        let searchKeywords: SearchKeywordsModel = <SearchKeywordsModel>{};
        searchKeywords.keywords = keywordsResp;
        searchKeywords.clicks = clicksResp;
        searchKeywords.advertiser_website = advertiserWebsite;
        return searchKeywords;
    }

    mapProposalStatusResponseToModel(proposalStatusResponse){
        let proposalStatus : RFPSteps = <RFPSteps>{};
        proposalStatus
    }

    mapProductsResponseToModel(productResponse) {
        let status: boolean[] = _.uniq(_.pluck(productResponse, 'selected'));
        let allEmpty: boolean = status.length == 1 ? true : false;
        for (let product of productResponse) {
            product.definition = typeof product.definition == "string" ? UtilityService.toJson(product.definition) : product.definition;
            product.selected = !allEmpty ? product.selected : true;
            product.disabled = false;
        }
        return productResponse;
    }

    mapPoliticalResponseToModel(politicalData) {
        var politicalModel = [];
        for (var key in politicalData) {
            let temp: any = {};
            let values = [];
            temp.title = key;
            for (var data of politicalData[key]) {
                let val: any = {};
                val.id = data.id;
                val.name = data.name;
                val.selected = !!UtilityService.toInt(data.value);
                values.push(val);
            }
            temp.values = values;
            politicalModel.push(temp);
        }
        return politicalModel;
    }

    mapResponseToDiscountModel(response) {
        let discountModel: DiscountModel = <DiscountModel>{};
        discountModel.discount = response.raw_discount;
        discountModel.name = response.raw_discount_name;
        return discountModel;
    }


    mapRFPDataToSubmission(rfpData, productConfig: ProductConfigurationModel, rfpSteps) {
        let mappedData: any = {};

        let mapIABCategories = () => {
            mappedData.iab_categories = !rfpData.audience_interests ? "[]" : JSON.stringify(rfpData.audience_interests.map((interest) => {
                return interest.id;
            }));
        }

        let mapDemographicsData = () => {
            mappedData.demographics = rfpData.demographics.reduce((demos, demo_group) => {
                    return demos.concat(demo_group.demographic_elements.reduce((demos, demo) => {
                        demos.push(demo.is_checked ? 1 : 0);
                        return demos;
                    }, []));
                }, []).join('_') + "_1_1_1_1_1_75_All_unusedstring";
        }

        let mapPoliticalData = () => {
            if (productConfig.showPoliticalComponent)
                mappedData.political_segments = this.mapPoliticalDataToSubmission(rfpData.political);
        }

        mappedData.discount_text = rfpData.discountName;
        mappedData.product_object = {};
        mappedData.options = rfpData.options;
        mappedData.status = {};

        let productDataValues = ['unit', 'cpm', 'cpc', 'budget_allocation', 'inventory', 'raw_inventory', 'price', 'content', 'custom_name', 'raw_unit'];

        mappedData.options.forEach((option, option_index) => {
            for (let i in option.config) {
                if (option.config.hasOwnProperty(i)) {

                    let product = option.config[i];

                    if (product.productType === "discount") {
                        option.discount = product.discount;
                    } else {
                        if (_.findWhere(rfpData.products, {id: i.toString()}) !== undefined) {
                            if (mappedData.product_object[i] === undefined) {
                                mappedData.product_object[i] = [];
                            }

                            let productData: any = {};
                            for (let key in product.data) {
                                if (product.data[key] === null) product.data[key] = 0;
                                if (productDataValues.indexOf(key) > -1) {
                                    if (product.productType != PRODUCT_TYPE.TV_UNIT && _.contains(['unit', 'cpm', 'cpc', 'price'], key))
                                        product.data[key] = UtilityService.formatNumber(product.data[key]);

                                    productData[key] = product.data[key];

                                    if (product.afterDiscount) {
                                        var tempProduct = _.findWhere(rfpData.products, {id: i.toString()});
                                        productData["custom_name"] = tempProduct.definition.last_name;
                                    }
                                }
                            }

                            // TODO: replace with constant values
                            switch (product.productType) {
                                case "cost_per_unit":
                                    if (rfpData.has_geofences && _.findWhere(rfpData.products, {id: i.toString()}).has_geofencing == "1") {
                                        productData.geofence_unit = product.data.geofence_impressions_total();
                                        productData.geofence_cpm = product.data.geofence_cpm;
                                    }

                                    if (product.data.type === "dollars")
                                        productData.unit = product.data.impressions_total();
                                    productData.budget_allocation = _.findWhere(rfpData.products, {id: i.toString()}).budget_allocation;
                                    break;

                                case "cost_per_inventory_unit":
                                    let inventory = _.findWhere(rfpData.products, {id: i.toString()}).inventory;
                                    productData.inventory = UtilityService.formatNumber(inventory.custom);
                                    productData.raw_inventory = inventory.default;
                                    break;

                                case "cost_per_static_unit":
                                    productData.content = _.findWhere(rfpData.products, {id: i.toString()}).content.default;
                                    productData.content = UtilityService.formatNumber(productData.content);
                                    break;

                                case PRODUCT_TYPE.TV_UNIT:
                                    productData.price = UtilityService.formatNumber(productData.price);
                                    if (product.data.customEnabled && productData.unit === "0 Network") {
                                        productData.unit = "custom";
                                        productData.spots = UtilityService.formatNumber(product.data["spots"]);
                                    }
                                    break;

                                case PRODUCT_TYPE.TV_UPLOAD:
                                    productData.price = UtilityService.formatNumber(rfpData.tv_scx_data.budget.dollars);
                                    break;
                            }

                            mappedData.product_object[i][option_index] = productData;
                        }
                    }
                }
            }
        });

        mappedData.options = JSON.stringify(mappedData.options.map((option) => {
            return {
                name: option.optionName,
                discount: option.discount || 0,
                term: option.term,
                duration: option.duration,
                grand_total: option.total()
            }
        }));

        mappedData.rooftops = JSON.stringify(rfpData.rooftops);
        mappedData.keywords_data = productConfig.showSEMComponent ? this.mapKeywordsDataToSubmission(rfpData.keywords) : [];
        mappedData.advertiser_website = mappedData.keywords_data.advertiser_website;
        mappedData.tv_zones = rfpData.tv_zones;
        mappedData.tv_selected_networks = rfpData.tv_scx_data ?
            rfpData.tv_scx_data.networks.reduce((carry, network) => {
                if (network.selected)
                    carry.push(network.name);
                return carry;
            }, []) :
            false;
        mappedData.cc_owner = rfpData.cc_owner ? 1 : 0;
        mappedData.mpq_id = rfpData.mpq_id;

        mappedData.rfp_status = {
            has_geo_products: true,
            has_audience_products: true
        };

        let mapStatusData = () =>{
            mappedData.status["is_gate_cleared"] = rfpSteps.isGateCleared;
            mappedData.status["is_targets_cleared"] = rfpSteps.isTargetsCleared;
            mappedData.status["is_budget_cleared"] = rfpSteps.isBudgetCleared;
            mappedData.status["is_builder_cleared"] = rfpSteps.isBuilderCleared;
        }

        mapIABCategories();
        mapPoliticalData();
        mapDemographicsData();
        mapStatusData();

        return mappedData;
    }

    mapKeywordsDataToSubmission(keywordsData) {
        let mappedKeywordsData: any = {};
        mappedKeywordsData.clicks = UtilityService.formatNumber(keywordsData.clicks);
        mappedKeywordsData.search_terms = keywordsData.keywords;
        mappedKeywordsData.advertiser_website = keywordsData.advertiser_website;
        return mappedKeywordsData;
    }

    mapPoliticalDataToSubmission(politicalData) {
        let values = _.flatten(_.pluck(politicalData, 'values'));
        let mappedPoliticalData: any = {};
        for (var i in values) {
            let temp: any = {};
            temp.name = "political_segment_" + values[i].id;
            temp.value = values[i].selected;
            mappedPoliticalData[i] = temp;
        }
        return mappedPoliticalData;
    }



    mapBuilderDataToSubmission(templateIds, uniqueDisplayId, mpqId, rfpSteps) {
        let mappedData: any = {};
        mappedData.template_page_ids = templateIds;
        mappedData.unique_display_id = uniqueDisplayId;
        mappedData.mpq_id = mpqId;
        mappedData.status = {};

        let mapStatusData = () =>{
            mappedData.status["is_gate_cleared"] = rfpSteps.isGateCleared;
            mappedData.status["is_targets_cleared"] = rfpSteps.isTargetsCleared;
            mappedData.status["is_budget_cleared"] = rfpSteps.isBudgetCleared;
            mappedData.status["is_builder_cleared"] = rfpSteps.isBuilderCleared;
        }

        mapStatusData();

        return mappedData;
    }

}
