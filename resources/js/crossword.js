// Crossword generator (/tools/crossword): each line is "word | clue". The words are placed on a working grid, which is then cropped to the
// smallest box around them, and drawn together with the numbered clues on a canvas that is also what gets exported to PNG.
const MAX_WORD_LENGTH = 30;
const ATTEMPTS = 80;
const CELL = 44;
const PADDING = 20;
const CLUE_WIDTH = 320;
const COLUMN_GAP = 64;
const MIN_WIDTH = 480;
const FONT = 'Lexend, sans-serif';

const root = document.querySelector('[data-crossword]');

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
        const word = rawWord.normalize('NFD').replace(/[̀-ͯ]/g, '').toUpperCase().replace(/[^A-Z]/g, '');

        if (word.length >= 2 && !entries.has(word)) {
            entries.set(word, rawClue.join('|').trim());
        }
    }

    return [...entries].map(([word, clue]) => ({ word, clue }));
};

// Returns the number of crossings when `word` fits at the position, or -1 when it does not.
const fit = (grid, size, word, row, col, across) => {
    const dr = across ? 0 : 1;
    const dc = across ? 1 : 0;
    const at = (r, c) => (r >= 0 && r < size && c >= 0 && c < size ? grid[r][c] : '');

    if (row + dr * (word.length - 1) >= size || col + dc * (word.length - 1) >= size) {
        return -1;
    }

    if (at(row - dr, col - dc) || at(row + dr * word.length, col + dc * word.length)) {
        return -1;
    }

    let crossings = 0;

    for (let i = 0; i < word.length; i++) {
        const r = row + dr * i;
        const c = col + dc * i;

        if (grid[r][c]) {
            if (grid[r][c] !== word[i]) {
                return -1;
            }

            crossings++;
        } else if (at(r + dc, c + dr) || at(r - dc, c - dr)) {
            return -1;
        }
    }

    return crossings;
};

const boundsWith = (bounds, word, row, col, across) => ({
    top: Math.min(bounds.top, row),
    left: Math.min(bounds.left, col),
    bottom: Math.max(bounds.bottom, row + (across ? 0 : word.length - 1)),
    right: Math.max(bounds.right, col + (across ? word.length - 1 : 0)),
});

const areaOf = (bounds) => (bounds.bottom - bounds.top + 1) * (bounds.right - bounds.left + 1);

const build = (entries, size) => {
    const grid = Array.from({ length: size }, () => Array(size).fill(''));
    const placed = [];
    const skipped = [];
    let bounds = null;

    for (const entry of entries) {
        const { word } = entry;
        let options = [];

        if (!bounds) {
            const across = Math.random() < 0.5;
            const row = across ? Math.floor(size / 2) : Math.floor((size - word.length) / 2);
            const col = across ? Math.floor((size - word.length) / 2) : Math.floor(size / 2);

            options = [{ row, col, across, score: 0, area: 0 }];
        } else {
            for (let row = 0; row < size; row++) {
                for (let col = 0; col < size; col++) {
                    for (const across of [true, false]) {
                        const score = fit(grid, size, word, row, col, across);

                        if (score > 0) {
                            options.push({ row, col, across, score, area: areaOf(boundsWith(bounds, word, row, col, across)) });
                        }
                    }
                }
            }
        }

        if (options.length === 0) {
            skipped.push(entry);

            continue;
        }

        const bestScore = Math.max(...options.map((option) => option.score));
        const top = options.filter((option) => option.score === bestScore);
        const smallest = Math.min(...top.map((option) => option.area));
        const pick = shuffle(top.filter((option) => option.area === smallest))[0];

        for (let i = 0; i < word.length; i++) {
            grid[pick.row + (pick.across ? 0 : i)][pick.col + (pick.across ? i : 0)] = word[i];
        }

        bounds = boundsWith(bounds ?? { top: pick.row, left: pick.col, bottom: pick.row, right: pick.col }, word, pick.row, pick.col, pick.across);
        placed.push({ ...entry, row: pick.row, col: pick.col, across: pick.across });
    }

    return { grid, placed, skipped, bounds };
};

const crop = ({ grid, placed, skipped, bounds }) => {
    if (!bounds) {
        return { cells: [], placed: [], skipped };
    }

    const cells = [];

    for (let row = bounds.top; row <= bounds.bottom; row++) {
        cells.push(grid[row].slice(bounds.left, bounds.right + 1));
    }

    return {
        cells,
        placed: placed.map((entry) => ({ ...entry, row: entry.row - bounds.top, col: entry.col - bounds.left })),
        skipped,
    };
};

const generate = (entries) => {
    const usable = entries.filter((entry) => entry.word.length <= MAX_WORD_LENGTH);
    const tooLong = entries.filter((entry) => entry.word.length > MAX_WORD_LENGTH);
    const longest = Math.max(0, ...usable.map((entry) => entry.word.length));
    const size = Math.max(longest, 8) * 2 + 1;
    let best = null;
    let bestArea = Infinity;

    for (let attempt = 0; attempt < ATTEMPTS && usable.length > 0; attempt++) {
        const ordered = attempt === 0 ? [...usable].sort((a, b) => b.word.length - a.word.length) : shuffle([...usable]).sort((a, b) => b.word.length - a.word.length + (Math.random() - 0.5) * 4);
        const result = build(ordered, size);
        const area = result.bounds ? areaOf(result.bounds) : 0;

        if (!best || result.placed.length > best.placed.length || (result.placed.length === best.placed.length && area < bestArea)) {
            best = result;
            bestArea = area;
        }
    }

    const cropped = crop(best ?? { grid: [], placed: [], skipped: [], bounds: null });
    cropped.skipped = [...cropped.skipped, ...tooLong];

    return cropped;
};

