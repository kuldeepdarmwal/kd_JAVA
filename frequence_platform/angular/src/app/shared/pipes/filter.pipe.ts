import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'filter', pure: false})
export class filter implements PipeTransform {

    transform(input:any, config: Object): any{
        
        if(!Array.isArray(input)) return input;

        return input.filter((item) => {
            
            let status = true;

            for (let i in config){
                if (config.hasOwnProperty(i)){
                    if (item[i] === undefined || item[i] != config[i]){
                        status = false;
                    }
                }
            }

            return status;

        });
    }

}