import {Component, Input, OnDestroy} from "@angular/core";
import {ValidationSwitchBoard} from "../../../services/validationswitch.service";
import {SearchKeywordsModel} from "../../../models/searchkeywords.model";
import {TagInput} from "../../../../shared/directives/taginput.directive";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";

@Component({
    selector: 'search-engine-marketing',
    templateUrl: '/angular/build/app/views/rfp/targets/product-inputs/sem-keywords.html',
    directives : [TagInput, CurrencyDirective]
})
export class SEMComponent implements OnDestroy {

    @Input('product-names') productNames:string[];
    @Input("search-marketing") searchMarketingObj:SearchKeywordsModel;

    private validationStatus:boolean = true;
    private eventSubscription;

    constructor(private validationSwitchBoard:ValidationSwitchBoard) {
        this.eventSubscription = validationSwitchBoard.validationDone.subscribe(resp => {
            this.showValidationMessages(resp);
        });
    }

    showValidationMessages(config) {
        if (config !== null) {
            this.validationStatus = config.sem.status;
        }
    }

    ngOnDestroy() {
        this.eventSubscription.unsubscribe();
    }
}
