import {Injectable} from "@angular/core";
import {RFPDataModel} from "../models/rfpdatamodel";
import {MapperService} from "./mapper.service";
import {NavigationService} from "./navigation.service";
import {RFPService} from "./rfp.service";
import {StepsCompletionService} from "./stepscompletion.service";
import {EmitterService} from "../../shared/services/emitter.service";
import {EVENTEMITTERS} from "../../shared/constants/builder.constants";
/**
 *  Utility Service
 *  Used to save Proposal Data by Targeting and Budget Components
 *  Saves Data
 *  Navigates through Pages after http success
 *
 */
export interface ProposalUtilityConfig {
    validateFn: any
    allowNavigation: boolean
    allowValidation : boolean
    from: string
    to: string,
    saveFn: any
}
@Injectable()
export class ProposalUtilityService {

    constructor(private mapperService: MapperService, private rfpDataModel: RFPDataModel,
                private navigationService: NavigationService, private rfpService: RFPService,
                private stepsCompletionService : StepsCompletionService) {
    }

    private saveProposal() {
        let mappedData = this.getMapperData();
        return this.rfpService.submitRFP(mappedData);
    }

    saveTargets() {
        let mappedData = this.getMapperData();
        return this.rfpService.saveTargets(mappedData);
    }

    saveBudget() {
        let mappedData = this.getMapperData();
        return this.rfpService.saveBudget(mappedData);
    }

    private validateProposal(_validationFn) {
        return _validationFn;
    }

    private getMapperData() {
        return this.mapperService.mapRFPDataToSubmission(this.rfpDataModel.data, this.rfpDataModel.productConfig, this.stepsCompletionService.RFPSteps);
    }

    private navigate(from, to) {
        this.navigationService.navigate(from, to);
    }

    process(proposalUtilityConfig: ProposalUtilityConfig) {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        let validationStatus = proposalUtilityConfig.allowValidation ? this.validateProposal(proposalUtilityConfig.validateFn) : true;
        if (validationStatus) {
            proposalUtilityConfig.saveFn
                .subscribe(response=> {
                    this.processResponse(proposalUtilityConfig, response);
                })
        } else {
            EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        }
    }

    private processResponse(proposalUtilityConfig: ProposalUtilityConfig, response) {
        this.loadResponse(response);
        if (proposalUtilityConfig.allowNavigation) {
            this.navigate(proposalUtilityConfig.from, proposalUtilityConfig.to);
        }
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
    }


    private loadResponse(response: any) {
        if (response.is_success) {
            //this.rfpDataModel.proposalId = response.prop_id;
            //this.rfpDataModel.mpqId = response.mpq_id;
        }
    }

    buildProposalUtilityConfig(allowNavigation:boolean, doValidation : boolean, from : string, to : string, saveFn : any, validationFn : any) {
        let proposalUtilConfig = <ProposalUtilityConfig>{}
        proposalUtilConfig.validateFn = doValidation ? validationFn : "";
        proposalUtilConfig.allowNavigation = allowNavigation;
        proposalUtilConfig.allowValidation = doValidation;
        proposalUtilConfig.from = from;
        proposalUtilConfig.to = to;
        proposalUtilConfig.saveFn = saveFn;
        return proposalUtilConfig;
    }

}
