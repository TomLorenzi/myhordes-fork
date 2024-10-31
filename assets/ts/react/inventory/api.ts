import {Fetch} from "../../v2/fetch";
import {TranslationStrings} from "./strings";
import {number} from "prop-types";

export type Item = {
    i: number,
    p: number,
    c: number,
    b: boolean,
    h: boolean,
    e: boolean,
}

export type InventoryResponse = {
    size: number|null,
    items: Item[],
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
}