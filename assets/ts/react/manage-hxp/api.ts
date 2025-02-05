import {TranslationStrings} from "./strings";
import {Fetch} from "../../v2/fetch";

export type HeroSkill = {
    id: number,
    title: string,
    description: string,
    bullets: string[],
    icon: string,
    level: number
    sort: number
    group: string,
    value: number,
    locked: boolean,
}

export type SkillState = {
    skills: HeroSkill[],
    hxp: number
    hxp_needed: number
}

export class HxpManagementApi {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/soul/skills/hxp/pack' );
    }

    public index(): Promise<TranslationStrings> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<TranslationStrings>;
    }

    public skills(): Promise<SkillState> {
        return this.fetch.from('/list')
            .request().get() as Promise<SkillState>;
    }
}