import {Component, Input, AfterViewInit, ViewChild, ElementRef, Output, EventEmitter} from "@angular/core";
import {Select2Directive} from "../../shared/directives/select2.directive";
import {TrackingModel} from "../models/tracking.model";
import {IODataModel} from "../models/iodatamodel";
import {AdvertiserModel} from "../models/opportunity.model";
import {EVENTEMITTERS, CONSTANTS, EXTENSIONS} from "../../shared/constants/builder.constants";
import {EmitterService} from "../../shared/services/emitter.service";
import {IOService} from "../services/io.service";
import {IOPropertiesBuilder} from "../utils/io-propertiesbuilder.utility";
declare var jQuery:any;

@Component({
    selector: 'tracking',
    templateUrl: '/angular/build/app/views/io/tracking.html',
    directives: [Select2Directive]
})
export class TrackingComponent implements AfterViewInit {

    @Input("tracking") tracking:TrackingModel;
    @Input("advertiser") advertiser: AdvertiserModel;
    @Output("tracking-tag-selected") trackingTagSelected = new EventEmitter<any>();
    @ViewChild('trackingTag') trackingTagElem:ElementRef;

    private trackingTagObj:any = {};
    private enableNewTrackingTag:boolean = false;
    private trackingTagDirName:string = "";
    private trackingTagFile:string = "";
    private trackingTagFileName:string = "";

    constructor(private ioDataModel:IODataModel, private ioService:IOService,
                private ioPropertiesBuilder:IOPropertiesBuilder) {
        this._buildProperties();
        this.setEventSubscribers();
    }

    ngAfterViewInit() {
        if (this.ioDataModel.uniqueDisplayId && this.tracking.trackingTagFileName !== null)
            jQuery(this.trackingTagElem.nativeElement).select2('data', this.ioDataModel.trackingSelect2Format);
    }

    setEventSubscribers() {
        EmitterService.get(EVENTEMITTERS.IO.TRACKING_TAG).subscribe(obj => {
            this.tagSelected(obj);
        });
    }

    tagSelected(obj) {
        if (obj.id == CONSTANTS.IO.NEW_TRACKING_TAG_FILE) {
            this.enableNewTrackingTag = true;
            this.getAdvertiserDirectoryName();
        } else {
            this.enableNewTrackingTag = false;
            this.trackingTagSelected.emit(obj);
        }
    }

    resetTrackingTagOptions(){
        if(this.trackingTagElem){
            jQuery(this.trackingTagElem.nativeElement).select2('val', "");
        }
        this.trackingTagFile = "";
    }

    getAdvertiserDirectoryName() {
        let advObj = this.ioDataModel.advertiserObjForDir;
        this.ioService.getAdvertiserDirectoryName(advObj)
            .subscribe((response:any) => {
                this.trackingTagDirName = response.directory_name;
                this.trackingTagDirName += "/";
            });
    }

    createTrackingTagFile(e) {
        if (this.trackingTagFile == ''){
            e.preventDefault();
            return false;
        }
        this.trackingTagFileName = this.trackingTagDirName + this.trackingTagFile + "." +EXTENSIONS.JS;
        let trackingTagObj = this.ioDataModel.getTrackingObjectForCreatingFile(this.trackingTagDirName + this.trackingTagFile);
        this.createTrackingTagFileService(trackingTagObj);
    }

    createTrackingTagFileService(trackingObj) {
        this.ioService.createTrackingTagFile(trackingObj)
            .subscribe((response) => {
                this.saveTrackingResponse(response);
            });
    }

    saveTrackingResponse(response: any) {
        response.isNew = true;
        let trackingTagId = response.id;
        let trackingTagFileName = response.name;
        this.ioDataModel.tracking.trackingTagFileId = trackingTagId;
        this.ioDataModel.tracking.trackingTagFileName = trackingTagFileName;
        this.enableNewTrackingTag = false;
        jQuery(this.trackingTagElem.nativeElement).select2('data', this.ioDataModel.trackingSelect2Format);
        this.trackingTagSelected.emit(response);
    }

    //Building Properties
    _buildProperties() {
        this.trackingTagObj = this.ioPropertiesBuilder._buildPropertiesForTrackingTag();
    }

}