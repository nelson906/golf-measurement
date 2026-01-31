<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buca {{ $hole->hole_number }} - {{ $course->name }}</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; overflow: hidden; }
        #container { display: flex; height: 100vh; }
        #sidebar { width: 340px; background: #2a2a2a; color: white; padding: 20px; overflow-y: auto; }
        #map { flex: 1; position: relative; }
        h1 { font-size: 18px; margin-bottom: 10px; color: #4CAF50; }
        h2 { font-size: 14px; margin-top: 15px; margin-bottom: 10px; color: #81C784; border-bottom: 1px solid #444; padding-bottom: 5px; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #2196F3; text-decoration: none; font-size: 13px; }
        .control-group { background: #333; padding: 12px; margin-bottom: 15px; border-radius: 6px; }
        label { display: block; font-size: 12px; margin-bottom: 5px; color: #bbb; }
        input[type="range"] { width: 100%; margin: 5px 0 10px 0; }
        input[type="number"] { width: 70px; padding: 6px; background: #444; border: 1px solid #555; color: white; border-radius: 3px; font-size: 13px; }
        button { width: 100%; padding: 11px; margin-bottom: 8px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        button:hover { background: #45a049; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        button.active { background: #FF5722; }
        button.secondary { background: #2196F3; }
        button.danger { background: #f44336; }
        button.warning { background: #FF9800; }
        button:disabled { background: #555; cursor: not-allowed; opacity: 0.5; transform: none; }
        .btn-group { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .value-display { display: inline-block; background: #444; padding: 4px 10px; border-radius: 3px; margin-left: 10px; color: #4CAF50; font-weight: bold; font-size: 11px; }
        #measurements { background: #1e1e1e; padding: 12px; border-radius: 6px; max-height: 400px; overflow-y: auto; font-size: 12px; }
        .measurement-item { padding: 8px 0; border-bottom: 1px solid #444; }
        .measurement-item:last-child { border-bottom: none; }
        .shot-detail { padding-left: 20px; font-size: 11px; color: #bbb; }
        .total { font-size: 16px; font-weight: bold; color: #4CAF50; margin: 10px 0; padding: 10px; background: #1a1a1a; border-radius: 4px; }
        #status { background: linear-gradient(135deg, #1e1e1e 0%, #2a2a2a 100%); padding: 10px; border-radius: 4px; text-align: center; font-size: 12px; color: #4CAF50; margin-bottom: 15px; font-weight: 600; border: 1px solid #333; }
        .help-text { font-size: 10px; color: #888; margin-top: 8px; line-height: 1.5; padding: 8px; background: #1e1e1e; border-radius: 4px; }
        .inline { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
        .hole-info { background: #1e1e1e; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 12px; line-height: 1.6; }

        /* Distance Tooltip */
        .distance-tooltip {
            position: absolute;
            background: rgba(0,0,0,0.95);
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            pointer-events: none;
            z-index: 2000;
            display: none;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            border: 2px solid #FF5722;
        }
        .distance-tooltip.active { display: block; }
        .distance-tooltip .shot-label { color: #81C784; font-size: 11px; margin-bottom: 3px; }
        .distance-tooltip .distance-line { margin: 2px 0; }
        .distance-tooltip .delta { color: #4CAF50; font-size: 15px; margin-top: 3px; }
        .distance-tooltip .delta.negative { color: #f44336; }

        /* Mode indicator */
        .mode-indicator {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            display: none;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border: 2px solid #FF5722;
        }
        .mode-indicator.active { display: block; }
        .mode-indicator .icon { font-size: 18px; margin-right: 8px; }

        #map-panel {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 360px;
            height: 260px;
            z-index: 1500;
            background: rgba(0,0,0,0.9);
            border: 2px solid #9C27B0;
            border-radius: 10px;
            overflow: hidden;
            display: none;
            box-shadow: 0 6px 18px rgba(0,0,0,0.45);
            resize: both;
            min-width: 320px;
            min-height: 220px;
        }

        #map-panel.enlarged {
            width: min(900px, 70vw);
            height: min(700px, 70vh);
        }
        #map-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            background: rgba(0,0,0,0.95);
            border-bottom: 1px solid #333;
            font-size: 12px;
            font-weight: 700;
        }
        #map-panel-header span { color: #E1BEE7; }
        #map-panel-header button {
            width: auto;
            padding: 6px 10px;
            margin: 0;
            font-size: 12px;
            border-radius: 6px;
        }
        #map-panel-body { position: relative; width: 100%; height: calc(100% - 41px); overflow: hidden; }
        #ref-map-viewport { position:absolute; left:0; top:0; width:100%; height:100%; transform-origin: 0 0; cursor: grab; }
        #ref-map-viewport.dragging { cursor: grabbing; }
        #ref-map-img { width: 100%; height: 100%; object-fit: contain; display: block; user-select: none; pointer-events: none; }
        #ref-map-canvas { position:absolute; left:0; top:0; width:100%; height:100%; }
    </style>
</head>
<body>
    <div id="container">
        <div id="sidebar">
            <a href="{{ route('courses.show', $course) }}" class="back-link">← Torna al Campo</a>
            <h1>🏌️ Buca {{ $hole->hole_number }}</h1>
            <div class="hole-info">
                <div><strong>{{ $course->name }}</strong></div>
                @if($hole->par)<div>Par: {{ $hole->par }}</div>@endif
                @if($hole->length_yards)<div>{{ $hole->length_yards }} yards</div>@endif
            </div>
            <div id="status">Vista Satellitare Pulita</div>

            <div class="control-group">
                <h2>🎯 Misurazione Drive</h2>
                <div class="inline">
                    <label style="margin:0;">Drive iniziale:</label>
                    <input type="number" id="yards" value="250" min="50" max="400" step="10">
                    <span style="font-size:11px;color:#888;">yards</span>
                </div>

                <button id="tee-btn" onclick="placeTee()">📍 Posiziona TEE</button>
                <button id="shot-btn" onclick="addShot()" disabled>➕ Aggiungi Colpo</button>
                <button id="complete-btn" onclick="completeDrive()" disabled>✓ Completa Drive</button>

                <div class="help-text">
                    <strong>Workflow:</strong><br>
                    1. Posiziona TEE (click sulla mappa)<br>
                    2. Primo colpo automatico a 250yds<br>
                    3. Trascina punti per aggiustare<br>
                    4. Aggiungi altri colpi se serve<br>
                    5. Completa per salvare
                </div>
            </div>

            <div class="control-group">
                <h2>🧭 Setup Buca {{ $hole->hole_number }}</h2>
                <button id="setup-btn" class="secondary" onclick="toggleSetupMode()">🧭 Avvia Setup</button>
                <div class="btn-group" style="margin-top:8px;">
                    <button id="set-tee-yellow-btn" onclick="setPointMode('tee_yellow')" disabled>🟡 TEE Giallo</button>
                    <button id="set-tee-red-btn" onclick="setPointMode('tee_red')" disabled>🔴 TEE Rosso</button>
                    <button id="set-green-btn" onclick="setPointMode('green')" disabled>🟢 GREEN</button>
                    <button id="draw-centerline-btn" onclick="toggleCenterlineDraw()" disabled>📐 Disegna Centerline (satellite)</button>
                </div>
                <div class="btn-group" style="margin-top:8px;">
                    <button id="undo-centerline-btn" class="warning" onclick="undoCenterlinePoint()" disabled>↩️ Undo Punto</button>
                    <button id="clear-centerline-btn" class="danger" onclick="clearCenterline()" disabled>🗑️ Cancella Centerline</button>
                </div>
                <button id="save-geometry-btn" onclick="saveGeometry()" disabled>💾 Salva Geometria</button>
                <button id="reset-geometry-btn" class="danger" onclick="resetGeometry()" disabled>🧹 Reset Geometria</button>

                <details id="calib-details" style="margin-top:15px;">
                    <summary style="cursor:pointer;font-size:14px;color:#81C784;font-weight:700;">🗺️ Calibrazione Mappetta</summary>
                    <div style="margin-top:10px;">
                        <button id="open-map-panel-btn" class="secondary" onclick="toggleMapPanel()" disabled>🗺️ Apri Mappetta</button>
                        <div class="btn-group" style="margin-top:8px;">
                            <button id="start-calib-btn" onclick="startCalibration()" disabled>🎯 Avvia Calibrazione</button>
                            <button id="reset-calib-btn" class="danger" onclick="resetCalibration()" disabled>🧹 Reset Calibrazione</button>
                        </div>
                        <button id="save-calib-btn" onclick="saveCalibration()" disabled>💾 Salva Calibrazione Campo</button>
                    </div>
                </details>

                <h2 style="margin-top:15px;">🏷️ Landing Zone (da Centerline)</h2>
                <div class="inline">
                    <label style="margin:0;">Tee:</label>
                    <select id="tee-color" onchange="refreshLandingZones()" style="flex:1;padding:8px;background:#444;border:1px solid #555;color:white;border-radius:4px;">
                        <option value="yellow">Gialli</option>
                        <option value="red">Rossi</option>
                    </select>
                </div>
                <div class="inline">
                    <label style="margin:0;">Drive (yds):</label>
                    <input type="number" id="drive-yds" value="230" min="50" max="400" step="5">
                    <label style="margin:0;">2° (yds):</label>
                    <input type="number" id="second-yds" value="200" min="50" max="300" step="5">
                </div>
                <div class="inline">
                    <label style="margin:0;">3° (yds):</label>
                    <input type="number" id="third-yds" value="0" min="0" max="250" step="5">
                </div>
                <button id="gen-landing-btn" onclick="refreshLandingZones()" disabled>✨ Genera Landing Zone</button>

                <div class="help-text">
                    <strong>Setup consigliato:</strong><br>
                    1. Avvia Setup<br>
                    2. Imposta TEE (giallo/rosso) e GREEN dalla mappetta<br>
                    3. Disegna centerline sul satellite (click multipli, poi trascina i vertici)<br>
                    4. Salva geometria<br>
                    5. Genera landing zone lungo centerline (dogleg incluso)
                </div>
            </div>

            <div class="control-group">
                <h2>📏 Larghezza Fairway</h2>
                <button id="width-btn" onclick="startWidth()" disabled>📏 Misura Larghezza</button>
                <div id="width-status" style="font-size:11px;color:#888;margin-top:8px;padding:8px;background:#1e1e1e;border-radius:4px;display:none;">
                    In attesa di 2 click...
                </div>
                <div class="help-text">
                    <strong>Modalità separata:</strong><br>
                    • Click pulsante per attivare<br>
                    • 2 click = larghezza fairway<br>
                    • Misura più volte se serve<br>
                    • Disponibile dopo drive
                </div>
            </div>

            <div class="control-group">
                <h2>🗑️ Azioni</h2>
                <div class="btn-group">
                    <button class="secondary" onclick="undoLast()">↩️ Annulla</button>
                    <button class="danger" onclick="clearAll()">🗑️ Cancella</button>
                </div>
            </div>

            <div class="control-group">
                <h2>📊 Risultati</h2>
                <div id="measurements">
                    <div class="measurement-item" style="color:#888;">Inizia misurazione drive</div>
                </div>
            </div>
        </div>
            <div id="map">
                <div id="map-panel">
                    <div id="map-panel-header">
                        <span>Mappetta (clicca per digitizzare)</span>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button class="secondary" onclick="toggleMapPanelSize()" style="margin:0;">⤢</button>
                            <button class="danger" onclick="toggleMapPanel()" style="margin:0;">✕</button>
                        </div>
                    </div>
                    <div id="map-panel-body">
                        <div id="ref-map-viewport">
                            <img id="ref-map-img" alt="Mappa riferimento" />
                            <canvas id="ref-map-canvas"></canvas>
                        </div>
                    </div>
                </div>

                <div class="mode-indicator" id="mode-indicator"></div>
                <div class="distance-tooltip" id="distance-tooltip"></div>
            </div>
        </div>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
const COURSE=@json($course),HOLE=@json($hole),YD=0.9144,CENTER=[{{$course->latitude}},{{$course->longitude}}];
const HOLE_NUMBER=parseInt(HOLE?.hole_number ?? HOLE?.holeNumber ?? (location.pathname.match(/\/holes\/(\d+)\//)?.[1]||'0'),10)||0;
const MAP_URL='{{$course->map_url}}',SAVED_CONFIG=@json($course->overlay_config);

const HOLE_GEOMETRY={
    tee_points:@json($hole->tee_points),
    green_point:@json($hole->green_point),
    centerline:@json($hole->centerline)
};

const SAVED_DRIVES=@json($hole->drives);

let map;

// Stati misurazione - SEPARATI
let teeMode=false,shotMode=false,widthMode=false,driveComplete=false;
let currentDrive=null,drives=[],widths=[],tempWidth=[];
let draggedMarker=null,originalDistance=0,shotIndex=0;

let driveLayers=[];

// Stati setup geometria
let setupMode=false;
let pointMode=null; // 'tee_yellow'|'tee_red'|'green'
let drawingCenterline=false;

let refView={scale:1,tx:0,ty:0,dragging:false,moved:false,lastX:0,lastY:0};

let geom={
    tee_points:HOLE_GEOMETRY.tee_points||{yellow:null,red:null},
    green_point:HOLE_GEOMETRY.green_point||null,
    centerline:Array.isArray(HOLE_GEOMETRY.centerline)?HOLE_GEOMETRY.centerline:[]
};

let geomLayers={
    teeYellow:null,
    teeRed:null,
    green:null,
    centerline:null,
    centerlinePoints:[],
    landingMarkers:[],
    landingLines:[],
    teeToGreen:null,
    holeLabel:null
};

let mapPanelVisible=false;
let mapPanelEnlarged=false;
let calib={active:false,stage:'idle',zoom:18,points:[],H:null};
let mapDraw={mode:null,drawing:false};
let calibMapMarkers=[];

// Init mappa - SOLO SATELLITARE
map=L.map('map',{zoomControl:true}).setView(CENTER,16);
L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:20}).addTo(map);

if(MAP_URL){
    document.getElementById('status').textContent='✅ Vista Satellitare Pulita';
    document.getElementById('open-map-panel-btn').disabled=false;
    document.getElementById('start-calib-btn').disabled=false;
    document.getElementById('reset-calib-btn').disabled=false;
}else{
    document.getElementById('status').textContent='Vista Satellitare - Nessuna mappetta caricata';
}

map.on('click',handleClick);
map.on('dblclick',function(e){
    if(setupMode&&drawingCenterline){
        toggleCenterlineDraw();
    }
});

document.getElementById('drive-yds').addEventListener('input',updateLandingUiState);
document.getElementById('second-yds').addEventListener('input',updateLandingUiState);
document.getElementById('third-yds').addEventListener('input',updateLandingUiState);

// Render geometria pre-esistente (se presente)
renderGeometry();
updateLandingUiState();
clearLandingLayers();

loadSavedDrives();
updateResults();

// Larghezza sempre disponibile (fuori dal Setup)
document.getElementById('width-btn').disabled=false;

initMapPanel();
loadCalibrationFromConfig();

// WORKFLOW DRIVE - STEP BY STEP
function placeTee(){
    if(setupMode||widthMode){
        alert('Esci da Setup/Larghezza per misurare il drive');
        return;
    }
    if(teeMode){
        // Annulla
        teeMode=false;
        document.getElementById('tee-btn').classList.remove('active');
        document.getElementById('tee-btn').textContent='📍 Posiziona TEE';
        document.getElementById('mode-indicator').classList.remove('active');
        document.getElementById('status').textContent='✅ Vista Satellitare Pulita';
        return;
    }

    teeMode=true;
    shotMode=false;
    widthMode=false;

    currentDrive={tee:null,shots:[],markers:[],lines:[]};

    document.getElementById('tee-btn').classList.add('active');
    document.getElementById('tee-btn').textContent='❌ Annulla TEE';
    document.getElementById('shot-btn').disabled=true;
    document.getElementById('complete-btn').disabled=true;

    const indicator=document.getElementById('mode-indicator');
    indicator.classList.add('active');
    indicator.innerHTML='<span class="icon">📍</span> Click sulla mappa per posizionare TEE';

    document.getElementById('status').textContent='🎯 Click per posizionare TEE';
}

function addShot(){
    if(!currentDrive||!currentDrive.tee){alert('Posiziona prima il TEE!');return;}

    shotMode=true;

    const indicator=document.getElementById('mode-indicator');
    indicator.classList.add('active');
    indicator.innerHTML='<span class="icon">➕</span> Click sulla mappa per aggiungere colpo';

    document.getElementById('shot-btn').classList.add('active');
    document.getElementById('status').textContent='➕ Click per aggiungere colpo';
}

function handleClick(e){
    if(calib.active&&calib.stage==='await_map'){
        handleCalibrationMapClick(e.latlng);
        return;
    }
    if(setupMode){
        // Durante setup, con mappetta disponibile:
        // - TEE/GREEN: si impostano da mappetta
        // - CENTERLINE: si disegna sul satellite (per seguire davvero la buca)
        if(MAP_URL){
            if(drawingCenterline){
                handleSetupClick(e.latlng);
            }else{
                document.getElementById('status').textContent='🗺️ Setup: imposta TEE/GREEN dalla mappetta. Usa "Disegna Centerline" per cliccare sul satellite.';
            }
            return;
        }
        handleSetupClick(e.latlng);
        return;
    }
    if(teeMode){
        handleTeeClick(e.latlng);
    }else if(shotMode){
        handleShotClick(e.latlng);
    }else if(widthMode){
        handleWidthClick(e.latlng);
    }
}

function toggleSetupMode(){
    setupMode=!setupMode;
    pointMode=null;
    drawingCenterline=false;

    if(setupMode){
        widthMode=false;
        tempWidth=[];
        document.getElementById('width-btn').classList.remove('active');
        document.getElementById('width-btn').textContent='📏 Misura Larghezza';
        document.getElementById('width-status').style.display='none';
    }

    document.getElementById('setup-btn').classList.toggle('active',setupMode);
    document.getElementById('setup-btn').textContent=setupMode?'❌ Chiudi Setup':'🧭 Avvia Setup';

    ['set-tee-yellow-btn','set-tee-red-btn','set-green-btn','draw-centerline-btn','save-geometry-btn','reset-geometry-btn','undo-centerline-btn','clear-centerline-btn'].forEach(id=>{
        document.getElementById(id).disabled=!setupMode;
    });

    if(setupMode){
        const indicator=document.getElementById('mode-indicator');
        indicator.classList.add('active');
        indicator.innerHTML='<span class="icon">🧭</span> SETUP BUCA: scegli un elemento e clicca sulla mappetta';
        document.getElementById('status').textContent='🧭 Setup buca: usa la mappetta';

        if(MAP_URL){
            mapPanelVisible=true;
            document.getElementById('map-panel').style.display='block';
            resizeRefCanvas();
            redrawRefCanvas();
        }
    }else{
        document.getElementById('mode-indicator').classList.remove('active');
        document.getElementById('status').textContent='✅ Vista Satellitare Pulita';
    }

    updateLandingUiState();
}

function toggleMapPanel(){
    if(!MAP_URL){
        alert('Nessuna mappetta disponibile');
        return;
    }
    mapPanelVisible=!mapPanelVisible;
    document.getElementById('map-panel').style.display=mapPanelVisible?'block':'none';
    if(mapPanelVisible){
        redrawRefCanvas();
    }
}

function toggleMapPanelSize(){
    const panel=document.getElementById('map-panel');
    mapPanelEnlarged=!mapPanelEnlarged;
    panel.classList.toggle('enlarged',mapPanelEnlarged);
    // Dopo il cambio dimensioni, riallinea canvas con nuovo rect
    setTimeout(function(){
        if(mapPanelVisible){
            resizeRefCanvas();
            redrawRefCanvas();
        }
    },0);
}

function initMapPanel(){
    const img=document.getElementById('ref-map-img');
    if(MAP_URL){
        img.src=MAP_URL;
    }
    img.onload=function(){
        resizeRefCanvas();
        redrawRefCanvas();
    };

    window.addEventListener('resize',function(){
        if(mapPanelVisible){
            resizeRefCanvas();
            redrawRefCanvas();
        }
    });

    // Quando il pannello viene ridimensionato (CSS resize), aggiorna il canvas
    const panel=document.getElementById('map-panel');
    if(window.ResizeObserver){
        const ro=new ResizeObserver(function(){
            if(mapPanelVisible){
                resizeRefCanvas();
                redrawRefCanvas();
            }
        });
        ro.observe(panel);
    }

    initRefViewportInteractions();
    panel.addEventListener('click',function(ev){
        // Evita che Leaflet riceva click provenienti dalla mappetta (importantissimo in calibrazione)
        ev.preventDefault();
        ev.stopPropagation();
    });

    const canvas=document.getElementById('ref-map-canvas');
    canvas.addEventListener('click',function(ev){
        // Evita che lo stesso click venga interpretato anche come click sulla mappa Leaflet
        ev.preventDefault();
        ev.stopPropagation();
        if(!mapPanelVisible) return;
        if(refView.moved){
            refView.moved=false;
            return;
        }
        const p=getRefPixelFromEvent(ev);
        if(calib.active&&calib.stage==='await_img'){
            handleCalibrationImgClick(p);
            return;
        }
        if(setupMode){
            handleMapDigitizeClick(p);
            return;
        }
    });
}

function resizeRefCanvas(){
    const canvas=document.getElementById('ref-map-canvas');
    const body=document.getElementById('map-panel-body');
    const rect=body.getBoundingClientRect();
    canvas.width=Math.round(rect.width);
    canvas.height=Math.round(rect.height);
}

function getRefPixelFromEvent(ev){
    const canvas=document.getElementById('ref-map-canvas');
    const img=document.getElementById('ref-map-img');
    const body=document.getElementById('map-panel-body');
    const rect=body.getBoundingClientRect();

    let x=ev.clientX-rect.left;
    let y=ev.clientY-rect.top;
    x=(x-refView.tx)/refView.scale;
    y=(y-refView.ty)/refView.scale;

    const iw=img.naturalWidth||1;
    const ih=img.naturalHeight||1;

    const cw=rect.width;
    const ch=rect.height;

    const scale=Math.min(cw/iw,ch/ih);
    const dw=iw*scale;
    const dh=ih*scale;
    const ox=(cw-dw)/2;
    const oy=(ch-dh)/2;

    const u=(x-ox)/scale;
    const v=(y-oy)/scale;
    return {u:u,v:v,valid:(u>=0&&v>=0&&u<=iw&&v<=ih)};
}

function applyRefViewportTransform(){
    const vp=document.getElementById('ref-map-viewport');
    vp.style.transform=`translate(${refView.tx}px,${refView.ty}px) scale(${refView.scale})`;
}

function initRefViewportInteractions(){
    const body=document.getElementById('map-panel-body');
    const vp=document.getElementById('ref-map-viewport');

    if(vp.dataset.inited==='1') return;
    vp.dataset.inited='1';

    function clamp(v,min,max){
        return Math.max(min,Math.min(max,v));
    }

    body.addEventListener('wheel',function(ev){
        if(!mapPanelVisible) return;
        ev.preventDefault();
        ev.stopPropagation();

        const rect=body.getBoundingClientRect();
        const cx=ev.clientX-rect.left;
        const cy=ev.clientY-rect.top;

        const wx=(cx-refView.tx)/refView.scale;
        const wy=(cy-refView.ty)/refView.scale;

        const dir=ev.deltaY>0 ? 0.9 : 1.1;
        const nextScale=clamp(refView.scale*dir,1,6);

        refView.scale=nextScale;
        refView.tx=cx-wx*refView.scale;
        refView.ty=cy-wy*refView.scale;

        applyRefViewportTransform();
    },{passive:false});

    body.addEventListener('mousedown',function(ev){
        if(!mapPanelVisible) return;
        if(ev.button!==0) return;
        ev.preventDefault();
        ev.stopPropagation();
        refView.dragging=true;
        refView.moved=false;
        refView.lastX=ev.clientX;
        refView.lastY=ev.clientY;
        vp.classList.add('dragging');
    });

    window.addEventListener('mousemove',function(ev){
        if(!refView.dragging) return;
        const dx=ev.clientX-refView.lastX;
        const dy=ev.clientY-refView.lastY;
        refView.lastX=ev.clientX;
        refView.lastY=ev.clientY;
        if(Math.abs(dx)+Math.abs(dy)>2){
            refView.moved=true;
        }
        refView.tx+=dx;
        refView.ty+=dy;
        applyRefViewportTransform();
    });

    window.addEventListener('mouseup',function(){
        if(!refView.dragging) return;
        refView.dragging=false;
        vp.classList.remove('dragging');
    });

    applyRefViewportTransform();
}

function redrawRefCanvas(){
    const canvas=document.getElementById('ref-map-canvas');
    const ctx=canvas.getContext('2d');
    ctx.clearRect(0,0,canvas.width,canvas.height);

    const img=document.getElementById('ref-map-img');
    const iw=img.naturalWidth||1;
    const ih=img.naturalHeight||1;
    const cw=canvas.width;
    const ch=canvas.height;
    const scale=Math.min(cw/iw,ch/ih);
    const dw=iw*scale;
    const dh=ih*scale;
    const ox=(cw-dw)/2;
    const oy=(ch-dh)/2;

    function toCanvas(u,v){
        return {x:ox+u*scale,y:oy+v*scale};
    }

    // calib points
    if(Array.isArray(calib.points)){
        calib.points.forEach((pt,i)=>{
            if(pt.img){
                const c=toCanvas(pt.img.u,pt.img.v);
                ctx.fillStyle='rgba(255,152,0,0.9)';
                ctx.strokeStyle='#fff';
                ctx.lineWidth=2;
                ctx.beginPath();
                ctx.arc(c.x,c.y,6,0,Math.PI*2);
                ctx.fill();
                ctx.stroke();
                ctx.fillStyle='#fff';
                ctx.font='12px Arial';
                ctx.fillText(String(i+1),c.x+9,c.y+4);
            }
        });
    }

    // digitized geometry preview on map image
    const imgPts=[];
    if(setupMode&&mapDraw.mode&&mapDraw.mode!=='centerline'){
        // no-op
    }
}

function startCalibration(){
    if(!MAP_URL){
        alert('Carica una mappetta per calibrare');
        return;
    }
    clearCalibrationMarkers();
    calib={active:true,stage:'await_img',zoom:Math.max(16,map.getZoom()),points:[],H:null};
    document.getElementById('save-calib-btn').disabled=true;

    // Forza apertura pannello mappetta (no toggle) per evitare che sparisca
    mapPanelVisible=true;
    document.getElementById('map-panel').style.display='block';
    document.getElementById('map-panel').style.pointerEvents='auto';
    resizeRefCanvas();
    redrawRefCanvas();

    setModeIndicator('🎯','CALIBRAZIONE: click punto 1 sulla mappetta');
    document.getElementById('status').textContent='🎯 Calibrazione: seleziona sulla mappetta il punto 1';
    redrawRefCanvas();
}

function resetCalibration(){
    clearCalibrationMarkers();
    calib={active:false,stage:'idle',zoom:18,points:[],H:null};
    if(SAVED_CONFIG&&SAVED_CONFIG.calibration){
        // keep saved calibration until overwritten
    }
    // Riabilita interazione con la mappetta
    document.getElementById('map-panel').style.pointerEvents='auto';
    setModeIndicator('🧭','');
    redrawRefCanvas();
}

function clearCalibrationMarkers(){
    calibMapMarkers.forEach(m=>map.removeLayer(m));
    calibMapMarkers=[];
}

function handleCalibrationImgClick(p){
    if(!p.valid) return;
    const idx=calib.points.length;
    if(idx>=4) return;
    calib.points.push({img:{u:p.u,v:p.v},map:null});
    calib.stage='await_map';
    setModeIndicator('🎯',`CALIBRAZIONE: ora click punto ${idx+1} sul satellite`);
    document.getElementById('status').textContent=`🎯 Calibrazione: seleziona sul satellite il punto ${idx+1}`;

    // Evita che il pannello mappetta intercetti i click sulla mappa
    document.getElementById('map-panel').style.pointerEvents='none';
    redrawRefCanvas();
}

function handleCalibrationMapClick(latlng){
    const idx=calib.points.length-1;
    if(idx<0||idx>=4) return;
    calib.points[idx].map={lat:latlng.lat,lng:latlng.lng};

    // Marker numerato sul satellite per feedback
    const m=L.marker(latlng,{icon:makeIcon('#FF9800',String(idx+1)),draggable:false}).addTo(map);
    calibMapMarkers.push(m);
    document.getElementById('status').textContent=`📍 Punto ${idx+1} registrato sul satellite`;

    if(calib.points.length<4){
        calib.stage='await_img';
        setModeIndicator('🎯',`CALIBRAZIONE: click punto ${calib.points.length+1} sulla mappetta`);
        document.getElementById('status').textContent=`🎯 Calibrazione: seleziona sulla mappetta il punto ${calib.points.length+1}`;

        // Riabilita click sulla mappetta
        document.getElementById('map-panel').style.pointerEvents='auto';
        redrawRefCanvas();
        return;
    }
    // compute H
    calib.H=computeHomography(calib.points,calib.zoom);
    calib.active=false;
    calib.stage='done';
    // Fine calibrazione: riabilita click sulla mappetta per digitizzazione
    document.getElementById('map-panel').style.pointerEvents='auto';
    document.getElementById('save-calib-btn').disabled=!calib.H;
    setModeIndicator('✅','Calibrazione completata: salva calibrazione campo');
    document.getElementById('status').textContent='✅ Calibrazione completata - premi "Salva Calibrazione Campo"';
    redrawRefCanvas();
}

async function saveCalibration(){
    if(!calib.H||!Array.isArray(calib.points)||calib.points.length!==4){
        alert('Calibrazione incompleta');
        return;
    }
    try{
        const r=await fetch(`{{ route('courses.save-overlay-config', $course) }}`,{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
            body:JSON.stringify({calibration:{zoom:calib.zoom,H:calib.H,points:calib.points}})
        });
        if(!r.ok){
            const t=await r.text();
            console.error('Save calibration failed',r.status,t);
            alert(`Errore salvataggio calibrazione (HTTP ${r.status})`);
            return;
        }
        const d=await r.json();
        if(d.success){
            document.getElementById('status').textContent='💾 Calibrazione campo salvata!';
            setModeIndicator('💾','Calibrazione salvata');
            // Se sei già in setup, ripristina l'indicazione di setup (evita messaggi "bloccati")
            if(typeof setupMode!=='undefined' && setupMode){
                setTimeout(function(){
                    setModeIndicator('🧭','SETUP BUCA: scegli un elemento e clicca sulla mappetta');
                },800);
            }else{
                setTimeout(function(){
                    setModeIndicator('🧭','');
                },1200);
            }
        }else{
            alert('Errore salvataggio calibrazione');
        }
    }catch(e){
        console.error(e);
        alert('Errore salvataggio calibrazione');
    }
}

function loadCalibrationFromConfig(){
    const c=SAVED_CONFIG?.calibration;
    if(c&&Array.isArray(c.H)&&c.H.length===9){
        calib.H=c.H;
        calib.zoom=c.zoom||18;
        document.getElementById('save-calib-btn').disabled=false;
    }
}

function computeHomography(points,zoom){
    const A=[];
    const b=[];

    for(let i=0;i<4;i++){
        const u=points[i].img.u;
        const v=points[i].img.v;
        const ll=L.latLng(points[i].map.lat,points[i].map.lng);
        const mp=map.project(ll,zoom);
        const x=mp.x;
        const y=mp.y;

        A.push([u,v,1,0,0,0,-u*x,-v*x]);
        b.push(x);
        A.push([0,0,0,u,v,1,-u*y,-v*y]);
        b.push(y);
    }

    const h=solveLinearSystem(A,b);
    if(!h) return null;
    return [h[0],h[1],h[2],h[3],h[4],h[5],h[6],h[7],1];
}

function solveLinearSystem(A,b){
    const n=b.length;
    // augmented
    const M=A.map((row,i)=>row.concat([b[i]]));
    const m=M[0].length;

    for(let col=0;col<n;col++){
        // pivot
        let pivot=col;
        for(let r=col+1;r<n;r++){
            if(Math.abs(M[r][col])>Math.abs(M[pivot][col])) pivot=r;
        }
        if(Math.abs(M[pivot][col])<1e-12) return null;
        const tmp=M[col];
        M[col]=M[pivot];
        M[pivot]=tmp;

        const div=M[col][col];
        for(let c=col;c<m;c++) M[col][c]/=div;

        for(let r=0;r<n;r++){
            if(r===col) continue;
            const factor=M[r][col];
            if(Math.abs(factor)<1e-12) continue;
            for(let c=col;c<m;c++){
                M[r][c]-=factor*M[col][c];
            }
        }
    }

    // last column is solution
    const x=new Array(n);
    for(let i=0;i<n;i++) x[i]=M[i][m-1];
    return x;
}

function applyHomography(H,u,v){
    const x=(H[0]*u+H[1]*v+H[2]);
    const y=(H[3]*u+H[4]*v+H[5]);
    const w=(H[6]*u+H[7]*v+1);
    return {x:x/w,y:y/w};
}

function handleMapDigitizeClick(p){
    if(!p.valid) return;
    if(!SAVED_CONFIG?.calibration?.H && !calib.H){
        alert('Calibra prima la mappetta (4 punti)');
        return;
    }
    const H=calib.H||SAVED_CONFIG.calibration.H;
    const z=calib.zoom||SAVED_CONFIG.calibration.zoom||18;
    const mp=applyHomography(H,p.u,p.v);
    if(!Number.isFinite(mp.x)||!Number.isFinite(mp.y)){
        alert('Errore calibrazione: coordinate proiettate non valide (ripeti calibrazione)');
        return;
    }
    const ll=map.unproject(L.point(mp.x,mp.y),z);
    if(!Number.isFinite(ll.lat)||!Number.isFinite(ll.lng)){
        alert('Errore calibrazione: lat/lng non validi (ripeti calibrazione)');
        return;
    }

    if(pointMode==='tee_yellow'){
        geom.tee_points=geom.tee_points||{yellow:null,red:null};
        geom.tee_points.yellow={lat:ll.lat,lng:ll.lng};
        renderGeometry();
        clearLandingLayers();
        document.getElementById('status').textContent='🟡 TEE giallo impostato da mappetta';
        setModeIndicator('🟡','TEE giallo impostato (da mappetta)');
        return;
    }
    if(pointMode==='tee_red'){
        geom.tee_points=geom.tee_points||{yellow:null,red:null};
        geom.tee_points.red={lat:ll.lat,lng:ll.lng};
        renderGeometry();
        clearLandingLayers();
        document.getElementById('status').textContent='🔴 TEE rosso impostato da mappetta';
        setModeIndicator('🔴','TEE rosso impostato (da mappetta)');
        return;
    }
    if(pointMode==='green'){
        geom.green_point={lat:ll.lat,lng:ll.lng};
        renderGeometry();
        clearLandingLayers();
        document.getElementById('status').textContent='🟢 GREEN impostato da mappetta';
        setModeIndicator('🟢','GREEN impostato (da mappetta)');
        return;
    }
    if(drawingCenterline){
        // Centerline la disegniamo sul satellite (non dalla mappetta) per evitare confusione.
        document.getElementById('status').textContent='📐 Centerline: clicca sul satellite (non sulla mappetta)';
        setModeIndicator('📐','CENTERLINE: clicca sul satellite');
    }
}

function setModeIndicator(icon,text){
    const indicator=document.getElementById('mode-indicator');
    if(!text){
        indicator.classList.remove('active');
        return;
    }
    indicator.classList.add('active');
    indicator.innerHTML=`<span class="icon">${icon}</span> ${text}`;
}

function setPointMode(mode){
    pointMode=mode;
    drawingCenterline=false;
    document.getElementById('draw-centerline-btn').classList.remove('active');
    document.getElementById('set-tee-yellow-btn').classList.toggle('active',mode==='tee_yellow');
    document.getElementById('set-tee-red-btn').classList.toggle('active',mode==='tee_red');
    document.getElementById('set-green-btn').classList.toggle('active',mode==='green');

    const label=mode==='tee_yellow'?'TEE Giallo':mode==='tee_red'?'TEE Rosso':'GREEN';
    const indicator=document.getElementById('mode-indicator');
    indicator.classList.add('active');
    indicator.innerHTML=`<span class="icon">�️</span> Click sulla mappetta per impostare ${label}`;
    document.getElementById('status').textContent=`�️ Clicca sulla mappetta: ${label}`;
}

function toggleCenterlineDraw(){
    drawingCenterline=!drawingCenterline;
    pointMode=null;
    if(drawingCenterline){
        geom.centerline=Array.isArray(geom.centerline)?geom.centerline:[];
    }
    document.getElementById('set-tee-yellow-btn').classList.remove('active');
    document.getElementById('set-tee-red-btn').classList.remove('active');
    document.getElementById('set-green-btn').classList.remove('active');
    document.getElementById('draw-centerline-btn').classList.toggle('active',drawingCenterline);

    const indicator=document.getElementById('mode-indicator');
    indicator.classList.add('active');
    if(drawingCenterline){
        map.doubleClickZoom.disable();
        indicator.innerHTML='<span class="icon">📐</span> Click multipli sul satellite per centerline. Doppio click per finire.';
        document.getElementById('status').textContent='📐 Centerline: clicca sul satellite';
    }else{
        map.doubleClickZoom.enable();
        indicator.innerHTML='<span class="icon">🧭</span> SETUP BUCA: scegli un elemento e clicca sulla mappetta';
        document.getElementById('status').textContent='🧭 Setup buca: usa la mappetta';
    }
}

function handleSetupClick(latlng){
    if(pointMode==='tee_yellow'){
        geom.tee_points=geom.tee_points||{yellow:null,red:null};
        geom.tee_points.yellow={lat:latlng.lat,lng:latlng.lng};
        renderGeometry();
        return;
    }
    if(pointMode==='tee_red'){
        geom.tee_points=geom.tee_points||{yellow:null,red:null};
        geom.tee_points.red={lat:latlng.lat,lng:latlng.lng};
        renderGeometry();
        return;
    }
    if(pointMode==='green'){
        geom.green_point={lat:latlng.lat,lng:latlng.lng};
        renderGeometry();
        return;
    }
    if(drawingCenterline){
        geom.centerline.push({lat:latlng.lat,lng:latlng.lng});
        renderGeometry();
        updateLandingUiState();
        clearLandingLayers();
        document.getElementById('status').textContent=`📐 Centerline: ${geom.centerline.length} punti (trascina i vertici per aggiustare)`;
        setModeIndicator('📐',`CENTERLINE: ${geom.centerline.length} punti`);
    }
}

function renderGeometry(){
    // Clear layers
    if(geomLayers.teeYellow) map.removeLayer(geomLayers.teeYellow);
    if(geomLayers.teeRed) map.removeLayer(geomLayers.teeRed);
    if(geomLayers.green) map.removeLayer(geomLayers.green);
    if(geomLayers.centerline) map.removeLayer(geomLayers.centerline);
    if(geomLayers.teeToGreen) map.removeLayer(geomLayers.teeToGreen);
    if(geomLayers.holeLabel) map.removeLayer(geomLayers.holeLabel);
    geomLayers.centerlinePoints.forEach(m=>map.removeLayer(m));
    geomLayers.centerlinePoints=[];

    if(geom.tee_points?.yellow){
        geomLayers.teeYellow=L.marker([geom.tee_points.yellow.lat,geom.tee_points.yellow.lng],{icon:makeIcon('#FFEB3B','Y'),draggable:true}).addTo(map);
        geomLayers.teeYellow.on('dragend',function(){
            const pt=this.getLatLng();
            geom.tee_points=geom.tee_points||{yellow:null,red:null};
            geom.tee_points.yellow={lat:pt.lat,lng:pt.lng};
            updateLandingUiState();
            refreshLandingZones();
        });
    }
    if(geom.tee_points?.red){
        geomLayers.teeRed=L.marker([geom.tee_points.red.lat,geom.tee_points.red.lng],{icon:makeIcon('#F44336','R'),draggable:true}).addTo(map);
        geomLayers.teeRed.on('dragend',function(){
            const pt=this.getLatLng();
            geom.tee_points=geom.tee_points||{yellow:null,red:null};
            geom.tee_points.red={lat:pt.lat,lng:pt.lng};
            updateLandingUiState();
            refreshLandingZones();
        });
    }
    if(geom.green_point){
        geomLayers.green=L.marker([geom.green_point.lat,geom.green_point.lng],{icon:makeIcon('#4CAF50','G'),draggable:true}).addTo(map);
        geomLayers.green.on('dragend',function(){
            const pt=this.getLatLng();
            geom.green_point={lat:pt.lat,lng:pt.lng};
            clearLandingLayers();
        });
    }
    if(Array.isArray(geom.centerline)&&geom.centerline.length>0){
        const latlngs=geom.centerline.map(p=>[p.lat,p.lng]);
        geomLayers.centerline=L.polyline(latlngs,{color:'#9C27B0',weight:4,opacity:0.9}).addTo(map);
        if(setupMode){
            geom.centerline.forEach((p,i)=>{
                const cm=L.marker([p.lat,p.lng],{icon:makeSmallVertexIcon('#9C27B0'),draggable:true}).addTo(map);
                cm.on('dragend',function(){
                    const pt=this.getLatLng();
                    geom.centerline[i]={lat:pt.lat,lng:pt.lng};
                    renderGeometry();
                    updateLandingUiState();
                    clearLandingLayers();
                });
                geomLayers.centerlinePoints.push(cm);
            });
        }
    }

    // Linea riferimento tee→green e label buca (solo come guida; se c'e' gia' una centerline, evitiamo confusione)
    const hasCenterline=Array.isArray(geom.centerline)&&geom.centerline.length>=2;
    if(!hasCenterline && geom.tee_points?.yellow && geom.green_point){
        const a=L.latLng(geom.tee_points.yellow.lat,geom.tee_points.yellow.lng);
        const b=L.latLng(geom.green_point.lat,geom.green_point.lng);
        geomLayers.teeToGreen=L.polyline([a,b],{color:'#00BCD4',weight:2,opacity:0.9,dashArray:'4,6'}).addTo(map);

        const mid=L.latLng((a.lat+b.lat)/2,(a.lng+b.lng)/2);
        geomLayers.holeLabel=L.marker(mid,{
            icon:L.divIcon({
                className:'hole-label',
                html:`<div style="background:rgba(0,0,0,0.75);border:2px solid #00BCD4;color:#fff;padding:2px 8px;border-radius:999px;font-weight:800;font-size:12px;">${HOLE_NUMBER}</div>`,
                iconSize:[30,18],
                iconAnchor:[15,9]
            })
        }).addTo(map);
    }

    updateLandingUiState();
}

function updateLandingUiState(){
    const teeColor=document.getElementById('tee-color')?.value||'yellow';
    const hasTee=!!(geom.tee_points&&geom.tee_points[teeColor]);
    const hasCenterline=Array.isArray(geom.centerline)&&geom.centerline.length>=2;
    document.getElementById('gen-landing-btn').disabled=!(hasTee&&hasCenterline);
    if(MAP_URL){
        document.getElementById('open-map-panel-btn').disabled=false;
        document.getElementById('start-calib-btn').disabled=false;
        document.getElementById('reset-calib-btn').disabled=false;
    }
}

function undoCenterlinePoint(){
    if(!Array.isArray(geom.centerline)||geom.centerline.length===0) return;
    geom.centerline.pop();
    renderGeometry();
    refreshLandingZones();
}

function clearCenterline(){
    geom.centerline=[];
    clearLandingLayers();
    renderGeometry();
    refreshLandingZones();
}

function resetGeometry(){
    if(!confirm('Resettare TEE/GREEN/CENTERLINE per questa buca?')) return;
    geom={tee_points:{yellow:null,red:null},green_point:null,centerline:[]};
    clearLandingLayers();
    renderGeometry();
    refreshLandingZones();
}

function clearLandingLayers(){
    geomLayers.landingMarkers.forEach(m=>map.removeLayer(m));
    geomLayers.landingMarkers=[];
    geomLayers.landingLines.forEach(l=>map.removeLayer(l));
    geomLayers.landingLines=[];
}

function refreshLandingZones(){
    clearLandingLayers();
    if(!geom.centerline||geom.centerline.length<2) return;

    const teeColor=document.getElementById('tee-color').value;
    const tee=geom.tee_points?.[teeColor];
    if(!tee) return;

    const driveYds=parseFloat(document.getElementById('drive-yds').value||0);
    const secondYds=parseFloat(document.getElementById('second-yds').value||0);
    const thirdYds=parseFloat(document.getElementById('third-yds').value||0);

    const teeLatLng=L.latLng(tee.lat,tee.lng);
    const centerlineLatLngs=geom.centerline.map(p=>L.latLng(p.lat,p.lng));

    // Anchor: ensure first point is tee-ish by prepending tee if far
    const first=centerlineLatLngs[0];
    if(map.distance(teeLatLng,first)>15){
        centerlineLatLngs.unshift(teeLatLng);
    }

    const shots=[driveYds,secondYds,thirdYds].filter(v=>v>0);
    let cumulative=0;
    let prevPoint=centerlineLatLngs[0];

    shots.forEach((yds,idx)=>{
        cumulative+=yds*YD;
        const res=pointAtDistance(centerlineLatLngs,cumulative);
        if(!res) return;
        const pt=res.point;
        const m=L.marker(pt,{icon:makeIcon('#FF9800',String(idx+1)),draggable:false}).addTo(map);
        geomLayers.landingMarkers.push(m);
        const line=L.polyline([prevPoint,pt],{color:'#FF9800',weight:3,opacity:0.8,dashArray:'6,6'}).addTo(map);
        geomLayers.landingLines.push(line);
        prevPoint=pt;
    });

    // Optional: line to green
    if(geom.green_point){
        const green=L.latLng(geom.green_point.lat,geom.green_point.lng);
        const line=L.polyline([prevPoint,green],{color:'#4CAF50',weight:3,opacity:0.8}).addTo(map);
        geomLayers.landingLines.push(line);
    }
}

function pointAtDistance(latlngs,meters){
    if(!latlngs||latlngs.length<2) return null;
    let acc=0;
    for(let i=0;i<latlngs.length-1;i++){
        const a=latlngs[i];
        const b=latlngs[i+1];
        const seg=map.distance(a,b);
        if(acc+seg>=meters){
            const t=(meters-acc)/seg;
            return {point:L.latLng(a.lat+(b.lat-a.lat)*t,a.lng+(b.lng-a.lng)*t),segmentIndex:i,t:t};
        }
        acc+=seg;
    }
    return {point:latlngs[latlngs.length-1],segmentIndex:latlngs.length-2,t:1};
}

async function saveGeometry(){
    const payload={
        tee_points:geom.tee_points||null,
        green_point:geom.green_point||null,
        centerline:Array.isArray(geom.centerline)&&geom.centerline.length>0?geom.centerline:null
    };
    try{
        const r=await fetch(`{{ route('holes.geometry.save', ['course' => $course, 'hole' => $hole->hole_number]) }}`,{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
            body:JSON.stringify(payload)
        });
        const d=await r.json();
        if(d.success){
            document.getElementById('status').textContent='💾 Geometria buca salvata!';
            refreshLandingZones();
        }else{
            alert('Errore salvataggio geometria');
        }
    }catch(e){
        console.error(e);
        alert('Errore salvataggio geometria');
    }
}

function handleTeeClick(latlng){
    currentDrive.tee=latlng;

    // Marker TEE
    const teeM=L.marker(latlng,{icon:makeIcon('#4CAF50','T'),draggable:false}).addTo(map);
    currentDrive.markers.push(teeM);

    // Primo colpo automatico
    const yds=parseInt(document.getElementById('yards').value);
    const pt=calcDest(latlng,yds*YD,0);
    createDraggableShot(pt,0);

    // Disattiva tee mode
    teeMode=false;
    document.getElementById('tee-btn').classList.remove('active');
    document.getElementById('tee-btn').textContent='📍 Posiziona TEE';
    document.getElementById('tee-btn').disabled=true;

    // Abilita shot e complete
    document.getElementById('shot-btn').disabled=false;
    document.getElementById('complete-btn').disabled=false;

    document.getElementById('mode-indicator').classList.remove('active');
    document.getElementById('status').textContent='✅ TEE posizionato - Trascina punto o aggiungi colpi';

    updateLines();
}

function handleShotClick(latlng){
    createDraggableShot(latlng,currentDrive.shots.length);

    shotMode=false;
    document.getElementById('shot-btn').classList.remove('active');
    document.getElementById('mode-indicator').classList.remove('active');
    document.getElementById('status').textContent='✅ Colpo aggiunto - Trascina o aggiungi altri';

    updateLines();
}

function createDraggableShot(pt,prevIdx){
    currentDrive.shots.push({point:pt});
    const idx=currentDrive.shots.length;
    const m=L.marker(pt,{icon:makeIcon('#FF5722',idx),draggable:true}).addTo(map);

    const prevPt=prevIdx===0?currentDrive.tee:currentDrive.markers[prevIdx].getLatLng();
    const initialDist=map.distance(prevPt,pt);

    m.on('dragstart',function(){
        draggedMarker={marker:m,index:idx-1,prevIndex:prevIdx};
        originalDistance=initialDist;
        shotIndex=idx;
        showDistanceTooltip(true);
    });

    m.on('drag',function(){
        updateDistanceTooltip(m);
        updateLines();
    });

    m.on('dragend',function(){
        draggedMarker=null;
        showDistanceTooltip(false);
        updateLines();
    });

    currentDrive.markers.push(m);
}

function showDistanceTooltip(show){
    const tooltip=document.getElementById('distance-tooltip');
    if(show){
        tooltip.classList.add('active');
    }else{
        tooltip.classList.remove('active');
    }
}

function updateDistanceTooltip(marker){
    if(!draggedMarker)return;

    const tooltip=document.getElementById('distance-tooltip');
    const markerPoint=map.latLngToContainerPoint(marker.getLatLng());

    const prevPt=draggedMarker.prevIndex===0?currentDrive.tee:currentDrive.markers[draggedMarker.prevIndex].getLatLng();
    const currentDist=map.distance(prevPt,marker.getLatLng());
    const currentYds=(currentDist/YD).toFixed(1);
    const originalYds=(originalDistance/YD).toFixed(1);
    const delta=currentDist-originalDistance;
    const deltaYds=(delta/YD).toFixed(1);

    const deltaClass=delta>=0?'delta':'delta negative';
    const deltaSign=delta>=0?'+':'';

    tooltip.style.left=(markerPoint.x+25)+'px';
    tooltip.style.top=(markerPoint.y-55)+'px';
    tooltip.innerHTML=`
        <div class="shot-label">Colpo ${shotIndex}</div>
        <div class="distance-line">${originalYds} → ${currentYds} yds</div>
        <div class="${deltaClass}">${deltaSign}${deltaYds} yds</div>
    `;
}

function updateLines(){
    if(!currentDrive)return;

    currentDrive.lines.forEach(l=>map.removeLayer(l));
    currentDrive.lines=[];

    let prev=currentDrive.tee,total=0;
    let html='<div class="measurement-item" style="color:#FF5722;font-weight:bold;">🎯 Drive in corso</div>';

    currentDrive.shots.forEach((s,i)=>{
        const pt=currentDrive.markers[i+1].getLatLng();
        const line=L.polyline([prev,pt],{color:'#FF5722',weight:3,opacity:0.8,dashArray:'10,5'}).addTo(map);
        currentDrive.lines.push(line);
        const dist=map.distance(prev,pt);
        const yds=(dist/YD).toFixed(1);
        html+=`<div class="measurement-item">
            <strong>Colpo ${i+1}:</strong> ${yds} yards
            <div class="shot-detail">${dist.toFixed(1)} metri</div>
        </div>`;
        total+=dist;
        prev=pt;
    });

    const totalYds=(total/YD).toFixed(1);
    html+=`<div class="total">TOTALE BUCA: ${totalYds} yards<div style="font-size:13px;color:#888;margin-top:4px;">${total.toFixed(1)} metri</div></div>`;
    document.getElementById('measurements').innerHTML=html;
}

function loadSavedDrives(){
    clearDriveLayers();
    if(!Array.isArray(SAVED_DRIVES)||SAVED_DRIVES.length===0){
        return;
    }
    const sorted=SAVED_DRIVES.slice().sort((a,b)=>{
        const ai=parseInt(a.id||0,10);
        const bi=parseInt(b.id||0,10);
        if(Number.isFinite(ai)&&Number.isFinite(bi)&&ai!==bi) return ai-bi;
        const at=new Date(a.created_at||0).getTime();
        const bt=new Date(b.created_at||0).getTime();
        return at-bt;
    });
    const d=sorted[sorted.length-1];
    const tee=L.latLng(parseFloat(d.tee_lat),parseFloat(d.tee_lng));
    const shots=Array.isArray(d.shots)?d.shots:[];
    const points=[tee].concat(shots.map(s=>L.latLng(parseFloat(s.lat),parseFloat(s.lng))));

    const line=L.polyline(points,{color:'#FF9800',weight:3,opacity:0.65,dashArray:'8,8'}).addTo(map);
    const teeM=L.circleMarker(tee,{radius:6,fillColor:'#FF9800',color:'#fff',weight:2,fillOpacity:0.9}).addTo(map);
    driveLayers.push(line,teeM);

    drives=[{tee:tee,markers:[teeM],lines:[line],shots:shots}];

    driveComplete=true;
}

function clearDriveLayers(){
    driveLayers.forEach(l=>{try{map.removeLayer(l);}catch(e){}});
    driveLayers=[];
}

async function completeDrive(){
    if(!currentDrive||!currentDrive.tee||currentDrive.shots.length===0){
        alert('Nessun drive da completare!');
        return;
    }

    driveComplete=true;
    currentDrive.markers.forEach(m=>{if(m.dragging)m.dragging.disable()});

    let prevPt=currentDrive.tee,totalM=0;
    const shotsData=currentDrive.markers.slice(1).map((m,i)=>{
        const pt=m.getLatLng(),dist=map.distance(prevPt,pt);
        totalM+=dist;prevPt=pt;
        return{lat:pt.lat,lng:pt.lng,distance_meters:dist,distance_yards:dist/YD};
    });

    try{
        const r=await fetch('/drives',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
            body:JSON.stringify({hole_id:HOLE.id,tee_lat:currentDrive.tee.lat,tee_lng:currentDrive.tee.lng,shots:shotsData})
        });
        const d=await r.json();
        if(d.success){
            // Politica "drive unico" anche in UI: rimuovi eventuale drive precedente dalla mappa e sostituisci
            drives.forEach(old=>{old.markers.forEach(m=>{try{map.removeLayer(m);}catch(e){}});old.lines.forEach(l=>{try{map.removeLayer(l);}catch(e){}});});
            drives=[currentDrive];
            currentDrive=null;

            document.getElementById('tee-btn').disabled=false;
            document.getElementById('shot-btn').disabled=true;
            document.getElementById('complete-btn').disabled=true;
            document.getElementById('width-btn').disabled=false;

            document.getElementById('status').textContent='✅ Drive salvato nel database!';
            updateResults();
        }
    }catch(e){
        console.error(e);
        alert('Errore salvataggio drive');
    }
}

// LARGHEZZA - MODALITÀ SEPARATA
function startWidth(){
    if(setupMode){
        alert('Esci dal Setup buca per misurare la larghezza');
        return;
    }

    if(widthMode){
        // Disattiva
        widthMode=false;
        tempWidth=[];
        document.getElementById('width-btn').classList.remove('active');
        document.getElementById('width-btn').textContent='📏 Misura Larghezza';
        document.getElementById('width-status').style.display='none';
        document.getElementById('mode-indicator').classList.remove('active');
        document.getElementById('status').textContent='✅ Misurazione larghezza annullata';
        return;
    }

    widthMode=true;
    tempWidth=[];

    document.getElementById('width-btn').classList.add('active');
    document.getElementById('width-btn').textContent='❌ Annulla Larghezza';
    document.getElementById('width-status').style.display='block';
    document.getElementById('width-status').textContent='Click 1/2: Primo punto fairway';

    const indicator=document.getElementById('mode-indicator');
    indicator.classList.add('active');
    indicator.innerHTML='<span class="icon">📏</span> MODALITÀ LARGHEZZA - Click 2 punti bordo fairway';

    document.getElementById('status').textContent='📏 Click primo punto bordo fairway';
}

function handleWidthClick(latlng){
    tempWidth.push(latlng);
    const m=L.circleMarker(latlng,{radius:6,fillColor:'#2196F3',color:'#FFF',weight:2,fillOpacity:0.8}).addTo(map);

    if(tempWidth.length===1){
        tempWidth.push(m);
        document.getElementById('width-status').textContent='Click 2/2: Secondo punto fairway';
        document.getElementById('status').textContent='📏 Click secondo punto bordo fairway';
    }else if(tempWidth.length===3){
        const line=L.polyline([tempWidth[0],tempWidth[2]],{color:'#2196F3',weight:3}).addTo(map);
        const distM=map.distance(tempWidth[0],tempWidth[2]),distYd=distM/YD;
        widths.push({markers:[tempWidth[1],m],line:line,distance:distYd});

        widthMode=false;
        tempWidth=[];
        document.getElementById('width-btn').classList.remove('active');
        document.getElementById('width-btn').textContent='📏 Misura Larghezza';
        document.getElementById('width-status').style.display='none';
        document.getElementById('mode-indicator').classList.remove('active');
        document.getElementById('status').textContent=`✅ Larghezza: ${distYd.toFixed(1)} yards (${distM.toFixed(1)}m)`;

        updateResults();
    }
}

function updateResults(){
    let html='';
    if(drives.length>0){
        html+='<div class="measurement-item" style="font-weight:bold;color:#4CAF50;font-size:13px;">🎯 DRIVE COMPLETATI</div>';
        drives.forEach((d,i)=>{
            let tot=0,prev=d.tee,shotsDetail='';
            d.markers.slice(1).forEach((m,idx)=>{
                const pt=m.getLatLng(),dist=map.distance(prev,pt),yds=(dist/YD).toFixed(1);
                shotsDetail+=`<div class="shot-detail">└ Colpo ${idx+1}: ${yds} yds (${dist.toFixed(1)}m)</div>`;
                tot+=dist;prev=pt;
            });
            const totalYds=(tot/YD).toFixed(1);
            html+=`<div class="measurement-item"><strong>Drive ${i+1}:</strong> ${d.shots.length} colpi${shotsDetail}</div>`;
            html+=`<div class="total">TOTALE BUCA: ${totalYds} yards<div style="font-size:13px;color:#888;margin-top:4px;">${tot.toFixed(1)} metri</div></div>`;
        });
    }
    if(widths.length>0){
        html+='<div class="measurement-item" style="font-weight:bold;color:#2196F3;margin-top:15px;font-size:13px;">📏 LARGHEZZE FAIRWAY</div>';
        widths.forEach((w,i)=>{
            const meters=(w.distance*YD).toFixed(1);
            html+=`<div class="measurement-item">Larghezza ${i+1}: ${w.distance.toFixed(1)} yards<div class="shot-detail">(${meters} metri)</div></div>`;
        });
    }
    if(!html)html='<div class="measurement-item" style="color:#888;">Nessuna misurazione</div>';
    document.getElementById('measurements').innerHTML=html;
}

function undoLast(){
    if(currentDrive&&currentDrive.shots.length>0){
        const m=currentDrive.markers.pop();
        map.removeLayer(m);
        currentDrive.shots.pop();
        if(currentDrive.shots.length>0){
            updateLines();
        }else{
            document.getElementById('measurements').innerHTML='<div class="measurement-item" style="color:#888;">TEE posizionato - Aggiungi colpi</div>';
        }
        document.getElementById('status').textContent='↩️ Ultimo punto rimosso';
    }else if(widthMode&&tempWidth.length>0){
        if(tempWidth.length===3){
            map.removeLayer(tempWidth[2]);
            tempWidth.pop();
            document.getElementById('width-status').textContent='Click 1/2: Primo punto fairway';
        }else if(tempWidth.length===2){
            map.removeLayer(tempWidth[1]);
            tempWidth=[];
            document.getElementById('width-status').textContent='Click 1/2: Primo punto fairway';
        }
        document.getElementById('status').textContent='↩️ Punto larghezza rimosso';
    }else if(widths.length>0){
        const w=widths.pop();
        w.markers.forEach(m=>map.removeLayer(m));
        map.removeLayer(w.line);
        updateResults();
        document.getElementById('status').textContent='↩️ Ultima larghezza rimossa';
    }else if(drives.length>0){
        const d=drives.pop();
        d.markers.forEach(m=>map.removeLayer(m));
        d.lines.forEach(l=>map.removeLayer(l));
        driveComplete=drives.length>0;
        document.getElementById('width-btn').disabled=!driveComplete;
        updateResults();
        document.getElementById('status').textContent='↩️ Ultimo drive rimosso';
    }
}

function clearAll(){
    if(!confirm('Cancellare tutte le misurazioni?'))return;

    if(currentDrive){
        currentDrive.markers.forEach(m=>map.removeLayer(m));
        currentDrive.lines.forEach(l=>map.removeLayer(l));
        currentDrive=null;
    }
    drives.forEach(d=>{d.markers.forEach(m=>map.removeLayer(m));d.lines.forEach(l=>map.removeLayer(l));});
    drives=[];
    widths.forEach(w=>{w.markers.forEach(m=>map.removeLayer(m));map.removeLayer(w.line);});
    widths=[];
    if(tempWidth.length>1)map.removeLayer(tempWidth[1]);
    if(tempWidth.length===3)map.removeLayer(tempWidth[2]);
    tempWidth=[];

    teeMode=false;shotMode=false;widthMode=false;driveComplete=false;

    document.getElementById('tee-btn').classList.remove('active');
    document.getElementById('tee-btn').textContent='📍 Posiziona TEE';
    document.getElementById('tee-btn').disabled=false;
    document.getElementById('shot-btn').classList.remove('active');
    document.getElementById('shot-btn').disabled=true;
    document.getElementById('complete-btn').disabled=true;
    document.getElementById('width-btn').classList.remove('active');
    document.getElementById('width-btn').textContent='📏 Misura Larghezza';
    document.getElementById('width-btn').disabled=true;
    document.getElementById('width-status').style.display='none';
    document.getElementById('mode-indicator').classList.remove('active');

    document.getElementById('measurements').innerHTML='<div class="measurement-item" style="color:#888;">Tutto cancellato - Inizia nuovo drive</div>';
    document.getElementById('status').textContent='🗑️ Tutte le misurazioni cancellate';
}

function calcDest(start,dist,bear){
    const R=6371e3,lat1=start.lat*Math.PI/180,lng1=start.lng*Math.PI/180,brng=bear*Math.PI/180;
    const lat2=Math.asin(Math.sin(lat1)*Math.cos(dist/R)+Math.cos(lat1)*Math.sin(dist/R)*Math.cos(brng));
    const lng2=lng1+Math.atan2(Math.sin(brng)*Math.sin(dist/R)*Math.cos(lat1),Math.cos(dist/R)-Math.sin(lat1)*Math.sin(lat2));
    return L.latLng(lat2*180/Math.PI,lng2*180/Math.PI);
}

function makeIcon(color,label){
    return L.divIcon({
        className:'custom-marker',
        html:`<div style="background:${color};width:26px;height:26px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:12px;box-shadow:0 2px 8px rgba(0,0,0,0.4);">${label}</div>`,
        iconSize:[26,26],
        iconAnchor:[13,13]
    });
}

function makeSmallVertexIcon(color){
    return L.divIcon({
        className:'vertex-marker',
        html:`<div style="background:${color};width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.35);"></div>`,
        iconSize:[12,12],
        iconAnchor:[6,6]
    });
}
    </script>
</body>
</html>
