import {Injectable} from "@angular/core";
import {Router} from "@angular/router-deprecated";
import {RFPDataModel} from "../models/rfpdatamodel";
import {NAVIGATION} from "../../shared/constants/builder.constants";

@Injectable()
export class NavigationService{

    private _rfpDataModel : RFPDataModel;

    constructor(private _router: Router, private rfpDataModel : RFPDataModel){
        this._rfpDataModel = rfpDataModel;
    }

    navigate(from: string, to: string){
        switch(to){
            case NAVIGATION.GATE:
                this.navigateToGate();
                break;

            case NAVIGATION.TARGETS:
                this.navigateToTargets();
                break;

            case NAVIGATION.BUDGET:
                this.navigateToBudget();
                break;

            case NAVIGATION.BUILDER:
                this.navigateToBuilder();
                break;
            
            case NAVIGATION.SUCCESS:
                this.navigateToSuccess();
                break;

            case NAVIGATION.PROPOSALS:
                this.navigateToProposals();
                break;

            default:
                break;
        }
    }

    private navigateToGate(){
        let uniqueDisplayId = this._rfpDataModel.uniqueDisplayId;
        let link = ['EditGate', {uniqueDisplayId: uniqueDisplayId}];
        this._router.navigate(link);
    }

    private navigateToTargets(){
        let uniqueDisplayId = this._rfpDataModel.uniqueDisplayId;
        let link = ['Targeting', {uniqueDisplayId: uniqueDisplayId}];
        this._router.navigate(link);
    }

    private navigateToBudget(){
        let uniqueDisplayId = this._rfpDataModel.uniqueDisplayId;
        let link = ['Budget', {uniqueDisplayId: uniqueDisplayId}];
        this._router.navigate(link);
    }

    private navigateToBuilder(){
        let uniqueDisplayId = this._rfpDataModel.uniqueDisplayId;
        let link = ['Builder', {uniqueDisplayId: uniqueDisplayId}];
        this._router.navigate(link);
    }

    private navigateToSuccess(){
        let uniqueDisplayId = this._rfpDataModel.uniqueDisplayId;
        window.location.href = "/rfp/success/"+uniqueDisplayId;
    }

    private navigateToProposals(){
        window.location.href = "/proposals";
    }


}