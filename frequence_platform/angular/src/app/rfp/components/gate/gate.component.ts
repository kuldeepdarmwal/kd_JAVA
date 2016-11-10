import {Component} from "@angular/core";
import {ControlGroup, FormBuilder} from "@angular/common";
import {GateFormComponent} from "./gate-form.component";
import {StrategyComponent} from "./strategy.component";
import {ROUTER_DIRECTIVES, ComponentInstruction, CanActivate} from "@angular/router-deprecated";
import {RFPService} from "../../services/rfp.service";
import {HasRFPData} from "../../services/has-rfp-data.service";
import {StrategyModel, OpportunityOwnerModel, IndustryModel, GateModel} from "../../models/gatedatamodel";
import {RFPDataModel} from "../../models/rfpdatamodel";
import {MapperService} from "../../services/mapper.service";
import {GateForm} from "../forms/gate.form";
import {NavigationService} from "../../services/navigation.service";
import {NAVIGATION, EVENTEMITTERS} from "../../../shared/constants/builder.constants";
import {EmitterService} from "../../../shared/services/emitter.service";
import {StepsCompletionService} from "../../services/stepscompletion.service";
import {start} from "repl";
declare var _:any;
declare var jQuery:any;

@Component({
    selector: 'gate',
    templateUrl: '/angular/build/app/views/rfp/gate/gate.html',
    directives: [ROUTER_DIRECTIVES, GateFormComponent, StrategyComponent],
    providers: [GateForm]
})

@CanActivate(
    (next:ComponentInstruction, prev:ComponentInstruction) => {
        return HasRFPData(next, prev);
    }
)
export class GateComponent {

    private rfpFormData:any = {};
    private strategies:StrategyModel[] = [];
    private gateForm:ControlGroup;

    constructor(private fb:FormBuilder, private rfpForm:GateForm, private rfpDataModel:RFPDataModel,
                private rfpService:RFPService, private mapperService:MapperService,
                private navigationService:NavigationService, private stepsCompletionService: StepsCompletionService) {
        this.stepsCompletionService.clearGate();
        this.gateForm = fb.group(this.rfpForm.getForm());
        if (rfpDataModel.uniqueDisplayId) this.buildData();
        else this.loadCurrentUserData();
        this.setContainerHeight();
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
    }

    setContainerHeight(){
        var bodyH = jQuery("body").height();
        var navH = jQuery(".navbar").height();
        var h = bodyH - navH;
        jQuery("#main_container").height(h);
        jQuery("#main_container").css("overflow-y", "auto");
    }

    buildData() {
        this.rfpFormData = this.rfpDataModel.formData;
        this.rfpForm.setData(this.gateForm, this.rfpFormData);
        this.strategies = this.rfpDataModel.strategies;
        this.preSelectStrategy();
    }

    resizeWindow(){
        this.setContainerHeight();
    }

    loadCurrentUserData() {
        this.strategies = this.rfpDataModel.strategies;
        this.rfpForm.setData(this.gateForm, {
            owner_id: this.rfpDataModel.opportunityOwner.opportunityOwnerId,
            strategy_id: this.strategies[0].strategyId
        })
    }

    preSelectStrategy() {
        let strategyId = this.rfpDataModel.strategyId;
        let strategyObj:StrategyModel = _.findWhere(this.strategies, {strategyId: strategyId});
        if (strategyObj)strategyObj.selected = true;
    }

    accountExecSelected(accountExecObj) {
        let _opportunityOwnerObj:OpportunityOwnerModel = <OpportunityOwnerModel>{};
        _opportunityOwnerObj.opportunityOwnerId = accountExecObj.id;
        _opportunityOwnerObj.opportunityOwnerName = accountExecObj.text;
        this.rfpDataModel.opportunityOwner = _opportunityOwnerObj;
        this.rfpDataModel.strategyId = "";
        this.rfpForm.setData(this.gateForm, {owner_id: accountExecObj.id, strategy_id: ""});
        this.filterStrategies();
    }

