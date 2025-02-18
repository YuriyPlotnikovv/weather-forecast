const fs = require('fs');
const path = require('path');

const apiKeyIp = process.env.API_KEY_IP;
const apiKeyTime = process.env.API_KEY_TIME;

if (!apiKeyIp || !apiKeyTime) {
  console.error('API keys are not set.');
  process.exit(1);
}

const content = `<?php\n\n$ipKey = '${apiKeyIp}';\n$timeKey = '${apiKeyTime}';\n`;

const filePath = path.join(__dirname, 'core', 'apiKeys.php');

fs.writeFileSync(filePath, content, 'utf8');
console.log('API keys have been generated.');
