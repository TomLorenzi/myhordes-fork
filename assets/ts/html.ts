import {Global} from "./defaults";

import TwinoAlikeParser from "./twino"

declare var $: Global;

interface eventListener { (e: Event, element: HTMLElement, index: number): void }


export default class HTML {

    twinoParser: TwinoAlikeParser;

    constructor() { this.twinoParser = new TwinoAlikeParser(); }

    addEventListenerAll(query: string, event: string, handler: eventListener ): number {
        const elements = <NodeListOf<HTMLElement>>document.querySelectorAll(query);
        for (let i = 0; i < elements.length; i++)
            elements[i].addEventListener( event, function(e) { handler(e,elements[i],i) } );
        return elements.length;
    }

    serializeForm(form: ParentNode): object {
        let data: object = {};

        const input_fields = form.querySelectorAll('input');
        for (let i = 0; i < input_fields.length; i++) {
            const node_name = input_fields[i].getAttribute('name')
                ? input_fields[i].getAttribute('name')
                : input_fields[i].getAttribute('id');
            if (node_name && input_fields[i].getAttribute('type') != 'checkbox') {
                data[node_name] = input_fields[i].value;
            }
            if (node_name && input_fields[i].getAttribute('type') == 'checkbox') {
                data[node_name] = input_fields[i].checked;
            }
        }

        return data;
    }

    selectErrorMessage( code: string, messages: object, base: object, data: object = null ) {
        if (!code) code = 'default';
        if (code === 'message' && data.hasOwnProperty('message'))
            this.error( data['message'] );
        else if (messages && messages.hasOwnProperty(code)) {
            if (typeof messages[code] === 'function')
                this.error( messages[code](data) );
            else this.error( messages[code] );
        } else if ( base.hasOwnProperty(code) ) this.error( base[code] );
        else this.error( 'An error occurred. No further details are available.' );
    }

    message(label: string, msg: string ): void {
        let div = document.createElement('div');
        div.innerHTML = msg;
        div.classList.add( label );
        const f_hide = function() {
            div.classList.remove('show');
            setTimeout( function(node) { node.remove(); }, 500, div );
        };
        div.addEventListener('click', f_hide);
        let timeout_id = setTimeout( f_hide, 5000 );
        div.addEventListener('pointerenter', function() { clearTimeout(timeout_id); });
        div.addEventListener('pointerleave', function() { timeout_id = setTimeout( f_hide, 5000 ); });

        div = document.getElementById('notifications').appendChild( div );
        setTimeout( function(node) { node.classList.add('show'); }, 100, div );
    }

    error(msg: string): void {
        this.message('error',msg);
    }

    warning(msg: string): void {
        this.message('warning',msg);
    }

    notice(msg: string): void {
        this.message('notice',msg);
    }

    handleCountdown( element: Element ): void {
        let attr = parseInt(element.getAttribute('x-countdown'));
        const timeout = new Date( (new Date()).getTime() + 1000 * attr );

        const show_secs   = !element.getAttribute('x-countdown-no-seconds');
        const force_hours =  element.getAttribute('x-countdown-force-hours');
        const custom_handler = element.getAttribute('x-countdown-handler');
        let interval = element.getAttribute('x-countdown-interval');
        if (!interval) interval = '1000';

        const draw = function() {
            const seconds = Math.floor((timeout.getTime() - (new Date()).getTime())/1000);
            if (seconds < 0) return;

            const h = Math.floor(seconds/3600);
            const m = Math.floor((seconds - h*3600)/60);
            const s = seconds - h*3600 - m*60;

            if (custom_handler === 'pre' || custom_handler === 'handle') element.dispatchEvent(new CustomEvent('countdown', {detail: [seconds, h, m, s]}));
            if (!custom_handler || custom_handler === 'pre' || custom_handler === 'post')
                element.innerHTML =
                    ((h > 0 || force_hours) ? (h + ':') : '') +
                    ((h > 0 || force_hours) ? (m > 9 ? m : ('0' + m)) : m) +
                    (show_secs ? (':' + (s > 9 ? s : ('0' + s))) : '');
            if (custom_handler === 'post') element.dispatchEvent(new CustomEvent('countdown', {detail: [seconds, h, m, s]}));
        };

        const f = function(no_chk = false) {
            if (!no_chk && !document.body.contains(element)) return;
            if ((new Date() > timeout)) {
                if (custom_handler === 'pre' || custom_handler === 'handle') element.dispatchEvent(new CustomEvent('countdown', {detail: [-1,0,0,0]}));
                if (!custom_handler || custom_handler === 'pre' || custom_handler === 'post') element.innerHTML = '--:--';
                element.dispatchEvent(new Event("expire", { bubbles: true, cancelable: true }));
                if (custom_handler === 'post') element.dispatchEvent(new CustomEvent('countdown', {detail: [-1,0,0,0]}));
            }
            else {
                draw();
                window.setTimeout(f,parseInt(interval));
            }
        };

        f(true);
    }

