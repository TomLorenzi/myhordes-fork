export function html(): HTMLElement {
    return ((document.getRootNode() as Document).firstElementChild as HTMLElement);
}