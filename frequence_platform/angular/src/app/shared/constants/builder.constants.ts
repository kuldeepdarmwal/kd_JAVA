export const SERVICE_URL = {
    RFP: {
        GET_PROPOSAL: "/rfp/get_proposal_data/",
        GET_USER_PERMISSIONS: "/rfp/get_user_permissions",
        GET_CURRENT_USER: "/rfp/get_current_user_data/",
        UPDATE_GATE: "/rfp/createUpdateRFP/",
        ACCOUNT_EXECUTIVES: "/mpq_v2/get_account_executives_for_rfp/",
        ADVERTISER_INDUSTRY: "/mpq_v2/get_industries/",
        FILTERED_STRATEGY: "/mpq_v2/get_filtered_strategy_info",
        CREATE_RFP: "/rfp/create/",
        TV_ZONES: "/mpq_v2/get_tv_zones/",
        UPLOAD_TV_ZONE: "/rfp/process_scx_upload",
        PRICES_BY_TV_ZONE: "/mpq_v2/get_prices_by_zones_and_packs_for_tv_request",
        GEOGRAPHIES: {
            GET_CUSTOM_REGIONS: "/mpq_v2/get_custom_regions",
            GET_ZIPS_FROM_CUSTOM_REGIONS: "mpq_v2/get_zips_from_selected_regions_and_save",
            SAVE_ZIPS: "mpq_v2/ajax_save_zip_codes",
            REMOVE_CUSTOM_REGIONS: "mpq_v2/remove_selected_custom_regions",
            RADIUS_SEARCH: "mpq_v2/ajax_save_geo_radius_search/",
            ADD_LOCATION: "mpq/initialize_new_location",
            REMOVE_LOCATION: "mpq_v2/ajax_remove_location",
            BULK_UPLOAD: "maps/ajax_add_bulk_locations_to_session",
            RENAME_LOCATION: "mpq_v2/ajax_save_location_name",
            GEOFENCING: {
                SAVE_GEOFENCES: "/rfp/handle_geofencing_ajax",
                GET_RADIUS: "/rfp/get_center_geofence_type"
            }
        },
        AUDIENCE_INTERESTS: "/mpq_v2/get_contextual_iab_categories/",
        CREATIVES: "mpq_v2/get_select2_adset_versions_for_user_io",
        SUBMIT_TARGETING: "/proposals/builder_save_targeting",
        SUBMIT_BUDGET: "/proposals/builder_save_budget",
        SUBMIT_RFP: "/proposal_builder/create_mpq_proposal_v2",
        //Builder URL's
        GET_PAGE_TEMPLATES: "/proposals/page_templates/",
        GET_PROPOSAL_TEMPLATES: "/proposals/proposal_templates/",
        GET_PAGES: "/proposals/pages/",
        ADD_PAGE: "/proposals/add_page/",
        REMOVE_PAGE: "/proposals/remove_page/",
        GET_PROPOSAL_DATA: "/proposals/data/",
        SAVE_PROPOSAL_TEMPLATES: "/proposals/save_pages/",
        GET_GEO_SNAPSHOTS: "/proposals/get_geo_snapshots/",
        GET_PDF: "/proposals/generate_builder_pdf/"
    },
    IO: {
        GET_IO: "/insertion_orders/get_io_data/",
        ADVERTISERS: "mpq_v2/get_select2_advertisers/",
        CREATE_UNVERIFIED_ADVERTISER: "/mpq_v2/validate_and_create_unverified_advertiser/",
        DELETE_ALL_TIMESERIES_AND_CREATIVES: "/mpq_v2/io_delete_all_timeseries_and_creatives",
        CHANGE_PRODUCTS: "mpq_v2/ajax_change_io_product_set",
        DEFINE_CREATIVES: "/mpq_v2/io_define_creatives_for_product",
        GET_CREATIVES_INFO: "/mpq_v2/get_creatives_info",
        SAVE_ADSET: "/mpq_v2/io_save_adset_for_product_geo",
        FLIGHTS: {
            BUILD: "mpq_v2/save_time_series_for_product_region",
            ADD: "mpq_v2/add_flight",
            EDIT_BUDGET: "mpq_v2/update_existing_flight_budget",
            EDIT_OO_IMPRESSIONS: "mpq_v2/update_o_o_impressions_for_flight",
            EDIT_CPM: "mpq_v2/update_cpm",
            REMOVE: "mpq_v2/remove_flights",
            REMOVE_ALL: "mpq_v2/delete_all_time_series_for_product_region",
            POLL: "mpq_v2/poll_for_budget_values",
            REFORECAST: "mpq_v2/reforecast_flights"
        },
        TRACKING_TAG_FILE_NAMES: "/tag/get_select2_tracking_tag_file_names/",
        ADVERTISER_DIRECTORY_NAME: "/tag/get_advertiser_directory_name/",
        CREATE_TRACKING_TAG: "/tag/create_new_tracking_tag_file",
        SAVE_IO: "/proposal_builder/save_io",
        UNLOCK_SESSION: "mpq_v2/unlock_mpq_session",
        PRELOAD_FOR_CREATIVE_REQUEST: "mpq_v2/preload_io",
        DFP_ADVERTISERS: "mpq_v2/get_dfp_advertisers/",
        CREATE_DFP_ADVERTISERS: "mpq_v2/create_dfp_advertiser/",
        SHOW_DFP_ORDER_SUMMARY: "mpq_v2/get_order_summary/",
        GET_DFP_TEMPLATES: "mpq_v2/retrieve_dfp_template_groups",
        SAVE_DFP_TEMPLATE: "mpq_v2/assign_template_to_flights",
        PROCESS_DFP_ADVERTISERS: "/mpq_v2/create_new_dfp_order",
        CHECK_O_AND_O_STATUS: "insertion_orders/check_o_and_o_forecast_status",
        SAVE_IO_O_O: "insertion_orders/save_io_o_o_ids"
    },
    CAMPAIGN: {
        GET_CAMPAIGNS_LIST: "/campaigns_main/ajax_get_campaigns_data",
        CHECK_USER_BULK_FLAG: "campaigns_main/ajax_check_user_bulk_pending_flag",
        GET_PARTNER: "campaigns_main/health_v2_angular",
        GET_BULK_DOWNLOAD_SALES_PEOPLE: "campaigns_main/ajax_get_bulk_download_sales_people_angular",
        GET_CAMPAIGN_CREATIVE_LIST: "campaigns_main/ajax_get_adsets_for_campaign",
        UPDATE_REMINDER_STATUS: "campaigns_main/update_reminder_flag",
        GET_BULK_DOWNLOAD_DATA: "campaigns_main/get_bulk_download",
        CREATE_HELPDESK_TICKET: "campaigns_main/ajax_create_ticket",
        GET_FLIGHTS_DETAILS: "proposals/get_flights_details"
    },
    CAMPAIGN_SETUP: {
        GET_CAMPAIGN_FLIGHTS: "/mpq_v2/retrieve_flights_for_campaign/",
        REMOVE_ALL: "mpq_v2/delete_all_time_series_for_campaign"
    },
    CREATIVE_REQUEST: "creative_requests/new"
}

