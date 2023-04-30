import {SecureStorage} from "./v2/security";
document.addEventListener('DOMContentLoaded', () => {
    SecureStorage.acquire_token()
        .then(() => document.dispatchEvent(new Event('tokenExchangeCompleted')))
        .catch(() => alert('Token exchange failure. Please reload the page.'))
    ;
});
