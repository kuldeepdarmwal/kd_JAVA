import {Directive, ElementRef, Input, OnInit} from "@angular/core";

declare var jQuery:any;
@Directive({
    selector: '[mySelect2]',
    host: {
        '(click)' : 'onClick($event)'
    }
})
export class Select2Directive implements OnInit{

    @Input() propertiesObj;
    element : any;
    adv : any;

    constructor(el: ElementRef) {
        this.element = el;
    }

    ngOnInit(){
        jQuery(this.element.nativeElement).select2({
            width: '100%',
            placeholder: this.propertiesObj.placeHolder,
            minimumInputLength: this.propertiesObj.minLength,
            multiple: this.propertiesObj.allowMultiple,
            allowClear: this.propertiesObj.allowClear,
            ajax: {
                url: this.propertiesObj.url,
                type: "POST",
                dataType: "json",
                data : this.propertiesObj.dataFn,
                quietMillis: this.propertiesObj.delay || 0,
                transport:  this.propertiesObj.fetchFn,
                results: this.propertiesObj.resultFn || 
                    function(data) {
                        return { results: data.results, more: data.more };
                    }
            },
            formatResult: this.propertiesObj.resultFormatFn,
            formatSelection: this.propertiesObj.formatSelectionFn
        });
    }
    
    private onClick($event){
        var data = jQuery($event.target).select2('data');
        this.propertiesObj.emitter.next(data);
    }

}