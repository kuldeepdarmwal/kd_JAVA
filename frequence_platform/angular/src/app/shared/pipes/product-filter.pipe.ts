import {Pipe, PipeTransform} from '@angular/core';

interface productFilters {
    selectable ?: boolean
    selected ?: boolean
    after_discount ?: boolean
    term_based ?: boolean
    category ?: string
}

@Pipe({name: 'productFilter', pure: false})
export class ProductFilter implements PipeTransform {

    transform(input:any, config:productFilters): any{
        
        if(!Array.isArray(input)) return input;

        return input.filter((product) => {

            let status = true;

            if (config.selectable !== undefined){
                if (config.selectable !== (product.selectable === "1"))
                    status = false;
            }

            if (config.selected !== undefined){
                if (config.selected !== (product.selected))
                    status = false;
            }

            if (config.after_discount !== undefined){
                if (config.after_discount !== product.definition.after_discount)
                    status = false;
            }

            if (config.term_based !== undefined){
                if (config.term_based !== (product.term_based === "1"))
                    status = false;
            }

            if (config.category !== undefined){
                if (config.category !== product.definition.category)
                    status = false;
            }

            return status;

        });
    }

}