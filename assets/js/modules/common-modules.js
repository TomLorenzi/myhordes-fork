"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesUserSearchBar} from "../../ts/react/user-search/Wrapper";

// Define web component <hordes-user-search />
customElements.define('hordes-user-search', class HordesUserSearchElement extends HTMLElement {
    #_initialized = null;

    #_data = {}

    #_extractData() {
        this.#_data = {
            title: this.dataset.title ?? null,
            exclude: this.dataset.exclude ? this.dataset.exclude.split(',').filter(s=>s).map(s=>parseInt(s)) : [],
            clearOnCallback: parseInt( this.dataset.clear ?? '1' ) !== 0,
            acceptCSVListSearch: parseInt( this.dataset.list ?? '0' ) !== 0,
            withSelf: parseInt( this.dataset.self ?? '0' ) !== 0,
            withFriends: parseInt( this.dataset.friends ?? '1' ) !== 0,
            withAlias: parseInt( this.dataset.alias ?? '0' ) !== 0,
            context: this.dataset.context ?? 'common',
        }
        return true;
    }

    #_initialize() {
        if (this.#_initialized || !this.isConnected) return;
        if (this.#_extractData()) {
            this.#_initialized = new HordesUserSearchBar;
            this.#_initialized.mount(this, this.#_data);
        }
    }

    adoptedCallback() {
        this.#_initialize();
        if (this.#_extractData()) this.#_initialized?.mount(this, this.#_data);
    }

    static get observedAttributes() { return ['data-title','data-exclude','data-clear','data-list','data-self','data-friends','data-alias']; }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;
        if (this.#_extractData()) this.#_initialized?.mount(this, this.#_data);
    }

    constructor() {
        super();
        this.addEventListener('x-react-degenerate', () => this.#_initialized?.unmount());
        this.#_initialize();
    }
}, {  });