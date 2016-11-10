import {AfterViewInit, Component, Input, ViewChild, ElementRef, Output, EventEmitter} from "@angular/core";
import {NewPage} from "./newpage.component";
import {TemplateModel, CategoryModel} from "../../models/template.model";
import {BuilderUtilityService} from "../../services/builder.utility.service";
import {MaterializeDirective} from "angular2-materialize";
declare var jQuery : any;
declare var _ : any;
@Component({
    selector : "library",
    templateUrl: "/angular/build/app/views/rfp/builder/library.html",
    directives : [NewPage, MaterializeDirective]
})

export class LibraryComponent implements AfterViewInit{

    @Input() templates : TemplateModel[];
    @Input() proposal;
    @Input() collator;
    @Input() categories: CategoryModel[];
    @Output("selected-templates") templatesSelected = new EventEmitter<any>();

    @ViewChild("libModal") libModalElem : ElementRef;

    private selectedTemplates : TemplateModel[] = [];

    constructor(private builderUtilityService : BuilderUtilityService){}

    ngAfterViewInit(){
        this.builderUtilityService.addBackgroundModal();
        jQuery(".lib-container").perfectScrollbar({suppressScrollX: true});
    }

    getTemplatesByCategoryId(categoryId) : TemplateModel[]{
        return this.collator[categoryId]
    }

    selectTemplate(templateId){
        let template: TemplateModel = _.findWhere(this.templates, {id: templateId});
        template.selected = !template.selected;
        this.makeDecisionWithSelection(template)
    }

    makeDecisionWithSelection(template: TemplateModel){
        if(!_.findWhere(this.selectedTemplates, {id : template.id}))
            this.addToSelection(template);
        else
            this.removeFromSelection(template);
    }

    addToSelection(template){
        this.selectedTemplates.push(template);
    }

    removeFromSelection(template : TemplateModel){
        var index = this.builderUtilityService.getIndexFromList(template, this.selectedTemplates);
        this.selectedTemplates.splice(index, 1);
    }

    resetSelectedTemplates(){
        for(let template of this.templates)
            template.selected = false;
        this.selectedTemplates = [];
    }

    addSlides(){
        for(let template of this.selectedTemplates){
            template.templateHTML = template.isStacked ?
                this.builderUtilityService.getHTMLContentFromStackedElement(jQuery("#library"+template.id)):
                this.builderUtilityService.getHTMLContentFromElement(jQuery("#library"+template.id));
            template.hasHTML = true;
        }
        this.templatesSelected.emit(this.selectedTemplates);
        this.closeModal();
    }

    showLibraryModal(){
        jQuery(this.libModalElem.nativeElement).css("top", "0%");
        jQuery(this.libModalElem.nativeElement).css("left", "10%");
        jQuery(this.libModalElem.nativeElement).css("transition", "ease-out 0.5s");
        this.builderUtilityService.showBackgroundModal();
    }

    closeModal(){
        this.closeLibraryModal();
        this.builderUtilityService.hideBackgroundModal();
        this.resetSelectedTemplates();
    }

    closeLibraryModal(){
        jQuery(this.libModalElem.nativeElement).css("top", "-150%");
        jQuery(this.libModalElem.nativeElement).css("transition", "ease-in 0.5s");
    }
}
