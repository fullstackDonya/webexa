/**
 * ERP/CRM Synchronization Manager
 * Version: 1.0.0
 * Date: Février 2026
 * 
 * Gère la synchronisation en temps réel entre l'ERP et le CRM
 */

class ERPSyncManager {
    constructor(options = {}) {
        this.apiEndpoint = options.apiEndpoint || '/erp/api/sync.php';
        this.syncInterval = options.syncInterval || 5 * 60 * 1000; // 5 minutes par défaut
        this.autoSync = options.autoSync !== false; // true par défaut
        this.debug = options.debug || false;
        
        this.lastSync = {
            missions: null,
            shifts: null,
            companies: null,
            sales: null,
            stats: null
        };
        
        this.listeners = {
            onSyncStart: [],
            onSyncComplete: [],
            onSyncError: [],
            onDataUpdate: []
        };
        
        this.syncTimer = null;
        
        if (this.autoSync) {
            this.startAutoSync();
        }
        
        this.log('ERPSyncManager initialized', options);
    }
    
    /**
     * Démarrer la synchronisation automatique
     */
    startAutoSync() {
        this.log('Starting auto-sync...');
        
        // Sync initial
        this.syncAll();
        
        // Sync périodique
        this.syncTimer = setInterval(() => {
            this.syncAll();
        }, this.syncInterval);
    }
    
    /**
     * Arrêter la synchronisation automatique
     */
    stopAutoSync() {
        this.log('Stopping auto-sync...');
        if (this.syncTimer) {
            clearInterval(this.syncTimer);
            this.syncTimer = null;
        }
    }
    
    /**
     * Synchroniser toutes les données
     */
    async syncAll() {
        this.log('Starting full synchronization...');
        this.trigger('onSyncStart', { type: 'full' });
        
        const startTime = Date.now();
        const results = {};
        
        try {
            // Sync en parallèle pour plus de rapidité
            const [missions, shifts, companies, sales, stats] = await Promise.all([
                this.syncMissions(),
                this.syncShifts(),
                this.syncCompanies(),
                this.syncSales(),
                this.getStats()
            ]);
            
            results.missions = missions;
            results.shifts = shifts;
            results.companies = companies;
            results.sales = sales;
            results.stats = stats;
            
            const executionTime = Date.now() - startTime;
            
            this.log(`Full sync completed in ${executionTime}ms`, results);
            this.trigger('onSyncComplete', { type: 'full', results, executionTime });
            
            return results;
            
        } catch (error) {
            this.log('Sync error:', error, 'error');
            this.trigger('onSyncError', { type: 'full', error });
            throw error;
        }
    }
    
    /**
     * Synchroniser les missions (CRM → ERP)
     */
    async syncMissions() {
        const response = await this.apiCall('sync_missions');
        if (response.success) {
            this.lastSync.missions = new Date();
            this.trigger('onDataUpdate', { type: 'missions', data: response.data });
        }
        return response;
    }
    
    /**
     * Synchroniser les shifts (ERP)
     */
    async syncShifts() {
        const response = await this.apiCall('sync_shifts');
        if (response.success) {
            this.lastSync.shifts = new Date();
            this.trigger('onDataUpdate', { type: 'shifts', data: response.data });
        }
        return response;
    }
    
    /**
     * Synchroniser les entreprises
     */
    async syncCompanies() {
        const response = await this.apiCall('sync_companies');
        if (response.success) {
            this.lastSync.companies = new Date();
            this.trigger('onDataUpdate', { type: 'companies', data: response.data });
        }
        return response;
    }
    
    /**
     * Synchroniser les ventes
     */
    async syncSales() {
        const response = await this.apiCall('sync_sales');
        if (response.success) {
            this.lastSync.sales = new Date();
            this.trigger('onDataUpdate', { type: 'sales', data: response.data });
        }
        return response;
    }
    
    /**
     * Obtenir les statistiques
     */
    async getStats() {
        const response = await this.apiCall('get_stats');
        if (response.success) {
            this.lastSync.stats = new Date();
            this.trigger('onDataUpdate', { type: 'stats', data: response.data });
        }
        return response;
    }
    
    /**
     * Créer un shift depuis une mission
     */
    async createShiftFromMission(missionId, employeeId) {
        this.log(`Creating shift from mission ${missionId} for employee ${employeeId}`);
        
        try {
            const response = await this.apiCall('create_shift_from_mission', {
                method: 'POST',
                body: JSON.stringify({
                    mission_id: missionId,
                    employee_id: employeeId
                })
            });
            
            if (response.success) {
                this.log(`Shift created: #${response.shift_id}`);
                // Re-sync shifts
                await this.syncShifts();
            }
            
            return response;
            
        } catch (error) {
            this.log('Error creating shift:', error, 'error');
            throw error;
        }
    }
    
