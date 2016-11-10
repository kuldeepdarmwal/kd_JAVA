import {Http, Headers, RequestOptionsArgs} from "@angular/http"
declare var jQuery:any;
export class HTTPService {

    private headers:Headers;
    private http:Http;

    defaultOptionsArgs:RequestOptionsArgs;
    defaultData : string = "";

    constructor(http:Http) {
        this.http = http;
        this.headers = new Headers();
        this.headers.append('Content-Type', 'application/x-www-form-urlencoded');
        this.defaultOptionsArgs = {
            'headers': this.headers
        };
    }

    create(servicePath:string, model:any, options?:RequestOptionsArgs) {
        var options = options ? options : this.defaultOptionsArgs;
        return this.http.post(servicePath, jQuery.param(model), options);
    }

    query(servicePath:string, data? : string, options?:RequestOptionsArgs) {
        var options = options ? options : this.defaultOptionsArgs;
        return this.http.get(servicePath, options);
    }

}
