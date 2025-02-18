<?php
$apiKeyIp = getenv('API_KEY_IP');
$apiKeyTime = getenv('API_KEY_TIME');

$content = "<?php\n\n";
$content .= "\$ipKey = '$apiKeyIp';\n";
$content .= "\$timeKey = '$apiKeyTime';\n";

file_put_contents(__DIR__ . '/core/apiKeys.php', $content);
