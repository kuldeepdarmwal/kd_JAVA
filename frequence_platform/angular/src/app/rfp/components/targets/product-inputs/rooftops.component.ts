import {Component, Input, Output, EventEmitter, ViewChild, ElementRef, OnDestroy} from "@angular/core";
import {EmitterService} from "../../../../shared/services/emitter.service";
import {EVENTEMITTERS, PLACEHOLDERS} from "../../../../shared/constants/builder.constants";
import {PlaceCompleteDirective} from "../../../../shared/directives/placecomplete.directive";
import {ValidationSwitchBoard} from "../../../services/validationswitch.service";

declare var jQuery:any;

@Component({
    selector: 'rooftops',
    templateUrl: '/angular/build/app/views/rfp/targets/product-inputs/rooftops.html',
    directives: [PlaceCompleteDirective]
})
export class RooftopsComponent  implements OnDestroy{
    private validationStatus:boolean = true;
    private eventSubscription;
    @Input('product-names') productNames:string[];
    @Input("rooftops") rooftops:any[];
    @ViewChild('roof') rooftopsElement:ElementRef;
    @Output('update-rooftops') updateRooftops = new EventEmitter<Object>();

    //property object for rooftops
    rooftopsObj = {};

    constructor(private validationSwitchBoard:ValidationSwitchBoard) {
        this._buildPropertiesForRooftops();
        EmitterService.get(EVENTEMITTERS.ROOFTOPS).subscribe(obj => {
            this.updateRooftops.emit(obj);
        });
        this.eventSubscription = validationSwitchBoard.validationDone.subscribe(resp => {
            this.showValidationMessages(resp);
        })
    }

    showValidationMessages(config){
        if (config !== null) {
            this.validationStatus = config.rooftops.status;
        }
    }

    ngAfterViewInit() {
        jQuery(this.rooftopsElement.nativeElement).select2('data', this.rooftops);
    }

    //Building Properties
    _buildPropertiesForRooftops() {
        this.rooftopsObj = {
            placeHolder: PLACEHOLDERS.ROOF_TOPS,
            resultFormatFn: this._formatResultsRooftopFn,
            emitter: EmitterService.get(EVENTEMITTERS.ROOFTOPS),
            dataFn: this._dataRooftopFn,
            allowClear: true,
            allowMultiple: true,
            minLength: 3,
            requestParams: {
                types: ["geocode", "establishment"],
                componentRestrictions: {country: "us"}
            }
        };
    }

    _formatResultsRooftopFn(obj) {
        return obj.text;
    }

    _dataRooftopFn(term, page) {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page
        };
    }

    ngOnDestroy() {
        this.eventSubscription.unsubscribe();
    }
}
