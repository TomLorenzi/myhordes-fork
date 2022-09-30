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
        well_presets: {value: string, label: string}[]
    }
}