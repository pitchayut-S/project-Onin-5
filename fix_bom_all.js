const fs = require('fs');
const path = require('path');

const dir = __dirname;
const phpFiles = fs.readdirSync(dir).filter(f => f.endsWith('.php'));

phpFiles.forEach(file => {
    const filePath = path.join(dir, file);
    const buf = fs.readFileSync(filePath);

    // Check for BOM (EF BB BF)
    let start = 0;
    if (buf[0] === 0xEF && buf[1] === 0xBB && buf[2] === 0xBF) {
        start = 3;
        console.log(`[BOM FOUND] ${file} - Removing BOM...`);
    } else {
        console.log(`[OK] ${file} - No BOM detected. First 3 bytes: ${buf[0].toString(16)} ${buf[1].toString(16)} ${buf[2].toString(16)}`);
    }

    // Strip BOM if found and rewrite
    if (start > 0) {
        const clean = buf.slice(start);
        fs.writeFileSync(filePath, clean);
        console.log(`  -> Fixed: ${file}`);
    }
});

console.log('\nDone! All PHP files checked.');
