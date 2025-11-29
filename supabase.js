import { createClient } from "https://esm.sh/@supabase/supabase-js";

const supabaseUrl = "https://carykkmxuvhmxrjawphq.supabase.co";
const supabaseKey = "sb_publishable_tI_9bp91r0pXKHmEARX6FA_XS4_IBoE";

export const supabase = createClient(supabaseUrl, supabaseKey);

// Configuration d'accès à Supabase
export const supabaseConfig = {
    url: supabaseUrl,
    key: supabaseKey,
    isConfigured: true
};

// État de synchronisation
export let syncState = {
    lastSync: null,
    isSyncing: false,
    syncError: null
};

// Fonction pour mettre à jour l'état de sync
export function updateSyncState(updates) {
    syncState = { ...syncState, ...updates };
};
