// api-client.js — Client API Supabase
import { supabase } from './supabase.js';

console.log(`🌐 API Mode: SUPABASE (CONNECTÉ)`);

// ============ COMMENTS API ============

export const CommentsAPI = {
    // Charger les commentaires
    async getComments(manhwaId, chapterNumber) {
        try {
            const { data, error } = await supabase
                .from('comments')
                .select('*')
                .eq('manhwa_id', manhwaId)
                .eq('chapter_number', chapterNumber)
                .order('date', { ascending: false });
            
            if (error) throw error;
            return { success: true, data: data || [] };
        } catch (err) {
            console.error('CommentsAPI.getComments error:', err);
            return { success: false, data: [], error: err.message };
        }
    },

    // Créer/ajouter un commentaire
    async addComment(payload) {
        try {
            const images = Array.isArray(payload.images) ? JSON.stringify(payload.images) : payload.images;
            const { data, error } = await supabase
                .from('comments')
                .insert([{
                    id: payload.id || 'comment_' + Date.now(),
                    manhwa_id: payload.manhwa_id,
                    chapter_number: payload.chapter_number,
                    author: payload.author || 'Anonyme',
                    text: payload.text,
                    images: images,
                    date: payload.date || new Date().toISOString()
                }]);
            
            if (error) throw error;
            return { success: true, id: payload.id };
        } catch (err) {
            console.error('CommentsAPI.addComment error:', err);
            return { success: false, error: err.message };
        }
    },

    // Supprimer un commentaire
    async deleteComment(commentId, manhwaId = null, chapterNumber = null) {
        try {
            let query = supabase.from('comments').delete();
            if (commentId && !commentId.includes('_')) {
                query = query.eq('id', commentId);
            } else if (manhwaId) {
                query = query.eq('manhwa_id', manhwaId);
                if (chapterNumber !== null) query = query.eq('chapter_number', chapterNumber);
            }
            const { error } = await query;
            if (error) throw error;
            return { success: true };
        } catch (err) {
            console.error('CommentsAPI.deleteComment error:', err);
            return { success: false, error: err.message };
        }
    }
};

// ============ MANHWAS API ============

export const ManhwasAPI = {
    // Charger tous les manhwas
    async getAllManhwas() {
        try {
            const { data, error } = await supabase
                .from('manhwas')
                .select('*')
                .order('order_index', { ascending: true });
            
            if (error) throw error;
            return { success: true, data: data || [] };
        } catch (err) {
            console.error('ManhwasAPI.getAllManhwas error:', err);
            return { success: false, data: [], error: err.message };
        }
    },

    // Créer un manhwa
    async createManhwa(payload) {
        try {
            const { data, error } = await supabase
                .from('manhwas')
                .insert([payload]);
            
            if (error) throw error;
            return { success: true, data };
        } catch (err) {
            console.error('ManhwasAPI.createManhwa error:', err);
            return { success: false, error: err.message };
        }
    },

    // Mettre à jour un manhwa
    async updateManhwa(id, payload) {
        try {
            const { data, error } = await supabase
                .from('manhwas')
                .update(payload)
                .eq('manhwa_id', id);
            
            if (error) throw error;
            return { success: true, data };
        } catch (err) {
            console.error('ManhwasAPI.updateManhwa error:', err);
            return { success: false, error: err.message };
        }
    },

    // Supprimer un manhwa
    async deleteManhwa(id) {
        try {
            const { error } = await supabase
                .from('manhwas')
                .delete()
                .eq('manhwa_id', id);
            
            if (error) throw error;
            return { success: true };
        } catch (err) {
            console.error('ManhwasAPI.deleteManhwa error:', err);
            return { success: false, error: err.message };
        }
    }
};

// ============ CHAPTERS API ============

export const ChaptersAPI = {
    // Charger tous les chapitres
    async getAllChapters() {
        try {
            const { data, error } = await supabase
                .from('chapters')
                .select('*')
                .order('chapter_number', { ascending: true });
            
            if (error) throw error;
            return { success: true, data: data || [] };
        } catch (err) {
            console.error('ChaptersAPI.getAllChapters error:', err);
            return { success: false, data: [], error: err.message };
        }
    },

    // Charger les chapitres d'un manhwa spécifique
    async getChaptersByManhwaId(manhwaId) {
        try {
            const { data, error } = await supabase
                .from('chapters')
                .select('*')
                .eq('manhwa_id', manhwaId)
                .order('chapter_number', { ascending: false });
            
            if (error) throw error;
            return { success: true, data: data || [] };
        } catch (err) {
            console.error('ChaptersAPI.getChaptersByManhwaId error:', err);
            return { success: false, data: [], error: err.message };
        }
    },

    // Créer un chapitre
    async createChapter(payload) {
        try {
            const { data, error } = await supabase
                .from('chapters')
                .insert([payload]);
            
            if (error) throw error;
            return { success: true, data };
        } catch (err) {
            console.error('ChaptersAPI.createChapter error:', err);
            return { success: false, error: err.message };
        }
    },

    // Mettre à jour un chapitre
    async updateChapter(id, payload) {
        try {
            const { data, error } = await supabase
                .from('chapters')
                .update(payload)
                .eq('chapter_id', id);
            
            if (error) throw error;
            return { success: true, data };
        } catch (err) {
            console.error('ChaptersAPI.updateChapter error:', err);
            return { success: false, error: err.message };
        }
    },

    // Supprimer un chapitre
    async deleteChapter(id) {
        try {
            const { error } = await supabase
                .from('chapters')
                .delete()
                .eq('chapter_id', id);
            
            if (error) throw error;
            return { success: true };
        } catch (err) {
            console.error('ChaptersAPI.deleteChapter error:', err);
            return { success: false, error: err.message };
        }
    }
};

// Export Supabase client for advanced queries
export { supabase };
