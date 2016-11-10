import {Injectable} from "@angular/core";
import {RFPDataModel} from "../models/rfpdatamodel";
import {SERVICE_URL, PLACEHOLDERS, EVENTEMITTERS} from "../../shared/constants/builder.constants";
import {EmitterService} from "../../shared/services/emitter.service";

declare var jQuery: any;

@Injectable()
export class RFPSelect2PropertiesBuilder {

    constructor(private rfpDataModel: RFPDataModel) {
    }

    //Properties For Account Executive
    get select2PropertiesForAdvertiser(): {} {
        return {
            url: SERVICE_URL.RFP.ACCOUNT_EXECUTIVES,
            placeHolder: PLACEHOLDERS.ACCOUNT_EXECUTIVES,
            resultFormatFn: (obj): {} => {
                return '<small class="grey-text">' + obj.email + '</small>' +
                    '<br>' + obj.text;
            },
            emitter: EmitterService.get(EVENTEMITTERS.ACCOUNT_EXECUTIVES),
            dataFn: (term, page): {} => {
                term = (typeof term === "undefined" || term == "") ? "%" : term;
                return {
                    q: term,
                    page_limit: 50,
                    page: page
                };
            },
            allowClear: false,
            allowMultiple: false,
            minLength: 0
        };
    }

    //Properties For Advertiser Industry
    get select2PropertiesForAdvertiserIndustry(): {} {
        return {
            url: SERVICE_URL.RFP.ADVERTISER_INDUSTRY,
            placeHolder: PLACEHOLDERS.ADVERTISER_INDUSTRY,
            resultFormatFn: (obj): {} => {
                return obj.text;
            },
            emitter: EmitterService.get(EVENTEMITTERS.ADVERTISER_INDUSTRY),
            dataFn: (term, page): {} => {
                term = (typeof term === "undefined" || term == "") ? "%" : term;
                return {
                    q: term,
                    page_limit: 50,
                    page: page,
                };
            },
            allowClear: true,
            allowMultiple: false,
            minLength: 0,
            fetchFn: (params): {} => {
                params.data.strategy_id = this.rfpDataModel.strategyId;
                return jQuery.ajax(params);
            }
        };
    }

    //Properties For Geographies
    get select2PropertiesForGeographies(): {} {
        return {
            url: SERVICE_URL.RFP.GEOGRAPHIES.GET_CUSTOM_REGIONS,
            placeHolder: PLACEHOLDERS.GEOGRAPHIES,
            resultFormatFn: (obj): {} => {
                return obj.text;
            },
            emitter: EmitterService.get(EVENTEMITTERS.GEOGRAPHIES),
            dataFn: (term, page): {} => {
                term = (typeof term === "undefined" || term == "") ? "%" : term;
                return {
                    q: term,
                    page_limit: 50,
                    page: page
                }
            },
            allowClear: true,
            allowMultiple: true,
            minLength: 4,
            delay: 250,
            resultFn: (data): {} => {
                return {results: data.result, more: data.more};
            }
        };
    }

    //Building Properties for TV Zones
    get select2PropertiesForTvZones(): {} {
        return {
            url: SERVICE_URL.RFP.TV_ZONES,
            placeHolder: PLACEHOLDERS.TV_ZONES,
            resultFormatFn: (obj): {} => {
                return obj.text;
            },
            emitter: EmitterService.get(EVENTEMITTERS.TV_ZONES),
            dataFn: (term, page): {} => {
                term = (typeof term === "undefined" || term == "") ? "%" : term;
                return {
                    q: term,
                    page_limit: 50,
                    page: page
                };
            },
            allowClear: true,
            allowMultiple: true,
            minLength: 3,
            fetchFn: (params): {} => {
                params.data.mpq_id = this.rfpDataModel.mpqId;
                return jQuery.ajax(params);
            }
        };
    }

    //Building Properties for Interests Engine
    get select2PropertiesForInterests(): {} {
        return {
            url: SERVICE_URL.RFP.AUDIENCE_INTERESTS,
            placeHolder: PLACEHOLDERS.AUDIENCE_INTERESTS,
            resultFormatFn: (obj) => {
                return obj.text;
            },
            emitter: EmitterService.get(EVENTEMITTERS.AUDIENCE_INTERESTS),
            dataFn: (term, page): {} => {
                term = (typeof term === "undefined" || term == "") ? "%" : term;
                return {
                    q: term,
                    page_limit: 50,
                    page: page
                };
            },
            allowClear: true,
            allowMultiple: true,
            minLength: 0,
            resultFn: (data): {} => {
                return {results: data.result, more: data.more};
            }
        };
    }

}