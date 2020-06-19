<?php
    if (!file_exists('.active')) {
        header("HTTP/1.0 404 Not Found");
        die;
    }

    header('X-AJAX-Control: reset');
?>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="UTF-8">

    <!-- Keep the robots out -->
    <meta name="robots" content="noindex, nofollow">

    <!-- Set the base URL to account for virtual mod_rewrite URLs -->
    <base target="_blank" href="/myhordes">

    <!-- Set page title -->
    <title>MyHordes - System Maintenance</title>

    <style>
        body { background: black; font-family: sans-serif; }
        #header, #content, #footer { position: relative; margin: 0 auto; padding: 0; width: 950px; overflow: visible; }
        #header  { height: 140px; background: url(data:image/png;base64,<?=base64_encode( file_get_contents( 'deco_header.png' ) ) ?>) left top no-repeat; }
        #content { background: url(data:image/jpeg;base64,<?=base64_encode( file_get_contents( 'bg_content.jpg' ) ) ?>) left top repeat-y }
        #footer  { height: 14px; background: url(data:image/gif;base64,<?=base64_encode( file_get_contents( 'bg_content_footer.gif' ) ) ?>) left top no-repeat }

        #button { position: absolute; height: 46px; width: 137px; top: 51px; left: 409px; cursor: pointer;
            background: url(data:image/gif;base64,<?=base64_encode( file_get_contents( 'deco_jouerBt.gif' ) ) ?>) center no-repeat;
        }
        #sparks { position: absolute; height: 38px; width: 166px; top: 6px; left: 395px; cursor: pointer;
            background: url(data:image/gif;base64,<?=base64_encode( file_get_contents( 'electrik.gif' ) ) ?>) center no-repeat;
        }
        #button:hover { background: url(data:image/gif;base64,<?=base64_encode( file_get_contents( 'deco_jouerBt2.gif' ) ) ?>) center no-repeat; }
        #button>div {
            text-align: center; text-transform: uppercase; font-size: 19pt; padding-top: 5px; font-weight: bolder; color: #f0d79e;
            text-shadow: 0px 2px 0px #94361b, 0px -2px 0px #94361b, 2px 0px 0px #94361b, -2px 0px 0px #94361b, 2px 2px 0px #94361b, -2px -2px 0px #94361b, -2px 2px 0px #94361b;
        }
        #button:hover>div { padding-top: 8px; }

        #content-header, #content-content, #content-footer { position: relative; margin: 0 auto; padding: 0; width: 600px; overflow: hidden; }
        #content-header  { height: 18px; background: url(data:image/gif;base64,<?=base64_encode( file_get_contents( 'panel_header.gif' ) ) ?>) left top no-repeat; }
        #content-content { background: url(data:image/gif;base64,<?=base64_encode( file_get_contents( 'panel_bg.gif' ) ) ?>) left top repeat-y; }
        #content-footer  { height: 16px; background: url(data:image/gif;base64,<?=base64_encode( file_get_contents( 'panel_footer.gif' ) ) ?>) left top no-repeat }

        #content-content>div { padding: 2px 20px; }
        h1 { color: #f0d79e; border-bottom: 1px solid #f0d79e; font-size: 1.4em; }
        h1:not(:first-child) { margin: 45px 0 0; }
        p.text { text-align: justify; color: white; padding-left: 48px; }
        p.en { background: url(data:image/png;base64,<?=base64_encode( file_get_contents( 'en.png' ) ) ?>) left top no-repeat }
        p.fr { background: url(data:image/png;base64,<?=base64_encode( file_get_contents( 'fr.png' ) ) ?>) left top no-repeat }
        p.de { background: url(data:image/png;base64,<?=base64_encode( file_get_contents( 'de.png' ) ) ?>) left top no-repeat }
        p.es { background: url(data:image/png;base64,<?=base64_encode( file_get_contents( 'es.png' ) ) ?>) left top no-repeat }
    </style>
</head>
<body>
    <div id="header">
        <div id="sparks"></div>
        <div id="button" onclick="location.reload();"><div>Reload</div></div>
    </div>
    <div id="content">
        <div id="content-header"></div>
        <div id="content-content">
            <div>
                <h1>Maintenance Mode</h1>
                <p class="text en">
                    <i>MyHordes</i> is currently in <b>Maintenance Mode</b>. This usually happens when an update is installed. Please be patient for a moment, normal operation will surely continue shortly.
                    Reload this page or click the button at the top to try again.
                </p>

                <h1>Maintenance en cours</h1>
                <p class="text fr">
                    <i>MyHordes</i> est actuellement en <b>cours de maintenance</b>. Cela se produit généralement lorsqu'une mise à jour est installée. Veuillez patienter un instant, le fonctionnement normal reviendra sûrement sous peu.
                    Rechargez cette page ou cliquez sur le bouton en haut pour réessayer.
                </p>

                <h1>Wartungsmodus</h1>
                <p class="text de">
                    <i>MyHordes</i> befindet sich derzeit im <b>Wartungsmodus</b>. Dies geschieht üblicherweise, wenn ein Update installiert wird. Bitte habe einen Augenblick Geduld, es geht sicher gleich weiter.
                    Lade diese Seite neu oder klicke auf die Schaltfläche ganz oben, um es erneut zu versuchen.
                </p>

                <h1>Modo de Mantenimiento</h1>
                <p class="text es">
                    <i>MyHordes</i> está actualmente en <b>modo de mantenimiento</b>. Esto suele ocurrir cuando se instala una actualización. Por favor, tenga paciencia por un momento, el funcionamiento normal seguramente continuará en breve.
                    Recargue esta página o haga clic en el botón de la parte superior para intentarlo de nuevo.
                </p>
            </div>
        </div>
        <div id="content-footer"></div>

    </div>
    <div id="footer"></div>
</body>
</html>