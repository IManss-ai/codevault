/**
 * CodeVault — Client-Side JavaScript
 * 
 * Handles clipboard copy, search filtering, mobile menu,
 * and other interactive features.
 */

// ── Copy to Clipboard ────────────────────
document.addEventListener('click', function(e) {
    const copyBtn = e.target.closest('.copy-btn');
    if (!copyBtn) return;

    const codeBlock = copyBtn.closest('.code-block');
    const code = codeBlock ? codeBlock.querySelector('code') : null;

    if (!code) return;

    navigator.clipboard.writeText(code.textContent).then(function() {
        const originalText = copyBtn.innerHTML;
        copyBtn.textContent = 'Copied!';
        copyBtn.classList.add('copied');
        setTimeout(function() {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('copied');
        }, 2000);
    }).catch(function() {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = code.textContent;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        copyBtn.textContent = 'Copied!';
        copyBtn.classList.add('copied');
        setTimeout(function() {
            copyBtn.textContent = 'Copy';
            copyBtn.classList.remove('copied');
        }, 2000);
    });
});

// ── Dashboard Search Filter ──────────────
const searchInput = document.getElementById('snippet-search');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const cards = document.querySelectorAll('.snippet-card');
        
        cards.forEach(function(card) {
            const title = (card.dataset.title || '').toLowerCase();
            const tags = (card.dataset.tags || '').toLowerCase();
            const language = (card.dataset.language || '').toLowerCase();
            
            const matches = title.includes(query) || 
                          tags.includes(query) || 
                          language.includes(query);
            
            card.style.display = matches ? '' : 'none';
        });
    });
}

// ── Code Editor Line & Character Counter ──
const codeTextarea = document.getElementById('code-editor');
const lineCounter = document.getElementById('line-count');
const charCounter = document.getElementById('char-count');

if (codeTextarea && lineCounter && charCounter) {
    function updateCounters() {
        const text = codeTextarea.value;
        const lines = text ? text.split('\n').length : 0;
        const chars = text.length;
        lineCounter.textContent = lines + ' line' + (lines !== 1 ? 's' : '');
        charCounter.textContent = chars + ' char' + (chars !== 1 ? 's' : '');
    }
    
    codeTextarea.addEventListener('input', updateCounters);
    updateCounters(); // Initialize on page load

    // Tab key support in code editor
    codeTextarea.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
            this.selectionStart = this.selectionEnd = start + 4;
            updateCounters();
        }
    });
}

// ── Confirm Delete ───────────────────────
document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('[data-confirm]');
    if (!deleteBtn) return;
    
    if (!confirm(deleteBtn.dataset.confirm)) {
        e.preventDefault();
    }
});

// ── Star Toggle (AJAX) ──────────────────
document.addEventListener('click', function(e) {
    const starBtn = e.target.closest('.star-btn');
    if (!starBtn || !starBtn.dataset.snippetId) return;

    e.preventDefault();

    const snippetId = starBtn.dataset.snippetId;
    const countEl = starBtn.querySelector('.star-count');

    fetch(window.BASE_URL + '/api/v1/snippets', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'toggle_star',
            snippet_id: snippetId
        }),
        credentials: 'same-origin'
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            starBtn.classList.toggle('starred', data.starred);
            // Update the star icon character (first text node in the button)
            const textNode = starBtn.firstChild;
            if (textNode && textNode.nodeType === Node.TEXT_NODE) {
                textNode.textContent = data.starred ? '★' : '☆';
            }
            if (countEl) countEl.textContent = data.count;
        }
    })
    .catch(function(err) {
        console.error('Star toggle failed:', err);
    });
});

// ── Set BASE_URL for JS use ──────────────
window.BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/codevault';
