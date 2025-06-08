document.getElementById('signupForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const producerName = document.getElementById('producerName').value;
    const email = document.getElementById('email').value;
    
    if (password !== confirmPassword) {
        alert('passwords do not match');
        return;
    }
    
    try {
        const BASE_URL = window.location.origin + '/producerleague';
        const response = await fetch(`${BASE_URL}/backend/api/auth/signup.php`, { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                username, 
                password,
                producerName,
                email
            })
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'signup failed');
        }
        
        //signup successful - redirect to home page
        window.location.href = `${BASE_URL}/frontend/index.html`;
        
    } catch (error) {
        alert(error.message);
    }
}); 