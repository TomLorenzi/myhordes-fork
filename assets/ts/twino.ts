interface emoteResolver { (name: string): [string|null,string] }

import {Const, Global} from "./defaults";
declare var c: Const;
declare var $: Global;

class TwinoClientOptions {
    public readonly autoLinks?: boolean;
}

class TwinoInterimBlock {

    public readonly nodeName: string|null;
    public readonly nodeText: string;
    public readonly nodeClasses: Array<string>;
    public readonly nodeAttribs: Array<[string,string]>;

    public readonly rawText: string;

    constructor(text: string = '', name: string|null = null, classes: string|Array<string> = [], attribs: Array<[string,string]> = [], raw: string|null = null) {
        this.nodeText = text;
        this.nodeName = name;
        this.rawText = raw;

        if (name !== null) {
            this.nodeClasses = (typeof classes === 'string') ? [classes] : classes;
            this.nodeAttribs = attribs;
        }

    }

    isEmpty(): boolean { return !this.nodeText && this.nodeName === null }
    isPlainText(): boolean { return this.nodeName === null }

    hasClass(cls: string): boolean { return this.nodeClasses.indexOf( cls ) !== -1; }
    getAttribute(attrib: string): string|null {
        let a = this.nodeAttribs.find( e => e[0] === attrib );
        return a ? a[1] : null;
    }
}

class TwinoRegexResult {

    static readonly TypeBB = 1;
    static readonly TypeShortBB = 2;
    static readonly TypeInset = 3;
    static readonly TypeEmote = 4;

    private static readonly TypeInsetA  = 31;
    private static readonly TypeInsetB  = 32;
    private static readonly TypeInsetC  = 33;
    private static readonly TypeInsetD  = 34;
    private static readonly TypeInsetD2 = 35;

    private readonly type:  number;
    private readonly match: RegExpMatchArray|Array<string>;

    public readonly index:  number;
    public readonly length: number;

    private constructor(type: number, match: RegExpMatchArray|Array<string>, index: number = -1) {
        this.type = type;
        this.match = match;

        this.index  = (match as RegExpMatchArray).index ?? index;
        this.length = match[0].length;

        if (this.type === TwinoRegexResult.TypeInset) {
            if      (this.match[1] !== undefined) this.type = TwinoRegexResult.TypeInsetA;
            else if (this.match[2] !== undefined) this.type = TwinoRegexResult.TypeInsetB;
            else if (this.match[4] !== undefined) this.type = TwinoRegexResult.TypeInsetC;
            else if (this.match[6] !== undefined) this.type = TwinoRegexResult.TypeInsetD;
            else if (this.match[8] !== undefined) this.type = TwinoRegexResult.TypeInsetD2;
            else throw new Error( 'Unable to guess TRR subtype for TypeInset instance.' );

        }
    }

    private static getRegex(type: number): RegExp {

        switch (type) {
            case TwinoRegexResult.TypeShortBB: return /(?:([^\w\s]){2})([\s\S]*?)\1{2}/gm;
            case TwinoRegexResult.TypeInset:   return /\{([a-zA-Z]+)\}|\{([a-zA-Z]+),([\w,]*)\}|\{([a-zA-Z]+)(\d+)\}|(?:\B|\b)@([\p{L}\d_-]+)(?::(\d+))?(?:\B|\b)/gu;
            case TwinoRegexResult.TypeEmote:   return /(?::(\w+?):)|([:;=].)|(\p{Emoji})/gu;
            default: throw Error( 'No regex defined for this type of TRR!' )
        }

    }

    static create( type: number, s: string ): Array<TwinoRegexResult> {
        let results: Array<TwinoRegexResult> = [];

        // New fancy syntactic parser for range blocks
        if (type === TwinoRegexResult.TypeBB) {

            // The first RegEx is looking for the start tag
            let startMatchRegex = /\[([^\/].*?)(?:=([^\]]*))?]/gm
            let result: Array<string>;

            // Look for potential start tags
            while ( (result = startMatchRegex.exec( s )) !== null ) {

                // We have found a potential start tag; get the node name
                const node = result[1].replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

                // Create a new RegEx that looks for more instances if this tag, either as a starting tag or closing tag
                // The new RegEx should start at the position of the outer RegEx, so we set lastIndex
                let innerMatchRegex = new RegExp('(\\[' + node + '(?:=(?:[^\\]]*))?])|(\\[\\/' + node + '\])', 'gm');
                innerMatchRegex.lastIndex = startMatchRegex.lastIndex;
                let inner: Array<string>;

                // Counter for new starting tags
                let stack = 0;

                // Look for further tags
                while ( (inner = innerMatchRegex.exec( s )) !== null ) {

                    // If the first group matches, we found another starting tag - increase stack counter
                    if (inner[1]) stack++;

                    // If the second group matches while the stack counter is 0, we have found a complete tag and can create a result
                    if (inner[2] && stack === 0) {
                        let full_match  = result[0] + s.substr( startMatchRegex.lastIndex, (innerMatchRegex.lastIndex - startMatchRegex.lastIndex) );
                        let inner_match = s.substr( startMatchRegex.lastIndex, (innerMatchRegex.lastIndex - startMatchRegex.lastIndex - inner[2].length) );

                        results.push( new TwinoRegexResult( type, [ full_match, result[1], result[2] ?? null, inner_match ], startMatchRegex.lastIndex - result[0].length ) );
                        startMatchRegex.lastIndex = innerMatchRegex.lastIndex;
                    }

                    // If the second group matches, we found a ending tag - decrease stack counter
                    else if (inner[2]) stack--;
                }
            }

        } else
            // Old purely RegEx parser for non-range blocks
            for (let m of s.matchAll( this.getRegex( type ) ))
                results.push( new TwinoRegexResult( type, m )  )

