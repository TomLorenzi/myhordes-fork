"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesUserSearchBar} from "../react/user-search/Wrapper";
import {HordesDistinctions} from "../react/distinctions/Wrapper";
import {Shim} from "../react";
import {HordesTooltip} from "../react/tooltip/Wrapper";

// Define web component <hordes-user-search />
customElements.define('hordes-user-search', class HordesUserSearchElement extends Shim<HordesUserSearchBar> {

    protected generateInstance(): HordesUserSearchBar {
        return new HordesUserSearchBar();
    }

    protected generateProps(): object | null {
        return {
            title: this.dataset.title ?? null,
            exclude: this.dataset.exclude ? this.dataset.exclude.split(',').filter(s=>s).map(s=>parseInt(s)) : [],
            clearOnCallback: parseInt( this.dataset.clear ?? '1' ) !== 0,
            callbackOnClear: parseInt( this.dataset.notifyClear ?? '0' ) !== 0,
            acceptCSVListSearch: parseInt( this.dataset.list ?? '0' ) !== 0,
            withSelf: parseInt( this.dataset.self ?? '0' ) !== 0,
            withFriends: parseInt( this.dataset.friends ?? '1' ) !== 0,
            withAlias: parseInt( this.dataset.alias ?? '0' ) !== 0,
            context: this.dataset.context ?? 'common',
        }
    }

    protected static observedAttributeNames() {
        return ['data-title','data-exclude','data-clear','data-list','data-self','data-friends','data-alias','data-notify-clear'];
    }

}, {  });

customElements.define('hordes-distinctions', class HordesDistinctionsElement extends Shim<HordesDistinctions> {

    protected generateInstance(): HordesDistinctions {
        return new HordesDistinctions();
    }

    protected generateProps(): object | null {
        return {
            source: this.dataset.source ?? 'soul',
            plain: parseInt( this.dataset.plain ?? '0' ) !== 0,
            user: parseInt( this.dataset.user ?? '0' ),
            interactive: parseInt( this.dataset.interactive ?? '0' ) !== 0,
        }
    }

    protected static observedAttributeNames() {
        return ['data-source', 'data-plain', 'data-user', 'data-interactive'];
    }

}, {  });

customElements.define('hordes-tooltip', class HordesTooltipElement extends Shim<HordesTooltip> {

    private originalChildren: ChildNode[]|null = null;

    protected selfMount(): void {
        if (!this.originalChildren?.length)
            this.originalChildren = Array.from(this.childNodes);
        super.selfMount( {children: this.originalChildren} );
    }

    protected generateInstance(): HordesTooltip {
        return new HordesTooltip();
    }

    protected generateProps(): object | null {
        return {
            additionalClasses: this.classList.toString(),
            textContent: this.dataset.content ?? null,
            'for': (this.dataset.parent && document.querySelector(this.dataset.parent)) ?? this.parentElement
        }
    }

    protected static observedAttributeNames() {
        return ['class', 'data-content', 'data-parent'];
    }

}, {  });