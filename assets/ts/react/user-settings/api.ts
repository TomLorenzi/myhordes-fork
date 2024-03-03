import {Fetch} from "../../v2/fetch";

export type UserSettingBase = {
    option: string,
    value: any,
    'default': any
    isConfigured: boolean
}

export class UserSettingsAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'user/settings/options' );
    }

    public list( ): Promise<Array<UserSettingBase>> {
        return this.fetch.from('/')
            .request().get() as Promise<Array<UserSettingBase>>;
    }

    public toggle(option: string, value: boolean): Promise<UserSettingBase> {
        return this.fetch.from(`/${option}`).throwResponseOnError()
            .request().method( value ? 'put' : 'delete' ) as Promise<UserSettingBase>;
    }
}