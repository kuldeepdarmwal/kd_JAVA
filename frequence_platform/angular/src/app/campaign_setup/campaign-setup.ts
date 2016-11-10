import "rxjs/Rx";
import {Component, ComponentRef, provide, Injector, enableProdMode} from "@angular/core";
import {RouteConfig, ROUTER_DIRECTIVES, ROUTER_PROVIDERS} from "@angular/router-deprecated";
import {HTTP_PROVIDERS} from "@angular/http";
import {bootstrap} from "@angular/platform-browser-dynamic";
import {appInjector} from "../shared/utils/app-injector";
import {Loader} from "../shared/directives/loader.directive";
import {CampaignSetupFlightsComponent} from "./components/campaign-setup-flights.component";
import {IOService} from "../io/services/io.service";
import {IODataModel} from "../io/models/iodatamodel";
import {CampaignSetupDataModel} from "./models/campaign-setup-data-model";
import {IOMapperService} from "../io/services/iomapper.service";
import {CampaignSetupMapperService} from "./services/campaign-setup-mapper.service";
import {GoogleMapsService} from "../shared/services/google-maps.service";
import {ValidationSwitchBoard} from "../rfp/services/validationswitch.service";
import {RFPService} from "../rfp/services/rfp.service";
import {IOPropertiesBuilder} from "../io/utils/io-propertiesbuilder.utility";
import {IOUtilityService} from "../io/services/io.utility.service";

enableProdMode();

@Component({
	selector: 'campaign_setup',
	directives: [ROUTER_DIRECTIVES, Loader],
	templateUrl : '/angular/build/app/views/builder.html'
})
@RouteConfig([
    { path: '/campaign_setup/edit_flights', name: 'Campaign_setup', component: CampaignSetupFlightsComponent },
    { path: '/campaign_setup/edit_flights/:campaignId', name: 'Campaign_setup_edit_flights', component: CampaignSetupFlightsComponent },
])
export class CampaignSetupComponent { }

bootstrap(CampaignSetupComponent, [
	HTTP_PROVIDERS, 
	ROUTER_PROVIDERS,
	provide(IOService, {useClass: IOService}),
	provide(IODataModel, {useClass: IODataModel}),
	provide(CampaignSetupMapperService, {useClass: CampaignSetupMapperService}),
	provide(CampaignSetupDataModel, {useClass: CampaignSetupDataModel}),
	provide(RFPService, {useClass: RFPService}),
	provide(GoogleMapsService, {useClass: GoogleMapsService}),
	provide(ValidationSwitchBoard, {useClass: ValidationSwitchBoard}),
	provide(IOMapperService, {useClass: IOMapperService}),
	provide(IOUtilityService, {useClass: IOUtilityService}),
    provide(IOPropertiesBuilder, {useClass : IOPropertiesBuilder})
]).then((appRef: ComponentRef<Injector>) => {
	appInjector(appRef.injector);
});