<?php

$file = "/Applications/MAMP/logs/php_error.log";

require_once("../../redcap_connect.php");

// This is the ajax service that queries the log
if (isset($_GET['seek'])) {
    $seek = $_GET['seek'];
    $lines = [];
    $handle = fopen($file, 'rb');
    if ($seek > 0) {
        fseek($handle, $seek);
    }
    while (($line = fgets($handle, 4096)) !== false) {
        $lines[] = $line;
    }
    $seek = ftell($handle);

    // Return Data
    header("Content-Type: application/json");
    echo json_encode(['seek' => $seek, 'lines' => $lines]);
    exit();
}


$html = new HtmlPage();
$html->PrintHeaderExt();

?>

<div class="well">Log Viewer: <code><?= $file ?></code></div>

<div id="controls">
    <button class="btn btn-xs enabled" id="autoscroll">AutoScroll On</button>
    <button class="btn btn-xs" id="clear">Clear</button>
    <button class="btn btn-xs" id="goto-top">Top</button>
    <button class="btn btn-xs" id="goto-bottom">Bottom</button>
</div>

<table id="logTable" style="width:100%;">
    <thead><tr><th>#</th><th>Lines</th></tr></thead>
    <tbody></tbody>
</table>

<style>
    body {
        margin: 0;
        padding: 0;
    }

    #controls button            { background-color: #666; display:inline-block; color: #fff !important; }
    #controls button.enabled    { background-color: #8C1515; }
</style>

<style>
    table.dataTable tr.highlight td {
        color:yellow !important;
    }

    .dataTables_wrapper td pre {
        padding: 0px;
        margin:0px;
        color: inherit;
        background-color: inherit;
        font-size: inherit;
        word-break: break-all;
        word-wrap: break-word;
        border:none;
        vertical-align: inherit;
        line-height:inherit;
        width: 100%;
    }

    #logTable td, div.dataTables_wrapper table.dataTable tbody tr td.row {
        color: white;
        background-color: black;
        font-size: 10px;
        font-family: Menlo,Monaco,Consolas,"Courier New",monospace;
        word-break: break-all;
        word-wrap: break-word;
        padding: 1px;
        vertical-align: text-top;
    }

    table.dataTable thead th {
        padding: 1px 20px 0px 1px;
        background-color: #222;
        text-align:left;
        color: white;
        border-bottom: 1px solid white;
    }

    #pagecontainer {
        max-width: none;
    }

    .dataTables_wrapper .dataTables_filter {
        float:left;
    }

</style>

