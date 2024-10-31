import {Fetch} from "../../v2/fetch";
import {TranslationStrings} from "./strings";

export type Item = {
    i: number,
    p: number,
    c: number,
    w: string|null,
    b: boolean,
    h: boolean,
    e: boolean,
    s: number[],
}

export type InventoryMods = {
    has_drunk?: boolean,
}

export type InventoryResponse = {
    size: number|null,
    mods: InventoryMods
    items: Item[],
}

export type TransportResponse = {
    success: boolean,
    messages: string,
    errors: number[],
    source: InventoryResponse,
    target: InventoryResponse,
}

export class InventoryAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'game/inventory' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/index')
            .request().withCache().get() as Promise<TranslationStrings>;
    }

    public inventory(id: number): Promise<InventoryResponse> {
        return this.fetch.from(`/${id}`)
            .request().get() as Promise<InventoryResponse>;
    }

    public transfer(id: number|null, from: number, to: number, d: string, mod: string = null): Promise<TransportResponse> {
        return this.fetch.from(id !== null ? `/${from}/${id}` : `/${from}`)
            .request().patch({d,mod,to}) as Promise<TransportResponse>;
    }
}