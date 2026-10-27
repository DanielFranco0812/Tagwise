@extends('layouts/default')


@section('title0')

    @php
        $requestStatusType = request()->input('status_type');
        $requestOrderNumber = request()->input('order_number');
        $requestCompanyId = request()->input('company_id');
        $requestStatusTypeId = request()->input('status_id');
    @endphp


    @if (($requestCompanyId) && ($company))
        {{ $company->name }}
    @endif


    @if ($requestStatusType)

        @if ($requestStatusType == 'Pending')

            {{ trans('general.pending') }}

        @elseif ($requestStatusType == 'RTD')

            {{ trans('general.ready_to_deploy') }}

        @elseif ($requestStatusType == 'Deployed')

            {{ trans('general.deployed') }}

        @elseif ($requestStatusType == 'Undeployable')

            {{ trans('general.undeployable') }}

        @elseif ($requestStatusType == 'Deployable')

            {{ trans('general.deployed') }}

        @elseif ($requestStatusType == 'Requestable')

            {{ trans('admin/hardware/general.requestable') }}

        @elseif ($requestStatusType == 'Archived')

            {{ trans('general.archived') }}

        @elseif ($requestStatusType == 'Deleted')

            {{ ucfirst(trans('general.deleted')) }}

        @elseif ($requestStatusType == 'byod')

            {{ strtoupper(trans('general.byod')) }}

        @endif

    @else

        {{ trans('general.all') }}

    @endif


    {{ trans('general.assets') }}


    @if (Request::has('order_number'))

        : Order #{{ strval($requestOrderNumber) }}

    @endif

@stop



@section('title')

    @yield('title0') @parent

@stop



@section('content')

    <x-container>

        <x-box name="assets">


            {{-- ============================================================
                 BULK RFID SCANNER
            ============================================================ --}}

            <section
                class="bulk-rfid-panel"
                aria-labelledby="bulk-rfid-heading"
            >
                <h2 id="bulk-rfid-heading">Bulk RFID scan</h2>
                <p class="help-block" id="bulk-rfid-help">
                    Focus the field and scan RFID tags. Matching items will appear in the asset list below.
                    Clear the scan to restore the full list.
                </p>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="bulk_rfid_scan">Bulk RFID scan</label>
                            <input
                                type="text"
                                id="bulk_rfid_scan"
                                class="form-control"
                                autocomplete="off"
                                placeholder="Click here, then start bulk scanning RFID tags"
                                aria-describedby="bulk-rfid-help"
                            >

                            <div
                                id="bulk_rfid_status"
                                role="status"
                                aria-live="polite"
                                aria-atomic="true"
                                style="margin-top: 8px; font-weight: bold;"
                            >
                                Ready for bulk scanning
                            </div>

                            <div id="bulk_rfid_count" aria-live="polite" style="margin-top: 4px; color: #666;">
                                0 unique RFID ID(s) scanned
                            </div>

                            <div id="bulk_match_count" aria-live="polite" style="margin-top: 4px; color: #666;">
                                0 matching item(s)
                            </div>

                            <div
                                id="bulk_rfid_tags"
                                aria-label="Scanned RFID tags"
                                style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 6px;"
                            >
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4" style="padding-top: 25px;">
                        <button
                            type="button"
                            id="clear_bulk_rfid"
                            class="btn btn-danger"
                        >
                            Clear bulk scan
                        </button>
                    </div>
                </div>
            </section>



            {{-- ============================================================
                 EXISTING ASSET TABLE
            ============================================================ --}}

            <x-table.assets
                :route="route(
                    'api.assets.index',
                    array(
                        'status_type' => e($requestStatusType),
                        'order_number' => e(strval($requestOrderNumber)),
                        'company_id' => e($requestCompanyId),
                        'status_id' => e($requestStatusTypeId)
                    )
                )"
            />


        </x-box>

    </x-container>

@stop



@section('moar_scripts')



{{-- ================================================================
     ASSET TAG SHORT FORMATTER
================================================================ --}}

