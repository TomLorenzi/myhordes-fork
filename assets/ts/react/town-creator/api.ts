import {TranslationStrings} from "./strings";

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

    features: {
        attacks: string
    }
}

export type TownOptions = {
    head: {
        townName: string,
        townLang: string,
        townPop: number|string,
        townSeed: number|string,
        townType: number|string,
        townBase: number|string,
        townOpts: {
            noApi: boolean|string,
            alias: boolean|string,
            ffa: boolean|string
        }
    },
    rules: TownRules
}

export class TownCreatorAPI {

    private readonly base: string = null;

    constructor(base: string) {
        this.base = base;
    }

    private fetch_config( method: string ): RequestInit {
        return {
            method,
            mode: "no-cors",
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            redirect: 'follow'
        }
    }

    private async extract( promise: Promise<Response> ): Promise<any> {
        const data = await promise;
        if (data.ok) return data.json();
        else throw `FETCH ERROR ${data.status} (${data.statusText}): (${(await data.text()) ?? 'no_data'})`;
    }

    public async index(): Promise<ResponseIndex> {
        return await this.extract(
            fetch( this.base, this.fetch_config('GET') )
        ) as Promise<ResponseIndex>;
    }

    public async townList(): Promise<ResponseTownList> {
        return await this.extract(
            fetch( `${this.base}/town-types`, this.fetch_config('GET') )
        ) as Promise<ResponseTownList>;
    }

    public async townRulesPreset(id: number): Promise<TownRules> {
        return await this.extract(
            fetch( `${this.base}/town-rules/${id}`, this.fetch_config('GET') )
        ) as Promise<TownRules>;
    }

}