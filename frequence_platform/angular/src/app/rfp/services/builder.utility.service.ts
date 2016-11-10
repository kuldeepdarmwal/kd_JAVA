import {TemplateModel, CategoryModel} from "../models/template.model";
import {UtilityService} from "../../shared/services/utility.service";
import {ElementRef, Injectable} from "@angular/core";
import {ProductConfigurationModel} from "../models/configuration.model";

declare var jQuery: any;
declare var _: any;

@Injectable()
export class BuilderUtilityService {

    private templateSlideElementID = "#template-slide";
    private templatePageElementID = "#template-page";
    private libraryModalBgElementID = "#libraryModalBg";
    private defaultHeight = 1240;
    private defaultWidth = 1754;
    private containerWidth = 0;
    private modalPopupBgHTML = `<div id="libraryModalBg" style="position: absolute; top: 0px; left: 0px; 
                                                width: 100%;background: #000; z-index: 10001;"></div>`;
    private selectedConfig: any [] = [];
    private productConfig: any;

    constructor() {
    }

    filterTemplatesByProductSelection(template: TemplateModel) {
        var status;
        for (var configName of this.selectedConfig) {
            if (typeof status == 'undefined') {
                status = template.config[configName];
            } else {
                status = status || template.config[configName];
            }
        }
        status = template.config.showBothGeoAudience ? this.productConfig.showBothGeoAudience && status : status;
        return status;
    }

    setSelectedConfiguration(productConfig: ProductConfigurationModel) {
        this.selectedConfig = [];
        this.productConfig = productConfig;
        for (var key in productConfig) {
            productConfig[key] ? this.selectedConfig.push(key) : "";
        }
    }

    filterTemplates(productConfig: ProductConfigurationModel, templates: TemplateModel[]) {
        let filteredTemplates: TemplateModel[] = [];
        this.setSelectedConfiguration(productConfig);
        for (let template of templates) {
            if (template.isGeneric) {
                filteredTemplates.push(template);
            } else {
                if (this.filterTemplatesByProductSelection(template)) {
                    filteredTemplates.push(template);
                }
            }
        }
        return filteredTemplates;
    }


    getHTMLContentFromElement(element) {
        var templateSlide = jQuery(element).find(this.templateSlideElementID)[0];
        var htmlView = jQuery(templateSlide).html();
        return htmlView;
    }

    getHTMLContentFromStackedElement(element) {
        var templateSlide = jQuery(element).find(this.templateSlideElementID)[0];
        var htmlView = this.stripAllStylesFromElement(jQuery(templateSlide));
        return htmlView;
    }

    stripAllStylesFromElement(element) {
        var dupEle = jQuery(element).clone();
        jQuery(dupEle).find(".template-stack-container").removeAttr('style');
        jQuery(dupEle).find(".template-stack-area").removeAttr('style');
        jQuery(dupEle).find(".template-transform-stack").removeAttr('style');
        var divItems = jQuery(dupEle).find('.template-stack-area');
        jQuery(dupEle).html(divItems.get().reverse());
        return dupEle.html();
    }

    reverseElements(dupEle) {
        var stackEle = jQuery(dupEle).find(".template-transform-stack");
        var revEle = [];
        for (var i = stackEle.length - 1; i >= 0; i--) {
            revEle.push(jQuery(stackEle[i]));
        }
        return revEle;
    }

    stackHTMLForPreviewPane(html) {
        var elements = jQuery(html).find(".page");
        var pageWidth = jQuery("#preview-html").width();
        var pageHeight = jQuery("#preview-html").height();
        var totalElements = elements.length;
        var templateHTML = "<div style='width: " + pageWidth + "px; height : " + pageHeight + "px; ' class='template-stack-container'>";
        var count = 0;
        for (var i = elements.length - 1; i >= 0; i--) {
            var right = (i) * 10;
            var top = (i) * 6;
            var width = pageWidth - ((totalElements - 1) * 10);
            var height = pageHeight - ((totalElements - 1) * 10);
            var ratio = width / 1754;
            templateHTML += "<div style=' top : " + top + "px; right :" + right + "px; width : " + width + "px; " +
                "height : " + height + "px; z-index: " + i + "; ' class='template-stack-area'>" +
                "<div style='transform: scale(" + ratio + "); ' class='template-transform-stack'>";
            templateHTML += "<div class='" + jQuery(elements[i]).attr('class') + "'>"
            templateHTML += jQuery(elements[i]).html();
            templateHTML += "</div></div></div>";
            count++;
        }
        templateHTML += "</div>";
        return templateHTML;
    }

    findAndGetIndexFromList(id: any, source: TemplateModel[]) {
        return _.indexOf(source, _.findWhere(source, {id: UtilityService.toInt(id)}))
    }

