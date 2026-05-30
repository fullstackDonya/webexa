# API de Synchronisation ERP ↔ CRM

## 📡 Vue d'ensemble

L'API de synchronisation permet une communication bidirectionnelle entre l'ERP et le CRM pour maintenir les données cohérentes entre les deux systèmes.

**Endpoint principal :** `/erp/api/sync.php`

---

## 🔑 Authentification

L'API utilise les sessions PHP pour l'authentification. L'utilisateur doit être connecté avec un `customer_id` valide dans la session.

```php
$customer_id = $_SESSION['customer_id'] ?? 0;
```

---

## 📊 Endpoints Disponibles

### 1. Synchroniser les Missions (CRM → ERP)

**URL :** `GET /erp/api/sync.php?action=sync_missions`

**Description :** Récupère toutes les missions du CRM pour le client connecté

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "departure": "Paris",
      "arrival": "Lyon",
      "datetime": "2026-02-20 14:30:00",
      "driver": "Jean Dupont",
      "vehicle": "Renault Trafic",
      "status_id": 2,
      "notes": "Mission urgente",
      "folder_id": 45,
      "created_at": "2026-02-15 10:00:00",
      "updated_at": "2026-02-15 10:00:00"
    }
  ],
  "count": 1
}
```

**Utilisation :**
```javascript
fetch('/erp/api/sync.php?action=sync_missions')
  .then(res => res.json())
  .then(data => {
    console.log(`${data.count} missions synchronisées`);
    data.data.forEach(mission => {
      // Traiter chaque mission
    });
  });
```

---

### 2. Synchroniser les Entreprises

**URL :** `GET /erp/api/sync.php?action=sync_companies`

**Description :** Récupère toutes les entreprises clientes

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "name": "Acme Corporation",
      "email": "contact@acme.com",
      "phone": "+33 1 23 45 67 89",
      "address": "123 rue de la Paix, 75001 Paris",
      "created_at": "2025-01-15 09:00:00",
      "updated_at": "2026-02-10 14:30:00"
    }
  ],
  "count": 1
}
```

---

### 3. Synchroniser les Shifts (ERP → CRM)

**URL :** `GET /erp/api/sync.php?action=sync_shifts`

**Description :** Récupère les shifts ERP des 90 derniers jours

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "employee_id": 12,
      "company_id": 10,
      "start_time": "2026-02-20 09:00:00",
      "end_time": "2026-02-20 17:00:00",
      "notes": "Réception client",
      "employee_name": "Marie Martin",
      "company_name": "Acme Corporation"
    }
  ],
  "count": 1
}
```

---

### 4. Synchroniser les Ventes

**URL :** `GET /erp/api/sync.php?action=sync_sales`

**Description :** Récupère les ventes des 180 derniers jours

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 789,
      "sale_date": "2026-02-18",
      "customer_name": "Acme Corporation",
      "product_name": "Prestation consulting",
      "quantity": 5,
      "unit_price": 500.00,
      "total_price": 2500.00,
      "notes": "Projet XYZ",
      "created_at": "2026-02-18 16:00:00",
      "updated_at": "2026-02-18 16:00:00"
    }
  ],
  "count": 1
}
```

---

### 5. Obtenir les Statistiques Globales

**URL :** `GET /erp/api/sync.php?action=get_stats`

**Description :** Récupère les statistiques consolidées ERP + CRM

**Réponse :**
```json
{
  "success": true,
  "data": {
    "missions": {
      "total": 150,
      "completed": 120,
      "active": 30
    },
    "employees": {
      "total": 25,
      "active": 22,
      "avg_salary": 2500.50
    },
    "companies": {
      "total": 45
    },
    "shifts": {
      "total": 340,
      "unique_employees": 20,
      "unique_companies": 38
    }
  }
}
```

**Utilisation :**
```javascript
async function loadDashboardStats() {
  const response = await fetch('/erp/api/sync.php?action=get_stats');
  const data = await response.json();
  
  if (data.success) {
    // Mettre à jour le dashboard
    document.getElementById('total-missions').textContent = data.data.missions.total;
    document.getElementById('total-employees').textContent = data.data.employees.total;
    // ...
  }
}
```

---

### 6. Créer un Shift depuis une Mission

**URL :** `POST /erp/api/sync.php?action=create_shift_from_mission`

**Description :** Crée automatiquement un shift ERP à partir d'une mission CRM

**Body (JSON) :**
```json
{
  "mission_id": 123,
  "employee_id": 45
}
```

**Réponse succès :**
```json
{
  "success": true,
  "shift_id": 567,
  "message": "Shift créé avec succès depuis la mission"
}
```

**Réponse erreur :**
```json
{
  "success": false,
  "message": "Mission ID et Employee ID requis"
}
```

**Utilisation :**
```javascript
async function createShiftFromMission(missionId, employeeId) {
  const response = await fetch('/erp/api/sync.php?action=create_shift_from_mission', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      mission_id: missionId,
      employee_id: employeeId
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    console.log(`Shift #${data.shift_id} créé !`);
  } else {
    console.error(data.message);
  }
}

