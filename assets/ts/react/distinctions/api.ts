import {TranslationStrings} from "./strings";
import {AjaxV1Response, Fetch} from "../../v2/fetch";

export type ResponseIndex = {
    strings: TranslationStrings
}

export type DistinctionPicto = {
    id: number,
    label: string,
    description: string,
    comments: string[],
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
    top3: Top3|null
}

export type Top3 = number[]

export type ResponseUpdateTop3 = {
    updated: Top3
}

export class SoulDistinctionAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/soul/distinctions' );
    }

    public index(): Promise<ResponseIndex> {
        return this.fetch.from('/index')
            .request().withCache().get() as Promise<ResponseIndex>;
    }

    public data(user: number, source: string = 'soul'): Promise<ResponseDistinctions> {
        if (['old','soul','mh','imported','all'].indexOf(source) < 0)
            return new Promise<ResponseDistinctions>((_,reject) => reject(null))
        else return this.fetch.from(`${user}/${source}`)
            .request().get() as Promise<ResponseDistinctions>;
    }

    public top3(user: number, data: number[]): Promise<ResponseUpdateTop3> {
        return this.fetch.from(`${user}/top3`)
            .request().patch({data}) as Promise<ResponseUpdateTop3>;
    }

}