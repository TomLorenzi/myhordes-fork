"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesEventCreator} from "../../ts/react/event-creator/Wrapper";
import {HordesDistinctions} from "../../ts/react/distinctions/Wrapper";

// Define web component <hordes-town-creator />
customElements.define('hordes-event-creator', class HordesEventCreatorElement extends HTMLElement {
    #_initialized = null;

    #_data = {}

    #_extractData() {
        this.#_data = {
            creator: parseInt(this.dataset.creator) > 0
        }
        return true;
    }

    #_initialize() {
        if (this.#_initialized || !this.isConnected) return;
        if (this.#_extractData()) {
            this.#_initialized = new HordesEventCreator();
            this.#_initialized.mount(this, this.#_data);
        }
    }

    adoptedCallback() {
        this.#_initialize();
        if (this.#_extractData()) this.#_initialized?.mount(this, this.#_data);
    }

    static get observedAttributes() { return ['data-creator']; }

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