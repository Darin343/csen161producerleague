// Function to handle quick match (will be referenced by the DOMContentLoaded listener)
// This can remain here or be moved inside DOMContentLoaded if preferred.
// For now, let's assume it's accessible.
async function findMatch() {
    console.log('Find match called');
    // Quick match functionality will be implemented later
}

console.log('Setting up DOMContentLoaded listener for auth status and UI updates');
document.addEventListener('DOMContentLoaded', async () => {
    const profileNameElement = document.querySelector('.profile-name');
    const profileEloElement = document.querySelector('.profile-elo');
    const signOutLink = document.getElementById('signOutLink');
    const quickMatchBtn = document.getElementById('quickMatchBtn');

    // More robust BASE_URL definition
    const BASE_URL = window.location.origin + (window.location.pathname.startsWith('/producerleague') ? '/producerleague' : '');

    // Function to handle logout
    async function handleLogout(event) {
        if (event) event.preventDefault(); // Prevent default link behavior

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
                alert(data.message || 'Logout failed. Please try again.');
            }
        } catch (error) {
            console.error('Logout error:', error);
            alert('An error occurred during logout.');
        }
    }

    // Helper function for redirecting to login (for guest quick match click)
    const redirectToLogin = () => {
        console.log('Quick match clicked by guest, redirecting to login');
        window.location.href = `${BASE_URL}/frontend/login.html`;
    };

    try {
        console.log('Checking auth status via DOMContentLoaded...');
        // Use your existing status endpoint
        const response = await fetch(`${BASE_URL}/backend/api/auth/status.php`);
        console.log('Requesting auth status from:', `${BASE_URL}/backend/api/auth/status.php`);
        
        if (!response.ok) {
            // Handle HTTP errors like 404 or 500 if status.php is not found or errors out
            throw new Error(`Auth status check failed with HTTP status ${response.status}`);
        }
        
        const authData = await response.json(); // Changed variable name for clarity
        console.log('Auth status response:', authData);
        
        if (authData.logged_in && authData.user) {
            console.log('User is logged in:', authData.user);
            // Update nav bar for logged-in user
            if (profileNameElement) profileNameElement.textContent = authData.user.producer_name; // Use producer_name
            if (profileEloElement) profileEloElement.textContent = `${authData.user.elo} ELO`;
            
            // Show and setup sign-out link
            if (signOutLink) {
                signOutLink.style.display = 'inline';
                signOutLink.removeEventListener('click', handleLogout); // Prevent multiple listeners
                signOutLink.addEventListener('click', handleLogout);
            }
            
            // Setup quick match button for logged-in user
            if (quickMatchBtn) {
                console.log('Quick match button found (logged in):', !!quickMatchBtn);
                quickMatchBtn.removeEventListener('click', redirectToLogin); // Remove guest listener
                quickMatchBtn.addEventListener('click', findMatch); // Add logged-in listener
            }
        } else {
            console.log('User is not logged in or authData is incomplete.');
            // Update nav bar for guest user
            if (profileNameElement) profileNameElement.textContent = 'Guest User';
            if (profileEloElement) profileEloElement.textContent = '1500 ELO';
            
            // Hide sign-out link
            if (signOutLink) {
                signOutLink.style.display = 'none';
            }
            
            // Redirect from protected pages if user is a guest
            const protectedPages = ['lobby.html', 'upload.html']; // As in your original logic
            const currentPage = window.location.pathname.split('/').pop();
            console.log('Current page for guest check:', currentPage);
            
            if (protectedPages.includes(currentPage)) {
                console.log('Guest on protected page, redirecting to login.');
                window.location.href = `${BASE_URL}/frontend/login.html`;
            }
            
            // Setup quick match button to redirect to login for guests
            if (quickMatchBtn) {
                console.log('Quick match button found (guest):', !!quickMatchBtn);
                quickMatchBtn.removeEventListener('click', findMatch); // Remove logged-in listener
                quickMatchBtn.addEventListener('click', redirectToLogin); // Add guest listener
            }
        }
    } catch (error) {
        console.error('Auth check or UI update failed:', error);
        if (error.message.includes('HTTP status')) {
             console.error('The auth status endpoint might be down or returning an error.');
        } else if (error instanceof TypeError) {
            console.error('Network error or issue parsing JSON from auth status endpoint.');
        }
        
        // Fallback UI for error state
        if (profileNameElement) profileNameElement.textContent = 'Guest User';
        if (profileEloElement) profileEloElement.textContent = 'Error'; // Indicate error
        if (signOutLink) signOutLink.style.display = 'none';
        // Optionally, disable quickMatchBtn or set it to redirect to login on error
        if (quickMatchBtn) {
            quickMatchBtn.textContent = 'Login to Play';
            quickMatchBtn.removeEventListener('click', findMatch);
            quickMatchBtn.addEventListener('click', redirectToLogin);
        }
    }
}); 