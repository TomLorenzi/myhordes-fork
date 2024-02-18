import {Global} from "../defaults";
import {Fetch} from "./fetch";
declare const $: Global;

function applyFetchFunctions( node: HTMLElement ) {
    node.querySelectorAll('[data-fetch]').forEach( (node: HTMLElement) =>
        node.addEventListener('click', e => {

            e.preventDefault();

            if (node.dataset.fetchConfirm && !confirm( node.dataset.fetchConfirm )) return;

            let payload = node.dataset.fetchPayload ? JSON.parse( node.dataset.fetchPayload ) : null;
            if (payload === null && node.dataset.fetchPayloadForm) payload = $.html.serializeForm( document.querySelector(node.dataset.fetchPayloadForm) );
            else if (payload === null && node.closest('form')) payload = $.html.serializeForm( node.closest('form') );

            (new Fetch('', false))
                .from( node.dataset.fetch ).bodyDeterminesSuccess().withErrorMessages().withXHRHeader()
                .request()
                .method( node.dataset.fetchMethod ?? 'post', payload )
                .then(data => {
                    if (node.dataset.fetchMessage && data[node.dataset.fetchMessage]) $.html.notice( data[node.dataset.fetchMessage] );
                    if (node.dataset.fetchMessageText) $.html.notice( node.dataset.fetchMessageText );
                    if (node.dataset.fetchLoadFrom) $.ajax.load(null, data[node.dataset.fetchLoadFrom] ?? node.dataset.fetchLoad, true)
                    else if (node.dataset.fetchLoad) $.ajax.load(null, node.dataset.fetchLoad, true)
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