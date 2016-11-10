import {Component, Input} from "@angular/core";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";

@Component({
    selector: '.discount-product',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-products/discount-product.html'
})
export class DiscountProductComponent {

    @Input() discount: ProductModel;
    @Input() options: OptionModel;

    constructor() {
    }

}