export const PLACEHOLDERS = {
    ACCOUNT_EXECUTIVES: "Presented on Behalf of",
    ADVERTISER_INDUSTRY: "Advertiser Industry",
    TV_ZONES: "Start typing to find zones...",
    GEOGRAPHIES: "Start typing to find regions...",
    AUDIENCE_INTERESTS: "Select at least 3 audience interest channels",
    CREATIVES: "Select adsets",
    ROOF_TOPS: "Select your physical locations...",
    GEOFENCES: "Select the target location...",
    IO: {
        ADVERTISERS: "Select an advertiser",
        TRACKING_TAG: "Select Tracking Tag File",
        DFP_ADVERTISERS: "Select DFP advertiser",
        DFP_TEMPLATES: "Select a DFP Template"
    },
    CAMPAIGN: {
        PARTNERS: "Select a partner",
        ACCOUNT_EXECUTIVES: "Select an account executive"
    }
}

export const TOOLTIP = {
    CAMPAIGN: {
        GEOFENCE_HEADER: "Dynamic Impression Budget",
        GEOFENCE_BODY: "This campaign is set to dynamically allocate impressions to ensure dollar budget delivery as inventory fluctuates.",
        SCHEDULE: {
            BODY: '<div class="body"><p><b>LIVE: </b>When current flight has budget.</p><p><b>PAUSED: </b>When current flight is zero &amp; future flights are non zero.</p><p><b>COMPLETED: </b>When current flight is zero and no future flights</p><p><b>ENDING: </b>When this is the last flight and is ending in 7 days</p><p><b>LAUNCHING: </b>When first flight has yet to start</p></div>'
        },
        ALL: {
            START: {
                HEADER: "Start Date",
                BODY: "<p>The first impression date for the campaign.</p>"
            },
            END: {
                HEADER: "End Date",
                BODY: "<p>The scheduled final impression date for the campaign.</p>"
            },
            TOTAL: {
                REALIZED: {
                    HEADER: "Lifetime Realized Budget",
                    BODY: "<ul><li>The budget in dollars that has run for the entire campaign’s lifetime.</li><li>The number of impressions that have run for the entire campaign’s lifetime</li></ul>",
                },
                BUDGET: {
                    HEADER: "Lifetime Budget",
                    BODY: "<ul><li>The scheduled budget in dollars for the life of the campaign.</li><li>The scheduled number of impressions for the life of the campaign.</li></ul>",
                },
                OTI: {
                    HEADER: "Lifetime On Target Indicator",
                    BODY: "<p>If = 100%, the Lifetime Realized Budget is exactly on pace for its lifetime. If under/over 100%, the campaign is under/over its Lifetime Budget.</p>",
                }
            },
            AE: {
                REALIZED: {
                    HEADER: "Lifetime AX Realized Budget",
                    BODY: "<ul><li>The budget in dollars that has run for the Audience Extension component of the campaign.</li><li>The number of impressions that have run for the Audience Extension component of the campaign.</li></ul>",
                },
                BUDGET: {
                    HEADER: "Lifetime AX Budget",
                    BODY: "<ul><li>The lifetime scheduled budget in dollars for the Audience Extension component of the campaign.</li><li>The lifetime scheduled number of impressions for the Audience Extension component of the campaign.</li></ul>",
                },
                OTI: {
                    HEADER: "Lifetime AX On Target Indicator",
                    BODY: "<p>If = 100%, the Lifetime AX Realized Budget is exactly on pace for its lifetime. If under/over 100%, the Audience Extension component  of the campaign is under/over its Lifetime AX Budget.</p>",
                }
            },
            OO: {
                REALIZED: {
                    HEADER: "Lifetime O&O Realized Budget",
                    BODY: "<ul><li>The budget in dollars that has run for the O&O component of the campaign.</li><li>The number of impressions that have run for the O&O component of the campaign.</li></ul>",
                },
                BUDGET: {
                    HEADER: "Lifetime O&O Budget",
                    BODY: "<ul><li>The lifetime scheduled budget in dollars for the O&O component of the campaign.</li><li>The lifetime scheduled number of impressions for the O&O component of the campaign.</li></ul>",
                },
                OTI: {
                    HEADER: "Lifetime O&O On Target Indicator",
                    BODY: "<p>If = 100%, the Lifetime O&O Realized Budget is exactly on pace for its lifetime. If under/over 100%, the campaign is under/over its Lifetime O&O Budget.</p>",
                }
            }
        },
        FLIGHTS: {
            START: {
                HEADER: "Flight Start Date",
                BODY: "<p>The first impression date for the flight.</p>",
            },
            END: {
                HEADER: "Flight End Date",
                BODY: "<p>The scheduled final impression date for the flight.</p>",
            },
            TOTAL: {
                REALIZED: {
                    HEADER: "Flight Realized Budget",
                    BODY: "<ul><li>The budget in dollars that has run for the flight.</li><li>The number of impressions that have run for the flight.</li></ul>",
                },
                BUDGET: {
                    HEADER: "Flight Budget",
                    BODY: "<ul><li>The scheduled budget in dollars for the flight.</li><li>The scheduled number of impressions the flight.</li></ul>",
                },
                OTI: {
                    HEADER: "Flight On Target Indicator",
                    BODY: "<p>If = 100%, the Flight Realized Budget is exactly on pace for this flight. If under/over 100%, the flight is under/over its Flight Budget.</p>",
                }
            },
            AE: {
                REALIZED: {
                    HEADER: "Flight AX Realized Budget",
                    BODY: "<ul><li>The budget in dollars that has run for the Audience Extension component of the flight.</li><li>The number of impressions that have run for the Audience Extension component of the flight.</li></ul>",
                },
                BUDGET: {
                    HEADER: "Flight AX Budget",
                    BODY: "<ul><li>The scheduled budget in dollars for the Audience Extension component of the flight.</li><li>The scheduled budget in dollars for the Audience Extension component of the flight.</li></ul>",
                },
                OTI: {
                    HEADER: "Flight AX On Target Indicator",
                    BODY: "<p>If = 100%, the Flight AX Realized Budget is exactly on pace for its lifetime. If under/over 100%, the Audience Extension component of the flight is under/over its Flight AX Budget.</p>",
                }
            },
            OO: {
                REALIZED: {
                    HEADER: "Flight O&O Realized Budget",
                    BODY: "<ul><li>The budget in dollars that has run for the O&O component of the flight.</li><li>The number of impressions that have run for the O&O component of the flight.</li></ul>",
                },
                BUDGET: {
                    HEADER: "Flight O&O Budget",
                    BODY: "<ul><li>The scheduled budget in dollars for the O&O component of the flight.</li><li>The scheduled number of impressions for the O&O component of the flight.</li></ul>",
                },
                OTI: {
                    HEADER: "Flight O&O On Target Indicator",
                    BODY: "<p>If = 100%, the Flight O&O Realized Budget is exactly on pace for its lifetime. If under/over 100%, the O&O component of the flight is under/over its Flight O&O Budget.</p>",
                }
            }
        }
    }
}

