export interface BuildFlightsConfigurationModel {
	startDate: any
	endDate: any
	flightType: 'BROADCAST_MONTHLY' | 'MONTHLY' | 'FIXED'
	pacingType: 'MONTHLY' | 'DAILY'
	totalBudget: number
	budgetAllocation: 'per_pop' | 'even' | 'custom'
	productId?: any
	regionId: any
}

export interface BuildFlightsRequestConfigurationModel {
	start_date: any
	end_date: any
	flight_type: 'BROADCAST_MONTHLY' | 'MONTHLY' | 'FIXED'
	pacing_type: 'MONTHLY' | 'DAILY'
	budget: number
	budget_allocation: 'per_pop' | 'even' | 'custom'
	mpq_id: any
	product_id: any
	region_id ?: any
}

export interface CPMsModel {
	audienceExtension: number
	geofencing ?: number
	ownedAndOperated ?: number
}

export interface FlightModel {
	id: any
	startDate: any
	endDate: any
	totalBudget : number
	audienceExtensionImpressions: number
	geofencingBudget ?: number
	geofencingImpressions ?: number
	ownedAndOperatedBudget ?: number
	ownedAndOperatedImpressions ?: number
	ownedAndOperatedForecastImpressions ?: number,
	forecast_status ?: string
	regionId ?: any
}