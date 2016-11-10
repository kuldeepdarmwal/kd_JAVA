System.register(['angular2/core', 'angular2-materialize', './page', '../../services/page', '../../services/config', '../../directives/page-frame'], function(exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
        var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
        if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
        else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
    var __metadata = (this && this.__metadata) || function (k, v) {
        if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
    };
    var __param = (this && this.__param) || function (paramIndex, decorator) {
        return function (target, key) { decorator(target, key, paramIndex); }
    };
    var core_1, angular2_materialize_1, page_1, page_2, config_1, page_frame_1;
    var NewPageView;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (angular2_materialize_1_1) {
                angular2_materialize_1 = angular2_materialize_1_1;
            },
            function (page_1_1) {
                page_1 = page_1_1;
            },
            function (page_2_1) {
                page_2 = page_2_1;
            },
            function (config_1_1) {
                config_1 = config_1_1;
            },
            function (page_frame_1_1) {
                page_frame_1 = page_frame_1_1;
            }],
        execute: function() {
            NewPageView = (function () {
                function NewPageView(el, _pageService, config) {
                    this.el = el;
                    this._pageService = _pageService;
                    this.config = config;
                    this.onAddPage = new core_1.EventEmitter();
                }
                NewPageView.prototype.ngAfterViewInit = function () {
                    this.getPages();
                };
                NewPageView.prototype.getPages = function () {
                    var _this = this;
                    this._pageService.getPageTemplates()
                        .subscribe(function (templates) {
                        _this.templates = templates;
                    });
                };
                NewPageView.prototype.onSelect = function (template) {
                    this.selectedTemplate = template;
                };
                NewPageView.prototype.addPage = function () {
                    this.onAddPage.emit(this.selectedTemplate);
                    this.selectedTemplate = null;
                };
                __decorate([
                    core_1.Output(), 
                    __metadata('design:type', core_1.EventEmitter)
                ], NewPageView.prototype, "onAddPage", void 0);
                __decorate([
                    core_1.Input(), 
                    __metadata('design:type', Object)
                ], NewPageView.prototype, "proposal", void 0);
                NewPageView = __decorate([
                    core_1.Component({
                        selector: 'new-page-form',
                        templateUrl: '/public/app/components/proposal-builder/new-page.html?v=<%= VERSION %>',
                        directives: [page_1.PageView, angular2_materialize_1.MaterializeDirective, page_frame_1.PageFrame]
                    }),
                    __param(2, core_1.Inject(config_1.ConfigService)), 
                    __metadata('design:paramtypes', [core_1.ElementRef, page_2.PageService, Object])
                ], NewPageView);
                return NewPageView;
            }());
            exports_1("NewPageView", NewPageView);
        }
    }
});

//# sourceMappingURL=new-page.js.map
