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

        ok: string,
        cancel: string,
    },

    fields: {
        title: Field,
        desc: Field
    }
}