# Piano di Migrazione: golf-measurement → golf-rating-system

## Analisi Fattibilità

### ✅ FATTIBILE - Complessità Media

I due progetti sono **compatibili** e l'integrazione è fattibile perché:

1. **Stesso stack tecnologico**: Entrambi Laravel + Blade + Tailwind
2. **Strutture dati complementari**: 
   - `golf-rating-system`: Ha `Course` con `Hole1-18` come JSON (dati rating)
   - `golf-measurement`: Ha `GolfCourse` + `Hole` separati (coordinate, misurazioni)
3. **Obiettivi sinergici**: Le misurazioni popolano i dati necessari per il rating

---

## Mappatura Dati

### golf-measurement → golf-rating-system

| golf-measurement | golf-rating-system | Note |
|------------------|-------------------|------|
| `GolfCourse.name` | `Course.Circolo` | Diretto |
| `Hole.hole_number` | `Course.HoleN` (JSON) | N = 1-18 |
| `Hole.par` | `HoleN.par` | Diretto |
| `Hole.length_yards` | `HoleN.length` | Diretto |
| `Hole.tee_points` | `HoleN.tee_coords` | Nuovo campo |
| `Hole.green_point` | `HoleN.green_coords` | Nuovo campo |
| `Hole.centerline` | `HoleN.centerline` | Nuovo campo |
| `Drive.shots` | Calcolo → `HoleN.dogleg`, `HoleN.fairway_width` | Elaborazione |

### Campi da aggiungere a golf-rating-system.Course.HoleN:

```json
{
  "par": 4,
  "length": 380,
  "tee_coords": {"lat": 41.73, "lng": 12.62},
  "green_coords": {"lat": 41.74, "lng": 12.63},
  "centerline": [{"lat": ..., "lng": ...}, ...],
  "fairway_width": 35,
  "dogleg": "left",
  "bunkers": 3,
  "lateral_distance": 25
}
```

---

## Piano di Migrazione (5 Fasi)

### Fase 1: Preparazione Database (1-2 ore)
1. Aggiungere campi coordinate a `Course.HoleN` JSON in golf-rating-system
2. Creare migration per nuovi campi se necessario
3. Aggiungere `latitude`, `longitude` a tabella `courses`

### Fase 2: Copia Componenti Misurazione (2-3 ore)
Copiare da golf-measurement a golf-rating-system:
- `app/Services/OverpassService.php`
- `app/Http/Controllers/MeasurementController.php`
- `app/Http/Controllers/HoleDataController.php`
- `resources/views/measurements/measure-simple.blade.php`
- `resources/views/courses/map-holes.blade.php`
- `public/js/` (se presenti)

### Fase 3: Adattamento Models (1-2 ore)
1. Modificare `MeasurementController` per usare `Course` invece di `GolfCourse`
2. Adattare la struttura `HoleN` JSON invece di model `Hole` separato
3. Creare helper per accesso holes: `$course->getHole(5)`

### Fase 4: Integrazione Routes (30 min)
Aggiungere a `routes/web.php` di golf-rating-system:
```php
// Measurement Routes
Route::get('courses/{course}/map-holes', [HoleDataController::class, 'mapHolesView']);
Route::get('courses/{course}/holes/{hole}/measure', [MeasurementController::class, 'measureHole']);
// ... altre routes
```

### Fase 5: UI Integration (1 ora)
1. Aggiungere link "Misura" nella vista corso esistente
2. Integrare stili CSS
3. Test end-to-end

---

## Stima Tempo Totale

| Fase | Tempo |
|------|-------|
| Fase 1 | 1-2 ore |
| Fase 2 | 2-3 ore |
| Fase 3 | 1-2 ore |
| Fase 4 | 30 min |
| Fase 5 | 1 ora |
| **Totale** | **6-9 ore** |

---

## Alternativa: API Integration

Se preferisci mantenere i progetti separati:

1. **golf-measurement** espone API REST
2. **golf-rating-system** consuma API per importare dati
3. Sincronizzazione manuale o automatica via webhook

**Pro**: Progetti indipendenti, meno rischi
**Contro**: Più complessità, doppia manutenzione

---

## Raccomandazione

**Consiglio l'integrazione diretta** (merge) perché:
- Elimina duplicazione codice
- Un solo database da gestire
- UX unificata per l'utente
- Manutenzione semplificata

---

## Prossimi Passi

1. ✅ Analisi completata
2. ⏳ Decidere: merge o API?
3. ⏳ Backup entrambi i progetti
4. ⏳ Iniziare Fase 1
