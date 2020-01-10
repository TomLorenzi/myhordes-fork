export default class HTML {

    constructor() {}

    serializeForm(form: ParentNode): object {
        let data: object = {};

        const input_fields = form.querySelectorAll('input');
        for (let i = 0; i < input_fields.length; i++) {
            const node_name = input_fields[i].getAttribute('name')
                ? input_fields[i].getAttribute('name')
                : input_fields[i].getAttribute('id');
            if (node_name)
                data[node_name] = input_fields[i].value;
        }

        return data;
    }

    selectErrorMessage( code: string, messages: object, base: object, data: object = null ) {
        if (!code) code = 'default';
        if (messages && messages.hasOwnProperty(code)) {
            if (typeof messages[code] === 'function')
                this.error( messages[code](data) );
            else this.error( messages[code] );
        } else if ( base.hasOwnProperty(code) ) this.error( base[code] );
        else this.error( 'An error occurred. No further details are available.' );
    }

    error(message: string): void {
        alert('ERROR' + "\n" + message);
    }

    handleCountdown( element: Element ): void {
        let attr = parseInt(element.getAttribute('x-countdown'));
        const timeout = new Date( (new Date()).getTime() + 1000 * attr );

        const draw = function() {
            const seconds = Math.floor((timeout.getTime() - (new Date()).getTime())/1000);
            if (seconds < 0) return;

            const h = Math.floor(seconds/1800);
            const m = Math.floor((seconds - h*1800)/60);
            const s = seconds - h*1800 - m*60;
            element.innerHTML =
                (h > 0 ? (h + ':') : '') +
                (h > 0 ? (m > 9 ? (m + ':') : ('0' + m + ':')) : (m + ':')) +
                (s > 9 ? s : ('0' + s));
        };

        const f = function(no_chk = false) {
            if (!no_chk && !document.body.contains(element)) return;
            if ((new Date() > timeout)) {
                element.innerHTML = '--:--';
                element.dispatchEvent(new Event("expire", { bubbles: true, cancelable: true }));
            }
            else {
                draw();
                window.setTimeout(f,1000);
            }
        };

        f(true);
    }
}