import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export class HxpManagementApi {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'soul/skills/hxp/pack' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<TranslationStrings>;
    }
}