interface emoteResolver { (name: string): string|null }

class TwinoInterimBlock {

    public readonly nodeName: string|null;
    public readonly nodeText: string;
    public readonly nodeClasses: Array<string>;
    public readonly nodeAttribs: Array<[string,string]>;

    constructor(text: string = '', name: string|null = null, classes: string|Array<string> = [], attribs: Array<[string,string]> = []) {
        this.nodeText = text;
        this.nodeName = name;

        if (name !== null) {
            this.nodeClasses = (typeof classes === 'string') ? [classes] : classes;
            this.nodeAttribs = attribs;
        }

    }

    isEmpty(): boolean { return !this.nodeText && this.nodeName === null }
    isPlainText(): boolean { return this.nodeName === null }

    hasClass(cls: string): boolean { return this.nodeClasses.indexOf( cls ) !== -1; }
    getAttribute(attrib: string): string|null {
        let a = this.nodeClasses.find( e => e[0] === attrib );
        return a ? a[1] : null;
    }
}

class TwinoRegexResult {

    static readonly TypeBB = 1;
    static readonly TypeShortBB = 2;
    static readonly TypeInset = 3;
    static readonly TypeEmote = 4;

    private static readonly TypeInsetA = 31;
    private static readonly TypeInsetB = 32;
    private static readonly TypeInsetC = 33;

    private readonly type;
    private readonly match;

    public readonly index:  number;
    public readonly length: number;

    private constructor(type: number, match: RegExpMatchArray) {
        this.type = type;
        this.match = match;

        this.index  = match.index;
        this.length = match[0].length;

        if (this.type === TwinoRegexResult.TypeInset) {

            if      (this.match[1] !== undefined) this.type = TwinoRegexResult.TypeInsetA;
            else if (this.match[2] !== undefined) this.type = TwinoRegexResult.TypeInsetB;
            else if (this.match[4] !== undefined) this.type = TwinoRegexResult.TypeInsetC;
            else throw new Error( 'Unable to guess TRR subtype for TypeInset instance.' );

        }
    }

    private static getRegex(type: number): RegExp {

        switch (type) {
            case TwinoRegexResult.TypeBB:      return /\[(.*?)(?:=([^\]]*))?]([\s\S]*?)\[\/\1]/gm;
            case TwinoRegexResult.TypeShortBB: return /(?:([^\w\s]){2})([\s\S]*?)\1{2}/gm;
            case TwinoRegexResult.TypeInset:   return /{([a-zA-Z]+)}|{([a-zA-Z]+),([\w,]*)}|{([a-zA-Z]+)(\d+)}/g;
            case TwinoRegexResult.TypeEmote:   return /(?::(\w+?):)|([:;].)/g;
            default: throw Error( 'No regex defined for this type of TRR!' )
        }

    }

    static create( type: number, s: string ): Array<TwinoRegexResult> {
        let results: Array<TwinoRegexResult> = [];
        for (let m of s.matchAll( this.getRegex( type ) ))
            results.push( new TwinoRegexResult( type, m )  )
        return results;
    }

    raw(): string { return this.match[0]; }

    nodeType(): string {
        switch (this.type) {
            case TwinoRegexResult.TypeBB:
            case TwinoRegexResult.TypeShortBB:
            case TwinoRegexResult.TypeInsetA:
                return this.match[1].toLowerCase();
            case TwinoRegexResult.TypeInsetB:
                return this.match[2].toLowerCase();
            case TwinoRegexResult.TypeInsetC:
                return this.match[4].toLowerCase();
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
                return (this.match[2] + this.match[2]);
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
            default:
                throw new Error('Attempt to access node info of a TRR not supporting additional node information.');
        }
    }

}

class TwinoConverterToBlocks {

