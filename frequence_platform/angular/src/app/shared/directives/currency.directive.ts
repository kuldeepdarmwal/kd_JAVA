import {Directive} from "@angular/core";
import {NgModel} from "@angular/common";
declare var jQuery : any;

@Directive({
    selector: '[ngModel][mycurrency]',
    providers: [NgModel]
})

export class CurrencyDirective{

    constructor(public model:NgModel) {
        this.model._control.valueChanges
            .subscribe((value) => { this.formatValue(value); });
    }

    formatValue(val){
        if(val){
            val = this.numberDollarsWithCommas(val);
            this.model.valueAccessor.writeValue(val);
            this.model.viewToModelUpdate(val);
        }
    }

    numberWithCommas(num) {
        num = num.toString().replace(/[^\d\.\-\ ]/g, '');
		return num.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
    }

	numberDollarsWithCommas(num) {
		num = num.toString().replace(/[^\d\.]/g, '');
		var i = 0;
		num = num.replace(/\./g, function(all, match) { return i++===0 ? '.' : ''; });
		var num_array = num.split('.');
		num_array[0] = this.numberWithCommas(num_array[0]);
		if(num_array.length > 1 && num_array[1] !== "")
		{
			num_array[1] = num_array[1].substring(0,2);
		}
		return num_array.join('.');
	}
}
