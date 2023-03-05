import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export type EventCore = {
    uuid: string,
    name: string|null
    description: string|null
    own: boolean,
    published: boolean,
    expires: boolean
}

export type EventMeta = {
    lang: string,
    name: string
    description: string
}

export type ResponseIndex = {
    strings: TranslationStrings
}

export type ResponseCreate = {
    uuid: string
}

export type ResponseList = {
    events: EventCore[]
}

export type ResponseListMeta = {
    meta: EventMeta[]
}

export type ResponseMeta = {
    meta: EventMeta
}

export class EventCreationAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/soul/events' );
    }

    public index(): Promise<ResponseIndex> {
        return this.fetch.from('/index/')
            .request().withCache().get() as Promise<ResponseIndex>;
    }

    public list(): Promise<ResponseList> {
        return this.fetch.from('/')
            .request().get() as Promise<ResponseList>;
    }

    public create(): Promise<ResponseCreate> {
        return this.fetch.from('/')
            .request().put() as Promise<ResponseCreate>;
    }

    public delete(uuid: string): Promise<boolean> {
        return this.fetch.from(`/${uuid}/`)
            .request().delete().then(() => true);
    }

    public listMeta(uuid: string): Promise<ResponseListMeta> {
        return this.fetch.from(`/${uuid}/meta`)
            .request().get() as Promise<ResponseListMeta>;
    }

    public setMeta(uuid: string, lang: string, name: string, desc: string): Promise<ResponseMeta> {
        return this.fetch.from(`/${uuid}/meta/${lang}`)
            .request().patch({name,desc}) as Promise<ResponseMeta>;
    }

    public deleteMeta(uuid: string, lang: string): Promise<boolean> {
        return this.fetch.from(`/${uuid}/meta/${lang}`)
            .request().delete().then(() => true);
    }

}