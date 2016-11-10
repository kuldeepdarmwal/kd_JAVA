import {Pipe, PipeTransform} from '@angular/core';

interface productRegionFilters {
    productId ?: any
    regionId ?: any
}

@Pipe({name: 'productRegionFilter', pure: false})
export class ProductRegionFilter implements PipeTransform {

    transform(input:any, config:productRegionFilters): any{
        
        if(!Array.isArray(input)) return input;

        return input.filter((item) => {
            
            let status = true;

            if (config.productId !== undefined){
                if (item.productId != config.productId)
                    status = false;
            }

            if (config.regionId !== undefined){
                if (item.regionId != config.regionId)
                    status = false;
            }

            return status;

        });
    }

}