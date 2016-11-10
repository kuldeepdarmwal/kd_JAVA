import {Subject} from "rxjs/Subject";
import {Injectable} from "@angular/core";

@Injectable()
export class ReforecastService {
    private reforecastFlight = new Subject<any>();

    reforecastFlight$ = this.reforecastFlight.asObservable();

    reforecast(status) {
        this.reforecastFlight.next(status);
    }
}
