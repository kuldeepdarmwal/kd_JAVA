import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'sum', pure: false})
export class Sum implements PipeTransform {

    transform(input:Array<any>, property?:any): any{
        
        if(!Array.isArray(input)) return input;

        return input.reduce((total, item) => {

            let val = property !== undefined ? item[property] : item;
	    
	    if(typeof val == 'string'){
		val = val.replace(/[^\d\.\-\ ]/g, '');
		val = parseFloat(val);
	    }
	    
            if (val == '') return parseFloat(total);
            return parseFloat(total) + parseFloat(val);

        }, 0);
    }

}