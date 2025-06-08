document.addEventListener('DOMContentLoaded', () => {
    const BASE_URL = window.location.origin + (window.location.pathname.startsWith('/producerleague') ? '/producerleague' : '');

    const urlParams = new URLSearchParams(window.location.search);
    const matchId = urlParams.get('match_id');

    const matchStatusEl = document.getElementById('match-status');
    const player1NameEl = document.getElementById('player1-name');
    const player1EloEl = document.getElementById('player1-elo');
    const player2NameEl = document.getElementById('player2-name');
    const player2EloEl = document.getElementById('player2-elo');
    const goToUploadBtn = document.getElementById('goToUploadBtn');

    if (!matchId) {
        document.getElementById('lobby-main').innerHTML = '<div class="card"><p>No match ID provided in the URL. Return to <a href="index.html">the homepage</a> to find a match.</p></div>';
        return;
    }

    if (goToUploadBtn) {
        goToUploadBtn.addEventListener('click', () => {
            localStorage.setItem(`match_started_${matchId}`, 'true');
            window.location.href = `${BASE_URL}/frontend/upload.html?match_id=${matchId}`;
        });
    }

    let pollingInterval;

    async function fetchMatchDetails() {
        try {
            const response = await fetch(`${BASE_URL}/backend/api/match/get_match_details.php?id=${matchId}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Failed to fetch match details. You may not be part of this match.');
            }

            const data = await response.json();
            if (data.success) {
                updateLobbyUI(data.match);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error fetching match details:', error);
            matchStatusEl.textContent = 'Error loading match.';
            if (pollingInterval) clearInterval(pollingInterval);
        }
    }

    function updateLobbyUI(match) {
        matchStatusEl.textContent = match.status.charAt(0).toUpperCase() + match.status.slice(1);

        if (match.player1_id) {
            player1NameEl.textContent = match.player1_producername;
            player1EloEl.textContent = `${match.player1_elo} ELO`;
        }

        if (match.player2_id) {
            player2NameEl.textContent = match.player2_producername;
            player2EloEl.textContent = `${match.player2_elo} ELO`;
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        } else {
            player2NameEl.textContent = 'Waiting for opponent...';
            player2EloEl.textContent = '';
        }
    }

    fetchMatchDetails();

    pollingInterval = setInterval(() => {
        const player2Name = player2NameEl.textContent;
        if (player2Name === '' || player2Name === 'Waiting for opponent...') {
            fetchMatchDetails();
        } else {
            if (pollingInterval) clearInterval(pollingInterval);
        }
    }, 5000); 
}); 