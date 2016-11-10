import {Component, ViewChild, OnInit} from "@angular/core";
import {ComponentInstruction, CanActivate} from "@angular/router-deprecated";
import {Observable} from "rxjs/Observable";
import {Subject} from "rxjs/Subject";
import {Store} from "@ngrx/store";
import {RFPService} from "../../services/rfp.service";
import {ProductSelectionComponent} from "./product-selection.component";
import {RFPDataModel} from "../../models/rfpdatamodel";
import {HasRFPData} from "../../services/has-rfp-data.service";
import {ProductConfigurationModel, ProductNamesModel} from "../../models/configuration.model";
import {GeographiesComponent} from "./product-inputs/geographies.component";
import {AudienceComponent} from "./product-inputs/audience.component";
import {TVZonesComponent} from "./product-inputs/tvzones.component";
import {TVSCXUploadComponent} from "./product-inputs/tv-scx-upload.component";
import {RooftopsComponent} from "./product-inputs/rooftops.component";
import {ProductModel} from "../../models/product.model";
import {OptionModel} from "../../models/option.model";
import {LocationModel} from "../../models/location.model";
import {ValidationSwitchBoard} from "../../services/validationswitch.service";
import {ValidationService} from "../../services/validation.service";
import {NavigationService} from "../../services/navigation.service";
import {GoogleMapsService} from "../../../shared/services/google-maps.service";
import {
    CONSTANTS,
    NAVIGATION,
    EVENTEMITTERS,
    PRODUCT_TYPE,
    STORE,
    STORE_NAMES
} from "../../../shared/constants/builder.constants";
import {SEMComponent} from "./product-inputs/sem.component";
import {SearchKeywordsModel} from "../../models/searchkeywords.model";
import {EmitterService} from "../../../shared/services/emitter.service";
import {UtilityService} from "../../../shared/services/utility.service";
import {BreadCrumb} from "../common/breadcrumb.navigation";
import {FooterComponent} from "../common/footer.component";
import {ProposalUtilityService, ProposalUtilityConfig} from "../../services/proposal.utility.service";
import {StepsCompletionService} from "../../services/stepscompletion.service";
import {HeaderComponent} from "../common/header.component";
import {AutoSaveService} from "../../services/autosave.service";
import {InterestEngineComponent} from "./product-inputs/interest-engine.component";

declare var _: any;
declare var jQuery: any;
declare var Materialize: any;

declare interface IDsObject {
    product_id: number;
    option_id: number;
}

@Component({
    selector: 'targeting',
    templateUrl: '/angular/build/app/views/rfp/targets/targeting.html',
    directives: [HeaderComponent, ProductSelectionComponent, GeographiesComponent, AudienceComponent, InterestEngineComponent,
        TVZonesComponent, RooftopsComponent, TVSCXUploadComponent, SEMComponent, BreadCrumb, FooterComponent],
    providers: [GoogleMapsService, ValidationSwitchBoard, ValidationService, AutoSaveService]
})

@CanActivate(
    (next: ComponentInstruction, prev: ComponentInstruction) => {
        return HasRFPData(next, prev);
    }
)
export class TargetingComponent implements OnInit {

    private userData: any;
    private products: ProductModel[];
    private displayProducts: ProductModel[];
    private displayBudgetProducts: ProductModel[];
    private discountProducts: ProductModel[];
    private ProductConfig: Observable<any>;
    private productConfig: ProductConfigurationModel;
    private productNamesMap: ProductNamesModel = <ProductNamesModel>{};
    private cost_per_unit_required: boolean;
    private uniqueDisplayId: string;

    private options: OptionModel[];
    private displayOptions: OptionModel[];

    private termDropDown: any[];
    private durationDropDown: any[];

    private tvZones: any[];
    private tvScxData: any;
    private rooftops: any[];
    private keywords: SearchKeywordsModel;
    private locations: any[];
    private geofencing_defaults: any;
    private geofence_inventory: number;
    private has_geofencing: any;

    private selectedLocation: LocationModel;
    private demographics: any[];
    private politicalData: any[];
    private audienceInterests: any[];

