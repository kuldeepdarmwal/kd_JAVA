System.register(['angular2/core', 'angular2/http', 'rxjs/Observable', './config'], function(exports_1, context_1) {
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
    var core_1, http_1, Observable_1, config_1;
    var PageModel, PageService;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (http_1_1) {
                http_1 = http_1_1;
            },
            function (Observable_1_1) {
                Observable_1 = Observable_1_1;
            },
            function (config_1_1) {
                config_1 = config_1_1;
            }],
        execute: function() {
            PageModel = (function () {
                function PageModel(id, weight, template, friendly_name) {
                    this.id = id;
                    this.weight = weight;
                    this.template = template;
                    this.friendly_name = friendly_name;
                }
                PageModel = __decorate([
                    core_1.Injectable(), 
                    __metadata('design:paramtypes', [Number, Number, String, String])
                ], PageModel);
                return PageModel;
            }());
            exports_1("PageModel", PageModel);
            PageService = (function () {
                function PageService(http, config) {
                    this.http = http;
                    this.config = config;
                }
                PageService.prototype.getPages = function () {
                    return this.http.get("/proposals/pages/" + this.config.proposal_id)
                        .map(function (res) {
                        return res.json().map(function (page) {
                            return new PageModel(parseInt(page.id), parseInt(page.weight), page.template);
                        });
                    })
                        .catch(this.handleError);
                };
                PageService.prototype.getPageTemplates = function () {
                    return this.http.get("/proposals/page_templates/" + this.config.proposal_id)
                        .map(function (res) {
                        return res.json().map(function (page) {
                            return new PageModel(parseInt(page.id), parseInt(page.weight), page.template, page.friendly_name);
                        });
                    })
                        .catch(this.handleError);
                };
                PageService.prototype.addPage = function (weight, template_id) {
                    var body = this.makePostString({ proposal_id: this.config.proposal_id, weight: weight, template_id: template_id });
                    var headers = new http_1.Headers({ 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' });
                    var options = new http_1.RequestOptions({ headers: headers });
                    return this.http.post('/proposals/add_page', body, options)
                        .map(function (res) {
                        var page = res.json();
                        return new PageModel(page.id, weight, page.template);
                    })
                        .catch(this.handleError);
                };
                PageService.prototype.removePage = function (id) {
                    var body = this.makePostString({ page_id: id });
                    var headers = new http_1.Headers({ 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' });
                    var options = new http_1.RequestOptions({ headers: headers });
                    return this.http.post('/proposals/remove_page', body, options)
                        .map(function (res) { return res.status; })
                        .catch(this.handleError);
                };
                PageService.prototype.handleError = function (error) {
                    return Observable_1.Observable.throw(error.json().error || 'Server error');
                };
                PageService.prototype.makePostString = function (obj) {
                    var str = '';
                    for (var key in obj) {
                        if (obj.hasOwnProperty(key)) {
                            str += key + "=" + obj[key] + "&";
                        }
                    }
                    return str;
                };
                PageService = __decorate([
                    core_1.Injectable(),
                    __param(1, core_1.Inject(config_1.ConfigService)), 
                    __metadata('design:paramtypes', [http_1.Http, Object])
                ], PageService);
                return PageService;
            }());
            exports_1("PageService", PageService);
        }
    }
});

//# sourceMappingURL=page.js.map
