"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesEventCreator} from "../react/event-creator/Wrapper";
import {Shim} from "../react";

// Define web component <hordes-event-creator />
customElements.define('hordes-event-creator', class HordesEventCreatorElement extends Shim<HordesEventCreator> {

    protected generateInstance(): HordesEventCreator {
        return new HordesEventCreator();
    }

    protected generateProps(): object | null {
        return {
            creator: parseInt(this.dataset.creator) > 0,
            reviewer: parseInt(this.dataset.reviewer) > 0,
            admin: parseInt(this.dataset.admin) > 0
        }
    }

    protected static observedAttributeNames() {
        return ['data-creator', 'data-reviewer', 'data-admin'];
    }

}, {  });