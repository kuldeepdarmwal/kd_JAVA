import {ProductModel} from "../../rfp/models/product.model";


declare var moment : any;
/**
 *  Utility Service for misc. utilities
 *  Contains all static methods
 */
export class UtilityService {

    static isNumber(o) {
        return !isNaN(o - 0) && o != null;
    }

    static toJson(string) {
        return JSON.parse(string);
    }

    static toInt(string) {
        return parseInt(string);
    }
	
	static toFloat(string){
		return parseFloat(string);
	}

    static toIntOrReturnZero(value) {
        value = this.formatNumber(value);
        if (!this.isNumber(this.toInt(value))) return 0;
        else return this.toInt(value);
    }
	
	static toFloatOrReturnZero(value){
		value = this.formatPositiveNumber(value);
		if(!this.isNumber(this.toFloat(value))) return 0;
		else return this.toFloat(value);
	}

	static toDollarsOrReturnZero(value){
		value = this.toFloatOrReturnZero(value).toFixed(2);
		return value * 1;
	}

	static formatPositiveNumber(value) {
		return value.toString().replace(/[^\d\.\ ]/g, '');
	}

    static formatNumber(value) {
        return value.toString().replace(/[^\d\.\-\ ]/g, '');
    }

    static isUndefined(obj) {
        return typeof obj === "undefined";
    }

    static toTrueOrFalse(value){
        return !!this.toInt(value);
    }

    static getCurrentDate(){
       return moment().format('YYYY-MM-DD');
    }

    static getNamesOfProducts(products:ProductModel[]) {
        let productNames:string[] = [];
        for (let product of products) {
            let productName:string = ""
            if (product.definition) {
                if (product.definition.first_name)
                    productName += product.definition.first_name;

                if (product.definition.last_name)
                    productName += productName === "" ? product.definition.last_name : " " + product.definition.last_name;
                productNames.push(productName);
            }
        }
        return productNames;
    }

    static listToMatrix(list, elementsPerSubList) {
        var matrix = [], i, k;

        for (i = 0, k = -1; i < list.length; i++) {
            if (i % elementsPerSubList === 0) {
                k++;
                matrix[k] = [];
            }

            matrix[k].push(list[i]);
        }

        return matrix;
    }
    
}
