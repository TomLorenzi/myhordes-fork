import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export type ResponseIndex = {
    strings: TranslationStrings,
}

export type EmoteListResponse = {
    result: {[index:string]: Emote}
    help?: string
}

export type EmoteResponse = EmoteListResponse & {
    mock: {[index:string]: string},
    snippets: null|{
        base: string,
        list: {[index:string]: Snippet}
    },
}

export type Emote = {
    tag: string,
    path: string,
    orderIndex: number,
    url: string,
}

export type Snippet = {
    key: string,
    value: string,
    lang: string,
    role: string,
}

export class TwinoEditorAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/soul/editor' );
    }

    public index(): Promise<ResponseIndex> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<ResponseIndex>;
    }

    public emotes( user: number|null, context: string = 'common' ): Promise<EmoteResponse> {
        return this.fetch.from(`/${user ?? 'me'}/unlocks/${context}/emotes`)
            .request().get() as Promise<EmoteResponse>;
    }

    public games( user: number|null, context: string = 'common' ): Promise<EmoteListResponse> {
        return this.fetch.from(`/${user ?? 'me'}/unlocks/${context}/games`)
            .request().get() as Promise<EmoteListResponse>;
    }

    public rp( user: number|null, context: string = 'common' ): Promise<EmoteListResponse> {
        return this.fetch.from(`/${user ?? 'me'}/unlocks/${context}/rp`)
            .request().get() as Promise<EmoteListResponse>;
    }
}