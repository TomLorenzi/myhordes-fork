export function html(): HTMLElement {
    return ((document.getRootNode() as Document).firstElementChild as HTMLElement);
}

export function broadcast(message: string, args: object = {}): void {
    window?.mhWorker?.port.postMessage( {payload: {...args, message}, request: 'broadcast', except: window?.mhWorkerIdList} )
}