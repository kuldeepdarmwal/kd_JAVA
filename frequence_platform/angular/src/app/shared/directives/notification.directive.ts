/**
 * @author Anuteja Mallampati
 */
import {Component, ElementRef} from "@angular/core";
import {EmitterService} from "../services/emitter.service";
import {EVENTEMITTERS} from "../constants/builder.constants";
import {NotificationModel} from "../../campaign/models/notification.model";

declare var jQuery:any;

@Component({
    selector: 'notification',
    template: ` 
    <div class="alert alert-info message-box" [hidden]="!showNotification">
	    <button type="button" class="close" (click)="closeNotification()">×</button>
	    <div id="c_message_box_content">{{message}}</div>
    </div>
    `,
    styles : [
        '.message-box{width: 40%; position: fixed;margin-left: auto;margin-right: auto;margin-top: 0px;margin-bottom: 0px;z-index: 107;left: 0px; right: 0px;}'
    ]
})
export class NotificationDirective{

    private element:any;
    private showNotification:boolean = false;
    private message : string = "";
    private isError : boolean = false;

    constructor(el:ElementRef) {
        this.element = el;
        EmitterService.get(EVENTEMITTERS.NOTIFICATION).subscribe(obj => {
            this.toggleNotification(obj);
            setTimeout(() => {
                this.closeNotification();
            }, 5000);
        });
    }

    toggleNotification(obj : NotificationModel){
        this.showNotification = obj.showNotification;
        this.message = obj.message;
        this.isError = obj.isError;
    }

    closeNotification(){
        this.showNotification = false;
    }

}