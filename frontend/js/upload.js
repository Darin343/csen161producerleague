const fileInput = document.getElementById('fileInput');
const selectFileBtn = document.getElementById('selectFileBtn');
const submitBtn = document.getElementById('submitBtn');
const dropZone = document.getElementById('dropZone');
let selectedFile = null;

// Get match ID from URL parameters
const urlParams = new URLSearchParams(window.location.search);
const matchId = urlParams.get('match_id');

if (!matchId) {
    alert('No match ID provided. Redirecting to lobby.');
    window.location.href = 'lobby.html';
}

function updateDropZone(file) {
    if (file) {
        dropZone.innerHTML = `<p>Selected File: ${file.name}</p>`;
        submitBtn.disabled = false;
    } else {
        dropZone.innerHTML = `<p>Drag & Drop your sample here</p>`;
        submitBtn.disabled = true;
    }
}

// Show file selector
selectFileBtn.addEventListener('click', () => fileInput.click());

// Enable submit button when file selected
fileInput.addEventListener('change', () => {
    selectedFile = fileInput.files[0];
    updateDropZone(selectedFile);
});

// Drag and drop
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-over');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    selectedFile = e.dataTransfer.files[0];
    updateDropZone(selectedFile);
});

// Handle form submission
document.getElementById('uploadForm').addEventListener('submit', (e) => {
e.preventDefault();
if (!selectedFile) return;

// File size check (e.g., 100MB limit)
// NOTE: You must also increase upload_max_filesize in your php.ini file
// to allow larger files to be uploaded by the server.
const MAX_FILE_SIZE = 100 * 1024 * 1024; 
if (selectedFile.size > MAX_FILE_SIZE) {
    alert(`File is too large. Maximum size is ${MAX_FILE_SIZE / 1024 / 1024}MB.`);
    return;
}

const formData = new FormData();
formData.append('file', selectedFile);
formData.append('match_id', matchId); // Include match ID

fetch('../backend/api/auth/upload.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => {
    alert(data.message);
    if (data.success) {
        // Mark that user has uploaded for this match
        localStorage.setItem(`uploaded_${matchId}`, 'true');
        
        submitBtn.disabled = true;
        fileInput.value = '';
        selectedFile = null;
        updateDropZone(null);
        // Redirect to complete page to view battle results
        setTimeout(() => {
            window.location.href = `complete.html?match_id=${matchId}`;
        }, 1500);
    }
})
.catch(() => alert('Upload failed.'));
});
