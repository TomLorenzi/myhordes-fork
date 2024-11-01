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

export type InventoryCategory = {
    id: number,
    items: Item[],
}

export type InventoryResponse = InventoryBagData | InventoryBankData;

export type InventoryBaseData = {
    bank: boolean
    mods: InventoryMods
}

export type InventoryBagData = InventoryBaseData & {
    bank: false
    size: number|null,
    items: Item[],
}

export type InventoryBankData = InventoryBaseData & {
    bank: true,
    categories: InventoryCategory[]
}

export type TransportResponse = {
    success: boolean,
    messages: string,
    errors: number[],
    incidentals: {[key:string]: string}
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