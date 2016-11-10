import {Injectable} from "@angular/core";
import {StepsModel, RFPSteps} from "../models/stepscompletion.model";
import {UtilityService} from "../../shared/services/utility.service";

@Injectable()
export class StepsCompletionService{

    private _stepsObj = new StepsModel();
    private _rfpStepsModel: any;

    constructor(){
        this.loadRFPStepsModel();
    }

    //Steps Model For RFP
    get RFPSteps(): any{
        return this._rfpStepsModel;
    }

    loadRFPStepsModel(){
        let stepsObj : RFPSteps = <RFPSteps>{};
        stepsObj.isGateCleared = false;
        stepsObj.isTargetsCleared = false;
        stepsObj.isBudgetCleared = false;
        stepsObj.isBuilderCleared = false;
        this._rfpStepsModel = stepsObj;
    }

    clearGate(){
        this._rfpStepsModel.isGateCleared = true;
    }

    clearTargets(){
        this._rfpStepsModel.isTargetsCleared = true;
    }

    clearBudget(){
        this._rfpStepsModel.isBudgetCleared = true;
    }

    clearBuilder(){
        this._rfpStepsModel.isBuilderCleared = true;
    }

    updateRFPStepsWhenEditing(){
        this._rfpStepsModel.isGateCleared = true;
        this._rfpStepsModel.isTargetsCleared = true;
        this._rfpStepsModel.isBudgetCleared = true;
        this._rfpStepsModel.isBuilderCleared = true;
    }

    set RFPSteps(proposalStatus){
        this._rfpStepsModel.isGateCleared = UtilityService.toTrueOrFalse(proposalStatus.is_gate_cleared);
        this._rfpStepsModel.isTargetsCleared = UtilityService.toTrueOrFalse(proposalStatus.is_targets_cleared);
        this._rfpStepsModel.isBudgetCleared = UtilityService.toTrueOrFalse(proposalStatus.is_budget_cleared);
        this._rfpStepsModel.isBuilderCleared = UtilityService.toTrueOrFalse(proposalStatus.is_builder_cleared);
    }



}