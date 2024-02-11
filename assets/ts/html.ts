import {Const, Global} from "./defaults";

import TwinoAlikeParser from "./twino"
import HordesTwinoEditorElement from "./modules/twino-editor";

declare var $: Global;
declare var c: Const;

interface elementHandler { (element: HTMLElement, index: number): void }
interface eventListener { (e: Event, element: HTMLElement, index: number): void }

class _SearchTableColumnProps {

    private readonly _name: string;
    private readonly _fq_name: string;
    private readonly _numeric: boolean;

    constructor( table: HTMLElement, name: string ) {
        let fq = "";
        name.split('-').forEach( c => fq += (c.substr(0,1).toUpperCase() + c.substr(1).toLowerCase()) );

        this._name = name;
        this._fq_name = fq;
        this._numeric = typeof table.dataset['searchProp' + this._fq_name + "AttrNumeric"] !== "undefined";
    }

    get name(): string { return this._name; }
    get fullyQualifiedName(): string { return "searchProp" + this._fq_name; }
    get isNumeric(): boolean { return this._numeric; }

    get sortAscFunction(): (a: HTMLElement, b: HTMLElement) => number {
        if (this.isNumeric) return (a: HTMLElement, b: HTMLElement) => (parseFloat(a.dataset[this.fullyQualifiedName]) - parseFloat(b.dataset[this.fullyQualifiedName])) || (parseInt(a.dataset['searchRow']) - parseInt(b.dataset['searchRow']));
        else return (a: HTMLElement, b: HTMLElement) => a.dataset[this.fullyQualifiedName].localeCompare( b.dataset[this.fullyQualifiedName] ) || (parseInt(a.dataset['searchRow']) - parseInt(b.dataset['searchRow']));
    }

    get sortDescFunction(): (a: HTMLElement, b: HTMLElement) => number {
        return (a: HTMLElement, b: HTMLElement) => -this.sortAscFunction(a,b);
    }

    filterFunction(query: string): (a: HTMLElement) => boolean {
        return query === '' ? (()=>true) : ((a: HTMLElement) => a.dataset[this.fullyQualifiedName].toLowerCase().includes(query.toLowerCase()));
    }
}

class HTMLInitParams {
    userPopupEndpoint: string;
    userPopupLoadingAnimation: string;
}

export default class HTML {

    twinoParser: TwinoAlikeParser;

    private tutorialStage: [number,string] = null;

    private title_segments: [number,string,string|null] = [0,'MyHordes',null];
    private title_timer: number = null;
    private title_alt: boolean = false;

    private initParams: HTMLInitParams = null;

    constructor() { this.twinoParser = new TwinoAlikeParser(); }

    init(): void {
        document.getElementById('modal-backdrop')?.addEventListener('pop', () => this.nextPopup())
    }

    setInitParams( params: HTMLInitParams ): void {
        this.initParams = params;
    }

    forEach( query: string, handler: elementHandler, parent: HTMLElement|Document = null ): number {
        const elements = <NodeListOf<HTMLElement>>(parent ?? document).querySelectorAll(query);
        for (let i = 0; i < elements.length; i++) handler(elements[i],i);
        return elements.length;
    }

    addEventListenerAll(query: string, event: string, handler: eventListener, trigger_now: boolean = false, capture: boolean = false ): number {
        return this.forEach( query, (elem, i) => {
            elem.addEventListener( event, e => handler(e,elem,i), capture );
            if (trigger_now) handler(new Event('event'), elem, i);
        } )
    }

    createElement(html: string, textContent: string|null = null ): HTMLElement {
        const e = document.createElement('div');
        e.innerHTML = html;
        if (textContent !== null) (<HTMLElement>(e.firstChild)).innerText = textContent;
        return <HTMLElement>e.firstChild;
    }

