export function byteToText(bytes: number) {
    if (bytes < 1024) return `${bytes} B`
    else if (bytes < 1048576) return `${Math.floor(bytes/102.4)/10} KiB`
    else if (bytes < 1073741824) return `${Math.floor(bytes/104857.6)/10} MiB`
    else if (bytes < 1099511627776) return `${Math.floor(bytes/107374182.4)/10} GiB`
    else if (bytes < 1125899906842624) return `${Math.floor(bytes/109951162777.6)/10} TiB`
    else return `${Math.floor(bytes/112589990684262.4)/10} PiB`
}