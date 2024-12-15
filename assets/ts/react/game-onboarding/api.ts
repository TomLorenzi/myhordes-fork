import {GameTranslationStrings, TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";
import {number} from "prop-types";

export type ResponseConfig = {
    "features": {
        "job": boolean,
        "alias": boolean,
        "skills": boolean
    }
}

export type JobDescription = {
    "id": number,
    "name": string,
    "desc": string,
    "hero": boolean,
    "icon": string,
    "poster": string,
    "help": string,
}

export type LegacySkill = {
    "id": number,
    "title": string,
    "description": string,
    "icon": string,
    "needed": number
}

export type Skill = {
    "id": number,
    "title": string,
    "description": string,
    "bullets": string[],
    "icon": string,
    "level": number,
    "sort": number,
    "group": string
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

export type OnboardingSkillPayload = {
    ids: number[]
}

export type OnboardingPayload = {
    identity: OnboardingIdentityPayload|false|null,
    profession: OnboardingProfessionPayload|null
    skills: OnboardingSkillPayload|null
}

export type OnboardingCache = {
    profession: ResponseJobs|null
    skills: ResponseSkills|null
}

export type ResponseJobs = JobDescription[]

export type ResponseSkills = {
    legacy?: {
        level: number
        list: LegacySkill[]
    },
    skills?: {
        pts: number,
        unlock_url: string|null
        groups: string[],
        list: Skill[]
    }
}

export type ResponseCitizenCount = {
    list: CitizenCount[],
    token?: object,
}

export type ResponseConfirm = {
    url: string
}

export class TownOnboardingAPI {

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

    public skills(town: number): Promise<ResponseSkills> {
        return this.fetch.from(`/${town}/skills`)
            .request().get() as Promise<ResponseSkills>;
    }

    public citizens(town: number): Promise<ResponseCitizenCount> {
        return this.fetch.from(`/${town}/citizens`)
            .request().get() as Promise<ResponseCitizenCount>;
    }

}

export type Town = {
    id: number,
    name: string,
    population: number
    language: string
    citizenCount: number
    type: number,
    mayor: boolean,
    event: boolean,
    coalitions: number,
    protection: {
        password: boolean,
        whitelist: boolean,
    },
    custom_rules: boolean
}

export type Citizen = {
    id: number,
    name: string,
    profession: number|null,
    alive: boolean,
}

export type TownListResponse = {
    towns: Town[],
}

export type CitizenListResponse = {
    citizens: Citizen[],
}

export type TownDetailsResponse = {
    town: Town,
    citizens: Citizen[],
    coa: Citizen[],
    locks: string[],
    warnings: string[],
    rules: string[],
}

export class GameOnboardingAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'game/lobby' );
    }

    public index(): Promise<GameTranslationStrings> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<GameTranslationStrings>;
    }

    public list(): Promise<TownListResponse> {
        return this.fetch.from('/list')
            .request().get() as Promise<TownListResponse>;
    }

    public citizens(town_id: number): Promise<CitizenListResponse> {
        return this.fetch.from(`/${town_id}/citizens`)
            .request().get() as Promise<CitizenListResponse>;
    }

    public details(town_id: number): Promise<TownDetailsResponse> {
        return this.fetch.from(`/${town_id}`)
            .request().get() as Promise<TownDetailsResponse>;
    }

    public join(town_id: number, pass: string|null): Promise<{url: string}> {
        return this.fetch.from(`/${town_id}`)
            .request().post({pass}) as Promise<{url: string}>;
    }

}