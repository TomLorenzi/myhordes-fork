"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesAvatarCreator} from "../react/avatar-creator/Wrapper";
import {Shim} from "../react";

// Define web component <hordes-avatar-creator />
customElements.define('hordes-avatar-creator', class HordesAvatarCreatorElement extends Shim<HordesAvatarCreator> {

    protected mountsLazily(): boolean { return true; }

    protected generateProps(): object {
        return {
            maxSize: parseInt(this.dataset.maxSize) ?? 0,
        };
    }

    protected generateInstance(): HordesAvatarCreator {
        return new HordesAvatarCreator();
    }

}, {  });