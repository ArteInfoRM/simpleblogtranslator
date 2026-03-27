/**
 * SimpleBlog Translator – Admin JS
 *
 * @author    Tecnoacquisti.com
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

$(document).ready(function () {

    /* ── State ──────────────────────────────────────────────────────────── */
    var stopRequested = false;

    /* ── Init: replace numeric IDs in lang badges with real names ────────── */
    function initLangBadges() {
        $('.sbt-lang-badge').each(function () {
            var lid = parseInt($(this).data('lang-id'), 10);
            var name = SBT.langMap[lid];
            if (name) {
                $(this).text(name);
            }
        });
    }
    initLangBadges();

    /* ── Regen meta toggle: disable translate-content when regen is on ──── */
    $('#sbt-regen-meta').on('change', function () {
        var regenOn = $(this).is(':checked');
        $('#sbt-translate-content').prop('disabled', regenOn);
        $('#sbt-opt-content-wrap').toggleClass('sbt-disabled-opt', regenOn);
        $('#sbt-opt-content-note').toggleClass('sbt-disabled-opt', regenOn);
        if (regenOn) {
            $('#sbt-translate-content').prop('checked', false);
        }
    });
    // Init state on page load
    if ($('#sbt-regen-meta').is(':checked')) {
        $('#sbt-translate-content').prop('disabled', true);
        $('#sbt-opt-content-wrap, #sbt-opt-content-note').addClass('sbt-disabled-opt');
    }

    /* ── Action-bar counter update ───────────────────────────────────────── */
    function refreshActionBar() {
        var selPosts = $('.sbt-post-check:checked').length;
        var targetVal = $('#sbt-target-langs').val();
        var selLangs = (targetVal && targetVal.length) ? targetVal.length : 0;
        var regenOn = $('#sbt-regen-meta').is(':checked');
        // In regen mode we also process the source language for each selected post
        var totalOps = regenOn
            ? selPosts * (selLangs + 1)   // +1 for source lang
            : selPosts * selLangs;

        $('#sbt-sel-count  strong').text(selPosts);
        $('#sbt-lang-count strong').text(regenOn ? selLangs + 1 : selLangs);

        if (totalOps > 0) {
            $('#sbt-ops-badge').text(totalOps + ' ' + SBT.i18n.operations).show();
        } else {
            $('#sbt-ops-badge').hide();
        }

        $('#sbt-translate-btn').prop('disabled', totalOps === 0);
    }
    $('#sbt-regen-meta').on('change', refreshActionBar);

    /* ── Row click → toggle checkbox ────────────────────────────────────── */
    $(document).on('click', '.sbt-post-row td:not(.sbt-col-check)', function () {
        var $cb = $(this).closest('tr').find('.sbt-post-check');
        if (!$cb.is(':disabled')) {
            $cb.prop('checked', !$cb.is(':checked'));
            syncHeaderCheckbox();
            refreshActionBar();
        }
    });

    $(document).on('change', '.sbt-post-check', function () {
        syncHeaderCheckbox();
        refreshActionBar();
    });

    function syncHeaderCheckbox() {
        var total = $('.sbt-post-check:not(:disabled)').length;
        var checked = $('.sbt-post-check:checked').length;
        $('#sbt-check-all').prop('checked', total > 0 && checked === total);
    }

    $('#sbt-check-all').on('change', function () {
        $('.sbt-post-check:not(:disabled)').prop('checked', $(this).is(':checked'));
        refreshActionBar();
    });

    $('#sbt-select-all-btn').on('click', function () {
        $('.sbt-post-check:not(:disabled)').prop('checked', true);
        $('#sbt-check-all').prop('checked', true);
        refreshActionBar();
    });
    $('#sbt-deselect-all-btn').on('click', function () {
        $('.sbt-post-check').prop('checked', false);
        $('#sbt-check-all').prop('checked', false);
        refreshActionBar();
    });

    $('#sbt-target-langs').on('change', refreshActionBar);

    /* ── Source language change → reload page ────────────────────────────── */
    $('#sbt-source-lang').on('change', function () {
        $('#sbt-filter-form').submit();
    });

    /* ── Per-page selector ───────────────────────────────────────────────── */
    $('#sbt-per-page').on('change', function () {
        var form = $('#sbt-search-form');
        $('input[name="per_page"]', form).val($(this).val());
        $('input[name="p"]', form).val(1);
        form.submit();
    });

    /* ── Stop button ──────────────────────────────────────────────────────── */
    $('#sbt-stop-btn').on('click', function () {
        stopRequested = true;
        $(this).prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> Stopping...');
    });

    /* ── Start translation button ─────────────────────────────────────────── */
    $('#sbt-translate-btn').on('click', function () {
        var targetLangs = $('#sbt-target-langs').val();
        var selectedIds = [];
        $('.sbt-post-check:checked').each(function () {
            selectedIds.push($(this).val());
        });
        var translateContent = $('#sbt-translate-content').is(':checked') ? 1 : 0;
        var regenMeta = $('#sbt-regen-meta').is(':checked') ? 1 : 0;

        if (!targetLangs || !targetLangs.length) {
            showAlert('warning', SBT.i18n.selectTargets);
            return;
        }
        if (!selectedIds.length) {
            showAlert('warning', SBT.i18n.selectPosts);
            return;
        }

        // Build queue
        var queue = [];

        if (regenMeta) {
            // Regen meta: process source lang first, then each target lang
            for (var i = 0; i < selectedIds.length; i++) {
                // Source language (target_lang_id = source_lang_id → regenerate in place)
                queue.push({
                    postId: selectedIds[i],
                    targetLangId: SBT.sourceLangId,
                    regenMeta: 1,
                    translateContent: 0
                });
                // Each target language
                for (var j = 0; j < targetLangs.length; j++) {
                    queue.push({
                        postId: selectedIds[i],
                        targetLangId: targetLangs[j],
                        regenMeta: 1,
                        translateContent: 0
                    });
                }
            }
        } else {
            // Normal translation
            for (var i = 0; i < selectedIds.length; i++) {
                for (var j = 0; j < targetLangs.length; j++) {
                    queue.push({
                        postId: selectedIds[i],
                        targetLangId: targetLangs[j],
                        regenMeta: 0,
                        translateContent: translateContent
                    });
                }
            }
        }

        runQueue(queue);
    });

    /* ════════════════════════════════════════════════════════════════════════
       QUEUE RUNNER
    ════════════════════════════════════════════════════════════════════════ */
    function runQueue(queue) {
        stopRequested = false;
        var total = queue.length;
        var done = 0;
        var errors = 0;

        $('#sbt-progress-panel').show();
        $('#sbt-alert-container').hide().html('');
        $('#sbt-translate-btn').prop('disabled', true);
        $('#sbt-stop-btn').prop('disabled', false)
            .html('<i class="icon-stop"></i> Stop');
        $('#sbt-progress-log').html('');
        setProgress(0, total, SBT.i18n.translating);

        function next(idx) {
            if (stopRequested || idx >= queue.length) {
                finalise(done, total, errors);
                return;
            }

            var item = queue[idx];
            var $row = $('tr[data-post-id="' + item.postId + '"]');
            var postTitle = $row.find('.sbt-title').text().trim() || ('#' + item.postId);
            var tLangName = SBT.langMap[item.targetLangId] || item.targetLangId;
            var isSource  = (parseInt(item.targetLangId, 10) === parseInt(SBT.sourceLangId, 10));

            var opLabel = item.regenMeta
                ? (isSource ? '[META src] ' : '[META] ')
                : '[TRANS] ';

            setProgress(done + errors, total,
                SBT.i18n.translating + ' ' + shorten(postTitle, 35) + ' > ' + tLangName);
            addLog('info', opLabel + shorten(postTitle, 45) + ' > ' + tLangName);

            $.ajax({
                url: SBT.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                timeout: 210000,
                data: {
                    ajax: 1,
                    action: 'translate',
                    token: SBT.token,
                    id_post: item.postId,
                    source_lang_id: SBT.sourceLangId,
                    target_lang_id: item.targetLangId,
                    translate_content: item.translateContent,
                    regen_meta: item.regenMeta
                },
                success: function (resp) {
                    if (resp && resp.success) {
                        done++;
                        addLog('success', '[OK] ' + shorten(postTitle, 45) + ' > ' + tLangName);
                        // Add lang badge if not already present (only for translation, not source regen)
                        if (!isSource) {
                            var $langCell = $('#sbt-langs-' + item.postId);
                            if (!$langCell.find('[data-lang-id="' + item.targetLangId + '"]').length) {
                                $langCell.append(
                                    '<span class="sbt-lang-badge" data-lang-id="' + item.targetLangId + '">'
                                    + escHtml(tLangName) + '</span>'
                                );
                            }
                        }
                    } else {
                        errors++;
                        var msg = (resp && resp.message) ? resp.message : 'Unknown error';
                        addLog('error', '[ERR] ' + shorten(postTitle, 45) + ' > ' + tLangName + ': ' + msg);
                    }
                },
                error: function (xhr, status, err) {
                    errors++;
                    addLog('error', '[ERR] ' + shorten(postTitle, 45) + ' > ' + tLangName + ': ' + (err || status));
                },
                complete: function () {
                    setProgress(done + errors, total, SBT.i18n.translating);
                    setTimeout(function () { next(idx + 1); }, 400);
                }
            });
        }

        next(0);
    }

    /* ── Finalise ───────────────────────────────────────────────────────── */
    function finalise(done, total, errors) {
        var label = stopRequested ? SBT.i18n.stopped : SBT.i18n.complete;
        var alertType = errors > 0 ? 'warning' : 'success';
        setProgress(done + errors, total, label);
        $('#sbt-translate-btn').prop('disabled', false);
        $('#sbt-stop-btn').prop('disabled', true);
        var msg = '<strong>' + escHtml(label) + '</strong> '
            + done + ' / ' + total + ' completed.';
        if (errors > 0) {
            msg += ' <span class="text-danger">' + errors + ' error(s) – check the log above.</span>';
        }
        showAlert(alertType, msg);
        $('html,body').animate({ scrollTop: $('#sbt-alert-container').offset().top - 60 }, 400);
    }

    /* ── Helpers ────────────────────────────────────────────────────────── */
    function setProgress(done, total, label) {
        var pct = total > 0 ? Math.round((done / total) * 100) : 0;
        $('#sbt-progress-bar')
            .css('width', pct + '%')
            .attr('aria-valuenow', pct)
            .text(pct + '%');
        $('#sbt-progress-label').text(label);
        $('#sbt-progress-count').text('(' + done + ' / ' + total + ')');
    }

    function addLog(type, msg) {
        var cls = { success: 'text-success', error: 'text-danger', info: 'text-info' }[type] || '';
        var $log = $('#sbt-progress-log');
        $log.append('<div class="sbt-log-line ' + cls + '">' + escHtml(msg) + '</div>');
        $log.scrollTop($log[0].scrollHeight);
    }

    function showAlert(type, html) {
        var cls = { success: 'alert-success', warning: 'alert-warning', danger: 'alert-danger' }[type]
            || 'alert-info';
        $('#sbt-alert-container')
            .html('<div class="alert ' + cls + '">' + html + '</div>')
            .show();
    }

    function escHtml(str) {
        return $('<span>').text(String(str)).html();
    }

    function shorten(str, max) {
        return str.length > max ? str.substring(0, max) + '...' : str;
    }

    /* ── Initial state ──────────────────────────────────────────────────── */
    refreshActionBar();
});
