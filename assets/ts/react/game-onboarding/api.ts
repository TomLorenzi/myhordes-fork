import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export type ResponseConfig = {
    "features": {
        "job": boolean,
        "alias": boolean,
        "abilities": boolean
    }
}

export type JobDescription = {
    "id": number,
    "name": string,
    "desc": string,
    "icon": string,
    "poster": string,
    "help": string,
}

export type CitizenCount = {
    n: number,
    id: number
}

export type OnboardingIdentityPayload = {
    name: string
}

export type OnboardingProfessionPayload = {
    id: number
}

export type OnboardingPayload = {
    identity: OnboardingIdentityPayload|false|null,
    profession: OnboardingProfessionPayload|null
}

export type ResponseJobs = JobDescription[]

export type ResponseCitizenCount = {
    list: CitizenCount[],
    token?: object,
}

export type ResponseConfirm = {
    url: string
}

export class GameOnboardingAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'game/welcome' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<TranslationStrings>;
    }

    public config(town: number): Promise<ResponseConfig> {
        return this.fetch.from(`/${town}`)
            .request().withCache().get() as Promise<ResponseConfig>;
    }

    public confirm(town: number, p: OnboardingPayload): Promise<ResponseConfirm> {
        return this.fetch.from(`/${town}`)
            .request().patch(p) as Promise<ResponseConfirm>;
    }

    public jobs(town: number): Promise<ResponseJobs> {
        return this.fetch.from(`/${town}/professions`)
            .request().withCache().get() as Promise<ResponseJobs>;
    }

    public citizens(town: number): Promise<ResponseCitizenCount> {
        return this.fetch.from(`/${town}/citizens`)
            .request().withCache().get() as Promise<ResponseCitizenCount>;
    }

}