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
        init_verification: string,
        cancel_verification: string,
        do_verification: string,
        verification_pending: string,
        start_pending: string,
        mark_end: string,

        planned_string: string,
        start_string_singular: string,
        start_string_plural: string,
        start_string_running: string,
        end_string: string,

        save: string,
        cancel: string,
        edit: string,
        edit_icon: string,

        delete: string,
        delete_icon: string,

        online_icon: string,
        offline_icon: string,

        flags: LangStrings,
        langs: LangStrings,
    },

    messages: {
        verification_started: string,
        verification_cancelled: string,
        verification_confirmed: string,
    }

    list: {
        no_events: string,
        default_event: string,

        delete_confirm: string,

        more_info: string,
    }

    towns: {
        title: string,
        password: string,
        help1: string,
        help2: string,

        no_towns: string,
        default_town: string,

        table_lang: string,
        table_town: string,
        table_act: string,

        add: string,
        delete_confirm: string

        expedite: string,
        expedited: string,
        expedite_help: string,
        expedite_confirm: string

        town_create: string,
        town_edit: string,

        town_instance_online: string,
        town_instance_offline: string,

        citizens: string,
        alive: string,
        day: string,

        forum_link: string,
        ranking_link: string,
    }

    editor: {
        title: string,
        help: string,
        edit: string,
        add_meta: string,

        schedule: string,

        field_title: string,
        field_short: string,
        field_description: string
    }
}