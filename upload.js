
const fileInput = document.getElementById('fileInput');
const selectFileBtn = document.getElementById('selectFileBtn');
const submitBtn = document.getElementById('submitBtn');
const dropZone = document.getElementById('dropZone');
let selectedFile = null;

// Show file selector
selectFileBtn.addEventListener('click', () => fileInput.click());

// Enable submit button when file selected
fileInput.addEventListener('change', () => {
selectedFile = fileInput.files[0];
if (selectedFile) {
    submitBtn.disabled = false;
}
});

// Drag and drop
dropZone.addEventListener('dragover', (e) => {
e.preventDefault();
dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
e.preventDefault();
dropZone.classList.remove('dragover');
selectedFile = e.dataTransfer.files[0];
if (selectedFile) {
    submitBtn.disabled = false;
}
});

// Handle form submission
document.getElementById('uploadForm').addEventListener('submit', (e) => {
e.preventDefault();
if (!selectedFile) return;

const formData = new FormData();
formData.append('file', selectedFile);
formData.append('user_id', 'guest_user'); 

fetch('../backend/upload.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => {
    alert(data.message);
    if (data.success) {
    submitBtn.disabled = true;
    fileInput.value = '';
    }
})
.catch(() => alert('Upload failed.'));
});
