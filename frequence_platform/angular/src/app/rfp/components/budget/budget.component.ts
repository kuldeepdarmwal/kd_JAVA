import {Component} from "@angular/core";
import {ComponentInstruction, CanActivate} from "@angular/router-deprecated";
import {Store} from "@ngrx/store";
import {Observable} from "rxjs/Rx";
import {MaterializeDirective} from "angular2-materialize";
import {RFPDataModel} from "../../models/rfpdatamodel";
import {ProductModel} from "../../models/product.model";
import {OptionModel} from "../../models/option.model";
import {ProductConfigurationModel} from "../../models/configuration.model";
import {NAVIGATION, EVENTEMITTERS, STORE, STORE_NAMES, PRODUCT_TYPE} from "../../../shared/constants/builder.constants";
import {ValidationSwitchBoard} from "../../services/validationswitch.service";
import {HasRFPData} from "../../services/has-rfp-data.service";
import {ValidationService} from "../../services/validation.service";
import {NavigationService} from "../../services/navigation.service";
import {EmitterService} from "../../../shared/services/emitter.service";
import {UtilityService} from "../../../shared/services/utility.service";
import {ProposalUtilityService, ProposalUtilityConfig} from "../../services/proposal.utility.service";
import {RFPService} from "../../services/rfp.service";
import {StepsCompletionService} from "../../services/stepscompletion.service";
import {OrderBy} from "../../../shared/pipes/orderby.pipe";
import {TitleCase} from "../../../shared/pipes/titlecase.pipe";
import {NumberFormat} from "../../../shared/pipes/number_format.pipe";
import {ProductFilter} from "../../../shared/pipes/product-filter.pipe";
import {HeaderComponent} from "../common/header.component";
import {BreadCrumb} from "../common/breadcrumb.navigation";
import {BudgetProductComponent} from "./budget-product.component";
import {DiscountProductComponent} from "./budget-products/discount-product.component";
import {FooterComponent} from "../common/footer.component";

declare var _: any;
declare var jQuery: any;
declare var Materialize: any;

declare interface IDsObject {
    product_id: number;
    option_id: number;
}

@Component({
    selector: 'budget',
    templateUrl: '/angular/build/app/views/rfp/budget/budget.html',
    pipes: [OrderBy, TitleCase, NumberFormat, ProductFilter],
    directives: [HeaderComponent, BudgetProductComponent, DiscountProductComponent, MaterializeDirective, BreadCrumb, FooterComponent],
    providers: [ValidationSwitchBoard, ValidationService]
})
@CanActivate(
    (next: ComponentInstruction, prev: ComponentInstruction) => {
        return HasRFPData(next, prev);
    }
)
export class BudgetComponent {

    private validationStatus: boolean = true;

    private userData: any;
    private products: ProductModel[];
    private displayProducts: ProductModel[];
    private discountProducts: ProductModel[];
    private afterDiscountProducts: ProductModel[];
    private ProductConfig: Observable<any>;

    private productConfig: ProductConfigurationModel;
    private options: OptionModel[];
    private displayOptions: OptionModel[];
    private locations: any[];

    private has_geofencing: any;
    private termDropDown: any[];
    private durationDropDown: any[];

    private currentMenu: string;

    ngOnInit() {
        this.ProductConfig = this.store.select(STORE_NAMES.CONFIGURATION);
        this.ProductConfig.subscribe(config => this.loadConfig(config));
    }

    constructor(private rfpDataModel: RFPDataModel,
                private navigationService: NavigationService,
                private validationService: ValidationService,
                private rfpService: RFPService,
                private store: Store<ProductConfigurationModel>,
                private proposalUtilityService: ProposalUtilityService,
                private stepsCompletionService: StepsCompletionService) {
        this.stepsCompletionService.clearBudget();
        this.loadData();
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
    }

    loadConfig(config) {
        this.productConfig = config;
        this.options = this.rfpDataModel.options;
        this.rfpDataModel.productConfig = config;
        this.has_geofencing = config.showGeofencingComponent;
    }

    loadData() {
        this.userData = this.rfpDataModel.userData;
        this.currentMenu = NAVIGATION.BUDGET;
        this.products = this.rfpDataModel.products;
        this.displayProducts = _.where(this.products, {selected: true});
        this.discountProducts = this.rfpDataModel.discountProducts;
        this.afterDiscountProducts = this.rfpDataModel.afterDiscountProducts;
        this.options = this.rfpDataModel.options;
        this.displayOptions = _.where(this.options, {selected: true});
        this.locations = this.rfpDataModel.locations;
        this.termDropDown = this.rfpDataModel.termDropDown;
        this.durationDropDown = this.rfpDataModel.durationDropDown;
        this.setContainerHeight();
        this.store.dispatch({type: STORE.PRODUCT_SELECTION, payload: this.displayProducts});
    }

