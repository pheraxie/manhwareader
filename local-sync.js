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

// Also create a small header sync button so it's easy to find
export function registerHeaderSyncButton() {
    try {
        // only show in local / developer mode
        if (!(window.isLocalMode === true || (typeof isDeveloperMode === 'function' && isDeveloperMode()))) return;
        if (document.getElementById('header-sync-button')) return;
        const header = document.querySelector('header') || document.body;
        const btn = document.createElement('button');
        btn.id = 'header-sync-button';
        btn.textContent = 'Synchroniser';
        btn.style.position = 'fixed';
        btn.style.top = '12px';
        btn.style.right = '12px';
        btn.style.zIndex = 99999;
        btn.style.padding = '8px 10px';
        btn.style.background = '#059669';
        btn.style.color = 'white';
        btn.style.border = 'none';
        btn.style.borderRadius = '6px';
        btn.style.cursor = 'pointer';
        document.body.appendChild(btn);
        btn.addEventListener('click', async () => {
            btn.disabled = true; btn.textContent = 'Sync...';
            await syncLocalToSupabase();
            btn.disabled = false; btn.textContent = 'Synchroniser';
        });
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
