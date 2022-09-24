"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesMap} from "../../ts/react/map/Wrapper";

// Define web component <hordes-map />
customElements.define('hordes-map', class HordesMapElement extends HTMLElement {
    #_data = {}
    #_initialized = false;

    #_extractData() {
        if (this.dataset.map) {
            this.#_data = JSON.parse(this.dataset.map);
            this.dataset.map = '';
            return true;
        }
        return false;
    }

    #_initialize() {
        if (this.#_initialized || !this.isConnected) return;
        if (this.#_extractData()) HordesMap.mount( this, this.#_data );
        this.#_initialized = true;
    }

    adoptedCallback() {
        this.#_initialize();
        if (this.#_extractData()) HordesMap.mount( this, this.#_data );
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name !== 'data-map' || oldValue === newValue) return;
        if (this.#_extractData()) HordesMap.mount( this, this.#_data );
    }

    constructor() {
        super();
        this.addEventListener('x-react-degenerate', () => HordesMap.unmount( this ));
        this.#_initialize();
    }
}, {  });