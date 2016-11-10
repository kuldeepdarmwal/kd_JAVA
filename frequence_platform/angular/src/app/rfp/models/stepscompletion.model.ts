export interface RFPSteps {
    isGateCleared:boolean
    isTargetsCleared:boolean
    isBudgetCleared:boolean
    isBuilderCleared:boolean
}

export class StepsModel {
     _rfpSteps: RFPSteps;

    constructor() {
        this._rfpSteps = <RFPSteps>{};
    }
}