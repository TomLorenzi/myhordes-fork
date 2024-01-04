"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {Shim} from "../react";
import {HordesTwinoEditor} from "../react/twino-editor/Wrapper";

export default class HordesTwinoEditorElement extends Shim<HordesTwinoEditor> {

    public value(field: string): string|number|boolean {
        return this.nestedObject().getValue( field );
    }

    public get html(): string {
        return `${this.value('html') ?? ''}`;
    }

    public set html(value: string) {
        this.dispatchEvent(new CustomEvent('import', {
            bubbles: false,
            cancelable: false,
            detail: { html: value }
        }));
    }

    public get twino(): string {
        return `${this.value('body') ?? ''}`;
    }

    public set twino(value: string) {
        this.dispatchEvent(new CustomEvent('import', {
            bubbles: false,
            cancelable: false,
            detail: { body: value }
        }));
    }

    protected generateProps(): object {
        let data = {
            id: this.getAttribute('id') ?? null,
            pm: parseInt(this.dataset.pmMode ?? "0") !== 0,
            context: this.dataset.context ?? 'forum',
            header: this.dataset.header ?? null,
            user: parseInt(this.dataset.user ?? "0"),
            username: this.dataset.username ?? null,
            tags: Object.fromEntries((this.dataset.tags ?? '').split(',').map((s:string) => s.split(':'))),
            features: (this.dataset.feature ?? '').split(','),
            controls: (this.dataset.control ?? '').split(','),
            target: this.dataset.targetUrl ? {
                url: this.dataset.targetUrl,
                method: this.dataset.targetMethod ?? 'post',
                map: this.dataset.targetMap
                    ? Object.fromEntries(this.dataset.targetMap.split(',').map((s:string) => {
                        let elems = s.split(':');
                        if (elems.length === 1) elems.push(elems[0]);
                        return elems;
                    }))
                    : null,
                include: Object.fromEntries((this.dataset.targetInclude ?? '').split(',').map((s:string) => s.split(':'))),
            } : null,
            roles: {},
            skin: this.dataset.skin ?? 'forum',
            defaultFields: {},
            redirectAfterSubmit: this.dataset.redirectAfterSubmit === "1" ? true : (this.dataset.redirectAfterSubmit ?? false),
            previewSelector: this.dataset.preview
        };

        for (const prop in this.dataset)
            if (prop.startsWith('default') && prop.length > 7)
                data.defaultFields[prop.slice(7,8).toLowerCase() + prop.slice(8)] = this.dataset[prop];
            else if (prop.startsWith('feature') && prop.length > 7 && parseInt(this.dataset[prop]) > 0)
                data.features.push(prop.slice(7,8).toLowerCase() + prop.slice(8));
            else if (prop.startsWith('control') && prop.length > 7 && parseInt(this.dataset[prop]) > 0)
                data.controls.push(prop.slice(7,8).toLowerCase() + prop.slice(8));
            else if (prop.startsWith('withRole') && prop.length > 8)
                data.roles[prop.slice(8).toUpperCase()] = this.dataset[prop];

        return data;
    }

    protected generateInstance(): HordesTwinoEditor {
        return new HordesTwinoEditor();
    }

}

// Define web component <hordes-twino-editor />
customElements.define('hordes-twino-editor', HordesTwinoEditorElement, {  });