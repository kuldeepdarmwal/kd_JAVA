import {Component, Input, Output, ViewChild, EventEmitter, ElementRef, OnDestroy} from "@angular/core";
import {SERVICE_URL, PLACEHOLDERS, EVENTEMITTERS, ERRORS} from "../../shared/constants/builder.constants";
import {Select2Directive} from "../../shared/directives/select2.directive";
import {EmitterService} from "../../shared/services/emitter.service";
declare var jQuery:any;

@Component({
    selector: 'audience',
    templateUrl: '/angular/build/app/views/io/audience.html',
    directives: [Select2Directive]
})
export class AudienceComponent {
    @Input('demographics') demographics:any[];
    @Input("audience-interests") audienceInterests:any[];
    @Output("audience-interests-selected") audienceInterestsSelected = new EventEmitter<any>();
    @ViewChild('interests') interests:ElementRef;

    private validationStatus: boolean = true; // hide error icon

    interestsObj = {};

    constructor(){
        EmitterService.get(EVENTEMITTERS.AUDIENCE_INTERESTS).subscribe((obj) => {
            this.audienceInterestsSelected.emit(obj);
        });
        this._buildPropertiesForInterests();
    }

    ngAfterViewInit() {
        jQuery(this.interests.nativeElement).select2('data', this.audienceInterests);
    }

    //Building Properties for directive
    _buildPropertiesForInterests() {
        this.interestsObj = {
            url: SERVICE_URL.RFP.AUDIENCE_INTERESTS,
            placeHolder: PLACEHOLDERS.AUDIENCE_INTERESTS,
            resultFormatFn: this._formatResultsInterestsFn,
            emitter: EmitterService.get(EVENTEMITTERS.AUDIENCE_INTERESTS),
            dataFn: this._dataInterestsFn,
            allowClear: true,
            allowMultiple: true,
            minLength: 0,
            resultFn: this._resultFn
        };
    }

    _formatResultsInterestsFn(obj) {
        return obj.text;
    }

    _resultFn(data) {
        return {results: data.result, more: data.more};
    }

    _dataInterestsFn(term, page) {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page
        };
    }
}