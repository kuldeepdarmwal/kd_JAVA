import {Injector, ReflectiveInjector} from "@angular/core";
import {HTTP_PROVIDERS, Http} from "@angular/http";
import {ComponentInstruction} from "@angular/router-deprecated";
import {appInjector} from "../../shared/utils/app-injector";
import {SERVICE_URL, EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {EmitterService} from "../../shared/services/emitter.service";
import {CampaignDataModel} from "../models/campaigndatamodel";

export const HasCampaignsData = (next:ComponentInstruction, prev:ComponentInstruction) => {
    let injector:Injector = appInjector(); // get the stored reference to the injector
    let httpInjector = ReflectiveInjector.resolveAndCreate([HTTP_PROVIDERS]);
    let campaignDataModel : CampaignDataModel = injector.get(CampaignDataModel);
    let http = httpInjector.get(Http);

    return new Promise((resolve) => {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
            http.post(SERVICE_URL.CAMPAIGN.GET_CAMPAIGNS_LIST)
                .map((res) => res.json())
                .subscribe((response) => {
                    campaignDataModel.responseData = response;
                    EmitterService.get(EVENTEMITTERS.LOADER).emit(false);
                    resolve(true);
                }, (error) => {
                    resolve(false);
                });
    });
};