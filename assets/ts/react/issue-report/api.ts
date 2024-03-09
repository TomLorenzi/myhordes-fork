import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export type ResponseIndex = {
    strings: TranslationStrings
}

export type ResponseReport = {
    success: boolean
}

type FileUpload = {
    file: string,
    ext: string,
    content: string
}

export class IssueReportAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/issues' );
    }

    public index(): Promise<ResponseIndex> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<ResponseIndex>;
    }

    public report(data: object, files: FileUpload[] = []): Promise<ResponseReport> {
        return this.fetch.from('/').withErrorMessages().throwResponseOnError()
            .request().put({
                ...data,
                issue_attachments: files
            }) as Promise<ResponseReport>;
    }

}