<script>

    // A delayed binder for keyup triggers
    // https://github.com/bgrins/bindWithDelay/blob/master/bindWithDelay.js
    (function($) {

        $.fn.bindWithDelay = function( type, data, fn, timeout, throttle ) {

            if ( $.isFunction( data ) ) {
                throttle = timeout;
                timeout = fn;
                fn = data;
                data = undefined;
            }

            // Allow delayed function to be removed with fn in unbind function
            fn.guid = fn.guid || ($.guid && $.guid++);

            // Bind each separately so that each element has its own delay
            return this.each(function() {

                var wait = null;

                function cb() {
                    var e = $.extend(true, { }, arguments[0]);
                    var ctx = this;
                    var throttler = function() {
                        wait = null;
                        fn.apply(ctx, [e]);
                    };

                    if (!throttle) { clearTimeout(wait); wait = null; }
                    if (!wait) { wait = setTimeout(throttler, timeout); }
                }

                cb.guid = fn.guid;

                $(this).bind(type, data, cb);
            });
        };

    })(jQuery);


    // Turn on/off the autoscroll feature and cache the status in a cookie for a year
    function toggleAutoscroll() {
        var status = $('#autoscroll').hasClass('enabled');
        if (status) {
            $('#autoscroll').removeClass('enabled').text("Autoscroll Off");
            setCookie('autoscroll','-1',365);
        } else {
            $('#autoscroll').addClass('enabled').text("Autoscroll On");
            setCookie('autoscroll','1',365);
        }
    }

    // Toggle Clear
    function toggleClear() {
        dt.clear().draw();
    }

    // Move to top of table
    $('#goto-top').on('click',function(){
        $(".dataTables_scrollBody").scrollTop(0);
    });

    // Move to bottom of table
    $('#goto-bottom').on('click',function(){
        $(".dataTables_scrollBody").scrollTop(99999);
    });

    // Implement ability to exclude in search box
    function excludeSearch() {
        // ^((?!hede).)*$
        filter = $('#exclude_input').val();
        // console.log("Raw Filter",filter);

        if (filter.length){
            filter = "^((?!" + filter + ").)*$"; // "1";  // this.value
        }
        // console.log("Filter",filter);
        dt
            .columns( 1 )
            .search(filter, true, false );
            // .draw();
        reDrawTable();
    }

    // Redraw Table but maintain scroll position
    function reDrawTable(scrollPos){
        if (seek == 0 || $('#autoscroll').hasClass('enabled')) {
            // First load - scroll to bottom
            scrollPos = 9999999;
        }

        if (!scrollPos) {
            // Cache the current position
            scrollPos = $(".dataTables_scrollBody").scrollTop();
        }
        dt.draw();
        $(".dataTables_scrollBody").scrollTop(scrollPos);
    }

    function reloadTable() {
        // console.log('Reloading with seek: ' + seek);
        $.ajax({
            dataType: "json",
            url: '', //'?seek=' + this.seek,
            data: { "seek": seek },
            success: function(data) {
                // console.log(data);
                seek = data.seek;

                var lines = data.lines;
                // Take the last 500 lines on start
                // lines = lines.slice(Math.max(lines.length - 500, 1));
                if (lines.length) {
                    // Update
                    for (var i = 0; i < lines.length; i++) {
                        dt.row.add({"id": row_num, "lines": "<pre>" + lines[i] + "</pre>"});
                        row_num++;
                    }

                    reDrawTable();
                }
                window.setTimeout(function() { reloadTable(); }, 2000);
            }
        });
    }


    $( document ).ready(function() {

        $('#autoscroll').bind('click',toggleAutoscroll);
        $('#clear').bind('click',toggleClear);
        if (getCookie('autoscroll') == -1) toggleAutoscroll();

        dt = $('#logTable').DataTable( {
            "order": [[ 0, "asc" ]],
            "scrollY": '50vh',
            "scrollCollapse": true,
            "paging": false,

            "columns": [
                { "data": "id" },
                {
                    "data": "lines",
                    "className": "line"
                }
            ]
        });

        var excludeFilter = $("<div id='logTable_exclude' class='dataTables_filter'><label>Exclude:</label></div>");
        $("<input id='exclude_input' type='text' class='form-control input-sm' placeholder='Exclude'/>")
            .on('change', excludeSearch)
            .bindWithDelay("keyup", excludeSearch, 1000, true)
            .appendTo(excludeFilter)
            .parent().insertAfter('#logTable_filter');

        $('#logTable tbody').on('dblclick','tr', function() {
            //https://stackoverflow.com/questions/35547647/how-to-make-datatable-row-or-cell-clickable
            $(this).toggleClass('highlight');
        });

        $('#logTable_filter.dataTables_filter input[type="search"]').attr('type','text').prop('placeholder','Search');

        // Cleanup
        $('#logTable_info').parent().removeClass('col-sm-5').addClass('col-sm-12');
        // $('#logTable_paginate').parent().removeClass('col-sm-7').addClass('col-sm-12');
        // $('#logTable_filter.dataTables_filter').parent().removeClass('col-sm-6');

        // Insert button controls into same row as other stuff
        $('#controls').detach().appendTo( $('#logTable_filter.dataTables_filter').parent().prev() );

        row_num = 0;
        seek = 0;

        reloadTable();

        return false;

        window.setTimeout(function(){ app.load(); }, 500);

    });


</script>
</body>
</html>