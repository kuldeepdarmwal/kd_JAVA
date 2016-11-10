import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'flights_summary', pure: false})
export class FlightsSummary implements PipeTransform {

    transform(input:Array<any>, property?:any): any{
        if(!Array.isArray(input)) return input;

        switch(property){
        	case 'budget':
        		return this.totalBudget(input);
        	case 'start':
        		return this.startDate(input);
        	case 'end':
        		return this.endDate(input);
        }
    }

    totalBudget(flights){
        return flights.reduce((total, timeseries) => {
            return total + (Array.isArray(timeseries) ?  
                timeseries.reduce((total, flight) => {
                    return total + parseFloat(flight.totalBudget);
                }, 0) :
                parseFloat(timeseries.totalBudget));
        }, 0);
    }

    startDate(flights){
        return flights.reduce((earliest, timeseries) => {
            let start = Array.isArray(timeseries) ?  
                timeseries.reduce((earliest, flight) => {
                    return earliest === 0 ? flight.startDate : (Date.parse(earliest) < Date.parse(flight.startDate) ? earliest : flight.startDate);
                }, 0) :
                timeseries.startDate;
            return earliest === 0 ? start : (Date.parse(earliest) < Date.parse(start) ? earliest : start);
        }, 0);
    }

    endDate(flights){
        return flights.reduce((latest, timeseries) => {
            let end = Array.isArray(timeseries) ?  
                timeseries.reduce((latest, flight) => {
                    return latest === 0 ? flight.endDate : (Date.parse(latest) > Date.parse(flight.endDate) ? latest : flight.endDate);
                }, 0) :
                timeseries.endDate;
            return latest === 0 ? end : (Date.parse(latest) > Date.parse(end) ? latest : end);
        }, 0);
    }

}