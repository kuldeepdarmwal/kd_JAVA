import {Component, Input, Output, EventEmitter, ViewChild, ElementRef, OnDestroy, OnInit} from "@angular/core";
import {SERVICE_URL, PLACEHOLDERS, EVENTEMITTERS, ERRORS} from "../../shared/constants/builder.constants";
import {EmitterService} from "../../shared/services/emitter.service";
import {Select2Directive} from "../../shared/directives/select2.directive";
import {MaterializeDirective} from "angular2-materialize";
import {ProductFilter} from "../../shared/pipes/product-filter.pipe";
import {ProductRegionFilter} from "../../shared/pipes/product-region-filter.pipe";
import {ProductModel} from "../models/product.model";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";
import {IOMapperService} from "../services/iomapper.service";
declare var jQuery:any;
declare var _:any;

@Component({
    selector: 'creatives',
    templateUrl: '/angular/build/app/views/io/creatives.html',
    directives: [Select2Directive, MaterializeDirective],
    pipes: [ProductFilter, ProductRegionFilter]
})
export class CreativesComponent implements OnInit {
    private selectedProductId: any;
    private static selectedProduct: any;
    private selectedRegionId: any;
    private showAllVersionsBool: boolean = false;
    private static showAllVersions: boolean;
    private static user_id: any;

    @Input('products') products;
    @Input('locations') locations;
    @Input('validation') validation: ValidationStatusConfigModel;
    @Input('userId') userId;
    @ViewChild('creativeSelect') creativeSelect:ElementRef;
    @ViewChild('creativeAssignSelect') creativeAssignSelect:ElementRef;

    @Output('update-creatives') updateCreatives = new EventEmitter<any>();
    @Output('new-creative-request') newCreativeRequest = new EventEmitter<any>();

    creativeSelectObj = {};

    constructor(private ioMapperService : IOMapperService){ }

    ngOnInit(){
        CreativesComponent.selectedProduct = this.selectedProductId;
        CreativesComponent.showAllVersions = this.showAllVersionsBool;
        CreativesComponent.user_id = this.userId;
        this._buildPropertiesForCreatives();
        console.log(this);
    }

    preloadCreativesSelect(product_id, region){
        this.selectedProductId = product_id;
        CreativesComponent.selectedProduct = product_id;

        this.selectedRegionId = region;

        jQuery(this.creativeSelect.nativeElement).select2('data', _.findWhere(this.products, { id: product_id }).creatives.filter((creative) => {
            return creative.regionId == this.selectedRegionId;
        }));
    }

    clearAssignCreativesSelect(product_id){
        this.selectedProductId = product_id;
        CreativesComponent.selectedProduct = product_id;
        jQuery(this.creativeAssignSelect.nativeElement).select2('data', []);
    }

    changeShowAllVersions(){
        CreativesComponent.showAllVersions = !this.showAllVersionsBool;
    }

    addCreatives(){
        let creatives = _.findWhere(this.products, { id: this.selectedProductId }).creatives.filter((creative) => {
            return creative.regionId != this.selectedRegionId;
        });

        let newCreatives = jQuery(this.creativeSelect.nativeElement).select2('data');
        newCreatives.forEach((creative) => {
            if (creative.creative_id !== undefined){
                creatives.push(creative);
            } else {
                creative = this.ioMapperService.mapSelect2ResponseToCreative(creative);
                creative.regionId = parseInt(this.selectedRegionId);
                creatives.push(creative);
            }
        });

        this.updateCreatives.emit({
            updatedCreatives: newCreatives, 
            allCreatives: creatives,
            product: this.selectedProductId, 
            region: this.selectedRegionId
        });
    }

