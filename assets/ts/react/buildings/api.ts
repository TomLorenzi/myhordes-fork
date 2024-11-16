import {Fetch} from "../../v2/fetch";
import {TranslationStrings} from "./strings";

export type Building = {
    i: number,
    p: number,
    l: number,
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

}