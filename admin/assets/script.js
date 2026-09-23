/**
 * EX SEO Cluster & Internal Link Analyzer - Admin Scripts
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const localized = window.exSeoClusterData || {};
        let activePostDetails = null;

        // Elements
        const $startScanBtn   = $('#ex-seo-btn-start-scan');
        const $progressBox    = $('#ex-seo-scan-progress');
        const $progressBar    = $('#ex-seo-progress-fill');
        const $progressText   = $('#ex-seo-progress-text');
        const $searchInput    = $('#ex-seo-search-input');
        const $categoryFilter = $('#ex-seo-category-filter');
        const $roleFilter     = $('#ex-seo-role-filter');
        const $tableRows      = $('.ex-seo-table tbody tr');

        // Start / Re-run Scan
        $startScanBtn.on('click', function(e) {
            e.preventDefault();

            if (!confirm('آیا می‌خواهید اسکن مقالات و لینک‌های داخلی وبسایت آغاز شود؟')) {
                return;
            }

            $startScanBtn.prop('disabled', true).addClass('updating-message');
            $progressBox.slideDown();
            updateProgress(0, 'در حال بارگذاری و فهرست‌بندی مقالات...');

            // Step 1: Initialize Scan
            $.ajax({
                url: localized.ajax_url,
                type: 'POST',
                data: {
                    action: 'ex_seo_cluster_start_scan',
                    nonce: localized.nonce,
                    post_types: $('#ex-seo-post-types-select').val() || ['post']
                },
                success: function(res) {
                    if (!res.success) {
                        alert(res.data && res.data.message ? res.data.message : 'خطا در آغاز اسکن');
                        resetScanState();
                        return;
                    }

                    const postIds = res.data.post_ids || [];
                    const totalPosts = postIds.length;

                    if (totalPosts === 0) {
                        alert('هیچ مقاله‌ای برای اسکن یافت نشد.');
                        resetScanState();
                        return;
                    }

                    // Process batches
                    const batchSize = 15;
                    let currentIndex = 0;

                    function processNextBatch() {
                        if (currentIndex >= totalPosts) {
                            finalizeScan();
                            return;
                        }

                        const batch = postIds.slice(currentIndex, currentIndex + batchSize);
                        const percent = Math.round((currentIndex / totalPosts) * 90);
                        updateProgress(percent, `در حال تحلیل محتوا و استخراج لینک‌ها (${currentIndex} از ${totalPosts} مقاله)...`);

                        $.ajax({
                            url: localized.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'ex_seo_cluster_scan_batch',
                                nonce: localized.nonce,
                                batch_ids: batch
                            },
                            success: function(batchRes) {
                                if (!batchRes.success) {
                                    console.error('Batch error:', batchRes);
                                }
                                currentIndex += batch.length;
                                processNextBatch();
                            },
                            error: function(err) {
                                console.error('AJAX error during batch:', err);
                                currentIndex += batch.length;
                                processNextBatch();
                            }
                        });
                    }

                    processNextBatch();
                },
                error: function(xhr, status, error) {
                    alert('خطای ارتباط با سرور: ' + error);
                    resetScanState();
                }
            });
        });

        // Step 2: Finalize and Calculate Pillars
        function finalizeScan() {
            updateProgress(92, 'در حال محاسبه ساختار پیلار-کلاستر و مقالات یتیم...');

            $.ajax({
                url: localized.ajax_url,
                type: 'POST',
                data: {
                    action: 'ex_seo_cluster_finalize_scan',
                    nonce: localized.nonce
                },
                success: function(res) {
                    if (res.success) {
                        updateProgress(100, 'اسکن با موفقیت به پایان رسید! در حال بارگذاری مجدد...');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1200);
                    } else {
                        alert(res.data && res.data.message ? res.data.message : 'خطا در ثبت نتایج');
                        resetScanState();
                    }
                },
                error: function() {
                    alert('خطا در نهایی‌سازی اسکن.');
                    resetScanState();
                }
            });
        }

        function updateProgress(percent, text) {
            $progressBar.css('width', percent + '%');
            $progressText.text(text);
        }

        function resetScanState() {
            $startScanBtn.prop('disabled', false).removeClass('updating-message');
            $progressBox.slideUp();
        }

        // Live Table Filtering
        function filterTable() {
            const query    = ($searchInput.val() || '').trim().toLowerCase();
            const category = $categoryFilter.val();
            const role     = $roleFilter.val();

            $tableRows.each(function() {
                const $row      = $(this);
                const title     = ($row.data('title') || '').toString().toLowerCase();
                const rowCat    = ($row.data('category') || '').toString();
                const rowRole   = ($row.data('role') || '').toString();

                let matchSearch = true;
                if (query.length > 0) {
                    matchSearch = title.includes(query);
                }

                let matchCat = true;
                if (category && category !== 'all') {
                    matchCat = rowCat.includes(category);
                }

                let matchRole = true;
                if (role && role !== 'all') {
                    matchRole = (rowRole === role);
                }

                if (matchSearch && matchCat && matchRole) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        }

        $searchInput.on('input', filterTable);
        $categoryFilter.on('change', filterTable);
        $roleFilter.on('change', filterTable);

        // View Details Modal
        $(document).on('click', '.ex-seo-view-details', function(e) {
            e.preventDefault();
            const postId = $(this).data('post-id');

            // Open Modal
            $('#ex-seo-modal').fadeIn(200);
            $('#ex-seo-modal-post-title').text('در حال دریافت اطلاعات...');
            $('#ex-seo-inlinks-list').html('<li class="ex-seo-empty-message">در حال بارگذاری...</li>');
            $('#ex-seo-outlinks-list').html('<li class="ex-seo-empty-message">در حال بارگذاری...</li>');

            $.ajax({
                url: localized.ajax_url,
                type: 'POST',
                data: {
                    action: 'ex_seo_cluster_get_details',
                    nonce: localized.nonce,
                    post_id: postId
                },
                success: function(res) {
                    if (!res.success || !res.data) {
                        $('#ex-seo-inlinks-list').html('<li class="ex-seo-empty-message">خطا در بارگذاری اطلاعات</li>');
                        return;
                    }

                    const post = res.data;
                    activePostDetails = post;

                    $('#ex-seo-modal-post-title').text(post.title);
                    $('#modal-tab-inlinks-count').text(`(${post.inlinks.length})`);
                    $('#modal-tab-outlinks-count').text(`(${post.outlinks.length})`);

                    // Render Inlinks
                    if (post.inlinks && post.inlinks.length > 0) {
                        let html = '';
                        post.inlinks.forEach(function(item) {
                            html += `
                                <li>
                                    <div>
                                        <span class="ex-seo-link-anchor">متن پیوند: «${escapeHtml(item.anchor_text)}»</span>
                                        <div class="ex-seo-link-target">
                                            مبدأ: <a href="${item.source_url}" target="_blank">${escapeHtml(item.source_title)}</a>
                                        </div>
                                    </div>
                                    <div>
                                        ${item.is_nofollow ? '<span class="badge-role badge-deadend">Nofollow</span>' : '<span class="badge-role badge-cluster">Follow</span>'}
                                    </div>
                                </li>
                            `;
                        });
                        $('#ex-seo-inlinks-list').html(html);
                    } else {
                        $('#ex-seo-inlinks-list').html('<li class="ex-seo-empty-message">هیچ مقاله دیگری به این مقاله لینک نداده است (مقاله یتیم).</li>');
                    }

                    // Render Outlinks
                    if (post.outlinks && post.outlinks.length > 0) {
                        let html = '';
                        post.outlinks.forEach(function(item) {
                            html += `
                                <li>
                                    <div>
                                        <span class="ex-seo-link-anchor">متن پیوند: «${escapeHtml(item.anchor_text)}»</span>
                                        <div class="ex-seo-link-target">
                                            مقصد: <a href="${item.raw_url}" target="_blank">${item.raw_url}</a>
                                        </div>
                                    </div>
                                    <div>
                                        ${item.is_nofollow ? '<span class="badge-role badge-deadend">Nofollow</span>' : '<span class="badge-role badge-cluster">Follow</span>'}
                                    </div>
                                </li>
                            `;
                        });
                        $('#ex-seo-outlinks-list').html(html);
                    } else {
                        $('#ex-seo-outlinks-list').html('<li class="ex-seo-empty-message">این مقاله به هیچ مقاله داخلی دیگری لینک نداده است (بن‌بست).</li>');
                    }
                },
                error: function() {
                    $('#ex-seo-inlinks-list').html('<li class="ex-seo-empty-message">خطای سرور</li>');
                }
            });
        });

        // Modal Tab Switching
        $('.ex-seo-modal-tab-btn').on('click', function() {
            const targetTab = $(this).data('tab');
            $('.ex-seo-modal-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.ex-seo-modal-tab-pane').hide();
            $('#ex-seo-tab-' + targetTab).show();
        });

        // Close Modal
        $('.ex-seo-modal-close, .ex-seo-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $('#ex-seo-modal').fadeOut(200);
            }
        });

        // Trigger CSV Exports
        $('.ex-seo-btn-export').on('click', function(e) {
            e.preventDefault();
            const exportType = $(this).data('export-type');
            const url = `${localized.ajax_url}?action=ex_seo_cluster_export&type=${exportType}&nonce=${localized.nonce}`;
            window.location.href = url;
        });

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });

})(jQuery);