    public static rangeBlocks( match: TwinoRegexResult, nested: boolean = false ): [boolean,Array<TwinoInterimBlock>] {

        let changed: boolean = false;
        let blocks: Array<TwinoInterimBlock> = [];

        switch (match.nodeType()) {
            case 'b': case 'i': case 'u': case 's': case 'ul': case 'ol': case 'li':
                blocks.push( new TwinoInterimBlock(match.nodeContent(), match.nodeType()) ); changed = true; break;
            case '**': blocks.push( new TwinoInterimBlock(match.nodeContent(), 'b') ); changed = true; break;
            case '//': blocks.push( new TwinoInterimBlock(match.nodeContent(), 'i') ); changed = true; break;
            case '--': blocks.push( new TwinoInterimBlock(match.nodeContent(), 's') ); changed = true; break;
            case 'spoiler': blocks.push( new TwinoInterimBlock(match.nodeContent(), 'span', match.nodeType()) ); changed = true; break;
            case 'quote':
                if ( match.nodeInfo() )
                    blocks.push( new TwinoInterimBlock(match.nodeInfo(), 'span', 'quoteauthor') );
                blocks.push( new TwinoInterimBlock(match.nodeContent(), 'blockquote') );
                changed = true; break;
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
                blocks.push( new TwinoInterimBlock(match.nodeContent(), 'a', match.nodeType(), [ ['href', match.nodeInfo()], ['target', '_blank'], ['x-raw','1'] ]) );
                changed = true;
                break;
            case 'bad': case 'glory':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'span', match.nodeType(), [['x-nested','1']]) );
                changed = true; break;
            case 'aparte':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'span', 'sideNote', [['x-nested','1']]) );
                changed = true; break;
            case 'admannounce':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'div', 'adminAnnounce', [['x-nested','1']]) );
                changed = true; break;
            case 'modannounce':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'div', 'modAnnounce', [['x-nested','1']]) );
                changed = true; break;
            case 'announce':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'div', 'oracleAnnounce', [['x-nested','1']]) );
                changed = true; break;
            case 'rp':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else {
                    if ( match.nodeInfo() )
                        blocks.push( new TwinoInterimBlock(match.nodeInfo(), 'span', 'rpauthor', [['x-raw','1']]) );
                    blocks.push( new TwinoInterimBlock(match.nodeContent(), 'div', 'rpText', [['x-nested','1']]) );
                }
                changed = true; break;
            default: blocks.push( new TwinoInterimBlock(match.raw()) ); break;
        }

        return [changed,blocks];
    }

    public static insets( match: TwinoRegexResult, listspace: boolean ): Array<TwinoInterimBlock> {
        let blocks: Array<TwinoInterimBlock> = [];

        switch ( match.nodeType() ) {
            case 'hr': blocks.push( new TwinoInterimBlock( '', match.nodeType()) ); break;
            case 'br': blocks.push( listspace ? new TwinoInterimBlock() : new TwinoInterimBlock( '', match.nodeType()) ); break;
            case 'dice': case 'dc': case 'de': case 'des': case 'd': case 'w':
                if (match.nodeInfo() && ["4","6","8","10","12","20","100"].indexOf( match.nodeInfo() ) !== -1)
                    blocks.push( new TwinoInterimBlock( '???', 'div', 'dice-' + match.nodeInfo()) );
                else blocks.push( new TwinoInterimBlock(match.raw()) );
                break;
            case 'letter': case 'lettre':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'letter-a') ); break;
            case 'consonant': case 'consonne':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'letter-c') ); break;
            case 'vowel': case 'voyelle':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'letter-v') ); break;
            case 'pfc': case 'rps': case 'ssp':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'rps') ); break;
            case 'flip': case 'coin': case 'ht': case 'pf': case 'mw':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'coin') ); break;
            case 'carte': case 'card': case 'skat': case 'blatt':
                blocks.push( new TwinoInterimBlock( '???', 'div', 'card') ); break;
            case 'citizen': case 'rnduser': case 'user': case 'spieler':
                let attribs = match.nodeInfo() ? match.nodeInfo().split(',') : [];
                if (!attribs[0]) attribs[0] = 'any';
                if (!attribs[1]) attribs[1] = '0';
                blocks.push( new TwinoInterimBlock( attribs[1] === '0' ? '???' : '??? [' + attribs[1] + ']', 'div', 'citizen', [['x-a', attribs[0]], ['x-b', attribs[1]]]) );
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

    public static anyBlocks( blocks: Array<TwinoInterimBlock> ): string {

        let cursor = 0;
        let nextBlock = function(): TwinoInterimBlock {
            return blocks.length > (cursor+1) ? blocks[cursor++] : null;
        }
        let peekBlock = function(): TwinoInterimBlock {
            return blocks.length > (cursor+1) ? blocks[cursor+1] : null;
        }

        let ret = '';

        let block: TwinoInterimBlock;
        while (block = nextBlock()) {
            if (block.isEmpty()) continue;
            const peek = peekBlock();

            if (block.isPlainText()) ret += block.nodeText;
            else switch (block.nodeName) {
                case 'b': case 'strong':
                    ret += HTMLConverterFromBlocks.wrapBlock( block, 'b' );
                    break;
                case 'i': case 'u': case 's': case 'ul': case 'ol': case 'li':
                    ret += HTMLConverterFromBlocks.wrapBlock( block, block.nodeName );
                    break;
                case 'span':
                    if (block.hasClass('spoiler'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'spoiler' )
                    else if (block.hasClass('quoteauthor')) {
                        if (peek.nodeName === 'blockquote') {
                            ret += HTMLConverterFromBlocks.wrapBlock( nextBlock(), 'quote', block.nodeText )
                        }
                    }
                    break;
                case 'blockquote':
                    ret += HTMLConverterFromBlocks.wrapBlock( block, 'quote' );
                    break;
                case 'img':
                    let src = block.getAttribute('src');
                    let alt = block.getAttribute('alt') ?? '';
                    if (src) ret += HTMLConverterFromBlocks.rangeBlock( alt, 'img', src );
                    break;
                case 'link':
                    let href = block.getAttribute('href');
                    if (href) ret += HTMLConverterFromBlocks.wrapBlock( block, 'link', href );
                    break;
                default:
                    ret += block.nodeText;
                    break;
            }
        }

        return ret;
    }

}

