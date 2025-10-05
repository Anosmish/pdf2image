document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('uploadForm');
    const pdfInput = document.getElementById('pdfFile');
    const status = document.getElementById('status');
    const convertBtn = document.getElementById('convertBtn');
    const btnText = convertBtn.querySelector('.btn-text');
    const btnLoading = convertBtn.querySelector('.btn-loading');
    
    // Update range value displays
    const qualityInput = document.getElementById('quality');
    const scaleInput = document.getElementById('scale');
    const qualityValue = document.getElementById('qualityValue');
    const scaleValue = document.getElementById('scaleValue');
    
    qualityInput.addEventListener('input', () => {
        qualityValue.textContent = `${qualityInput.value}%`;
    });
    
    scaleInput.addEventListener('input', () => {
        scaleValue.textContent = `${scaleInput.value}%`;
    });

    // File input feedback
    pdfInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        const fileLabel = document.querySelector('.file-label');
        
        if (file) {
            fileLabel.innerHTML = `
                <span class="file-label-text">✓ ${file.name}</span>
                <span class="file-label-hint">${(file.size / 1024 / 1024).toFixed(2)} MB • Click to change</span>
            `;
            fileLabel.style.borderColor = '#10b981';
            fileLabel.style.background = '#f0fdf4';
        }
    });

    // Drag and drop support
    const fileLabel = document.querySelector('.file-label');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileLabel.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        fileLabel.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        fileLabel.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        fileLabel.style.borderColor = '#0ea5a4';
        fileLabel.style.background = '#ecfeff';
    }
    
    function unhighlight() {
        fileLabel.style.borderColor = '#cbd5e1';
        fileLabel.style.background = '#fff';
    }
    
    fileLabel.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            const file = files[0];
            if (file.type === 'application/pdf') {
                pdfInput.files = files;
                pdfInput.dispatchEvent(new Event('change'));
            } else {
                showStatus('Please select a PDF file', 'error');
            }
        }
    }

    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const file = pdfInput.files[0];
        if (!file) {
            showStatus('Please select a PDF file first', 'error');
            return;
        }

        if (file.type !== 'application/pdf') {
            showStatus('Please select a valid PDF file', 'error');
            return;
        }

        // Check file size (20MB limit)
        if (file.size > 20 * 1024 * 1024) {
            showStatus('File too large. Maximum size is 20MB.', 'error');
            return;
        }

        setLoadingState(true);
        showStatus('Uploading and converting your PDF...', 'info');

        const formData = new FormData();
        formData.append('pdf', file);
        formData.append('format', document.getElementById('format').value);
        formData.append('quality', document.getElementById('quality').value);
        formData.append('scale', document.getElementById('scale').value);

        try {
            const backendUrl = 'https://pdf2image-gg8f.onrender.com/convert.php';
            
            const response = await fetch(backendUrl, {
                method: 'POST',
                body: formData,
                // Add timeout for mobile networks
                signal: AbortSignal.timeout(120000) // 2 minutes
            });

            const contentType = response.headers.get('content-type');
            
            if (!response.ok) {
                if (contentType && contentType.includes('application/json')) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || `Server error: ${response.status}`);
                } else {
                    const text = await response.text();
                    if (text.includes('error')) {
                        // Try to extract error message from HTML response
                        const match = text.match(/<b>.*?error.*?<\/b>[\s\S]*?([^<]+)/i);
                        throw new Error(match ? match[1].trim() : `Server returned ${response.status}`);
                    }
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                }
            }

            if (!contentType || !contentType.includes('application/zip')) {
                const text = await response.text();
                console.error('Non-ZIP response:', text.substring(0, 500));
                throw new Error('Server returned invalid response format');
            }

            const blob = await response.blob();
            
            if (blob.size === 0) {
                throw new Error('Received empty file from server');
            }

            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `pdf-images-${Date.now()}.zip`;
            document.body.appendChild(a);
            
            // Trigger download
            a.click();
            
            // Cleanup
            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 100);

            showStatus('✓ Download started! Your images are ready.', 'success');
            
        } catch (err) {
            console.error('Conversion error:', err);
            
            if (err.name === 'AbortError' || err.name === 'TimeoutError') {
                showStatus('Request timeout. Please try again with a smaller file or check your connection.', 'error');
            } else if (err.name === 'TypeError' && err.message.includes('fetch')) {
                showStatus('Network error. Please check your internet connection and try again.', 'error');
            } else {
                showStatus(`Error: ${err.message}`, 'error');
            }
        } finally {
            setLoadingState(false);
        }
    });

    function setLoadingState(loading) {
        if (loading) {
            convertBtn.disabled = true;
            convertBtn.classList.add('loading');
        } else {
            convertBtn.disabled = false;
            convertBtn.classList.remove('loading');
        }
    }

    function showStatus(message, type = 'info') {
        status.textContent = message;
        status.className = 'status';
        status.classList.add(type);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                status.textContent = '';
                status.className = 'status';
            }, 5000);
        }
    }

    // Mobile-specific optimizations
    if ('touchAction' in document.documentElement.style) {
        document.body.classList.add('touch-device');
    }

    // Prevent double-tap zoom on buttons
    convertBtn.style.touchAction = 'manipulation';
});
