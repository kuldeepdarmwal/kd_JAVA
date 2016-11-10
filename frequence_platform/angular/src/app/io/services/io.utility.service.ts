import {Injectable} from "@angular/core";
import {IOService} from "./io.service";
import {EmitterService} from "../../shared/services/emitter.service";
import {EVENTEMITTERS, CONSTANTS} from "../../shared/constants/builder.constants";
import {IOMapperService} from "./iomapper.service";
import {IODataModel} from "../models/iodatamodel";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";
import {IOModel} from "../models/io.model";
declare var jQuery : any;
declare var Materialize: any;
/**
 *  IO Utility Service
 *  Used to save IO Data
 *  Saves Data
 *  Navigates through Page after http success
 *
 */
export interface IOUtilityConfig {
    validateFn:any
    validateStatusFn:any
    submissionType : string
    allowNavigation : boolean
}

@Injectable()
export class IOUtilityService {

    private validationStatusMap:ValidationStatusConfigModel;
    private ioMappedData:IOModel;
    private ioUtilityConfig : IOUtilityConfig;

    constructor(private ioMapperService:IOMapperService, private ioService:IOService,
                private ioDataModel:IODataModel) {
    }

    private saveIO(dfpAdvertiserId?: string, navigate?: boolean, submissionType?: string) {
        this.ioService.saveIO(this.ioMappedData)
            .subscribe((response) => {
                if (navigate){
                    if (submissionType === CONSTANTS.IO.SUBMIT && this.ioDataModel.hasDFPProducts){
                        jQuery("#dfp_adv_submit_msg").show();
            		    this.processDFPAdvertisers(dfpAdvertiserId);
                    } else {
                        this.navigateAway();
                    }
                } else {
        		  EmitterService.get(EVENTEMITTERS.LOADER).emit(false);	
                  Materialize.toast('Success! Insertion order saved successfully.', 4000, 'toast-primary');
        		}
            });
    }

    private validateIO(_validationFn) {
        return _validationFn;
    }

    private getMapperData() {
        this.ioMappedData = this.ioMapperService.mapIODataToSubmission(this.ioDataModel.ioData, this.validationStatusMap);
        this.ioMappedData.submission_type = this.ioUtilityConfig.submissionType;
	    this.ioMappedData.mpq_id = this.ioDataModel.mpqId;
    }

    process(ioUtilityConfig:IOUtilityConfig, dfpAdvertiserId) {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
	
        this.ioUtilityConfig = ioUtilityConfig;
        this.validationStatusMap = ioUtilityConfig.validateFn;
        this.getMapperData();

        if(ioUtilityConfig.allowNavigation){
            if(this.validateIO(ioUtilityConfig.validateStatusFn)){
                this.saveIO(dfpAdvertiserId, ioUtilityConfig.allowNavigation, ioUtilityConfig.submissionType);
            }
        }else{
            this.saveIO(dfpAdvertiserId);
        }
    }

	processDFPAdvertisers(dfpAdvertiserId) {
		this.getMapperData();
		this.ioService.processDFPAdvertisers(this.ioMappedData, dfpAdvertiserId)
		.subscribe((response) => {
			if(response.success == true)
			{
			   jQuery("#dfp_adv_submit_msg").hide();	
			   this.navigateAway();
			}else{
			    jQuery("#io_dfp_advertisers_cancel").click();
			    jQuery("#dfp_error_show_modal").openModal();
			    EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
			}
		})
	}

    navigateAway(){
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        window.location.href = "/insertion_orders";
    }


}
