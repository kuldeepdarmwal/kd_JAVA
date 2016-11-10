System.register(['angular2/core'], function(exports_1, context_1) {
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
    var core_1;
    var ResizeFrame;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            }],
        execute: function() {
            ResizeFrame = (function () {
                function ResizeFrame(el) {
                    this.el = el;
                    this._el = el.nativeElement;
                }
                ResizeFrame.prototype.onLoad = function (e) {
                    this.onResize();
                };
                ResizeFrame.prototype.onResize = function () {
                    var container_width = document.querySelector('main').offsetWidth;
                    var page = this._el.contentWindow.document.querySelector('.page');
                    if (page)
                        this.pageWidth = page.offsetWidth;
                    var ratio = container_width / (this.pageWidth + 50);
                    if (ratio >= 1) {
                        this._el.contentWindow.document.body.setAttribute('style', "transform: none; width: 100%;");
                        return true;
                    }
                    this._el.contentWindow.document.body.setAttribute('style', "\n\t\t\twidth: " + this.pageWidth + "px;\n\t\t\ttransform: scale(" + ratio + ");\n\t\t\ttransform-origin: 0px 0px 0px;\n\t\t\toverflow: hidden");
                    if (page)
                        page.setAttribute('style', "margin:0;");
                    if (page)
                        this._el.setAttribute('style', "height: " + (page.offsetHeight * ratio + 11) + "px");
                };
                ResizeFrame = __decorate([
                    core_1.Directive({
                        selector: 'iframe[resize]',
                        host: {
                            '(window:resize)': 'onResize($event)',
                            '(load)': 'onLoad($event)'
                        }
                    }), 
                    __metadata('design:paramtypes', [core_1.ElementRef])
                ], ResizeFrame);
                return ResizeFrame;
            }());
            exports_1("ResizeFrame", ResizeFrame);
        }
    }
});

//# sourceMappingURL=resize-frame.js.map
