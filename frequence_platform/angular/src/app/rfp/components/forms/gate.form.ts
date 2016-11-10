import {Validators} from "@angular/common";
import {abstractForm} from "../../../shared/forms/abstract.form";

export class GateForm extends abstractForm{

    getForm(){
        return {
            "owner_id" : ['', Validators.required],
            "advertiser_name" : ['', Validators.required],
            "proposal_name" : ['', Validators.required],
            "presentation_date" : ['', Validators.required],
            "industry_id" : ['', Validators.required],
            "strategy_id" : ['', Validators.required]
        }
    }

}