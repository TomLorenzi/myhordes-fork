// LESS files
require('../less/theme.less');
require('../less/grid.less');
require('../less/mixins.less');
require('../less/mobile.less');
require('../less/intl.less');

// CSS files
require('../css/app.css');

// JavaScript and TypeScript files
import Client from '../ts/client'
import Ajax from '../ts/ajax'
import HTML from '../ts/html'
import MessageAPI from '../ts/messages'
import Components from "../ts/react";
const matchAll = require('string.prototype.matchall');
matchAll.shim();

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

document.addEventListener('DOMContentLoaded', function() {
    $.ajax.setDefaultNode( document.getElementById('content') );
    $.html.init();
    if (!document.body.classList.contains('page-attract'))
        document.body.classList.add( 'icon-zoom-' + $.client.config.iconZoom.get() )
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

    for (let i = 0; i < game_menu_hide.length; i++)
        game_menu_hide[i].style.display = null;

    let content_width = 0;
    for (let i = 0; i < game_menu_elems.length; i++)
        content_width += game_menu_elems[i].offsetWidth;

    if (game_menu.offsetWidth - content_width < 80) {
        if(game_menu_burger !== null)
            game_menu_burger.style.display = null;
        
        for (let i = 0; i < game_menu_hide.length; i++)
            game_menu_hide[i].style.display = 'none';
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
            document.querySelectorAll('#gma .alt-hunger-bar').forEach( e => e.classList.add('hidden') );
        }
        if (status.offsetLeft < 156 || status.offsetTop > 10) {
            rucksack.classList.add('fix-bottom');
            ap_counter.classList.add('fix-left');
            if (ghoul) {
                ghoul.classList.add('hidden');
                document.querySelectorAll('#gma .alt-hunger-bar').forEach( e => e.classList.remove('hidden') );
            }
        }
    }
};

const resize_map = function() {
    let outer_maps = document.querySelectorAll('.map');
    for (let i = 0; i < outer_maps.length; i++)
        outer_maps[i].dispatchEvent(new Event("x-resize", { bubbles: false, cancelable: true }));
};

const resizer = function() {
    resize_game_menu();
    resize_map();

    //console.log(game_menu.offsetWidth, content_width, game_menu.offsetWidth - content_width);
};
window.addEventListener("resize", resizer);
window.addEventListener('load', resizer, {once: true});
window.addEventListener( 'load', () => {
    if (document.querySelector('#crowdin-jipt-mask')) {
        console.log('do');
        let button = document.createElement('button');
        button.style.display = 'inline-block';
        button.style.width = 'auto';
        button.style.position = 'fixed';
        button.style.right = '10px';
        button.style.bottom = '10px';
        button.innerText = 'Exit In-Context Translation';
        button.style.zIndex = '2047483650';
        button.addEventListener('click', () => {
            window.location.assign( url + '/r/ach' );
        })
        document.body.append( button );
    }
}, {once: true} )

// Import common modules
require('./modules/common-modules.js');