// Exemple d'utilisation
createShiftFromMission(123, 45);
```

---

## 🔄 Flux de Synchronisation Automatique

### Exemple : Synchronisation toutes les 5 minutes

```javascript
// Dans missions.php ou shifts.php
class SyncManager {
  constructor() {
    this.syncInterval = 5 * 60 * 1000; // 5 minutes
    this.lastSync = null;
  }
  
  async syncAll() {
    try {
      // Sync missions
      const missions = await this.syncMissions();
      console.log(`✓ ${missions.count} missions synchronisées`);
      
      // Sync shifts
      const shifts = await this.syncShifts();
      console.log(`✓ ${shifts.count} shifts synchronisées`);
      
      // Sync stats
      const stats = await this.getStats();
      console.log('✓ Statistiques mises à jour');
      
      this.lastSync = new Date();
      this.updateUI(missions, shifts, stats);
      
    } catch (error) {
      console.error('Erreur de synchronisation:', error);
    }
  }
  
  async syncMissions() {
    const res = await fetch('/erp/api/sync.php?action=sync_missions');
    return await res.json();
  }
  
  async syncShifts() {
    const res = await fetch('/erp/api/sync.php?action=sync_shifts');
    return await res.json();
  }
  
  async getStats() {
    const res = await fetch('/erp/api/sync.php?action=get_stats');
    return await res.json();
  }
  
  startAutoSync() {
    // Sync initial
    this.syncAll();
    
    // Sync périodique
    setInterval(() => {
      this.syncAll();
    }, this.syncInterval);
  }
  
  updateUI(missions, shifts, stats) {
    // Mettre à jour l'interface avec les nouvelles données
    // ...
  }
}

// Démarrer la synchronisation
const syncManager = new SyncManager();
syncManager.startAutoSync();
```

---

## ⚡ WebSocket (Future Implementation)

Pour une synchronisation en temps réel, implémentation future avec WebSocket :

```javascript
// Future: WebSocket pour sync temps réel
const ws = new WebSocket('ws://localhost:8080/erp-sync');

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  
  switch(data.type) {
    case 'mission_created':
      addMissionToUI(data.mission);
      break;
    case 'shift_updated':
      updateShiftInUI(data.shift);
      break;
    case 'stats_changed':
      updateStatsUI(data.stats);
      break;
  }
};

ws.send(JSON.stringify({
  action: 'subscribe',
  customer_id: 123
}));
```

---

## 🛡️ Sécurité

### Headers Recommandés

```php
// Dans sync.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS (si nécessaire)
header('Access-Control-Allow-Origin: https://votre-domaine.com');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
```

### Validation des Données

```php
// Validation mission_id
$mission_id = filter_var($_POST['mission_id'], FILTER_VALIDATE_INT);
if (!$mission_id) {
    throw new Exception('Mission ID invalide');
}

// Validation employee_id
$employee_id = filter_var($_POST['employee_id'], FILTER_VALIDATE_INT);
if (!$employee_id) {
    throw new Exception('Employee ID invalide');
}
```

### Rate Limiting (Recommandé)

```php
// Limiter les requêtes par utilisateur
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$key = "rate_limit:customer_{$customer_id}";
$current = $redis->incr($key);

if ($current === 1) {
    $redis->expire($key, 60); // 1 minute
}

if ($current > 60) { // Max 60 requêtes/minute
    http_response_code(429);
    die(json_encode(['success' => false, 'message' => 'Too many requests']));
}
```

---

## 📈 Monitoring & Logs

### Logging des Requêtes

```php
// Logger toutes les requêtes API
function logAPIRequest($action, $customer_id, $success, $data = null) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'customer_id' => $customer_id,
        'success' => $success,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'data' => $data
    ];
    
    file_put_contents(
        __DIR__ . '/../logs/api_' . date('Y-m-d') . '.log',
        json_encode($log) . "\n",
        FILE_APPEND
    );
}

// Utilisation
logAPIRequest($action, $customer_id, $response['success'], $response);
```

---

## 🧪 Tests

### Test avec cURL

```bash
# Test sync missions
curl -X GET "http://localhost/erp/api/sync.php?action=sync_missions" \
  -H "Cookie: PHPSESSID=votre_session_id"

# Test création shift
curl -X POST "http://localhost/erp/api/sync.php?action=create_shift_from_mission" \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=votre_session_id" \
  -d '{"mission_id":123,"employee_id":45}'
```

### Test avec JavaScript (Console)

```javascript
// Dans la console du navigateur

// Test sync missions
fetch('/erp/api/sync.php?action=sync_missions')
  .then(res => res.json())
  .then(console.log);

// Test stats
fetch('/erp/api/sync.php?action=get_stats')
  .then(res => res.json())
  .then(console.log);

// Test création shift
fetch('/erp/api/sync.php?action=create_shift_from_mission', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({mission_id: 123, employee_id: 45})
})
  .then(res => res.json())
  .then(console.log);
```

---

## 📞 Support

- **Email :** api@webitech.com
- **Documentation :** `/erp/README.md`
- **Changelog :** Voir fichier principal README

---

**API Version:** 1.0.0  
**Dernière mise à jour:** Février 2026