export default class TwinoAlikeParser {

    private static lego(blocks: Array<TwinoInterimBlock>, elem: HTMLElement): void {
        for (let i = 0; i < blocks.length; i++) {
            if (blocks[i].isEmpty()) continue;

            let node = null;
            if ( blocks[i].isPlainText() )
                node = document.createTextNode( blocks[i].nodeText );
            else {
                node = document.createElement( blocks[i].nodeName );
                node.appendChild( document.createTextNode( blocks[i].nodeText ) );
                for (let nodeClass of blocks[i].nodeClasses) node.classList.add( nodeClass );
                for (let nodeAttribTuple of blocks[i].nodeAttribs) node.setAttribute( nodeAttribTuple[0], nodeAttribTuple[1]);
            }
            elem.parentNode.insertBefore(node, elem);
        }
        elem.parentNode.removeChild(elem);
    }

    private static parseRangeBlocks(elem: HTMLElement, secondary: boolean = false, nested: boolean = false): boolean {
        let changed = false;
        if (elem.nodeType === Node.TEXT_NODE) {
            let str = elem.textContent;
            let current_offset = 0;
            let blocks: Array<TwinoInterimBlock> = [];

            for (const match of TwinoRegexResult.create( secondary ? TwinoRegexResult.TypeShortBB : TwinoRegexResult.TypeBB, str ) ) {

                if (current_offset < match.index )
                    blocks.push( new TwinoInterimBlock( str.substr(current_offset,match.index-current_offset) ) );

                const conversion = TwinoConverterToBlocks.rangeBlocks( match, nested );
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
                if (children[i].nodeType === Node.ELEMENT_NODE) {
                    let node = (children[i]) as HTMLElement;
                    if (node.hasAttribute( 'x-raw' )) skip = true;
                    if (node.hasAttribute( 'x-nested' )) nested = true;
                }

                if (!skip) changed = changed || TwinoAlikeParser.parseRangeBlocks(children[i] as HTMLElement,secondary, nested);
            }

        }
        return changed;
    }

    private static parseInsets(elem: HTMLElement, listspace: boolean = false) {
        if (elem.nodeType === Node.TEXT_NODE) {
            let str = elem.textContent;
            let current_offset = 0;
            let blocks: Array<TwinoInterimBlock> = [];

            for (const match of TwinoRegexResult.create( TwinoRegexResult.TypeInset, str )) {

                if (current_offset < match.index )
                    blocks.push( new TwinoInterimBlock( str.substr(current_offset,match.index-current_offset ) ));

                for (const result of TwinoConverterToBlocks.insets( match, listspace ))
                    blocks.push( result );

                current_offset = (match.length + match.index);
            }

            if (blocks.length === 0) blocks.push( new TwinoInterimBlock(str) );
            else if (current_offset < str.length) blocks.push( new TwinoInterimBlock( str.substr( current_offset )));

            TwinoAlikeParser.lego(blocks,elem);
        } else {

            let is_list_wrapper = ['UL','OL'].indexOf(elem.tagName) !== -1;
            let is_list_entry   = ['LI'].indexOf(elem.tagName) !== -1;
            let children = elem.childNodes;
            for (let i = 0; i < children.length; i++)
                TwinoAlikeParser.parseInsets(children[i] as HTMLElement, (listspace || is_list_wrapper) && !is_list_entry);

        }
    }