    showValidationMessages(config) {
        if (config !== null) {
            this.validationStatus = config.budget.status;
        }
    }

    setContainerHeight() {
        var bodyH = jQuery("body").height();
        var navH = jQuery(".navbar").height();
        var h = bodyH - navH;
        jQuery("#main_container").height(h);
        jQuery("#main_container").css("overflow-y", "auto");
    }

    resizeWindow() {
        this.setContainerHeight();
    }

    openBudgetName(event) {
        var text = event.target.parentNode;
        var input = text.parentNode.querySelector('.budget_option_title');
        text.style.cssText = "display: none;";
        input.style.cssText = "display: inline-block;";
        input.focus();
    }

    closeBudgetName(event) {
        var input = event.target;
        var text = input.parentNode.querySelector('.budget_option_text_container');
        input.style.cssText = "display: none;";
        text.style.cssText = "display: inline-block;";
    }

    addOption(e) {
        _.findWhere(this.options, {selected: false})
            .selected = true;
        this.displayOptions = _.where(this.options, {selected: true});
    }

    removeOption(optionId: number) {
        _.findWhere(this.options, {optionId: optionId})
            .selected = false;
        this.displayOptions = _.where(this.options, {selected: true});
    }

    changeImpressionsType(ids: IDsObject) {
        let option: OptionModel = _.findWhere(this.options, {optionId: ids.option_id})
        let productConfigData = option.config[ids.product_id].data;
        productConfigData.unit = productConfigData.type === "dollars" ?
            Math.round(UtilityService.formatNumber(productConfigData.unit) * UtilityService.formatNumber(productConfigData.cpm) / 1000) :
            Math.round(UtilityService.formatNumber(productConfigData.unit) * 1000 / UtilityService.formatNumber(productConfigData.cpm));
    }

    getTermText() {
        return this.options.reduce((term, option) => {
            if (option.selected === false) {
                return term;
            }
            return term === option.term ? term : '';
        }, this.options[0].term);
    }

    durationChanged(optionId: number) {
        EmitterService.get(EVENTEMITTERS.DURATION_CHANGED).emit(optionId);
    }

    networkChanged() {
        this.updatePriceForNetworks();
    }

    preDiscountDisplayProducts() {
        return this.displayProducts.filter((product) => {
            return !product.definition.after_discount;
        });
    }

    afterDiscountDisplayProducts() {
        return this.displayProducts.filter((product) => {
            return product.definition.after_discount;
        });
    }

    updatePriceForNetworks() {
        let tvZones = this.rfpDataModel.tvZones;
        var tvData = [];
        for (var i in this.options) tvData.push(_.findWhere(this.options[i].config, {productType: PRODUCT_TYPE.TV_UNIT}).data);
        if (tvZones.length > 0) {
            let zoneIds: string[] = _.pluck(tvZones, 'id');
            var networks = _.uniq(_.pluck(_.where(tvData, {customEnabled: false}), 'unit'));
            this.getPricingByZonesAndUpdate(zoneIds, networks, tvData);
        } else {
            for (var tvObj of tvData) {
                if (!tvObj.customEnabled) tvObj.price = 0;
            }
        }
    }

    getPricingByZonesAndUpdate(zoneIds, networks, tvData) {
        this.rfpService.getTVPricingByZones({zones: zoneIds, packs: networks}) .subscribe((res) => {
            var prices = res.data;
            for (var i in prices) {
                var tvObjArr = _.where(tvData, {unit: prices[i].pack_name});
                for (var tvObj of tvObjArr)
                    tvObj.price = parseInt(prices[i].price);
            }
        });
    }


    //Footer Functions
    saveBudget() {
        this.proposalUtilityService.process(this.buildProposalUtilityConfig(false, false, NAVIGATION.BUILDER));
    }

    goToTargets(){
        this.proposalUtilityService.process(this.buildProposalUtilityConfig(true, true, NAVIGATION.TARGETS));
    }

    goToBuilder(){
        this.proposalUtilityService.process(this.buildProposalUtilityConfig(true, true, NAVIGATION.BUILDER));
    }

    goBack() {
        this.navigationService.navigate(NAVIGATION.BUDGET, NAVIGATION.TARGETS);
    }

    next() {
        this.proposalUtilityService.process(this.buildProposalUtilityConfig(true, true, NAVIGATION.BUILDER));
    }

    buildProposalUtilityConfig(allowNavigation:boolean, doValidation : boolean, navigateTo : string) {
        return this.proposalUtilityService.buildProposalUtilityConfig
        (allowNavigation, doValidation, NAVIGATION.BUDGET, navigateTo,
            this.proposalUtilityService.saveBudget(), this.validationService.validateBudget)
    }
}
