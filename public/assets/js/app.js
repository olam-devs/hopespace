/**
 * Space of Hope - Main JavaScript
 */

// --- Base URL for API calls ---
const BASE_URL = document.querySelector('link[rel="stylesheet"]')?.href.replace('/assets/css/style.css', '') || '';

// --- Reaction State (localStorage) ---
function getReactionKey(msgId, type) {
    return 'soh_reaction_' + msgId + '_' + type;
}

function isReacted(msgId, type) {
    return localStorage.getItem(getReactionKey(msgId, type)) === '1';
}

function setReacted(msgId, type, active) {
    if (active) {
        localStorage.setItem(getReactionKey(msgId, type), '1');
    } else {
        localStorage.removeItem(getReactionKey(msgId, type));
    }
}

// --- Restore reaction states on page load ---
document.querySelectorAll('.reaction-btn').forEach(btn => {
    const msgId = btn.dataset.id;
    const type = btn.dataset.type;
    if (isReacted(msgId, type)) {
        btn.classList.add('reacted');
    }
});

// --- Toggle Reactions ---
document.querySelectorAll('.reaction-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const messageId = this.dataset.id;
        const type = this.dataset.type;
        const wasReacted = this.classList.contains('reacted');

        // Optimistic UI update
        this.classList.toggle('reacted');

        try {
            const res = await fetch(BASE_URL + '/react.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: parseInt(messageId), type: type })
            });

            const data = await res.json();
            if (data.success) {
                if (data.action === 'added') {
                    this.classList.add('reacted');
                    setReacted(messageId, type, true);
                } else if (data.action === 'removed') {
                    this.classList.remove('reacted');
                    setReacted(messageId, type, false);
                }
            } else {
                // Revert on failure
                if (wasReacted) {
                    this.classList.add('reacted');
                } else {
                    this.classList.remove('reacted');
                }
            }
        } catch (err) {
            // Revert on error
            if (wasReacted) {
                this.classList.add('reacted');
            } else {
                this.classList.remove('reacted');
            }
            console.error('Reaction failed:', err);
        }
    });
});

// --- Share on WhatsApp ---
function shareWhatsApp(id, btn) {
    const content = btn.dataset.content;
    const watermark = '\n\n— Hope Space | hopespace.olamtec.co.tz';
    const text = encodeURIComponent(content + watermark);
    window.open('https://wa.me/?text=' + text, '_blank');
}

// --- Copy Message ---
function copyMessage(id, btn) {
    const content = btn.dataset.content;
    const watermark = '\n\n— Hope Space | hopespace.olamtec.co.tz';
    const text = content + watermark;

    navigator.clipboard.writeText(text).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '&#10004; Copied!';
        btn.style.background = 'var(--success)';
        btn.style.color = 'white';
        btn.style.borderColor = 'var(--success)';
        setTimeout(() => {
            btn.innerHTML = original;
            btn.style.background = '';
            btn.style.color = '';
            btn.style.borderColor = '';
        }, 2000);
    }).catch(err => {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.innerHTML = '&#10004; Copied!';
        setTimeout(() => { btn.innerHTML = '&#128203; Copy'; }, 2000);
    });
}

// --- Character Counter for Submit Form ---
const formatSelect = document.getElementById('format');
const contentArea = document.getElementById('content');
const charCount = document.getElementById('charCount');

if (formatSelect && contentArea && charCount) {
    function updateCharLimit() {
        const format = formatSelect.value;
        const maxLen = format === 'quote' ? 200 : 600;
        contentArea.maxLength = maxLen;
        updateCharCount(maxLen);
    }

    function updateCharCount(maxLen) {
        if (!maxLen) {
            maxLen = formatSelect.value === 'quote' ? 200 : 600;
        }
        const remaining = maxLen - contentArea.value.length;
        const label = document.documentElement.lang === 'sw' ? 'herufi zimebaki' : 'characters remaining';
        charCount.textContent = remaining + ' ' + label;

        if (remaining < 20) {
            charCount.classList.add('warning');
        } else {
            charCount.classList.remove('warning');
        }
    }

    formatSelect.addEventListener('change', updateCharLimit);
    contentArea.addEventListener('input', () => updateCharCount());
    updateCharLimit();
}
