// Make v1 API available
import {Const, Global} from "../defaults"
import {bool, string} from "prop-types";
import {SecureStorage} from "./security";
declare var c: Const;
declare const $: Global;

export interface AjaxV1Response {
    success: boolean|any,
    error?: number|string,
    message?: string,
}

class FetchCacheEntry {

    private content: any = null;
    private generator: (() => Promise<any>)|null = null;
    private resolvers: ((v:any) => void)[] = [];
    private rejectors: ((v:any) => void)[] = [];

    constructor( fn: () => Promise<any> ) {
        this.generator = fn;
    }

    private enhanceResponse(r: Response): Response {
        let json = null;
        let json_cached = false;
        let promise = null;
        let promises = [], rejectors = [];
        return new Proxy<Response>( r, {
            get(target: Response, p: string|symbol): any {
                if (p === 'json') {
                    return ()=>new Promise<any>((p,r) => {
                        if (json_cached) p(json);
                        else {
                            promises.push(p);
                            rejectors.push(r);
                            if (promise === null) {
                                promise = new Promise<any>(e =>
                                    target.json().then(v => {
                                        json_cached = true;
                                        promises.forEach(f=>f(json = v));
                                    }).catch(v => rejectors.forEach(f=>f(v)))
                                );
                            }
                        }
                    })
                }
                return target[p];
            }
        } )
    }

    /**
     * Returns a promise to the cached value. Will cause the promise generator to be executed on first access.
     */
    public get resolve(): Promise<any> {
        return new Promise<any>((resolve,reject) => {
            // If the generator is still cached, we have no result yet
            if (this.generator) {
                // Push the resolver / rejector to the queue
                this.resolvers.push(resolve);
                this.rejectors.push(reject);
                // If this function was called for the first time, execute the promise generator
                if (this.resolvers.length === 1) this.generator()
                    .then(result => {
                        // If the internal promise is resolved, store the result, delete the generator (as it is no
                        // longer needed) and execute all cached resolvers
                        const proxy = this.enhanceResponse(result);
                        this.content = proxy;
                        this.generator = null;
                        this.resolvers.forEach( f => f(proxy) );
                        this.resolvers = this.rejectors = [];
                    }).catch(result => {
                        // If the internal promise is rejected, execute all cached rejectors. Afterwards, a call to this
                        // function may re-run the promise generator
                        this.rejectors.forEach( f => f(result) );
                        this.resolvers = this.rejectors = [];
                    });
            // Without a generator, simply resolve to the cached data value
            } else resolve(this.content);
        });
    }
}

let fetch_catch = new Map<string,FetchCacheEntry>;

class FetchBuilder {

    private readonly url: string;
    private readonly params: URLSearchParams|null;
    private readonly request: RequestInit;
    private readonly f_then: (Response) => Promise<any>
    private readonly f_catch: (any) => Promise<any>
    private f_before: (()=>void)[]

    private cache_id: string|null = null;
    private use_cache: boolean = false;
    private send_token: boolean = false;

    constructor(url: string, headers: object|null, params: URLSearchParams|null, f_then: (Response) => Promise<any>, f_catch: (any) => Promise<any>) {
        this.url = url;
        this.params = params;
        this.f_then = f_then;
        this.f_catch = f_catch;
        this.f_before = [];

        this.request = {
            mode: "cors",
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(headers ?? {})
            },
            redirect: 'follow'
        }
    }


    private execute(method: string, body?: object): Promise<any> {
        this.f_before.map(fn=>fn());

        if (this.send_token) this.request.headers['X-Toaster'] = SecureStorage.token();

        const make_promise = () => fetch( this.params ? `${this.url}?${this.params}` : this.url, body ? {
            method,
            body: JSON.stringify( body ),
            ...this.request,
        } : {
            method,
            ...this.request
        } );

        // If cache is enable, we check if the same request has previously been executed. If not, we create a new cache
        // entry that will resolve itself as soon as it is accessed. Otherwise, we will return the existing entry (that
        // may already be pending or even resolved)
        if (this.use_cache) {
            const full_identifier = `${method}//${this.url}${body ? `//${JSON.stringify( body )}` : ''}${this.cache_id ? `//${this.cache_id}` : ''}`;
            if (fetch_catch.has( full_identifier ))
                return fetch_catch.get(full_identifier).resolve.then(this.f_then, this.f_catch);
            else {
                const entry = new FetchCacheEntry(make_promise);
                fetch_catch.set( full_identifier, entry );
                return entry.resolve.then(this.f_then, this.f_catch);
            }
        } else return make_promise().then(this.f_then, this.f_catch);
    }

    public withCache(identifier: string|null = null): FetchBuilder {
        this.use_cache = true;
        this.cache_id = identifier;
        return this;
    }

    public secure(): FetchBuilder {
        this.send_token = true;
        return this;
    }

    public before(fn: ()=>void): FetchBuilder { this.f_before.push( fn ); return this; }

    public get(): Promise<any> { return this.execute('GET'); }
    public delete(): Promise<any> { return this.execute('DELETE'); }
    public post(body?: object): Promise<any> { return this.execute('POST', body); }
    public patch(body?: object): Promise<any> { return this.execute('PATCH', body); }
    public put(body?: object): Promise<any> { return this.execute('PUT', body); }
    public method(method: string, body?: object): Promise<any> { return this.execute(method.toUpperCase(), body); }
}

