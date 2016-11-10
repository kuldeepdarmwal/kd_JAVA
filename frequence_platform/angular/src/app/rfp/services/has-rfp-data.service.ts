import {Injector, ReflectiveInjector} from "@angular/core";
import {HTTP_PROVIDERS, Http, Response} from "@angular/http";
import {ComponentInstruction} from "@angular/router-deprecated";
import {appInjector} from "../../shared/utils/app-injector";
import {SERVICE_URL, EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {RFPDataModel} from "../models/rfpdatamodel";
import {MapperService} from "./mapper.service";
import {BuilderDataModel} from "../models/builderDataModel";
import {StepsCompletionService} from "./stepscompletion.service";
import {EmitterService} from "../../shared/services/emitter.service";
import {IODataModel} from "../../io/models/iodatamodel";
import {PermissionsDataModel} from "../models/permissionsdatamodel";

export const HasRFPData = (next: ComponentInstruction, prev: ComponentInstruction) => {
    let injector: Injector = appInjector(); // get the stored reference to the injector
    let rfpDataModel: RFPDataModel = injector.get(RFPDataModel);
    let permissionsDataModel: PermissionsDataModel = injector.get(PermissionsDataModel);
    let mapperService: MapperService = injector.get(MapperService);
    let stepsCompletionService: StepsCompletionService = injector.get(StepsCompletionService);
    let httpInjector = ReflectiveInjector.resolveAndCreate([HTTP_PROVIDERS]);
    let http = httpInjector.get(Http);
    let uniqueDisplayId: string = next.params["uniqueDisplayId"];

    return new Promise((resolve) => {
        if (rfpDataModel.loaded) {
            resolve(true);
        } else {
            EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
            if (uniqueDisplayId) {
                rfpDataModel.uniqueDisplayId = uniqueDisplayId;
                rfpDataModel.isNew = false;
                http.get(SERVICE_URL.RFP.GET_PROPOSAL + uniqueDisplayId)
                    .map((response: Response) => {
                        rfpDataModel.data = response.json();
                    })
                    .concatMap(response => http.get(SERVICE_URL.RFP.GET_USER_PERMISSIONS))
                    .subscribe((response: Response) => {
                        permissionsDataModel.permissionsData = response.json();
                        resolve(true);
                    }, (error) => {
                        resolve(false);
                    });
            } else {//Comes in if it is a New RFP
                http.get(SERVICE_URL.RFP.GET_CURRENT_USER)
                    .map((response: Response) => {
                        rfpDataModel.isNew = true;
                        rfpDataModel.currentUserData = response.json().current_user;
                        rfpDataModel.strategies = mapperService.mapStrategyResponseToModel(response.json().strategies);
                    })
                    .concatMap(response => http.get(SERVICE_URL.RFP.GET_USER_PERMISSIONS))
                    .subscribe((response: Response) => {
                        permissionsDataModel.permissionsData = response.json();
                        resolve(true);
                    }, (error) => {
                        resolve(false);
                    });
            }
        }
    });
};

export const GetBuilderData = (next: ComponentInstruction, prev: ComponentInstruction) => {
    let injector: Injector = appInjector(); // get the stored reference to the injector
    let rfpDataModel: RFPDataModel = injector.get(RFPDataModel);
    let builderDataModel: BuilderDataModel = injector.get(BuilderDataModel);
    let permissionsDataModel: PermissionsDataModel = injector.get(PermissionsDataModel);
    let httpInjector = ReflectiveInjector.resolveAndCreate([HTTP_PROVIDERS]);
    let http = httpInjector.get(Http);
    let uniqueDisplayId = next.params["uniqueDisplayId"];
    rfpDataModel.uniqueDisplayId = uniqueDisplayId;

    return new Promise((resolve) => {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
            if (rfpDataModel.loaded) {
                http.get(SERVICE_URL.RFP.GET_PROPOSAL_TEMPLATES + uniqueDisplayId)
                    .subscribe((response: Response) => {
                        builderDataModel.builderData = response.json();
                        permissionsDataModel.permissionsData = response.json();
                        resolve(true);
                    }, (error) => {
                        resolve(false);
                    });
            } else {
                http.get(SERVICE_URL.RFP.GET_PROPOSAL + uniqueDisplayId)
                    .map((response: Response) => {
                        rfpDataModel.data = response.json();
                    })
                    .concatMap(response => http.get(SERVICE_URL.RFP.GET_PROPOSAL_TEMPLATES + uniqueDisplayId))
                    .subscribe((response: Response) => {
                        builderDataModel.builderData = response.json();
                        permissionsDataModel.permissionsData = response.json();
                        resolve(true);
                    }, (error) => {
                        resolve(false);
                    });
            }
        }
        );
};

export const HasIOData = (next: ComponentInstruction, prev: ComponentInstruction) => {
    let injector: Injector = appInjector(); // get the stored reference to the injector
    let httpInjector = ReflectiveInjector.resolveAndCreate([HTTP_PROVIDERS]);
    let ioDataModel: IODataModel = injector.get(IODataModel);
    let http = httpInjector.get(Http);
    let uniqueDisplayId: string = next.params["uniqueDisplayId"];

    return new Promise((resolve) => {
        EmitterService.get(EVENTEMITTERS.LOADER).emit(true);
        if (!uniqueDisplayId) {
            resolve(true);
        }
        else {
            http.get(SERVICE_URL.IO.GET_IO + uniqueDisplayId)
                .map((res) => res.json())
                .subscribe((response) => {
                    ioDataModel.data = response;
                    resolve(true);
                }, (error) => {
                    resolve(false);
                });
        }
    });
};
