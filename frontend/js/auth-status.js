function clearOldMatchData() {
    // Clear all match-related localStorage data
    const keysToRemove = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && (key.startsWith('match_started_') || key.startsWith('uploaded_'))) {
            keysToRemove.push(key);
        }
    }
    keysToRemove.forEach(key => localStorage.removeItem(key));
    console.log('Cleared old match data:', keysToRemove);
}

async function findMatch() {
    console.log('find match called');
    try {
        const BASE_URL = window.location.origin + (window.location.pathname.startsWith('/producerleague') ? '/producerleague' : '');
        const response = await fetch(`${BASE_URL}/backend/api/match/quick.php`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            try {
                const errorData = JSON.parse(errorText);
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            } catch (e) {
                // Fallback for non-JSON error responses
                throw new Error(errorText || `HTTP error! status: ${response.status}`);
            }
        }

        const data = await response.json();

        if (data.success && data.match_id) {
            console.log('Match found/created:', data.match_id);
            // Clear old match data before starting new match
            clearOldMatchData();
            window.location.href = `${BASE_URL}/frontend/lobby.html?match_id=${data.match_id}`;
        } else {
            alert(data.message || 'Could not find or create a match.');
        }
    } catch (error) {
        console.error('Error finding match:', error);
        alert(`An error occurred while finding a match: ${error.message}`);
    }
}

console.log('setting up DOMContentLoaded listener for auth status and UI updates');
document.addEventListener('DOMContentLoaded', async () => {
    const profileNameElement = document.querySelector('.profile-name');
    const profileEloElement = document.querySelector('.profile-elo');
    const signOutLink = document.getElementById('signOutLink');
    const quickMatchBtn = document.getElementById('quickMatchBtn');
    const currentMatchLink = Array.from(document.querySelectorAll('.nav-link')).find(el => el.textContent.trim() === 'Current Match');

    const BASE_URL = window.location.origin + (window.location.pathname.startsWith('/producerleague') ? '/producerleague' : '');

    async function handleLogout(event) {
        if (event) event.preventDefault(); 

        try {
            const response = await fetch(`${BASE_URL}/backend/api/auth/logout.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = `${BASE_URL}/frontend/login.html`;
            } else {
                alert(data.message || 'logout failed. please try again.');
            }
        } catch (error) {
            console.error('logout error:', error);
            alert('an error occurred during logout.');
        }
    }

    const redirectToLogin = () => {
        console.log('quick match clicked by guest, redirecting to login');
        window.location.href = `${BASE_URL}/frontend/login.html`;
    };

    async function updateCurrentMatchLink() {
        if (!currentMatchLink) return;

        try {
            const response = await fetch(`${BASE_URL}/backend/api/match/get_current_match.php`);
            if (!response.ok) return; 
            const data = await response.json();

            if (data.in_match && data.match_id) {
                const matchStarted = localStorage.getItem(`match_started_${data.match_id}`) === 'true';
                const hasUploaded = localStorage.getItem(`uploaded_${data.match_id}`) === 'true';
                
                if (hasUploaded) {
                    // User has uploaded, show battle results
                    currentMatchLink.href = `${BASE_URL}/frontend/complete.html?match_id=${data.match_id}`;
                } else if (matchStarted) {
                    // Match started but user hasn't uploaded yet
                    currentMatchLink.href = `${BASE_URL}/frontend/upload.html?match_id=${data.match_id}`;
                } else {
                    // Match not started yet
                    currentMatchLink.href = `${BASE_URL}/frontend/lobby.html?match_id=${data.match_id}`;
                }
            } else {
                currentMatchLink.href = `${BASE_URL}/frontend/lobby.html`;
            }
        } catch (error) {
            console.error('Could not check for current match:', error);
        }
    }

    try {
        console.log('checking auth status via DOMContentLoaded...');
        const response = await fetch(`${BASE_URL}/backend/api/auth/status.php`);
        console.log('requesting auth status from:', `${BASE_URL}/backend/api/auth/status.php`);
        
        if (!response.ok) {
            throw new Error(`auth status check failed with HTTP status ${response.status}`);
        }
        
        const authData = await response.json();
        console.log('auth status response:', authData);
        
        if (authData.logged_in && authData.user) {
            console.log('user is logged in:', authData.user);

            if (profileNameElement) profileNameElement.textContent = authData.user.producer_name;
            if (profileEloElement) profileEloElement.textContent = `${authData.user.elo} ELO`;
            
            if (signOutLink) {
                signOutLink.style.display = 'inline';
                signOutLink.removeEventListener('click', handleLogout);
                signOutLink.addEventListener('click', handleLogout);
            }
            
            await updateCurrentMatchLink();

            if (quickMatchBtn) {
                quickMatchBtn.removeEventListener('click', redirectToLogin);
                quickMatchBtn.addEventListener('click', findMatch);
            }
        } else {
            console.log('user is not logged in or authData is incomplete.');
            if (profileNameElement) profileNameElement.textContent = 'Guest User';
            if (profileEloElement) profileEloElement.textContent = '1500 ELO';
            
            if (signOutLink) {
                signOutLink.style.display = 'none';
            }
            
            const protectedPages = ['lobby.html', 'upload.html'];
            const currentPage = window.location.pathname.split('/').pop();

            if (protectedPages.includes(currentPage)) {
                if (currentPage !== 'lobby.html' || new URLSearchParams(window.location.search).has('match_id')) {
                    window.location.href = `${BASE_URL}/frontend/login.html`;
                }
            }
            
            if (quickMatchBtn) {
                quickMatchBtn.removeEventListener('click', findMatch);
                quickMatchBtn.addEventListener('click', redirectToLogin);
            }

            if (currentMatchLink) {
                currentMatchLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    redirectToLogin();
                });
            }
        }
    } catch (error) {
        console.error('auth check or UI update failed:', error);

        if (profileNameElement) profileNameElement.textContent = 'Guest User';
        if (profileEloElement) profileEloElement.textContent = 'Error';
        if (signOutLink) signOutLink.style.display = 'none';
        if (quickMatchBtn) {
            quickMatchBtn.textContent = 'Login to Play';
            quickMatchBtn.removeEventListener('click', findMatch);
            quickMatchBtn.addEventListener('click', redirectToLogin);
        }
    }
}); 