export const EVENTEMITTERS = {
    LOADER: "loader",
    NOTIFICATION: "notification",
    ACCOUNT_EXECUTIVES: "opportunity-owner",
    ADVERTISER_INDUSTRY: "advertiser-industry",
    FILTER_STRATEGY: "filter-strategy",
    TV_ZONES: "tv-zones",
    GEOGRAPHIES: "geographies",
    AUDIENCE_INTERESTS: "audience-interests",
    CREATIVES: "creatives",
    ROOFTOPS: "rooftops",
    GEOFENCES: "geofences",
    DURATION_CHANGED: "duration-changed",
    SLIDER: "builder-slider",
    SAVE_PROPOSAL: "save-proposal",
    AUTO_SAVE: "auto-save",
    IO: {
        OPPORTUNITY_OWNER: "opportunity-owner",
        ADVERTISER_INDUSTRY: "advertiser-industry",
        ADVERTISERS: "advertisers",
        TRACKING_TAG: "tracking-tag",
        DFP_ADVERTISERS: "dfp-advertisers",
        DFP_TEMPLATES: "dfp-templates"
    },
    CAMPAIGN: {
        PARTNER: "partner_select",
        ACCOUNT_EXECUTIVES: "account_executive_select"
    }
}

export const EXTENSIONS = {
    JS: "js",
    CSS: "css"
}

