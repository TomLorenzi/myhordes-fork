import {Fetch} from "./fetch";

export class SecureStorage {

    private static api_token: string = null;

    public static token(): string { return this.api_token ?? 'no_token_set_yet' }

    public static partial_token(): string { return this.api_token?.slice(0,8) ?? 'no_token' }

    public static acquire_token(): Promise<boolean> {
        const ticket = ((document.getRootNode() as Document).children[0] as HTMLElement).dataset.ticket;
        if (!ticket) return new Promise<boolean>((resolve,reject) => { reject('no_ticket'); })

        const fetch = new Fetch( 'user/security' );
        return (fetch.from(`/token/exchange/${ticket}/`)
            .request().get() as Promise<{ token: string }>)
            .then( ({token}) => {
                this.api_token = token;
                return true;
            } );
    }

}