import {Component, ElementRef, ViewEncapsulation, ViewChild, AfterViewInit} from "@angular/core";
import {MaterializeDirective} from "angular2-materialize";
import {BreadCrumb} from "../common/breadcrumb.navigation";
import {ComponentInstruction, CanActivate} from "@angular/router-deprecated";
import {GetBuilderData} from "../../services/has-rfp-data.service";
import {BuilderDataModel} from "../../models/builderDataModel";
import {TemplateModel, IncludedFilesModel, CategoryModel} from "../../models/template.model";
import {EmitterService} from "../../../shared/services/emitter.service";
import {EVENTEMITTERS, NAVIGATION, SERVICE_URL} from "../../../shared/constants/builder.constants";
import {NavigationService} from "../../services/navigation.service";
import {BuilderService} from "../../services/builder.service";
import {StepsCompletionService} from "../../services/stepscompletion.service";
import {SlideSorterComponent} from "./slide-sorter.component";
import {PreviewPaneComponent} from "./preview-pane.component";
import {LibraryComponent} from "./library.component";
import {BuilderUtilityService} from "../../services/builder.utility.service";
import {MapperService} from "../../services/mapper.service";
import {ProductConfigurationModel} from "../../models/configuration.model";
import {HeaderComponent} from "../common/header.component";
import {RFPDataModel} from "../../models/rfpdatamodel";
import {BuilderPermissionsModel} from "../../models/builderpermissions.model";
import {PermissionsDataModel} from "../../models/permissionsdatamodel";
declare var Mustache: any;
declare var _: any;
declare var jQuery: any;
declare var Ps: any;

@Component({
    selector: 'proposal',
    templateUrl: '/angular/build/app/views/rfp/builder/proposal-builder.html',
    directives: [MaterializeDirective, BreadCrumb,
        SlideSorterComponent, PreviewPaneComponent, LibraryComponent, HeaderComponent],
    providers: [BuilderUtilityService],
    encapsulation: ViewEncapsulation.Emulated
})

@CanActivate(
    (next: ComponentInstruction, prev: ComponentInstruction) => {
        return GetBuilderData(next, prev);
    }
)
export class BuilderComponent implements AfterViewInit {
    private proposal: any;
    private _el: any;
    private currentMenu: string;
    private libraryTemplates: TemplateModel[];
    private slideSorterTemplates: TemplateModel[];
    private libraryCategoryCollator: any;
    private categories: CategoryModel[];
    private previewTemplate: any;
    private templateIndex: any;
    private isStacked: boolean;
    private loaded = false;
    private geoSnapshotCBFn;
    private builderPermissions: BuilderPermissionsModel;
    private enablePreview: boolean;
    private NAVIGATION = NAVIGATION;
    private enableDownload = false;

    @ViewChild('styles') stylesElem: ElementRef;
    @ViewChild('scripts') scriptsElem: ElementRef;
    @ViewChild('customScript') customElem: ElementRef;
    @ViewChild('library') libraryElem: ElementRef;
    @ViewChild('canvas') canvasElem: ElementRef;

    @ViewChild(PreviewPaneComponent) previewPaneChild: PreviewPaneComponent;
    @ViewChild(LibraryComponent) libraryChild: LibraryComponent;
    @ViewChild(SlideSorterComponent) slideSorterChild: SlideSorterComponent;


    constructor(private navigationService: NavigationService, private builderUtilityService: BuilderUtilityService,
                private builderDataModel: BuilderDataModel, private permissionsDataModel: PermissionsDataModel,
                private el: ElementRef, private builderService: BuilderService, private rfpDataModel : RFPDataModel,
                private stepsCompletionService: StepsCompletionService, private mapperService: MapperService) {
        this._el = el.nativeElement;
        this.stepsCompletionService.clearBuilder();
        this.currentMenu = NAVIGATION.BUILDER;
        this.libraryTemplates = builderDataModel.libraryTemplates;
        this.slideSorterTemplates = builderDataModel.slideSorterTemplates;
        this.categories = builderDataModel.categories;
        this.libraryCategoryCollator = builderUtilityService.collateTemplatesByCategory(this.libraryTemplates, this.categories);
        this.proposal = builderDataModel.proposalData;
        this.builderPermissions = permissionsDataModel.builderPermissions;
        this.enablePreview = permissionsDataModel.enablePreviewText;
        this.previewTemplate = "";
    }

    ngAfterViewInit() {
        this.addExternals();
    }

    keyPressed($event) {
        if ($event.which == 40) {
            this.slideSorterChild.showNextTemplate();
        } else if ($event.which == 38) {
            this.slideSorterChild.showPreviousTemplate();
        }
    }

