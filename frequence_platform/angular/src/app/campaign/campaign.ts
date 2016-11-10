import "rxjs/Rx";
import {provideStore} from "@ngrx/store";
import {Component, ComponentRef, provide, Injector, enableProdMode} from "@angular/core";
import {RouteConfig, ROUTER_DIRECTIVES, ROUTER_PROVIDERS} from "@angular/router-deprecated";
import {HTTP_PROVIDERS} from "@angular/http";
import {bootstrap} from "@angular/platform-browser-dynamic";
import {CampaignComponent} from "./components/campaign.component";
import {appInjector} from "../shared/utils/app-injector";
import {CampaignDataModel} from "./models/campaigndatamodel";
import {CampaignsService} from "./services/campaign.service";
import {CampaignMapperService} from "./services/campaignmapper.service";
import {Loader} from "../shared/directives/loader.directive";

enableProdMode();

@Component({
	selector: 'campaign',
	directives: [ROUTER_DIRECTIVES, Loader],
	templateUrl : '/angular/build/app/views/builder.html'
})
@RouteConfig([
        { path: '/campaigns', name: 'Campaigns', component: CampaignComponent }
])
export class CampaignsComponent { }

bootstrap(CampaignsComponent, [
	HTTP_PROVIDERS, 
	ROUTER_PROVIDERS,
	provide(CampaignDataModel, {useClass: CampaignDataModel}),
	provide(CampaignsService, {useClass: CampaignsService}),
        provide(Window, {useValue: window}),
	provide(CampaignMapperService, {useClass: CampaignMapperService})
]).then((appRef: ComponentRef<Injector>) => {
	appInjector(appRef.injector);
});