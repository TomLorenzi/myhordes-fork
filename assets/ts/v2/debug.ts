
export default class Console {
    static assert(...args): void { if (process.env.NODE_ENV === 'development') console.assert(...args); }
    static debug(...args): void { if (process.env.NODE_ENV === 'development') console.debug(...args); }
    static dir(...args): void { if (process.env.NODE_ENV === 'development') console.dir(...args); }
    static error(...args): void { if (process.env.NODE_ENV === 'development') console.error(...args); }
    static group(...args): void { if (process.env.NODE_ENV === 'development') console.group(...args); }
    static info(...args): void { if (process.env.NODE_ENV === 'development') console.info(...args); }
    static log(...args): void { if (process.env.NODE_ENV === 'development') console.log(...args); }
    static table(...args): void { if (process.env.NODE_ENV === 'development') console.table(...args); }
    static trace(...args): void { if (process.env.NODE_ENV === 'development') console.trace(...args); }
    static warn(...args): void { if (process.env.NODE_ENV === 'development') console.warn(...args); }
}