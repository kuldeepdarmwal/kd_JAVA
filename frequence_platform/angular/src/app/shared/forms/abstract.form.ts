import {form} from "./form";

export class abstractForm implements form {

    getForm():{} {
        return undefined;
    }

    getData(group):any {
        var data = {};
        for (var key in group.controls) {
            data[key] = group.controls[key].value;
        }
        return data;
    }

    setData(group, obj): any{
        for(var key in obj){
            group.controls[key].updateValue(obj[key]);
        }
    }

}