    private _advertiserName: string;
    private _advertiserSite: string;
    private _proposalName: string;
    private _opportunityOwnerName: string;
    private cc_owner: any;
    private currentMenu: string;
    private confirmLargeRegions: Subject<boolean>;

    @ViewChild(GeographiesComponent) geographyChild: GeographiesComponent;

    constructor(private rfpService: RFPService,
                private rfpDataModel: RFPDataModel,
                private googleMapsService: GoogleMapsService,
                private store: Store<ProductConfigurationModel>,
                private navigationService: NavigationService,
                private validationService: ValidationService,
                private proposalUtility: ProposalUtilityService,
                private stepsCompletionService: StepsCompletionService,
                private AutoSaveService: AutoSaveService) {
        this.stepsCompletionService.clearTargets();
        this.loadData();
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
    }

    ngOnInit() {
        this.ProductConfig = this.store.select(STORE_NAMES.CONFIGURATION);
        this.ProductConfig.subscribe(config => this.loadConfig(config));
    }

    loadConfig(config) {
        this._advertiserName = this.rfpDataModel.advertiserName;
        this._proposalName = this.rfpDataModel.proposalName;
        this._opportunityOwnerName = this.rfpDataModel.opportunityOwner.opportunityOwnerName;
        this.productConfig = config;
        this.options = this.rfpDataModel.options;
        this.rfpDataModel.productConfig = config;
        if (this.productConfig.showTvComponent) this.updatePriceForNetworks();
        this.buildProductStatusMap();
        this.setContainerHeight();
    }

    setContainerHeight() {
        var bodyH = jQuery("body").height();
        var navH = jQuery(".navbar").height();
        var h = bodyH - navH;
        jQuery("#main_container").height(h);
        jQuery("#main_container").css("overflow-x", "auto");
    }

    resizeWindow() {
        this.setContainerHeight();
    }

    loadData() {
        this.currentMenu = NAVIGATION.TARGETS;
        this.userData = this.rfpDataModel.userData;
        this.products = this.rfpDataModel.products;
        this.displayProducts = _.where(this.products, {selected: true})
        this.discountProducts = this.rfpDataModel.discountProducts;
        this.demographics = this.rfpDataModel.demographics;
        this.politicalData = this.rfpDataModel.politicalData;
        this.audienceInterests = this.rfpDataModel.audienceInterests;
        this.rooftops = this.rfpDataModel.rooftops;
        this.tvZones = this.rfpDataModel.tvZones;
        this.tvScxData = this.rfpDataModel.tvScxData;
        this.keywords = this.rfpDataModel.keywords;
        this.cc_owner = this.rfpDataModel.ccOwner;
        this.locations = this.rfpDataModel.locations;
        this.geofencing_defaults = this.rfpDataModel.geofencingDefaults;
        this.selectedLocation = this.locations[0];
        this.options = this.rfpDataModel.options;
        this.displayOptions = _.where(this.options, {selected: true});
        this.termDropDown = this.rfpDataModel.termDropDown;
        this.durationDropDown = this.rfpDataModel.durationDropDown;
        this.cost_per_unit_required = _.findWhere(this.rfpDataModel.strategies, {strategyId: this.rfpDataModel.strategyId}).cost_per_unit_required;
        this.disableOtherDigitalProductIfOnlyOneSelected();
        this.store.dispatch({type: STORE.PRODUCT_SELECTION, payload: this.displayProducts});
        this.uniqueDisplayId = this.rfpDataModel.uniqueDisplayId;
    }

    buildProductStatusMap() {
        this.productNamesMap.audience = UtilityService.getNamesOfProducts(_.where(this.displayProducts, {is_audience_dependent: "1"}));
        this.productNamesMap.geos = UtilityService.getNamesOfProducts(_.where(this.displayProducts, {is_geo_dependent: "1"}));
        this.productNamesMap.rooftops = UtilityService.getNamesOfProducts(_.where(this.displayProducts, {is_rooftops_dependent: "1"}));
        this.productNamesMap.tvzones = UtilityService.getNamesOfProducts(_.where(this.displayProducts, {is_zones_dependent: "1"}));
        this.productNamesMap.tv_upload = UtilityService.getNamesOfProducts(_.where(this.displayProducts, {product_type: "tv_scx_upload"}));
        this.productNamesMap.political = UtilityService.getNamesOfProducts(_.where(this.displayProducts, {is_political: "1"}));
        this.productNamesMap.keywords = UtilityService.getNamesOfProducts(_.where(this.displayProducts, {is_keywords_dependent: "1"}));
    }

