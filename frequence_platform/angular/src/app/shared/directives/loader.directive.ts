/**
 * @author Anuteja Mallampati
 */
import {Component, ElementRef} from "@angular/core";
import {EmitterService} from "../services/emitter.service";
import {EVENTEMITTERS} from "../constants/builder.constants";

declare var jQuery:any;
@Component({
    selector: '[loader]',
    template: ` 
    <div class="body-content-loading-overlay" *ngIf="showLoader">
	<div id="dfp_adv_submit_msg" style="position: absolute;left: 40%;top: 35%;font-size: 18px;z-index: 100002;color: #fff;display:none;">Creating order on DFP platform. This will take a few minutes.</div>
        <div class="spinner">
            <div class="rect1"></div>
            <div class="rect2"></div>
            <div class="rect3"></div>
            <div class="rect4"></div>
            <div class="rect5"></div>
        </div>
    </div>
    `
})
export class Loader{

    element:any;
    showLoader:boolean = false;

    constructor(el:ElementRef) {
        this.element = el;
        EmitterService.get(EVENTEMITTERS.LOADER).subscribe(obj => {
            this.toggleLoader(obj);
        });
    }

    toggleLoader(obj){
        this.showLoader = obj;
    }

}