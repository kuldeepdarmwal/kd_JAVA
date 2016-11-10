import {Component, Output, EventEmitter, Input} from "@angular/core";

@Component({
    selector: 'footer',
    templateUrl: '/angular/build/app/views/rfp/common/footer.html'
})
export class FooterComponent {

    @Input() isInBuilder : boolean;
    @Input("enable-download") enableDownload : boolean;

    @Output() save: EventEmitter<any> = new EventEmitter<any>();
    @Output('save-builder') saveBuilder: EventEmitter<any> = new EventEmitter<any>();
    @Output() back: EventEmitter<any> = new EventEmitter<any>();
    @Output() next: EventEmitter<any> = new EventEmitter<any>();
    @Output() present: EventEmitter<any> = new EventEmitter<any>();
    @Output() download: EventEmitter<any> = new EventEmitter<any>();

    constructor() {}


}