<script>

function assetTagShortFormatter(value, row, index) {

    if (
        value === null ||
        value === undefined ||
        value === ''
    ) {

        return '';

    }


    const fullValue =
        String(value);


    let shortValue =
        fullValue;


    if (fullValue.length > 20) {

        shortValue =
            fullValue.substring(
                0,
                20
            )
            +
            '...';

    }


    /*
    |--------------------------------------------------------------------------
    | KEEP ORIGINAL SNIPE-IT LINK
    |--------------------------------------------------------------------------
    */

    if (
        typeof hardwareLinkFormatter === 'function'
    ) {

        try {

            const originalHtml =
                hardwareLinkFormatter(
                    value,
                    row,
                    index
                );


            const wrapper =
                document.createElement(
                    'div'
                );


            wrapper.innerHTML =
                originalHtml;


            const link =
                wrapper.querySelector(
                    'a'
                );


            if (link) {

                link.textContent =
                    shortValue;


                link.setAttribute(
                    'title',
                    fullValue
                );


                return wrapper.innerHTML;

            }


            if (
                fullValue.length <= 20
            ) {

                return originalHtml;

            }

        }
        catch (error) {

            console.error(
                'assetTagShortFormatter error:',
                error
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | FALLBACK
    |--------------------------------------------------------------------------
    */

    const span =
        document.createElement(
            'span'
        );


    span.textContent =
        shortValue;


    span.setAttribute(
        'title',
        fullValue
    );


    return span.outerHTML;

}

</script>



{{-- ================================================================
     ORIGINAL SNIPE-IT BOOTSTRAP TABLE
================================================================ --}}

@include('partials.bootstrap-table')



{{-- ================================================================
     BULK RFID SCANNER
================================================================ --}}

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const scanInput =
            document.getElementById(
                "bulk_rfid_scan"
            );


        const statusMessage =
            document.getElementById(
                "bulk_rfid_status"
            );


        const countMessage =
            document.getElementById(
                "bulk_rfid_count"
            );


        const matchMessage =
            document.getElementById(
                "bulk_match_count"
            );


        const tagsContainer =
            document.getElementById(
                "bulk_rfid_tags"
            );


        const clearButton =
            document.getElementById(
                "clear_bulk_rfid"
            );



        /*
        |--------------------------------------------------------------------------
        | CHECK REQUIRED ELEMENTS
        |--------------------------------------------------------------------------
        */

        if (
            !scanInput ||
            !statusMessage ||
            !countMessage ||
            !matchMessage ||
            !tagsContainer ||
            !clearButton
        ) {

            console.error(
                "Bulk RFID ERROR: Required elements not found."
            );

            return;

        }



        /*
        |--------------------------------------------------------------------------
        | FIND EXISTING TABLE
        |--------------------------------------------------------------------------
        */

        let $assetTable =
            $("#assetsListingTable");


        if (!$assetTable.length) {

            $assetTable =
                $('table[data-toggle="table"]')
                    .first();

        }


        if (!$assetTable.length) {

            console.error(
                "Bulk RFID ERROR: Asset table not found."
            );

            return;

        }



        /*
        |--------------------------------------------------------------------------
        | VARIABLES
        |--------------------------------------------------------------------------
        */


        /*
         * All RFID IDs scanned during
         * the current bulk scan.
         */

        let scannedRFIDs =
            new Set();


        /*
         * Original unfiltered table data.
         */

        let originalTableData =
            [];


        /*
         * Prevent original data from being
         * replaced by filtered data.
         */

        let originalDataCaptured =
            false;


        /*
         * Stores how many assets contain
         * each RFID ID.
         *
         * Example:
         *
         * RFID-A = 5 assets
         * RFID-B = 5 assets
         * RFID-C = 5 assets
         * RFID-D = 1 asset
         *
         * RFID-D is UNIQUE.
         */

        let rfidFrequency =
            new Map();



        /*
        |--------------------------------------------------------------------------
        | CLEAN RFID
        |--------------------------------------------------------------------------
        */

        function cleanRFID(value) {

            return String(
                value || ""
            )
                .trim()
                .toUpperCase();

        }



        /*
        |--------------------------------------------------------------------------
        | RFID BADGES
        |--------------------------------------------------------------------------
        */

        function updateRFIDTags() {


            tagsContainer.innerHTML =
                "";


            Array.from(
                scannedRFIDs
            ).forEach(
                function (id, index) {


                    const tag =
                        document.createElement(
                            "span"
                        );


                    tag.style.display =
                        "inline-block";


                    tag.style.padding =
                        "6px 10px";


                    tag.style.margin =
                        "2px";


                    tag.style.background =
                        "#3c8dbc";


                    tag.style.color =
                        "#ffffff";


                    tag.style.borderRadius =
                        "4px";


                    tag.style.fontSize =
                        "12px";


                    tag.style.fontFamily =
                        "monospace";


                    tag.style.wordBreak =
                        "break-all";


                    tag.textContent =
                        (index + 1)
                        +
                        ". "
                        +
                        id;


                    tag.setAttribute(
                        "title",
                        id
                    );


                    tagsContainer.appendChild(
                        tag
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | UPDATE RFID COUNT
        |--------------------------------------------------------------------------
        */

        function updateRFIDCount() {

            countMessage.textContent =
                scannedRFIDs.size
                +
                " unique RFID ID(s) scanned";

        }



        /*
        |--------------------------------------------------------------------------
        | GET RFID IDs FROM ASSET TAG
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | A-B-C-D
        |
        | becomes:
        |
        | [
        |   A,
        |   B,
        |   C,
        |   D
        | ]
        |
        |--------------------------------------------------------------------------
        */

        function getAssetRFIDs(
            assetTag
        ) {


            if (
                assetTag === null ||
                assetTag === undefined ||
                assetTag === ""
            ) {

                return [];

            }


            return String(
                assetTag
            )
                .split("-")
                .map(
                    cleanRFID
                )
                .filter(
                    function (id) {

                        return id !== "";

                    }
                );

        }



        /*
        |--------------------------------------------------------------------------
        | BUILD RFID FREQUENCY MAP
        |--------------------------------------------------------------------------
        |
        | Count how many different assets
        | contain each RFID ID.
        |
        | RFID appearing in only ONE asset
        | is considered UNIQUE.
        |--------------------------------------------------------------------------
        */

        function buildRFIDFrequencyMap() {


            rfidFrequency =
                new Map();


            originalTableData.forEach(
                function (row) {


                    const assetIDs =
                        getAssetRFIDs(
                            row.asset_tag
                        );


                    /*
                     * Remove duplicates inside
                     * the same asset.
                     */

                    const uniqueIDsInAsset =
                        [
                            ...new Set(
                                assetIDs
                            )
                        ];


                    uniqueIDsInAsset.forEach(
                        function (id) {


                            const currentCount =
                                rfidFrequency.get(
                                    id
                                )
                                ||
                                0;


                            rfidFrequency.set(
                                id,
                                currentCount + 1
                            );

                        }
                    );

                }
            );


            console.log(
                "RFID FREQUENCY:",
                Object.fromEntries(
                    rfidFrequency
                )
            );

        }



        /*
        |--------------------------------------------------------------------------
        | GET UNIQUE RFID IDs FOR ONE ASSET
        |--------------------------------------------------------------------------
        |
        | An RFID is unique when its
        | frequency is exactly 1.
        |--------------------------------------------------------------------------
        */

        function getUniqueRFIDsForAsset(
            assetTag
        ) {


            const assetIDs =
                getAssetRFIDs(
                    assetTag
                );


            return assetIDs.filter(
                function (id) {

                    return (
                        rfidFrequency.get(id) === 1
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | CAPTURE ORIGINAL TABLE DATA
        |--------------------------------------------------------------------------
        */

        function captureOriginalData() {


            if (
                originalDataCaptured
            ) {

                return;

            }


            const data =
                $assetTable.bootstrapTable(
                    "getData"
                );


            if (
                data &&
                data.length > 0
            ) {


                /*
                 * Save original rows.
                 */

                originalTableData =
                    data.slice();


                originalDataCaptured =
                    true;


                /*
                 * Now calculate which
                 * RFIDs are unique.
                 */

                buildRFIDFrequencyMap();


                console.log(
                    "Original asset rows captured:",
                    originalTableData.length
                );

            }

        }



        /*
        |--------------------------------------------------------------------------
        | CHECK IF ASSET MATCHES BULK SCAN
        |--------------------------------------------------------------------------
        |
        | NEW MATCHING RULE:
        |
        | CONDITION 1:
        |
        | If ONE UNIQUE RFID belonging to
        | this asset has been scanned:
        |
        |       SHOW ASSET
        |
        | OR
        |
        | CONDITION 2:
        |
        | If ALL RFID IDs belonging to
        | this asset have been scanned:
        |
        |       SHOW ASSET
        |
        |--------------------------------------------------------------------------
        */

        function assetMatchesScan(
            assetTag
        ) {


            const assetIDs =
                getAssetRFIDs(
                    assetTag
                );


            /*
             * No RFID information.
             */

            if (
                assetIDs.length === 0
            ) {

                return false;

            }



            /*
            |--------------------------------------------------------------------------
            | GET UNIQUE IDs FOR THIS ASSET
            |--------------------------------------------------------------------------
            */

            const uniqueRFIDs =
                getUniqueRFIDsForAsset(
                    assetTag
                );



            /*
            |--------------------------------------------------------------------------
            | UNIQUE RFID MATCH
            |--------------------------------------------------------------------------
            |
            | As soon as Zebra scans an RFID
            | that belongs only to this asset,
            | show the asset.
            |--------------------------------------------------------------------------
            */

            const uniqueRFIDScanned =
                uniqueRFIDs.some(
                    function (id) {

                        return scannedRFIDs.has(
                            id
                        );

                    }
                );


            if (
                uniqueRFIDScanned
            ) {

                return true;

            }



            /*
            |--------------------------------------------------------------------------
            | FALLBACK: ALL RFID IDS SCANNED
            |--------------------------------------------------------------------------
            |
            | If there is no unique RFID match,
            | keep the original 4/4 behavior.
            |--------------------------------------------------------------------------
            */

            const allRFIDsScanned =
                assetIDs.every(
                    function (id) {

                        return scannedRFIDs.has(
                            id
                        );

                    }
                );


            return allRFIDsScanned;

        }



        /*
        |--------------------------------------------------------------------------
        | FILTER TABLE
        |--------------------------------------------------------------------------
        */

        function applyBulkFilter() {


            captureOriginalData();



            /*
            |--------------------------------------------------------------------------
            | NOTHING SCANNED
            |--------------------------------------------------------------------------
            */

            if (
                scannedRFIDs.size === 0
            ) {


                if (
                    originalDataCaptured
                ) {

                    $assetTable.bootstrapTable(
                        "load",
                        originalTableData
                    );

                }


                matchMessage.textContent =
                    "0 matching item(s)";


                return;

            }



            /*
            |--------------------------------------------------------------------------
            | FIND MATCHING ASSETS
            |--------------------------------------------------------------------------
            */

            const matchedAssets =
                originalTableData.filter(
                    function (row) {

                        return assetMatchesScan(
                            row.asset_tag
                        );

                    }
                );



            /*
            |--------------------------------------------------------------------------
            | REDRAW TABLE
            |--------------------------------------------------------------------------
            */

            $assetTable.bootstrapTable(
                "load",
                matchedAssets
            );



            /*
            |--------------------------------------------------------------------------
            | MATCH COUNT
            |--------------------------------------------------------------------------
            */

            matchMessage.textContent =
                matchedAssets.length
                +
                " matching item(s)";


            console.log(
                "Matching assets:",
                matchedAssets
            );

        }



        /*
        |--------------------------------------------------------------------------
        | ACCEPT ONE RFID
        |--------------------------------------------------------------------------
        */

        function acceptRFID(id) {


            id =
                cleanRFID(
                    id
                );


            if (
                id === ""
            ) {

                return;

            }



            /*
            |--------------------------------------------------------------------------
            | DUPLICATE RFID
            |--------------------------------------------------------------------------
            */

            if (
                scannedRFIDs.has(
                    id
                )
            ) {


                statusMessage.textContent =
                    "RFID already detected - continue scanning";


                scanInput.focus();


                return;

            }



            /*
            |--------------------------------------------------------------------------
            | NEW RFID
            |--------------------------------------------------------------------------
            */

            scannedRFIDs.add(
                id
            );


            /*
             * Update badges.
             */

            updateRFIDTags();


            /*
             * Update count.
             */

            updateRFIDCount();


            /*
             * Re-check every asset.
             */

            applyBulkFilter();


            statusMessage.textContent =
                "RFID captured - continue scanning";


            console.log(
                "RFID DETECTED:",
                id
            );


            console.log(
                "ALL SCANNED RFID IDS:",
                Array.from(
                    scannedRFIDs
                )
            );


            scanInput.focus();

        }



        /*
        |--------------------------------------------------------------------------
        | RFID INPUT
        |--------------------------------------------------------------------------
        |
        | Zebra HID configuration:
        |
        | RFID
        | ENTER
        |
        | Everything before Enter is treated
        | as ONE RFID ID.
        |--------------------------------------------------------------------------
        */

        scanInput.addEventListener(
            "keydown",
            function (event) {


                /*
                |--------------------------------------------------------------------------
                | ENTER
                |--------------------------------------------------------------------------
                */

                if (
                    event.key === "Enter"
                ) {


                    event.preventDefault();


                    const scannedID =
                        scanInput.value;


                    /*
                     * Clear immediately so
                     * next RFID can arrive.
                     */

                    scanInput.value =
                        "";


                    acceptRFID(
                        scannedID
                    );


                    scanInput.focus();


                    return;

                }



                /*
                |--------------------------------------------------------------------------
                | TAB
                |--------------------------------------------------------------------------
                */

                if (
                    event.key === "Tab"
                ) {


                    event.preventDefault();


                    scanInput.focus();


                    return;

                }

            }
        );



        /*
        |--------------------------------------------------------------------------
        | CLEAR BULK SCAN
        |--------------------------------------------------------------------------
        */

        clearButton.addEventListener(
            "click",
            function () {


                /*
                 * Clear scanned RFID IDs.
                 */

                scannedRFIDs =
                    new Set();



                /*
                 * Clear badges.
                 */

                updateRFIDTags();



                /*
                 * Clear input.
                 */

                scanInput.value =
                    "";



                /*
                 * Restore original table.
                 */

                if (
                    originalDataCaptured
                ) {

                    $assetTable.bootstrapTable(
                        "load",
                        originalTableData
                    );

                }



                /*
                 * Reset labels.
                 */

                statusMessage.textContent =
                    "Ready for bulk scanning";


                updateRFIDCount();


                matchMessage.textContent =
                    "0 matching item(s)";


                scanInput.focus();

            }
        );



        /*
        |--------------------------------------------------------------------------
        | CAPTURE DATA AFTER TABLE API LOAD
        |--------------------------------------------------------------------------
        */

        $assetTable.on(
            "load-success.bs.table",
            function () {


                if (
                    !originalDataCaptured
                ) {

                    captureOriginalData();

                }

            }
        );



        /*
        |--------------------------------------------------------------------------
        | TABLE MAY ALREADY BE LOADED
        |--------------------------------------------------------------------------
        */

        setTimeout(
            function () {

                captureOriginalData();

            },
            500
        );



        /*
        |--------------------------------------------------------------------------
        | INITIALIZE
        |--------------------------------------------------------------------------
        */

        updateRFIDCount();

        updateRFIDTags();


    }
);

</script>


@stop