    addExternals() {
        this.addStyles();
        this.addScripts();
    }

    addStyles() {
        let styles: IncludedFilesModel[] = this.builderDataModel.styles;
        let styleHTML = "";
        for (let style of styles) {
            styleHTML += `<link href="${style.fileUrl}" rel="stylesheet" type="text/css"/>`
        }
        jQuery(this.stylesElem.nativeElement).html(styleHTML);
    }

    addScripts() {
        let scripts: IncludedFilesModel[] = this.builderDataModel.scripts;
        for (let script of scripts) {
            var scriptElement = document.createElement('script');
            scriptElement.setAttribute('type', 'text/javascript');
            scriptElement.async = false;
            scriptElement.src = script.fileUrl;
            jQuery(this.scriptsElem.nativeElement).append(scriptElement);
        }
    }

    checkIfAllLoaded($event) {
        setTimeout(() => {
            this.loadCustomScript();
        }, 2500);
    }

    loadCustomScript() {
        this.builderService.getScriptContentFromSrc(this.builderDataModel.customScript.fileUrl)
            .subscribe((response) => this.renderCustomScript(response._body));
        this.loaded = true;
    }

    renderCustomScript(script) {
        this.setHeight();
        var s = Mustache.render(script, this.proposal);
        eval(s);
        this.slideSorterChild.getFirstTemplateFromList();
        EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
        this.pollGeoSnapshotsDataIfNotAvailable();
    }

    resizeWindow() {
        this.setHeight();
    }

    pollGeoSnapshotsDataIfNotAvailable() {
        this.enableDownload = this.builderDataModel.isSnapshotsAvailable;
        if (!this.builderDataModel.isSnapshotsAvailable) {
            this.geoSnapshotCBFn = setInterval(() => {
                this.getGeoData();
            }, 5000);
        }
    }

    getGeoData() {
        this.builderService.getSnapshots(this.builderDataModel.uniqueDisplayId)
            .subscribe((response) => {
                if (this.checkIfAllSnapshotsAvailable(response)) {
                    clearInterval(this.geoSnapshotCBFn);
                    this.enableDownload = true;
                    this.processSnapshotsResponseAndInsertImages(response);
                }
            })
    }

    processSnapshotsResponseAndInsertImages(snapshotsResponse: any) {
        if (this.builderDataModel.productConfig.showGeoComponent)
            this.insertGeoSnapshots(snapshotsResponse.geos);

        if (this.builderDataModel.productConfig.showRoofTopsComponent)
            this.insertRooftopsSnapshots(snapshotsResponse.rooftops[0])//Has Only One Rooftop Object
    }

    insertGeoSnapshots(geoSnapshots: any[]) {
        let selectedGeos = this.builderDataModel.proposalData.geos;
        for (let geoSnapshot of geoSnapshots) {
            jQuery(".geo_snapshot_link_" + geoSnapshot.lap_id).css("background-image", "url('" + geoSnapshot.snapshot_data + "')");
            jQuery(".geo_snapshot_loader__" + geoSnapshot.lap_id).hide();
        }
        if (selectedGeos.length > 1) {
            let geo = _.findWhere(geoSnapshots, {lap_id: null})
            jQuery(".geo_overview_snapshot").css("background-image", "url('" + geo.snapshot_data + "')");
        } else if (selectedGeos.length == 1) {
            jQuery(".geo_overview_snapshot").css("background-image", "url('" + geoSnapshots[0].snapshot_data + "')");
        }
        jQuery(".geo_overview_link_loader").hide();
    }

    insertRooftopsSnapshots(rooftopSnapshot: any) {
        jQuery(".rooftops_snapshot_link").css("background-image", "url('" + rooftopSnapshot.rooftops_snapshot + "')");
        jQuery(".rooftops_snapshot_link_loader").hide();
    }

    checkIfAllSnapshotsAvailable(snapshotsResponse: any) {
        let productConfig: ProductConfigurationModel = this.builderDataModel.productConfig;
        let geos = snapshotsResponse.geos;
        let rooftops = snapshotsResponse.rooftops[0]; //Will always be 1
        let selectedGeos = this.builderDataModel.proposalData.geos;
        let requiredGeoSnapshotsCount = selectedGeos.length > 1 ?
            (selectedGeos.length + 1) : selectedGeos.length;
        let geoStatus = productConfig.showGeoComponent ? (geos.length == requiredGeoSnapshotsCount) : !productConfig.showGeoComponent;
        let rooftopStatus = productConfig.showRoofTopsComponent ? (rooftops.rooftops_snapshot != null) : !productConfig.showRoofTopsComponent;
        return geoStatus && rooftopStatus;
    }

