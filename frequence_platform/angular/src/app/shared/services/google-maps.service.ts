import {Observable} from "rxjs/Observable";
import {Injectable} from "@angular/core";
declare var google: any;

@Injectable()
export class GoogleMapsService {
	private geocoder:any;

	constructor() {
		this.geocoder = new google.maps.Geocoder();
	}

	getCoords(term: string) {
		return new Promise((resolve, reject) => {
			this.geocoder.geocode({ 'address': term }, (results, status) => {
				if (status == google.maps.GeocoderStatus.OK) {
					resolve(results[0]);
				} else {
					reject(status);
				}
			});
		});
	}

}