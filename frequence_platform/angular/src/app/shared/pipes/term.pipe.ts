import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'termTransform'})
export class TermTransform implements PipeTransform {

    transform(input:any, plural:boolean): any{
        let output = input;

        switch(input.toLowerCase()) {
            case 'monthly':
                output = plural ? 'Months' : 'Month';
                break;

            case 'weekly':
                output = plural ? 'Weeks' : 'Week';
                break;

            case 'daily':
                output = plural ? 'Days' : 'Day';
                break;
        }

        return output;
    }

}