    setHeight() {
        var bodyHeight = jQuery('body').height();
        var navBarHeight = jQuery('.navbar-inner').height();
        var height = (bodyHeight - navBarHeight );
        jQuery("#main_container").height(height);
        var titleBar = jQuery('.title-bar').height();
        var slidesContainerHeight = height - titleBar - 60;

        jQuery(".filmstrip").css("height", height);
        jQuery(".preview-pane-container").css("height", height);
        jQuery(".filmstrip-slides-container").css("height", slidesContainerHeight);
        jQuery(".filmstrip-slides-container").perfectScrollbar();
    }

    scrollToASlide() {
        let templateId = this.slideSorterChild.currentTemplateId;
        setTimeout(() => {
            this.builderUtilityService.scrollToATemplate(templateId);
        }, 1000);
    }

    setPreviewTemplate($event) {
        this.previewTemplate = $event.templateHTML;
        this.templateIndex = $event.index;
        this.isStacked = $event.isStacked;

        if ($event.isStacked) {
            this.previewPaneChild.stripStyles();
        } else {
            this.previewPaneChild.resize();
            this.previewPaneChild.resetStyles();
        }
    }

    addToSlideSorterView(template: TemplateModel) {
        this.slideSorterTemplates.push(template);
    }

    addTemplates(templates: TemplateModel[]) {
        for (let template of templates) {
            this.removeFromLibrary(template.id);
            this.addToSlideSorterView(template);
        }
        if (templates.length > 0) {
            let template: TemplateModel = templates[templates.length - 1];
            let $event: any = {};
            $event.templateHTML =  template.isStacked ?
            	this.builderUtilityService.stackHTMLForPreviewPane(template.templateHTML) :
            	template.templateHTML;
            $event.index = this.builderUtilityService.getIndexFromList(template, this.slideSorterTemplates);
            $event.isStacked = template.isStacked;
            this.setPreviewTemplate($event);
            this.slideSorterChild.currentTemplateId = template.id;
            this.scrollToASlide();
        }
        this.save(false);
    }

    removeFromLibrary(templateId) {
        var index = this.builderUtilityService.findAndGetIndexFromList(templateId, this.libraryTemplates);
        this.libraryTemplates.splice(index, 1);
        this.libraryCategoryCollator = this.builderUtilityService.collateTemplatesByCategory(this.libraryTemplates, this.categories);
    }

    openLibrary($event) {
        this.libraryChild.showLibraryModal();
    }

    deleteTemplate($event) {
        this.addToLibrary($event);
    }

    addToLibrary(template: TemplateModel) {
        if (!template.categoryId) {
            template.categoryId = "-2";
        }
        this.libraryTemplates.push(template);
        this.libraryCategoryCollator = this.builderUtilityService.collateTemplatesByCategory(this.libraryTemplates, this.categories);
        this.save(false);
    }

    dragComplete($event) {
        this.save(false);
    }

    present() {
        console.log("present - builder");
    }

    goBack() {
        this.navigationService.navigate(NAVIGATION.BUILDER, NAVIGATION.BUDGET);
    }

    goToTargets() {
        this.save(true, NAVIGATION.BUILDER, NAVIGATION.TARGETS);
    }

    goToBudget() {
        this.save(true, NAVIGATION.BUILDER, NAVIGATION.BUDGET);
    }

    save(shouldNavigate, navigateFrom?, navigateTo?) {
        let templateIds = _.pluck(this.slideSorterTemplates, 'id');
        let builderSubmissionData = this.mapperService.mapBuilderDataToSubmission(templateIds, this.builderDataModel.uniqueDisplayId,
           this.rfpDataModel.mpqId, this.stepsCompletionService.RFPSteps);
        if (shouldNavigate) EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        this.builderService.saveProposal(builderSubmissionData)
            .subscribe((response) => {
                if (shouldNavigate) {
                    EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
                    clearInterval(this.geoSnapshotCBFn);
                    this.navigationService.navigate(navigateFrom, navigateTo);
                }
            });
    }

    downloadPDF() {
        if(this.enableDownload)
            window.open(SERVICE_URL.RFP.GET_PDF + this.builderDataModel.uniqueDisplayId, '_blank');
    }

    dateSelected($event) {
        console.log("Event", $event);
    }

    ngOnDestroy() {
        clearInterval(this.geoSnapshotCBFn);
    }
}
