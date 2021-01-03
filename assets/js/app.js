// LESS files
require('../less/theme.less');
require('../less/grid.less');
require('../less/mixins.less');
require('../less/mobile.less');
require('../less/intl.less');

// Packages - FontAwesome
require('@fortawesome/fontawesome-free/less/fontawesome.less');
require('@fortawesome/fontawesome-free/js/all.js');

// CSS files
require('../css/app.css');

// JavaScript and TypeScript files
import Client from '../ts/client'
import Ajax from '../ts/ajax'
import HTML from '../ts/html'
import MessageAPI from '../ts/messages'
const matchAll = require('string.prototype.matchall');
matchAll.shim();

require("./attack");

// Get the base URL
const base_node = document.getElementsByTagName('base');
const url = base_node.length === 0 ? '' : base_node[0].getAttribute('href');

let $ = {
    ajax: new Ajax(url),
    html: new HTML(),
    client: new Client(),
    msg: new MessageAPI(),
    vendor: {
        punycode: require("./punycode")
    }
};
window.$ = $;

document.addEventListener('DOMContentLoaded', function() {
    $.ajax.setDefaultNode( document.getElementById('content') );
    $.html.init();
    const initial_landing = document.documentElement.getAttribute('x-ajax-landing');
    if (initial_landing) $.ajax.no_loader().load( null, initial_landing, true, {}, ()=>$.msg.execute() );
    else $.msg.execute();
}, {once: true, passive: true});

window.addEventListener('popstate', function(event) {
    $.ajax.load( document.getElementById('content'), event.state, false );
});

const resize_game_menu = function() {
    let game_menu = document.querySelector('#gma');
    let game_menu_elems = document.querySelectorAll('#gma>div.game-bar>ul:not(.clock)');
    let game_menu_burger = document.querySelector('#gma>div.game-bar>ul.text-menu>li.burger-button');
    let game_menu_hide = document.querySelectorAll('#gma>div.game-bar>ul.text-menu>li:not(.burger-button)');

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