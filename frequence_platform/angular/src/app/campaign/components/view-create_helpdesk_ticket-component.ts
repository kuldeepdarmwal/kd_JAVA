import {Component, ViewChild, Input, Output, EventEmitter} from "@angular/core";
import "rxjs/add/operator/map";
import "rxjs/add/operator/toPromise";
import {EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {CampaignsService} from "../services/campaign.service";
import {EmitterService} from "../../shared/services/emitter.service";

declare var jQuery:any;
declare var moment:any;
declare var Materialize:any;

@Component({
    selector: 'create-helpdesk-ticket',
    templateUrl: '/angular/build/app/views/campaign/view-create_helpdesk_ticket.html'
})

export class ViewCreateHelpdeskTicketComponent{   
    private campaignId:any;
    public subject: any;
    public description: any;

    constructor(private campaignsService: CampaignsService) {
        this.subject = '';
        this.description = '';
    }

    public openCreateHelpdeskTicket(id) {
        this.openModal();
        this.subject = '';
        this.description = '';
        this.campaignId = id;
    }
    
    public createTicket(){
        this.closeModal();
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        this.campaignsService.createHelpdeskTicket(this.campaignId, this.subject, this.description)
            .subscribe((response) => {
                console.log("response", response);
                EmitterService.get(EVENTEMITTERS.LOADER).emit(false);                
            });
    }
    
    public openModal(){
        jQuery('#view_create_helpdesk_ticket_modal').openModal();
    }
    
    public closeModal(){
        jQuery('#view_create_helpdesk_ticket_modal').closeModal();
    }
}