// Word search generator (/tools/wordsearch): each line is "word | clue". Hides the words in a 15x15 grid (across or down, forwards only),
// fills the rest with random letters and draws the grid with the clue list on a canvas, which is also what gets exported to PNG.
const SIZE = 15;
const ATTEMPTS = 40;
const TRIES_PER_WORD = 200;
const CELL = 40;
const PADDING = 20;
const COLUMN_GAP = 64;
const LIST_WIDTH = 320;
const LINE_HEIGHT = 26;
const FONT = 'Lexend, sans-serif';
const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const DIRECTIONS = [[0, 1], [1, 0]];

const root = document.querySelector('[data-wordsearch]');

const shuffle = (items) => {
    for (let i = items.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [items[i], items[j]] = [items[j], items[i]];
    }

    return items;
};

const parseEntries = (text) => {
    const entries = new Map();

    for (const line of text.split('\n')) {
        const [rawWord, ...rawClue] = line.split('|');
        const word = rawWord.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().replace(/[^A-Z]/g, '');

        if (word.length >= 2 && !entries.has(word)) {
            entries.set(word, rawClue.join('|').trim());
        }
    }

    return [...entries].map(([word, clue], order) => ({ word, clue, order }));
};

const canPlace = (grid, word, row, col, dr, dc) => {
    for (let i = 0; i < word.length; i++) {
        const r = row + dr * i;
        const c = col + dc * i;

        if (r < 0 || r >= SIZE || c < 0 || c >= SIZE || (grid[r][c] && grid[r][c] !== word[i])) {
            return false;
        }
    }

    return true;
};

const build = (words) => {
    const grid = Array.from({ length: SIZE }, () => Array(SIZE).fill(''));
    const placed = [];
    const skipped = [];

    for (const entry of words) {
        const { word } = entry;
        let spot = null;

        for (let attempt = 0; attempt < TRIES_PER_WORD && !spot; attempt++) {
            const [dr, dc] = DIRECTIONS[Math.floor(Math.random() * DIRECTIONS.length)];
            const row = Math.floor(Math.random() * SIZE);
            const col = Math.floor(Math.random() * SIZE);

            if (canPlace(grid, word, row, col, dr, dc)) {
                spot = { row, col, dr, dc };
            }
        }

        if (!spot) {
            skipped.push(entry);

            continue;
        }

        for (let i = 0; i < word.length; i++) {
            grid[spot.row + spot.dr * i][spot.col + spot.dc * i] = word[i];
        }

        placed.push({ ...entry, ...spot });
    }

    return { grid, placed, skipped };
};

const generate = (entries) => {
    const usable = entries.filter((entry) => entry.word.length <= SIZE);
    const tooLong = entries.filter((entry) => entry.word.length > SIZE);
    let best = null;

    for (let attempt = 0; attempt < ATTEMPTS; attempt++) {
        const result = build([...usable].sort((a, b) => b.word.length - a.word.length));

        if (!best || result.placed.length > best.placed.length) {
            best = result;
        }

        if (best.placed.length === usable.length) {
            break;
        }
    }

    best ??= build([]);
    best.skipped = [...best.skipped, ...tooLong];

    // Letters that are not part of a word are filled in last so they never change the placement.
    const solution = best.grid.map((row) => [...row]);

    best.grid = best.grid.map((row) => row.map((letter) => letter || LETTERS[Math.floor(Math.random() * LETTERS.length)]));
    best.solution = solution;

    return best;
};

// Splits `text` into lines no wider than `maxWidth` for the current canvas font.
const wrap = (ctx, text, maxWidth) => {
    const lines = [];
    let line = '';

    for (const part of text.split(' ')) {
        const attempt = line ? `${line} ${part}` : part;

        if (line && ctx.measureText(attempt).width > maxWidth) {
            lines.push(line);
            line = part;
        } else {
            line = attempt;
        }
    }

    lines.push(line);

    return lines;
};

const draw = (canvas, { grid, placed }, showAnswers) => {
    const ratio = window.devicePixelRatio || 1;
    const ctx = canvas.getContext('2d');
    const gridSide = SIZE * CELL;
    const listX = PADDING + gridSide + COLUMN_GAP;
    const clues = [...placed].sort((a, b) => a.order - b.order).map((entry, index) => `${index + 1}. ${entry.clue || entry.word}`);

    ctx.font = `500 18px ${FONT}`;

    const clueLines = clues.map((clue) => wrap(ctx, clue, LIST_WIDTH));
    const listHeight = clueLines.reduce((sum, lines) => sum + lines.length * LINE_HEIGHT + 8, 0);
    const usedListWidth = Math.max(80, ...clueLines.flat().map((line) => ctx.measureText(line).width));
    const width = Math.ceil(listX + usedListWidth + PADDING);
    const contentHeight = Math.max(gridSide, listHeight);
    const height = PADDING * 2 + contentHeight;
    const gridY = PADDING;

    canvas.width = width * ratio;
    canvas.height = height * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, width, height);

    if (showAnswers) {
        ctx.fillStyle = 'rgba(255, 210, 63, 0.55)';

        for (const { word, row, col, dr, dc } of placed) {
            const x1 = PADDING + col * CELL + CELL / 2;
            const y1 = gridY + row * CELL + CELL / 2;
            const x2 = PADDING + (col + dc * (word.length - 1)) * CELL + CELL / 2;
            const y2 = gridY + (row + dr * (word.length - 1)) * CELL + CELL / 2;

            ctx.strokeStyle = 'rgba(255, 210, 63, 0.55)';
            ctx.lineWidth = CELL * 0.8;
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        }
    }

    ctx.strokeStyle = '#1B1F3B';
    ctx.lineWidth = 2;
    ctx.strokeRect(PADDING, gridY, gridSide, gridSide);

    ctx.fillStyle = '#1B1F3B';
    ctx.font = `600 22px ${FONT}`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    for (let row = 0; row < SIZE; row++) {
        for (let col = 0; col < SIZE; col++) {
            ctx.fillText(grid[row][col], PADDING + col * CELL + CELL / 2, gridY + row * CELL + CELL / 2 + 2);
        }
    }

    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    ctx.font = `500 18px ${FONT}`;

    let y = PADDING + 4;

    for (const lines of clueLines) {
        for (const line of lines) {
            ctx.fillText(line, listX, y);
            y += LINE_HEIGHT;
        }

        y += 8;
    }
};

if (root) {
    const textarea = root.querySelector('[data-wordsearch-words]');
    const canvas = root.querySelector('[data-wordsearch-canvas]');
    const answers = root.querySelector('[data-wordsearch-answers]');
    const warning = root.querySelector('[data-wordsearch-warning]');
    let result = generate([]);

    const render = () => draw(canvas, result, answers.checked);

    const regenerate = () => {
        result = generate(parseEntries(textarea.value));

        const skipped = result.skipped.map((entry) => entry.word.toLowerCase());

        warning.textContent = skipped.length ? `Không xếp được: ${skipped.join(', ')}` : '';
        warning.classList.toggle('hidden', skipped.length === 0);
        render();
    };

    root.querySelector('[data-wordsearch-generate]').addEventListener('click', regenerate);
    answers.addEventListener('change', render);

    root.querySelector('[data-wordsearch-export]').addEventListener('click', () => {
        const link = document.createElement('a');

        link.download = 'wordsearch.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    document.fonts?.ready.then(render);
    render();
}