    getIndexFromList(template: TemplateModel, source: TemplateModel[]) {
        return _.indexOf(source, template);
    }

    getTemplateById(templates: TemplateModel[], templateId: any) {
        return _.findWhere(templates, {id: templateId});
    }

    setResizeDimensions(element: ElementRef, isChildPresent: boolean, isLibrary: boolean) {
        let parentElement: any = jQuery(element).parent();
        this.containerWidth = isLibrary ? this.calculateWidthForLibrary(parentElement) : (jQuery(parentElement).width() * 100) / 100;
        var scaleRatio = this.containerWidth / this.defaultWidth;
        var parentHeight = scaleRatio * this.defaultHeight;
        if (isChildPresent) {
            jQuery(element).find(this.templatePageElementID).css("transform", "scale(" + scaleRatio + ")");
            jQuery(element).find(this.templatePageElementID).css("transform-origin", "0 0 0");
        }else{
            jQuery(element).css("transform", "scale(" + scaleRatio + ")");
            jQuery(element).css("transform-origin", "0 0 0");
        }
        parentElement.css("height", parentHeight);
    }

    calculateWidthForLibrary(element) {
        var collapseWidth = 0;
        var libWidth = jQuery(".library-pane").width();
        collapseWidth = libWidth - (libWidth * 3 / 100) * 2; //removePadding
        collapseWidth = collapseWidth - 4 - 10; //remove border & padding
        var wPercentage = jQuery(element).parent().width();
        var width = collapseWidth * 19 /100 -2;
        return width;
    }

    addBackgroundModal() {
        jQuery("body").append(this.modalPopupBgHTML);
    }

    showBackgroundModal() {
        jQuery(this.libraryModalBgElementID).css('opacity', '1');
        jQuery(this.libraryModalBgElementID).css('height', '100%');
        jQuery(this.libraryModalBgElementID).css('opacity', '0.5');
    }

    hideBackgroundModal() {
        jQuery(this.libraryModalBgElementID).css('opacity', '0');
        jQuery(this.libraryModalBgElementID).css('height', '0');
        jQuery(this.libraryModalBgElementID).css('padding', '0');
        jQuery(this.libraryModalBgElementID).css('overflow', 'hidden');
        jQuery(this.libraryModalBgElementID).css('transition', 'all .5s ease .3s');
    }

    getSnapShotsStatus(productConfig, geoOverviewLink, geos, rooftopSnapshotLink) {
        let geoStatus = productConfig.showGeoComponent ? this.getGeoSnapShotsStatus(geoOverviewLink, geos) : !productConfig.showGeoComponent;
        let rooftopStatus = productConfig.showRoofTopsComponent ? this.getRooftopsStatus(rooftopSnapshotLink) : !productConfig.showRoofTopsComponent;
        return geoStatus && rooftopStatus;
    }

    getRooftopsStatus(rooftopSnapshotLink) {
        return rooftopSnapshotLink != null;
    }

    getGeoSnapShotsStatus(geoOverviewLink, geos: any[]) {
        let status = false;
        status = geoOverviewLink != null;
        for (let geo of geos) {
            status = status && geo.geo_snapshot_link != null;
        }
        return status;
    }

    collateTemplatesByCategory(templates: TemplateModel[], categories: CategoryModel[]) {
        let categoryCollator: any = {};
        for (let category of categories) {
            categoryCollator[category.id] = _.where(templates, {categoryId: category.id});
        }
        return categoryCollator;
    }

    scrollToATemplate(templateId) {
        var position = jQuery(".filmstrip-slides-container").prop('scrollHeight') - 30;
        jQuery('.filmstrip-slides-container').animate({
            scrollTop: position
        }, 200);
    }

    scrollToNextTemplate(nextTemplateId, goingDown) {
        var scrollPosition = jQuery(".filmstrip-slides-container").scrollTop();
        var nextPosition = jQuery("#slide-sorter-" + nextTemplateId).position().top;
        var nextSlideHeight = jQuery("#slide-sorter-" + nextTemplateId).height();
        var containerHeight = jQuery('.filmstrip-slides-container').height();
        if (goingDown) {
            if (nextPosition > containerHeight) {
                scrollPosition += (nextSlideHeight + 30);
                jQuery(".filmstrip-slides-container").animate({
                    scrollTop: scrollPosition
                }, 10);
            }
        } else {
            if (nextPosition < 0) {
                scrollPosition -= (nextSlideHeight + 30);
                jQuery(".filmstrip-slides-container").animate({
                    scrollTop: scrollPosition
                }, 10);
            }
        }
    }

}
