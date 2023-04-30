"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesMap} from "../react/map/Wrapper";
import {Shim} from "../react";

customElements.define('hordes-map', class HordesMapElement extends Shim<HordesMap> {

    protected allow_migration: boolean = true;

    protected generateInstance(): HordesMap {
        return new HordesMap();
    }

    protected generateProps(): object | null {
        return JSON.parse(this.dataset.map);
    }

    protected static observedAttributeNames() {
        return ['data-map'];
    }

}, {  });