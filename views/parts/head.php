<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php if (isset($pageDescription)): ?>
        <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <?php endif; ?>
    <?php if (isset($pageCanonical)): ?>
        <link rel="canonical" href="<?php echo $baseUrl . $pageCanonical; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-L5QV4WDDL0"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-L5QV4WDDL0');
    </script>
    <link rel="manifest" href="<?php echo $baseUrl; ?>/manifest.json">
    <meta name="theme-color" content="#008000">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo $baseUrl; ?>/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</head>

<body>