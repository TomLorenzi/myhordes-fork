import {Setting} from "./api";

export type TranslationStrings = {
    common: {
        help: string,
        infoText1: string
        infoText2: string
        infoText3: string
        unsupported: string
        rejected: string

        error_put_400: string
        error_put_409: string
    },
    actions: {
        add: string,
        registered: string
        removed: string
        edit: string
        test_ok: string
        test_expired: string
        test_error: string
    },
    table: {
        none: string
        device: string
        edit: string
        edit_icon: string
        remove: string
        remove_icon: string
        test: string
        test_icon: string
        expired: string
        expired_icon: string
    }
    settings: {
        headline: string,
        toggle: Array<Setting>
    }
}