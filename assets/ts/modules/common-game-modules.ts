"use strict";

// This is a react shim
// It's purpose is to map a react node to a custom web component

// Import the actual react code
import {HordesMap} from "../react/map/Wrapper";
import {PersistentShim, Shim} from "../react";
import {HordesLog} from "../react/log/Wrapper";
import {HordesInventory, HordesPassiveInventory} from "../react/inventory/Wrapper";
import {InventoryBagData, Item} from "../react/inventory/api";

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
            etag: this.dataset.etag,
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
        const t = this.dataset.tutorial?.split('/').map(v => v.split('::')) ?? null;

        const h = parseInt(this.dataset.hideCosts ?? '-1');

        return {
            etag: this.dataset.etag,
            locked: parseInt(this.dataset.locked) > 0,
            inventoryAId: parseInt(this.dataset.inventoryAId),
            inventoryAType: this.dataset.inventoryAType,
            inventoryBId: parseInt(this.dataset.inventoryBId ?? '0'),
            inventoryBType: this.dataset.inventoryBType ?? 'none',
            reload: this.dataset.softReload,
            reset: this.dataset.resetProxyTemplates === '1',
            hide: h >= 0 ? h : null,
            steal: this.dataset.steal === '1',
            log: this.dataset.log === '1',
            uncloak: this.dataset.uncloak === '1',
            tutorial: t === null ? null : {
                from: {
                    tutorial: parseInt(t[0][0]),
                    stage: t[0][1],
                },
                to: t[1][0] === '-1' ? null : {
                    tutorial: parseInt(t[1][0]),
                    stage: t[1][1],
                },
                restrict: (t[2]??[])[0] ?? null
            }
        }
    }

    protected static observedAttributeNames() {
        return [
            'data-etag', 'data-locked', 'data-tutorial', 'data-steal', 'data-log',
            'data-hide-costs', 'data-uncloak',
            'data-inventory-a-id', 'data-inventory-a-type',
            'data-inventory-b-id', 'data-inventory-b-type',
        ];
    }

    bag(id: number): InventoryBagData|null {
        return this.nestedObject().cachedBagData(id);
    }

}, {  });

customElements.define('hordes-passive-inventory', class HordesPassiveInventoryElement extends PersistentShim<HordesPassiveInventory> {
    protected generateInstance(): HordesPassiveInventory {
        return new HordesPassiveInventory();
    }

    protected generateProps(): object | null {
        return {
            max: parseInt(this.dataset.max ?? '0'),
            id: parseInt(this.dataset.id ?? '0'),
            link: this.dataset.link
        }
    }

    protected static observedAttributeNames() {
        return [
            'data-max', 'data-id', 'data-link'
        ];
    }
}, {  });