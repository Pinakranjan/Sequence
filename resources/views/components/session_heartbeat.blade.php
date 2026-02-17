<script>
    // Session heartbeat: redirects to login with a persisted toast when session ends
    (function(){
        'use strict';
        try {
            if (window.__sessionHeartbeatAttached) return; // singleton guard
            window.__sessionHeartbeatAttached = true;

            function writeToastAndRedirect(message, opts){
                try {
                    var payload = Object.assign({
                        message: message || 'Your session has ended.<br>Please login again.',
                        type: 'error',
                        positionClass: 'toast-bottom-right',
                        timeout: 5000
                    }, (opts || {}));
                    localStorage.setItem('toast-next', JSON.stringify(payload));
                } catch(_) {}
                var to = (opts && opts.redirect) ? opts.redirect : '/login';
                try { window.location.replace(to); } catch(_) { window.location.href = to; }
            }

            function checkAuthHeartbeat(){
                fetch('/__auth-check?_ts=' + Date.now(), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store'
                })
                .then(function(r){ return r.ok ? r.json() : { authenticated: false, redirect: '/login' }; })
                .then(function(j){
                    if (!j || j.authenticated !== true) {
                        writeToastAndRedirect('Your session has ended.<br>Please login again.', { redirect: (j && j.redirect) ? j.redirect : '/login' });
                    }
                })
                .catch(function(){ /* ignore transient errors */ });
            }

            // immediate check + 10s interval
            try { checkAuthHeartbeat(); } catch(_) {}
            setInterval(checkAuthHeartbeat, 10000);
        } catch(_) {}
    })();
</script>
