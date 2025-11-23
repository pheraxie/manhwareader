import { createClient } from "https://esm.sh/@supabase/supabase-js";

const supabaseUrl = "https://carykkmxuvhmxrjawphq.supabase.co";
const supabaseKey = "sb_publishable_tI_9bp91r0pXKHmEARX6FA_XS4_IBoE";

export const supabase = createClient(supabaseUrl, supabaseKey);
