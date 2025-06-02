document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    try {
        const BASE_URL = window.location.origin + '/producerleague'; // Define BASE_URL
        const response = await fetch(`${BASE_URL}/backend/api/auth/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Login failed');
        }
        
        // Login successful - redirect to home page
        window.location.href = `${BASE_URL}/frontend/index.html`;
        
    } catch (error) {
        alert(error.message);
    }
}); 