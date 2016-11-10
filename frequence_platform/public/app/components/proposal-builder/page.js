System.register(['angular2/core', 'mustache', '../../services/page', '../../services/config', '../../directives/page-frame'], function(exports_1, context_1) {
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
    var core_1, page_1, config_1, page_frame_1;
    var PageView;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (_1) {},
            function (page_1_1) {
                page_1 = page_1_1;
            },
            function (config_1_1) {
                config_1 = config_1_1;
            },
            function (page_frame_1_1) {
                page_frame_1 = page_frame_1_1;
            }],
        execute: function() {
            PageView = (function () {
                function PageView(el, _pageService, config) {
                    this.el = el;
                    this._pageService = _pageService;
                    this.config = config;
                    this._el = el.nativeElement;
                }
                PageView.prototype.writeHtml = function () {
                    this._el.querySelector('iframe').contentWindow.document.body.innerHTML = Mustache.render(this.template.template, this.proposal);
                };
                __decorate([
                    core_1.Input(), 
                    __metadata('design:type', page_1.PageModel)
                ], PageView.prototype, "page", void 0);
                __decorate([
                    core_1.Input(), 
                    __metadata('design:type', Object)
                ], PageView.prototype, "proposal", void 0);
                PageView = __decorate([
                    core_1.Component({
                        selector: 'page-frame',
                        template: "<iframe src=\"/proposals/page/{{config.proposal_id}}\" pageframe (onPageLoad)=\"writeHtml()\"></iframe>",
                        directives: [page_frame_1.PageFrame]
                    }),
                    __param(2, core_1.Inject(config_1.ConfigService)), 
                    __metadata('design:paramtypes', [core_1.ElementRef, page_1.PageService, Object])
                ], PageView);
                return PageView;
            }());
            exports_1("PageView", PageView);
        }
    }
});

//# sourceMappingURL=page.js.map
