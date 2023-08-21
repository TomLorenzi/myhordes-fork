import {Global} from "../defaults";
import {Fetch} from "./fetch";
declare const $: Global;

function applyFetchFunctions( node: HTMLElement ) {
    node.querySelectorAll('[data-fetch][data-fetch-method][data-fetch-load]').forEach( (node: HTMLElement) =>
        node.addEventListener('click', () => {

            if (node.dataset.fetchConfirm && !confirm( node.dataset.fetchConfirm )) return;

            (new Fetch('', false))
                .from( node.dataset.fetch ).bodyDeterminesSuccess().withErrorMessages()
                .request()
                .method( node.dataset.fetchMethod, node.dataset.fetchPayload ? JSON.parse( node.dataset.fetchPayload ) : null )
                .then(() => $.ajax.load(null, node.dataset.fetchLoad, true) )
        })
    )
}

export function dataDrivenFunctions( node: HTMLElement ) {

    applyFetchFunctions( node );

}