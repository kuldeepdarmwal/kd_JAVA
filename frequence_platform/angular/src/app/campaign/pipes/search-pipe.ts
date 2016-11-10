import {Pipe} from '@angular/core';

declare var jQuery: any;

@Pipe({
  name: 'find'
})

export class SearchPipe {
  transform(pipeData, [pipeModifier,mode]) {    
    if(pipeModifier) {
        let searchBy = pipeModifier.toLowerCase().toString();
        let searchByNumeric = searchBy;
        searchByNumeric = searchByNumeric.replace('$', '');
        searchByNumeric = searchByNumeric.replace(',', '');
        searchByNumeric = searchByNumeric.replace('%', '');
        if(mode){
            return pipeData.filter((eachItem) => {            
                if(eachItem['allTimeAudienceExtension']) {
                    let data = eachItem['allTimeAudienceExtension'];
                    if(data['budget'].toString().includes(searchByNumeric) ||
                      data['oti'].toString().includes(searchByNumeric) ||
                      data['realizedImpression'].toString().includes(searchByNumeric) ||
                      data['budgetImpression'].toString().includes(searchByNumeric) ||
                      (data['oti'] >= 100 && '>100%'.toString().includes(searchBy)) || 
                      data['realized'].toString().includes(searchByNumeric)){
                        return true;
                      }
                }

                if(eachItem['allTimeCampaign']) {
                    let data = eachItem['allTimeCampaign'];
                    if(data['budget'].toString().includes(searchByNumeric) ||
                      data['oti'].toString().includes(searchByNumeric) ||
                      data['realizedImpression'].toString().includes(searchByNumeric) ||
                      data['budgetImpression'].toString().includes(searchByNumeric) ||
                      (data['oti'] >= 100 && '>100%'.toString().includes(searchBy)) || 
                      data['realized'].toString().includes(searchByNumeric)){
                        return true;
                      }
                }

                if(eachItem['allTimeOAndO']) {
                    let data = eachItem['allTimeOAndO'];
                    if(data['budget'].toString().includes(searchByNumeric) ||
                      data['oti'].toString().includes(searchByNumeric) ||
                      data['realizedImpression'].toString().includes(searchByNumeric) ||
                      data['budgetImpression'].toString().includes(searchByNumeric) ||
                      (data['oti'] >= 100 && '>100%'.toString().includes(searchBy)) || 
                      data['realized'].toString().includes(searchByNumeric)){
                        return true;
                      }
                }
                return eachItem['name'].toLowerCase().toString().includes(searchBy) ||
                    (eachItem['partner'] && eachItem['partner'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['advertiser'] && eachItem['advertiser'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['isGeofencingFlag'] && eachItem['isGeofencingFlag'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['orderId'] && eachItem['orderId'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['allTimeStart'] && eachItem['allTimeStart'].toString().includes(searchBy)) ||
                    (eachItem['allTimeEnd'] && eachItem['allTimeEnd'].toString().includes(searchBy)) ||
                    (eachItem['scheduleStatus'].toLowerCase().toString().includes(searchBy));
            });        
        }
        else {
            return pipeData.filter((eachItem) => {
            
                if(eachItem['thisFlightCampaign']) {
                    let data = eachItem['thisFlightCampaign'];
                    if(data['budget'].toString().includes(searchByNumeric) ||
                      data['oti'].toString().includes(searchByNumeric) ||
                      data['realizedImpression'].toString().includes(searchByNumeric) ||
                      data['budgetImpression'].toString().includes(searchByNumeric) ||
                      (data['oti'] >= 100 && '>100%'.toString().includes(searchBy)) || 
                      data['realized'].toString().includes(searchByNumeric)){
                        return true;
                      }
                }

                if(eachItem['thisFlightAudienceExtension']) {
                    let data = eachItem['thisFlightAudienceExtension'];
                    if(data['budget'].toString().includes(searchByNumeric) ||
                      data['oti'].toString().includes(searchByNumeric) ||
                      data['realizedImpression'].toString().includes(searchByNumeric) ||
                      data['budgetImpression'].toString().includes(searchByNumeric) ||
                      (data['oti'] >= 100 && '>100%'.toString().includes(searchBy)) ||  
                      data['realized'].toString().includes(searchByNumeric)){
                        return true;
                      }
                }

                if(eachItem['thisFlightOAndO']) {
                    let data = eachItem['thisFlightOAndO'];
                    if(data['budget'].toString().includes(searchByNumeric) ||
                      data['oti'].toString().includes(searchByNumeric) ||
                      data['realizedImpression'].toString().includes(searchByNumeric) ||
                      data['budgetImpression'].toString().includes(searchByNumeric) ||
                      (data['oti'] >= 100 && '>100%'.toString().includes(searchBy)) ||  
                      data['realized'].toString().includes(searchByNumeric)){
                        return true;
                      }
                }
                
                return eachItem['name'].toLowerCase().toString().includes(searchBy) ||
                    (eachItem['partner'] && eachItem['partner'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['advertiser'] && eachItem['advertiser'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['isGeofencingFlag'] && eachItem['isGeofencingFlag'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['orderId'] && eachItem['orderId'].toLowerCase().toString().includes(searchBy)) ||
                    (eachItem['thisFlightStart'] && eachItem['thisFlightStart'].toString().includes(searchBy)) ||
                    (eachItem['thisFlightEnd'] && eachItem['thisFlightEnd'].toString().includes(searchBy)) ||
                    (eachItem['scheduleStatus'].toLowerCase().toString().includes(searchBy));
            });
        }        
    } else {
        return pipeData;
    }
  }
}


