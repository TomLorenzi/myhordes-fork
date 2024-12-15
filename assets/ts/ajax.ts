import {Const, Global} from "./defaults";
import {dataDrivenFunctions} from "./v2/applicator";
import {SecureStorage} from "./v2/security";
import {EventConnector} from "./v2/events";

interface ajaxResponse { error: string, success: any }
interface ajaxCallback { (data: ajaxResponse, code: number): void }
interface ajaxStack    { (): void }
interface navigationPromiseCallback { (any): void }

declare var c: Const;
declare var $: Global;

export default class Ajax {

    private readonly base: string;
    private defaultNode: HTMLElement;
    private no_load_spinner: boolean;
    private no_history_manipulation: boolean;
    private no_connection_error_message: boolean;

    private render_queue: Array<ajaxStack> = [];
    private render_block_stack: number = 0;
    private render_block_promises: Array<navigationPromiseCallback> = []

    private known_dynamic_modules: Array<string> = []

    constructor(baseUrl: string) {
        if (baseUrl.length == 0 || baseUrl.slice(-1) != '/')
            baseUrl += '/';
        this.base = baseUrl;
        this.defaultNode = null;
    }

    getBaseURL(): string {
        return this.base;
    }

    setDefaultNode( target: HTMLElement ) {
        this.defaultNode = target;
    }

    no_history(): Ajax {
        this.no_history_manipulation = true;
        return this;
    }

    no_loader(): Ajax {
        this.no_load_spinner = true;
        return this;
    }

    soft_fail(): Ajax {
        this.no_connection_error_message = true;
        return this;
    }

    background(): Ajax {
        this.no_history_manipulation = this.no_load_spinner = true;
        return this;
    }

    push_renderblock(): Ajax {
        this.render_block_stack++;
        return this;
    }

    pop_renderblock(): Ajax {
        this.render_block_stack--;
        if (this.render_block_stack < 0) this.render_block_stack = 0;
        while ( this.render_queue.length > 0 && this.render_block_stack == 0 )
            this.render_queue.shift()();
        while ( this.render_block_promises.length > 0 && this.render_block_stack == 0 )
            this.render_block_promises.shift()(true);
        return this;
    }

    private fetch_no_loader(): boolean {
        const r = this.no_load_spinner;
        this.no_load_spinner = false;
        return r;
    }

    private fetch_no_history(): boolean {
        const r = this.no_history_manipulation;
        this.no_history_manipulation = false;
        return r;
    }

    private fetch_soft_fail(): boolean {
        const r = this.no_connection_error_message;
        this.no_connection_error_message = false;
        return r;
    }

    private prepareURL(url: string): string {
        if (url.slice(0,4) !== 'http' && url.slice(0,this.base.length) !== this.base) url = this.base + url;
        return url;
    }

    private prepareTarget(target: HTMLElement): HTMLElement {
        if (target === null) target = this.defaultNode;
        if (target === null) {
            alert('ERROR_NO_TARGET_NODE');
            return null;
        }
        return target;
    }

    private fetch_module(src: string) {
        const existing = document.querySelector('head>script[src="' + src + '"]');
        if (!existing && !this.known_dynamic_modules.includes( src )) {
            console.debug( 'Loading dynamic JS module: ' + src );
            this.known_dynamic_modules.push(src);
            fetch( src, {mode:"no-cors"} )
                .then( response => response.ok ? response.text() : new Promise(r=>r(null)) )
                .then( script => {
                    if (!script) $.html.error(c.errors['dyn_script_no'] + '<br /><code>' + src + '</code>');
                    else try {
                        eval(script as string);
                    } catch (e) {
                        $.html.error(c.errors['dyn_script'] + '<br /><code>' + src + ': ' + e.message + '</code>');
                        console.error(e,script);
                    }
                } )
        }
    }

    private setNetworkOffset( date: string ) {
        const serverDate = new Date(date);
        const localDate = new Date();

        if (c) c.ot = serverDate.getTime() - localDate.getTime();
    }

    public networkTimeOffset( ): number {
        return c?.ot ?? 0;
    }

    public load_dynamic_modules(target: Document|HTMLElement) {
        // Check content source for non-defined nodes
        target.querySelectorAll(':not(:defined)').forEach( e =>
            // @ts-ignore
            ((window.c?.modules ?? {})[e.localName] ?? []).forEach( src => this.fetch_module(src) )
        );
    }

