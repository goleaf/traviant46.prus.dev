<?php
use Core\Config;
?>
@include('layout.head')
@php
    $vars = $vars ?? [];
    $bodyClasses = implode(' ', array_filter([
        'v35',
        'webkit',
        'chrome',
        get_locale(),
        Config::getProperty('settings', 'global_css_class'),
        $vars['contentCssClass'] ?? '',
        !empty($vars['colorBlind']) ? 'colorBlind' : '',
        $vars['bodyCssClass'] ?? '',
        getDirection() === 'RTL' ? 'rtl' : 'ltr',
        'season-' . detect_season(),
        'buildingsV1',
    ]));
@endphp
<body class="{{ $bodyClasses }}">
<div id="reactDialogWrapper"></div>
<div id="background">
    @if (!empty($vars['headerBar']))
        <div id="headerBar"></div>
    @endif
    <div id="bodyWrapper">
        <img style="filter:chroma();" src="img/x.gif" id="msfilter" alt=""/>

        <div id="header">
            <a id="logo" href="{{ Config::getInstance()->settings->indexUrl }}" target="_blank"
               title="{{ T('Global', 'Travian') }}"></a>
            @if (!empty($vars['showNavBar']))
                <ul id="navigation">
                    <li id="n1" class="villageResources">
                        <a class="{{ ($vars['bodyCssClass'] ?? '') === 'perspectiveBuildings' ? 'in' : '' }}active"
                           href="dorf1.php"
                           accesskey="1"
                           title="{{ T('inGame', 'Navigation.Resources') }}||"></a>
                    </li>
                    <li id="n2" class="villageBuildings">
                        <a class="{{ ($vars['bodyCssClass'] ?? '') === 'perspectiveResources' ? 'in' : '' }}active"
                           href="dorf2.php"
                           accesskey="2"
                           title="{{ T('inGame', 'Navigation.Buildings') }}||"></a>
                    </li>
                    <li id="n3" class="map">
                        <a href="karte.php" accesskey="3"
                           title="{{ T('inGame', 'Navigation.Map') }}||"></a>
                    </li>
                    <li id="n4" class="statistics">
                        <a href="statistiken.php" accesskey="4"
                           title="{{ T('inGame', 'Navigation.Statistics') }}||"></a>
                    </li>
                    <li id="n5" class="reports">
                        <a href="reports.php" accesskey="5"
                           title="{{ T('inGame', 'Navigation.Reports') }}||{{ T('inGame', 'Navigation.newReports') }}: {{ $vars['newReportsCount'] ?? 0 }}"></a>
                        @if (!empty($vars['newReportsCount']))
                            <div class="speechBubbleContainer ">
                                <div class="speechBubbleBackground">
                                    <div class="start">
                                        <div class="end">
                                            <div class="middle"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="speechBubbleContent">{{ ($vars['newReportsCount'] ?? 0) > 99 ? '+99' : ($vars['newReportsCount'] ?? 0) }}</div>
                            </div>
                            <div class="clear"></div>
                        @endif
                    </li>
                    <li id="n6" class="messages">
                        <a href="messages.php" accesskey="6"
                           title="{{ T('inGame', 'Navigation.Messages') }}||{{ T('inGame', 'Navigation.newMessages') }}: {{ $vars['newMessagesCount'] ?? 0 }}"></a>
                        @if (!empty($vars['newMessagesCount']))
                            <div class="speechBubbleContainer ">
                                <div class="speechBubbleBackground">
                                    <div class="start">
                                        <div class="end">
                                            <div class="middle"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="speechBubbleContent">{{ ($vars['newMessagesCount'] ?? 0) > 99 ? '+99' : ($vars['newMessagesCount'] ?? 0) }}</div>
                            </div>
                            <div class="clear"></div>
                        @endif
                    </li>
                    <li id="n7" class="gold">
                        <a href="#" accesskey="7"
                           title="{{ T('inGame', 'Navigation.Buy gold') }}"
                           onclick="jQuery(window).trigger('startPaymentWizard', {}); this.blur(); return false;"></a>
                    </li>
                    <li class="clear">&nbsp;</li>
                </ul>
                <div id="goldSilver">
                    <div class="gold">
                        <img src="img/x.gif" alt="{{ T('inGame', 'gold') }}"
                             title="{{ T('inGame', 'gold') }}"
                             class="gold"
                             onclick="jQuery(window).trigger('startPaymentWizard', {data:{activeTab: 'pros'}}); return false;"/>
                        <span class="ajaxReplaceableGoldAmount">
            @if (getCustom('serverIsFreeGold'))
                <b>{{ T('Global', 'Unlimited') }}</b>
            @else
                {{ $vars['goldCount'] ?? 0 }}
            @endif
        </span>
                    </div>
                    <div class="silver">
                        <img src="img/x.gif" alt="{{ T('inGame', 'silver') }}"
                             title="{{ T('inGame', 'silver') }}"
                             class="silver"
                             onclick="jQuery(window).trigger('startPaymentWizard', {data:{activeTab: 'pros'}}); return false;"/>
                        <span class="ajaxReplaceableSilverAmount">{{ $vars['silverCount'] ?? 0 }}</span>
                    </div>
                </div>
                <ul id="outOfGame" class="{{ getDirection() }}">
                    <li class="profile">
                        <a href="spieler.php"
                           title="{{ T('inGame', 'Profile.Profile') }}||{{ T('inGame', 'Profile.edit profile description') }}">
                            <img src="img/x.gif"
                                 alt="{{ T('inGame', 'Profile.Profile') }}"/>
                        </a>
                    </li>
                    <li class="options">
                        @if (empty($vars['isSitter']))
                            <a href="options.php"
                               title="{{ T('inGame', 'Options.Options') }}||{{ T('inGame', 'Options.edit account settings') }}">
                                <img src="img/x.gif"
                                     alt="{{ T('inGame', 'Options.Options') }}"/>
                            </a>
                        @else
                            <div class="a disabled"
                                 title="{{ T('inGame', 'Options.Options') }}||{{ '<span class="warning">' . T('inGame', 'Options.you may not edit settings of another account') . '</span>' }}">
                                <img src="img/x.gif"
                                     alt="{{ T('inGame', 'Options.Options') }}"/>
                            </div>
                        @endif
                    </li>
                    <li class="forum">
                        <a target="_blank" href="{{ getForumUrl() }}"
                           title="{{ T('inGame', 'Forum.Forum') }}||{{ T('inGame', 'Forum.Meet other players on our external forum') }}">
                            <img src="img/x.gif"
                                 alt="{{ T('inGame', 'Forum.Forum') }}"/>
                        </a>
                    </li>
                    <li class="help">
                        <a href="help.php"
                           title="{{ T('inGame', 'Help.Help') }}||{{ T('inGame', 'Help.Manuals, Answers and Support') }}">
                            <img src="img/x.gif" alt="{{ T('inGame', 'Help.Help') }}"/>
                        </a>
                    </li>
                    <li class="logout ">
                        <a href="logout.php"
                           title="{{ T('inGame', 'Logout.Logout') }}||{{ T('inGame', 'Logout.Log out from the game') }}">
                            <img src="img/x.gif"
                                 alt="{{ T('inGame', 'Logout.Logout') }}"/>
                        </a>
                    </li>
                    <li class="clear">&nbsp;</li>
                </ul>
                <script type="text/javascript">
                    jQuery('#outOfGame li.logout a').click(function () {
                        var windows = Travian.WindowManager.getWindows();
                        for (var i = 0; i < windows.length; i++) {
                            Travian.WindowManager.unregister(windows[i]);
                        }
                    });
                </script>
            @elseif (!empty($vars['headerBar']))
                <ul id="outOfGame" class="{{ getDirection() }}">
                    <li class="logout logoutOnly">
                        <a href="logout.php"
                           title="{{ T('inGame', 'Logout.Logout') }}||{{ T('inGame', 'Logout.Log out from the game') }}">
                            <img src="img/x.gif"
                                 alt="{{ T('inGame', 'Logout.Logout') }}"/>
                        </a>
                    </li>
                </ul>
            @endif
        </div>
        <div id="center">
            <div id="sidebarBeforeContent" class="sidebar beforeContent">
                {!! $vars['sidebarBeforeContent'] ?? '' !!}
                <div class="clear"></div>
            </div>
            <div id="contentOuterContainer" class="size1">
                @livewire('stock-bar', [
                    'showStockbar' => !empty($vars['showStockbar']) && !empty($vars['stockBar']),
                    'stockBar' => $vars['stockBar'] ?? [],
                    'bodyCssClass' => $vars['bodyCssClass'] ?? '',
                ])
                <div class="contentTitle">
                    @if (!empty($vars['showCloseButton']))
                        <a id="closeContentButton" class="contentTitleButton"
                           href="{{ ($vars['bodyCssClass'] ?? '') === 'perspectiveResources' ? 'dorf1.php' : 'dorf2.php' }}"
                           title="{{ T('Global', 'General.closeWindow') }}">
                            &nbsp;</a>
                    @endif
                    @if (!empty($vars['answerId']))
                        <a id="answersButton" class="contentTitleButton"
                           href="{{ getAnswersUrl() }}aid={{ $vars['answerId'] }}#go2answer"
                           target="_blank"
                           title="{{ T('Global', 'FAQ') }}">&nbsp;</a>
                    @endif
                </div>
                <div class="contentContainer">
                    <div id="content"
                         class="{{ $vars['contentCssClass'] ?? '' }}">
                        @if (!empty($vars['titleInHeader']))
                            <h1 class="titleInHeader">{!! $vars['titleInHeader'] !!}</h1>
                        @endif
                        {!! $vars['content'] ?? '' !!}
                        <div class="clear"></div>
                    </div>
                    <div class="clear">&nbsp;</div>
                </div>
                <div class="contentFooter"></div>
            </div>
            <div id="sidebarAfterContent" class="sidebar afterContent">
                {!! $vars['sidebarAfterContent'] ?? '' !!}
                <div class="clear"></div>
            </div>

            <div class="clear"></div>
        </div>
        <div id="footer">
            <!--email_off-->
            <div id="pageLinks">
                <a href="{{ Config::getInstance()->settings->indexUrl }}"
                   target="_blank">{{ T('Global', 'Footer.HomePage') }}</a>
                <a href="{{ getForumUrl() }}"
                   target="_blank">{{ T('Global', 'Footer.Forum') }}</a>
                <a href="{{ Config::getInstance()->settings->indexUrl }}links.php"
                   target="_blank">{{ T('Global', 'Footer.Links') }}</a>
                <a href="{{ getAnswersUrl() }}"
                   target="_blank">{{ T('Global', 'Footer.FAQ') }}</a>
                <a href="{{ Config::getInstance()->settings->indexUrl }}agb.php"
                   target="_blank">{{ T('Global', 'Footer.Terms') }}</a>
                <a href="{{ Config::getInstance()->settings->indexUrl }}impressum.php"
                   target="_blank">{{ T('Global', 'Footer.Imprint') }}</a>
                <div class="clear"></div>
            </div>
            <br/>
            <p class="copyright" style="direction:ltr;">© 2011 - {{ date('Y') }} Travian Games GmbH</p>
            @if (getDisplay('showCopyright'))
                <p class="copyright" style="direction:ltr;">
                    Developed By <a style="font-weight: bold; color: orange;" href="mailto:chamirhossein@gmail.com">Amirhossein</a>.
                </p>
                <div id="pageLinks">
                    <a href="/credits.php">{{ empty(T('Global', 'Footer.Credits')) ? 'Credits' : T('Global', 'Footer.Credits') }}</a>
                    <div class="clear"></div>
                </div>
            @endif
            <!--/email_off-->
            <br/>
        </div>
        @if (!empty($vars['headerBar']))
            @php
                if (!isset($vars['dateTime'])) {
                    $vars['dateTime'] = time();
                }
            @endphp
            <div id="servertime" class="stime">
                {{ T('inGame', 'serverTime') }}:&nbsp;
                {!! appendTimer($vars['dateTime'], 1) !!}
            </div>
        @endif
    </div>
    <div id="ce"></div>
</div>
<script type="text/javascript">
    @php
        $feature_flags = [
            'vacationMode' => true,
            'territory' => false,
            'heroitems' => true,
            'allianceBonus' => true,
            'boostedStart' => false,
            'pushingProtectionAlways' => false,
            'tribesEgyptiansAndHuns' => false,
            'hideFoolsArtifacts' => false,
            'welcomeScreen' => false,
        ];
    @endphp
    var T4_feature_flags = @json($feature_flags)
</script>
</body>
</html>
<!---- This page was generated in {{ round(1000 * (microtime(true) - ($GLOBALS['start_time'] ?? microtime(true))), 2) }} ms ---->
