"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {Shim} from "../react";
import {HordesBuySkillPoint} from "../react/manage-hxp/Wrapper";

// Define web component <hordes-buy-skill-point />
customElements.define('hordes-buy-skill-point', class HordesBuySkillPointElement extends Shim<HordesBuySkillPoint> {

    protected mountsLazily(): boolean { return true; }

    protected generateProps(): object {
        return {
            reload: this.dataset.reload
        };
    }

    protected static observedAttributeNames(): string[] { return ['data-reload']; };

    protected generateInstance(): HordesBuySkillPoint {
        return new HordesBuySkillPoint();
    }

}, {  });