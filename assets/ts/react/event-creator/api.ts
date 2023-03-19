import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export type EventCore = {
    uuid: string,
    name: string|null
    description: string|null
    short: string|null
    own: boolean,
    published: boolean,
    expires: boolean
}

export type EventMeta = {
    lang: string,
    name: string
    short: string
    description: string
}

export type TownPresetUUID = {
    uuid: string
}

export interface TownPreset extends TownPresetUUID {
    name: string|null
    lang: string,
    'type': string
}

export interface TownPresetData extends TownPresetUUID {
    header: object
    rules: object
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

export type ResponseListTowns = {
    towns: TownPreset[]
}

export type ResponseTown = TownPresetData

export type ResponseTownUUID = TownPresetUUID

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

    public setMeta(uuid: string, lang: string, name: string, desc: string, short: string): Promise<ResponseMeta> {
        return this.fetch.from(`/${uuid}/meta/${lang}`)
            .request().patch({name,desc,short}) as Promise<ResponseMeta>;
    }

    public deleteMeta(uuid: string, lang: string): Promise<boolean> {
        return this.fetch.from(`/${uuid}/meta/${lang}`)
            .request().delete().then(() => true);
    }

    public listTowns(uuid: string): Promise<ResponseListTowns> {
        return this.fetch.from(`/${uuid}/towns`)
            .request().get() as Promise<ResponseListTowns>;
    }

    public getTown(uuid: string, town: string): Promise<ResponseTown> {
        return this.fetch.from(`/${uuid}/town/${town}`)
            .request().get() as Promise<ResponseTown>;
    }

    public createTown(uuid: string, header: object, rules: object): Promise<ResponseTownUUID> {
        return this.fetch.from(`/${uuid}/town`)
            .request().put({header,rules}) as Promise<ResponseTownUUID>;
    }

    public updateTown(uuid: string, town: string, header: object, rules: object): Promise<ResponseTownUUID> {
        return this.fetch.from(`/${uuid}/town/${town}`)
            .request().patch({header,rules}) as Promise<ResponseTownUUID>;
    }

    public deleteTown(uuid: string, town: string): Promise<boolean> {
        return this.fetch.from(`/${uuid}/town/${town}`)
            .request().delete().then(() => true)
    }

}