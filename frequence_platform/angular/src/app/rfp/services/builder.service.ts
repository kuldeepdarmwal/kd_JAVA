import {Observable} from "rxjs/Observable";
import "rxjs/add/operator/map";
import "rxjs/add/operator/catch";
import {Response, Http} from "@angular/http";
import {Injectable} from "@angular/core";
import {HTTPService} from "../../shared/services/http.service";
import {SERVICE_URL} from "../../shared/constants/builder.constants";

@Injectable()
export class BuilderService extends HTTPService{

    constructor(http: Http) {
        super(http);
    }

    getPages(proposalId) {
        return this.query(`${SERVICE_URL.RFP.GET_PAGES}${proposalId}`)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    getPageTemplates(proposalId) {
        return this.query(`${SERVICE_URL.RFP.GET_PAGE_TEMPLATES}${proposalId}`)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    addPage(obj) {
        return this.create(SERVICE_URL.RFP.ADD_PAGE, obj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    removePage(obj) {
        return this.create(SERVICE_URL.RFP.REMOVE_PAGE, obj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    getProposal(proposalId) {
        return this.query(`${SERVICE_URL.RFP.GET_PROPOSAL_DATA}${proposalId}`)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    getScriptContentFromSrc(scriptUrl){
        return this.query(scriptUrl)
            .catch(this.handleError);
    }

    saveProposal(proposalObj){
        return this.create(SERVICE_URL.RFP.SAVE_PROPOSAL_TEMPLATES, proposalObj)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    getSnapshots(uniqueDisplayId){
        return this.query(`${SERVICE_URL.RFP.GET_GEO_SNAPSHOTS}${uniqueDisplayId}`)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    generatePDF(uniqueDisplayId){
        return this.query(`${SERVICE_URL.RFP.GET_PDF}${uniqueDisplayId}`)
            .map((res) => res.json())
            .catch(this.handleError);
    }

    private handleError(error:Response) {
        return Observable.throw(error.text());
    }
}
