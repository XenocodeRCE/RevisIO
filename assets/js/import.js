// ===== IMPORT (PDF / URL) =====

async function handlePdfUpload(input) {
    const file = input.files[0];
    if (!file) return;

    const status = document.getElementById('pdf-status');
    const textarea = document.getElementById('wiz-content');

    if (file.type !== 'application/pdf') {
        alert('Veuillez sélectionner un fichier PDF.');
        return;
    }

    try {
        status.textContent = 'Lecture du PDF...';
        const arrayBuffer = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;

        let fullText = '';
        const totalPages = pdf.numPages;
        const MAX_CHARS = 1000000;

        for (let i = 1; i <= totalPages; i++) {
            status.textContent = `Extraction page ${i}/${totalPages}...`;
            const page = await pdf.getPage(i);
            const textContent = await page.getTextContent();
            const pageText = textContent.items.map(item => item.str).join(' ');
            fullText += pageText + '\n\n';

            if (fullText.length > MAX_CHARS) {
                fullText = fullText.substring(0, MAX_CHARS);
                fullText += '\n\n[...Texte tronqué : limite de sécurité atteinte...]';
                break;
            }
        }

        textarea.value = fullText.trim();
        status.textContent = 'PDF importé avec succès !';
        status.style.color = '#10B981';

        input.value = '';

        setTimeout(() => {
            status.textContent = '';
            status.style.color = '#666';
        }, 3000);
    } catch (error) {
        console.error('Erreur PDF:', error);
        status.textContent = 'Erreur lors de la lecture du PDF.';
        status.style.color = '#EF4444';
        alert('Impossible de lire ce fichier PDF.');
    }
}

async function handleUrlImport() {
    const urlInput = document.getElementById('wiz-url-input');
    const url = urlInput.value.trim();
    if (!url) {
        alert("Veuillez entrer une URL valide.");
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳...';

    try {
        const response = await fetch(`https://r.jina.ai/${url}`);
        if (!response.ok) throw new Error("Erreur lors de la récupération");
        const text = await response.text();

        const contentArea = document.getElementById('wiz-content');
        const limit = 100000;
        if (text.length > limit) {
            contentArea.value = text.substring(0, limit);
            alert("Le contenu a été tronqué car il est trop long.");
        } else {
            contentArea.value = text;
        }
    } catch (e) {
        console.error(e);
        alert("Impossible d'extraire le contenu de cette URL (CORS ou erreur réseau). Essayez de copier-coller le texte.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
