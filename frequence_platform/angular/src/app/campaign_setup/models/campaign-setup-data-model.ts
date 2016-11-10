import {Injectable} from "@angular/core";
import {CampaignSetupMapperService} from "../services/campaign-setup-mapper.service";

/**
 * This model is used to hold the IO Data.
 */
declare var _:any;
@Injectable()
export class CampaignSetupDataModel {
	private flights: any;
	
	constructor(private _campaignSetupMapperService : CampaignSetupMapperService){}
	
	get campaignSetupFlightsData() : any{
		return this.flights;
	}
	
	//logic to load data
	set campaignSetupFlightsData(obj : any){
		this.flights = this._campaignSetupMapperService.mapCampaignSetupFlightsResponseToModel(obj);
	}
}
