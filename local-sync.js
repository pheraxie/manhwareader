// local-sync.js — Client-side local -> Supabase synchronisation helper
import { supabase } from './supabase.js';

async function fetchLocalAllData() {
    try {
        const resp = await fetch('./api-all-data.php?t=' + Date.now(), { cache: 'no-cache' });
        if (!resp.ok) throw new Error('api-all-data.php HTTP ' + resp.status);
        const json = await resp.json();
        if (!json || !json.success) throw new Error('api-all-data.php returned invalid payload');
        return json.data || [];
    } catch (err) {
        console.error('fetchLocalAllData error:', err);
        throw err;
    }
}

async function fetchLocalComments() {
    try {
        const resp = await fetch('./api-comments.php?t=' + Date.now(), { cache: 'no-cache' });
        if (!resp.ok) throw new Error('api-comments.php HTTP ' + resp.status);
        const json = await resp.json();
        return json || [];
    } catch (err) {
        console.error('fetchLocalComments error:', err);
        return [];
    }
}

function ensureNotification(msg, level = 'info') {
    if (typeof showNotification === 'function') {
        showNotification(msg, level);
    } else {
        console.log(msg);
    }
}

async function syncLocalToSupabase() {
    ensureNotification('Démarrage de la synchronisation locale → Supabase', 'info');
    try {
        const allData = await fetchLocalAllData();
        const localComments = await fetchLocalComments();

        // Separate manhwas and chapters from the mixed array
        const manhwas = [];
        const chapters = [];
        const tracking = [];
        const trash = [];

        for (const item of allData) {
            if (item && typeof item === 'object') {
                if (item.manhwa_title !== undefined) manhwas.push(item);
                else if (item.chapter_number !== undefined) chapters.push(item);
                else if (item.type === 'tracking') tracking.push(item);
                else if (item.type === 'trash') trash.push(item);
            }
        }

        // Fetch existing keys from Supabase to avoid per-row queries
        const { data: remoteManhwas } = await supabase.from('manhwas').select('manhwa_id');
        const existingManhwas = new Set((remoteManhwas || []).map(r => r.manhwa_id));

        const manhwasToInsert = manhwas.filter(m => m.manhwa_id && !existingManhwas.has(m.manhwa_id)).map(m => ({
            manhwa_id: m.manhwa_id,
            manhwa_title: m.manhwa_title || null,
            manhwa_cover: m.manhwa_cover || null,
            manhwa_description: m.manhwa_description || null,
            manhwa_season: m.manhwa_season || null,
            read_count: m.read_count || 0,
            order_index: m.order_index || 0,
            last_read_at: m.last_read_at || null,
            date_added: m.date_added || null
        }));

        if (manhwasToInsert.length) {
            const { error } = await supabase.from('manhwas').insert(manhwasToInsert);
            if (error) throw error;
            ensureNotification(`${manhwasToInsert.length} manhwas ajoutés à Supabase`, 'success');
        } else {
            console.log('Aucun manhwa local à insérer');
        }

        // Chapters: build set of existing remote keys "manhwa_id|chapter_number"
        const { data: remoteChapters } = await supabase.from('chapters').select('manhwa_id,chapter_number');
        const existingChapters = new Set((remoteChapters || []).map(r => `${r.manhwa_id}|${r.chapter_number}`));

        const chaptersToInsert = chapters.filter(c => c.manhwa_id && (c.chapter_number !== undefined)).map(c => ({
            chapter_id: c.__backendId ? String(c.__backendId) : undefined,
            manhwa_id: c.manhwa_id,
            chapter_number: Number(c.chapter_number),
            chapter_title: c.chapter_title || null,
            chapter_description: c.chapter_description || null,
            chapter_season: c.chapter_season || null,
            last_read_at: c.last_read_at || null,
            chapter_pages: c.chapter_pages || null,
            chapter_cover: c.chapter_cover || null,
            is_favorite: c.is_favorite ? true : false,
            date_added: c.date_added || null
        })).filter(c => !existingChapters.has(`${c.manhwa_id}|${c.chapter_number}`));

        if (chaptersToInsert.length) {
            const { error } = await supabase.from('chapters').insert(chaptersToInsert);
            if (error) throw error;
            ensureNotification(`${chaptersToInsert.length} chapitres ajoutés à Supabase`, 'success');
        } else {
            console.log('Aucun chapitre local à insérer');
        }

        // Comments: use id uniqueness
        const { data: remoteComments } = await supabase.from('comments').select('id');
        const existingComments = new Set((remoteComments || []).map(r => r.id));

        const commentsToInsert = (Array.isArray(localComments) ? localComments : []).filter(c => c.id && !existingComments.has(c.id)).map(c => ({
            id: c.id,
            manhwa_id: c.manhwa_id,
            chapter_number: c.chapter_number !== undefined ? Number(c.chapter_number) : null,
            author: c.author || 'Anonyme',
            text: c.text || '',
            images: typeof c.images === 'string' ? c.images : JSON.stringify(c.images || []),
            date: c.date || new Date().toISOString()
        }));

        if (commentsToInsert.length) {
            const { error } = await supabase.from('comments').insert(commentsToInsert);
            if (error) throw error;
            ensureNotification(`${commentsToInsert.length} commentaires ajoutés à Supabase`, 'success');
        } else {
            console.log('Aucun commentaire local à insérer');
        }

        ensureNotification('Synchronisation terminée', 'success');
        return { success: true };
    } catch (err) {
        console.error('syncLocalToSupabase error:', err);
        ensureNotification('Erreur lors de la synchronisation: ' + (err.message || err), 'error');
        return { success: false, error: err.message || String(err) };
    }
}

