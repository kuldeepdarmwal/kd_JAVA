import {ProductModel} from './product.model';

export interface RFPModel {
	unique_display_id: number
	products: Array<ProductModel>
	options: any
	product_input_components: any
}