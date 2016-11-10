import {Pipe, PipeTransform} from '@angular/core';

/*
 * Changes the case of the first letter of a given number of words in a string.
*/

@Pipe({name: 'join', pure: false})
export class Join implements PipeTransform {
    transform(input:Array<any>): any{
    	if(!Array.isArray(input)) return input;

        return input.join(', ').trim();
    }
}