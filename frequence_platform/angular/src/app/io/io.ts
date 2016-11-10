import "rxjs/Rx";
import {Component, ComponentRef, provide, Injector, enableProdMode} from "@angular/core";
import {RouteConfig, ROUTER_DIRECTIVES, ROUTER_PROVIDERS} from "@angular/router-deprecated";
import {HTTP_PROVIDERS} from "@angular/http";
import {bootstrap} from "@angular/platform-browser-dynamic";
import {appInjector} from "../shared/utils/app-injector";
import {Loader} from "../shared/directives/loader.directive";
import {IOComponent} from "./components/io.component";
import {IOService} from "./services/io.service";
import {IODataModel} from "./models/iodatamodel";
import {IOMapperService} from "./services/iomapper.service";
import {GoogleMapsService} from "../shared/services/google-maps.service";
import {ValidationSwitchBoard} from "../rfp/services/validationswitch.service";
import {RFPService} from "../rfp/services/rfp.service";
import {IOPropertiesBuilder} from "./utils/io-propertiesbuilder.utility";
import {IOUtilityService} from "./services/io.utility.service";
import {RFPSelect2PropertiesBuilder} from "../rfp/utils/rfp-select2-propertiesbuilder.utility";

enableProdMode();

@Component({
	selector: 'io',
	directives: [ROUTER_DIRECTIVES, Loader],
	templateUrl : '/angular/build/app/views/builder.html'
})
@RouteConfig([
    { path: '/io', name: 'Io', component: IOComponent },
    { path: '/io/:uniqueDisplayId', name: 'EditIO', component: IOComponent },
])
export class InsertionOrderComponent { }

bootstrap(InsertionOrderComponent, [
	HTTP_PROVIDERS, 
	ROUTER_PROVIDERS,
	provide(IOService, {useClass: IOService}),
	provide(IODataModel, {useClass: IODataModel}),
	provide(RFPService, {useClass: RFPService}),
	provide(GoogleMapsService, {useClass: GoogleMapsService}),
	provide(ValidationSwitchBoard, {useClass: ValidationSwitchBoard}),
	provide(IOMapperService, {useClass: IOMapperService}),
	provide(IOUtilityService, {useClass: IOUtilityService}),
    provide(IOPropertiesBuilder, {useClass : IOPropertiesBuilder})
]).then((appRef: ComponentRef<Injector>) => {
	appInjector(appRef.injector);
});