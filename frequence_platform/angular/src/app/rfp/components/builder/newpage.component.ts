import {
    Component,
    Input,
    ElementRef,
    AfterViewInit,
    ViewEncapsulation,
    Output,
    EventEmitter,
    ViewChild
} from "@angular/core";
import {TemplateModel} from "../../models/template.model";
import {BuilderUtilityService} from "../../services/builder.utility.service";
declare var Mustache: any;
declare var jQuery: any;

@Component({
    selector: 'new-page',
    template: `
                <div id="template-page" (window:resize)="resizeWindow()">
                    <div id="template-slide">
                        <div  #slideHTML></div>
                    </div>
                </div>
                `,
    encapsulation: ViewEncapsulation.Emulated
})
export class NewPage implements AfterViewInit {

    @Input() template: TemplateModel;
    @Input() proposal: Object;
    @Input() isLibrary: boolean;
    @Output('loaded') loaded = new EventEmitter();
    @ViewChild('slideHTML') slideHTML: ElementRef;

    private _el: any;
    private _html: any;

    constructor(private el: ElementRef, private builderUtilityService: BuilderUtilityService) {
        this._el = el.nativeElement;
    }

    ngAfterViewInit() {
        if (!this.template.hasHTML) {
            this._html = Mustache.render(this.template.template, this.proposal);
            this.slideHTML.nativeElement.innerHTML = this._html
            this.loaded.emit({templateId: this.template.id});
            this.checkIfStacked();
        }
        else {
            this.slideHTML.nativeElement.innerHTML = this.template.templateHTML;
            this.checkIfStacked();
        }
        this.resize();
    }

    checkIfStacked() {
        var elements = jQuery(this._el).find(".page");
        this.template.isStacked = this.template.isStacked && elements.length > 1;
        if(this.template.isStacked){
            setTimeout(() => {
                this.stackTemplateSlide();
            }, 1000);
        }
    }

    stackTemplateSlide() {
        var elements = jQuery(this._el).find(".page");
        var pageWidth = jQuery(this._el.parentElement).width();
        var pageHeight = jQuery(this._el.parentElement).height();
        var totalElements = elements.length;
        var templateHTML = "<div style='width: "+pageWidth+"px; height : "+ pageHeight +"px; ' class='template-stack-container'>";
        var count = 0;
        for(var i = elements.length-1; i >=0; i--){
            var right = (i)*6; var top = (i)*3;
            var width = pageWidth - ((totalElements-1)*6);
            var height = pageHeight- ((totalElements-1)*3);
            var ratio = width / 1754;
            templateHTML += "<div style='top : "+ top +"px; right :"+ right +"px; width : "+ width +"px; " +
                "height : "+ height +"px; z-index: "+ i+";' class='template-stack-area'>" +
                "<div style='transform: scale("+ratio+");' class='template-transform-stack'>" ;
            templateHTML += "<div class='"+ jQuery(elements[i]).attr('class') +"'>"
            templateHTML +=jQuery(elements[i]).html();
            templateHTML += "</div></div></div>";
            count++
        }
        templateHTML += "</div>";
        jQuery(this._el).find("#template-slide").html(templateHTML);
        jQuery(this._el).find("#template-page").removeAttr('style');
    }


    resize() {
        this.builderUtilityService.setResizeDimensions(this._el, true, this.isLibrary);
    }

    resizeWindow() {
        if(!this.template.isStacked)
            this.resize();
    }
}