const numberStarts = (placed) => {
    const starts = new Map();

    [...placed]
        .sort((a, b) => a.row - b.row || a.col - b.col)
        .forEach((entry) => {
            const key = `${entry.row},${entry.col}`;

            if (!starts.has(key)) {
                starts.set(key, starts.size + 1);
            }
        });

    return starts;
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

const draw = (canvas, { cells, placed }, showAnswers) => {
    const ratio = window.devicePixelRatio || 1;
    const ctx = canvas.getContext('2d');
    const starts = numberStarts(placed);
    const rows = cells.length;
    const cols = rows ? cells[0].length : 0;
    const gridX = PADDING;
    const clueX = PADDING + cols * CELL + COLUMN_GAP;
    const textWidth = CLUE_WIDTH;
    const lineHeight = 24;

    const groups = [
        ['NGANG', placed.filter((entry) => entry.across)],
        ['DỌC', placed.filter((entry) => !entry.across)],
    ]
        .map(([title, entries]) => [title, entries.sort((a, b) => starts.get(`${a.row},${a.col}`) - starts.get(`${b.row},${b.col}`))])
        .filter(([, entries]) => entries.length > 0);

    ctx.font = `400 16px ${FONT}`;

    const clueLines = groups.map(([title, entries]) => ({
        title,
        items: entries.map((entry) => wrap(ctx, `${starts.get(`${entry.row},${entry.col}`)}. ${entry.clue || '—'}`, textWidth)),
    }));
    const clueHeight = clueLines.reduce((sum, group) => sum + lineHeight * 1.6 + group.items.reduce((total, lines) => total + lines.length * lineHeight + 4, 0), 0);
    const usedClueWidth = Math.max(80, ...clueLines.flatMap((group) => group.items.flat()).map((line) => ctx.measureText(line).width));
    const width = rows ? Math.ceil(clueX + usedClueWidth + PADDING) : MIN_WIDTH;
    const contentHeight = Math.max(rows * CELL, clueHeight);
    const height = Math.max(200, PADDING * 2 + contentHeight);
    const gridY = PADDING;

    canvas.width = width * ratio;
    canvas.height = height * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, width, height);

    if (!rows) {
        ctx.fillStyle = '#5A5F7A';
        ctx.font = `500 18px ${FONT}`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('Nhập danh sách từ rồi bấm Regenerate', width / 2, height / 2);

        return;
    }

    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const letter = cells[row][col];

            if (!letter) {
                continue;
            }

            const x = gridX + col * CELL;
            const y = gridY + row * CELL;
            const number = starts.get(`${row},${col}`);

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(x, y, CELL, CELL);
            ctx.strokeStyle = '#1B1F3B';
            ctx.lineWidth = 2;
            ctx.strokeRect(x, y, CELL, CELL);

            if (number) {
                ctx.fillStyle = '#5A5F7A';
                ctx.font = `600 12px ${FONT}`;
                ctx.textAlign = 'left';
                ctx.textBaseline = 'top';
                ctx.fillText(String(number), x + 4, y + 3);
            }

            if (showAnswers) {
                ctx.fillStyle = '#0F62E0';
                ctx.font = `700 24px ${FONT}`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(letter, x + CELL / 2, y + CELL / 2 + 3);
            }
        }
    }

    let y = PADDING;

    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';

    for (const group of clueLines) {
        ctx.fillStyle = '#1B1F3B';
        ctx.font = `700 16px ${FONT}`;
        ctx.fillText(group.title, clueX, y);
        y += lineHeight * 1.6;

        ctx.font = `400 16px ${FONT}`;

        for (const lines of group.items) {
            for (const line of lines) {
                ctx.fillText(line, clueX, y);
                y += lineHeight;
            }

            y += 4;
        }
    }
};

if (root) {
    const textarea = root.querySelector('[data-crossword-words]');
    const canvas = root.querySelector('[data-crossword-canvas]');
    const answers = root.querySelector('[data-crossword-answers]');
    const warning = root.querySelector('[data-crossword-warning]');
    let result = { cells: [], placed: [], skipped: [] };

    const render = () => draw(canvas, result, answers.checked);

    const regenerate = () => {
        result = generate(parseEntries(textarea.value));

        const skipped = result.skipped.map((entry) => entry.word.toLowerCase());

        warning.textContent = skipped.length ? `Không xếp được: ${skipped.join(', ')}` : '';
        warning.classList.toggle('hidden', skipped.length === 0);
        render();
    };

    root.querySelector('[data-crossword-generate]').addEventListener('click', regenerate);
    answers.addEventListener('change', render);

    root.querySelector('[data-crossword-export]').addEventListener('click', () => {
        const link = document.createElement('a');

        link.download = 'crossword.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    document.fonts?.ready.then(render);
    render();
}
