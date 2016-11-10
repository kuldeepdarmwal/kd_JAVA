import {Component, ViewChild, ElementRef, Input, Output, EventEmitter} from "@angular/core";
import {ComponentDefinition} from "@angular/router-deprecated";
import {CORE_DIRECTIVES, FORM_DIRECTIVES, ControlGroup} from "@angular/common";
import {GateForm} from "../forms/gate.form";
import {RFPService} from "../../services/rfp.service";
import {Select2Directive} from "../../../shared/directives/select2.directive";
import {SERVICE_URL, PLACEHOLDERS, EVENTEMITTERS} from "../../../shared/constants/builder.constants";
import {EmitterService} from "../../../shared/services/emitter.service";
import {RFPDataModel} from "../../models/rfpdatamodel";
import {RFPSelect2PropertiesBuilder} from "../../utils/rfp-select2-propertiesbuilder.utility";
import {DatePicker} from "../../../shared/directives/datepicker.directive";

declare var jQuery: any;
declare var moment: any;

@Component({
    selector: 'rfp-gate-form',
    templateUrl: '/angular/build/app/views/rfp/gate/rfp-gate.html',
    providers: [RFPService, GateForm],
    directives: [CORE_DIRECTIVES, FORM_DIRECTIVES, Select2Directive, DatePicker]
})

export class GateFormComponent implements ComponentDefinition {
    type = "component";

    @Input("form-data") formData: any;
    @Input("rfp-form") rfpForm: GateForm;
    @Input("gate-form") gateForm: ControlGroup;

    @Output('adv-selected') advertiserSelected = new EventEmitter<any>();
    @Output('ind-selected') industrySelected = new EventEmitter<any>();
    @Output('date-selected') dateSelected = new EventEmitter<any>();
    @Output('submit-gate') submitGate = new EventEmitter<any>();

    @ViewChild('accountExec') accountExec: ElementRef;
    @ViewChild('advInd') advInd: ElementRef;
    @ViewChild('presentationDate') presentationDate: DatePicker;

    private accountExecObj = {};
    private advertiserIndObj = {};
    private datePickerOptions = {
        format: 'yyyy-mm-dd',
        min: moment().format('YYYY-MM-DD'),
        selectMonths: true,
        selectYears: 15,
        container: 'body',
        onSet: function (c) {
            if (c.select) {
                this.close();
            }
        }
    }


    constructor(private rfpDataModel: RFPDataModel, private rfpSelect2PropertiesBuilder: RFPSelect2PropertiesBuilder) {
        this.setSelect2PropertiesObject()
        this.setEventSubscribers();
    }

    ngAfterViewInit() {
        jQuery(this.accountExec.nativeElement).select2('data', this.rfpDataModel.opportunityOwnerSelect2Format);
        if (this.rfpDataModel.uniqueDisplayId) {
            jQuery(this.advInd.nativeElement).select2('data', this.rfpDataModel.industrySelect2Format);
            this.presentationDate.set('select', this.rfpDataModel.presentationDate);
        }
    }

    setSelect2PropertiesObject() {
        this.accountExecObj = this.rfpSelect2PropertiesBuilder.select2PropertiesForAdvertiser;
        this.advertiserIndObj = this.rfpSelect2PropertiesBuilder.select2PropertiesForAdvertiserIndustry;
    }

    setEventSubscribers() {
        EmitterService.get(EVENTEMITTERS.ACCOUNT_EXECUTIVES).subscribe(obj => {
            this.advertiserSelected.emit(obj);
        });
        EmitterService.get(EVENTEMITTERS.ADVERTISER_INDUSTRY).subscribe(obj => {
            this.industrySelected.emit(obj);
        });
    }

    onSubmit() {
        this.submitGate.emit(true);
    }

    setStartDate(startDate) {
        this.dateSelected.emit(startDate);
    }
}
