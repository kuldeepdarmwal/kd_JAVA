import {Pipe, PipeTransform} from '@angular/core';

/*
 * Changes the case of the first letter of a given number of words in a string.
*/

@Pipe({name: 'number_format'})
export class NumberFormat implements PipeTransform {
    transform(input:number): string{
        return input.toLocaleString();
    }
}