        return results;

    }

    raw(): string { return this.match[0]; }

    nodeType(): string {
        switch (this.type) {
            case TwinoRegexResult.TypeBB:
            case TwinoRegexResult.TypeInsetA:
                return this.match[1].toLowerCase();
            case TwinoRegexResult.TypeShortBB:
                return this.match[1].toLowerCase() + this.match[1].toLowerCase();
            case TwinoRegexResult.TypeInsetB:
                return this.match[2].toLowerCase();
            case TwinoRegexResult.TypeInsetC:
                return this.match[4].toLowerCase();
            case TwinoRegexResult.TypeInsetD: case TwinoRegexResult.TypeInsetD2:
                return '@';
            case TwinoRegexResult.TypeEmote:
                return this.match[0].toLowerCase();
            default:
                throw new Error('Attempt to access node type of a TRR not representing a node.');
        }
    }

    nodeContent(): string {
        switch (this.type) {
            case TwinoRegexResult.TypeBB:
                return this.match[3];
            case TwinoRegexResult.TypeShortBB:
                return this.match[2];
            case TwinoRegexResult.TypeInsetD:
                return this.match[6];
            case TwinoRegexResult.TypeInsetD2:
                return this.match[8];
            default:
                throw new Error('Attempt to access node content of a TRR not representing a block node.');
        }
    }

    nodeInfo(): string|null {
        switch (this.type) {
            case TwinoRegexResult.TypeBB:
                return this.match[2] ?? null;
            case TwinoRegexResult.TypeInsetA:
                return null;
            case TwinoRegexResult.TypeInsetB:
                return this.match[3] ?? null;
            case TwinoRegexResult.TypeInsetC:
                return this.match[5] ?? null;
            case TwinoRegexResult.TypeInsetD:
                return this.match[7] ?? null;
            case TwinoRegexResult.TypeInsetD2:
                return this.match[8] ?? null;
            default:
                throw new Error('Attempt to access node info of a TRR not supporting additional node information.');
        }
    }

}

type playerCacheEntry = {
    exists: boolean,
    id: number,
    displayName: string,
    queryName: string
}

type playerCacheObject = {
    id: { [id: number]: playerCacheEntry }
    name: { [name: string]: playerCacheEntry }
};

const playerCache: playerCacheObject = { id: {}, name: {}};
let playerCacheRefreshing: number = null;

class TwinoConverterToBlocks {

