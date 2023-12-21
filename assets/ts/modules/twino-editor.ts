"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {Shim} from "../react";
import {HordesTwinoEditor} from "../react/twino-editor/Wrapper";

// Define web component <hordes-twino-editor />
customElements.define('hordes-twino-editor', class HordesTwinoEditorElement extends Shim<HordesTwinoEditor> {

    protected generateProps(): object {
        let data = {
            context: this.dataset.context ?? 'forum',
            header: this.dataset.header ?? null,
            username: this.dataset.username ?? null,
            features: [],
            defaultFields: {},
        };

        for (const prop in this.dataset)
            if (prop.startsWith('default') && prop.length > 7)
                data.defaultFields[prop.slice(0,1).toLowerCase() + prop.slice(1)] = this.dataset[prop];
            else if (prop.startsWith('feature') && prop.length > 7 && parseInt(this.dataset[prop]) > 0)
                data.features.push(prop.slice(0,1).toLowerCase() + prop.slice(1));

        return data;
    }

    protected generateInstance(): HordesTwinoEditor {
        return new HordesTwinoEditor();
    }

}, {  });