import {Subject} from "rxjs/Subject";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";

/**
 *  Any component should register with switchboard to know when to validate.
 */
export class ValidationSwitchBoard {
    validateRFP: Subject<any>;
    validationDone : Subject<ValidationStatusConfigModel>;

    constructor() {
        this.validateRFP = new Subject<any>(null);
        this.validationDone = new Subject<ValidationStatusConfigModel>(null);
    }
}
