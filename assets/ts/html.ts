import {Const, Global} from "./defaults";

import TwinoAlikeParser from "./twino"
import {DOMElement} from "react";

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

export default class HTML {

    twinoParser: TwinoAlikeParser;

    private tutorialStage: [number,string] = null;

    private title_segments: [number,string,string|null] = [0,'MyHordes',null];
    private title_timer: number = null;
    private title_alt: boolean = false;

    constructor() { this.twinoParser = new TwinoAlikeParser(); }

    init(): void {
        document.getElementById('modal-backdrop').addEventListener('pop', () => this.nextPopup())
    }

    forEach( query: string, handler: elementHandler, parent: HTMLElement|Document = null ): number {
        const elements = <NodeListOf<HTMLElement>>(parent ?? document).querySelectorAll(query);
        for (let i = 0; i < elements.length; i++) handler(elements[i],i);
        return elements.length;
    }

    addEventListenerAll(query: string, event: string, handler: eventListener, trigger_now: boolean = false ): number {
        return this.forEach( query, (elem, i) => {
            elem.addEventListener( event, e => handler(e,elem,i) );
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
        else this.error( c.errors['common'] );
    }

    message(label: string, msg: string ): void {

        const is_popup = label.substr(0, 6) === 'popup-';

        if ($.client.config.notificationAsPopup.get() || is_popup) {

            this.popup('',msg, 'popup-' + (is_popup ? label.substr(6) : label), [
                [window['c'].label.confirm, [['click', (e,elem) => $.html.triggerPopupPop(elem)]]]
            ]);

        } else {
            let div = document.createElement('div');
            div.innerHTML = msg;
            div.classList.add( label );
            const f_hide = function() {
                div.classList.remove('show');
                setTimeout( node => node.remove(), 500, div );
            };
            div.addEventListener('click', f_hide);
            let timeout_id = setTimeout( f_hide, 5000 );
            div.addEventListener('pointerenter', () => clearTimeout(timeout_id) );
            div.addEventListener('pointerleave', () => timeout_id = setTimeout( f_hide, 5000 ) );

            div = document.getElementById('notifications').appendChild( div );
            setTimeout( node => node.classList.add('show'), 100, div );
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

    private popupStack: Array<[string,string|HTMLElement,string|null,Array<[string,Array<[string,eventListener]>]>]> = [];
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

            let title: string, msg: string|HTMLElement, css: string|null = null, buttons: Array<[string,Array<[string,eventListener]>]>;
            [title,msg,css,buttons] = this.popupStack.shift();

            elem_title.innerText = title;
            if (typeof msg === "string") elem_content.innerHTML = msg;
            else elem_content.append(msg);
            elem_backdrop.classList.add('active');

            if (css) elem_modal.classList.add(css);

            let first = true;
            for (const button of buttons) {
                let elem_button = document.createElement('div');
                elem_button.classList.add('modal-button', 'small', 'inline')
                elem_button.innerHTML = button[0];

                let c = 0;
                for (const listener of button[1])
                    elem_button.addEventListener( listener[0], e => listener[1]( e, elem_button, c++ )  );

                if (!first) elem_actions.appendChild( document.createTextNode(' ') );
                first = false;

                elem_actions.appendChild(elem_button);
            }
        }
    }

    popup(title: string, msg: string|HTMLElement, css: string|null = null, buttons: Array<[string,Array<[string,eventListener]>]>): void {
        this.popupStack.push( [title,msg,css,buttons] );
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
                element.innerHTML = html +
                    ((h > 0 || force_hours) ? (h + ':') : '') +
                    ((h > 0 || force_hours) ? (m > 9 ? m : ('0' + m)) : m) +
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

        const fun_tooltip_pos = function(pointer: boolean = false) {
            return function(e: PointerEvent|MouseEvent) {

                if (pointer) {
                    if (e instanceof PointerEvent && e.pointerType === 'mouse') return;

                    // Center the tooltip below the parent
                    element.style.top  = parent.getBoundingClientRect().top + parent.clientHeight + 'px';
                    element.style.left = (window.innerWidth - element.clientWidth)/2 + 'px';

                } else if (element.dataset.touchtip !== '1') {
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


            }
        }

        const fun_tooltip_hide = function(e: PointerEvent|TouchEvent|MouseEvent) {
            element.dispatchEvent( new Event('disappear') );
            element.style.display = 'none';
            parent.append( element );
            parent.dataset.stage = element.dataset.touchtip = '0';
        }

        const fun_tooltip_show = function(pointer: boolean) {
            return function(e: PointerEvent|MouseEvent) {
                if (pointer && e instanceof PointerEvent && e.pointerType === 'mouse') return;
                element.style.display = 'block';
                container.append( element );
                fun_tooltip_pos(pointer)(e);
                element.dispatchEvent( new Event('appear') );
                if (pointer && $.client.config.twoTapTooltips.get()) {
                    if (parent.dataset.stage !== '1') {
                        document.body.addEventListener('click', e => e.stopPropagation(),
                            {capture: true, once: true});
                        parent.addEventListener('click', () => parent.dataset.stage = '0', {once: true})
                        window.addEventListener('scroll', () => fun_tooltip_hide(e), {once: true})
                    }

                    $.html.forEach( '[data-stage="1"]', e => e.dataset.stage = '0' );
                    parent.dataset.stage = element.dataset.touchtip = '1';

                    if (!$.client.config.ttttHelpSeen.get()) {
                        alert(c.taptut);
                        $.client.config.ttttHelpSeen.set(true);
                    }
                }
            }
        }

        parent.addEventListener('pointerdown',  fun_tooltip_show(true));
        parent.addEventListener('mouseenter',   fun_tooltip_show(false));

        parent.addEventListener('mousemove', fun_tooltip_pos(false));
        parent.addEventListener('mouseleave',   fun_tooltip_hide);

        if (!$.client.config.twoTapTooltips.get()) {
            parent.addEventListener('pointerleave', fun_tooltip_hide);
            parent.addEventListener('pointerup',    fun_tooltip_hide);
            parent.addEventListener('touchend',     fun_tooltip_hide);
        }
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

                if (setting === '') {
                    renderTableRows(sorted_rows = [...rows].sort(column.sortAscFunction));
                    elem.dataset['sortSetting'] = '1';
                    elem.innerHTML = '<i class="fa fa-caret-up"></i>&nbsp;' + elem.dataset['originalText'];
                } else if (setting === '1') {
                    renderTableRows(sorted_rows = [...rows].sort(column.sortDescFunction));
                    elem.dataset['sortSetting'] = '-1';
                    elem.innerHTML = '<i class="fa fa-caret-down"></i>&nbsp;' + elem.dataset['originalText'];
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