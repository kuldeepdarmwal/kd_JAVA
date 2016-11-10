import {Component, Input} from "@angular/core";
import {MaterializeDirective} from "angular2-materialize";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";

declare var _: any;

@Component({
    selector: 'cost-per-discrete-unit',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-products/cost-per-discrete-unit.html',
    pipes: [OrderBy],
    directives: [MaterializeDirective, CurrencyDirective]
})
export class CostPerDiscreteUnitComponent {

    @Input() product: ProductModel;
    @Input() options: OptionModel;

    constructor() {
    }

    changeType(value: string, optionId: number, productId: any) {
        let option = _.findWhere(this.options, {optionId: optionId});
        let total = option.config[productId].data.total();
        option.type = value;
        option.unit = value === "dollars" ? total : Math.round(option.unit * 1000 / option.cpm);
    }
}
