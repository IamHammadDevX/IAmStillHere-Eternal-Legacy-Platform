const PHOOLWALA_URL = 'https://www.phoolwala.com';

document.addEventListener('DOMContentLoaded', () => {
    const link = document.getElementById('phoolwala-gift-link');
    if (!link) return;
    link.href = PHOOLWALA_URL;
    link.target = '_blank';
    link.rel = 'noopener noreferrer external';
});
