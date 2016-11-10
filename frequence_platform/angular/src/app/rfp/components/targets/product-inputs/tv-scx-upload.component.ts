import {Component, Input, Output, EventEmitter, OnDestroy} from "@angular/core";
import {SERVICE_URL, CONSTANTS} from "../../../../shared/constants/builder.constants";
import {RFPDataModel} from "../../../models/rfpdatamodel";
import {ValidationSwitchBoard} from "../../../services/validationswitch.service";
import {NgFileSelect, NgFileDrop} from "../../../../shared/directives/fileselect.directive";
declare var jQuery:any;
declare var _:any;
declare var Materialize:any;

@Component({
    selector: 'tv-upload',
    templateUrl: '/angular/build/app/views/rfp/targets/product-inputs/tv-scx-upload.html',
    directives: [NgFileSelect, NgFileDrop]
})
export class TVSCXUploadComponent implements OnDestroy{
    private validationStatus:boolean = true;
    private eventSubscription;
    private maxNetworks: number = CONSTANTS.MAX_TOP_NETWORKS;
    private error: any = false;

    @Input('product-names') productNames:string[];
    @Input('tv-scx-data') tvScxData:any;
    @Output('upload-strata-file') uploadStrataFile = new EventEmitter<any>();
    @Output('remove-scx-data') removeScxData = new EventEmitter();

    static mpqId:number;
    uploadFile: any;
    uploadOptions: Object = {
        url: SERVICE_URL.RFP.UPLOAD_TV_ZONE,
        data: {
            mpq_id: this.rfpDataModel.mpqId,
            unique_display_id: this.rfpDataModel.uniqueDisplayId
        }
    }

    constructor(private rfpDataModel:RFPDataModel, private validationSwitchBoard:ValidationSwitchBoard) {
        this.eventSubscription = validationSwitchBoard.validationDone.subscribe(resp => {
            this.showValidationMessages(resp);
        })
    }

    showValidationMessages(config) {
        if (config !== null) {
            this.validationStatus = config.tv_scx_upload.status;
        }
    }

    handleUpload(data): void {
        this.error = false;
        if (data && data.response) {
            data = JSON.parse(data.response);
            if (data.error !== undefined) {
                Materialize.toast(data.error, 5000, 'error-toast');
            } else {
                this.uploadFile = data;
                this.uploadStrataFile.emit(data);
            }
        }
    }

    toggleNetwork(index: number) {
        this.tvScxData.networks[index].selected = !this.tvScxData.networks[index].selected;
    }

    selectedNetworksCount() {
        return this.tvScxData.networks.length > 0 ? this.tvScxData.networks.reduce((carry, network) => { return network.selected ? ++carry : carry; }, 0) : 0;
    }

    ngOnDestroy() {
        this.eventSubscription.unsubscribe();
    }

}
