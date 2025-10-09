<?php use Core\Helper\PreferencesHelper; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{{ get_language_properties('title') }}</title>
    <meta http-equiv="cache-control" content="max-age=0"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta http-equiv="expires" content="0"/>
    <meta http-equiv="imagetoolbar" content="no"/>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <meta name="content-language" content="{{ get_locale() }}"/>
    <link href="{{ get_gpack_link_and_hash('compact.css') }}"
          rel="stylesheet" type="text/css"/>
    <link href="{{ get_gpack_link_and_hash('lang.css') }}"
          rel="stylesheet" type="text/css"/>
    @if (is_lowres())
        <link href="{{ get_gpack_link_and_hash('compact-lowres.css') }}" rel="stylesheet" type="text/css"/>
    @endif
    <link href="{{ get_gpack_link_and_hash('fixes.css', false) }}?rev13" rel="stylesheet" type="text/css"/>

    <script type="text/javascript">
        window.ajaxToken = @json($vars['ajaxToken'] ?? null);
        window._player_uuid = @json($vars['_player_uuid'] ?? null);
    </script>
    <script type="text/javascript" src="js/default/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="js/default/jquery.md5.min.js"></script>
    <script type="text/javascript" src="js/default/jquery.scrollbar.min.js"></script>
    <script type="text/javascript" src="js/default/d3/d3.min.js"></script>
    <script type="text/javascript" src="js/default/d3/d3pie.min.js"></script>
    <script type="text/javascript" src="js/default/gsap/minified/TweenMax.min.js"></script>
    <script type="text/javascript" src="js/default/gsap/minified/plugins/MorphSVGPlugin.min.js"></script>
    <script type="text/javascript" src="js/Game/General/General.js"></script>
    @php
        $data = [
            'Map' => [
                'Size' => [
                    'width'  => (2 * MAP_SIZE) + 1,
                    'height' => (2 * MAP_SIZE) + 1,
                    'left'   => -MAP_SIZE,
                    'right'  => MAP_SIZE,
                    'bottom' => -MAP_SIZE,
                    'top'    => MAP_SIZE,
                ],
            ],
            'Season' => detect_season(),
        ];
    @endphp
    <script type="text/javascript">
        window.TravianDefaults = Object.assign(@json($data), false || {});
    </script>
    <script type="text/javascript" src="{{ get_crypt_js_link() }}"></script>
    <link rel="icon" href="favicon.ico" type="image/x-icon"/>
    <script type="text/javascript">
        @if (!isset($vars['autoReload']) || $vars['autoReload'] != 1)
        window.reload_enabled = false;
        @endif
        Travian.Translation.add({
            'allgemein.anleitung': '{{ T('Global', 'General.instructions') }}',
            'allgemein.cancel': '{{ T('Global', 'General.cancel') }}',
            'allgemein.ok': '{{ T('Global', 'General.ok') }}',
            'allgemein.close': '{{ T('Global', 'General.close') }}',
            'allgemein.close_with_capital_c': '{{ T('Global', 'General.close') }}',
            'cropfinder.keine_ergebnisse': '{{ T('Global', 'cropfinder.no results found') }}'
        });
    @php
        $buttonTemplate = <<<'HTML'
<button ><div class="button-container addHoverClick"><div class="button-background"><div class="buttonStart"><div class="buttonEnd"><div class="buttonMiddle"></div></div></div></div><div class="button-content"></div></div></button>
HTML;
        $eventJamHtml = '<a href="http://t4.answers.travian.ir/index.php?aid=249#go2answer" target="blank" title="پاسخ‌های تراوین"><span class="c0 t">0:00:0</span>?</a>';
        if (!isset($vars['autoReload']) || $vars['autoReload'] == 0) {
            $eventJamHtml = '<a href="javascript:void" onclick="document.location.reload();"><img src="img/refresh.png"></a>';
        }
        $preferences = PreferencesHelper::getPreferences();
        if (isset($preferences['allianceBonusesOverview'])) {
            $preferences['allianceBonusesOverview'] = json_encode($preferences['allianceBonusesOverview']);
        }
    @endphp
        Travian.applicationId = 'T4.4 Game';
        Travian.Game.version = '4.4';
        Travian.Game.worldId = '{{ getWorldId() }}';
        Travian.Game.speed = {{ getGameSpeed() }};
        Travian.Game.country = '{{ get_language_properties('country') }}';
        Travian.Templates = {};
        Travian.Templates.ButtonTemplate = @json($buttonTemplate);
        Travian.Game.eventJamHtml = @json($eventJamHtml);
        Travian.Form.UnloadHelper.message = '{{ T('Global', 'UnloadHelper.message') }}';
        Travian.alphabetizeTeletypesBrushesUnwarranted = function () {
            return {{ json_encode($vars['ajaxToken'] ?? null) }};
        };
        Travian.Game.Preferences.initialize(@json($preferences));
    </script>
    <!-- Default Statcounter code for Molon Lave
    https://molon-lave.net -->
    <script type="text/javascript">
        var sc_project=12377439;
        var sc_invisible=1;
        var sc_security="cd86d596";
    </script>
    <script type="text/javascript" src="https://www.statcounter.com/counter/counter.js" async></script>
    <noscript>
        <div class="statcounter">
            <a title="Web Analytics" href="https://statcounter.com/" target="_blank">
                <img class="statcounter" src="https://c.statcounter.com/12377439/0/cd86d596/1/" alt="Web Analytics">
            </a>
        </div>
    </noscript>
    <!-- End of Statcounter Code -->
</head>
