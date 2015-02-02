/**
 * Custom File Input
 * -----------------
 *
 *    <form class="form-upload">
 *      <span class="input-wrapper btn btn-default">
 *        <span><i class="fa fa-folder-open"></i> Select a file&hellip;</span>
 *        <input type="file" name="file" data-icon-ready="fa fa-check" data-icon-error="fa fa-times">
 *      </span> <button class="btn btn-action" type="submit"><i class="fa fa-cloud-upload"></i> Upload</button>
 *    </form>
 *
 */

(function($, base) {

    var $uploader = $('input[type="file"]'),
        accepted = $uploader.attr('data-accepted-extensions') ? $uploader.data('acceptedExtensions').split(',') : 'css,html,js,md,txt,bmp,cur,gif,ico,jpg,jpeg,png,eot,ttf,woff,gz,rar,tar,zip,zipx'.split(','),
        cache = $uploader.prev().html();

    if (!$uploader.length) return;

    $uploader.on("change", function(e) {

        var segments = this.value.split('.'),
            extension = segments[segments.length - 1].toLowerCase(),
            ok = $.inArray(extension, accepted) !== -1,
            status = ok ? 'btn-accept' : 'btn-reject',
            statusIcon = ok ? 'iconReady' : 'iconError';

        $(this).parent().next().prop('disabled', !ok);

        if (this.value === "") {

            $(this).prev().html(cache)
                .parent()
                    .removeClass('btn-accept btn-reject');

        } else {

            $(this).attr('title', this.value)
                .prev()
                    .html('<i class="' + $(this).data(statusIcon) + '"></i> ' + this.value)
                        .parent()
                            .removeClass('btn-accept btn-reject')
                                .addClass(status);

        }

        base.fire('on_file_change', {
            'event': e,
            'target': this
        });

        base.fire('on_file_' + (ok ? 'accept' : 'reject'), {
            'event': e,
            'target': this
        });

    });

})(Zepto, DASHBOARD);