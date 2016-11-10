/// <reference path="../node_modules/angular2/typings/browser.d.ts" />
System.register(['rxjs/Rx', 'angular2-materialize', 'mustache', 'angular2/core', 'angular2/http', 'angular2/platform/browser', './components/proposal-builder/proposal', './services/config', './utils/orderby'], function(exports_1, context_1) {
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
    var core_1, http_1, browser_1, proposal_1, config_1, orderby_1;
    var ProposalBuilder;
    return {
        setters:[
            function (_1) {},
            function (_2) {},
            function (_3) {},
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (http_1_1) {
                http_1 = http_1_1;
            },
            function (browser_1_1) {
                browser_1 = browser_1_1;
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
            ProposalBuilder = (function () {
                function ProposalBuilder() {
                }
                ProposalBuilder = __decorate([
                    core_1.Component({
                        selector: 'main',
                        directives: [proposal_1.Proposal],
                        templateUrl: '/public/app/proposal-builder.html?v=<%= VERSION %>',
                        pipes: [orderby_1.OrderBy]
                    }), 
                    __metadata('design:paramtypes', [])
                ], ProposalBuilder);
                return ProposalBuilder;
            }());
            browser_1.bootstrap(ProposalBuilder, [http_1.HTTP_PROVIDERS, core_1.provide(config_1.ConfigService, { useClass: config_1.ConfigService })]);
        }
    }
});

//# sourceMappingURL=proposal-builder.js.map
