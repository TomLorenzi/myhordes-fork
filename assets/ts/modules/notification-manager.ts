"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesNotificationManager} from "../react/notification-manager/Wrapper";
import {Shim} from "../react";

// Define web component <hordes-avatar-creator />
customElements.define('hordes-notification-manager', class HordesNotificationManagerElement extends Shim<HordesNotificationManager> {

    protected generateProps(): object {
        return {};
    }

    protected generateInstance(): HordesNotificationManager {
        return new HordesNotificationManager();
    }

}, {  });