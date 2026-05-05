// ===== CREATION =====

function openCreationModal() {
    document.getElementById('creation-type-overlay').classList.add('active');
    const fab = document.getElementById('fabBtn');
    if (fab) fab.classList.add('active');
}

function closeCreationModal() {
    document.getElementById('creation-type-overlay').classList.remove('active');
    document.getElementById('creation-mode-overlay').classList.remove('active');
    document.getElementById('creation-source-overlay').classList.remove('active');
    document.getElementById('creation-import-overlay').classList.remove('active');
    const fab = document.getElementById('fabBtn');
    if (fab) fab.classList.remove('active');
    selectedCreationType = null;
}

function selectCreationType(type) {
    selectedCreationType = type;
    document.getElementById('creation-type-overlay').classList.remove('active');
    document.getElementById('creation-mode-overlay').classList.add('active');
}

function selectCreationMode(mode) {
    document.getElementById('creation-mode-overlay').classList.remove('active');
    const fab = document.getElementById('fabBtn');
    if (fab) fab.classList.remove('active');

    if (selectedCreationType) {
        if (mode === 'ai') {
            document.getElementById('creation-source-overlay').classList.add('active');
        } else {
            startCreation(selectedCreationType, mode);
        }
    }
}

function selectSource(source) {
    document.getElementById('creation-source-overlay').classList.remove('active');
    if (source === 'text') {
        startCreation(selectedCreationType, 'ai', 'text');
    } else if (source === 'import') {
        document.getElementById('creation-import-overlay').classList.add('active');
    }
}

function selectImport(type) {
    document.getElementById('creation-import-overlay').classList.remove('active');
    startCreation(selectedCreationType, 'ai', type);
}

function startCreation(type, mode, source = 'text') {
    if (mode === 'ai') {
        pendingCreationType = type;

        document.getElementById('wiz-topic').value = '';
        document.getElementById('wiz-content').value = '';
        document.getElementById('wiz-count').value = '10';
        const urlInput = document.getElementById('wiz-url-input');
        if (urlInput) urlInput.value = '';
        const pdfStatus = document.getElementById('pdf-status');
        if (pdfStatus) pdfStatus.textContent = '';
        const pdfUpload = document.getElementById('pdf-upload');
        if (pdfUpload) pdfUpload.value = '';

        const pdfGroup = document.getElementById('wiz-pdf-group');
        const urlGroup = document.getElementById('wiz-url-group');
        const contentArea = document.getElementById('wiz-content');

        if (pdfGroup) pdfGroup.style.display = 'none';
        if (urlGroup) urlGroup.style.display = 'none';

        if (source === 'pdf') {
            if (pdfGroup) pdfGroup.style.display = 'flex';
            contentArea.placeholder = "Le contenu extrait du PDF apparaîtra ici...";
        } else if (source === 'url') {
            if (urlGroup) urlGroup.style.display = 'flex';
            contentArea.placeholder = "Le contenu extrait de l'URL apparaîtra ici...";
        } else {
            contentArea.placeholder = "Collez vos notes ici pour plus de précision...";
        }

        const title = type === 'qcm' ? 'Nouveau QCM (IA)' : 'Nouvelles Flashcards (IA)';
        document.getElementById('wizard-title').textContent = title;
        document.getElementById('wizard-overlay').classList.add('active');
        document.getElementById('wiz-topic').focus();
    } else {
        manualCreationType = type;
        manualCards = [];
        document.getElementById('manual-deck-title').value = '';
        document.getElementById('manual-cards-container').innerHTML = '';

        const title = type === 'qcm' ? 'Nouveau QCM Manuel' : 'Nouvelles Flashcards Manuelles';
        document.getElementById('manual-wizard-title').textContent = title;
        document.getElementById('wizard-manual-overlay').classList.add('active');

        addManualCard();
    }
}

function closeWizard() {
    document.getElementById('wizard-overlay').classList.remove('active');
    pendingCreationType = null;
}

function closeManualWizard() {
    document.getElementById('wizard-manual-overlay').classList.remove('active');
    manualCreationType = null;
    manualCards = [];
}