    serializeForm(form: ParentNode): object {
        let data: object = {};

        const input_fields = form.querySelectorAll('input,select,hordes-twino-editor') as NodeListOf<HTMLInputElement|HTMLSelectElement|HordesTwinoEditorElement>;
        for (let i = 0; i < input_fields.length; i++) {
            const node = input_fields[i];
            const node_name = node.getAttribute('name') ?? node.getAttribute('id');
            const node_type = node.dataset['type'] ?? node.getAttribute('type') ?? 'text';

            // Do not enter react parents!
            const blacklisted = node.closest('hordes-twino-editor');
            if (blacklisted && blacklisted !== node) continue;

            if (node_name) {
                switch (node.nodeName) {
                    case 'INPUT':
                        data[node_name] = node.getAttribute('type') != 'checkbox'
                            ? (node as HTMLInputElement).value
                            : (node as HTMLInputElement).checked;
                        break;
                    case 'SELECT':
                        data[node_name] = (node as HTMLSelectElement).value;
                        break;
                    case 'HORDES-TWINO-EDITOR':
                        data[node_name] = node_type === "twino" ? (node as HordesTwinoEditorElement).twino : (node as HordesTwinoEditorElement).html;
                        break;
                }

                switch (node_type) {
                    case 'number':
                        data[node_name] = parseFloat( data[node_name] );
                        break;
                }
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
        } else if ( base.hasOwnProperty(code) ) {

            if (typeof base[code] === 'function')
                this.error( base[code](data) );
            else {
                let str = base[code];
                for(let prop in data){
                    str = str.replace('{' + prop + '}', data[prop]);
                }
                this.error( str );
            }
        }
        else this.error( c.errors['common'] );
    }

    message(label: string, msg: string ): void {

        const is_popup = label.substr(0, 6) === 'popup-';

        if ($.client.config.notificationAsPopup.get() || is_popup) {

            this.popup('',msg, 'popup-' + (is_popup ? label.substr(6) : label), [
                [window['c'].label.confirm, [['click', (e,elem) => $.html.triggerPopupPop(elem)]]]
            ]);

        } else {

            const notification_parent = document.getElementById('notifications');
            if (!notification_parent) {
                console.error('Notification area is unavailable!');
                return;
            }

            let div = document.createElement('div');
            div.innerHTML = msg;
            div.classList.add( label );
            const f_hide = function() {
                div.classList.remove('show');
                div.classList.add('disappear');
                div.style.marginTop = '-' + div.clientHeight + 'px';
                setTimeout( node => node.remove(), 500, div );
            };
            div.addEventListener('click', f_hide);
            let timeout_id = setTimeout( f_hide, 5000 );
            div.addEventListener('pointerenter', () => clearTimeout(timeout_id) );
            div.addEventListener('pointerleave', () => timeout_id = setTimeout( f_hide, 5000 ) );

            div = notification_parent.appendChild( div );
            setTimeout( node => node.classList.add('show'), 1, div );

            let n = notification_parent.children.length - ((window.innerWidth < 600) ? 1 : 3);
            let fc = notification_parent.firstChild;

            while (n > 0 && fc) {
                fc.dispatchEvent( new Event( 'click' ) );
                fc = fc.nextSibling;
                --n;
            }
        }


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

    private popupStack: Array<[string,string|HTMLElement,string|null,Array<[string,Array<[string,eventListener]>]>,boolean]> = [];
    private popupOpen: boolean = false;


    triggerPopupPop( e: HTMLElement ) {
        e.dispatchEvent(new Event('pop', { bubbles: true, cancelable: false } ));
    }

    private nextPopup():void {
        let elem_modal    = document.getElementById('modal');
        let elem_title    = document.getElementById('modal-title');
        let elem_content  = document.getElementById('modal-content');
        let elem_backdrop = document.getElementById('modal-backdrop');
        let elem_actions  = document.getElementById('modal-actions');

        elem_title.innerHTML = elem_content.innerHTML = elem_actions.innerHTML = elem_modal.className = '' ;

        if (this.popupStack.length === 0) {
            elem_backdrop.classList.remove('active');
            elem_modal.classList.add('hidden');
            this.popupOpen = false;
        } else {
            elem_backdrop.classList.add('active');
            this.popupOpen = true;

            let title: string, msg: string|HTMLElement, css: string|null = null, buttons: Array<[string,Array<[string,eventListener]>]>, lastIsSmall: boolean;
            [title,msg,css,buttons,lastIsSmall] = this.popupStack.shift();

            elem_title.innerText = title;
            if (typeof msg === "string") elem_content.innerHTML = msg;
            else elem_content.append(msg);
            elem_backdrop.classList.add('active');

            if (css) elem_modal.classList.add(css);

            let first = true, i = 1;
            for (const button of buttons) {
                let elem_button = document.createElement('div');
                if (i < buttons.length || !lastIsSmall)
                    elem_button.classList.add('modal-button', 'small', 'inline')
                else elem_button.classList.add('small','inline','pointer')
                elem_button.innerHTML = button[0];

                let c = 0;
                for (const listener of button[1])
                    elem_button.addEventListener( listener[0], e => listener[1]( e, elem_button, c++ )  );

                if (!first) elem_actions.appendChild( document.createTextNode(' ') );
                first = false;

                if (i < buttons.length || !lastIsSmall)
                    elem_actions.appendChild(elem_button);
                else {
                    elem_actions.appendChild(document.createElement('br'))
                    elem_actions.appendChild(document.createElement('br'))
                    let wrapper = document.createElement('div')
                    wrapper.classList.add('right');
                    elem_actions.appendChild(wrapper)
                    wrapper.appendChild(elem_button)
                }

                i++;
            }
        }
    }

    popup(title: string, msg: string|HTMLElement, css: string|null = null, buttons: Array<[string,Array<[string,eventListener]>]>, lastOptionSmall: boolean = false): void {
        this.popupStack.push( [title,msg,css,buttons,lastOptionSmall] );
        if (!this.popupOpen) this.nextPopup();
    }

    private maxZ(): number {
        return Math.max.apply(null,
            Array.from( document.querySelectorAll<HTMLElement>('body *') )
                .map(function (e: HTMLElement): number { const z = parseFloat(window.getComputedStyle(e).zIndex); return isNaN(z) ? 0 : z })
        );
    }

    handleCountdown( element: Element ): void {
        let attr = parseInt(element.getAttribute('x-countdown'));
        const timeout = new Date( (new Date()).getTime() + 1000 * attr );

        const show_secs   = !element.getAttribute('x-countdown-no-seconds');
        const force_hours =  element.getAttribute('x-countdown-force-hours');
        const custom_handler = element.getAttribute('x-countdown-handler');
        const clock = element.getAttribute('countdown-clock');
        let interval = element.getAttribute('x-countdown-interval');
        if (!interval) interval = '1000';

        const draw = function() {
            const seconds = Math.floor((timeout.getTime() - (new Date()).getTime())/1000);
            if (seconds < 0) return;

            const h = Math.floor(seconds/3600);
            const m = Math.floor((seconds - h*3600)/60);
            const s = seconds - h*3600 - m*60;

            let html = "";
            // Check if there's a tooltip set
            let tooltip = element.querySelectorAll(".tooltip");
            if (tooltip.length > 0) {
                for (let i = 0 ; i < tooltip.length ; i++) {
                    html += tooltip[i].outerHTML;
                }
            }

            if (custom_handler === 'pre' || custom_handler === 'handle') element.dispatchEvent(new CustomEvent('countdown', {detail: [seconds, h, m, s]}));
            if (!custom_handler || custom_handler === 'pre' || custom_handler === 'post') {
                // If we hide seconds, we round at display.
                element.innerHTML = html +
                    (clock ? '~' : '') +
                    (((show_secs ? h : (m + 1) === 60 ? (h + 1) : h) > 0 || force_hours) ? ((show_secs ? h : (m + 1) === 60 ? (h + 1) : h) + ':') : '') +
                    (((show_secs ? h : (m + 1) === 60 ? (h + 1) : h) > 0 || force_hours) ? ((show_secs ? m : (m + 1) === 60 ? 0 : (m + 1)) > 9 ? (show_secs ? m : (m + 1) === 60 ? 0 : (m + 1)) : ('0' + (show_secs ? m : (m + 1) === 60 ? 0 : (m + 1)))) : (show_secs ? m : (m + 1) === 60 ? 0 : (m + 1))) +
                    (show_secs ? (':' + (s > 9 ? s : ('0' + s))) : '');
            }
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

    handleCurrentTime( element: Element, offsetInSeconds: number = -1 ): void {
        const show_secs   = !element.getAttribute('x-no-seconds');
        const force_hours =  element.getAttribute('x-force-hours');
        const custom_handler = element.getAttribute('x-handler');
        let interval = element.getAttribute('x-countdown-interval');
        if (!interval) interval = '1000';

        let offset = 0;
        if (offsetInSeconds >= 0) offset = 1000 * (offsetInSeconds + ((new Date()).getTimezoneOffset() * 60));

        const draw = function() {
            let date = new Date();
            if (offset != 0) date.setTime( date.getTime() + offset );
            const h = date.getHours();
            const m = date.getMinutes();
            const s = date.getSeconds();
            let html = "";
            // Check if there's a tooltip set
            let tooltip = element.querySelectorAll(".tooltip");
            if (tooltip.length > 0) {
                for (let i = 0 ; i < tooltip.length ; i++) {
                    html += tooltip[i].outerHTML;
                }
            }
            element.innerHTML = html +
                ((h > 0 || force_hours) ? (h + ':') : '') +
                ((h > 0 || force_hours) ? (m > 9 ? m : ('0' + m)) : m) +
                (show_secs ? (':' + (s > 9 ? s : ('0' + s))) : '');
        };

        const f = function(no_chk = false) {
            if (!no_chk && !document.body.contains(element)) return;
            draw();
            window.setTimeout(f,parseInt(interval));
        };

        f(true);
    }

    handleTooltip( element: HTMLElement): void {
        const proxy = document.createElement('hordes-tooltip');
        Array.from(element.classList).filter( c => c !== 'tooltip' ).forEach( c => proxy.classList.add(c) );
        Array.from(element.childNodes).forEach( f => proxy.appendChild(f) );
        element.replaceWith( proxy );
    };

    createTooltip(element: HTMLElement, tooltip_type: string = "help"): void {
        let text_attribute = element.getAttribute('x-tooltip');
        if (!element.hasAttribute(text_attribute)) return;
        let tooltip_text = element.getAttribute(text_attribute);
        if (tooltip_text === undefined || tooltip_text === "") return;

        let tooltip = document.createElement('hordes-tooltip');
        if(tooltip_type !== null && tooltip_type !== "") tooltip.classList.add(tooltip_type);

        if(element.hasAttribute("x-tooltip-html") && element.getAttribute("x-tooltip-html") === "true")
            tooltip.innerHTML = tooltip_text;
        else
            tooltip.innerText = tooltip_text;
        element.appendChild(tooltip);
        element.removeAttribute(text_attribute);
    }

    handleUserPopup( element: HTMLElement ): (MouseEvent)=>void  {

        const handler = (event: MouseEvent) => {
            event.stopPropagation();
            event.preventDefault();

            while (!element.getAttribute("x-user-id")) {
                element = element.parentElement;
            }
            let target = document.getElementById("user-tooltip");
            if(!target) {
                target = document.createElement("div");
                document.getElementsByTagName("body")[0].appendChild(target);
            }
            target.setAttribute("id", "user-tooltip");
            target.innerHTML = '<div class="center small"><img src="' + this.initParams.userPopupLoadingAnimation + '" alt=""/></div>';

            const reposition = () => {
                target.style.width = null;
                if (element.getBoundingClientRect().left + element.offsetWidth + target.offsetWidth > window.innerWidth) {

                    const temp_left = Math.max(0,element.getBoundingClientRect().left + element.offsetWidth/2 - element.offsetWidth/2);
                    if (temp_left + target.offsetWidth > window.innerWidth) {
                        target.style.top = (element.getBoundingClientRect().top + document.documentElement.scrollTop + element.offsetHeight) + "px";
                        if ( window.innerWidth < target.offsetWidth ) {
                            target.style.left = "0px";
                            target.style.width = '100%';
                        } else
                            target.style.left = Math.floor(window.innerWidth - target.offsetWidth) + 'px';

                    } else {
                        target.style.top = (element.getBoundingClientRect().top + document.documentElement.scrollTop + element.offsetHeight) + "px";
                        target.style.left = temp_left + "px";
                    }

                } else {
                    target.style.top = (element.getBoundingClientRect().top + document.documentElement.scrollTop) + "px";
                    target.style.left = element.getBoundingClientRect().left + element.offsetWidth + 5 + "px";
                }

                target.style.transform = 'translateY(0px)';
                window.requestAnimationFrame(() => {
                    const rect = target.getBoundingClientRect();
                    if (rect.top < 0) target.style.transform = 'translateY(' + (-rect.top) + 'px)';
                    else if (rect.bottom > window.innerHeight) target.style.transform = 'translateY(' + (window.innerHeight-rect.bottom) + 'px)';
                })
            }

            const scrollHandler = () => reposition();
            const removeTooltip = function(event,force=false) {
                if(event.target !== target && (event.target === window || !event.target?.closest("#user-tooltip") || force)) {
                    target.remove();
                    document.removeEventListener("click", removeTooltip);
                    window.removeEventListener("popstate", removeTooltip);
                    window.removeEventListener( "scroll", scrollHandler, {capture: true} );
                }
            }

            document.addEventListener("click", removeTooltip, {once: true});
            window.addEventListener('popstate', removeTooltip, {once: true});
            window.addEventListener("scroll", scrollHandler, {capture: true});
            reposition();

            $.ajax.background().load(target, this.initParams.userPopupEndpoint, false, {'id': element.getAttribute("x-user-id")}, () => {
                $.html.addEventListenerAll('[x-ajax-href]', 'click', e => removeTooltip(e,true));
                reposition();
            });
        }

        element.addEventListener( 'click', handler);
        return handler;
    }

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
                    const was_selected = buttons[b].classList.contains('selected');
                    for (let bi = 0; bi < buttons.length; bi++) buttons[bi].classList.remove('selected');
                    buttons[b].classList.add('selected');
                    let selector = '*[x-tab-target][x-tab-group=' + group + ']';
                    if(id != 'all')
                        selector += '[x-tab-id= ' + id + ']';
                    let targets = element.querySelectorAll(selector);
                    for (let t = 0; t < targets.length; t++)
                        (<HTMLElement>targets[t]).style.display = null;
                    $.client.set( group, 'tabState', id, true );
                    if (!was_selected) controllers[i].dispatchEvent(new CustomEvent('tab-switch', { bubbles: false, cancelable: true, detail: {group: group, tab: id, initial: false} }))
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

    handleCollapseSection( element: HTMLElement): void {
        element?.querySelectorAll('.collapsor:not([data-processed])+.collapsed').forEach( collapsed => {
            const collapsor = collapsed.previousElementSibling as HTMLElement;
            collapsor.dataset.processed = '1';

            if (collapsor.dataset.open === '1') {
                (collapsed as HTMLElement).style.maxHeight = null;
                (collapsed as HTMLElement).style.opacity = '1';
            } else {
                (collapsed as HTMLElement).style.maxHeight = '0';
                (collapsed as HTMLElement).style.opacity = '0';
            }

            const updateState = () => {
                if (collapsor.dataset.open === '1') {
                    collapsor.dataset.transition = '0';
                    (collapsed as HTMLElement).style.maxHeight = null;
                    const h = (collapsed as HTMLElement).offsetHeight;
                    (collapsed as HTMLElement).style.maxHeight = '0';

                    collapsor.dataset.transition = '1';
                    window.setTimeout(() => {
                        (collapsed as HTMLElement).style.maxHeight = `${h}px`;
                        (collapsed as HTMLElement).style.opacity = '1';
                        window.setTimeout( () => {
                            if (collapsed as HTMLElement) (collapsed as HTMLElement).style.maxHeight = null;
                        }, 300 );
                    }, 1)

                } else {
                    collapsor.dataset.transition = '0';
                    const h = (collapsed as HTMLElement).offsetHeight;
                    (collapsed as HTMLElement).style.maxHeight = `${h}px`;
                    (collapsed as HTMLElement).style.opacity = '1';

                    collapsor.dataset.transition = '1';
                    window.setTimeout(() => {
                        (collapsed as HTMLElement).style.maxHeight = '0';
                        (collapsed as HTMLElement).style.opacity = '0';
                    }, 1)
                }
            }
            collapsor.addEventListener('click', () => {
                collapsor.dataset.open = collapsor.dataset.open === '1' ? '0' : '1';
                updateState();
            })
        } )
    }

    setTutorialStage( tutorial: number, stage: string ): void {
        this.forEach('[x-tutorial-content]', elem => {
            const list = elem.getAttribute('x-tutorial-content').split(' ');
            let display = (list.includes( '*' ) || list.includes( tutorial + '.*' ) || list.includes( tutorial + '.' + stage ));
            if (display) display = !(list.includes( '!*' ) || list.includes( '!' + tutorial + '.*' ) || list.includes( '!' + tutorial + '.' + stage ));
            elem.style.display = display ? 'block' : null;

            if (display && elem.classList.contains('text')) elem.scrollIntoView( elem.classList.contains('arrow-down') );
        });
        this.tutorialStage = [tutorial,stage];
    }

    conditionalSetTutorialStage( current_tutorial: number, current_stage: string, tutorial: number, stage: string ): void {
        if (this.tutorialStage !== null && current_tutorial === this.tutorialStage[0] && current_stage === this.tutorialStage[1])
            this.setTutorialStage(tutorial,stage);
    }

    restoreTutorialStage(): void {
        if (this.tutorialStage !== null) this.setTutorialStage(this.tutorialStage[0],this.tutorialStage[1]);
    }

    finishTutorialStage(complete: boolean = false): void {
        if (this.tutorialStage !== null && complete) {
            let completed = $.client.config.completedTutorials.get();
            if (!completed.includes(this.tutorialStage[0])) {
                completed.push(this.tutorialStage[0]);
                $.client.config.completedTutorials.set(completed);
                $.html.forEach('.beginner-mode li.tick[x-tutorial-section="' + this.tutorialStage[0] + '"]', element => element.classList.add('complete'));
            }
        }
        this.forEach('[x-tutorial-content]', elem =>  elem.style.display = null);
        this.tutorialStage = null;
    }

    conditionalFinishTutorialStage( current_tutorial: number, current_stage: string, complete: boolean = false ): void {
        if (this.tutorialStage !== null && current_tutorial === this.tutorialStage[0] && current_stage === this.tutorialStage[1])
            this.finishTutorialStage( complete );
    }

    private updateTitle(alt: boolean = false): void {
        const msg_char =
            (alt ? [null,'❶۬','❷۬','❸۬','❹۬','❺۬','❻۬','❼۬','❽۬','❾۬','⬤۬'] : [null,'❶','❷','❸','❹','❺','❻','❼','❽','❾','⬤'])
                [Math.max(0,Math.min(this.title_segments[0], 10))];
        window.document.title = (msg_char === null) ? this.title_segments[1] : (msg_char + ' ' + this.title_segments[1]);
        if (this.title_segments[2] !== null) window.document.title += ' | ' + this.title_segments[2];
    }

    private titleTimer(): void {
        this.updateTitle(this.title_alt);
        this.title_alt = !this.title_alt;
        this.title_timer = window.setTimeout(() => this.titleTimer(), this.title_alt ? 900 : 100);
    }

    setTitleSegmentCount(num: number): void {
        this.title_segments[0] = num;
        if (num > 0 && this.title_timer === null) this.titleTimer();
        else if (num <= 0 && this.title_timer !== null) {
            window.clearTimeout( this.title_timer );
            this.title_timer = null;
        }
        this.updateTitle(this.title_alt);
    }

    setTitleSegmentCore(core: string): void {
        this.title_segments[1] = core;
        this.updateTitle(this.title_alt);
    }

    setTitleSegmentAddendum(add: string|null): void {
        this.title_segments[2] = add;
        this.updateTitle(this.title_alt);
    }

    validateEmail(email: string): boolean {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    makeSearchTable( table: HTMLElement ) {
        // Search tables need data-search-table and data-search-props attributes
        // If search-tablle-processed is set, then this table has already been processed an should be ignored
        if (!table.dataset['searchTable'] || !table.dataset['searchProps'] || table.dataset['searchTableProcessed'] === '1') return;

        // Set processed flag
        table.dataset['searchTableProcessed'] = '1';

        // Column translation
        let columns: { [key: string]: _SearchTableColumnProps } = {};
        const makeColumn = c => columns[c] = new _SearchTableColumnProps(table,c);
        const extractSearchProp = (a,s) => table.querySelectorAll('[data-' + a + ']').forEach((e: HTMLElement) => columns[e.dataset[s]] = makeColumn(e.dataset[s]));
        table.dataset['searchProps'].split(',').forEach( col => {
            if (col === 'auto')
                [
                    ['search-column','searchColumn'],
                    ['search-agent', 'searchAgent']
                ].forEach( conf => extractSearchProp(conf[0],conf[1]) );
            else columns[col] = makeColumn(col);
        } );

        let rows: Array<HTMLElement> = [], sorted_rows: Array<HTMLElement> = [];
        let row_container = null;

        // Catalog all rows, correct their properties if they are missing / invalid
        table.querySelectorAll('[data-search-row]').forEach( (row: HTMLElement) => {

            const closest_container = row.parentElement;
            if (closest_container === null || typeof closest_container.dataset['searchContainer'] === "undefined") {
                console.warn( 'Table "' + table.dataset['searchTable'] + "', Row " + row.dataset['searchRow'] + ": Not located within a search container! Ignoring row.");
                return;
            }

            if (row_container === null) row_container = closest_container;
            else if (row_container !== closest_container) {
                console.warn( 'Table "' + table.dataset['searchTable'] + "', Row " + row.dataset['searchRow'] + ": Inconsistent row container. Moving the row to the correct container.");
                row_container.appendChild( row );
            }

            rows[ parseInt( row.dataset['searchRow'] ) ] = row;
            for (const [,col] of Object.entries(columns)) {
                if (typeof row.dataset[col.fullyQualifiedName] === "undefined") {
                    row.dataset[col.fullyQualifiedName] = col.isNumeric ? '0' : '';
                    console.warn( 'Table "' + table.dataset['searchTable'] + "', Row " + row.dataset['searchRow'] + ": Missing property '" + col.name + "'. Inferring '" + row.dataset[col.fullyQualifiedName] + "'."  )
                } else if ( col.isNumeric && isNaN(parseFloat( row.dataset[col.fullyQualifiedName] )) ) {
                    const before = row.dataset[col.fullyQualifiedName];
                    row.dataset[col.fullyQualifiedName] = '0';
                    console.warn( 'Table "' + table.dataset['searchTable'] + "', Row " + row.dataset['searchRow'] + ": Invalid numeric property '" + col.name + "' ('" + before + "'). Inferring '" + row.dataset[col.fullyQualifiedName] + "'."  )
                }
            }
        });

        const renderTableRows = ( list: Array<HTMLElement>, filter: boolean = false ) => {
            if (filter) rows.forEach(elem => elem.classList.add('hidden'));
            list.forEach(elem => {
                row_container.appendChild(elem);
                if (filter) elem.classList.remove('hidden');
            })
        }

        sorted_rows = [...rows];

        // Add events for sort columns
        table.querySelectorAll('[data-search-column]').forEach( (elem: HTMLElement) => {
            if (typeof columns[elem.dataset['searchColumn']] === "undefined") return;

            elem.dataset['originalText'] = elem.innerHTML;
            elem.dataset['sortSetting'] = '';

            const column = columns[elem.dataset['searchColumn']];
            elem.addEventListener('click', () => {
                const setting = elem.dataset['sortSetting'];

                table.querySelectorAll('[data-search-column]').forEach( (inner: HTMLElement) => {
                    inner.dataset['sortSetting'] = '';
                    inner.innerHTML = inner.dataset['originalText'];
                } );

                const sortInvert = typeof elem.dataset['searchInvert'] !== "undefined";

                if (setting === '') {
                    renderTableRows(sorted_rows = [...rows].sort(sortInvert ? column.sortDescFunction : column.sortAscFunction));
                    elem.dataset['sortSetting'] = '1';
                    elem.innerHTML = (sortInvert ? '<i class="h-icon caret-down"></i>' : '<i class="h-icon caret-up"></i>') + '&nbsp;' + elem.dataset['originalText'];
                } else if (setting === '1') {
                    renderTableRows(sorted_rows = [...rows].sort(sortInvert ? column.sortAscFunction : column.sortDescFunction));
                    elem.dataset['sortSetting'] = '-1';
                    elem.innerHTML = (sortInvert ? '<i class="h-icon caret-up"></i>' : '<i class="h-icon caret-down"></i>') + '&nbsp;' + elem.dataset['originalText'];
                } else {
                    renderTableRows(sorted_rows = [...rows]);
                    elem.dataset['sortSetting'] = '';
                    elem.innerHTML = elem.dataset['originalText'];
                }
            })
        } );

        // Add events for filter input
        table.querySelectorAll('input[data-search-agent]').forEach( (elem: HTMLInputElement) => {
            if (typeof columns[elem.dataset['searchAgent']] === "undefined") return;

            const column = columns[elem.dataset['searchAgent']];
            elem.addEventListener('input', () => {
                renderTableRows(sorted_rows.filter( column.filterFunction( elem.value ) ), true );
            });
        } );
    }
}
