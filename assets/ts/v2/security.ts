import {Fetch} from "./fetch";

export class SecureStorage {

    private static api_token: string = null;

    public static token(): string { return this.api_token }
    public static partial_token(): string { return this.api_token.slice(0,8) }

    public static acquire_token(): Promise<boolean> {
        const ticket = ((document.getRootNode() as Document).children[0] as HTMLElement).dataset.ticket;
        if (!ticket) return new Promise<boolean>((resolve,reject) => { reject(false); })

        const fetch = new Fetch( 'user/security' );
        return (fetch.from(`/token/exchange/${ticket}/`)
            .request().get() as Promise<{ token: string }>)
            .then( ({token}) => {
                this.api_token = token;
                return true;
            } );
    }

}