function addManualCard() {
    const container = document.getElementById('manual-cards-container');
    const uniqueId = Date.now() + Math.floor(Math.random() * 1000);
    const displayIndex = container.children.length + 1;

    const cardDiv = document.createElement('div');
    cardDiv.className = 'form-group';
    cardDiv.id = `manual-card-${uniqueId}`;
    cardDiv.style.cssText = 'background: #F8F4FF; padding: 15px; border-radius: 12px; margin-bottom: 15px; position: relative;';

    if (manualCreationType === 'qcm') {
        cardDiv.innerHTML = `
            <button onclick="removeManualCard('${uniqueId}')" style="position: absolute; top: 10px; right: 10px; background: var(--danger); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 1rem;">×</button>
            <label style="font-weight: 600; margin-bottom: 8px; display: block;">Question <span class="q-num">${displayIndex}</span></label>
            <select class="form-control" onchange="toggleQCMType(this, '${uniqueId}')" style="margin-bottom: 10px; font-size: 0.85rem; padding: 8px;">
                <option value="standard">Choix Multiple (4)</option>
                <option value="boolean">Vrai / Faux</option>
            </select>
            <input type="text" class="form-control" id="manual-q-${uniqueId}" placeholder="Votre question" style="margin-bottom: 10px;">
            <div id="manual-opts-container-${uniqueId}">
                <label style="font-weight: 500; margin-bottom: 5px; display: block; font-size: 0.9rem;">Options (4 réponses)</label>
                <input type="text" class="form-control" id="manual-opt-${uniqueId}-0" placeholder="Option A" style="margin-bottom: 5px;">
                <input type="text" class="form-control" id="manual-opt-${uniqueId}-1" placeholder="Option B" style="margin-bottom: 5px;">
                <input type="text" class="form-control" id="manual-opt-${uniqueId}-2" placeholder="Option C" style="margin-bottom: 5px;">
                <input type="text" class="form-control" id="manual-opt-${uniqueId}-3" placeholder="Option D" style="margin-bottom: 10px;">
            </div>
            <label style="font-weight: 500; margin-bottom: 5px; display: block; font-size: 0.9rem;">Réponse correcte</label>
            <select class="form-control" id="manual-correct-${uniqueId}">
                <option value="0">Option A</option>
                <option value="1">Option B</option>
                <option value="2">Option C</option>
                <option value="3">Option D</option>
            </select>
        `;
    } else {
        cardDiv.innerHTML = `
            <button onclick="removeManualCard('${uniqueId}')" style="position: absolute; top: 10px; right: 10px; background: var(--danger); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 1rem;">×</button>
            <label style="font-weight: 600; margin-bottom: 8px; display: block;">Flashcard <span class="q-num">${displayIndex}</span></label>
            <input type="text" class="form-control" id="manual-front-${uniqueId}" placeholder="Recto (Question/Concept)" style="margin-bottom: 10px;">
            <textarea class="form-control" id="manual-back-${uniqueId}" placeholder="Verso (Réponse/Définition)" rows="3"></textarea>
        `;
    }

    container.appendChild(cardDiv);
}

function toggleQCMType(select, uniqueId) {
    const type = select.value;
    const optsContainer = document.getElementById(`manual-opts-container-${uniqueId}`);
    const correctSelect = document.getElementById(`manual-correct-${uniqueId}`);

    if (type === 'boolean') {
        optsContainer.style.display = 'none';
        correctSelect.innerHTML = `<option value="0">Vrai</option><option value="1">Faux</option>`;
    } else {
        optsContainer.style.display = 'block';
        correctSelect.innerHTML = `
            <option value="0">Option A</option>
            <option value="1">Option B</option>
            <option value="2">Option C</option>
            <option value="3">Option D</option>
        `;
    }
}

function removeManualCard(uniqueId) {
    const container = document.getElementById('manual-cards-container');
    if (container.children.length > 1) {
        const card = document.getElementById(`manual-card-${uniqueId}`);
        if (card) card.remove();
        Array.from(container.children).forEach((child, index) => {
            const numSpan = child.querySelector('.q-num');
            if (numSpan) numSpan.textContent = index + 1;
        });
    } else {
        alert('Vous devez avoir au moins une carte !');
    }
}