    private render( url: string, target: HTMLElement, result_document: Document, push_history: boolean, replace_history: boolean ) {
        // Get URL
        if (push_history) history.pushState( url, '', url );
        if (replace_history) history.replaceState( url, '', url );

        // If there is a CLEAR target, remove content from the targeted elements
        let fragment = null;
        while (fragment = result_document.querySelector<HTMLElement>('[x-clear-target]')) {
            $.html.forEach( fragment.getAttribute('x-clear-target'), elem => elem.innerHTML = '' );
            fragment.remove();
        }

        // First move content with a specific TARGET selector
        while (fragment = result_document.querySelector<HTMLElement>('[x-render-target]')) {
            let frag_target = document.querySelector<HTMLElement>( fragment.getAttribute('x-render-target') );
            if (!frag_target) console.warn('Rendered HTML contains an invalid fragment target: ', frag_target, ' Discarding.')
            else {
                (fragment.dataset.renderTargetClassesToggle?.split(';') || []).forEach(opt => {
                    let [c,o] = opt.split(',');
                    frag_target.classList.toggle( c, o === '1' );
                });

                const frag_doc = document.implementation.createHTMLDocument('');
                while (fragment.children.length > 0)
                    frag_doc.body.appendChild( fragment.children.item(0) );
                this.render( url, frag_target, frag_doc, false, false );
            }
            fragment.remove();
        }

        this.load_dynamic_modules(result_document);

        // Get content, style and script tags
        let content_source = result_document.querySelectorAll('html>body>:not(script):not(x-message)');
        let style_source = result_document.querySelectorAll('html>head>style');
        let script_source = result_document.querySelectorAll('script');
        let react_mounts = {}
        $.html.forEach('[id][data-react-mount]', c => {
            react_mounts[c.id] = c;
        }, result_document)

        // Disable elements that are waiting for a custom element class to become available
        result_document.querySelectorAll('[data-await-custom-element]').forEach( (e: HTMLElement) => {
            if (!customElements.get( e.dataset.awaitCustomElement )) {
                e.setAttribute('disabled','disabled');
                customElements.whenDefined( e.dataset.awaitCustomElement ).then(() => e.removeAttribute('disabled'))
            }
        } );

        // Merge flash messages
        let message_stack_changed = true, flash_source = null;
        do {
            message_stack_changed = false;
            flash_source = result_document.querySelectorAll<HTMLElement>('x-message');
            for (let i = 0; i < flash_source.length; i++)
                if (flash_source[i].getAttribute('x-label') === 'collapse') {
                    message_stack_changed = true;

                    if (i > 0) flash_source[i-1].innerHTML += '<hr/>' + flash_source[i].innerHTML
                    else if (i < (flash_source.length-1)) flash_source[i+1].innerHTML += '<hr/>' + flash_source[i].innerHTML
                    else {
                        message_stack_changed = false;
                        flash_source[i].setAttribute('x-label', 'notice');
                        break;
                    }

                    flash_source[i].remove();
                    break;
                }
        } while (message_stack_changed);

        // Get the ajax intention; assume "native" if no intention is given
        let ajax_html_elem = result_document.querySelector('html');
        let ajax_intention = ajax_html_elem ? ajax_html_elem.getAttribute('x-ajax-intention') : 'inline';
        ajax_intention = ajax_intention ? ajax_intention :  'native';

        // React container cache
        let container_cache = {};

        // Save react mounts
        $.html.forEach( '[id][data-react-mount]', c => {
            if (react_mounts[c.id]) {
                for (const [key, value] of Object.entries(react_mounts[c.id].dataset))
                    if (key !== 'react' && key !== 'reactMount')
                        { // @ts-ignore
                            c.dataset[key] = value;
                        }
                react_mounts[c.id].parentElement.insertBefore( c, react_mounts[c.id] );
                react_mounts[c.id].remove();
                react_mounts[c.id] = c;
            } else c.dispatchEvent(new Event("x-react-degenerate", { bubbles: true, cancelable: false }));
        }, target );

        // Clear the target
        {let c; while ((c = target.firstChild)) target.removeChild(c);}

        let ajax_instance = this;

        // Move nodes from the AJAX document to the current document
        for (let i = 0; i < style_source.length; i++)
            target.appendChild( style_source[i] );
        for (let i = 0; i < content_source.length; i++) {
            let buttons = content_source[i].querySelectorAll('*[x-ajax-href]');
            for (let b = 0; b < buttons.length; b++) {
                buttons[b].addEventListener('click', function(e) {

                    if (e.target.dataset.fetch) return;

                    e.preventDefault();
                    let target_desc = buttons[b].getAttribute('x-ajax-target');
                    let load_target = null;

                    if (target_desc === '_blank') {
                        window.open(buttons[b].getAttribute('x-ajax-href'), '_blank');
                        return;
                    }
                    else if (target_desc === 'parent') load_target = target;
                    else if (!target_desc || target_desc === 'default') load_target = ajax_instance.defaultNode;
                    else load_target = document.querySelector(target_desc) as HTMLElement;

                    if (!load_target) {
                        console.warn('Unable to determine a DOM target for the active ajax href. Falling back to the DOM target the href was originally loaded in. This will likely break the site, consider reloading.');
                        load_target = target;
                    }

                    let no_scroll = buttons[b].hasAttribute('x-ajax-sticky');
                    let no_history = buttons[b].hasAttribute('x-ajax-transient');
                    ajax_instance.load( load_target, buttons[b].getAttribute('x-ajax-href'), !no_history, {}, () => {
                        if (!no_scroll) window.scrollTo(0, 0);
                    } )
                }, {once: true, capture: true});
                buttons[b].addEventListener('mousedown', function(e: MouseEvent) {
                    if (e.button === 1) {
                        e.preventDefault();
                        window.open(buttons[b].getAttribute('x-ajax-href'), '_blank');
                    }
                });
            }

            let tutorials = content_source[i].querySelectorAll('*[x-advance-tutorial]');
            for (let t = 0; t < tutorials.length; t++) {
                tutorials[t].addEventListener('click', function(e) {
                    e.preventDefault();

                    const next_section = tutorials[t].getAttribute('x-advance-tutorial');

                    const conditionals =  next_section.split('>');
                    const from = conditionals.length > 1 ? conditionals[0].split('.') : [];
                    const to   = conditionals.length > 1 ? conditionals[1].split('.') : conditionals[0].split('.');

                    if (from.length > 0) {
                        if (to.length === 1 && (to[0] === 'finish' || to[0] === 'complete')) $.html.conditionalFinishTutorialStage(parseInt(from[0]), from[1], to[0] === 'complete');
                        else $.html.conditionalSetTutorialStage( parseInt(from[0]), from[1], parseInt(to[0]), to[1] );
                    } else {
                        if (to.length === 1 && (to[0] === 'finish' || to[0] === 'complete')) $.html.finishTutorialStage(to[0] === 'complete');
                        else $.html.setTutorialStage( parseInt(to[0]), to[1] );
                    }
                }, {capture: true});
            }

            let conditional_help = content_source[i].querySelectorAll<HTMLElement>('*[x-conditional-help]');
            let hidden = $.client.config.hiddenConditionalHelp.get();
            for (let t = 0; t < conditional_help.length; t++) {
                if (hidden.includes(conditional_help[t].getAttribute('x-conditional-help')))
                    conditional_help[t].style.display = 'none';
            }

            let conditional_help_ctrl = content_source[i].querySelectorAll('*[x-confirm-conditional-help]');
            for (let t = 0; t < conditional_help_ctrl.length; t++) {
                conditional_help_ctrl[t].addEventListener('click', function(e) {
                    e.preventDefault();

                    const section = conditional_help_ctrl[t].getAttribute('x-confirm-conditional-help')
                    $.html.forEach('[x-conditional-help="' + section + '"]', function (elem) {
                        elem.style.display = 'none';
                    });

                    if (!conditional_help_ctrl[t].getAttribute('x-temp')) {
                        let hidden = $.client.config.hiddenConditionalHelp.get();
                        if (!hidden.includes(section)) {
                            hidden.push(section);
                            $.client.config.hiddenConditionalHelp.set(hidden);
                        }
                    }

                }, {capture: true});
            }

            let countdowns = content_source[i].querySelectorAll('*[x-countdown],*[x-countdown-to]');
            for (let c = 0; c < countdowns.length; c++) {
                if ( countdowns[c].getAttribute('x-on-expire') === 'reload' )
                    countdowns[c].addEventListener('expire', function() { ajax_instance.load( target, url ) });
                $.html.handleCountdown( countdowns[c] );
            }

            $.html.handleCollapseSection( content_source[i] as HTMLElement );

            content_source[i].querySelectorAll('*[x-current-time]').forEach( elem => $.html.handleCurrentTime( <HTMLElement>elem, parseInt(elem.getAttribute('x-current-time')) ))
            content_source[i].querySelectorAll('div.tooltip')      .forEach( elem => $.html.handleTooltip( <HTMLElement>elem ))
            content_source[i].querySelectorAll('*[x-tooltip]')     .forEach( elem => $.html.createTooltip( <HTMLElement>elem ))
            content_source[i].querySelectorAll('.username')        .forEach( elem => $.html.handleUserPopup( <HTMLElement>elem ))
            target.appendChild( content_source[i] );
        }

        for (let i = 0; i < script_source.length; i++)
            try {
                if (script_source[i].hasAttribute('src'))
                    this.fetch_module( script_source[i].getAttribute('src') );
                else eval(script_source[i].innerText);
            } catch (e) {
                $.html.error(c.errors['script'] + '<br /><code>' + e.message + '</code>');
                console.error(e,script_source[i].innerText);
            }

        $.html.handleTabNavigation(target);

        target.querySelectorAll('[data-search-table]').forEach( (table: HTMLElement) => $.html.makeSearchTable(table) );

        for (let i = 0; i < flash_source.length; i++)
            $.html.message( flash_source[i].getAttribute('x-label'), flash_source[i].innerHTML );

        // If ajax intention is 'native', trigger a DOMContentLoaded event on the document
        if (ajax_intention === 'native')
            window.document.dispatchEvent(new Event("DOMContentLoaded", {
                bubbles: true, cancelable: true
            }));
        window.dispatchEvent(new Event("resize", {
            bubbles: true, cancelable: true
        }));

        $.components.prune();
        $.html.restoreTutorialStage();

        dataDrivenFunctions( target );
        EventConnector.handle( target );
    }

