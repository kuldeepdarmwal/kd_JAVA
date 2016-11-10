import {Injector, ReflectiveInjector} from "@angular/core";
import {HTTP_PROVIDERS, Http} from "@angular/http";
import {ComponentInstruction} from "@angular/router-deprecated";
import {appInjector} from "../../shared/utils/app-injector";
import {SERVICE_URL, EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {EmitterService} from "../../shared/services/emitter.service";
import {CampaignSetupDataModel} from "../models/campaign-setup-data-model";

export const HasCampaignSetupFlightsData = (next:ComponentInstruction, prev:ComponentInstruction) => {
	let injector:Injector = appInjector(); // get the stored reference to the injector
	let httpInjector = ReflectiveInjector.resolveAndCreate([HTTP_PROVIDERS]);
	let campaignSetupDataModel : CampaignSetupDataModel = injector.get(CampaignSetupDataModel);
	let http = httpInjector.get(Http);
	let campaignId:string = next.params["campaignId"];
	
	return new Promise((resolve) => {
		EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
		if (!campaignId) {
			resolve(true);
		}
		else {
			http.post(SERVICE_URL.CAMPAIGN_SETUP.GET_CAMPAIGN_FLIGHTS + campaignId)
				.map((res) => res.json())
				.subscribe((response) => {
					response.campaignId = campaignId;
					campaignSetupDataModel.campaignSetupFlightsData =  response;
					resolve(true);
				}, (error) => {
					resolve(false);
				});
		}
	});
};