async function submitManualDeck() {
    const title = document.getElementById('manual-deck-title').value.trim();

    if (!title) {
        alert('Veuillez entrer un titre pour le deck');
        return;
    }

    const cards = [];

    if (manualCreationType === 'qcm') {
        const cardElements = document.getElementById('manual-cards-container').children;
        for (let i = 0; i < cardElements.length; i++) {
            const cardEl = cardElements[i];
            const questionInput = cardEl.querySelector('input[id^="manual-q-"]');
            const typeSelect = cardEl.querySelector('select[onchange^="toggleQCMType"]');
            const correctSelect = cardEl.querySelector('select[id^="manual-correct-"]');

            if (!questionInput) continue;

            const question = questionInput.value.trim();
            const correct = parseInt(correctSelect.value);
            let options = [];

            if (typeSelect && typeSelect.value === 'boolean') {
                options = ['Vrai', 'Faux'];
            } else {
                const optInputs = cardEl.querySelectorAll('input[id^="manual-opt-"]');
                options = Array.from(optInputs).map(input => input.value.trim());
                if (options.some(o => !o)) {
                    alert(`Veuillez remplir toutes les options de la question ${i + 1}`);
                    return;
                }
            }

            if (!question) {
                alert(`Veuillez remplir la question ${i + 1}`);
                return;
            }

            cards.push({ question, options, correct });
        }
    } else {
        const cardElements = document.getElementById('manual-cards-container').children;
        for (let i = 0; i < cardElements.length; i++) {
            const cardEl = cardElements[i];
            const frontInput = cardEl.querySelector('input[id^="manual-front-"]');
            const backInput = cardEl.querySelector('textarea[id^="manual-back-"]');

            if (!frontInput || !backInput) continue;

            const front = frontInput.value.trim();
            const back = backInput.value.trim();

            if (!front || !back) {
                alert(`Veuillez remplir les deux faces de la carte ${i + 1}`);
                return;
            }

            cards.push({ front, back });
        }
    }

    if (cards.length === 0) {
        alert('Ajoutez au moins une carte !');
        return;
    }

    const btn = document.getElementById('manual-submit');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>💾 Enregistrement...</span>';

    try {
        const newDeck = {
            id: Date.now(),
            title,
            type: manualCreationType,
            icon: manualCreationType === 'qcm' ? '📝' : '🎴',
            cards,
            progress: 0,
            created: new Date().toISOString(),
            author: userCode
        };

        await fetch(`${API_BACKEND}?action=add_deck`, {
            method: 'POST',
            body: JSON.stringify({ deck: newDeck, userCode })
        });

        closeManualWizard();
        alert('Deck créé avec succès !');
        window.location.reload();
    } catch (e) {
        console.error(e);
        alert('Erreur lors de la création du deck');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function submitWizard() {
    const topic = document.getElementById('wiz-topic').value.trim();
    let content = document.getElementById('wiz-content').value.trim();
    const count = document.getElementById('wiz-count').value;

    content = content.replace(/\[\.\.\.Texte tronqué : limite de sécurité atteinte\.\.\.\]/g, '').trim();

    if (!topic) {
        alert("Veuillez entrer un sujet.");
        return;
    }

    const btn = document.getElementById('wiz-submit');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ Génération...</span>';

    try {
        // CHUNKING LOGIC
        const CHUNK_SIZE = 50000;
        let chunks = [];

        if (content && content.length > CHUNK_SIZE) {
            for (let i = 0; i < content.length; i += CHUNK_SIZE) {
                chunks.push(content.substring(i, i + CHUNK_SIZE));
            }
        } else {
            chunks = [content];
        }

        const totalQuestions = parseInt(count);
        const questionsPerChunk = Math.ceil(totalQuestions / chunks.length);
        let allCards = [];

        const processChunk = async (chunkContent, index) => {
            let promptText = '';
            const context = chunkContent ? `Basé sur cet extrait (partie ${index + 1}/${chunks.length}) : "${chunkContent}".` : '';

            if (pendingCreationType === 'qcm') {
                promptText = `Génère un QCM de ${questionsPerChunk} questions sur "${topic}". ${context}
                Format de sortie STRICTEMENT JSON.
                Tu peux générer deux types de questions :
                1. QCM classique (4 choix) : {"question": "...", "options": ["A", "B", "C", "D"], "correct": 0}
                2. Vrai/Faux : {"question": "...", "options": ["Vrai", "Faux"], "correct": 0} (0 pour Vrai, 1 pour Faux)
                Mélange les types de questions de manière pertinente.
                Règles :
                - Pour QCM : 4 options obligatoires.
                - Pour Vrai/Faux : options fixes ["Vrai", "Faux"].
                - "correct" est toujours l'index de la bonne réponse dans le tableau "options".`;
            } else {
                promptText = `Génère ${questionsPerChunk} flashcards sur "${topic}". ${context}
                Format de sortie STRICTEMENT JSON :
                [
                    {"front": "Question/Concept", "back": "Réponse/Définition"}
                ]`;
            }

            const formData = new FormData();
            formData.append('prompt', sanitizeForWaf(promptText));

            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await response.json();

            // Handle server-side moderation block
            if (data.flagged) {
                const categories = (data.categories || []).join(', ');
                throw new Error(`Contenu inapproprié détecté (${categories}). Veuillez reformuler.`);
            }

            if (data.error) throw new Error(data.error);

            let jsonStr;

            if (data.response && data.response.output) {
                const messageOutput = data.response.output.find(item => item.type === 'message');
                if (messageOutput && messageOutput.content && messageOutput.content[0]) {
                    jsonStr = messageOutput.content[0].text;
                } else {
                    throw new Error("Format de réponse API invalide - pas de message trouvé");
                }
            } else if (data.response && typeof data.response === 'string') {
                const parsed = JSON.parse(data.response);
                jsonStr = parsed.choices[0].message.content;
            } else if (data.choices) {
                jsonStr = data.choices[0].message.content;
            } else {
                throw new Error("Format de réponse API invalide");
            }

            jsonStr = jsonStr.replace(/```json/g, '').replace(/```/g, '').trim();
            return JSON.parse(jsonStr);
        };

        const results = await Promise.all(chunks.map((chunk, index) => processChunk(chunk, index)));

        results.forEach(cards => {
            if (Array.isArray(cards)) allCards.push(...cards);
        });

        const finalCards = allCards.slice(0, totalQuestions);

        const newDeck = {
            id: Date.now(),
            title: topic,
            type: pendingCreationType,
            icon: pendingCreationType === 'qcm' ? '📝' : '🎴',
            cards: finalCards,
            progress: 0,
            created: new Date().toISOString(),
            author: userCode
        };

        await fetch(`${API_BACKEND}?action=add_deck`, {
            method: 'POST',
            body: JSON.stringify({ deck: newDeck, userCode })
        });

        closeWizard();
        alert('Deck créé avec succès !');
        window.location.reload();
    } catch (e) {
        console.error(e);
        alert(e.message || 'Erreur lors de la création. Réessaie.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
