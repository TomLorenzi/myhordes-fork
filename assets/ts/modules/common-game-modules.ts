"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesMap} from "../react/map/Wrapper";
import {PersistentShim, Shim} from "../react";
import {HordesLog} from "../react/log/Wrapper";
import {HordesInventory} from "../react/inventory/Wrapper";

customElements.define('hordes-map', class HordesMapElement extends PersistentShim<HordesMap> {
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

customElements.define('hordes-log', class HordesLogElement extends PersistentShim<HordesLog> {
    protected generateInstance(): HordesLog {
        return new HordesLog();
    }

    protected generateProps(): object | null {
        return {
            domain: this.dataset.domain ?? 'any',
            etag: parseInt(this.dataset.etag ?? '0') ?? 0,
            day: parseInt(this.dataset.day ?? '0') ?? 0,
            entries: parseInt(this.dataset.entries ?? '5') ?? 5,
            citizen: parseInt(this.dataset.citizen ?? '-1') ?? -1,
            category: (this.dataset.category ?? '-1').split(',').map( v => parseInt(v) ).filter(v=>v>=0),
            indicators: parseInt(this.dataset.indicators ?? '0') !== 0,
            inlineDays: parseInt(this.dataset.inlineDays ?? '0') !== 0,
            chat: parseInt(this.dataset.chat ?? '0') !== 0,
            zone: parseInt(this.dataset.zone ?? '0'),
        }
    }

    protected static observedAttributeNames() {
        return [
            'data-day','data-etag','data-citizen','data-category','data-domain','data-entries','data-indicators',
            'data-zone','data-inline-days', 'data-chat'
        ];
    }

}, {  });

customElements.define('hordes-inventory', class HordesInventoryElement extends PersistentShim<HordesInventory> {
    protected generateInstance(): HordesInventory {
        return new HordesInventory();
    }

    protected generateProps(): object | null {
        return {
            etag: this.dataset.etag,
            inventoryAId: parseInt(this.dataset.inventoryAId),
            inventoryAType: this.dataset.inventoryAType,
            inventoryBId: parseInt(this.dataset.inventoryBId ?? '0'),
            inventoryBType: this.dataset.inventoryBType ?? 'none',
        }
    }

    protected static observedAttributeNames() {
        return [
            'data-etag',
            'data-inventory-a-id', 'data-inventory-a-type',
            'data-inventory-b-id', 'data-inventory-b-type',
        ];
    }

}, {  });