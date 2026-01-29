class MapHandler {
    constructor(containerId, center, zoom) {
        this.map = L.map(containerId).setView(center, zoom);
        this.initTiles();
    }

    initTiles() {
        L.tileLayer('https://server.arcgisonline.com/...').addTo(this.map);
    }
}