    public static rangeBlocks( match: TwinoRegexResult, parents: Array<HTMLElement> ): [boolean,Array<TwinoInterimBlock>] {

        let changed: boolean = false;
        let blocks: Array<TwinoInterimBlock> = [];

        let typeToClass = {
            'admannounce': 'adminAnnounce',
            'modannounce': 'modAnnounce',
            'announce': 'oracleAnnounce',
            'quote': 'blockquote',
            'aparte': 'sideNote',
        };

        let nested = false;
        let pollspace = false;
        for (let i = 0; i < parents.length; i++) {
            pollspace = ((parents[i].tagName === 'UL' && parents[i].classList.contains('poll')))

            let css = typeToClass[match.nodeType()] ?? match.nodeType();

            if (!nested && parents[i].hasAttribute( 'x-nested' ) && (parents[i].tagName.toLowerCase() === css.toLowerCase() || parents[i].classList.contains(css))) nested = true;
        }

        // We remove useless {br} at the beginning & the end of the text
        let nodeContent = match.nodeContent();
        while(nodeContent.match(/^({br})/g)) {
            nodeContent = nodeContent.replace(/^({br})/g, '')
        }
        while(nodeContent.match(/({br})$/g)) {
            nodeContent = nodeContent.replace(/({br})$/g, '')
        }

        if (pollspace && ['ul','li','q','desc'].indexOf(match.nodeType()) === -1)
            return [false, []];

        switch (match.nodeType()) {
            case 'b': case 'i': case 'u': case 's': case 'ul': case 'ol': case 'li':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, match.nodeType(), [], [['x-nested','1']]) );
                changed = true; break;
            case '.':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'span', ['.'], [['x-nested','1']]) );
                changed = true; break;
            case 'poll':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'ul', ['poll'], [['x-nested','1']]) );
                changed = true; break;
            case 'desc': case 'q':
                if (!pollspace) blocks.push( new TwinoInterimBlock(match.raw()) );
                else blocks.push( new TwinoInterimBlock(nodeContent, 'li', [match.nodeType()], [['x-nested','1']]) );
                break;
            case 'c':
                blocks.push( new TwinoInterimBlock(nodeContent, 'span', ['inline-code'], [ ['x-raw','1'] ]) ); changed = true; break;
            case '**': blocks.push( new TwinoInterimBlock(nodeContent, 'b') ); changed = true; break;
            case '//': blocks.push( new TwinoInterimBlock(nodeContent, 'i') ); changed = true; break;
            case '--': blocks.push( new TwinoInterimBlock(nodeContent, 's') ); changed = true; break;
            case 'spoiler':
                if (nested) blocks.push( new TwinoInterimBlock('[.]' + nodeContent + '[/.]') )
                else blocks.push( new TwinoInterimBlock('[.]' + nodeContent + '[/.]', 'div', match.nodeType(), [['x-nested','1']]) );
                changed = true; break;
            case 'code': blocks.push( new TwinoInterimBlock(nodeContent, 'pre', [], [ ['x-raw','1'] ]) ); changed = true; break;
            case 'quote':case 'cite':
                if ( match.nodeInfo() ) {
                    let split = match.nodeInfo().split(':');
                    blocks.push( new TwinoInterimBlock('', 'div', 'clear') );
                    blocks.push( new TwinoInterimBlock(match.nodeInfo(), 'span', 'quoteauthor', split.length === 2 && !isNaN(parseInt(split[1])) ? [ ['x-a',split[1]] ] : []) );
                }

                blocks.push( new TwinoInterimBlock(nodeContent, 'blockquote') );
                changed = true;

                break;
            case 'image':
                if ( !match.nodeInfo() ) {
                    blocks.push( new TwinoInterimBlock(match.raw()) );
                    break;
                }
                blocks.push( new TwinoInterimBlock('', 'img', match.nodeType(), [ ['src', match.nodeInfo()], ['alt', match.nodeContent()] ]) );
                changed = true;
                break;
            case 'link':
                if ( !match.nodeInfo() ) {
                    blocks.push( new TwinoInterimBlock(match.raw()) );
                    break;
                }
                blocks.push( new TwinoInterimBlock(nodeContent, 'a', match.nodeType(), [ ['href', match.nodeInfo()], ['target', '_blank'], ['x-raw','1'], ['x-raw-emotes', '1'] ]) );
                changed = true;
                break;
            case 'bad': case 'big':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'span', match.nodeType(), [['x-nested','1']]) );
                changed = true; break;
            case 'aparte':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'div', 'sideNote', [['x-nested','1']]) );
                changed = true; break;
            case 'admannounce':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'div', 'adminAnnounce', [['x-nested','1']]) );
                changed = true; break;
            case 'modannounce':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'div', 'modAnnounce', [['x-nested','1']]) );
                changed = true; break;
            case 'announce':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'div', 'oracleAnnounce', [['x-nested','1']]) );
                changed = true; break;
            case 'glory':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else blocks.push( new TwinoInterimBlock(nodeContent, 'div', 'glory', [['x-nested','1']]) );
                changed = true; break;
            case 'rp':
                if (nested) blocks.push( new TwinoInterimBlock(nodeContent) )
                else {
                    if ( match.nodeInfo() )
                        blocks.push( new TwinoInterimBlock(match.nodeInfo(), 'span', 'rpauthor', [['x-raw','1'], ['x-raw-emotes','1']]) );
                    blocks.push( new TwinoInterimBlock(nodeContent, 'div', 'rpText', [['x-nested','1']]) );
                }
                changed = true; break;
            case 'collapse':
                if ( match.nodeInfo() ) {
                    blocks.push( new TwinoInterimBlock(match.nodeInfo(), 'div', 'collapsor', [['data-open', '1']]) );
                    blocks.push( new TwinoInterimBlock(nodeContent, 'div', 'collapsed') );
                } else blocks.push( new TwinoInterimBlock(nodeContent) );
                changed = true; break;
            case 'html':
                blocks.push( new TwinoInterimBlock(nodeContent, 'html') );
                break;
            default: blocks.push( new TwinoInterimBlock(match.raw()) ); break;
        }

        return [changed,blocks];
    }

    public static insets( match: TwinoRegexResult, parents: Array<HTMLElement> ): Array<TwinoInterimBlock> {
        let blocks: Array<TwinoInterimBlock> = [];

        let listspace = false;
        for (let i = 0; i < parents.length; i++) {

            if (!listspace && ['UL', 'OL', 'POLL'].indexOf(parents[i].tagName) !== -1)
                listspace = true;
            else if (listspace && parents[i].tagName === 'LI')
                listspace = false;
        }

        let attribs = null;
        switch ( match.nodeType() ) {
            case 'hr': blocks.push( new TwinoInterimBlock( '', match.nodeType()) ); break;
            case 'br': blocks.push( listspace ? new TwinoInterimBlock() : new TwinoInterimBlock( '', match.nodeType()) ); break;
            case 'dice': case 'dc': case 'de': case 'des': case 'd': case 'w': case 'dado':
                if (match.nodeInfo() && ["4","6","8","10","12","20","100"].indexOf( match.nodeInfo() ) !== -1)
                    blocks.push( new TwinoInterimBlock( '???', 'div', 'dice-' + match.nodeInfo()) );
                else blocks.push( new TwinoInterimBlock(match.raw()) );
                break;
            case 'letter': case 'lettre': case 'letra':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'letter-a') ); break;
            case 'consonant': case 'consonne': case 'consonante':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'letter-c') ); break;
            case 'vowel': case 'voyelle': case 'vocal':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'letter-v') ); break;
            case 'pfc': case 'rps': case 'ssp': case 'ppt':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'rps') ); break;
            case 'flip': case 'coin': case 'ht': case 'pf': case 'mw': case 'moneda': case 'zk':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'coin') ); break;
            case 'carte': case 'card': case 'skat': case 'blatt': case 'carta': case 'karte':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'card') ); break;
            case 'coords': case 'xy': case 'pos': case 'map':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'coords') ); break;
            case 'town': case 'stadt': case 'ville':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'town') ); break;
            case 'citizen': case 'rnduser': case 'user': case 'spieler': case 'habitant': case 'habitante': case'einwohner':
                attribs = match.nodeInfo() ? match.nodeInfo().split(',') : [];
                if (typeof attribs[0] !== 'undefined' && typeof attribs[1] === 'undefined' && !isNaN(attribs[0]))
                    attribs = ['any', attribs[0]];
                else {
                    if (!attribs[0]) attribs[0] = 'any';
                    if (!attribs[1]) attribs[1] = '0';
                }

                blocks.push( new TwinoInterimBlock( attribs[1] === '0' ? '???' : '??? [' + attribs[1] + ']', 'div', 'citizen', [['x-a', attribs[0]], ['x-b', attribs[1]]]) );
                break;
            case 'coalition':
                attribs = match.nodeInfo() ? match.nodeInfo().split(',') : [];
                if (!attribs[0]) attribs[0] = '0';
                blocks.push( new TwinoInterimBlock( '???', 'div', 'coalition', [['x-b', attribs[0]]]) );
                break;
            case '@':
                let id = match.nodeInfo() ? match.nodeInfo() : 'auto';
                let name = match.nodeContent() ? match.nodeContent() : ('user #' + id)

                blocks.push( new TwinoInterimBlock( name, 'div', 'cref', [['x-a',id], ['x-qi',match.nodeInfo() ?? ''], ['x-qn',match.nodeContent() ?? '']]) );

                if ( match.nodeInfo() ) {
                    if (!playerCache.id[match.nodeInfo()]) playerCache.id[match.nodeInfo()] = null;
                } else if ( match.nodeContent() ) {
                    if (!playerCache.name[match.nodeContent()]) playerCache.name[match.nodeContent()] = null;
                }

                break;
            default: blocks.push( new TwinoInterimBlock( match.raw() ) ); break;
        }

        return blocks;
    }

}

