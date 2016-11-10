import {Component, AfterViewInit} from "@angular/core";
import {RFPDataModel} from "../../models/rfpdatamodel";
import {NavigationService} from "../../services/navigation.service";
import {NAVIGATION} from "../../../shared/constants/builder.constants";

@Component({
    selector: 'header',
    templateUrl: '/angular/build/app/views/rfp/common/header.html'
})
export class HeaderComponent implements AfterViewInit{

    private advertiserName : string;
    private proposalName : string;
    private submittedBy : string;

    constructor(private rfpDataModel : RFPDataModel, private navigationService : NavigationService) {
    }

    ngAfterViewInit(){
        this.advertiserName = this.rfpDataModel.advertiserName;
        this.proposalName = this.rfpDataModel.proposalName;
        this.submittedBy = this.rfpDataModel.opportunityOwner.opportunityOwnerName;
    }

    toGate(){
        this.navigationService.navigate(null, NAVIGATION.GATE);
    }

}
