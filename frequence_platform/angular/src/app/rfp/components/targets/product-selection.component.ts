import {Component, Input, Output, EventEmitter} from "@angular/core";
import {OrderBy} from "../../../shared/pipes/orderby.pipe";
import {ProductFilter} from "../../../shared/pipes/product-filter.pipe";
import {ProductComponent} from "./product.component";

declare var _: any;

@Component({
    selector: 'product-selection',
    templateUrl: '/angular/build/app/views/rfp/targets/product-selection.html',
    pipes: [OrderBy, ProductFilter],
    directives: [ProductComponent]
})
export class ProductSelectionComponent {
    @Input('display-products') products;
    @Output('toggle-product') toggleProduct = new EventEmitter();

    constructor() {
    }

    categories() {
        return _.uniq(
            this.products.reduce((carry, product) => {
                if (product.selectable === "1") {
                    carry.push(product.definition.category);
                }
                return carry;
            }, []));
    }
}
