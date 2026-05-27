/**
 * Utilitaires AJAX basés sur Fetch API
 */

const Api = {
    /**
     * Effectue une requête POST
     */
    post: async function(endpoint, data = {}) {
        // Ajout automatique du token CSRF si disponible
        if(typeof CSRF_TOKEN !== 'undefined' && !(data instanceof FormData)) {
            data.csrf_token = CSRF_TOKEN;
        }

        let options = {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            }
        };

        if (data instanceof FormData) {
            if(typeof CSRF_TOKEN !== 'undefined' && !data.has('csrf_token')) {
                data.append('csrf_token', CSRF_TOKEN);
            }
            options.body = data;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(BASE_URL + endpoint, options);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error("Erreur AJAX POST:", error);
            return { success: false, message: "Erreur de connexion au serveur." };
        }
    },

    /**
     * Effectue une requête GET
     */
    get: async function(endpoint) {
        try {
            const response = await fetch(BASE_URL + endpoint, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error("Erreur AJAX GET:", error);
            return { success: false, message: "Erreur de connexion au serveur." };
        }
    }
};
