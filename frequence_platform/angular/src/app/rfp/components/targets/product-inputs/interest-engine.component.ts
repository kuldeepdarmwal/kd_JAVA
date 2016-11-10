import {Component, Input, ViewChild, ElementRef, Output, EventEmitter} from "@angular/core";
import {ValidationSwitchBoard} from "../../../services/validationswitch.service";

import {RFPSelect2PropertiesBuilder} from "../../../utils/rfp-select2-propertiesbuilder.utility";
import {Select2Directive} from "../../../../shared/directives/select2.directive";
import {EmitterService} from "../../../../shared/services/emitter.service";
import {EVENTEMITTERS} from "../../../../shared/constants/builder.constants";

declare var jQuery: any;
@Component({
    selector: 'interest-engine',
    templateUrl: "/angular/build/app/views/rfp/targets/product-inputs/interest-engine.html",
    directives: [Select2Directive]
})

export class InterestEngineComponent {
    private validationStatus: boolean = true;
    private eventSubscription;
    @Input('product-names') productNames: string[];
    @Input("audience-interests") audienceInterests: any[];
    @ViewChild('interests') interests: ElementRef;

    @Output('update-audience-interests') updateAudienceInterests = new EventEmitter<Object>();
    //property object for account-executive-select
    interestsObj = {};

    constructor(private validationSwitchBoard: ValidationSwitchBoard, private rfpSelect2PropertiesBuilder: RFPSelect2PropertiesBuilder) {
        this.setSelect2PropertiesObject();
        EmitterService.get(EVENTEMITTERS.AUDIENCE_INTERESTS).subscribe(obj => {
            this.updateAudienceInterests.emit(obj);
        });
        this.eventSubscription = validationSwitchBoard.validationDone.subscribe(resp => {
            this.showValidationMessages(resp);
        })
    }

    showValidationMessages(config) {
        if (config !== null) {
            this.validationStatus = config.interests.status;
        }
    }

    ngAfterViewInit() {
        jQuery(this.interests.nativeElement).select2('data', this.audienceInterests);
    }

    setSelect2PropertiesObject() {
        this.interestsObj = this.rfpSelect2PropertiesBuilder.select2PropertiesForInterests;
    }

    ngOnDestroy() {
        this.eventSubscription.unsubscribe();
    }

}
