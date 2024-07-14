"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {Shim} from "../react";
import {HordesTownOnboarding} from "../react/game-onboarding/WrapperTownOnboarding";

// Define web component <hordes-town-onboarding />
customElements.define('hordes-town-onboarding', class HordesTownOnboardingElement extends Shim<HordesTownOnboarding> {

    protected mountsLazily(): boolean { return true; }

    protected generateProps(): object {
        return {
            town: parseInt(this.dataset.town) ?? 0,
        };
    }

    protected static observedAttributeNames(): string[] { return ['data-town']; };

    protected generateInstance(): HordesTownOnboarding {
        return new HordesTownOnboarding();
    }

}, {  });