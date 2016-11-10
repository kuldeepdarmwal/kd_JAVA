import {ElementRef, Component, Output, EventEmitter} from "@angular/core";
declare var jQuery:any;

@Component({
    selector: 'slider',
    template: `
        <input type="range" id="slider" min="1" max="3" value="3" step="1">
    `
})

export class SliderDirective {
    element:any;
    @Output('on-slide') onSlide = new EventEmitter<any>();
    @Output('on-slide-end') onSlideEnd = new EventEmitter<any>();

    constructor(private el:ElementRef) {
        this.element = el.nativeElement;
    }

    ngOnInit() {
        var sliderElement = jQuery(this.element).find("#slider");
        var $ruler = jQuery('<div class="rangeslider__ruler" />');
        sliderElement.rangeslider({
            // Feature detection the default is `true`.
            // Set this to `false` if you want to use
            // the polyfill also in Browsers which support
            // the native <input type="range"> element.
            polyfill: false,
            // Default CSS classes
            rangeClass: 'rangeslider',
            disabledClass: 'rangeslider--disabled',
            horizontalClass: 'rangeslider--horizontal',
            verticalClass: 'rangeslider--vertical',
            fillClass: 'rangeslider__fill',
            handleClass: 'rangeslider__handle',

            // Callback function
            onInit: function() {
                let getRulerRange = (min, max, step) => {
                    var range = '';
                    var i = min;
                    while (i <= max) {
                        range += i + '-Column  ';
                        i = i + step;
                    }
                    return range;
                }
                $ruler[0].innerHTML = getRulerRange(this.min,this.max, this.step);
                this.$range.append($ruler);
            },

            onSlide: (position, value) =>{
                this.onSlide.emit(value);
            },

            onSlideEnd: (position, value) =>{
                this.onSlideEnd.emit(value);
            }
        });
    }

    static getRulerRange(min, max, step){
        var range = '';
        var i = 0;

        while (i <= max) {
            range += i + '  ';
            i = i + step;
        }
        return range;
    }

}
