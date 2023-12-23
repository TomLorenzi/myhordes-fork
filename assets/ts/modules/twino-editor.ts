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
            tags: Object.fromEntries((this.dataset.tags ?? '').split(',').map((s:string) => s.split(':'))),
            features: (this.dataset.feature ?? '').split(','),
            controls: (this.dataset.control ?? '').split(','),
            defaultFields: {},
        };

        for (const prop in this.dataset)
            if (prop.startsWith('default') && prop.length > 7)
                data.defaultFields[prop.slice(7,8).toLowerCase() + prop.slice(8)] = this.dataset[prop];
            else if (prop.startsWith('feature') && prop.length > 7 && parseInt(this.dataset[prop]) > 0)
                data.features.push(prop.slice(7,8).toLowerCase() + prop.slice(8));
            else if (prop.startsWith('control') && prop.length > 7 && parseInt(this.dataset[prop]) > 0)
                data.controls.push(prop.slice(7,8).toLowerCase() + prop.slice(8));

        return data;
    }

    protected generateInstance(): HordesTwinoEditor {
        return new HordesTwinoEditor();
    }

}, {  });