import {TranslationStrings} from "./strings";
import {AjaxV1Response, Fetch} from "../../v2/fetch";

export type ResponseIndex = {
    strings: TranslationStrings
}

export type DistinctionPicto = {
    id: number,
    label: string,
    description: string,
    icon: string,
    rare: boolean,
    count: number
}

type DistinctionPictoReference = {
    id: number,
    count: number
}

export type DistinctionAward = {
    id: number,
    label: string,
    picto: DistinctionPictoReference|null
}

export type ResponseDistinctions = {
    points: number|null,
    pictos: DistinctionPicto[]|null,
    awards: DistinctionAward[]|null
    top3: number[]|null
}

export class SoulDistinctionAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/soul/distinctions' );
    }

    public index(): Promise<ResponseIndex> {
        return this.fetch.from('/')
            .request().get() as Promise<ResponseIndex>;
    }

    public data(user: number, source: string = 'soul'): Promise<ResponseDistinctions> {
        if (['old','soul','mh','imported','all'].indexOf(source) < 0)
            return new Promise<ResponseDistinctions>((_,reject) => reject(null))
        else return this.fetch.from(`${user}/${source}`)
            .request().get() as Promise<ResponseDistinctions>;
    }

}