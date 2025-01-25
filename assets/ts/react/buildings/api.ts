import {Fetch} from "../../v2/fetch";
import {TranslationStrings} from "./strings";

export type Building = {
    i: number,
    p: number,
    l: number,
    t: boolean,
    c: boolean,
    d0: number,
    db: number,
    dt: number,
    e: boolean,
    a: [number,number],
    v?: boolean
}

export type BuildingListResponse = {
    buildings: Building[],
}

export type BuildingBuildResponse = {
    success?: boolean,
    message?: string,
    building?: Building,
}

export class BuildingAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'town/core/building' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<TranslationStrings>;
    }

    public list(completed: boolean): Promise<BuildingListResponse> {
        return this.fetch.from(`/list`, {completed: completed ? '1' : '0'})
            .request().get() as Promise<BuildingListResponse>;
    }

    public build(building: number, ap: number): Promise<BuildingBuildResponse> {
        return this.fetch.from(`/${building}`)
            .request().patch({ap}) as Promise<BuildingBuildResponse>;
    }

    public vote(building: number): Promise<BuildingBuildResponse> {
        return this.fetch.from(`/${building}`)
            .request().post() as Promise<BuildingBuildResponse>;
    }

}