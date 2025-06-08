document.addEventListener('DOMContentLoaded', () => {
    const BASE_URL = window.location.origin + (window.location.pathname.startsWith('/producerleague') ? '/producerleague' : '');
    
    // UI Elements
    const loadingMessage = document.getElementById('loading-message');
    const noMatchesMessage = document.getElementById('no-matches-message');
    const votingInterface = document.getElementById('voting-interface');
    
    // Match info elements
    const player1Name = document.getElementById('player1Name');
    const player2Name = document.getElementById('player2Name');
    const player1NameTrack = document.getElementById('player1NameTrack');
    const player2NameTrack = document.getElementById('player2NameTrack');
    const player1Votes = document.getElementById('player1Votes');
    const player2Votes = document.getElementById('player2Votes');
    
    // Audio elements
    const player1Audio = document.getElementById('player1Audio');
    const player2Audio = document.getElementById('player2Audio');
    const player1Source = document.getElementById('player1Source');
    const player2Source = document.getElementById('player2Source');
    
    // Voting elements
    const votePlayer1Btn = document.getElementById('votePlayer1Btn');
    const votePlayer2Btn = document.getElementById('votePlayer2Btn');
    const votingStatusText = document.getElementById('votingStatusText');
    
    let currentMatches = [];
    let currentMatchIndex = 0;
    
    // Load matches on page load
    loadMatches();
    
    // Vote button event listeners
    votePlayer1Btn.addEventListener('click', () => {
        submitVote(1);
    });
    
    votePlayer2Btn.addEventListener('click', () => {
        submitVote(2);
    });
    
    async function loadMatches() {
        try {
            showLoading();
            
            const response = await fetch(`${BASE_URL}/backend/api/vote/get_matches.php`);
            const data = await response.json();
            
            if (data.success) {
                // Filter out matches the user has already voted on
                currentMatches = data.matches.filter(match => !match.user_voted);
                currentMatchIndex = 0;
                
                if (currentMatches.length > 0) {
                    displayCurrentMatch();
                } else {
                    showNoMatches();
                }
            } else {
                console.error('Error loading matches:', data.message);
                showNoMatches();
            }
        } catch (error) {
            console.error('Error loading matches:', error);
            showNoMatches();
        }
    }
    
    function displayCurrentMatch() {
        if (currentMatchIndex >= currentMatches.length) {
            showNoMatches();
            return;
        }
        
        const match = currentMatches[currentMatchIndex];
        
        // Update all name fields
        player1Name.textContent = match.player1_name;
        player2Name.textContent = match.player2_name;
        player1NameTrack.textContent = match.player1_name;
        player2NameTrack.textContent = match.player2_name;
        
        // Update vote counts
        player1Votes.textContent = match.player1_votes || 0;
        player2Votes.textContent = match.player2_votes || 0;
        
        // Set audio sources
        player1Source.src = `${BASE_URL}/${match.player1_track}`;
        player2Source.src = `${BASE_URL}/${match.player2_track}`;
        
        // Reload audio elements to apply new sources
        player1Audio.load();
        player2Audio.load();
        
        // Enable vote buttons
        votePlayer1Btn.disabled = false;
        votePlayer2Btn.disabled = false;
        
        // Update status text
        votingStatusText.textContent = 'Listen to both tracks and cast your vote';
        
        showVotingInterface();
    }
    
    async function submitVote(votedFor) {
        if (currentMatchIndex >= currentMatches.length) return;
        
        const match = currentMatches[currentMatchIndex];
        
        try {
            // Disable vote buttons during submission
            votePlayer1Btn.disabled = true;
            votePlayer2Btn.disabled = true;
            votingStatusText.textContent = 'Submitting vote...';
            
            const response = await fetch(`${BASE_URL}/backend/api/vote/submit_vote.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    match_id: match.match_id,
                    voted_for: votedFor
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                votingStatusText.textContent = 'Vote submitted! Loading next match...';
                
                // Move to next match after a short delay
                setTimeout(() => {
                    currentMatchIndex++;
                    displayCurrentMatch();
                }, 1500);
            } else {
                votingStatusText.textContent = `Error: ${data.message}`;
                // Re-enable buttons if there was an error
                votePlayer1Btn.disabled = false;
                votePlayer2Btn.disabled = false;
            }
        } catch (error) {
            console.error('Error submitting vote:', error);
            votingStatusText.textContent = 'Error submitting vote. Please try again.';
            // Re-enable buttons if there was an error
            votePlayer1Btn.disabled = false;
            votePlayer2Btn.disabled = false;
        }
    }
    
    function showLoading() {
        loadingMessage.style.display = 'block';
        noMatchesMessage.style.display = 'none';
        votingInterface.style.display = 'none';
    }
    
    function showNoMatches() {
        loadingMessage.style.display = 'none';
        noMatchesMessage.style.display = 'block';
        votingInterface.style.display = 'none';
    }
    
    function showVotingInterface() {
        loadingMessage.style.display = 'none';
        noMatchesMessage.style.display = 'none';
        votingInterface.style.display = 'block';
    }
}); 