const fs = require('fs');
const glob = require('glob');

const files = fs.readdirSync('.').filter(f => f.endsWith('.php'));
let changed = [];
for (let f of files) {
    let content = fs.readFileSync(f);
    let originalLen = content.length;

    // Check for BOM (EF BB BF)
    if (content[0] === 0xef && content[1] === 0xbb && content[2] === 0xbf) {
        content = content.slice(3);
    }

    let text = content.toString('utf8');
    // Ensure file starts exactly with <?php
    let match = text.match(/^\s*(<\?php)/i);
    if (match) {
        text = text.trimStart();
    }

    // Write back if changed
    let newBuffer = Buffer.from(text, 'utf8');
    if (newBuffer.length !== originalLen || content.length !== originalLen) {
        fs.writeFileSync(f, newBuffer);
        changed.push(f);
    }
}
fs.writeFileSync('node_bom_log.txt', changed.join(', ') || 'No files changed');
