import {TranslationStrings} from "./strings";
import {AjaxV1Response, Fetch} from "../../v2/fetch";

interface LogEntryFaker {
    name: string,
    id: number
}

export interface LogEntry {
    timestamp: number,
    timestring: string,
    'class': number
    'type': number,
    'protected': boolean,
    id: number,
    hidden: boolean,
    hiddenBy?: LogEntryFaker
    text?: string
    hideable?: boolean
}

export interface LogEntryResponse {
    entries: LogEntry[],
    total: number,
    manipulations: number
}

export class LogAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'game/log' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<TranslationStrings>;
    }

    public logs(
        domain: string,
        citizen: number = null,
        day: number = -1,
        limit: number = -1,
        filter: number[] = [],
        below: number = -1,
        above: number = -1,
    ): Promise<LogEntryResponse> {
        return this.fetch.from(domain === 'beyond' ? `${domain}?` : `${domain}/${citizen}`)
            .param('day', day, day > 0)
            .param('limit', limit, limit > 0)
            .param('filter', filter.join(','), filter.length > 0)
            .param('below', below, below > 0)
            .param('above', above, above > 0)
            .request().secure().get() as Promise<LogEntryResponse>;
    }

    public deleteLog( id: number ): Promise<LogEntryResponse> {
        return this.fetch.from(`${id}`)
            .request().secure().delete() as Promise<LogEntryResponse>;
    }
}