export const CONSTANTS = {
    OPTION_DEFAULT_NAME: "Budget",
    OPTION_DEFAULT_TERM: "monthly",
    OPTION_DEFAULT_DURATION: 6,
    GEOFENCING_RADII: {
        URBAN: 200,
        SUBURBAN: 350,
        RURAL: 800,
        CONQUESTING: 200
    },
    MAX_TOP_NETWORKS: 10,
    IO: {
        NEW_TRACKING_TAG_FILE: "new_tracking_tag_file",
        UNVERIFIED_ADVERTISER_NAME: "unverified_advertiser_name",
        ADVERTISERS: "Advertisers",
        UNVERIFIED_ADVERTISERS: "advertisers_unverified",
        NEW: "*New*",
        VERIFIED: "verified",
        DONE: "done",
        NOT_STARTED: "not_started",
        SAVE: "save",
        SUBMIT: "submit",
        SUBMIT_FOR_REVIEW: "submit_for_review",
        NEW_DFP_ADVERTISER: "new_dfp_advertiser"
    },
    DURATION_MULTIPLIER: {
        DAILY: 30,
        WEEKLY: 4,
        MONTHLY: 1
    },
    PRODUCTS_TO_CHECK_IMPRESSIONS_POPULATION_RATIO: [
        "display",
        "preroll"
    ],
    GEOS: {
        WARN_ZIPS_PER_LOCATION: 1000,
        MAX_ZIPS_PER_LOCATION: 2000,
        WARN_RADIUS: 100,
        MAX_RADIUS: 300
    }
}

