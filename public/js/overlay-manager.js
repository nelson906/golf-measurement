class OverlayManager {
    constructor(map, imageUrl, initialBounds) {
        this.map = map;
        this.config = {
            bounds: initialBounds,
            rotation: 0,
            scaleX: 1,
            scaleY: 1,
            offsetX: 0,
            offsetY: 0
        };
        this.createOverlay(imageUrl);
    }

    createOverlay(imageUrl) {
        // Logica overlay con trasformazioni
    }

    saveConfig() {
        // Salva config nel DB via AJAX
        return fetch('/api/courses/' + courseId + '/overlay', {
            method: 'POST',
            body: JSON.stringify(this.config)
        });
    }

    loadConfig(config) {
        // Carica config salvata
        this.config = config;
        this.applyTransforms();
    }
}
