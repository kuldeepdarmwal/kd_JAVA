System.register(['angular2/core', 'angular2/http', 'angular2-materialize', './page', './new-page', '../../services/page', '../../services/proposal', '../../services/config', '../../utils/orderby'], function(exports_1, context_1) {
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
    var core_1, http_1, angular2_materialize_1, page_1, new_page_1, page_2, proposal_1, config_1, orderby_1;
    var Proposal;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (http_1_1) {
                http_1 = http_1_1;
            },
            function (angular2_materialize_1_1) {
                angular2_materialize_1 = angular2_materialize_1_1;
            },
            function (page_1_1) {
                page_1 = page_1_1;
            },
            function (new_page_1_1) {
                new_page_1 = new_page_1_1;
            },
            function (page_2_1) {
                page_2 = page_2_1;
            },
            function (proposal_1_1) {
                proposal_1 = proposal_1_1;
            },
            function (config_1_1) {
                config_1 = config_1_1;
            },
            function (orderby_1_1) {
                orderby_1 = orderby_1_1;
            }],
        execute: function() {
            Proposal = (function () {
                function Proposal(_proposalService, _pageService, config) {
                    this._proposalService = _proposalService;
                    this._pageService = _pageService;
                    this.config = config;
                }
                Proposal.prototype.ngAfterViewInit = function () {
                    this.getProposal();
                };
                Proposal.prototype.getProposal = function () {
                    var _this = this;
                    this._proposalService.getProposal()
                        .subscribe(function (proposal) {
                        _this.proposal = proposal.json();
                        _this.getPages();
                    });
                };
                Proposal.prototype.getPages = function () {
                    var _this = this;
                    this._pageService.getPages()
                        .subscribe(function (pages) {
                        _this.pages = pages;
                    });
                };
                Proposal.prototype.addPage = function (template) {
                    var _this = this;
                    var weight = this.insertWeight + 1; // insert after the button we clicked
                    this._pageService.addPage(weight, template.id)
                        .subscribe(function (page) {
                        _this.pages.splice(weight, 0, page);
                        _this.sortPages();
                    });
                };
                Proposal.prototype.removePage = function (page) {
                    var _this = this;
                    this._pageService.removePage(page.id)
                        .subscribe(function (res) {
                        for (var i = 0; i < _this.pages.length; i++) {
                            if (_this.pages[i].id === page.id) {
                                _this.pages.splice(i, 1);
                            }
                        }
                        _this.sortPages();
                    });
                };
                Proposal.prototype.sortPages = function () {
                    for (var i = 0; i < this.pages.length; i++) {
                        this.pages[i].weight = i;
                    }
                };
                Proposal.prototype.pageTrackBy = function (index, page) {
                    return page.id;
                };
                Proposal.prototype.setInsertWeight = function (weight) {
                    this.insertWeight = weight;
                    this.resizeWindow();
                };
                Proposal.prototype.resizeWindow = function () {
                    var timeout = window.setInterval(function () {
                        if (document.querySelector('#new-page-form page-frame').offsetHeight > 0) {
                            clearInterval(timeout);
                        }
                        window.dispatchEvent(new Event('resize')); // opens the page frames in the modal
                    }, 50);
                };
                __decorate([
                    core_1.ViewChildren(page_1.PageView), 
                    __metadata('design:type', core_1.QueryList)
                ], Proposal.prototype, "_pageView", void 0);
                Proposal = __decorate([
                    core_1.Component({
                        selector: 'proposal',
                        templateUrl: '/public/app/components/proposal-builder/proposal.html?v=<%= VERSION %>',
                        pipes: [orderby_1.OrderBy],
                        directives: [page_1.PageView, new_page_1.NewPageView, angular2_materialize_1.MaterializeDirective],
                        providers: [
                            http_1.HTTP_PROVIDERS,
                            proposal_1.ProposalService,
                            page_2.PageModel,
                            page_2.PageService
                        ]
                    }),
                    __param(2, core_1.Inject(config_1.ConfigService)), 
                    __metadata('design:paramtypes', [proposal_1.ProposalService, page_2.PageService, Object])
                ], Proposal);
                return Proposal;
            }());
            exports_1("Proposal", Proposal);
        }
    }
});

//# sourceMappingURL=proposal.js.map
