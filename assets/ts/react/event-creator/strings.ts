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
        delete: string,

        flags: LangStrings,
        langs: LangStrings,
    },

    list: {
        no_events: string,
        default_event: string,
        edit_icon: string,

        delete_icon: string,
        delete_confirm: string,
    }

    editor: {
        edit: string,
        add_meta: string,

        field_title: string,
        field_description: string
    }
}