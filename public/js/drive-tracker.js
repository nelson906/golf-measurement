class DriveTracker {
    constructor(map) {
        this.map = map;
        this.currentDrive = null;
        this.drives = [];
    }

    startDrive() {
        this.currentDrive = {
            tee: null,
            shots: [],
            markers: [],
            lines: []
        };
    }

    addShot(latlng) {
        // Logica aggiunta colpi
    }

    completeDrive() {
        // Salva nel DB
        return fetch('/api/drives', {
            method: 'POST',
            body: JSON.stringify({
                hole_id: this.holeId,
                tee_point: this.currentDrive.tee,
                shots: this.currentDrive.shots
            })
        });
    }
}
