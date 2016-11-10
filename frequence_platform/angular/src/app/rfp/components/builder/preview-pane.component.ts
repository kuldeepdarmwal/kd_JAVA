import {AfterViewInit, Component, Input, ViewChild, ElementRef, Output, EventEmitter} from "@angular/core";
import {BuilderUtilityService} from "../../services/builder.utility.service";
import {FooterComponent} from "../common/footer.component";
declare var jQuery: any;
@Component({
    selector: 'preview-pane',
    templateUrl: '/angular/build/app/views/rfp/builder/preview-pane.html',
    directives: [FooterComponent]
})

export class PreviewPaneComponent implements AfterViewInit {

    @Input() templateHTML;
    @Input("template-index") templateIndex;
    @Input() isStacked;
    @Input("enable-download") enableDownload;
    @Output("go-back") back = new EventEmitter<any>();
    @Output("save") save = new EventEmitter<any>();
    @Output("next") next = new EventEmitter<any>();
    @Output("download") download = new EventEmitter<any>();
    @ViewChild('previewHTML') previewHTML: ElementRef;
    private _el;

    constructor(private builderUtilityService: BuilderUtilityService) {
    }

    ngAfterViewInit() {
        this._el = jQuery(this.previewHTML.nativeElement);
    }

    resize() {
        this.builderUtilityService.setResizeDimensions(this._el, false, false);
    }

    resizePreviewWindow() {
        if (!this.isStacked) {
            this.resize();
        }
    }

    stripStyles() {
        jQuery(this._el).removeAttr('style');
        jQuery(this._el).parent().parent().css('border', 'none');
        jQuery(this._el).parent().parent().css('background', 'none');
    }

    resetStyles() {
        jQuery(this._el).parent().parent().css('border', '1px solid #d1d1d1');
        jQuery(this._el).parent().parent().css('background', '#fff');
    }


}
