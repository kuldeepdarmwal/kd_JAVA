import {Injectable} from "@angular/core";
import {IODataModel} from "../models/iodatamodel";
import {SERVICE_URL, PLACEHOLDERS, EVENTEMITTERS, CONSTANTS} from "../../shared/constants/builder.constants";
import {EmitterService} from "../../shared/services/emitter.service";

@Injectable()
export class IOPropertiesBuilder {

    constructor(private ioDataModel:IODataModel) {}

    //Building Properties Object for Opportunity Owner
    _buildPropertiesForOppOwner = ():{} => {
        return {
            url: SERVICE_URL.RFP.ACCOUNT_EXECUTIVES,
            placeHolder: PLACEHOLDERS.ACCOUNT_EXECUTIVES,
            resultFormatFn: this._formatResultsOppOwnerFn,
            emitter: EmitterService.get(EVENTEMITTERS.IO.OPPORTUNITY_OWNER),
            dataFn: this._dataOppOwnerFn,
            allowClear: false,
            allowMultiple: false,
            minLength: 0
        };
    }

    private _formatResultsOppOwnerFn = (obj):{} => {
        return '<small class="grey-text">' + obj.email + '</small>' +
            '<br>' + obj.text;
    }

    private _dataOppOwnerFn = (term, page):{} => {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page
        };
    }

    //Building Properties Object for Advertiser
    _buildPropertiesForAdvertiser = ():{} => {
        return {
            url: SERVICE_URL.IO.ADVERTISERS,
            placeHolder: PLACEHOLDERS.IO.ADVERTISERS,
            resultFormatFn: this._formatResultsAdvFn,
            emitter: EmitterService.get(EVENTEMITTERS.IO.ADVERTISERS),
            dataFn: this._dataAdvFn,
            allowClear: false,
            allowMultiple: false,
            minLength: 0,
            formatSelectionFn: this._formatAdvSelection
        };
    }

    private _formatResultsAdvFn = (data):{} => {
        if (data.text === CONSTANTS.IO.NEW) {
            return data.text;
        }
        else {
            var verified_html = '';
            if (data.status === CONSTANTS.IO.VERIFIED) {
                verified_html = '<br/><i class="material-icons io_icon_done" style="position:relative;top:7px;left:10px;margin-right:10px;">&#xE8E8;</i><small class="grey-text">verified advertiser</small>';
            }
            var external_id_text = data.externalId ? ' <small class="grey-text">EXTID: ' + data.externalId + '</small>' : '';
            var third_party_ids = '';
            if (data.ul_id !== '' || data.eclipse_id !== '') {
                third_party_ids += '<small class="grey-text">';
                if (data.ul_id !== '') {
                    third_party_ids += 'ulid : ' + data.ul_id;
                }
                if (data.eclipse_id !== '') {
                    if (data.ul_id !== '') {
                        third_party_ids += ' | ';
                    }
                    third_party_ids += 'eclipseid : ' + data.eclipse_id;
                }
                third_party_ids += '</span><br/>';
            }

            return '<small class="grey-text">' + data.user_name + '&nbsp;&nbsp;[' + data.email + ']' + '</small><br/>' + third_party_ids + data.text + verified_html + external_id_text;
        }
    }

    private _dataAdvFn = (term, page):{} => {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page,
        };
    }

    private _formatAdvSelection = (data):{} => {
        if (data.status === CONSTANTS.IO.VERIFIED) {
            var external_id_text = data.externalId ? ' <small class="grey-text">EXTID: ' + data.externalId + '</small>' : '';
            return data.text + '&nbsp;<i class="material-icons io_icon_done" style="position:relative;top:7px;left:10px;margin-right:10px;">&#xE8E8;</i> ' + external_id_text;
        } else {
            return data.text;
        }
    }

    //Building Properties Object for Advertiser Industry
    _buildPropertiesForAdvertiserIndustry = ():{} => {
        return {
            url: SERVICE_URL.RFP.ADVERTISER_INDUSTRY,
            placeHolder: PLACEHOLDERS.ADVERTISER_INDUSTRY,
            resultFormatFn: this._formatResultsAdvIndFn,
            emitter: EmitterService.get(EVENTEMITTERS.IO.ADVERTISER_INDUSTRY),
            dataFn: this._dataAdvIndFn,
            allowClear: true,
            allowMultiple: false,
            minLength: 0
        };
    }

    private _formatResultsAdvIndFn = (obj):{} => {
        return obj.text;
    }

    private _dataAdvIndFn = (term, page):{} => {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 50,
            page: page,
        };
    }

    //Building Properties Object for Tracking Tag
    _buildPropertiesForTrackingTag = ():{} => {
        return {
            url: SERVICE_URL.IO.TRACKING_TAG_FILE_NAMES,
            placeHolder: PLACEHOLDERS.IO.TRACKING_TAG,
            resultFormatFn: this._formatResultsTrackingTagFn,
            emitter: EmitterService.get(EVENTEMITTERS.IO.TRACKING_TAG),
            dataFn: this._dataTrackingTagFn,
            allowClear: false,
            allowMultiple: false,
            minLength: 0
        };
    }

    private _formatResultsTrackingTagFn = (data):{} => {
        if (data.text.indexOf("/") != -1) {
            var directory_name = data.text.substring(0, data.text.indexOf("/") + 1);
            var file_name = data.text.substring(data.text.indexOf("/") + 1, (data.text.length - 3));

            return '<span style="font-size:12px;font-weight: 400;">/' + directory_name + '</span>'
                + '<span style="font-weight:700;">' + file_name + '</span>'
                + '<span style="font-size:12px;font-weight: 400;">.js</span>';
        }
        else {
            return data.text;
        }
    }

    private _dataTrackingTagFn = (term, page):{} => {
        term = (typeof term === "undefined" || term == "") ? "%" : term;
        return {
            q: term,
            page_limit: 100,
            page: page,
            source_table: this.ioDataModel.opportunity.advertiser.sourceTable,
            advertiser_id: this.ioDataModel.opportunity.advertiser.advertiserId
        };
    }

	//Building propoerties object for DFP Advertisers
	_buildPropertiesForDFPAdvertiser = ():{} => {
		return {
		    url: SERVICE_URL.IO.DFP_ADVERTISERS,
		    placeHolder: PLACEHOLDERS.IO.DFP_ADVERTISERS,
		    resultFormatFn: this._formatResultsDfpAdvFn,
		    emitter: EmitterService.get(EVENTEMITTERS.IO.DFP_ADVERTISERS),
		    dataFn: this._dataDfpAdvFn,
		    allowClear: false,
		    allowMultiple: false,
		    minLength: 0
		};
	}

	private _formatResultsDfpAdvFn = (data):{} => {
		return data.text;
	}

	private _dataDfpAdvFn = (term, page):{} => {
		term = (typeof term === "undefined" || term == "") ? "%" : term;
		return {
		    q: term,
		    page_limit: 50,
		    page: page,
		};
	}
}
