"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesUserSearchBar} from "../react/user-search/Wrapper";
import {HordesDistinctions} from "../react/distinctions/Wrapper";
import {Shim} from "../react";

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
            acceptCSVListSearch: parseInt( this.dataset.list ?? '0' ) !== 0,
            withSelf: parseInt( this.dataset.self ?? '0' ) !== 0,
            withFriends: parseInt( this.dataset.friends ?? '1' ) !== 0,
            withAlias: parseInt( this.dataset.alias ?? '0' ) !== 0,
        }
    }

    protected static observedAttributeNames() {
        return ['data-title','data-exclude','data-clear','data-list','data-self','data-friends','data-alias'];
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