    push_history( url: string ) {
        url = this.prepareURL(url);
        history.pushState( url, '', url );
    }

    load( target: HTMLElement, url: string, push_history: boolean = false, data: object = {}, callback: ajaxStack|null = null ) {
        let ajax_instance = this;

        if (!(target = this.prepareTarget( target ))) return;
        url = this.prepareURL(url);

        const no_hist    = this.fetch_no_history() || !push_history;
        const no_loader  = this.fetch_no_loader();
        const no_error  = this.fetch_soft_fail();

        if (!no_hist) this.push_history(url);
        let this_promise;
        document.dispatchEvent( new CustomEvent('mh-navigation-begin', {detail: {url: url, post: data, node: target, complete: new Promise((resolve,reject) => {
            this_promise = [resolve,reject]
        })}}) );

        const sno = d => this.setNetworkOffset( d );

        if (!no_loader) $.html.addLoadStack();
        let request = new XMLHttpRequest();
        request.responseType = 'document';
        request.addEventListener('load', function(e) {
            sno( this.getResponseHeader('Date') );
            // Check if a reset header is set
            switch ( this.getResponseHeader('X-AJAX-Control') ) {
                case 'reset':
                    window.location.href = ajax_instance.base;
                    return;
                case 'navigate':
                    window.location.href = this.getResponseHeader('X-AJAX-Navigate') ?? ajax_instance.base;
                    return;
                case 'reload':
                    window.location.reload();
                    return;
                case 'cancel':
                    return;
                case 'process': default: break;
            }

            if (this.getResponseHeader('X-Session-Domain')) {
                const [p = '0', v1 = '0', v2 = '0', v3 = '0'] = this.getResponseHeader('X-Session-Domain').split(':');
                $.client.setSessionDomain(parseInt(p),parseInt(v1),parseInt(v2),parseInt(v3));
            }

            if (this.status >= 400) {
                this_promise[1]();
                switch (this.status) {
                    case 403:
                        if (target === ajax_instance.defaultNode)
                            $.client.config.navigationCache.set(url);
                        window.location.href = ajax_instance.base; break;
                    case 500: break;
                    default:
                        alert('Error loading page (' + this.status + ')');
                        window.location.href = ajax_instance.base;
                        break;
                }

                return;
            }

            document.querySelectorAll('[x-ajax-volatile="any"]').forEach(function (elem) {
                elem.remove();
            });

            if (ajax_instance.render_block_stack > 0) {
                const r_url = this.responseURL;
                const r_xml = this.responseXML;
                ajax_instance.render_queue.push( function() { ajax_instance.render( r_url, target, r_xml, false, !no_hist ) } );
            } else ajax_instance.render( this.responseURL, target, this.responseXML, false, !no_hist );

            if (callback) callback();

            let event = {url: url, post: data, node: target, render: new Promise(resolve => {
                if (ajax_instance.render_block_stack === 0) resolve(true);
                else ajax_instance.render_block_promises.push( resolve )
            })}
            this_promise[0](event);
            document.dispatchEvent( new CustomEvent('mh-navigation-complete', {detail: event}) );

            if (!no_loader) $.html.removeLoadStack();
        });
        request.addEventListener('error', function(e) {
            if (!no_error) alert('Error loading page.');
            if (!no_loader) $.html.removeLoadStack();
        });
        request.open('POST', url);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-Request-Intent', 'WebNavigation');
        request.setRequestHeader('X-Toaster', SecureStorage.partial_token());

        const target_id = target.getAttribute('x-target-id') ?? target.getAttribute('id') ?? '';
        if (target_id) request.setRequestHeader('X-Render-Target', target_id);

        request.setRequestHeader('Content-Type', 'application/json');
        request.send( JSON.stringify(data) );
    };

