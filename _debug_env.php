<?php
require __DIR__ . '/includes/init.php';
header('Content-Type: text/plain; charset=utf-8');

printf("FILE: %s\n", __FILE__);
printf("PWD:  %s\n\n", getcwd());

echo "SITE_KEY  (from \$_ENV):   " . ($_ENV['RECAPTCHA_SITE_KEY'] ?? '(no)') . "\n";
echo "SECRET    (from \$_ENV):   " . ($_ENV['RECAPTCHA_SECRET_KEY'] ?? '(no)') . "\n";
echo "MAILER_HOST (from \$_ENV): " . ($_ENV['MAILER_HOST'] ?? '(no)') . "\n";
echo "MAILER_USER (from \$_ENV): " . ($_ENV['MAILER_USER'] ?? '(no)') . "\n";
