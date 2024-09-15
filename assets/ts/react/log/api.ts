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
    day: number,
    hidden: boolean,
    hiddenBy?: LogEntryFaker
    text?: string
    hideable?: boolean,
    retro: boolean
}

export interface LogEntryResponse {
    entries: LogEntry[],
    total: number,
    manipulations: number
    purges: number
}

export interface ChatResponse {
    success?: boolean,
    error?: string|number
}

export class LogAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'game/log' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/index')
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
        return this.fetch.from(citizen <= 0 ? `${domain}` : `${domain}/${citizen}`)
            .param('day', day, day > 0)
            .param('limit', limit, limit > 0)
            .param('filter', filter.join(','), filter.length > 0)
            .param('below', below, below > 0)
            .param('above', above, above > 0)
            .request().secure().get() as Promise<LogEntryResponse>;
    }

    public deleteLog( id: number, purge: boolean ): Promise<LogEntryResponse> {
        return this.fetch.from( purge ? `${id}/full` : `${id}`)
            .request().secure().delete() as Promise<LogEntryResponse>;
    }

    public chat( zone: number, html: string ): Promise<ChatResponse> {
        return this.fetch.from(`chat/${zone}`).withErrorMessages()
            .request().secure().put({msg: html}) as Promise<ChatResponse>;
    }
}