import {Component, Input, Output, EventEmitter} from "@angular/core";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {MaterializeDirective} from "angular2-materialize";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";


@Component({
	selector: 'cost-per-tv',
	templateUrl: '/angular/build/app/views/rfp/budget/budget-products/tv-scx-upload.html',
	pipes: [OrderBy],
    directives : [MaterializeDirective, CurrencyDirective]
})
export class CostPerTVComponent{

	@Input() product: ProductModel;
    @Input() options: OptionModel;

	constructor() {
	}
}
