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

            let payloadQuery = node.dataset.fetchPayloadQuery;
            let payloadQueryField = node.dataset.fetchPayloadQueryField;

            if (payloadQuery && payloadQueryField) {
                const q = prompt( payloadQuery );
                if (q === null) return;

                let data = {};
                data[payloadQueryField] = q;

                payload = {
                    ...(payload ?? {}),
                    ...data
                }
            }

            const spinner = node.dataset.fetchNoSpin !== "0";
            const classic = node.dataset.fetchV2 !== "1";

            if (spinner) $.html.addLoadStack();

            (new Fetch('', false))
                .from( node.dataset.fetch ).bodyDeterminesSuccess(classic).withErrorMessages().withXHRHeader(classic)
                .request()
                .method( node.dataset.fetchMethod ?? 'post', payload )
                .then(data => {
                    if (node.dataset.fetchMessage && data[node.dataset.fetchMessage]) $.html.notice( data[node.dataset.fetchMessage] );
                    else if (data['message']) $.html.notice( data['message'] );
                    if (node.dataset.fetchMessageText) $.html.notice( node.dataset.fetchMessageText );
                    if (node.dataset.fetchLoadFrom) $.ajax.load(null, data[node.dataset.fetchLoadFrom] ?? node.dataset.fetchLoad, true)
                    else if (node.dataset.fetchLoad) $.ajax.load(null, node.dataset.fetchLoad, true);
                    if (spinner) $.html.removeLoadStack();
                } )
                .catch(data => {
                    if (spinner) $.html.removeLoadStack();
                    if (node.dataset.fetchMessage && data[node.dataset.fetchMessage]) $.html.error( 'X' + data[node.dataset.fetchMessage] );
                    else if (data['message']) $.html.error( data['message'] );
                })
        })
    )
}

export function dataDrivenFunctions( node: HTMLElement ) {

    applyFetchFunctions( node );

}