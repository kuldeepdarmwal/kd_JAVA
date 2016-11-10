System.register(['angular2/core', 'angular2/http', './config'], function(exports_1, context_1) {
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
    var core_1, http_1, config_1;
    var ProposalService;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (http_1_1) {
                http_1 = http_1_1;
            },
            function (config_1_1) {
                config_1 = config_1_1;
            }],
        execute: function() {
            ProposalService = (function () {
                function ProposalService(http, config) {
                    this.http = http;
                    this.config = config;
                }
                ProposalService.prototype.getProposal = function () {
                    return this.http.get("/proposals/data/" + this.config.proposal_id);
                };
                ProposalService = __decorate([
                    core_1.Injectable(),
                    __param(1, core_1.Inject(config_1.ConfigService)), 
                    __metadata('design:paramtypes', [http_1.Http, Object])
                ], ProposalService);
                return ProposalService;
            }());
            exports_1("ProposalService", ProposalService);
        }
    }
});

//# sourceMappingURL=proposal.js.map
