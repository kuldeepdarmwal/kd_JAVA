import {Injectable, EventEmitter} from '@angular/core';
declare let dragula: any;
declare let jQuery : any;
declare let _ : any;

@Injectable()
export class DragulaService {
    public cancel:      EventEmitter<any> = new EventEmitter();
    public cloned:      EventEmitter<any> = new EventEmitter();
    public drag:        EventEmitter<any> = new EventEmitter();
    public dragend:     EventEmitter<any> = new EventEmitter();
    public drop:        EventEmitter<any> = new EventEmitter();
    public out:         EventEmitter<any> = new EventEmitter();
    public over:        EventEmitter<any> = new EventEmitter();
    public remove:      EventEmitter<any> = new EventEmitter();
    public shadow:      EventEmitter<any> = new EventEmitter();
    public dropModel:   EventEmitter<any> = new EventEmitter();
    public removeModel: EventEmitter<any> = new EventEmitter();
    private events: Array<string> = [
        'cancel',
        'cloned',
        'drag',
        'dragend',
        'drop',
        'out',
        'over',
        'remove',
        'shadow',
        'dropModel',
        'removeModel'
    ];
    private bags: Array<any> = [];
    private eventObj : any = {};

    constructor(){}

    public add(name: string, drake: any): any {
        let bag = this.find(name);
        if (bag) {
            throw new Error('Bag named: "' + name + '" already exists.');
        }
        bag = {
            name: name,
            drake: drake
        };
        this.bags.push(bag);
        if (drake.models) { // models to sync with (must have same structure as containers)
            this.handleModels(name, drake);
        }
        if (!bag.initEvents) {
             this.setupEvents(bag);
        }
        return bag;
    }

    public find(name: string): any {
        for (var i = 0; i < this.bags.length; i++) {
            if (this.bags[i].name === name) {
                return this.bags[i];
            }
        }
    }

    public destroy(name: string): void {
        let bag = this.find(name);
        let i = this.bags.indexOf(bag);
        this.bags.splice(i, 1);
        bag.drake.destroy();
    }

    public setOptions(name: string, options: any) {
        let bag = this.add(name, dragula(options));
        this.handleModels(name, bag.drake);
    }

    private handleModels(name: string, drake: any) {
        let dragElm: any;
        let dropElem : any;
        let dragIndex: number;
        let dropIndex: number;
        let sourceModel: any;
        let targetModel : any;
        let mask : any;
        drake.on('remove', (el: any, source: any) => {
            if (!drake.models) {
                return;
            }
            sourceModel = drake.models[drake.containers.indexOf(source)];
            sourceModel.splice(dragIndex, 1);
            this.removeModel.emit([name, el, source]);
        });
        drake.on('drag', (el: any, source: any) => {
            dragElm = el;
            dragIndex = this.domIndexOf(el, source);
            sourceModel = drake.models[drake.containers.indexOf(source)];

            mask = jQuery(source).parent();
            var h = mask.height();
            mask.bind('mousemove',function(e) {
                var offset=mask.position().top;
                var mousePosition = e.clientY - offset;
                var topRegion = 0.65 * h;
                var bottomRegion = 0.35 * h;
                if((mousePosition < topRegion || mousePosition > bottomRegion)) {    // e.which = 1 => click down !
                    var distance = mousePosition - h / 2;
                    distance = distance * 0.04; // <- velocity
                    mask.scrollTop(distance + mask.scrollTop());
                }
            });
            this.drag.emit([dragElm, dragIndex, sourceModel])
        });
        drake.on('drop', (dropElm: any, target: any, source: any) => {
            mask.unbind('mousemove');
            dropElem = _.extend(dropElm);
            this._onDrop(drake, dropElem, target, source, dragIndex);
            drake.cancel(true);
            this.dropModel.emit([sourceModel, targetModel, dragIndex, dropIndex]);
        });
        drake.on('cancel', (elm: any) => {
            this.dropModel.emit(this.eventObj);
        });
    }

    private _onDrop(drake, dropElm, target, source, dragIndex){
        let dropIndex: number;
        let sourceModel: any;
        let targetModel : any;
        if (!drake.models || !target) {
            return;
        }
        dropIndex = this.domIndexOf(dropElm, target);
        sourceModel = drake.models[drake.containers.indexOf(source)];
        targetModel = drake.models[drake.containers.indexOf(target)];
        this.eventObj = {source: sourceModel, target: targetModel, sourceIndex: dragIndex, targetIndex : dropIndex};
    }

    private setupEvents(bag: any) {
        bag.initEvents = true;
        let that: any = this;
        let emitter = (type: any) => {
            function replicate (value) {
                let args = Array.prototype.slice.call(arguments);
                that[type].emit([bag.name].concat(that.eventObj));
            }
            bag.drake.on(type, replicate);
        };
        this.events.forEach(emitter);
    }

    private domIndexOf(child: any, parent: any) {
        return Array.prototype.indexOf.call(parent.children, child);
    }
}