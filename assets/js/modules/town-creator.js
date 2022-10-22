"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesTownCreator} from "../../ts/react/town-creator/Wrapper";

// Define web component <hordes-town-creator />
customElements.define('hordes-town-creator', class HordesTownCreatorElement extends HTMLElement {

    #_initialized = false;
    #_elevation = 0;

    #_extractData() {
        if (this.dataset.elevation) {
            this.#_elevation = parseInt(this.dataset.elevation);
            return true;
        }
        return false;
    }

    #_initialize() {
        if (this.#_initialized || !this.isConnected) return;
        if (this.#_extractData()) HordesTownCreator.mount( this, {elevation: this.#_elevation} );
        this.#_initialized = true;
    }

    adoptedCallback() {
        this.#_initialize();
        if (this.#_extractData()) HordesTownCreator.mount( this, {elevation: this.#_elevation} );
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name !== 'data-api' || oldValue === newValue) return;
        if (this.#_extractData()) HordesTownCreator.mount( this, {elevation: this.#_elevation} );
    }

    constructor() {
        super();
        this.addEventListener('x-react-degenerate', () => HordesTownCreator.unmount( this ));
        this.#_initialize();
    }
}, {  });