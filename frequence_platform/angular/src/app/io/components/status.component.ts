import {Component, Input, AfterViewInit, Output, EventEmitter} from "@angular/core";
import {ValidationStatusConfigModel} from "../models/validationstatusconfig.model";
import {IO_VALIDATION} from "../../shared/constants/builder.constants";
declare var jQuery:any;

@Component({
    selector: 'status',
    templateUrl: '/angular/build/app/views/io/status.html'
})
export class StatusComponent implements AfterViewInit{

    @Input("status") status: ValidationStatusConfigModel;
    @Input("validated") validated: boolean;
    @Input("submitAllowed") submitAllowed: boolean;
    @Input("hasOandOEnable") hasOandOEnable: boolean;
	
    @Output("save") save = new EventEmitter<any>();
    @Output("submit") submit = new EventEmitter<any>();
    @Output("submit-review") submitReview = new EventEmitter<any>();

    private sections: Array<any> = IO_VALIDATION;

    constructor() { }

    ngAfterViewInit(){
        jQuery(".scrollspy").scrollSpy();
    }
    
    showSection(sectionObj){
    	if(sectionObj.title == "Forecast" && (!this.submitAllowed || !this.hasOandOEnable))
    	{
    		return false;
    	}
    	return true;
    }

    preventDefaultClick(e){
        e.preventDefault();
    }	
}