    assignCreatives(){
        let creatives = _.findWhere(this.products, { id: this.selectedProductId }).creatives;

        let newCreatives = jQuery(this.creativeAssignSelect.nativeElement).select2('data');
        newCreatives.forEach((creative) => {
            if (creative.creative_id === undefined){
                creative = this.ioMapperService.mapSelect2ResponseToCreative(creative);
                creative.productId = this.selectedProductId;
            }
            this.locations.forEach((location,i) => {
                creative.regionId = i;
                creatives.push(_.clone(creative));
            });
        });

        this.updateCreatives.emit({
            updatedCreatives: newCreatives, 
            allCreatives: creatives,
            product: this.selectedProductId
        });
    }

    //Building Properties for directive
    _buildPropertiesForCreatives() {
        var self = this;

        this.creativeSelectObj = {
            url: SERVICE_URL.RFP.CREATIVES,
            placeHolder: PLACEHOLDERS.CREATIVES,
            resultFormatFn: this._formatResultsInterestsFn,
            emitter: EmitterService.get(EVENTEMITTERS.CREATIVES),
            dataFn: this._dataInterestsFn,
            allowClear: true,
            allowMultiple: true,
            minLength: 0,
            resultFn: this._resultFn
        };
    }

    _formatResultsInterestsFn(data) {
        var thumb_html = '<img src="'+data.normal_thumb+'" style="width: 100px;">';
        if(data.normal_thumb == null)
        {
            thumb_html = '<div class="io_creative_no_preview_available">No Preview Available</div>';
        }

        var creative_html = "";
        if(data.show_for_io == 1)
        {
            creative_html += ''+
                '<table class="io_creative_results_table">'+
                    '<tr>'+
                        '<td rowspan="3" class="io_creative_thumb">'+thumb_html+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Adset:</span><br>'+data.text+' <small>v'+data.version+'.'+data.variation_name+'</small></td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Landing Page:</span><br>'+data.landing_page+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Last Saved:</span><br>'+data.time_created+' GMT</td>'+
                    '</tr>'+
                    '<tr>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser:</span><br>'+data.advertiser_name+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser Web Page:</span><br>'+data.website+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Request Type:</span><br>'+data.request_type+'</td>'+
                    '</tr>'+
                    '<tr>'+
                        '<td colspan="3" class="io_creative_normal_table_data"><span class="io_creative_small">Preview Link:</span><br><a class="io_creative_link_anchor" href="'+data.gallery_link+'" target="_blank">'+data.gallery_link+'<i class="material-icons">&#xE89E;</i></a></td>'+
                    '</tr>'+
                '</table>';
        }
        else
        {    //TODO: Add something to differentiate banner intakes from creatives.
            creative_html += ''+
                '<table class="io_creative_results_table banner_intake_result">'+
                    '<tr>'+
                        '<td rowspan="3" class="io_creative_thumb">'+thumb_html+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small banner_intake_header"><i class="material-icons io_icon_single_creative_status io_icon_on_hold" style="font-size:12px;top:2px;">&#xE924;</i>Creative Request:</span><br>'+data.text+' <small>v'+data.version+'.'+data.variation_name+'</small></td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Landing Page:</span><br>'+data.landing_page+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Last Saved:</span><br>'+data.time_created+' GMT</td>'+
                    '</tr>'+
                    '<tr>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser:</span><br>'+data.advertiser_name+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser Web Page:</span><br>'+data.website+'</td>'+
                        '<td class="io_creative_normal_table_data"><span class="io_creative_small">Request Type:</span><br>'+data.request_type+'</td>'+
                    '</tr>'+
                '</table>';
        }
        return creative_html;
    }

    _resultFn(data) {
        return {results: data.results, more: data.more};
    }

    _dataInterestsFn(term, page) {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page,
            user_id: CreativesComponent.user_id,
            product_id: CreativesComponent.selectedProduct,
            show_all_versions: CreativesComponent.showAllVersions
        };
    }

    closeModal() {
        jQuery('#io_creatives_modal').closeModal();
        jQuery('#io_assign_creatives_modal').closeModal();
    }
}