import {Component, Input} from "@angular/core";

@Component({
    selector: 'audience',
    templateUrl: '/angular/build/app/views/rfp/targets/product-inputs/audience.html'
})

export class AudienceComponent {

    @Input('product-names') productNames: string[];
    @Input('demographics') demographics: any[];
    @Input("is-political") isPolitical: boolean;
    @Input("political") politicalData: any[];

    constructor() {
    }

}