    tt_counter: number = 0;

    clearTooltips( element: HTMLElement ): void {
        let container = document.getElementById('tooltip_container');
        let active_tts = container.querySelectorAll('[x-tooltip]');
        for (let i = 0; i < active_tts.length; i++) {
            let source = <HTMLElement>element.querySelector('[x-tooltip-source="' + active_tts[i].getAttribute('x-tooltip') + '"]');
            if (source) {
                source.append(active_tts[i]);
                source.style.display = 'none';
            }
        }
    }

    handleTooltip( element: HTMLElement): void {
        let parent = element.parentElement;
        if (!parent) return;

        let container = document.getElementById('tooltip_container');
        let current_id = ++this.tt_counter;

        element.setAttribute('x-tooltip', '' + current_id);
        parent.setAttribute('x-tooltip-source', '' + current_id);

        parent.addEventListener('contextmenu', function(e) {
           e.preventDefault();
        }, false);

        const fun_tooltip_pos = function(e: PointerEvent) {
            element.style.top  = e.clientY + 'px';

            // Make sure the tooltip does not exit the screen on the right
            // If it does, attach it left to the cursor instead of right
            if (e.clientX + element.clientWidth + 25 > window.innerWidth) {

                // Make sure the tooltip does not exit the screen on the left
                // If it does, center it on screen below the cursor
                if ( (e.clientX - element.clientWidth - 50) < 0 ) {
                    element.style.left = (window.innerWidth - element.clientWidth)/2 + 'px';
                } else element.style.left = (e.clientX - element.clientWidth - 50) + 'px';

            } else element.style.left = e.clientX + 'px';

        }

        const fun_tooltip_show = function(e: PointerEvent) {
            element.style.display = 'block';
            container.append( element );
            fun_tooltip_pos(e);
        }

        const fun_tooltip_hide = function(e: PointerEvent) {
            element.style.display = 'none';
            parent.append( element );
        }

        parent.addEventListener('pointerenter', fun_tooltip_show);
        parent.addEventListener('pointermove',  fun_tooltip_pos);
        parent.addEventListener('pointerleave', fun_tooltip_hide);
        parent.addEventListener('pointerup',    fun_tooltip_hide);
    };

    addLoadStack( num: number = 1): void {
        let loadzone = document.getElementById('loadzone');
        let current = parseInt(loadzone.getAttribute( 'x-stack' ));
        loadzone.setAttribute( 'x-stack', '' + Math.max(0,current+num) );
    }

    removeLoadStack( num: number = 1): void {
        this.addLoadStack(-num);
    }

    handleTabNavigation( element: Element ): void {

        const hide_group = function(group: string) {

            let targets = element.querySelectorAll('*[x-tab-target][x-tab-group=' + group + ']');
            for (let t = 0; t < targets.length; t++)
                (<HTMLElement>targets[t]).style.display = 'none';
        };

        let controllers = element.querySelectorAll('*[x-tab-control][x-tab-group]');
        for (let i = 0; i < controllers.length; i++) {

            const group = controllers[i].getAttribute('x-tab-group');

            hide_group(group);

            let buttons = controllers[i].querySelectorAll( '[x-tab-id]' );
            for (let b = 0; b < buttons.length; b++) {
                const id = buttons[b].getAttribute('x-tab-id');
                buttons[b].addEventListener('click', function () {
                    hide_group( group );
                    for (let bi = 0; bi < buttons.length; bi++) buttons[bi].classList.remove('selected');
                    buttons[b].classList.add('selected');
                    let selector = '*[x-tab-target][x-tab-group=' + group + ']';
                    if(id != 'all')
                        selector += '[x-tab-id= ' + id + ']';
                    let targets = element.querySelectorAll(selector);
                    for (let t = 0; t < targets.length; t++)
                        (<HTMLElement>targets[t]).style.display = null;
                    $.client.set( group, 'tabState', id, true );
                })
            }

            if (buttons.length > 0) {
                const pre_selection = controllers[i].getAttribute('x-tab-default') ? controllers[i].getAttribute('x-tab-default') : $.client.get( group, 'tabState' );
                let target: Element | null = null;
                if (pre_selection !== null)
                    target = controllers[i].querySelector( '[x-tab-id=' + pre_selection + ']' );
                if (target === null)
                    target = buttons[0];
                if (target !== null) target.dispatchEvent(new Event("click", {
                    bubbles: true, cancelable: true
                }));
            }

        }
    }
}