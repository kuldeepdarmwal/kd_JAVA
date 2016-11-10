import {Component, Input, Output, EventEmitter} from "@angular/core";
import {StrategyModel} from "../../models/gatedatamodel";

@Component({
    selector: 'rfp-strategy',
    templateUrl: '/angular/build/app/views/rfp/gate/rfp-strategy.html'
})

export class StrategyComponent {
    @Input("strategies") strategies: StrategyModel[];
    @Output("select-strategy") selectStrategy = new EventEmitter<any>();
}
