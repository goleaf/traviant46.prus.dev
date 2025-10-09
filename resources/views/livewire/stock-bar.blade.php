@if ($showStockbar)
    <ul id="stockBar">
        <li id="stockBarWarehouseWrapper" class="stock"
            title="{{ T('Buildings', '10.title') }}">
            <i class="warehouse"></i>
            <span class="value"
                  id="stockBarWarehouse" style="{{ getDisplay('smallResourcesFontSize') ? 'font-size: 9px;' : '' }}"
            >{{ number_format_x($stockBar['maxstore'] ?? 0, 9 * 1e6) }}</span>
        </li>
        @for ($resourceIndex = 0; $resourceIndex < 3; $resourceIndex++)
            <li id="stockBarResource{{ $resourceIndex + 1 }}" class="stockBarButton"
                title="{{ $stockBar['titles'][$resourceIndex] ?? '' }}">
                <div class="begin"></div>
                <div class="middle">
                    <i class="r{{ $resourceIndex + 1 }}"></i>
                    @if (!empty($stockBar['productionBoost'][$resourceIndex] ?? false))
                        <img src="img/x.gif" class="productionBoost"
                             alt="">
                    @endif
                    <span id="l{{ $resourceIndex + 1 }}"
                          class="value"
                          style="{{ getDisplay('smallResourcesFontSize') ? 'font-size: 9px;' : '' }}">{{ $stockBar['storageString'][$resourceIndex] ?? '' }}</span>

                    <div class="barBox">
                        <div id="lbar{{ $resourceIndex + 1 }}"
                             class="bar stock{{ $stockBar['storageClass'][$resourceIndex] ?? '' }}"
                             style="width:{{ $stockBar['percents'][$resourceIndex] ?? 0 }}%;"></div>
                    </div>
                    <a href="production.php?t={{ $resourceIndex + 1 }}"
                       title="{{ $stockBar['titles'][$resourceIndex] ?? '' }}"><img
                                src="img/x.gif"
                                alt=""/></a>
                </div>
                <div class="end"></div>
            </li>
        @endfor

        <li id="stockBarGranaryWrapper" class="stock"
            title="{{ T('Buildings', '11.title') }}">
            <i class="granary"></i>
            <span class="value" id="stockBarGranary" style="{{ getDisplay('smallResourcesFontSize') ? 'font-size: 9px;' : '' }}"
            >{{ number_format_x($stockBar['maxcrop'] ?? 0, 9 * 1e6) }}</span>
        </li>

        <li id="stockBarResource4" class="stockBarButton"
            title="{{ $stockBar['titles'][3] ?? '' }}">
            <div class="begin"></div>
            <div class="middle">
                <i class="r4"></i>
                @if (!empty($stockBar['productionBoost'][3] ?? false))
                    <img src="img/x.gif" class="productionBoost"
                         alt="">
                @endif
                <span id="l4"
                      class="value {{ ($stockBar['production'][3] ?? 0) < 0 ? 'alert' : '' }}"
                      style="{{ getDisplay('smallResourcesFontSize') ? 'font-size: 9px;' : '' }}">{{ $stockBar['storageString'][3] ?? '' }}</span>

                <div class="barBox">
                    <div id="lbar4"
                         class="bar stock{{ $stockBar['storageClass'][3] ?? '' }}"
                         style="width:{{ $stockBar['percents'][3] ?? 0 }}%;"></div>
                </div>
                <a href="production.php?t=4"
                   title="{{ $stockBar['titles'][3] ?? '' }}"><img
                            src="img/x.gif"
                            alt=""/></a>
            </div>
            <div class="end"></div>
        </li>

        <li id="stockBarFreeCropWrapper" class="stockBarButton r5"
            title="{{ $stockBar['titles'][4] ?? '' }}">
            <div class="begin"></div>
            <div class="middle">
                <i class="r5"></i>
                <span id="stockBarFreeCrop"
                      class="value"
                      style="{{ getDisplay('smallResourcesFontSize') ? 'font-size: 8px;' : '' }}">{{ $stockBar['production'][4] ?? 0 }}</span>
                <a href="production.php?t=5"
                   title="{{ $stockBar['titles'][4] ?? '' }}"><img
                            src="img/x.gif"
                            alt=""/></a>
            </div>
            <div class="end"></div>
        </li>
        <li class="clear">&nbsp;</li>
    </ul>

    <script type="text/javascript">
        var resources = {};

        resources.production = {
            "l1": {{ $stockBar['production'][0] ?? 0 }},
            "l2": {{ $stockBar['production'][1] ?? 0 }},
            "l3": {{ $stockBar['production'][2] ?? 0 }},
            "l4": {{ $stockBar['production'][3] ?? 0 }},
            "l5": {{ $stockBar['production'][4] ?? 0 }}
        };
        resources.storage = {
            "l1": {{ $stockBar['storage'][0] ?? 0 }},
            "l2": {{ $stockBar['storage'][1] ?? 0 }},
            "l3": {{ $stockBar['storage'][2] ?? 0 }},
            "l4": {{ $stockBar['storage'][3] ?? 0 }}
        };
        resources.maxStorage = {
            "l1": {{ $stockBar['maxstore'] ?? 0 }},
            "l2": {{ $stockBar['maxstore'] ?? 0 }},
            "l3": {{ $stockBar['maxstore'] ?? 0 }},
            "l4": {{ $stockBar['maxcrop'] ?? 0 }}
        };
    </script>
@endif
