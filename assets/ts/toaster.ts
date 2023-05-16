import {SecureStorage} from "./v2/security";
window.addEventListener('load', () => {
    SecureStorage.acquire_token()
        .then(() => document.dispatchEvent(new Event('tokenExchangeCompleted')))
        .catch((e) => {
            if (e === 'no_ticket') {
                console.warn('No ticket.');
                document.dispatchEvent(new Event('tokenExchangeCompleted'));
            } else alert('Token exchange failure. Please reload the page.')
        })
    ;
}, {once: true, passive: true});
