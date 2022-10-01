export type TranslationStrings = {
    common: {
        need_selection: string,
    }

    head: {
        section: string,

        town_name: string,
        town_name_hint: string

        lang: string,
        langs: {
            code: string,
            label: string,
        }[],

        'citizens': string,
        'citizens_help': string,

        'seed': string,
        'seed_help': string,

        'type': string,
        'base': string,

        settings: {
            section: string,

            disable_api: string,
            disable_api_help: string,

            alias: string,
            alias_help: string,

            ffa: string,
            ffa_help: string,
        }
    },

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

        position: string,
        position_presets: {value: string, label: string}[],

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
        }
    }
}