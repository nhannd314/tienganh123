// PostToolUse hook: rebuild the Vite assets after Claude edits a file under resources/views, resources/css or resources/js.
const { execSync } = require('child_process');
const path = require('path');

let input = '';
process.stdin.on('data', (chunk) => (input += chunk));
process.stdin.on('end', () => {
    let filePath = '';
    try {
        const payload = JSON.parse(input);
        filePath = (payload.tool_input?.file_path || payload.tool_response?.filePath || '').replace(/\\/g, '/');
    } catch {
        process.exit(0);
    }

    if (!/\/resources\/(views|css|js)\//.test(filePath)) {
        process.exit(0);
    }

    try {
        execSync('npm run build', { cwd: path.resolve(__dirname, '..', '..'), stdio: 'pipe' });
    } catch (error) {
        process.stderr.write('npm run build failed:\n' + (error.stdout?.toString() || '') + (error.stderr?.toString() || ''));
        process.exit(2);
    }
});
