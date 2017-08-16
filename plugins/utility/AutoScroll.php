<?php

/**
 * This is a hook for autoscrolling after inputs in a form
 */

Plugin::log("In AutoScroll");

?>


<style>
    #_autoscroll         { background-color: #666; display:inline-block; color: #fff !important; }
    #_autoscroll.enabled { background-color: #8C1515; }
</style>

<script>

    // Scroll to the next sibling TR
    function autoScrollNextTr() {
        if ( $('#_autoscroll').hasClass('enabled') ) {

            // Skip Matrix Radios
            if ($(this).closest('td').hasClass('choicematrix')) return;

            // Get the current tr
            currentTr = $(this).parentsUntil('tr').parent();

            // Add a slight delay for branching logic to file and new TRs to be displayed before scrolling...
            var timeoutId = window.setTimeout(function() {
                if (nextTr = $(currentTr).nextAll('tr:visible').first()) {
                    $("html, body").animate({
                        scrollTop: $(nextTr).offset().top
                    }, 300);
                } else {
                    // No more visible trs
                }
            },100,currentTr);
        }
    }


    // Turn on/off the autoscroll feature and cache the status in a cookie for a year
    function toggleAutoscroll() {
        var as = $('#_autoscroll');
        if ( as.hasClass('enabled') ) {
            as.removeClass('enabled').text("Autoscroll Off");
            setCookie('autoscroll','-1',365);
        } else {
            as.addClass('enabled').text("Autoscroll On");
            setCookie('autoscroll','1',365);
        }
    }


    $(document).ready(function() {
        // Enable Radios
        $('#questiontable tr input[type="radio"]').bind('click',autoScrollNextTr);

        // Enable Selects
        $('#questiontable tr select').bind('change',autoScrollNextTr);

        // Add Button in corner to toggle feature
        var btn = $('<button class="btn btn-xs enabled" id="_autoscroll">AutoScroll On</button>').on('click',toggleAutoscroll);

        // We have to find a place to insert the button
        if ($('#changeFont').length) {
            // If we are in a survey
            $('#changeFont').prepend(btn).bind('click',toggleAutoscroll());
        } else if ($('#pdfExportDropdownTrigger').length) {
            // If we are in a data entry form
            $('#pdfExportDropdownTrigger').after(btn).bind('click',toggleAutoscroll());
        }

        // Turn autoscroll off on load if cookie is -1
        if (getCookie('autoscroll') == -1) toggleAutoscroll();
    });

</script>


}