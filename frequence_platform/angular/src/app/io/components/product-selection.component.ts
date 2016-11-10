import {Component, Input, Output, EventEmitter} from "@angular/core";
import {OrderBy} from "../../shared/pipes/orderby.pipe";
import {ProductFilter} from "../../shared/pipes/product-filter.pipe";
import {ProductComponent} from "./product.component";

declare var _:any;

@Component({
    selector: 'product-selection',
    templateUrl: '/angular/build/app/views/io/product-selection.html',
    pipes: [OrderBy, ProductFilter],
    directives: [ProductComponent]
})
export class ProductSelectionComponent{
    private useCategories: boolean = false;

    @Input('products') products;
    @Output('toggle-product') toggleProduct = new EventEmitter();

    constructor() { }
}