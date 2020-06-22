interface confSetter<T> { (T): void }
interface confGetter<T> { (): T }
interface conf<T> { set: confSetter<T>, get: confGetter<T> }

class Config {

    private client: Client;

    public notificationAsPopup: conf<boolean>;

    constructor(c:Client) {
        this.client = c;

        this.notificationAsPopup = this.makeConf<boolean>('notifAsPopup', false);
    }

    public get<T>(s:string): conf<T> {
        return (this[s] ?? null) as conf<T>;
    }

    private makeConf<T>(name: string, initial: T): conf<T> {
        return {
            set: (v:T):void => this.client.set( name, 'config', v, false ) as null,
            get: ():T       => this.client.get( name, 'config', initial )
        }
    }
}

export default class Client {

    public config: Config;

    constructor() { this.config = new Config(this); }

    private static key( name: string, group: string|null ): string {
        return 'myh.' + (group === null ? 'default' : group) + '.' + name;
    }

    private static get_var(storage: Storage, name: string, group: string|null = null, default_value: any = null ): any | null {
        const item = storage.getItem( this.key( name, group ) );
        if (item === null) return default_value;
        try {
            return JSON.parse(item);
        } catch (e) {
            return item;
        }
    }

    private static set_var( storage: Storage, name: string, group: string|null, value: any ): boolean {
        try {
            if (value === null)
                storage.removeItem( this.key( name, group ) );
            storage.setItem( this.key( name, group ), JSON.stringify(value) );
            return true;
        } catch (e) {
            return false;
        }
    }

    set( name: string, group: string|null, value: any, session_only: boolean ): boolean {
        return Client.set_var( session_only ? window.sessionStorage : window.localStorage, name, group, value );
    }

    get( name: string, group: string|null = null, default_value: any = null ): any {
        return Client.get_var( window.sessionStorage, name, group, Client.get_var( localStorage, name, group, default_value ) );
    }
}