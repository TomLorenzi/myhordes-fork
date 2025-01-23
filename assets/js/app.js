// LESS files
require('../less/theme.less');
require('../less/grid.less');
require('../less/mixins.less');
require('../less/mobile.less');
require('../less/intl.less');

// CSS files
require('../styles/app.css');

// JavaScript and TypeScript files
import Client from '../ts/client'
import Ajax from '../ts/ajax'
import HTML from '../ts/html'
import MessageAPI from '../ts/messages'
import Components from "../ts/react";
import Sortable from 'sortablejs';
window.Sortable = Sortable;

import {init} from "../ts/v2/init";
init();

// Get the base URL
const base_node = document.getElementsByTagName('base');
const url = base_node.length === 0 ? '' : base_node[0].getAttribute('href');

let $ = {
    ajax: new Ajax(url),
    html: new HTML(),
    client: new Client(),
    msg: new MessageAPI(),
    components: new Components(),
    vendor: {
        punycode: require("./punycode")
    }
};
window.$ = $;

document.addEventListener('tokenExchangeCompleted', function() {
    $.ajax.setDefaultNode( document.getElementById('content') );
    $.html.init();
    if (!document.body.classList.contains('page-attract')) {
        document.body.classList.add('icon-zoom-' + $.client.config.iconZoom.get())
        document.body.classList.add('forum-font-' + $.client.config.forumFontSize.get())
    }
    const initial_landing = document.documentElement.getAttribute('x-ajax-landing');
    if (initial_landing) $.ajax.no_loader().load( null, initial_landing, true, {} );
}, {once: true, passive: true});

window.addEventListener('popstate', function(event) {
    $.ajax.load( document.getElementById('content'), event.state, false );
});

const resize_game_menu = function() {
    let game_menu = document.querySelector('#gma');
    let game_menu_elems = document.querySelectorAll('#gma>div.game-bar>*:not(.clock)');
    let game_menu_burger = document.querySelector('#gma>div.game-bar>ul.text-menu>li.burger-button');
    let game_menu_hide = document.querySelectorAll('#gma>div.game-bar>ul.text-menu>li:not(.burger-button),#poll-spacer');

    if (!game_menu) return;

    if(game_menu_burger !== null)
        game_menu_burger.style.display = 'none';

    for (let i = 0; i < game_menu_hide.length; i++) {
        game_menu_hide[i].style.display = null;
        game_menu_hide[i].querySelectorAll('img').forEach(f => f.style.display = 'none');
    }

    let content_width = 0;
    for (let i = 0; i < game_menu_elems.length; i++)
        content_width += game_menu_elems[i].offsetWidth;

    if (game_menu.offsetWidth - content_width < 80) {
        if(game_menu_burger !== null)
            game_menu_burger.style.display = null;
        
        for (let i = 0; i < game_menu_hide.length; i++) {
            game_menu_hide[i].style.display = 'none';
            game_menu_hide[i].querySelectorAll('img').forEach(f => f.style.display = 'inline-block');
        }
    } else {
        let dropdown = document.querySelector('#gma>.game-dropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
            dropdown.style.height = '0px'
        }
    }

    let ap_counter = document.querySelector('#gma>div.game-bar>div.ulcont');
    if (ap_counter) {
        ap_counter.classList.remove('fix-left');
        if (ap_counter.offsetLeft < 120 || ap_counter.offsetTop > 10)
            ap_counter.classList.add('fix-left');
    }

    let rucksack = document.querySelector('#gma>div.game-bar>ul.rucksack');
    let status = document.querySelector('#gma>div.game-bar>ul.status');
    let ghoul = document.querySelector('#gma>div.game-bar>ul.status>.status-ghoul');
    if (rucksack && status) {
        rucksack.classList.remove('fix-bottom');
        if (ghoul) {
            ghoul.classList.remove('hidden');
            document.body.classList.add('alt-ghoul-hunger-bar-hidden');
        }
        if (status.offsetLeft < 156 || status.offsetTop > 10) {
            rucksack.classList.add('fix-bottom');
            ap_counter.classList.add('fix-left');
            if (ghoul) {
                ghoul.classList.add('hidden');
                document.body.classList.remove('alt-ghoul-hunger-bar-hidden');
            }
        }
    }
};

const resizer = function() {
    resize_game_menu();

    //console.log(game_menu.offsetWidth, content_width, game_menu.offsetWidth - content_width);
};
window.addEventListener("resize", resizer);
window.addEventListener('load', resizer, {once: true});
window.addEventListener( 'load', () => {
    if (document.querySelector('html.lang-base-ach')) {
        let button = document.createElement('button');
        button.style.display = 'inline-block';
        button.innerText = 'Exit In-Context Translation';
        let a = document.createElement('a');
        a.href = url + '/r/ach';
        a.target = '_self';
        a.style.zIndex = '2047483650';
        a.style.zIndex = '2047483650';
        a.style.width = 'auto';
        a.style.position = 'fixed';
        a.style.right = '10px';
        a.style.bottom = '10px';
        a.style.display = 'block';
        a.appendChild( button );
        //button.addEventListener('click', () => {
        //    window.location.assign( url + '/r/ach' );
        //    window.location.reload();
        //})
        document.body.append( a );
    }
}, {once: true} )

// Import common modules
require('../ts/modules/common-modules.ts');
require('../ts/toaster');