// Create a button only visible in local mode and bind the click to sync
// Register a sync button inside the settings panel (instead of floating)
export function registerSyncInSettings() {
    try {
        const container = document.querySelector('#settings-panel .p-6');
        if (!container) return;
        const existing = document.getElementById('settings-sync-row');
        if (existing) return;

        const row = document.createElement('div');
        row.id = 'settings-sync-row';
        row.className = 'mb-4';
        row.innerHTML = `
            <div class="mb-2 font-medium">Synchronisation</div>
            <button id="settings-sync-button" class="w-full bg-emerald-500 text-white px-4 py-2 rounded-lg">Synchroniser (local → Supabase)</button>
        `;
        container.appendChild(row);

        const btn = document.getElementById('settings-sync-button');
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            const orig = btn.textContent;
            btn.textContent = 'Synchronisation en cours...';
            await syncLocalToSupabase();
            btn.textContent = orig;
            btn.disabled = false;
        });
    } catch (err) {
        console.error('registerSyncInSettings error:', err);
    }
}

// Add sync button to the header area as a proper icon button
export function registerHeaderSyncButton() {
    try {
        if (!(window.isLocalMode === true || (typeof isDeveloperMode === 'function' && isDeveloperMode()))) return;
        if (document.getElementById('header-sync-button')) return;
        
        const btn = document.createElement('button');
        btn.id = 'header-sync-button';
        btn.title = 'Synchroniser avec Supabase';
        btn.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
        btn.style.cssText = `
            position: fixed;
            top: 12px;
            right: 12px;
            z-index: 50;
            padding: 8px 10px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: 2px solid #047857;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        `;
        
        btn.addEventListener('mouseenter', () => {
            btn.style.background = 'linear-gradient(135deg, #059669 0%, #047857 100%)';
            btn.style.boxShadow = '0 6px 16px rgba(16, 185, 129, 0.4)';
            btn.style.transform = 'scale(1.05)';
        });
        
        btn.addEventListener('mouseleave', () => {
            btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            btn.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.3)';
            btn.style.transform = 'scale(1)';
        });
        
        document.body.appendChild(btn);
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            const svg = btn.querySelector('svg');
            if (svg) svg.style.animation = 'spin 1s linear infinite';
            await syncLocalToSupabase();
            btn.disabled = false;
            btn.style.opacity = '1';
            if (svg) svg.style.animation = '';
        });
        
        // Ajouter animation CSS si elle n'existe pas
        if (!document.getElementById('sync-button-styles')) {
            const style = document.createElement('style');
            style.id = 'sync-button-styles';
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                #header-sync-button svg {
                    width: 20px;
                    height: 20px;
                }
            `;
            document.head.appendChild(style);
        }
    } catch (err) {
        console.error('registerHeaderSyncButton error:', err);
    }
}

// Utility: scan a folder and create missing chapters locally (calls server scan and posts chapters)
export async function autoCreateChaptersFromFolder(manhwaId, folder) {
    try {
        const r = await fetch(`./api-scan-chapters.php?folder=${encodeURIComponent(folder)}`);
        const json = await r.json();
        if (!json.success) return { success: false, message: json.message };
        const chapters = json.chapters || [];
        // For each chapter number, post to local API to create chapter
        let created = 0;
        for (const num of chapters) {
            // Check if chapter already exists locally
            const check = await fetch(`./api-chapters.php?manhwa_id=${encodeURIComponent(manhwaId)}`);
            const list = await check.json();
            const exists = Array.isArray(list.data) && list.data.some(c => Number(c.chapter_number) === Number(num));
            if (!exists) {
                await fetch('./api-chapters.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ manhwa_id: manhwaId, chapter_number: num })
                });
                created++;
            }
        }
        return { success: true, created };
    } catch (err) {
        console.error('autoCreateChaptersFromFolder error:', err);
        return { success: false, error: err.message };
    }
}