    toggleProduct(product: ProductModel) {
        if (!product.disabled) {
            let _product: ProductModel = _.findWhere(this.products, {id: product.id});
            _product.selected = !_product.selected;
            if (product.product_type === PRODUCT_TYPE.COST_UNIT && this.cost_per_unit_required) {
                this.enableAtleastOneDigitalProduct(product);
            }
            this.getDigitalProductStatusMap();
            this.displayProducts = _.where(this.products, {selected: true});
            this.store.dispatch({type: STORE.PRODUCT_SELECTION, payload: this.displayProducts});
        }
    }

    disableOtherDigitalProductIfOnlyOneSelected() {
        if (this.cost_per_unit_required) {
            let product = _.filter(this.products, function (product: ProductModel) {
                // return true for every valid entry!
                return product.product_type === PRODUCT_TYPE.COST_UNIT && !product.selected;
            });
            if (product.length === 1)this.enableAtleastOneDigitalProduct(product[0]);
        }
    }

    // Assuming there are only 2 Digital Products
    // Should be changed once backend support is in place
    enableAtleastOneDigitalProduct(product) {
        var productStatusMap = this.getDigitalProductStatusMap();
        delete productStatusMap[product.id];
        var otherProductId = _.keys(productStatusMap)[0];
        _.findWhere(this.products, {id: otherProductId}).disabled = !product.selected;
    }

    getDigitalProductStatusMap() {
        let digitalProducts: ProductModel[] = _.where(this.products, {product_type: PRODUCT_TYPE.COST_UNIT});
        let productStatusMap: any = {};
        for (let product of digitalProducts) productStatusMap[product.id] = product.selected;
        return productStatusMap;
    }

    updateZones(tvZones) {
        this.rfpDataModel.tvZones = tvZones;
        this.tvZones = tvZones;
        this.updatePriceForNetworks();
    }

    uploadStrataFile(data) {
        data.networks.forEach((network, i) => {
            network.selected = i < CONSTANTS.MAX_TOP_NETWORKS ? true : false;
        });
        this.rfpDataModel.tvScxData = data;
        this.tvScxData = data;

        this.options.forEach((option) => {
            option.term = 'monthly';
            option.duration = Math.ceil(this.tvScxData.budget.weeks / 4.333);
        });
        // this.autoSaveData();
    }

    removeScxData() {
        this.rfpDataModel.tvScxData = false;
        this.tvScxData = false;
    }

