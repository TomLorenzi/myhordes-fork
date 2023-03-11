"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesTownCreator} from "../react/town-creator/Wrapper";
import {Shim} from "../react";

// Define web component <hordes-town-creator />
customElements.define('hordes-town-creator', class HordesTownCreatorElement extends Shim<HordesTownCreator> {

    protected generateProps(): object {
        return {
            elevation: parseInt(this.dataset.elevation ?? '0'),
            eventMode: parseInt(this.dataset.eventMode ?? '0') !== 0
        };
    }

    protected generateInstance(): HordesTownCreator {
        return new HordesTownCreator();
    }

}, {  });