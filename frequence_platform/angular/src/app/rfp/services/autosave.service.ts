import {Injectable} from "@angular/core";
import {ProposalUtilityService} from "./proposal.utility.service";
import {EmitterService} from "../../shared/services/emitter.service";
import {EVENTEMITTERS} from "../../shared/constants/builder.constants";

@Injectable()
export class AutoSaveService {

    private DEBOUNCE_TIME = 2000;

    constructor(private proposalUtilityService: ProposalUtilityService) {
        this.setSubscription();
    }

    setSubscription() {
        EmitterService.get(EVENTEMITTERS.AUTO_SAVE)
            .debounceTime(this.DEBOUNCE_TIME)
            .subscribe((config) => {
                console.log("Config")
                this.proposalUtilityService.process(config);
            })
    }

}