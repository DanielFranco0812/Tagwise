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



{{-- ============================================================
     PAGE TITLE
============================================================ --}}

@section('title')

    @yield('title0') @parent

@stop



{{-- ============================================================
     PAGE CONTENT
============================================================ --}}

@section('content')

    <x-container>

        <x-box name="assets">


            {{-- ============================================================
                 BULK RFID SCANNER
            ============================================================ --}}

            <div
                class="row"
                style="margin-bottom: 20px;"
            >

                <div class="col-md-8">

                    <div class="form-group">

                        <label for="bulk_rfid_scan">
                            Bulk RFID Scan
                        </label>


                        <input
                            type="text"
                            id="bulk_rfid_scan"
                            class="form-control"
                            autocomplete="off"
                            placeholder="Click here, then start bulk scanning RFID tags"
                        >


                        {{-- Status --}}
                        <div
                            id="bulk_rfid_status"
                            style="
                                margin-top: 8px;
                                font-weight: bold;
                            "
                        >
                            Ready for bulk scanning
                        </div>


                        {{-- RFID Count --}}
                        <div
                            id="bulk_rfid_count"
                            style="
                                margin-top: 4px;
                                color: #666;
                            "
                        >
                            0 unique RFID ID(s) scanned
                        </div>


                        {{-- Matching Assets Count --}}
                        <div
                            id="bulk_match_count"
                            style="
                                margin-top: 4px;
                                color: #666;
                            "
                        >
                            0 matching item(s)
                        </div>


                        {{-- ====================================================
                             SCANNED RFID TAGS
                        ===================================================== --}}

                        <div
                            id="bulk_rfid_tags"
                            style="
                                margin-top: 12px;
                                display: flex;
                                flex-wrap: wrap;
                                gap: 6px;
                            "
                        >
                        </div>


                    </div>

                </div>


                {{-- CLEAR BUTTON --}}

                <div
                    class="col-md-4"
                    style="padding-top: 25px;"
                >

                    <button
                        type="button"
                        id="clear_bulk_rfid"
                        class="btn btn-danger"
                    >
                        Clear Bulk Scan
                    </button>

                </div>

            </div>



            {{-- ============================================================
                 EXISTING SNIPE-IT ASSET TABLE
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



{{-- ============================================================
     JAVASCRIPT
============================================================ --}}

@section('moar_scripts')



{{-- ================================================================
     ASSET TAG SHORT FORMATTER
================================================================ --}}

<script>

function assetTagShortFormatter(value, row, index) {

    /*
    |--------------------------------------------------------------------------
    | EMPTY
    |--------------------------------------------------------------------------
    */

    if (
        value === null ||
        value === undefined ||
        value === ''
    ) {

        return '';

    }


    /*
    |--------------------------------------------------------------------------
    | FULL ASSET TAG
    |--------------------------------------------------------------------------
    */

    const fullValue =
        String(value);


    /*
    |--------------------------------------------------------------------------
    | SHORT DISPLAY VALUE
    |--------------------------------------------------------------------------
    */

    let shortValue =
        fullValue;


    /*
     * Display only first 20 characters.
     *
     * The actual API/database value
     * remains complete.
     */

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
    | KEEP ORIGINAL SNIPE-IT HARDWARE LINK
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

                /*
                 * Change displayed text only.
                 */

                link.textContent =
                    shortValue;


                /*
                 * Hover displays full Asset Tag.
                 */

                link.setAttribute(
                    'title',
                    fullValue
                );


                return wrapper.innerHTML;

            }


            /*
             * Short values can use
             * original formatter directly.
             */

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
     ORIGINAL SNIPE-IT BOOTSTRAP TABLE SCRIPT
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
        | CHECK ELEMENTS
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
        | FIND EXISTING SNIPE-IT TABLE
        |--------------------------------------------------------------------------
        */

        let $assetTable =
            $("#assetsListingTable");


        /*
         * Fallback for versions where
         * the table ID is different.
         */

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
         * ALL unique RFID IDs received
         * during this bulk scan.
         */

        let scannedRFIDs =
            new Set();


        /*
         * Complete original Bootstrap Table data.
         */

        let originalTableData =
            [];


        /*
         * Prevent recapturing filtered data.
         */

        let originalDataCaptured =
            false;



        /*
        |--------------------------------------------------------------------------
        | CLEAN RFID VALUE
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
        | DISPLAY SCANNED RFID IDs AS TAGS
        |--------------------------------------------------------------------------
        */

        function updateRFIDTags() {


            /*
             * Remove existing badges.
             */

            tagsContainer.innerHTML =
                "";


            /*
             * Create one badge for each
             * unique scanned RFID.
             */

            Array.from(
                scannedRFIDs
            ).forEach(
                function (id, index) {


                    const tag =
                        document.createElement(
                            "span"
                        );


                    /*
                     * Bootstrap-style appearance.
                     */

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


                    /*
                     * Example:
                     *
                     * 1. 30340C139C...
                     */

                    tag.textContent =
                        (index + 1)
                        +
                        ". "
                        +
                        id;


                    /*
                     * Hover displays same ID.
                     */

                    tag.setAttribute(
                        "title",
                        id
                    );


                    tagsContainer
                        .appendChild(
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
        | CAPTURE ORIGINAL TABLE DATA
        |--------------------------------------------------------------------------
        */

        function captureOriginalData() {


            /*
             * Already captured.
             */

            if (
                originalDataCaptured
            ) {

                return;

            }


            const data =
                $assetTable.bootstrapTable(
                    "getData"
                );


            /*
             * Wait until table has data.
             */

            if (
                data &&
                data.length > 0
            ) {


                /*
                 * Store copy of ORIGINAL rows.
                 */

                originalTableData =
                    data.slice();


                originalDataCaptured =
                    true;


                console.log(
                    "Original asset rows captured:",
                    originalTableData.length
                );

            }

        }



        /*
        |--------------------------------------------------------------------------
        | GET RFID IDs FROM DATABASE asset_tag
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | Database value:
        |
        | AAA-BBB-CCC-DDD
        |
        | becomes:
        |
        | [
        |   AAA,
        |   BBB,
        |   CCC,
        |   DDD
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
        | CHECK WHETHER AN ASSET HAS BEEN SCANNED
        |--------------------------------------------------------------------------
        |
        | ORDER DOES NOT MATTER.
        |
        | Database:
        |
        | A-B-C-D
        |
        | Scanner may detect:
        |
        | C
        | D
        | A
        | B
        |
        | If A, B, C and D are all in
        | scannedRFIDs:
        |
        | MATCH = TRUE
        |
        |--------------------------------------------------------------------------
        */

        function assetMatchesScan(
            assetTag
        ) {


            const requiredRFIDs =
                getAssetRFIDs(
                    assetTag
                );


            /*
             * Asset has no RFID information.
             */

            if (
                requiredRFIDs.length === 0
            ) {

                return false;

            }


            /*
             * EVERY ID belonging to this asset
             * must exist in our scanned Set.
             */

            return requiredRFIDs.every(
                function (id) {

                    return scannedRFIDs.has(
                        id
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | FILTER EXISTING BOOTSTRAP TABLE
        |--------------------------------------------------------------------------
        |
        | We do NOT manually hide <tr>.
        |
        | Bootstrap Table reloads itself
        | using only matching data.
        |--------------------------------------------------------------------------
        */

        function applyBulkFilter() {


            /*
             * Make sure original data exists.
             */

            captureOriginalData();



            /*
            |--------------------------------------------------------------------------
            | NOTHING SCANNED
            |--------------------------------------------------------------------------
            */

            if (
                scannedRFIDs.size === 0
            ) {


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


                matchMessage.textContent =
                    "0 matching item(s)";


                return;

            }



            /*
            |--------------------------------------------------------------------------
            | GET MATCHING ASSETS
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
        |
        | Zebra HID sends:
        |
        | RFID VALUE
        | ENTER
        |
        | Everything before ENTER is considered
        | one complete RFID ID.
        |
        | LENGTH DOES NOT MATTER.
        |--------------------------------------------------------------------------
        */

        function acceptRFID(id) {


            /*
             * Clean incoming value.
             */

            id =
                cleanRFID(
                    id
                );


            /*
             * Empty.
             */

            if (
                id === ""
            ) {

                return;

            }



            /*
            |--------------------------------------------------------------------------
            | DUPLICATE RFID
            |--------------------------------------------------------------------------
            |
            | Zebra may read the same RFID
            | many times.
            |
            | Keep it only once.
            |--------------------------------------------------------------------------
            */

            if (
                scannedRFIDs.has(
                    id
                )
            ) {


                statusMessage.textContent =
                    "RFID already detected - continue scanning";


                /*
                 * Keep scanner ready.
                 */

                scanInput.focus();


                return;

            }



            /*
            |--------------------------------------------------------------------------
            | NEW UNIQUE RFID
            |--------------------------------------------------------------------------
            */

            scannedRFIDs.add(
                id
            );



            /*
             * Immediately display badge.
             */

            updateRFIDTags();



            /*
             * Update number of IDs.
             */

            updateRFIDCount();



            /*
             * Re-check table.
             */

            applyBulkFilter();



            /*
             * Status.
             */

            statusMessage.textContent =
                "RFID captured - continue scanning";



            /*
             * Debug.
             */

            console.log(
                "RFID DETECTED:",
                id
            );


            console.log(
                "ALL SCANNED RFID IDs:",
                Array.from(
                    scannedRFIDs
                )
            );



            /*
             * Keep Zebra input focused.
             */

            scanInput.focus();

        }



        /*
        |--------------------------------------------------------------------------
        | RFID INPUT LISTENER
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Only #bulk_rfid_scan listens
        | for the Zebra reader.
        |
        | It will NOT block typing
        | in other inputs.
        |--------------------------------------------------------------------------
        */

        scanInput.addEventListener(
            "keydown",
            function (event) {



                /*
                |--------------------------------------------------------------------------
                | ENTER = END OF ONE RFID
                |--------------------------------------------------------------------------
                */

                if (
                    event.key === "Enter"
                ) {


                    /*
                     * Prevent form submission.
                     */

                    event.preventDefault();



                    /*
                     * Whatever Zebra typed
                     * before Enter = one RFID.
                     */

                    const scannedID =
                        scanInput.value;



                    /*
                     * Clear input immediately
                     * for the next RFID.
                     */

                    scanInput.value =
                        "";



                    /*
                     * Process RFID.
                     */

                    acceptRFID(
                        scannedID
                    );



                    /*
                     * Keep scanner input focused.
                     */

                    scanInput.focus();


                    return;

                }



                /*
                |--------------------------------------------------------------------------
                | TAB
                |--------------------------------------------------------------------------
                |
                | Prevent Zebra from changing focus.
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
                |--------------------------------------------------------------------------
                | CLEAR RFID SET
                |--------------------------------------------------------------------------
                */

                scannedRFIDs =
                    new Set();



                /*
                |--------------------------------------------------------------------------
                | CLEAR RFID BADGES
                |--------------------------------------------------------------------------
                */

                updateRFIDTags();



                /*
                |--------------------------------------------------------------------------
                | CLEAR INPUT
                |--------------------------------------------------------------------------
                */

                scanInput.value =
                    "";



                /*
                |--------------------------------------------------------------------------
                | RESTORE ORIGINAL TABLE
                |--------------------------------------------------------------------------
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
                |--------------------------------------------------------------------------
                | RESET LABELS
                |--------------------------------------------------------------------------
                */

                statusMessage.textContent =
                    "Ready for bulk scanning";


                updateRFIDCount();


                matchMessage.textContent =
                    "0 matching item(s)";



                /*
                |--------------------------------------------------------------------------
                | READY FOR NEXT BULK SCAN
                |--------------------------------------------------------------------------
                */

                scanInput.focus();

            }
        );



        /*
        |--------------------------------------------------------------------------
        | CAPTURE ORIGINAL TABLE AFTER API LOAD
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