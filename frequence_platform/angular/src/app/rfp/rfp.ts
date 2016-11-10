import "rxjs/Rx";
import {provideStore} from "@ngrx/store";
import {Component, ComponentRef, provide, Injector, enableProdMode} from "@angular/core";
import {RouteConfig, ROUTER_DIRECTIVES, ROUTER_PROVIDERS} from "@angular/router-deprecated";
import {HTTP_PROVIDERS} from "@angular/http";
import {bootstrap} from "@angular/platform-browser-dynamic";
import {appInjector} from "../shared/utils/app-injector";
import {RFPService} from "./services/rfp.service";
import {GateComponent} from "./components/gate/gate.component";
import {TargetingComponent} from "./components/targets/targeting.component";
import {BudgetComponent} from "./components/budget/budget.component";
import {RFPDataModel} from "./models/rfpdatamodel";
import {Loader} from "../shared/directives/loader.directive";
import {ConfigurationStore} from "./services/rfp.store";

import {MapperService} from "./services/mapper.service";
import {BuilderComponent} from "./components/builder/builder.component";
import {BuilderService} from "./services/builder.service";
import {BuilderDataModel} from "./models/builderDataModel";
import {NavigationService} from "./services/navigation.service";
import {ProposalUtilityService} from "./services/proposal.utility.service";
import {StepsCompletionService} from "./services/stepscompletion.service";
import {BuilderUtilityService} from "./services/builder.utility.service";
import {RFPSelect2PropertiesBuilder} from "./utils/rfp-select2-propertiesbuilder.utility";
import {PermissionsDataModel} from "./models/permissionsdatamodel";
import {AutoSaveService} from "./services/autosave.service";

enableProdMode();

@Component({
  selector: 'rfp',
  directives: [ROUTER_DIRECTIVES, Loader],
  templateUrl: '/angular/build/app/views/builder.html'
})
@RouteConfig([
  {path: '/gate', name: 'Gate', component: GateComponent},
  {path: '/gate/:uniqueDisplayId', name: 'EditGate', component: GateComponent},
  {path: '/targeting/:uniqueDisplayId', name: 'Targeting', component: TargetingComponent},
  {path: '/budget/:uniqueDisplayId', name: 'Budget', component: BudgetComponent},
  {path: '/builder/:uniqueDisplayId', name: 'Builder', component: BuilderComponent},
  {path: '/**', redirectTo: ['Gate']}

])
export class AppComponent {
}

bootstrap(AppComponent, [
  HTTP_PROVIDERS,
  ROUTER_PROVIDERS,
  provide(RFPService, {useClass: RFPService}),
  provide(BuilderService, {useClass: BuilderService}),
  provide(RFPDataModel, {useClass: RFPDataModel}),
  provide(BuilderDataModel, {useClass: BuilderDataModel}),
  provide(PermissionsDataModel, {useClass: PermissionsDataModel}),
  provide(MapperService, {useClass: MapperService}),
  provide(NavigationService, {useClass: NavigationService}),
  provide(StepsCompletionService, {useClass: StepsCompletionService}),
  provide(ProposalUtilityService, {useClass: ProposalUtilityService}),
  provide(BuilderUtilityService, {useClass: BuilderUtilityService}),
  provide(RFPSelect2PropertiesBuilder, {useClass: RFPSelect2PropertiesBuilder}),
  provide(AutoSaveService, {useClass: AutoSaveService}),
  provideStore({ConfigurationStore}),
]).then((appRef:ComponentRef<Injector>) => {
  appInjector(appRef.injector);
}).catch(err => console.error(err));