    send( url: string, data: object, callback: ajaxCallback ) {
        url = this.prepareURL(url);
        const base = this.base;

        const no_hist   = this.fetch_no_history();
        const no_loader = this.fetch_no_loader();
        const no_error  = this.fetch_soft_fail();

        const sno = d => this.setNetworkOffset( d )

        if (!no_loader) $.html.addLoadStack();
        let request = new XMLHttpRequest();
        request.responseType = 'json';
        request.addEventListener('load', function(e) {
            sno( this.getResponseHeader('Date') );
            switch ( this.getResponseHeader('X-AJAX-Control') ) {
                case 'reset':
                    window.location.href = base;
                    return;
                case 'navigate':
                    window.location.href = this.getResponseHeader('X-AJAX-Navigate') ?? base;
                    return;
                case 'reload':
                    window.location.reload();
                    return;
                case 'cancel':
                    return;
                case 'process': default: break;
            }

            if (this.getResponseHeader('X-Session-Domain')) {
                const [p = '0', v1 = '0', v2 = '0', v3 = '0'] = this.getResponseHeader('X-Session-Domain').split(':');
                $.client.setSessionDomain(parseInt(p),parseInt(v1),parseInt(v2),parseInt(v3));
            }

            callback( this.response, this.status );
            if (!no_loader) $.html.removeLoadStack();
        });
        request.addEventListener('error', function(e) {
            if (!no_error) alert('Error transferring data.');
            else callback(this.response, 1 );
            if (!no_loader) $.html.removeLoadStack();
        });
        request.open('POST', url);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-Request-Intent', 'JSONDataExchange');
        request.setRequestHeader('X-Toaster', SecureStorage.partial_token());
        request.setRequestHeader('Content-Type', 'application/json');
        request.setRequestHeader('Accept', 'application/json');
        request.send( JSON.stringify(data) );
    };

    easySend( url: string, data: object, success: ajaxCallback, errors: object = null, error: ajaxCallback|null = null, handleErrors: boolean = true ) {
        this.send( url, data,function (data: ajaxResponse, code) {
            if (code < 200 || code >= 300) {
                if (handleErrors && !error) $.html.selectErrorMessage( 'com', {}, c.errors );
                if (error) error(null,code);
            } else if (data.error) {
                if (handleErrors && !error) $.html.selectErrorMessage( data.error, errors, c.errors, data );
                if (error) error(data,code);
            } else if (data.success)
                success(data,code);
            else {
                if (handleErrors && !error) $.html.selectErrorMessage('default', errors, c.errors, data);
                if (error) error(null,null);
            }
        } );
    }
}