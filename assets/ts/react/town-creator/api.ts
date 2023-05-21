import {TranslationStrings} from "./strings";
import {AjaxV1Response, Fetch} from "../../v2/fetch";

export type ResponseIndex = {
    strings: TranslationStrings,
    config: SysConfig
}

export type ResponseTownList = {
    id: number
    preset: boolean
    name: string
    help: string
}[]

export type SysConfig = {
    default_lang: string
}

export type TownRules = {
    wellPreset: string
    well: { min: number|string, max: number|string }

    mapPreset: string
    map: { min: number|string, max: number|string, margin: number|string }
    ruins: number|string
    explorable_ruins: number|string

    mapMarginPreset: string
    margin_custom: {
        enabled: boolean,
        north: number,
        south: number,
        west: number,
        east: number
    }

    features: {
        attacks: string
        ghoul_mode: string
        shaman: string
        nightwatch: {
            enabled: boolean|string
            instant: boolean|string
        },
        nightmode: boolean|string
        escort: {
            enabled: boolean|string
            max: number|string
        }
        shun: boolean|string
        camping: boolean|string
        all_poison: boolean|string
        'hungry_ghouls': boolean|string
        citizen_alias: boolean|string
        xml_feed: boolean|string
        free_for_all: boolean|string
        free_from_teams: boolean|string

        give_all_pictos: boolean|string
        enable_pictos: boolean|string
        give_soulpoints: boolean|string
    },

    modifiers: {

        building_attack_damage: boolean|string

        daytime: {
            range: (number|string)[]
            invert: boolean|string
        }

        strict_picto_distribution: boolean|string
    }

    disabled_jobs: Set<string>|string[]
    disabled_roles: Set<string>|string[]

    disabled_buildings: Set<string>|string[]
    unlocked_buildings: Set<string>|string[]
    initial_buildings: Set<string>|string[]

    initial_chest: Set<string>|string[]

    overrides: {
        named_drops: Set<string>|string[]
    }

    open_town_limit: number|string
    lock_door_until_full: boolean|string
}

export type TownOptions = {
    head: {
        townName: string
        townLang: string
        townCode: string
        townPop: number|string
        townSeed: number|string
        townType: number|string
        townBase: number|string
        townOpts: {
            noApi: boolean|string
            ffa: boolean|string
        },
        townIncarnation: string
        townEventTag: boolean|string

        customJobs: boolean|string
        customConstructions: boolean|string
    },
    rules: TownRules
}

export type Template = {
    uuid: string
    name: string
}

interface TownCreationResponse extends AjaxV1Response {
    url?: string
}

interface TemplateDataResponse extends Template {}
interface TemplateListResponse extends Array<Template> {}
interface TemplateContentResponse {
    rules: TownRules
}
export class TownCreatorAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'town-creator' );
    }

    public index(): Promise<ResponseIndex> {
        return this.fetch.from('/')
            .request().withCache().get() as Promise<ResponseIndex>;
    }

    public townList(): Promise<ResponseTownList> {
        return this.fetch.from('town-types')
            .request().get() as Promise<ResponseTownList>;
    }

    public townRulesPreset(id: number, privateTown: boolean = false): Promise<TownRules> {
        return this.fetch.from(`town-rules/${privateTown ? 'private/' : ''}${id}`)
            .request().get() as Promise<TownRules>;
    }

    public createTown(data: TownOptions): Promise<TownCreationResponse> {
        return this.fetch.from('create-town')
            .bodyDeterminesSuccess()
            .request().post(data) as Promise<TownCreationResponse>;
    }

    public listTemplates(): Promise<TemplateListResponse> {
        return this.fetch.from('template')
            .request().get() as Promise<TemplateListResponse>;
    }

    public getTemplate(uuid: string): Promise<TemplateContentResponse> {
        return this.fetch.from(`template/${uuid}`)
            .request().get() as Promise<TemplateContentResponse>;
    }

    public createTemplate(rules: TownRules, name: string): Promise<TemplateDataResponse> {
        return this.fetch.from('template')
            .request().put({rules, name}) as Promise<TemplateDataResponse>;
    }

    public updateTemplate(rules: TownRules, uuid: string): Promise<TemplateDataResponse> {
        return this.fetch.from(`template/${uuid}`)
            .request().patch({rules}) as Promise<TemplateDataResponse>;
    }

    public deleteTemplate(uuid: string): Promise<TemplateDataResponse> {
        return this.fetch.from(`template/${uuid}`)
            .request().delete() as Promise<TemplateDataResponse>;
    }

}