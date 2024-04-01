import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export type ResponseIndex = {
    strings: TranslationStrings
}

export type ResponseReport = {
    message?: string,
}


export class ContentReportAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/complaint' );
    }

    public index(type: string): Promise<ResponseIndex> {
        return this.fetch.from(`/${type}`)
            .request().withCache().get() as Promise<ResponseIndex>;
    }

    public report(type: string, principal: number, form: object): Promise<ResponseReport> {
        return this.fetch.from(`/${type}/${principal}`).withErrorMessages().throwResponseOnError()
            .request().put(form) as Promise<ResponseReport>;
    }

}