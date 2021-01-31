import {Const, Global} from "./defaults";

interface ajaxResponse { error: string, success: any }
interface ajaxCallback { (data: ajaxResponse, code: number): void }
interface ajaxStack    { (): void }

declare var c: Const;
declare var $: Global;

export default class Ajax {

    private readonly base: string;
    private defaultNode: HTMLElement;
    private no_load_spinner: boolean;
    private no_history_manipulation: boolean;

    private render_queue: Array<ajaxStack> = [];
    private render_block_stack: number = 0;

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

    private render( url: string, target: HTMLElement, result_document: Document, push_history: boolean, replace_history: boolean ) {
        // Get URL
        if (push_history) history.pushState( url, '', url );
        if (replace_history) history.replaceState( url, '', url );

        // Get content, style and script tags
        let content_source = result_document.querySelectorAll('html>body>:not(script):not(x-message)');
        let style_source = result_document.querySelectorAll('html>head>style');
        let script_source = result_document.querySelectorAll('script');
        let flash_source = result_document.querySelectorAll('x-message');

        // Get the ajax intention; assume "native" if no intention is given
        let ajax_html_elem = result_document.querySelector('html');
        let ajax_intention = ajax_html_elem ? ajax_html_elem.getAttribute('x-ajax-intention') : 'inline';
        ajax_intention = ajax_intention ? ajax_intention :  'native';

        // Clear the target
        $.html.clearTooltips( target );
        {let c; while ((c = target.firstChild)) target.removeChild(c);}

        let ajax_instance = this;

        // Move nodes from the AJAX document to the current document
        for (let i = 0; i < style_source.length; i++)
            target.appendChild( style_source[i] );
        for (let i = 0; i < content_source.length; i++) {
            let buttons = content_source[i].querySelectorAll('*[x-ajax-href]');
            for (let b = 0; b < buttons.length; b++) {
                buttons[b].addEventListener('click', function(e) {
                    e.preventDefault();
                    let target_desc = buttons[b].getAttribute('x-ajax-target');
                    let load_target = target_desc === 'default' ? ajax_instance.defaultNode : document.querySelector(target_desc) as HTMLElement;
                    if (load_target == undefined)
                        load_target = target;

                    let no_scroll = buttons[b].hasAttribute('x-ajax-sticky');

                    ajax_instance.load( load_target, buttons[b].getAttribute('x-ajax-href'), true, {}, () => {
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
                    if (next_section === 'finish') $.html.finishTutorialStage();
                    const list = next_section.split('.');
                    $.html.setTutorialStage(parseInt(list[0]), list[1]);
                }, {capture: true});
            }

            let countdowns = content_source[i].querySelectorAll('*[x-countdown]');
            for (let c = 0; c < countdowns.length; c++) {
                if ( countdowns[c].getAttribute('x-on-expire') === 'reload' )
                    countdowns[c].addEventListener('expire', function() { ajax_instance.load( target, url ) });
                $.html.handleCountdown( countdowns[c] );
            }

            let current_time = content_source[i].querySelectorAll("*[x-current-time]");
            for (let c = 0; c < current_time.length; c++) {
                $.html.handleCurrentTime( current_time[c] );
            }

            let tooltips = content_source[i].querySelectorAll('div.tooltip');
            for (let t = 0; t < tooltips.length; t++)
                $.html.handleTooltip( <HTMLElement>tooltips[t] );
            target.appendChild( content_source[i] );
            $.html.handleTabNavigation(target);
        }

        for (let i = 0; i < script_source.length; i++)
            try {
                eval(script_source[i].innerText);
            } catch (e) {
                $.html.error('A script on this page has crashed; details have been sent to the web console. The page may no longer work properly. Please report this issue: "' + e.message + '".');
                console.error(e,script_source[i].innerText);
            }


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

        $.html.restoreTutorialStage();
    }

    push_history( url: string ) {
        url = this.prepareURL(url);
        history.pushState( url, '', url );
    }

    load( target: HTMLElement, url: string, push_history: boolean = false, data: object = {}, callback: ajaxStack|null = null ) {
        let ajax_instance = this;

        if (!(target = this.prepareTarget( target ))) return;
        url = this.prepareURL(url);

        const no_hist    = this.fetch_no_history();
        const no_loader  = this.fetch_no_loader();
        if (push_history) history.pushState( url, '', url );

        if (!no_loader) $.html.addLoadStack();
        let request = new XMLHttpRequest();
        request.responseType = 'document';
        request.addEventListener('load', function(e) {
            // Check if a reset header is set
            switch ( this.getResponseHeader('X-AJAX-Control') ) {
                case 'reset':
                    window.location.href = ajax_instance.base;
                    return;
                case 'reload':
                    window.location.reload();
                    return;
                case 'cancel':
                    return;
                case 'process': default: break;
            }

            if (this.status >= 400) {

                switch (this.status) {
                    case 403:
                        console.log(target === ajax_instance.defaultNode,url);
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

            if (ajax_instance.render_block_stack > 0) {
                const r_url = this.responseURL;
                const r_xml = this.responseXML;
                ajax_instance.render_queue.push( function() { ajax_instance.render( r_url, target, r_xml, false, !no_hist ) } );
            } else ajax_instance.render( this.responseURL, target, this.responseXML, false, !no_hist );

            if (callback) callback();

            if (!no_loader) $.html.removeLoadStack();
        });
        request.addEventListener('error', function(e) {
            alert('Error loading page.');
            if (!no_loader) $.html.removeLoadStack();
        });
        request.open('POST', url);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-Request-Intent', 'WebNavigation');
        request.setRequestHeader('X-Request-Intent', 'WebNavigation');

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

        if (!no_loader) $.html.addLoadStack();
        let request = new XMLHttpRequest();
        request.responseType = 'json';
        request.addEventListener('load', function(e) {
            switch ( this.getResponseHeader('X-AJAX-Control') ) {
                case 'reset':
                    window.location.href = base;
                    return;
                case 'reload':
                    window.location.reload();
                    return;
                case 'cancel':
                    return;
                case 'process': default: break;
            }
            callback( this.response, this.status );
            if (!no_loader) $.html.removeLoadStack();
        });
        request.addEventListener('error', function(e) {
            alert('Error transferring data.');
            if (!no_loader) $.html.removeLoadStack();
        });
        request.open('POST', url);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-Request-Intent', 'JSONDataExchange');
        request.setRequestHeader('Content-Type', 'application/json');
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