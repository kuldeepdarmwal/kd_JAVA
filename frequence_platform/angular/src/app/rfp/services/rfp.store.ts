import {ProductConfigurationModel} from "../models/configuration.model";
declare var _:any;
/**
 *
 * @Author Anuteja Mallampati
 */
//-------------------------------------------------------------------
// Component Configuration Store
//-------------------------------------------------------------------
export const ConfigurationStore = (state:any = null, {type, payload}) => {
    switch (type) {
        case 'UPDATE_PRODUCT_SELECTION':
            var productConfig = <ProductConfigurationModel>{};
            var selectedProducts = _.where(payload, {selected: true});

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

        default:
            return state;
    }
};