export const NAVIGATION = {
    GATE: "navigate-gate",
    TARGETS: "navigate-targets",
    BUDGET: "navigate-budget",
    BUILDER: "navigate-builder",
    PROPOSALS: "navigate-proposals",
    SUCCESS: "navigate-success"
}

export const PRODUCT_TYPE = {
    COST_UNIT: "cost_per_unit",
    INVENTORY_UNIT: "cost_per_inventory_unit",
    STATIC_UNIT: "cost_per_static_unit",
    SEM_UNIT: "cost_per_sem_unit",
    TV_UNIT: "cost_per_tv_package",
    TV_UPLOAD: "tv_scx_upload"
}

export const STORE = {
    PRODUCT_SELECTION: "UPDATE_PRODUCT_SELECTION",
    VALIDATION_STATUS: "VALIDATION_STATUS_UPDATE"
}

export const STORE_NAMES = {
    CONFIGURATION: "ConfigurationStore",
    VALIDATION: "ValidationStatusStore"
}

export const ERRORS = {
    ROOFTOPS: "Please add at least 1 rooftop location.",
    TV_ZONES: "Please add at least 1 TV Zone.",
    INTERESTS: "Please add at least 3 Audience Interests.",
    TV_SCX_UPLOAD: "Please upload your SCX file.",
    GEOS: {
        SINGLE: "Please add at least 1 Location.",
        MULTI: "Please fill out all geographies completely.",
        IMPRESSIONS_POPULATION_RATIO: "Consider expanding your geographies for the following locations: "
    },
    SEM: {
        KEYWORDS: "Please enter your keywords",
        EMPTY_CLICKS: "Please fill out Click Inventory",
        CLICKS: "Your Click Inventory must be greater than zero",
        WEBSITE: "Please enter a valid URL for your Website"
    },
    BUDGET: {
        SEM: {
            UNITS_MISMATCH: "You must either set an amount for both SEM clicks and Budget or set both to zero",
            BUDGETS_ALL_ZERO: "You must have at least one budget with SEM clicks greater than 0",
            LARGER_THAN_INVENTORY: "Your clicks per month must be fewer than the click inventory specified above"
        }
    },
    FORECAST: "Forecast is not done yet"
}

export const IO_VALIDATION = [
    {
        id: "opportunity",
        title: "Opportunity",
        link: "mpq_advertiser_info_section"
    },
    {
        id: "geo",
        title: "Geo",
        link: "mpq_geography_info_section"
    },
    {
        id: "audience",
        title: "Audience",
        link: "mpq_audience_info_section"
    },
    {
        id: "tracking",
        title: "Tracking",
        link: "mpq_tracking_info_section"
    },
    {
        id: "flights",
        title: "Flights",
        link: "mpq_flight_info_section"
    },
    {

        id: "forecast",
        title: "Forecast",
        link: "mpq_flight_info_section"
    },
    {
        id: "creative",
        title: "Creative",
        link: "mpq_creative_info_section"
    },
    {
        id: "notes",
        title: "Notes",
        link: "mpq_notes_info_section"
    },
    {
        id: "product",
        title: "Product",
        link: null,
        ignore: true
    }
];

export const TOAST_OPTIONS = {
    autoDismiss: false,
    maxShown: 10
}

export const DROPDOWN_OPTIONS = {
    TERM: [{name: "Months", value: "monthly"}, {name: "Weeks", value: "weekly"}, {name: "Days", value: "daily"}],
    DURATION: [{name: "1", value: 1}, {name: "2", value: 2}, {name: "3", value: 3}, {name: "4", value: 4}, {
        name: "5",
        value: 5
    }, {name: "6", value: 6},
        {name: "7", value: 7}, {name: "8", value: 8}, {name: "9", value: 9}, {name: "10", value: 10}, {
            name: "11",
            value: 11
        }, {name: "12", value: 12}],
    BUDGET_ALLOCATION: [{
        name: "Distribute budget by location pop.",
        value: 'per_pop'
    }, {name: "Distribute budget evenly by location", value: 'even'}, {name: "Custom location budget", value: "custom"}]
}

export const USER_DATA = {
    ROLE_SALES_UPPER: 'SALES',
    ROLE_SALES_LOWER: 'sales'
}

export const BUILDER_PERMISSIONS = {
    DRAG_SLIDE: "builder_drag_slide",
    REMOVE_SLIDE: "builder_remove_slide",
    ADD_SLIDE: "builder_add_slide"
}