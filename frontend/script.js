document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('uploadForm');
  const pdfInput = document.getElementById('pdfFile');
  const status = document.getElementById('status');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    status.textContent = 'Uploading & converting...';

    const file = pdfInput.files[0];
    if (!file || file.type !== 'application/pdf') {
      status.textContent = 'Please select a valid PDF.';
      return;
    }

    const formData = new FormData();
    formData.append('pdf', file);
    formData.append('format', document.getElementById('format').value);
    formData.append('quality', document.getElementById('quality').value);
    formData.append('scale', document.getElementById('scale').value);

    try {
      const backendUrl = 'https://your-backend.onrender.com/convert.php'; // <-- Update this

      const response = await fetch(backendUrl, { method: 'POST', body: formData });

      if (!response.ok) {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
          const json = await response.json();
          throw new Error(json.error || 'Conversion failed');
        } else {
          throw new Error('Backend returned non-JSON response (404/HTML)');
        }
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'images.zip';
      a.click();
      window.URL.revokeObjectURL(url);
      status.textContent = 'Download started!';
    } catch (err) {
      status.textContent = 'Error: ' + err.message;
    }
  });
});
