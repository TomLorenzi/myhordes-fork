type Field = {
    title: string,
    hint: string,
    example?: string|null
}

export type TranslationStrings = {
    redirect: string|null,

    common: {
        prompt: string,
        warn: string,

        add_file: string,
        add_screenshot: string,
        screenshot_failed: string,
        delete_file: string,
        ok: string,
        cancel: string,

        success: string,
    },

    errors: {
        too_large: string,
        error_400: string,
        error_407: string,
        error_412: string,
    }

    fields: {
        title: Field,
        desc: Field
        attachment: Field
    }
}