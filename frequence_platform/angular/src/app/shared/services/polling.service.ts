import {Observable} from 'rxjs/Rx'
import "rxjs/add/operator/map";
import "rxjs/add/operator/catch";
import {Response, Http} from "@angular/http";
import {Injectable} from "@angular/core";
import {HTTPService} from "../../shared/services/http.service";
declare var jQuery:any;

@Injectable()
export class PollingService extends HTTPService {
	constructor(http:Http){
		super(http);
	}

	pollData(url, args, interval, stopPolling) {
		return Observable
			.interval(interval)
			.mergeMap(() => this.create(url, args))
			.map((res) => res.json())
			.takeUntil(stopPolling);
	}
}