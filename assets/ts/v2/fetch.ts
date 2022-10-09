// Make v1 API available
import {Const, Global} from "../defaults"
import {bool, string} from "prop-types";
declare var c: Const;
declare const $: Global;

export interface AjaxV1Response {
    success: boolean|any,
    error?: number|string,
    message?: string,
}

class FetchBuilder {

    private readonly url: string;
    private request: RequestInit;
    private readonly f_then: (Response) => Promise<any>
    private readonly f_catch: (any) => Promise<any>
    private f_before: (()=>void)[]

    constructor(url: string, f_then: (Response) => Promise<any>, f_catch: (any) => Promise<any>) {
        this.url = url;
        this.f_then = f_then;
        this.f_catch = f_catch;
        this.f_before = [];

        this.request = {
            mode: "cors",
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            redirect: 'follow'
        }
    }

    private execute(method: string, body?: object): Promise<any> {
        this.f_before.map(fn=>fn());
        return fetch( this.url, body ? {
                method,
                body: JSON.stringify( body ),
                ...this.request
        } : {
            method,
            ...this.request
        } ).then(this.f_then, this.f_catch)
    }

    public before(fn: ()=>void): FetchBuilder { this.f_before.push( fn ); return this; }

    public get(): Promise<any> { return this.execute('GET'); }
    public delete(): Promise<any> { return this.execute('DELETE'); }
    public post(body?: object): Promise<any> { return this.execute('POST', body); }
    public patch(body?: object): Promise<any> { return this.execute('PATCH', body); }
    public put(body?: object): Promise<any> { return this.execute('PUT', body); }

}

class FetchOptions {
    public error_messages: boolean = true;
    public loader: boolean = true;
    public body_success: boolean = false;
}

class FetchOptionBuilder {
    private readonly processor: (Response, FetchOptions) => Promise<any>;
    private readonly error_handler: (any, FetchOptions) => Promise<any>;
    private readonly url: string;
    private readonly options: FetchOptions;

    constructor(url: string, processor: (Response, FetchOptions) => Promise<any>, handler: (any, FetchOptions) => Promise<any>) {
        this.url = url;
        this.processor = processor;
        this.error_handler = handler;
        this.options = new FetchOptions();
    }

    public withErrorMessages(): FetchOptionBuilder { this.options.error_messages = true; return this; }
    public withoutErrorMessages(): FetchOptionBuilder { this.options.error_messages = false; return this; }

    public withLoader(): FetchOptionBuilder { this.options.loader = true; return this; }
    public withoutLoader(): FetchOptionBuilder { this.options.loader = false; return this; }

    public bodyDeterminesSuccess(b: boolean = true): FetchOptionBuilder { this.options.body_success = b; return this; }

    public request(): FetchBuilder {
        return new FetchBuilder( this.url, (r: Response) => this.processor(r, this.options), r => this.error_handler(r, this.options) );
    }
}

export class Fetch {

    private readonly rest: string = null;

    private remove_slashes(url: string): string {
        return url.match(/^\/?(.*?)\/?$/)[1];
    }

    constructor(rest_endpoint?: string, version: number = 1) {
        const base_url = this.remove_slashes( document.querySelector('base[href]').getAttribute('href') ?? '' );

        this.rest = `${window.location.protocol}//${window.location.host}/${base_url ? `${base_url}/rest` : 'rest'}/v${version}/${this.remove_slashes( rest_endpoint ?? '' )}`;
    }

    private handle_response_headers( response: Response ) {
        const instruction = response.headers.get('X-AJAX-Control') ?? 'process';
        switch ( instruction ) {
            case 'reset':
                window.location.href = $.ajax.getBaseURL();
                throw null;
            case 'navigate':
                window.location.href = response.headers.get('X-AJAX-Navigate') ?? $.ajax.getBaseURL();
                throw null;
            case 'reload':
                window.location.reload();
                throw null;
            case 'cancel':
                throw null;
            case 'process': default:
                break;
        }

        const session_domain = response.headers.get('X-Session-Domain') ?? null;
        if (session_domain) {
            const [p = '0', v1 = '0', v2 = '0', v3 = '0'] = session_domain.split(':');
            $.client.setSessionDomain(parseInt(p),parseInt(v1),parseInt(v2),parseInt(v3));
        }
    }

    private async process_network_failure( error: any = undefined, options: FetchOptions ) {
        if ( typeof error !== "undefined") {
            if (options.error_messages)
                $.html.error( typeof error === 'string' ? `${c.errors['net']}<br/><code>${error}</code>` : c.errors['net']);
            else if (error) console.error(error);

            throw error;
        }
    }

    private async preprocess_response( response: Response|null, options: FetchOptions ) {

        let data = undefined;
        try {
            data = await response.json();
        } catch (_) {}

        let error_code = data?.error ?? null;
        const error_message = data?.error === 'message' ? (data?.message) ?? null : null;
        const success_data = data?.success ?? null;

        if (!response.ok || typeof data === "undefined" || (options.body_success && (!success_data || error_message))) {

            if (!response.ok) {
                switch (response.status) {
                    case 401: case 403:
                        window.location.href = $.ajax.getBaseURL();
                        throw null;
                    case 429: error_code = error_code ?? 1; break;
                    case 500: error_code = error_code ?? 3; break;
                    default: error_code = error_code ?? 'com'; break;
                }

                if (options.error_messages)
                    $.html.error(`${error_message ?? c.errors[error_code ?? 'com'] ?? c.errors['com']} (${response.status})`);
                throw error_code ?? 'com';
            }

            if (options.error_messages)
                $.html.error(`${error_message ?? c.errors[error_code ?? 'common'] ?? c.errors['common']}`);
            throw error_code ?? 'common';
        }

        this.handle_response_headers( response );
        return data;
    }

    public from( endpoint: string ) {
        const e = this.remove_slashes( endpoint );
        return new FetchOptionBuilder( e ? `${this.rest}/${e}` : this.rest,
            (r,opt) => this.preprocess_response(r,opt),
            (e,opt) => this.process_network_failure(e,opt) );
    }
}