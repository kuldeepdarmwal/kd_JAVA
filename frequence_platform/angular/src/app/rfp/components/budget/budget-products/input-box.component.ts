import {Component, Input, ElementRef, OnInit} from "@angular/core";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";

@Component({
    selector: 'input-box',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-products/input-box.html',
    pipes: [OrderBy],
    directives: [CurrencyDirective]
})
export class InputBoxComponent {

    @Input() product: ProductModel;
    @Input() options: OptionModel;

    constructor() {
    }

}
