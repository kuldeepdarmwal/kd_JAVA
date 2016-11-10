import {Component, Input} from "@angular/core";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";


@Component({
    selector: 'cost-per-sem-unit',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-products/cost-per-sem-unit.html',
    directives: [CurrencyDirective],
    pipes: [OrderBy]
})
export class CostPerSemUnitComponent {

    @Input() product: ProductModel;
    @Input() options: OptionModel;

    constructor() {
    }

}
