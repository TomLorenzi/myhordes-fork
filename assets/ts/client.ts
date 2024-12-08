interface confSetter<T> { (T): void }
interface confGetter<T> { (): T }
interface conf<T> { set: confSetter<T>, get: confGetter<T> }

class Config {

    private client: Client;

    public showShortConstrList:   conf<boolean>;
    public showBankCategories:    conf<boolean>;
    public notificationAsPopup:   conf<boolean>;
    public advancedPMEditor:      conf<boolean>;
    public usePostBackup:         conf<boolean>;
    public autoParseLinks:        conf<boolean>;
    public twoTapTooltips:        conf<boolean>;
    public extendConstructions:   conf<boolean>;
    public ttttHelpSeen:          conf<boolean>;
    public iconZoom:              conf<string>;
    public forumFontSize:         conf<string>;
    public twinoidImport:         conf<[number,string,string]>;
    public editorCache:           conf<string>;
    public scopedEditorCache:     conf<[string,string]>;
    public navigationCache:       conf<string>;
    public hiddenConditionalHelp: conf<Array<string>>;
    public completedTutorials:    conf<Array<number>>;
    public armaHideSkulls:        conf<number>;

    constructor(c:Client) {
        this.client = c;

        this.showShortConstrList   = this.makeConf<boolean>('showShortConstrList', false);
        this.showBankCategories    = this.makeConf<boolean>('showBankCategories', true);
        this.notificationAsPopup   = this.makeConf<boolean>('notifAsPopup', false);
        this.advancedPMEditor      = this.makeConf<boolean>('advancedPMEditor', false);
        this.usePostBackup         = this.makeConf<boolean>('useEditorCache', true);
        this.autoParseLinks        = this.makeConf<boolean>('autoParseLinks', true);
        this.twoTapTooltips        = this.makeConf<boolean>('twoTapTooltips', false);
        this.extendConstructions   = this.makeConf<boolean>('extendConstructions', false);
        this.ttttHelpSeen          = this.makeConf<boolean>('ttttHelpSeen', false);
        this.iconZoom              = this.makeConf<string>('iconZoom', '1-00');
        this.forumFontSize         = this.makeConf<string>('forumFontSize', 'normal');
        this.twinoidImport         = this.makeConf<[number,string,string]>('twinImport', [0,'',''], true);
        this.editorCache           = this.makeConf<string>('editorCache', '', true);
        this.scopedEditorCache     = this.makeConf<[string,string]>('scopedEditorCache', ['',''], true);
        this.navigationCache       = this.makeConf<string>('navigationCache', null, true);
        this.hiddenConditionalHelp = this.makeConf<Array<string>>('hiddenConditionalHelp', [], false);
        this.completedTutorials    = this.makeConf<Array<number>>('completedTutorials', [], false);
        this.armaHideSkulls        = this.makeConf<number>('armaHideSkulls', 0, true);
    }

    public get<T>(s:string): conf<T> {
        return (this[s] ?? null) as conf<T>;
    }

    private makeConf<T>(name: string, initial: T, session: boolean = false): conf<T> {
        return {
            set: (v:T):void => this.client.set( name, 'config', v, session ) as null,
            get: ():T       => this.client.get( name, 'config', initial )
        }
    }
}

export default class Client {

    public config: Config;

    public static DomainScavenger = [true,true,true];
    public static DomainDaily     = [true,true,false];
    public static DomainTown      = [true,false,false];
    public static DomainUser      = [false,false,false];

    private pSession = 0;
    private vSession = [0,0,0];

    constructor() { this.config = new Config(this); }

    private key( name: string, group: string|null ): string {
        const user_prefix = group !== 'config' || [
            'editorCache',
            'scopedEditorCache',
            'twinImport',
            'completedTutorials'
        ].includes(name);
        return (user_prefix ? 'myh:' + this.pSession : 'myh') + '.' + (group === null ? 'default' : group) + '.' + name;
    }

    private get_var(storage: Storage, name: string, group: string|null = null, default_value: any, mask: Array<boolean> ): any | null {
        const key = this.key( name, group );
        const item = storage.getItem( key );
        if (item === null) return default_value;
        try {
            let object = JSON.parse(item);
            if (typeof object === "object" && typeof object.domain !== "undefined" && typeof object.value !== "undefined") {
                if (this.vSession.reduce( (validator,v,i) => !mask[i] || object.domain[i] === v ? validator : false, true ))
                    return object.value;
                else {
                    storage.removeItem( key );
                    return default_value;
                }
            } else return object;
        } catch (e) {
            return default_value;
        }
    }

    private set_var( storage: Storage, name: string, group: string|null, value: any ): boolean {
        try {
            if (value === null)
                storage.removeItem( this.key( name, group ) );
            storage.setItem( this.key( name, group ), JSON.stringify({domain: this.vSession, value}) );
            return true;
        } catch (e) {
            return false;
        }
    }

    setSessionDomain( persistent: number, volatile1: number, volatile2: number, volatile3: number ): void {
        this.pSession = persistent;
        this.vSession = [ volatile1, volatile2, volatile3 ];
    }

    set( name: string, group: string|null, value: any, session_only: boolean ): boolean {
        return this.set_var( session_only ? (window.sessionStorage as Storage) : (window.localStorage as Storage), name, group, value );
    }

    get( name: string, group: string|null = null, default_value: any = null, mask: Array<boolean> = Client.DomainUser ): any {
        return this.get_var( window.sessionStorage, name, group, this.get_var( localStorage, name, group, default_value, mask ), mask );
    }
}