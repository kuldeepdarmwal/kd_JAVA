import {Component, Input, ElementRef, OnInit} from "@angular/core";
import {MaterializeDirective} from "angular2-materialize";
import {OrderBy} from "../../../../shared/pipes/orderby.pipe";
import {ProductModel} from "../../../models/product.model";
import {OptionModel} from "../../../models/option.model";
import {CurrencyDirective} from "../../../../shared/directives/currency.directive";

@Component({
    selector: 'cost-per-static-unit',
    templateUrl: '/angular/build/app/views/rfp/budget/budget-products/cost-per-static-unit.html',
    pipes: [OrderBy],
    directives: [MaterializeDirective, CurrencyDirective]
})
export class CostPerStaticUnitComponent {
    private _el: HTMLElement;

    @Input() product: ProductModel;
    @Input() options: OptionModel;

    constructor(private el: ElementRef) {
        this._el = el.nativeElement;
    }

    changeType(value: string, option_id: number) {
        let option = this.product.options[option_id];
        let total = option.total();
        option.type = value;
        option.unit = value === "dollars" ? total : Math.round(option.unit * 1000 / option.cpm);
    }
}
