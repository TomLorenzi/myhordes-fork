import {SecureStorage} from "./v2/security";
window.addEventListener('load', () => {
    SecureStorage.acquire_token()
        .then(() => document.dispatchEvent(new Event('tokenExchangeCompleted')))
        .catch(() => alert('Token exchange failure. Please reload the page.'))
    ;
}, {once: true, passive: true});
