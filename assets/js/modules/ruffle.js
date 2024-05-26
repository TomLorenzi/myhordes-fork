"use strict";

require("@ruffle-rs/ruffle");
window.RufflePlayer.config = {
    "publicPath": "/build/ruffle",
    "contextMenu": "off",
    "autoplay": "on",
    'unmuteOverlay': "hidden",
    "splashScreen": false,
    "frameRate": 25
}
/*window.RufflePlayer.config.publicPath = "/build/ruffle";
window.RufflePlayer.config.contextMenu = false;*/

// Define web component <hordes-map />
customElements.define('hordes-flash', class FlashPlayer extends HTMLElement {

    #_initialize() {
        let ruffle = window.RufflePlayer.newest();
        let player = ruffle.createPlayer();
        this.innerHTML = '';
        this.appendChild(player);
        player.addEventListener('loadedmetadata', () => console.log(player.metadata))
        player.load({
            url: this.dataset.src,
            parameters: this.dataset.parameters ?? '',
            maxExecutionDuration: 2,
            backgroundColor: '#5c2b20',
            quality: "low",
            scale: "noScale",
            playerRuntime: "air"
        });
    }


    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue || name === 'data-src' || name === 'data-parameters') return;
        this.#_initialize();
    }

    constructor() {
        super();
        this.#_initialize();
    }

}, {  });