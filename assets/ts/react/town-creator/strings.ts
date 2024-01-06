export type BuildingListEntry = {
    icon: string
    label: string
    name: string
    id: number
    parent: number|null
    unlockable: boolean
}

export type TranslationStrings = {
    common: {
        help: string
        need_selection: string

        create: string
        confirm: string

        notice: string
        negate: string

		incorrect_fields: string

        delete_icon: string
    }

    head: {
        section: string

        town_name: string
        town_name_help: string

        lang: string
        name_lang: string
        langs: {
            code: string
            label: string
        }[],

        code: string
        code_help: string

        'citizens': string
        'citizens_help': string

        'seed': string
        'seed_help': string

        'type': string
        'base': string

        reserve: string
        reserve_none: string
        reserve_num: string
        reserve_add: string
        reserve_help: string

        schedule: string
        schedule_help: string

        participation: string,
        participation_presets: {value: string, label: string, help: string}[]

        management: {
            section: string

            event_tag: string
            event_tag_help: string
        }
    }

    template: {
        section: string
        description: string
        description_2: string

        select: string
        none: string

        save: string
        saveConfirm: string
        saveDone: string
        saveNameError: string

        update: string
        updateConfirm: string
        updateDone: string

        load: string
        loadConfirm: string
        loadDone: string

        delete: string
        deleteConfirm: string
        deleteDone: string
    }

    difficulty: {
        section: string,

        well: string,
        well_help: string,
        well_presets: {value: string, label: string}[],

        map: string,
        map_presets: {value: string, label: string}[],
        map_exact: string,
        map_ruins: string,
        map_e_ruins: string,

        explorable: string,
        explorable_presets: {value: string, label: string}[],
        explorable_floors: string,
        explorable_rooms: string,
        explorable_min_rooms: string,

        position: string,
        position_presets: {value: string, label: string}[],

        position_north: string,
        position_south: string,
        position_west: string,
        position_east: string,

        attacks: string,
        attacks_presets: {value: string, label: string}[],
    }

    mods: {
        section: string,

        ghouls: string,
        ghouls_presets: {value: string, label: string, help: string}[]

        shamans: string,
        shamans_presets: {value: string, label: string, help: string}[]
        shaman_buildings: {
            job: string[]
            normal: string[]
        }

        watch: string,
        watch_presets: {value: string, label: string, help: string}[]
        watch_buildings: string[]

        nightmode: string,
        nightmode_presets: {value: string, label: string, help: string}[]
        nightmode_buildings: string[]

        timezone: string,
        timezone_presets: {value: string, label: string, help: string}[]

        modules: {

            section: string,

            e_ruins: string,
            e_ruins_help: string,

            escorts: string,
            escorts_help: string,

            shun: string,
            shun_help: string,

            camp: string,
            camp_help: string,

            buildingdamages: string,
            buildingdamages_help: string,

            improveddump: string,
            improveddump_help: string,
            improveddump_buildings: string[],

            alias: string
            alias_help: string

            api: string
            api_help: string

            ffa: string
            ffa_help: string

            fft: string
            fft_help: string
        }

        special: {
            section: string

            nobuilding: string,
            nobuilding_help: string,

            poison: string,
            poison_help: string,

            beta: string,
            beta_help: string,
            beta_items: string[],

            'with-toxin': string,
            'with-toxin_help': string,

            'hungry-ghouls': string,
            'hungry-ghouls_help': string,

            super_poison: string
            super_poison_help: string

            strange_soil: string
            strange_soil_help: string

            redig: string
            redig_help: string

            carry_bag: string
            carry_bag_help: string
        }
    }

    animation: {
        section: string

        pictos: string
        pictos_presets: {value: string, label: string, help: string}[]

        picto_rules: string
        picto_rules_presets: {value: string, label: string, help: string}[]

        sp: string
        sp_presets: {value: string, label: string, help: string}[]

        management: {
            section: string

            negate: string
            negate_help: string

            lock_door: string
            lock_door_help: string
        }
    }

    advanced: {
        section: string
        show_section: string

        jobs: string
        jobs_help: string
        job_list: {
            icon: string
            label: string
            name: string
        }[]

        buildings: string
        buildings_help: string
        buildings_list: BuildingListEntry[]
        building_props: string[]

        events: string
        events_help: string

        event_management: string
        event_list: {
            id: string
            label: string
            desc: string
        }[]
        event_auto: string
        event_auto_help: string
        event_none: string
        event_none_help: string
        event_any_help: string
    }
}