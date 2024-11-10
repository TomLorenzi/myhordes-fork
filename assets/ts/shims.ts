import dialogPolyfill from "dialog-polyfill";

export function dialogShim(element: HTMLDialogElement|null): boolean {

    if (typeof HTMLDialogElement === 'function') return false;
    if (!element || element.dataset.shimRegistered === '1') return true;

    dialogPolyfill.registerDialog(element);
    element.dataset.shimRegistered = '1';
    return true;
}