    networkChanged() {
        this.updatePriceForNetworks();
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

    loadRegions(selectedRegion: LocationModel) {
        switch (selectedRegion.search_type) {
            case "custom_regions":
                this.rfpService.getZipsFromCustomRegions(selectedRegion, this.rfpDataModel.mpqId)
                    .subscribe((regions) => {
                        if (regions.length > 0) {
                            let zips_array = [];
                            regions.forEach((region) => {
                                let zips = JSON.parse(region.regions);
                                zips.forEach((zip) => {
                                    if (zips_array.indexOf(zip) === -1) {
                                        zips_array.push(zip);
                                    }
                                });
                            });
                            zips_array = zips_array.concat(selectedRegion.affected_regions);
                            selectedRegion.ids.zcta = zips_array;

                            if (zips_array.length > CONSTANTS.GEOS.MAX_ZIPS_PER_LOCATION){
                                Materialize.toast('Your custom regions contain too many zips. Consider moving some of them to another location.', 5000, 'error-toast');
                                return false;
                            } else if (zips_array.length >= CONSTANTS.GEOS.WARN_ZIPS_PER_LOCATION) {
                                jQuery('#geos_warn_custom_regions_search').openModal();
                                let subscription = this.confirmLargeRegionsSubscription();
                                subscription.subscribe(() => {
                                    this.rfpService.saveZips(selectedRegion, this.rfpDataModel.mpqId)
                                        .subscribe((res) => this.mapSaveZipsResponse(selectedRegion, res));
                                });
                            } else {
                                this.rfpService.saveZips(selectedRegion, this.rfpDataModel.mpqId)
                                    .subscribe((res) => this.mapSaveZipsResponse(selectedRegion, res));
                            }
                        } else {
                            Materialize.toast('No regions were found for your search. Please try again.', 3000, 'error-toast');
                        }
                    });
                break;
            case "radius":
                this.rfpService.removeCustomRegions(selectedRegion.page, this.rfpDataModel.mpqId)
                    .subscribe((res) => {
                        this.googleMapsService.getCoords(selectedRegion.address)
                            .then((result: any) => {
                                this.rfpService.saveZipsFromRadius(
                                    selectedRegion.page,
                                    selectedRegion.counter,
                                    selectedRegion.address,
                                    result.geometry.location.lat(),
                                    result.geometry.location.lng(),
                                    this.rfpDataModel.mpqId)
                                    .subscribe((res) => {
                                        selectedRegion.ids.zcta = selectedRegion.ids.zcta.concat(selectedRegion.affected_regions);
                                        this.mapSaveZipsResponse(selectedRegion, res);
                                    });
                            }, (err) => {
                                console.error(err);
                            });
                    });
                break;
            case "known_zips":
                this.rfpService.removeCustomRegions(selectedRegion.page, this.rfpDataModel.mpqId)
                    .subscribe((res) => {
                        selectedRegion.ids.zcta = selectedRegion.ids.zcta.concat(selectedRegion.affected_regions);
                        this.rfpService.saveZips(selectedRegion, this.rfpDataModel.mpqId)
                            .subscribe((res) => this.mapSaveZipsResponse(selectedRegion, res));
                    });
                break;
        }
    }

    confirmLargeRegionsSubscription(){
        this.confirmLargeRegions = new Subject<boolean>();
        let confirmLargeRegions = this.confirmLargeRegions.asObservable();

        jQuery('#geos_warn_custom_regions_search').openModal();

        return confirmLargeRegions;
    }

    mapSaveZipsResponse(selectedRegion:LocationModel, response) {
        this.locations[selectedRegion.page].user_supplied_name = response.custom_location_name;
        this.locations[selectedRegion.page].ids.zcta = response.zips;
        this.locations[selectedRegion.page].total = response.zips.length;
        this.locations[selectedRegion.page].location_population = response.location_population;
        (<HTMLIFrameElement> document.querySelector('#region-links iframe')).contentWindow.location.reload(true);
    }

    addLocation() {
        let newLocation: LocationModel = this.rfpDataModel.emptyLocation;
        this.rfpService.addLocation(newLocation, this.rfpDataModel.mpqId)
            .subscribe((res) => {
                this.locations.push(newLocation);
                this.geographyChild.cloneGeofences(this.locations.length - 1);
                this.geographyChild.selectLocation(this.locations.length - 1);
            });
    }

    removeLocation(location_id: number) {
        this.rfpService.removeLocation(location_id, this.rfpDataModel.mpqId)
            .subscribe((res) => {
                this.locations.splice(location_id, 1);

                this.locations.forEach((location, i) => {
                    location.template = i;
                });

                this.rfpService.saveGeofences({
                    mpq_id: this.rfpDataModel.mpqId,
                    location_id: location_id,
                    geofences: false
                })
                    .subscribe((res) => {
                        this.geographyChild.removeGeofence(location_id);
                    });

                if (this.geographyChild.selectedLocationId === location_id) {
                    this.geographyChild.selectLocation(0);
                }
            });
    }

    selectLocation(index: number) {
        if (this.locations[index] !== undefined) {
            this.locations.forEach((location) => {
                location.selected = false;
            });
            this.locations[index].selected = true;
        }
    }

    updateCustomRegions(location: LocationModel) {
        this.locations[location.page].custom_regions = location.custom_regions;
    }

    uploadBulkLocations(locations: Array<any>) {
        // Assuming it's a radius-based entry here, because population-based and both no longer work.
        // TODO: get population-based uploads working or remove those options.
        if (locations.length > 0) {
            let new_locations = [];

            locations.forEach((new_location) => {
                let location = this.rfpDataModel.emptyLocation;
                location.ids = {zcta: new_location.regions.split(', ')};
                location.page = new_location.location_id;
                location.search_type = "radius";
                location.selected = false;
                location.total = location.ids.zcta.length;
                location.user_supplied_name = new_location.location_name;
                location.counter = new_location.geo_dropdown_options.radius;
                location.address = new_location.geo_dropdown_options.address;
                location.location_population = new_location.location_population;

                new_locations.push(location);
            });

            if (this.locations.length === 1 && this.locations[0].ids.zcta.length === 0) {
                this.locations = new_locations;
            } else {
                this.locations.concat(new_locations);
            }

            this.geographyChild.selectLocation(this.locations.length - 1);
        }
    }

    saveGeofences(location_id: number) {
        let geofencesObj = {
            geofences: _.map(this.locations[location_id].geofences, (geofence) => {
                return {
                    search: geofence.address,
                    latlng: geofence.latlng,
                    radius: geofence.type === "proximity" ? geofence.proximity_radius : this.geofencing_defaults.radius.CONQUESTING,
                    type: geofence.type
                }
            }),
            mpq_id: this.rfpDataModel.mpqId,
            location_id: location_id
        }

        if (geofencesObj.geofences.length === 0) {
            geofencesObj.geofences = false;
        }

        this.rfpService.saveGeofences(geofencesObj)
            .subscribe((res: any) => {
                this.geographyChild.closeModal(location_id);
                if (res.missing_geofence_regions !== undefined && res.missing_geofence_regions.length > 0) {
                    let missing_zips = res.missing_geofence_regions.join(", ");
                    Materialize.toast('The following zips were added to your location:<br />' + missing_zips, 5000, 'toast-primary');
                    this.locations[location_id].ids.zcta = this.locations[location_id].ids.zcta.concat(res.missing_geofence_regions);
                    this.locations[location_id].search_type = 'known_zips';
                }
                if (res.affected_regions !== undefined && res.affected_regions.length > 0) {
                    this.locations[location_id].affected_regions = res.affected_regions;
                }
                if (res.location_population !== undefined && res.location_population > 0) {
                    this.locations[location_id].location_population = res.location_population;
                }
                if (res.geofence_inventory !== undefined) {
                    this.rfpDataModel.geofenceLocationsInventory[location_id] = res.geofence_inventory;
                }
                (<HTMLIFrameElement>document.querySelector('#region-links iframe')).contentWindow.location.reload(true);
                this.geographyChild.cloneGeofences(location_id);
            });
    }

    updateAudienceInterests(audienceInterests: any) {
        this.rfpDataModel.audienceInterests = audienceInterests;
    }

    updateRooftops(rooftops_obj: any) {
        this.rfpDataModel.rooftops = rooftops_obj;
    }

    //Footer Functions
    saveTargets() {
        this.proposalUtility.process(this.buildProposalUtilityConfig(false, false, NAVIGATION.BUDGET));
    }

    goToBudget(){
        this.proposalUtility.process(this.buildProposalUtilityConfig(true, true, NAVIGATION.BUDGET));
    }

    goToBuilder(){
        this.proposalUtility.process(this.buildProposalUtilityConfig(true, true, NAVIGATION.BUILDER));
    }

    goBack() {
        this.navigationService.navigate(NAVIGATION.TARGETS, NAVIGATION.GATE);
    }

    next() {
        this.proposalUtility.process(this.buildProposalUtilityConfig(true, true, NAVIGATION.BUDGET));
    }

    buildProposalUtilityConfig(allowNavigation: boolean, doValidation: boolean, navigateTo : string) {
        return this.proposalUtility.buildProposalUtilityConfig
        (allowNavigation, doValidation, NAVIGATION.TARGETS, navigateTo,
            this.proposalUtility.saveTargets(), this.validationService.validateTargeting)
    }
}
