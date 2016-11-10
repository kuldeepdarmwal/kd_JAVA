import {Component, Input, Output, EventEmitter} from "@angular/core";
import {ProductModel} from "../models/product.model";

@Component({
	selector: 'product',
	templateUrl: '/angular/build/app/views/io/product.html',
	host: {
		'(click)': 'productToggle()'
	}
})
export class ProductComponent {

	@Input() product: ProductModel;
	@Output('toggle-product') toggleProduct = new EventEmitter<ProductModel>();

	constructor() {}

	productImg() {
		return this.product.selected ? this.product.definition.product_enabled_img : this.product.definition.product_disabled_img;
	}

	productToggle() {
		this.toggleProduct.emit(this.product);
	}
}