    private static parseEmotes(elem: HTMLElement, resolver: emoteResolver) {
        if (elem.nodeType === Node.TEXT_NODE) {
            let str = elem.textContent;
            let regex_wrappers = /(?::(\w+?):)|([:;].)/g;
            let current_offset = 0;
            let blocks: Array<TwinoInterimBlock> = [];

            for (const match of TwinoRegexResult.create( TwinoRegexResult.TypeEmote, str )) {

                if (current_offset < match.index )
                    blocks.push( new TwinoInterimBlock( str.substr(current_offset,match.index-current_offset ) ));

                let ctrl = resolver( match.nodeType() );
                if (ctrl)
                    blocks.push( new TwinoInterimBlock( '', 'img', [], [['src', ctrl], ['x-foxy-proxy', match.nodeType()]]) );
                else blocks.push( new TwinoInterimBlock( match.raw() ) );
                current_offset = (match.length + match.index);
            }

            if (blocks.length === 0) blocks.push( new TwinoInterimBlock(str) );
            else if (current_offset < str.length) blocks.push( new TwinoInterimBlock(str.substr( current_offset )) );

            TwinoAlikeParser.lego(blocks,elem);
        } else {
            let children = elem.childNodes;
            for (let i = 0; i < children.length; i++)
                TwinoAlikeParser.parseEmotes(children[i] as HTMLElement, resolver);
        }
    }

    parseTo( text: string, target: HTMLElement, resolver: emoteResolver ): void {

        let container_node = document.createElement('p');
        container_node.innerText = text.replace(/(\r\n|\n|\r)/gm,'{br}');

        let changed = true;
        while (changed) changed = changed && TwinoAlikeParser.parseRangeBlocks(container_node,false);
        TwinoAlikeParser.parseInsets(container_node);
        TwinoAlikeParser.parseEmotes(container_node, resolver);

        changed = true;
        while (changed) changed = changed && TwinoAlikeParser.parseRangeBlocks(container_node,true);

        let c = null;
        while ((c = target.lastChild))
            target.removeChild(c)

        let marked_nodes = container_node.querySelectorAll('[x-raw],[x-nested]');
        for (let i = 0; i < marked_nodes.length; i++) {
            marked_nodes[i].removeAttribute('x-raw');
            marked_nodes[i].removeAttribute('x-nested');
        }

        while ((c = container_node.firstChild))
            target.appendChild(c);
    }

    private static parsePlainBlock( elem: HTMLElement ): TwinoInterimBlock {

        if (elem.nodeType === Node.ELEMENT_NODE) {

            let classes: Array<string> = [];
            for (let c = 0; c < elem.classList.length; c++) classes.push( elem.classList.item(c) );

            let attribs: Array<[string,string]> = [];
            for (let a = 0; a < elem.attributes.length; a++) attribs.push( [ elem.attributes.item(a).name, elem.attributes.item(a).value ] )

            return new TwinoInterimBlock( elem.innerText, elem.tagName, classes, attribs );

        }

        if (elem.nodeType === Node.TEXT_NODE)
            return new TwinoInterimBlock( elem.textContent );

        return new TwinoInterimBlock( );
    }

    private static parseNestedBlock( elem: HTMLElement ): string {

        if (elem.nodeType === Node.TEXT_NODE)
            return elem.textContent;

        if (elem.nodeType === Node.ELEMENT_NODE) {

            let blocks: Array<TwinoInterimBlock> = [];

            for (let i = 0; i < elem.childNodes.length; i++) {
                let simple = true
                for (let j = 0; j < elem.childNodes[i].childNodes.length; j++)
                    if (elem.childNodes[i].childNodes[j].nodeType === Node.ELEMENT_NODE) simple = false;

                if (simple) blocks.push( TwinoAlikeParser.parsePlainBlock( elem.childNodes[i] as HTMLElement ) );
                else blocks.push( new TwinoInterimBlock( TwinoAlikeParser.parseNestedBlock(elem.childNodes[i] as HTMLElement) ) );
            }

            return HTMLConverterFromBlocks.anyBlocks( blocks );
        }

        return '';

    }

    parseFrom( htmlText: string, resolver: emoteResolver ): string {

        let container_node = document.createElement('p');
        container_node.innerHTML = htmlText;

        return TwinoAlikeParser.parseNestedBlock( container_node );
    }

}