class FetchOptions {
    public throw_response_on_error = false;
    public error_messages: boolean = true;
    public loader: boolean = true;
    public body_success: boolean = false;
    public add_xhr_header: boolean = false;
}

class FetchOptionBuilder {
    private readonly processor: (Response, FetchOptions) => Promise<any>;
    private readonly error_handler: (any, FetchOptions) => Promise<any>;
    private readonly url: string;
    private queryParams: URLSearchParams|null;
    private readonly options: FetchOptions;

    constructor(url: string, processor: (Response, FetchOptions) => Promise<any>, handler: (any, FetchOptions) => Promise<any>) {
        this.url = url;
        this.processor = processor;
        this.error_handler = handler;
        this.options = new FetchOptions();
        this.queryParams = null;
    }

    public throwResponseOnError(): FetchOptionBuilder { this.options.throw_response_on_error = true; return this; }
    public throwAliasCodeOnError(): FetchOptionBuilder { this.options.throw_response_on_error = false; return this; }

    public withErrorMessages(): FetchOptionBuilder { this.options.error_messages = true; return this; }
    public withoutErrorMessages(): FetchOptionBuilder { this.options.error_messages = false; return this; }

    public withLoader(): FetchOptionBuilder { this.options.loader = true; return this; }
    public withoutLoader(): FetchOptionBuilder { this.options.loader = false; return this; }

    public withXHRHeader(): FetchOptionBuilder { this.options.add_xhr_header = true; return this; }
    public withoutXHRHeader(): FetchOptionBuilder { this.options.add_xhr_header = false; return this; }

    public bodyDeterminesSuccess(b: boolean = true): FetchOptionBuilder { this.options.body_success = b; return this; }

    public param( name: string, value: any, condition: boolean = true ) {
        if (condition) {
            if (!this.queryParams) this.queryParams = new URLSearchParams();
            this.queryParams.set(name,value);
        }
        return this;
    }

    public request(): FetchBuilder {
        return new FetchBuilder(
            this.url,
            this.options.add_xhr_header ? {
                'X-Requested-With': 'XMLHttpRequest'
            }: null,
            this.queryParams,
            (r: Response) => this.processor(r, this.options),
            r => this.error_handler(r, this.options)
        );
    }
}

export class Fetch {

    private readonly rest: string = null;

    private remove_slashes(url: string): string {
        return url.match(/^\/?(.*?)\/?$/)[1];
    }

    constructor(rest_endpoint?: string, version: false|number = 1) {
        if (version === false) this.rest = rest_endpoint;
        else {
            const base_url = this.remove_slashes( document.querySelector('base[href]').getAttribute('href') ?? '' );
            this.rest = `${window.location.protocol}//${window.location.host}/${base_url ? `${base_url}/rest` : 'rest'}/v${version}/${this.remove_slashes( rest_endpoint ?? '' )}`;
        }
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

            throw options.throw_response_on_error ? null : error;
        }
    }

    private async preprocess_response( response: Response|null, options: FetchOptions ) {

        let data = undefined;
        try {
            data = await response.json();
        } catch (_) {}

        let error_code = data?.error ?? null;
        const success_data = data?.success ?? null;
        const error_message = (success_data !== false && !error_code) ? null : (error_code === 'message' || error_code === null) ? (data?.message) ?? null : null;



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

                if (options.error_messages && options.throw_response_on_error && error_message) {
                    $.html.error(error_message);
                    throw null;
                }

                if (options.error_messages && !options.throw_response_on_error)
                    $.html.error(error_message ?? c.errors[error_code ?? '__'] ?? `${c.errors['com']} (HTTP-${response.status})`);
                throw options.throw_response_on_error ? response : (error_code ?? 'com');
            }

            if (options.error_messages && options.throw_response_on_error && error_message) {
                $.html.error(error_message);
                throw null;
            }

            if (options.error_messages)
                $.html.error(`${error_message ?? c.errors[error_code ?? 'common'] ?? c.errors['common']}`);
            throw options.throw_response_on_error ? response : (error_code ?? 'common');
        }

        this.handle_response_headers( response );
        return data;
    }

    public from( endpoint: string, params: object = {} ) {
        const query = Object.entries(params)
            .filter(([key,value]) => value !== null && typeof value !== 'undefined')
            .map( ([key,value]) => `${key}=${encodeURIComponent(value)}` ).join('&');
        const e = this.remove_slashes( endpoint );

        const full_uri = this.rest +
            (e ? `/${e}` : '') +
            (query ? `?${query}` : '');

        return new FetchOptionBuilder(full_uri,
            (r,opt) => this.preprocess_response(r,opt),
            (e,opt) => this.process_network_failure(e,opt) );
    }

    public fromEndpoint( ) {
        return new FetchOptionBuilder( this.rest,
            (r,opt) => this.preprocess_response(r,opt),
            (e,opt) => this.process_network_failure(e,opt) );
    }
}