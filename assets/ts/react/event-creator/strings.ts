type LangStrings = {
    de: string,
    en: string,
    fr: string,
    es: string,
    multi: string,
}

export type TranslationStrings = {
    common: {
        create: string,
        cancel_create: string,

        save: string,
        cancel: string,
        edit: string,
        edit_icon: string,

        delete: string,
        delete_icon: string,

        flags: LangStrings,
        langs: LangStrings,
    },

    list: {
        no_events: string,
        default_event: string,

        delete_confirm: string,
    }

    towns: {
        title: string,

        no_towns: string,
        default_town: string,

        table_lang: string,
        table_town: string,
        table_act: string,

        add: string,
        delete_confirm: string

        town_create: string,
        town_edit: string,
    }

    editor: {
        title: string,
        edit: string,
        add_meta: string,

        field_title: string,
        field_description: string
    }
}