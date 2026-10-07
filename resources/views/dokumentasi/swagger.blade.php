<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        Dokumentasi API — {{ config('pos.nama_toko') }}
    </title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.32.15/swagger-ui.css"
    >
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.32.15/swagger-ui-bundle.js"></script>

    <script>
        window.addEventListener('load', function () {
            window.ui = SwaggerUIBundle({
                url: @json(route('docs.spesifikasi')),
                dom_id: '#swagger-ui',
                deepLinking: true,
                docExpansion: 'list',
                filter: true,
                displayRequestDuration: true,
                persistAuthorization: false,
            });
        });
    </script>
</body>
</html>