    advertiserIndSelected(advIndObj) {
        if (advIndObj != null) {
            let _industryObj:IndustryModel = <IndustryModel>{};
            _industryObj.industryId = advIndObj.id;
            _industryObj.industryName = advIndObj.text;
            this.rfpDataModel.industry = _industryObj;
            this.rfpForm.setData(this.gateForm, {industry_id: advIndObj.id});
        } else {//which means user clicked on close icon to remove industry selected
            let _industryObj:IndustryModel = <IndustryModel>{};
            _industryObj.industryId = "";
            _industryObj.industryName = "";
            this.rfpDataModel.industry = _industryObj;
            this.rfpDataModel.strategyId = "";
            this.rfpForm.setData(this.gateForm, {industry_id: ""});
        }
        this.filterStrategies();
    }

    presentationDateSelected(presentationDate){
        this.rfpDataModel.presentationDate = presentationDate;
        this.rfpForm.setData(this.gateForm, {presentation_date: presentationDate});
    }

    filterStrategies() {
        var obj = this.rfpDataModel.filteredStrategyObj;
        this.rfpService.getFilteredStrategy(obj)
            .subscribe(response => {
                this.rfpDataModel.strategies = this.mapperService.mapStrategyResponseToModel(response.strategies);
                this.selectStrategyIfAlreadySelected();
            });
    }

    selectStrategyIfAlreadySelected() {
        let strategyId = this.rfpDataModel.strategyId;
        this.strategies = this.rfpDataModel.strategies;
        let strategy = _.findWhere(this.strategies, {strategyId: strategyId});
        if (strategy) strategy.selected = true;
        else {
            if (this.strategies.length == 1) {
                let strat:StrategyModel = _.findWhere(this.strategies, {selected: true});
                this.rfpDataModel.strategyId = strat.strategyId;
                this.rfpForm.setData(this.gateForm, {strategy_id: strat.strategyId})
            }
            this.rfpDataModel.loaded = false
        }
        ;
    }

    strategySelected(strategyId) {
        this.strategies.forEach((_strategy:StrategyModel, index:number) => {
            _strategy.selected = false;
            if (_strategy.strategyId == strategyId)
                _strategy.selected = true;
        });
        this.rfpDataModel.strategyId = strategyId;
        this.rfpDataModel.loaded = false;
        this.rfpForm.setData(this.gateForm, {strategy_id: strategyId})
    }

    submitGate() {
        let data: GateModel = this.rfpForm.getData(this.gateForm);
        data.status = this.getStatusObject();
        let uniqueDisplayId = this.rfpDataModel.uniqueDisplayId ? this.rfpDataModel.uniqueDisplayId : "";
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        this.rfpService.updateGate(<GateModel>data, uniqueDisplayId)
            .subscribe(success => this.successFn(success, data), error => this.errorFn(error));
    }

    getStatusObject(){
        let status : any = {};
        let rfpSteps = this.stepsCompletionService.RFPSteps;
        status["is_gate_cleared"] = rfpSteps.isGateCleared;
        status["is_targets_cleared"] = rfpSteps.isTargetsCleared;
        status["is_budget_cleared"] = rfpSteps.isBudgetCleared;
        status["is_builder_cleared"] = rfpSteps.isBuilderCleared;
        return status;
    }

    successFn(response, dataObj: GateModel) {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        this.updateDataModel(dataObj);
        if (!this.rfpDataModel.loaded) { this.rfpDataModel.data = response};
        this.navigate();
    }

    errorFn(errResponse) {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        console.error("LOG [ERR]: ", errResponse)
    }

    updateDataModel(obj: GateModel){
        this.rfpDataModel.proposalName = obj.proposal_name;
        this.rfpDataModel.advertiserName = obj.advertiser_name;
    }

    navigate() {
        this.navigationService.navigate(NAVIGATION.GATE, NAVIGATION.TARGETS);
    }

}
