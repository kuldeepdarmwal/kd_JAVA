import {Component, Input, Output, EventEmitter} from "@angular/core";
import {ProductModel} from "../../models/product.model";
import {OptionModel} from "../../models/option.model";
import {CostPerUnitComponent} from "./budget-products/cost-per-unit.component";
import {InputBoxComponent} from "./budget-products/input-box.component";
import {CostPerDiscreteUnitComponent} from "./budget-products/cost-per-discrete-unit.component";
import {CostPerInventoryUnitComponent} from "./budget-products/cost-per-inventory-unit.component";
import {CostPerStaticUnitComponent} from "./budget-products/cost-per-static-unit.component";
import {CostPerTVComponent} from "./budget-products/cost-per-tv.component";
import {CostPerSemUnitComponent} from "./budget-products/cost-per-sem-unit.component";
import {OrderBy} from "../../../shared/pipes/orderby.pipe";

@Component({
    selector: '.budget-product',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-product.html',
    pipes: [OrderBy],
    directives: [CostPerUnitComponent, CostPerTVComponent, CostPerDiscreteUnitComponent, CostPerSemUnitComponent,
        CostPerInventoryUnitComponent, CostPerStaticUnitComponent, InputBoxComponent]
})
export class BudgetProductComponent {

    @Input() product: ProductModel;
    @Input() options: OptionModel;
    @Input() locations: any[];
    @Input() has_geofencing: any;
    @Input() userData: any;

    @Output('change-impressions-type') changeImpressionsType = new EventEmitter<Object>();
    @Output('network-changed') networkChanged = new EventEmitter<any>();

    constructor() {
    }

}
