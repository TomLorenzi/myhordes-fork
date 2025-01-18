import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export interface LogEntry {
    id: number
    timestamp: number,
    value: number,
    text: string,
    'type': number,
    reset: boolean,
    outdated: boolean,
    past: string|null,
}

export interface LogEntryResponse {
    entries: LogEntry[],
    additional: boolean
}


export class HxpLogAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/soul/skills/hxp' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/index')
            .request().withCache().get() as Promise<TranslationStrings>;
    }

    public logs(after: number = null, focus: number = null): Promise<LogEntryResponse> {
        return this.fetch.from('/', { after, focus })
            .request().get() as Promise<LogEntryResponse>;
    }

}