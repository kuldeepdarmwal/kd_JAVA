import {Pipe, PipeTransform} from '@angular/core';
import {UtilityService} from '../services/utility.service';

/*
 * Changes the given parameter as an integer.
*/

@Pipe({name: 'typecast'})
export class Typecast implements PipeTransform {
    transform(input:any, type:string): number{
        switch(type){
        	case 'int':
        		return parseInt(input);

        	case 'float':
        		return parseFloat(input);
        }
    }
}