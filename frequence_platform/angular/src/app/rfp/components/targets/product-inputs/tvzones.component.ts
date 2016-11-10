import {Component, Input, ViewChild, ElementRef, Output, EventEmitter, OnDestroy} from "@angular/core";
import {Select2Directive} from "../../../../shared/directives/select2.directive";
import {EVENTEMITTERS} from "../../../../shared/constants/builder.constants";
import {EmitterService} from "../../../../shared/services/emitter.service";
import {ValidationSwitchBoard} from "../../../services/validationswitch.service";
import {RFPSelect2PropertiesBuilder} from "../../../utils/rfp-select2-propertiesbuilder.utility";
declare var jQuery: any;
declare var _: any;

@Component({
    selector: 'tvzones',
    templateUrl: '/angular/build/app/views/rfp/targets/product-inputs/tvzones.html',
    directives: [Select2Directive]
})
export class TVZonesComponent implements OnDestroy {
    private validationStatus: boolean = true;
    private eventSubscription;

    @Input('product-names') productNames: string[];
    @Input("tv-zones") tvZones: any[];
    @Output('zone-added') zoneAdded = new EventEmitter();
    @ViewChild('tvZones') tvZoneElement: ElementRef;

    //property object for TV Zones select2
    tvZoneObj = {};

    constructor(private validationSwitchBoard: ValidationSwitchBoard, private rfpSelect2PropertiesBuilder: RFPSelect2PropertiesBuilder) {
        this.setSelect2PropertiesObject();
        EmitterService.get(EVENTEMITTERS.TV_ZONES).subscribe(obj => {
            this.zoneAdded.emit(obj);
        });
        this.eventSubscription = validationSwitchBoard.validationDone.subscribe(resp => {
            this.showValidationMessages(resp);
        })
    }

    showValidationMessages(config) {
        if (config !== null) {
            this.validationStatus = config.tvzones.status;
        }
    }

    ngAfterViewInit() {
        jQuery(this.tvZoneElement.nativeElement).select2('data', this.tvZones);
    }

    setSelect2PropertiesObject() {
        this.tvZoneObj = this.rfpSelect2PropertiesBuilder.select2PropertiesForTvZones;
    }

    ngOnDestroy() {
        this.eventSubscription.unsubscribe();
    }

}
