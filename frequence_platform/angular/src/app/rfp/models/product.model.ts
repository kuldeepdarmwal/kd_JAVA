interface ProductDefinition {
	// TODO: add all properties
    first_name: string
    last_name: string
	product_enabled_img: string
	product_disabled_img: string
	after_discount: boolean
    cpm_editable? : boolean
    cpm_periods? : any
    term? : [any]
    discount_percent? : number
    options? : [any]
    inventory?: any
    raw_inventory?: any
    content?: any
    allocation_method?: any
	geofencing?: any
}

interface ProductOption {
	cpm?: number
	type?: string
	unit?: number
	inventory?: any
    raw_inventory?: any
	price?: any
	content?: any
	discount_name?: any
    discount_percent? : number
	raw_discount_name?: any
    geofence_cpm?: any
	total(arg?:any)
}

export interface ProductModel {
	id: number
    selected: boolean
    disabled: boolean
    friendly_name: string
    discountName? : string
    definition: ProductDefinition
    options: ProductOption[]
    display_order: number
    product_type: string
    budget_allocation?: string
    inventory?: any
    raw_inventory?: any
    content?: any
    is_geo_dependent?: any
    is_rooftops_dependent?: any
    is_zones_dependent?: any
    has_geofencing?: any
    selectable?: any
}

export interface DiscountModel{
    discount : number;
    name : string;
}
