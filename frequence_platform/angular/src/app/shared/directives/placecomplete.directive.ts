import {Directive, ElementRef, Input, OnInit} from "@angular/core";

declare var jQuery:any;
@Directive({
    selector: '[myPlaceComplete]',
    host: {
        '(click)' : 'onClick($event)'
    }
})
export class PlaceCompleteDirective implements OnInit{

    @Input() propertiesObj;
    element : any;
    adv : any;

    constructor(el: ElementRef) {
        this.element = el;
    }

    ngOnInit(){
        jQuery(this.element.nativeElement).placecomplete({
            width: '100%',
            placeholder: this.propertiesObj.placeHolder,
            minimumInputLength: this.propertiesObj.minlength || 3,
            maximumSelectionSize: this.propertiesObj.maximumSelectionSize,
            multiple: this.propertiesObj.allowMultiple,
            allowClear: this.propertiesObj.allowClear,
            ajax: {
                url: this.propertiesObj.url,
                type: "POST",
                dataType: "json",
                data : this.propertiesObj.dataFn,
                transport:  this.propertiesObj.fetchFn,
                results: function (data) {
                    return {results: data.results , more: data.more};
                }
            },
            formatResult: this.propertiesObj.resultFormatFn,
            requestParams: this.propertiesObj.requestParams
        });
    }
    
    private onClick($event){
        var data = jQuery($event.target).select2('data');
        if (this.propertiesObj.location_id !== undefined) 
            data.location_id = this.propertiesObj.location_id;
        if (this.propertiesObj.geofence_id !== undefined)
            data.geofence_id = this.propertiesObj.geofence_id;
        this.propertiesObj.emitter.next(data);
    }

}