    /**
     * Appel API générique
     */
    async apiCall(action, options = {}) {
        const url = `${this.apiEndpoint}?action=${action}`;
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        const fetchOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, fetchOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            return data;
            
        } catch (error) {
            this.log(`API call failed for action "${action}":`, error, 'error');
            throw error;
        }
    }
    
    /**
     * Ajouter un écouteur d'événement
     */
    on(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        } else {
            this.log(`Unknown event: ${event}`, 'warn');
        }
        return this;
    }
    
    /**
     * Retirer un écouteur d'événement
     */
    off(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
        }
        return this;
    }
    
    /**
     * Déclencher un événement
     */
    trigger(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    this.log(`Error in ${event} listener:`, error, 'error');
                }
            });
        }
    }
    
    /**
     * Logger (debug mode)
     */
    log(message, data = null, level = 'info') {
        if (!this.debug && level !== 'error') return;
        
        const timestamp = new Date().toISOString();
        const prefix = `[ERPSync ${timestamp}]`;
        
        switch (level) {
            case 'error':
                console.error(prefix, message, data);
                break;
            case 'warn':
                console.warn(prefix, message, data);
                break;
            default:
                console.log(prefix, message, data);
        }
    }
    
    /**
     * Obtenir le statut de la dernière synchronisation
     */
    getSyncStatus() {
        return {
            lastSync: this.lastSync,
            isAutoSyncActive: this.syncTimer !== null,
            syncInterval: this.syncInterval
        };
    }
    
    /**
     * Forcer une synchronisation immédiate
     */
    forceSync() {
        this.log('Force sync requested');
        return this.syncAll();
    }
}

// ============================================
// Utilitaires UI
// ============================================

class SyncUI {
    constructor(syncManager) {
        this.syncManager = syncManager;
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Indicateur de synchronisation
        this.syncManager.on('onSyncStart', () => {
            this.showSyncIndicator();
        });
        
        this.syncManager.on('onSyncComplete', (data) => {
            this.hideSyncIndicator();
            this.updateLastSyncTime();
            this.showNotification('Synchronisation réussie', 'success');
        });
        
        this.syncManager.on('onSyncError', (data) => {
            this.hideSyncIndicator();
            this.showNotification('Erreur de synchronisation', 'error');
        });
        
        this.syncManager.on('onDataUpdate', (data) => {
            this.updateUI(data.type, data.data);
        });
    }
    
    showSyncIndicator() {
        const indicator = this.getOrCreateIndicator();
        indicator.classList.add('syncing');
        indicator.innerHTML = '<i class="fas fa-sync fa-spin"></i> Synchronisation...';
    }
    
    hideSyncIndicator() {
        const indicator = this.getOrCreateIndicator();
        indicator.classList.remove('syncing');
        indicator.innerHTML = '<i class="fas fa-check"></i> Synchronisé';
        
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 2000);
    }
    
    getOrCreateIndicator() {
        let indicator = document.getElementById('sync-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'sync-indicator';
            indicator.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(59, 130, 246, 0.95);
                color: white;
                padding: 12px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                font-size: 14px;
                font-weight: 600;
                z-index: 10000;
                display: none;
                backdrop-filter: blur(10px);
            `;
            document.body.appendChild(indicator);
        }
        
        indicator.style.display = 'block';
        return indicator;
    }
    
    updateLastSyncTime() {
        const elements = document.querySelectorAll('.last-sync-time');
        const now = new Date().toLocaleTimeString('fr-FR');
        
        elements.forEach(el => {
            el.textContent = `Dernière sync: ${now}`;
        });
    }
    
    showNotification(message, type = 'info') {
        // Toast notification simple
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-size: 14px;
            font-weight: 600;
            z-index: 10001;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    updateUI(type, data) {
        // À personnaliser selon la page
        switch(type) {
            case 'stats':
                this.updateStats(data);
                break;
            case 'missions':
                this.updateMissionsTable(data);
                break;
            case 'shifts':
                this.updateShiftsTable(data);
                break;
        }
    }
    
    updateStats(stats) {
        // Mettre à jour les KPIs
        const elements = {
            'total-missions': stats.missions?.total,
            'active-missions': stats.missions?.active,
            'total-employees': stats.employees?.total,
            'active-employees': stats.employees?.active
        };
        
        Object.keys(elements).forEach(id => {
            const el = document.getElementById(id);
            if (el && elements[id] !== undefined) {
                el.textContent = elements[id];
            }
        });
    }
    
    updateMissionsTable(missions) {
        // Rafraîchir le tableau des missions
        const table = document.querySelector('#missionsTable tbody');
        if (table && missions.length > 0) {
            // Implementation spécifique au tableau
        }
    }
    
    updateShiftsTable(shifts) {
        // Rafraîchir le tableau des shifts
        const table = document.querySelector('#shiftsTable tbody');
        if (table && shifts.length > 0) {
            // Implementation spécifique au tableau
        }
    }
}

// ============================================
// Export & Auto-init
// ============================================

// Export pour utilisation en module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ERPSyncManager, SyncUI };
}

// Auto-initialisation si window.initERPSync = true
if (typeof window !== 'undefined') {
    window.ERPSyncManager = ERPSyncManager;
    window.SyncUI = SyncUI;
    
    // Init automatique
    document.addEventListener('DOMContentLoaded', () => {
        if (window.initERPSync) {
            const syncManager = new ERPSyncManager({
                debug: true,
                autoSync: true,
                syncInterval: 5 * 60 * 1000 // 5 minutes
            });
            
            const syncUI = new SyncUI(syncManager);
            
            // Exposer globalement
            window.syncManager = syncManager;
            window.syncUI = syncUI;
            
            console.log('✓ ERP Sync initialized');
        }
    });
}
