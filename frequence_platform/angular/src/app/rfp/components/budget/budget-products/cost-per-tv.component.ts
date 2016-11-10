import {Component, Input, Output, EventEmitter} from "@angular/core";
import {MaterializeDirective} from "angular2-materialize";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";


@Component({
    selector: 'cost-per-tv',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-products/cost-per-tv.html',
    pipes: [OrderBy],
    directives: [MaterializeDirective, CurrencyDirective]
})
export class CostPerTVComponent {

    @Input() product: ProductModel;
    @Input() options: OptionModel;
    @Input() locations: any[];

    @Output('network-changed') networkChanged = new EventEmitter();
    @Output('change-impressions-type') typeChange = new EventEmitter<Object>();

    constructor() {
    }

    changeNetwork(optionId, productId, unit) {
        if (unit == "0 Network") {
            this.options[optionId].config[productId].data.customEnabled = true;
            this.buildCustomDataIfSelected(optionId, productId);
        } else {
            this.options[optionId].config[productId].data.customEnabled = false;
            this.networkChanged.emit(unit);
        }
    }

    buildCustomDataIfSelected(optionId, productId) {
        this.options[optionId].config[productId].data.price = 0;
        this.options[optionId].config[productId].data.spots = 0;
    }

    changeImpressionsType(option_id: number) {
        this.typeChange.emit({product_id: this.product.id, option_id: option_id});
    }
}
