import {TranslationStrings} from "./strings";
import {AjaxV1Response, Fetch} from "../../v2/fetch";

export type ResponseIndex = {
    strings: TranslationStrings,
}

type Media = {
    url: string,
    format: string,
    size: number
}

export type ResponseMedia =  {
    default: Media|null,
    round: Media|null,
    small: Media|null,
}

export type Crop = {
    x: number,
    y: number,
    height: number,
    width: number
}

export class AvatarCreatorAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/settings/avatar' );
    }

    public index(): Promise<ResponseIndex> {
        return this.fetch.from('/index')
            .request().withCache().get() as Promise<ResponseIndex>;
    }

    public getMedia(): Promise<ResponseMedia> {
        return this.fetch.from('/media')
            .request().get() as Promise<ResponseMedia>;
    }

    public deleteMedia(): Promise<boolean> {
        return this.fetch.from('/media')
            .request().delete().then(() => true);
    }

    public uploadMedia(mime,data,cropDefault = null,cropSmall = null,format=null): Promise<boolean|number> {
        return this.fetch.from('/media').bodyDeterminesSuccess(true).withErrorMessages()
            .request().put({mime,data,format,crop: {
                default: cropDefault,
                small: cropSmall
            }}).then(() => true);
    }

}