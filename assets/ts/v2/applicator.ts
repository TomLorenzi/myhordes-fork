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
                .then(data => {
                    if (node.dataset.fetchMessage && data[node.dataset.fetchMessage]) $.html.notice( data[node.dataset.fetchMessage] );
                    if (node.dataset.fetchLoad) $.ajax.load(null, node.dataset.fetchLoad, true)
                } )
                .catch(data => {
                    if (node.dataset.fetchMessage && data[node.dataset.fetchMessage]) $.html.error( 'X' + data[node.dataset.fetchMessage] );
                })
        })
    )
}

export function dataDrivenFunctions( node: HTMLElement ) {

    applyFetchFunctions( node );

}