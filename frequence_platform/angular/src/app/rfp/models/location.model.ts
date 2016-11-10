export interface LocationModel {
	page: number
	ids: any
	custom_regions: Array<any>
	search_type: string
	total: number
	user_supplied_name: string
	selected: boolean
	address?: string
	counter?: string
	geofences?: Array<GeofenceModel>
	affected_regions?: Array<any>
}

export interface GeofenceModel {
	address: string
	latlng: Array<number>,
	type: string,
	proximity_radius?: number
	affected_zips: Array<any>
}