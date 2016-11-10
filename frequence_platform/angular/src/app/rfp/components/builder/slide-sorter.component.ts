import {AfterViewInit, Component, Input, EventEmitter, Output} from "@angular/core";
import {NewPage} from "./newpage.component";
import {TemplateModel} from "../../models/template.model";
import {DragulaService} from "../../services/dragula.service";
import {Dragula} from "../../directives/dragula.directive";
import {BuilderUtilityService} from "../../services/builder.utility.service";
import {BuilderPermissionsModel} from "../../models/builderpermissions.model";
declare var _: any;
declare var jQuery: any;

@Component({
    selector: 'slide-sorter',
    templateUrl: '/angular/build/app/views/rfp/builder/slide-sorter.html',
    directives: [Dragula, NewPage],
    providers: [DragulaService]
})

export class SlideSorterComponent implements AfterViewInit {

    @Input("templates") templates: TemplateModel[];
    @Input("proposal") proposal;
    @Input("permissions") permissions : BuilderPermissionsModel;
    @Input("show-add-slides") showAddSlides : boolean;
    @Input("enable-preview") enablePreview : boolean;

    @Output("load-preview") loadPreview = new EventEmitter<any>();
    @Output("slidesorter-loaded") slideSorterLoaded = new EventEmitter<any>();
    @Output("load-library") loadLibrary = new EventEmitter<any>();
    @Output("template-removed") templateRemoved = new EventEmitter<any>();
    @Output("drag-complete") dragComplete = new EventEmitter<any>();
    currentTemplateId;

    constructor(private dragulaService: DragulaService, private builderUtilityService: BuilderUtilityService) {
        dragulaService.setOptions('first-bag', {
            direction: 'vertical',
            moves : () => {
                return this.permissions.hasDragSlide
            }
        });
        //Listening cancel to get the changes as drake.cancel() is
        // called to stop dom elements getting replaced.
        //Instead models are moved with a custom function
        dragulaService.cancel.subscribe((value) => {
            this.onDragComplete(value[1]);
        });

    }

    ngAfterViewInit() {

    }

    templateLoaded(eventObj) {
        let template: TemplateModel = _.findWhere(this.templates, {id: eventObj.templateId});
        template.loaded = true;
        this.checkIfAllLoaded();
    }

    showNextTemplate() {
        let currentIndex = this.builderUtilityService.findAndGetIndexFromList(this.currentTemplateId, this.templates);
        if (currentIndex != this.templates.length - 1) {
            let nextTemplate = this.templates[currentIndex + 1];
            this.currentTemplateId = nextTemplate.id;
            let eventObj: any = this.buildEventObjByTemplateId(this.currentTemplateId);
            this.loadPreview.emit(eventObj);
            this.builderUtilityService.scrollToNextTemplate(this.currentTemplateId, true);
        }
    }

    showPreviousTemplate() {
        let currentIndex = this.builderUtilityService.findAndGetIndexFromList(this.currentTemplateId, this.templates);
        if (currentIndex != 0) {
            let nextTemplate = this.templates[currentIndex - 1];
            this.currentTemplateId = nextTemplate.id;
            let eventObj: any = this.buildEventObjByTemplateId(this.currentTemplateId);
            this.loadPreview.emit(eventObj);
            this.builderUtilityService.scrollToNextTemplate(this.currentTemplateId, false);
        }
    }

    previewTemplate($event, templateId) {
        var element = $event.currentTarget;
        this.currentTemplateId = templateId;
        let eventObj: any = {};
        let template: TemplateModel = this.builderUtilityService.getTemplateById(this.templates, templateId);
        eventObj.templateHTML = template.isStacked ? this.builderUtilityService.stackHTMLForPreviewPane(
            this.builderUtilityService.getHTMLContentFromStackedElement(element)) :
            this.builderUtilityService.getHTMLContentFromElement(element);
        eventObj.index = this.builderUtilityService.findAndGetIndexFromList(templateId, this.templates) + 1;
        eventObj.isStacked = template.isStacked;
        this.loadPreview.emit(eventObj);
    }

    checkIfAllLoaded() {
        let loaded = _.pluck(this.templates, "loaded");
        if (_.indexOf(_.pluck(this.templates, 'loaded'), false) == -1) {
            this.slideSorterLoaded.emit(true);
        }
    }

    getFirstTemplateFromList(tempId?) {
        var templateId = tempId ? tempId : this.templates[0].id;
        this.currentTemplateId = templateId;
        let eventObj = this.buildEventObjByTemplateId(templateId);
        this.loadPreview.emit(eventObj);
    }

    buildEventObjByTemplateId(templateId) {
        let eventObj: any = {};
        let template: TemplateModel = this.builderUtilityService.getTemplateById(this.templates, templateId);
        eventObj.templateHTML = template.isStacked ? this.builderUtilityService.stackHTMLForPreviewPane(
            this.builderUtilityService.getHTMLContentFromStackedElement(jQuery("#slide-sorter-" + templateId))) :
            this.builderUtilityService.getHTMLContentFromElement(jQuery("#slide-sorter-" + templateId));
        eventObj.index = this.builderUtilityService.findAndGetIndexFromList(templateId, this.templates) + 1;
        eventObj.isStacked = template.isStacked;
        return eventObj;
    }

    onDragComplete(event) {
        if (!_.isEmpty(event)) {
            let sourceModel: TemplateModel[] = event.source;
            let sourceIndex: number = event.sourceIndex;
            let targetIndex: number = event.targetIndex;

            this.moveIt(sourceModel, sourceIndex, targetIndex);
            this.dragComplete.emit(true);
        }
    }

    moveIt(sourceModel: TemplateModel[], sourceIndex, targetIndex) {
        sourceModel.splice(targetIndex, 0, sourceModel.splice(sourceIndex, 1)[0]);
    }

    removeTemplate($event, templateId) {
        var element = jQuery($event.currentTarget).parent();
        let template: TemplateModel = _.findWhere(this.templates, {id: templateId});
        template.templateHTML = template.isStacked ?
            this.builderUtilityService.getHTMLContentFromStackedElement(element) :
            this.builderUtilityService.getHTMLContentFromElement(element);
        // template.templateHTML = this.builderUtilityService.getHTMLContentFromElement(element);
        template.hasHTML = true;
        let index = _.indexOf(this.templates, template);
        this.templates.splice(index, 1);
        this.templateRemoved.emit(template);
        if (templateId == this.currentTemplateId && this.templates.length > 0) {
            this.showNextIfSelected(index);
        }
    }

    showNextIfSelected(index) {
        index = index >= this.templates.length ? (this.templates.length - 1) : index;
        let template: TemplateModel = this.templates[index];
        this.currentTemplateId = template.id;
        let eventObj: any = {};
        eventObj.templateHTML = template.isStacked ? this.builderUtilityService.stackHTMLForPreviewPane(
            this.builderUtilityService.getHTMLContentFromStackedElement(jQuery("#slide-sorter-" + template.id))) :
            this.builderUtilityService.getHTMLContentFromElement(jQuery("#slide-sorter-" + template.id));
        eventObj.isStacked = template.isStacked;
        eventObj.index = index + 1;
        this.loadPreview.emit(eventObj);
    }

}
