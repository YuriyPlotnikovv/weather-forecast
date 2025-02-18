const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'core', 'apiKeys.php');
const content = `<?php
$ipKey = '${process.env.IP_KEY}';
$timeKey = '${process.env.TIME_KEY}';
`;

fs.writeFileSync(filePath, content, 'utf8');
console.log('apiKeys.php has been generated.');