class HTMLConverterFromBlocks {

    private static rangeBlock( b: string, t: string, a: string|null = null ): string {
        return '[' + t + ( a ? ('=' + a) : '' ) + ']' + b + '[/' + t + ']';
    }

    private static wrapBlock( b: TwinoInterimBlock, t: string, a: string|null = null ): string {
        return HTMLConverterFromBlocks.rangeBlock(b.nodeText, t, a);
    }

    public static anyBlocks( blocks: Array<TwinoInterimBlock>, parent: HTMLElement|null ): string {

        let cursor = 0;
        let nextBlock = function(): TwinoInterimBlock {
            return blocks.length > cursor ? blocks[cursor++] : null;
        }
        let peekBlock = function(): TwinoInterimBlock {
            return blocks.length > cursor ? blocks[cursor] : null;
        }

        let quotespace = false;
        let no_announces = false;
        let raw_fallback = false;
        let cp = parent;

        while (cp) {
            if (cp.nodeType === Node.ELEMENT_NODE) {
                if (!quotespace   && cp.classList.contains('no-quote')) quotespace = true;
                if (!no_announces && cp.classList.contains('no-announce')) no_announces = true;
                if (!raw_fallback && (cp.tagName === 'PRE' || cp.classList.contains('raw-fallback'))) raw_fallback = true;
            }
            cp = cp.parentNode as HTMLElement;
        }

        let ret = '';

        let block: TwinoInterimBlock, prev: TwinoInterimBlock|null;
        while (block = nextBlock()) {

            if (block.isEmpty()) continue;
            const peek = peekBlock();

            if (block.isPlainText()) ret += block.nodeText;
            else switch (block.nodeName) {
                case 'br':
                    ret += "\n";
                    break;
                case 'hr':
                    ret += '{hr}';
                    break;
                case 'b': case 'strong':
                    ret += HTMLConverterFromBlocks.wrapBlock( block, 'b' );
                    break;
                case 'a':
                    const link_href = block.getAttribute('href');
                    ret += (link_href ? HTMLConverterFromBlocks.wrapBlock( block, 'link', link_href ) : block.nodeText);
                    break;
                case 'i': case 'u': case 's': case 'ul': case 'ol': case 'li':
                    if (block.nodeName == 'ul' && block.hasClass('poll')) break;
                    else ret += HTMLConverterFromBlocks.wrapBlock( block, block.nodeName );
                    break;
                case 'pre':
                    ret += HTMLConverterFromBlocks.wrapBlock( block, 'code' );
                    break;
                case 'span':
                    if (block.hasClass('quoteauthor')) {
                        if (peek && peek.nodeName === 'blockquote') {
                            const xid = block.getAttribute('x-user-id') ?? block.getAttribute('x-id');
                            ret += quotespace ? '' : HTMLConverterFromBlocks.wrapBlock( nextBlock(), 'quote', (xid ? block.nodeText.replaceAll(/[^\p{L}\d_-]/gu,'') : block.nodeText) + (xid ? (':' + xid) : '') )
                        }
                    } else if (block.hasClass('rpauthor')) {
                        if (peek && peek.nodeName === 'div' && peek.hasClass('rpText')) {
                            ret += HTMLConverterFromBlocks.wrapBlock( nextBlock(), 'rp', block.nodeText )
                        }
                    }
                    else if (block.hasClass('bad'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'bad' );
                    else if (block.hasClass('big'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'big' );
                    else if (block.hasClass('inline-code'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'c' );
                    else ret += block.nodeText;
                    break;
                case 'div':
                    if (block.hasClass('cref')) {
                        let id = block.getAttribute('x-user-id') ?? block.getAttribute('x-id');
                        ret += '@' + ( id ? block.nodeText.replaceAll(/[^\p{L}\d_-]/gu,'') : block.nodeText) + ( id ? (':' + id) : '' );
                    } else if (block.hasClass('spoiler'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'spoiler' )
                    else if (block.hasClass('sideNote'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'aparte' );
                    else if (block.hasClass('adminAnnounce'))
                        ret += no_announces ? block.nodeText : HTMLConverterFromBlocks.wrapBlock( block, 'admannounce' );
                    else if (block.hasClass('modAnnounce'))
                        ret += no_announces ? block.nodeText : HTMLConverterFromBlocks.wrapBlock( block, 'modannounce' );
                    else if (block.hasClass('oracleAnnounce'))
                        ret += no_announces ? block.nodeText : HTMLConverterFromBlocks.wrapBlock( block, 'announce' );
                    else if (block.hasClass('rpText'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'rp' );
                    else if (block.hasClass('glory'))
                        ret += HTMLConverterFromBlocks.wrapBlock(block, 'glory');
                    else if (block.hasClass('clear')) {/* Do nothing, as a clearfix tag should be ignored */}
                    else if (block.hasClass('collapsor')) {
                        if (peek && peek.hasClass('collapsed'))
                            ret += HTMLConverterFromBlocks.wrapBlock( nextBlock(), 'collapse', block.nodeText )
                    } else if (block.hasClass('rpauthor')) {
                        if (peek && peek.nodeName === 'div' && peek.hasClass('rpText')) {
                            ret += HTMLConverterFromBlocks.wrapBlock( nextBlock(), 'rp', block.nodeText )
                        }
                    }
                    else ret += raw_fallback ? HTMLConverterFromBlocks.rangeBlock( block.rawText, 'html' ) : block.nodeText;
                    break;
                case 'blockquote':
                    ret += quotespace ? '' : HTMLConverterFromBlocks.wrapBlock( block, 'quote' );
                    break;
                case 'img':
                    let src = block.getAttribute('src');
                    let alt = block.getAttribute('alt') ?? '';
                    if (src && src.startsWith('/build/images/') && alt) ret += alt;
                    else if (src) ret += HTMLConverterFromBlocks.rangeBlock(alt, 'image', src)
                    break;
                case 'link':
                    let href = block.getAttribute('href');
                    if (href) ret += HTMLConverterFromBlocks.wrapBlock( block, 'link', href );
                    break;
                case 'p': case 'font':
                    ret += block.nodeText;
                    break;
                default:
                    ret += raw_fallback ? HTMLConverterFromBlocks.rangeBlock( block.rawText, 'html' ) : block.nodeText;
                    break;
            }

            prev = block;
        }

        return ret;
    }

}

export default class TwinoAlikeParser {

    public readonly OpModeRaw        = 0x1;
    public readonly OpModeQuote      = 0x2;

    private static lego(blocks: Array<TwinoInterimBlock>, elem: HTMLElement): void {
        for (let i = 0; i < blocks.length; i++) {
            if (blocks[i].isEmpty()) continue;

            let nodes = [];
            if ( blocks[i].isPlainText() )
                nodes.push(document.createTextNode( blocks[i].nodeText ));
            else if (blocks[i].nodeName === 'html') {
                let build = document.createElement( 'div' );
                build.classList.add( 'html' );
                build.innerHTML = blocks[i].nodeText;
                nodes.push( build );
            }
            else {
                let build = document.createElement( blocks[i].nodeName );
                build.appendChild( document.createTextNode( blocks[i].nodeText ) );
                for (let nodeClass of blocks[i].nodeClasses) build.classList.add( nodeClass );
                for (let nodeAttribTuple of blocks[i].nodeAttribs) build.setAttribute( nodeAttribTuple[0], nodeAttribTuple[1]);
                nodes.push(build);
            }
            for (let node of nodes) elem.parentNode.insertBefore(node, elem);
        }
        elem.parentNode.removeChild(elem);
    }

    private static parseRangeBlocks(elem: HTMLElement, secondary: boolean = false, parents: Array<HTMLElement> = []): boolean {
        let changed = false;
        if (elem.nodeType === Node.TEXT_NODE) {
            let str = elem.textContent;
            let current_offset = 0;
            let blocks: Array<TwinoInterimBlock> = [];

            for (const match of TwinoRegexResult.create( secondary ? TwinoRegexResult.TypeShortBB : TwinoRegexResult.TypeBB, str ) ) {

                if (current_offset < match.index )
                    blocks.push( new TwinoInterimBlock( str.substr(current_offset,match.index-current_offset) ) );

                const conversion = TwinoConverterToBlocks.rangeBlocks( match, parents );
                changed = changed || conversion[0];
                for (const result of conversion[1])
                    blocks.push( result );

                current_offset = (match.length + match.index);
            }

            if (blocks.length === 0) blocks.push( new TwinoInterimBlock( str ) );
            else if (current_offset < str.length) blocks.push( new TwinoInterimBlock( str.substr( current_offset ) ) );

            TwinoAlikeParser.lego(blocks,elem);

        } else if (elem.nodeType === Node.ELEMENT_NODE) {
            let children = elem.childNodes;
            for (let i = 0; i < children.length; i++) {

                let skip = false;
                let new_parents = [...parents];
                if (children[i].nodeType === Node.ELEMENT_NODE) {
                    let node = (children[i]) as HTMLElement;
                    if (node.hasAttribute( 'x-raw' )) {
                        node.innerHTML = node.innerHTML.replace(/{br}/gi,'\r\n');
                        skip = true;
                    }
                    new_parents.push(node);
                }

                if (!skip) changed = changed || TwinoAlikeParser.parseRangeBlocks(children[i] as HTMLElement,secondary, new_parents);
            }

        }
        return changed;
    }

    private static parseInsets(elem: HTMLElement, parents: Array<HTMLElement> = []) {
        if (elem.nodeType === Node.TEXT_NODE) {
            let str = elem.textContent;
            let current_offset = 0;
            let blocks: Array<TwinoInterimBlock> = [];

            for (const match of TwinoRegexResult.create( TwinoRegexResult.TypeInset, str )) {

                if (current_offset < match.index )
                    blocks.push( new TwinoInterimBlock( str.substr(current_offset,match.index-current_offset ) ));

                for (const result of TwinoConverterToBlocks.insets( match, parents ))
                    blocks.push( result );

                current_offset = (match.length + match.index);
            }

            if (blocks.length === 0) blocks.push( new TwinoInterimBlock(str) );
            else if (current_offset < str.length) blocks.push( new TwinoInterimBlock( str.substr( current_offset )));

            TwinoAlikeParser.lego(blocks,elem);
        } else {
            if (elem.hasAttribute( 'x-raw' )) return;
            let children = elem.childNodes;
            for (let i = 0; i < children.length; i++)
                TwinoAlikeParser.parseInsets(children[i] as HTMLElement, [...parents,elem]);

        }
    }

    private static parseEmotes(elem: HTMLElement, resolver: emoteResolver) {
        if (elem.nodeType === Node.TEXT_NODE) {
            let str = elem.textContent;
            let current_offset = 0;
            let blocks: Array<TwinoInterimBlock> = [];

            for (const match of TwinoRegexResult.create( TwinoRegexResult.TypeEmote, str )) {

                if (current_offset < match.index )
                    blocks.push( new TwinoInterimBlock( str.substr(current_offset,match.index-current_offset ) ));

                let [ctrl,proxy] = resolver( match.nodeType() );
                if (ctrl)
                    blocks.push( new TwinoInterimBlock( '', 'img', [], [['src', ctrl], ['x-foxy-proxy', proxy]]) );
                else blocks.push( new TwinoInterimBlock( match.raw() ) );
                current_offset = (match.length + match.index);
            }

            if (blocks.length === 0) blocks.push( new TwinoInterimBlock(str) );
            else if (current_offset < str.length) blocks.push( new TwinoInterimBlock(str.substr( current_offset )) );

            TwinoAlikeParser.lego(blocks,elem);
        } else {
            if (elem.hasAttribute( 'x-raw' ) && !elem.hasAttribute( 'x-raw-emotes' )) return;
            let children = elem.childNodes;
            for (let i = 0; i < children.length; i++)
                TwinoAlikeParser.parseEmotes(children[i] as HTMLElement, resolver);
        }
    }

    private static preprocessText( s: string ): string {
        s = s.replace( /(\S)(\[\*]|\[1])/gi, '$1\n$2' );

        let lines: Array<string> = s.split( '\n' );
        let m: Array<string>;

        let ulmode = false, olmode = false;

        const splitpos = s => {
            let i = 0;
            let offset = 0;

            let next_open   = 0;
            let next_closed = 0;

            do {
                next_open = s.slice(offset).search( /\[[^/]/gi );
                next_closed = s.slice(offset).search( /\[\//gi );

                if (next_open >= 0 && next_closed >= 0 && next_open < next_closed) {
                    offset += next_open + 1;
                    ++i;
                } else if (next_open >= 0 && next_closed >= 0 && next_open > next_closed) {
                    if (i > 0) {
                        --i;
                        offset += next_closed + 1;
                    } else return offset + next_closed;
                } else if ( next_open >= 0 && next_closed < 0 )
                    return offset + next_closed;
                else if ( next_open < 0 && next_closed >= 0 ) {
                    if (i > 0) {
                        --i;
                        offset += next_closed + 1;
                    } else return offset + next_closed;
                } else return -1;
            } while (next_open >= 0 || next_closed >= 0)

            return -1;
        }

        for (let i = 0; i < lines.length; i++) {

            //UL
            if ((m = lines[i].match(/^\s*?(?:\[\*]|\s\*\s)\s*(.*?)$/m))) {
                if (olmode) { lines.splice(i,0,'[/ol]'); olmode = false; i++ }

                const splicepos = splitpos(m[1]);
                if (splicepos < 0) lines[i] = '[li]' + m[1] + '[/li]';
                else {
                    lines[i] = '[li]' + m[1].slice(0,splicepos) + '[/li][/ul]' + m[1].slice(splicepos);
                    ulmode = false;
                    continue;
                }

                if (!ulmode) { lines.splice(i,0,'[ul]'); ulmode = true; i++ }
                continue;
            } else if (ulmode) { lines.splice(i,0,'[/ul]'); ulmode = false; i++; }

            //OL
            if ((m = lines[i].match(/^\s*?\[0]\s*(.*?)$/m))) {

                const splicepos = splitpos(m[1]);
                if (splicepos < 0) lines[i] = '[li]' + m[1] + '[/li]';
                else {
                    lines[i] = '[li]' + m[1].slice(0,splicepos) + '[/li][/ol]' + m[1].slice(splicepos);
                    olmode = false;
                    continue;
                }

                if (!olmode) { lines.splice(i,0,'[ol]'); olmode = true; i++ }
                continue;
            } else if (olmode) { lines.splice(i,0,'[/ol]'); olmode = false; i++; }

            if (i < (lines.length - 1)) lines[i] = lines[i] + '{br}';
        }

        if (ulmode) lines.push('[/ul]');
        if (olmode) lines.push('[/ol]');

        s = '';
        for (let i = 0; i < lines.length; i++)
            s += lines[i];
        return s;
    }

    private static postprocessReverseText( s: string ): string {
        // Wrapped in try because it may fail on ancient browsers
        try { s = s
            .replace(/(\[[uo]l\]|\[\/li\])/gu,"$1\n") // Add decorative line breaks into UL/OL lists
            .replace(/(^\s+)|(\s+$)/g, '')            // Remove all space characters at the beginning and end
            .replace( /\u{9}+/gu, ' ' )               // Replace tabs by whitespaces
            .replace(/\p{Zs}{2,}/gu, ' ');            // Compact all whitespace sequences to a single space
        }
        catch(e) {}
        return s;
    }

    private static postprocessReverseDOM( elem: HTMLElement ) {
        // Add single linebreak right after block elements
        if (elem.nodeType !== Node.TEXT_NODE ) {
            if (elem.nextSibling && (
                (['hr','blockquote','p','code'].indexOf(elem.tagName.toLowerCase()) >= 0) ||
                (['div'].indexOf(elem.tagName.toLowerCase()) >= 0 && elem.classList.contains('collapsed'))
            ))
                elem.parentElement.insertBefore( document.createElement('br'), elem.nextSibling );
            elem.childNodes.forEach( c => this.postprocessReverseDOM( c as HTMLElement ) );
        }
    }

    private static postprocessText( s: string ): string {
        // Remove single linebreak right after block elements
        // Wrapped in try because it may fail on ancient browsers
        try { s = s.replace(/(\{hr\}|<\/(?:div|blockquote|p|code)>)[\p{Zs}\u{9}]*\{br\}/gu,"$1")}
        catch(e) {}
        return s;
    }

    private static postprocessDOM( elem: HTMLElement, options: TwinoClientOptions = {} ) {
        if (elem.nodeType === Node.TEXT_NODE) {

            if (options.autoLinks && elem.parentElement.tagName !== 'A') {
                let str = elem.textContent;
                let result = null;
                let found = false;

                while (result = /\b((?:https?|ftps?):\/\/[^\s{}[\]<>]*)\s?/g.exec( str )) {
                    found = true;

                    let a = document.createElement('a');
                    a.setAttribute('href', a.innerText = result[1]);
                    a.setAttribute('x-raw', "1");

                    elem.parentElement.insertBefore( document.createTextNode( str.slice(0,result.index) ), elem );
                    elem.parentElement.insertBefore( a, elem );

                    str = str.slice(result.index + result[1].length);
                }

                if (found) {
                    if (str.length === 0) elem.remove();
                    else elem.textContent = str;
                }
            }
        } else {
            if (elem.hasAttribute( 'x-raw' )) return;
            elem.childNodes.forEach( c => this.postprocessDOM( c as HTMLElement, options ) );
        }
    }

    private static collapseTextNodes( elem: HTMLElement ) {
        if (elem.nodeType === Node.TEXT_NODE) {
            if (!elem.parentElement) return;
            while (elem.nextSibling?.nodeType === Node.TEXT_NODE) {
                elem.textContent += elem.nextSibling.textContent;
                elem.nextSibling.remove();
            }
        } else {
            let children = elem.childNodes;
            for (let i = 0; i < children.length; i++)
                this.collapseTextNodes( children[i] as HTMLElement )
        }
    }

    private static processPlayerNames( target: HTMLElement ) {
        target.querySelectorAll( '[x-qi][x-qn]' ).forEach( (elem:HTMLElement) => {
            const player_data =
                ( elem.getAttribute('x-qi' ) ? playerCache.id[elem.getAttribute('x-qi' )] : null ) ??
                ( elem.getAttribute('x-qn' ) ? playerCache.name[elem.getAttribute('x-qn' )] : null ) ?? null;

            if (player_data && player_data.exists === 1) {
                elem.classList.add("username");
                elem.setAttribute("x-a", player_data.id);
                elem.setAttribute("x-user-id", player_data.id);
                elem.textContent = player_data.displayName;
                elem.removeAttribute('x-qi');
                elem.removeAttribute('x-qn');
                $.html.handleUserPopup(<HTMLElement>elem);
            } else if (player_data && player_data.exists > 1) {
                elem.classList.add("username");
                elem.textContent = '[ ' + player_data.displayName + ' ]';
            } else if (player_data === null) {
                elem.classList.add("username");
                elem.innerHTML = '<i class="fa fa-pulse fa-spinner"></i>'
            } else {
                elem.classList.add("username");
                elem.textContent = '???';
            }
        } );
    }

    parseTo( text: string, target: HTMLElement, resolver: emoteResolver, options: TwinoClientOptions = {}, targetCallback = (s:string)=>{} ): void {

        text = text.replace('\u200B','');

        let container_node = document.createElement('p');
        container_node.innerText = TwinoAlikeParser.preprocessText( text );

        let changed = true;
        while (changed) changed = changed && TwinoAlikeParser.parseRangeBlocks(container_node,false);

        container_node.innerHTML = TwinoAlikeParser.postprocessText( container_node.innerHTML );
        TwinoAlikeParser.postprocessDOM( container_node, options );

        TwinoAlikeParser.parseInsets(container_node);
        TwinoAlikeParser.collapseTextNodes( container_node );

        changed = true;
        while (changed) changed = changed && TwinoAlikeParser.parseRangeBlocks(container_node,true);

        TwinoAlikeParser.collapseTextNodes( container_node );
        TwinoAlikeParser.parseEmotes(container_node, resolver);

        let c = null;
        while ((c = target.lastChild))
            target.removeChild(c)

        let marked_nodes = container_node.querySelectorAll('[x-raw],[x-nested]');
        for (let i = 0; i < marked_nodes.length; i++) {
            marked_nodes[i].removeAttribute('x-raw');
            marked_nodes[i].removeAttribute('x-nested');
        }

        const delete_empty = ( tag: HTMLElement|null ): boolean => {
            if (!tag || tag.nodeType !== Node.ELEMENT_NODE) return false;

            if (
                (tag as HTMLElement).tagName === 'BR' ||
                ((tag as HTMLElement).tagName === 'P' && !(tag as HTMLElement).innerHTML.match(/\S/))
            ) {
                c.remove();
                return true;
            } else return false;
        }

        do { c = container_node.firstChild; } while (delete_empty(c));
        do { c = container_node.lastChild; }  while (delete_empty(c));

        // Properly nest orphaned LIs
        let orphan = null;
        while (orphan = container_node.querySelector('*:not(ul):not(ol)>li')) {
            const new_parent = document.createElement('ul');
            let next_sibling = null;

            // Remove tailing BRs and concat additional LIs
            let did_concat = false;
            do {
                did_concat = false;

                // Trim line breaks and white spaces
                while (
                    orphan.nextSibling !== null && (
                        (orphan.nextSibling.nodeType === Node.TEXT_NODE && orphan.nextSibling.textContent.trim().length === 0) ||
                        (orphan.nextSibling.nodeType === Node.ELEMENT_NODE && orphan.nextSibling.tagName === 'BR')
                    )
                ) orphan.nextSibling.remove();

                // Concat following LIs into the same UL
                while ((next_sibling = orphan.nextSibling) && next_sibling.nodeType === Node.ELEMENT_NODE && next_sibling.tagName === 'LI') {
                    new_parent.appendChild(next_sibling);
                    did_concat = true;
                }
            } while (did_concat);

            // Place the new list right before the triggering LI and move the LI inside
            orphan.parentElement.insertBefore( new_parent, orphan );
            new_parent.insertBefore( orphan, new_parent.firstElementChild );
        }

        TwinoAlikeParser.processPlayerNames( container_node );
        if (playerCacheRefreshing) window.clearTimeout(playerCacheRefreshing);

        const missing_ids: Array<number> = Object.entries( playerCache.id ).filter( ([,cache]) => cache === null ).map( ([id]) => parseInt(id) );
        const missing_names: Array<string> = Object.entries( playerCache.name ).filter( ([,cache]) => cache === null ).map( ([name]) => name );

        if (missing_ids.length > 0 || missing_names.length > 0) {
            playerCacheRefreshing = window.setTimeout(() => {
                $.ajax.background().easySend((document.querySelector('base[href]').getAttribute('href') ?? '') + '/jx/soul/exists' , {
                    names: missing_names, ids: missing_ids
                }, (data) => {

                    (data as unknown as { data: Array<playerCacheEntry> })?.data?.forEach( player => {
                        playerCache.name[ player.queryName ] = player;
                        if (player.id > 0) playerCache.id[ player.id ] = player;
                    } );

                    missing_ids.forEach( id => { if (!playerCache.id[id]) delete playerCache.id[id] } );
                    missing_names.forEach( name => { if (!playerCache.name[name]) delete playerCache.name[name] } );
                    playerCacheRefreshing = null;
                    TwinoAlikeParser.processPlayerNames( target );
                    if (targetCallback) targetCallback(target.innerHTML);
                }, {}, () => playerCacheRefreshing = null);
            }, 1000);
        }

        while ((c = container_node.firstChild))
            target.appendChild(c);
    }

    parseToString( text: string, resolver: emoteResolver, options: TwinoClientOptions = {}, targetCallback = (s:string)=>{} ): string {
        let proxy = document.createElement( 'div' );
        this.parseTo( text, proxy, resolver, options, targetCallback );
        return proxy.innerHTML;
    }

    private static parsePlainBlock( elem: HTMLElement, content: string|null = null ): TwinoInterimBlock {

        if (elem.nodeType === Node.ELEMENT_NODE) {

            let classes: Array<string> = [];
            for (let c = 0; c < elem.classList.length; c++) classes.push( elem.classList.item(c) );

            let attribs: Array<[string,string]> = [];
            for (let a = 0; a < elem.attributes.length; a++) attribs.push( [ elem.attributes.item(a).name, elem.attributes.item(a).value ] )

            return new TwinoInterimBlock( content ?? elem.innerText, elem.tagName.toLowerCase(), classes, attribs, elem.outerHTML );

        }

        if (elem.nodeType === Node.TEXT_NODE)
            return new TwinoInterimBlock(elem.textContent.replace(/\s{2,}/,' '));

        return new TwinoInterimBlock( );
    }

    private static buildInterimBlockTree( elem: HTMLElement ): TwinoInterimBlock {

        if (elem.nodeType === Node.TEXT_NODE)
            return new TwinoInterimBlock(elem.textContent);

        if (elem.nodeType === Node.ELEMENT_NODE) {

            let blocks: Array<TwinoInterimBlock> = [];

            for (let i = 0; i < elem.childNodes.length; i++) {
                let simple = true
                for (let j = 0; j < elem.childNodes[i].childNodes.length; j++)
                    if (elem.childNodes[i].childNodes[j].nodeType === Node.ELEMENT_NODE) simple = false;

                if (simple) blocks.push( TwinoAlikeParser.parsePlainBlock( elem.childNodes[i] as HTMLElement ) );
                else blocks.push(TwinoAlikeParser.buildInterimBlockTree(elem.childNodes[i] as HTMLElement));
            }

            return TwinoAlikeParser.parsePlainBlock( elem as HTMLElement, HTMLConverterFromBlocks.anyBlocks( blocks.filter( block => !block.isEmpty() ), elem as HTMLElement ) );
        }

        return new TwinoInterimBlock('');

    }

    private static parseNestedBlock( elem: HTMLElement ): string {

        return HTMLConverterFromBlocks.anyBlocks([
            TwinoAlikeParser.buildInterimBlockTree(elem)
        ], elem.parentNode as HTMLElement);

    }

    parseFrom( htmlText: string, opmode: number ): string {

        let container_node = document.createElement('p');
        if (opmode & this.OpModeQuote) container_node.classList.add('no-quote', 'no-announce');
        if (opmode & this.OpModeRaw)   container_node.classList.add('raw-fallback');
        container_node.innerHTML = htmlText;

        TwinoAlikeParser.postprocessReverseDOM( container_node );
        return TwinoAlikeParser.postprocessReverseText( TwinoAlikeParser.parseNestedBlock( container_node ) );
    }

}
