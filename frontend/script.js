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
      // Replace with your Render backend URL
      const backendUrl = 'https://pdf2image-gg8f.onrender.com/convert.php';

      const response = await fetch(backendUrl, {
        method: 'POST',
        body: formData
      });

      if (!response.ok) throw new Error('Conversion failed');

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
