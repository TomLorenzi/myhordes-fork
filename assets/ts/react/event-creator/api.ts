import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

type EventOwner = {
    id: number,
    name: string
}

export type EventCore = {
    uuid: string,
    name: string|null
    description: string|null
    short: string|null
    own: boolean,
    published: boolean,
    started: boolean,
    daysLeft: number|null,
    ended: boolean,
    start: string|null,
    proposed: boolean,
    expedited?: boolean,
    owner: EventOwner|null
}

export type EventConfig = {
    startDate?: string,
    expedited?: boolean,
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
    'type': string,
    password: string|null,
    instance: TownPresetInstance|null
}

export interface TownPresetInstance  {
    name: string|null
    ranking_link: string|null
    forum_link: string|null,
    active: boolean|null,
    population: number|null,
    filled: number|null,
    living: number|null,
    day: number|null,
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
        return this.fetch.from('/index_data/')
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

    public propose(uuid: string): Promise<ResponseCreate> {
        return this.fetch.from(`/${uuid}/proposal`)
            .request().put() as Promise<ResponseCreate>;
    }

    public cancelProposal(uuid: string): Promise<ResponseCreate> {
        return this.fetch.from(`/${uuid}/proposal`)
            .request().delete() as Promise<ResponseCreate>;
    }

    public publish(uuid: string): Promise<ResponseCreate> {
        return this.fetch.from(`/${uuid}/publish`)
            .request().put() as Promise<ResponseCreate>;
    }

    public finish(uuid: string): Promise<ResponseCreate> {
        return this.fetch.from(`/${uuid}/end`)
            .request().put() as Promise<ResponseCreate>;
    }

    public getConfig(uuid: string): Promise<EventConfig> {
        return this.fetch.from(`/${uuid}/config`)
            .request().get() as Promise<EventConfig>;
    }

    public editConfig(uuid: string, config: EventConfig): Promise<EventConfig> {
        return this.fetch.from(`/${uuid}/config`)
            .request().patch(config) as Promise<EventConfig>;
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