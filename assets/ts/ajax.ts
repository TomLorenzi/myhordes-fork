import {Const, Global} from "./defaults";

interface ajaxResponse { error: string, success: any }
interface ajaxCallback { (data: ajaxResponse, code: number): void }

declare var c: Const;
declare var $: Global;

export default class Ajax {

    private readonly base: string;
    private defaultNode: HTMLElement;
    private no_load_spinner: boolean;
    private no_history_manipulation: boolean;

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
        let ajax_intention = result_document.querySelector('html').getAttribute('x-ajax-intention');
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
            for (let b = 0; b < buttons.length; b++)
                buttons[b].addEventListener('click', function(e) {
                    e.preventDefault();
                    ajax_instance.load( target, buttons[b].getAttribute('x-ajax-href'), true )
                }, {once: true, capture: true});
            let countdowns = content_source[i].querySelectorAll('*[x-countdown]');
            for (let c = 0; c < countdowns.length; c++) {
                if ( countdowns[c].getAttribute('x-on-expire') === 'reload' )
                    countdowns[c].addEventListener('expire', function() { ajax_instance.load( target, url ) });
                $.html.handleCountdown( countdowns[c] );
            }
            let tooltips = content_source[i].querySelectorAll('div.tooltip');
            for (let t = 0; t < tooltips.length; t++)
                $.html.handleTooltip( <HTMLElement>tooltips[t] );
            target.appendChild( content_source[i] );
            $.html.handleTabNavigation(target);
        }

        for (let i = 0; i < script_source.length; i++)
            eval(script_source[i].innerText);

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
    }

    push_history( url: string ) {
        url = this.prepareURL(url);
        history.pushState( url, '', url );
    }

    load( target: HTMLElement, url: string, push_history: boolean = false, data: object = {} ) {
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
            if (this.getResponseHeader('X-AJAX-Control') === 'reset') {
                window.location.href = ajax_instance.base;
                return;
            }

            if (this.status >= 400) {
                alert('Error loading page (' + this.status + ')');
                window.location.href = ajax_instance.base;
                return;
            }

            ajax_instance.render( this.responseURL, target, this.responseXML, false, !no_hist );
            if (!no_loader) $.html.removeLoadStack();
        });
        request.addEventListener('error', function(e) {
            alert('Error loading page.');
            if (!no_loader) $.html.removeLoadStack();
        });
        request.open('POST', url);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-Request-Intent', 'WebNavigation');
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

    easySend( url: string, data: object, success: ajaxCallback, errors: object = null, error: ajaxCallback|null = null ) {
        this.send( url, data,function (data: ajaxResponse, code) {
            if (code < 200 || code >= 300) {
                $.html.selectErrorMessage( 'com', {}, c.errors );
                if (error) error(null,code);
            } else if (data.error) {
                $.html.selectErrorMessage( data.error, errors, c.errors, data );
                if (error) error(data,code);
            } else if (data.success)
                success(data,code);
            else $.html.selectErrorMessage( 'default', errors, c.errors, data );
        } );
    }
}