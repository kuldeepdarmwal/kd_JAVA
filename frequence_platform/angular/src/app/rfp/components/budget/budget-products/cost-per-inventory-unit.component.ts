import {Component, Input} from "@angular/core";
import {MaterializeDirective} from "angular2-materialize";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";


@Component({
    selector: 'cost-per-inventory-unit',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-products/cost-per-inventory-unit.html',
    pipes: [OrderBy],
    directives: [MaterializeDirective, CurrencyDirective]
})
export class CostPerInventoryUnitComponent {

    @Input() product: ProductModel;
    @Input() options: OptionModel;

    constructor() {
    }

    changeInventory(e) {
        this.product.inventory.custom = e.target.value;
    }
}
