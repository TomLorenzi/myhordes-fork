export type TranslationStrings = {
    "common": {
        "help": string,
        "confirm": string,
        "continue": string,
        "return": string,

        "on": string,
        "off": string,
        "lock": string,
        "next": string,
        "prev": string,
    },
    "identity": {
        "headline": string,
        "help": string,
        "field": string,
        "validation1": string,
        "validation2": string
    }
    "jobs": {
        "headline": string,
        "help": string,
        "in_town": string,
        "more": string,
        "in_town_help": string,
        "flavour": string
    },
    "skills": {
        "headline": string,
        "help": string,
        "help_no_hero": string,
        "level": string,
        "pts": string,
        "unlock": string,
        "unlock_button": string,
        "unlock_img": string,
        "levels": string[],
    },
    "confirm": {
        "title": string,
        "help": string,
        "job": string,
        "skills": string,
        "empty_pt": string,
        "empty_pt_warn": string,
        "back": string,
        "ok": string,
    }
}

type TownClass = {
    id: number
    name: string
    order: number
    help: string,
    access: boolean,
}

export type GameTranslationStrings = {
    common: {
        help: string,
        warn: string,
        plus: string,
        death: string,
        close: string,
        lock: string,
        rules: string,
        cancel: string,
    },
    flags: {[key: string]: string},
    types: TownClass[],
    professions: {icon: string, name: string}[]
    table: {
        head: {
            name: string,
            citizens: string,
            coas: string
            coas_help: string
        },
        no_towns: string
        mayor: string,
        mayor_icon: string
        mayor_lines: string[],
        lang: string,
        lang_warn: string,
        show_players: string,
        password: string,
        whitelist: string,
        rules: string,
        more_info: string,
        event: string,
        event_help: string,
    },
    details: {
        join: string,
        headline: string,
        in_town: string,
        event1: string,
        event2: string,
        coa: string,
        password: string,
    }
}