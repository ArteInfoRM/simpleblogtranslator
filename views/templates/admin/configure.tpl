{*
 * SimpleBlog Translator
 *
 * @author    Tecnoacquisti.com
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}
<script>
(function ($) {

    /* -- Provider <-> Model filter -----------------------------------------
       Hide/show on <option> is not reliable in Chrome - we rebuild the
       select each time from a stored snapshot of all options.
    -------------------------------------------------------------------- */
    $(document).ready(function () {
        var $provider = $('#SIMPLEBLOGTRANSLATOR_PROVIDER');
        var $model    = $('#SIMPLEBLOGTRANSLATOR_MODEL');

        if (!$provider.length || !$model.length) { return; }

        // Snapshot all options once
        var $allOptions = $model.find('option').clone();

        function filterModels() {
            var prov    = $provider.val();
            var current = $model.val();

            $model.empty();

            $allOptions.each(function () {
                var id          = $(this).val();
                var isOpenAI    = id.indexOf('gpt-') === 0 || id.indexOf('o1') === 0 || id.indexOf('o3') === 0 || id.indexOf('o4') === 0;
                var isAnthropic = id.indexOf('claude-') === 0;
                var include     = (prov === 'anthropic') ? isAnthropic : isOpenAI;
                if (include) {
                    $model.append($(this).clone());
                }
            });

            // Restore previous selection if still valid, otherwise pick first
            if ($model.find('option[value="' + current + '"]').length) {
                $model.val(current);
            } else {
                $model.find('option:first').prop('selected', true);
            }

        }

        filterModels();
        $provider.on('change', filterModels);
    });

    /* -- API test button ----------------------------------------------- */
    $('#sbt-test-api-btn').on('click', function () {
        var $btn     = $(this);
        var $spinner = $('#sbt-test-spinner');
        var $result  = $('#sbt-test-result');

        $btn.prop('disabled', true);
        $spinner.show();
        $result.hide().html('');

        $.ajax({
            url: '{$sbt_ajax_url|escape:'javascript':'UTF-8'}',
            type: 'POST',
            dataType: 'json',
            timeout: 35000,
            data: {
                ajax:   1,
                action: 'test_api',
                token:  '{$sbt_ajax_token|escape:'javascript':'UTF-8'}'
            },
            success: function (resp) {
                if (resp && resp.success) {
                    $result.html('<div class="alert alert-success"><i class="icon-check"></i> ' + $('<span>').text(resp.message).html() + '</div>');
                } else {
                    var msg = (resp && resp.message) ? resp.message : 'Unknown error';
                    $result.html('<div class="alert alert-danger"><i class="icon-warning-sign"></i> ' + $('<span>').text(msg).html() + '</div>');
                }
            },
            error: function (xhr, status, err) {
                $result.html('<div class="alert alert-danger"><i class="icon-warning-sign"></i> ' + $('<span>').text('AJAX error: ' + (err || status)).html() + '</div>');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.hide();
                $result.show();
            }
        });
    });
}(jQuery));
</script>
