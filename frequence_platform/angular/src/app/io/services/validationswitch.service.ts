import {Subject} from "rxjs/Subject";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";
import {Injectable} from "@angular/core";

/**
 *  Any component should register with switchboard to know when to validate.
 */
@Injectable()
export class ValidationSwitchBoard {
    validateIO: Subject<any>;
    validationDone : Subject<ValidationStatusConfigModel>;

    constructor() {
        this.validateIO = new Subject<any>(null);
        this.validationDone = new Subject<ValidationStatusConfigModel>(null);
    }
}
