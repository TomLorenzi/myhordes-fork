interface emoteResolver { (name: string): [string|null,string] }

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
        let a = this.nodeAttribs.find( e => e[0] === attrib );
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
            else throw new Error( 'Unable to guess TRR subtype for TypeInset instance.' );

        }
    }

    private static getRegex(type: number): RegExp {

        switch (type) {
            case TwinoRegexResult.TypeShortBB: return /(?:([^\w\s]){2})([\s\S]*?)\1{2}/gm;
            case TwinoRegexResult.TypeInset:   return /{([a-zA-Z]+)}|{([a-zA-Z]+),([\w,]*)}|{([a-zA-Z]+)(\d+)}/g;
            case TwinoRegexResult.TypeEmote:   return /(?::(\w+?):)|([:;].)/g;
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
                const node = result[1];

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

    public static rangeBlocks( match: TwinoRegexResult, parents: Array<HTMLElement> ): [boolean,Array<TwinoInterimBlock>] {

        let changed: boolean = false;
        let blocks: Array<TwinoInterimBlock> = [];

        let quotespace = false;
        let nested = false;
        for (let i = 0; i < parents.length; i++) {
            if (!quotespace && (parents[i].tagName === 'BLOCKQUOTE'))
                quotespace = true;

            if (!nested && parents[i].hasAttribute( 'x-nested' )) nested = true;
        }

        switch (match.nodeType()) {
            case 'b': case 'i': case 'u': case 's': case 'ul': case 'ol': case 'li':
                blocks.push( new TwinoInterimBlock(match.nodeContent(), match.nodeType()) ); changed = true; break;
            case '**': blocks.push( new TwinoInterimBlock(match.nodeContent(), 'b') ); changed = true; break;
            case '//': blocks.push( new TwinoInterimBlock(match.nodeContent(), 'i') ); changed = true; break;
            case '--': blocks.push( new TwinoInterimBlock(match.nodeContent(), 's') ); changed = true; break;
            case 'spoiler': blocks.push( new TwinoInterimBlock(match.nodeContent(), 'div', match.nodeType()) ); changed = true; break;
            case 'quote':
                if (!quotespace) {
                    if ( match.nodeInfo() )
                        blocks.push( new TwinoInterimBlock(match.nodeInfo(), 'span', 'quoteauthor') );
                    blocks.push( new TwinoInterimBlock(match.nodeContent(), 'blockquote') );
                    changed = true;
                }

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
                blocks.push( new TwinoInterimBlock(match.nodeContent(), 'a', match.nodeType(), [ ['href', match.nodeInfo()], ['target', '_blank'], ['x-raw','1'] ]) );
                changed = true;
                break;
            case 'bad':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'span', match.nodeType(), [['x-nested','1']]) );
                changed = true; break;
            case 'aparte':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'div', 'sideNote', [['x-nested','1']]) );
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
            case 'glory':
                if (nested) blocks.push( new TwinoInterimBlock(match.nodeContent()) )
                else blocks.push( new TwinoInterimBlock(match.nodeContent(), 'div', 'glory', [['x-nested','1']]) );
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

    public static insets( match: TwinoRegexResult, parents: Array<HTMLElement> ): Array<TwinoInterimBlock> {
        let blocks: Array<TwinoInterimBlock> = [];

        let listspace = false;
        for (let i = 0; i < parents.length; i++) {

            if (!listspace && ['UL', 'OL'].indexOf(parents[i].tagName) !== -1)
                listspace = true;
            else if (listspace && parents[i].tagName === 'LI')
                listspace = false;
        }

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

    public static anyBlocks( blocks: Array<TwinoInterimBlock>, parent: HTMLElement|null ): string {

        let cursor = 0;
        let nextBlock = function(): TwinoInterimBlock {
            return blocks.length > cursor ? blocks[cursor++] : null;
        }
        let peekBlock = function(): TwinoInterimBlock {
            return blocks.length > cursor ? blocks[cursor] : null;
        }

        let quotespace = false;
        let cp = parent;
        while (cp) {
            if (cp.nodeType === Node.ELEMENT_NODE && (cp.tagName === 'BLOCKQUOTE' || cp.classList.contains('no-quote')))
                quotespace = true;
            cp = cp.parentNode as HTMLElement;
        }

        let ret = '';

        let block: TwinoInterimBlock;
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
                    ret += HTMLConverterFromBlocks.wrapBlock( block, block.nodeName );
                    break;
                case 'span':
                    if (block.hasClass('quoteauthor')) {
                        if (peek && peek.nodeName === 'blockquote') {
                            ret += quotespace ? '' : HTMLConverterFromBlocks.wrapBlock( nextBlock(), 'quote', block.nodeText )
                        }
                    } else if (block.hasClass('rpauthor')) {
                        if (peek && peek.nodeName === 'div' && peek.hasClass('rpText')) {
                            ret += HTMLConverterFromBlocks.wrapBlock( nextBlock(), 'rp', block.nodeText )
                        }
                    }
                    else if (block.hasClass('bad'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'bad' );
                    else ret += block.nodeText;
                    break;
                case 'div':
                    if (block.hasClass('spoiler'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'spoiler' )
                    else if (block.hasClass('sideNote'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'aparte' );
                    else if (block.hasClass('adminAnnounce'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'admannounce' );
                    else if (block.hasClass('modAnnounce'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'modannounce' );
                    else if (block.hasClass('oracleAnnounce'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'announce' );
                    else if (block.hasClass('rpText'))
                        ret += HTMLConverterFromBlocks.wrapBlock( block, 'rp' );
                    else if (block.hasClass('glory'))
                        ret += HTMLConverterFromBlocks.wrapBlock(block, 'glory');
                    else ret += block.nodeText;
                    break;
                case 'blockquote':
                    ret += quotespace ? '' : HTMLConverterFromBlocks.wrapBlock( block, 'quote' );
                    break;
                case 'img':
                    let alt = block.getAttribute('alt') ?? '';
                    if (alt) ret += alt;
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
                    if (node.hasAttribute( 'x-raw' )) skip = true;
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
            let children = elem.childNodes;
            for (let i = 0; i < children.length; i++)
                TwinoAlikeParser.parseEmotes(children[i] as HTMLElement, resolver);
        }
    }

    private static preprocessText( s: string ): string {
        let lines: Array<string> = s.split( '\n' );

        let m: Array<string>;

        let ulmode = false, olmode = false;

        for (let i = 0; i < lines.length; i++) {

            //UL
            if ((m = lines[i].match(/^\s*?(?:\[\*]|\s\*\s)\s*(.*?)$/m))) {
                if (olmode) { lines.splice(i,0,'[/ol]'); olmode = false; i++ }
                lines[i] = '[li]' + m[1] + '[/li]';
                if (!ulmode) { lines.splice(i,0,'[ul]'); ulmode = true; i++ }
                continue;
            } else if (ulmode) { lines.splice(i,0,'[/ul]'); ulmode = false; i++; }

            //OL
            if ((m = lines[i].match(/^\s*?\[0]\s*(.*?)$/m))) {
                lines[i] = '[li]' + m[1] + '[/li]';
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

    parseTo( text: string, target: HTMLElement, resolver: emoteResolver ): void {

        let container_node = document.createElement('p');
        container_node.innerText = TwinoAlikeParser.preprocessText( text );

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

    private static parsePlainBlock( elem: HTMLElement, content: string|null = null ): TwinoInterimBlock {

        if (elem.nodeType === Node.ELEMENT_NODE) {

            let classes: Array<string> = [];
            for (let c = 0; c < elem.classList.length; c++) classes.push( elem.classList.item(c) );

            let attribs: Array<[string,string]> = [];
            for (let a = 0; a < elem.attributes.length; a++) attribs.push( [ elem.attributes.item(a).name, elem.attributes.item(a).value ] )

            return new TwinoInterimBlock( content ?? elem.innerText, elem.tagName.toLowerCase(), classes, attribs );

        }

        if (elem.nodeType === Node.TEXT_NODE)
            return new TwinoInterimBlock(elem.textContent.replace(/\s{2,}/,' '));

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
                else blocks.push(new TwinoInterimBlock(TwinoAlikeParser.parseNestedBlock(elem.childNodes[i] as HTMLElement)));
            }

            return HTMLConverterFromBlocks.anyBlocks([
                TwinoAlikeParser.parsePlainBlock( elem as HTMLElement, HTMLConverterFromBlocks.anyBlocks( blocks.filter( block => !block.isEmpty() ), elem as HTMLElement ) )
            ], elem.parentNode as HTMLElement);
        }

        return '';

    }

    parseFrom( htmlText: string, isQuoted: boolean ): string {

        let container_node = document.createElement('p');
        if (isQuoted) container_node.classList.add('no-quote');
        container_node.innerHTML = htmlText;

        return TwinoAlikeParser.parseNestedBlock( container_node );
    }

}