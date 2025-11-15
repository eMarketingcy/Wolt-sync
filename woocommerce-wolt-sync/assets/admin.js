jQuery(function($){
    $('#wolt-sync-start').on('click', function(){
        if (!confirm('Start manual sync? This will run the scraper immediately.')) return;
        
        var $button = $(this);
        $button.attr('disabled', true).text('Initializing...');
        $('#wolt-sync-progress').show();
        
        // 1. Initial AJAX call to set up the queue
        $.post(WoltSync.ajax_url, { action: 'wolt_sync_manual', nonce: WoltSync.nonce }, function(resp){
            if (!resp.success) { 
                $button.attr('disabled', false).text('Start Manual Sync (AJAX)');
                alert('Error starting sync: ' + resp.data); 
                return; 
            }
            
            var key = resp.data.queue_key;
            var total = resp.data.total;
            
            $button.text('Processing...');

            // 2. Start the worker loop (calls the PHP worker repeatedly)
            function runWorker() {
                $.post(WoltSync.ajax_url, { 
                    action: 'wolt_sync_worker', 
                    nonce: WoltSync.nonce, 
                    queue_key: key 
                }, function(w_resp){
                    
                    if (!w_resp.success) {
                        alert('Worker error: Could not process product. See console.');
                        $button.attr('disabled', false).text('Start Manual Sync (AJAX)');
                        return;
                    }
                    
                    var data = w_resp.data;
                    var pct = total ? Math.round((data.progress.done / total) * 100) : 0;
                    
                    // Update progress bar
                    $('#wolt-sync-bar').css('width', pct + '%');
                    $('#wolt-sync-text').html(data.progress.done + ' / ' + total + ' processed. Errors: ' + data.progress.errors);

                    if (data.finished) {
                        // All done!
                        $button.attr('disabled', false).text('Start Manual Sync (AJAX)');
                        $('#wolt-sync-text').html('Sync complete! Checked ' + total + ' products.');
                        alert('Sync complete!');
                        
                        // Small delay before reloading to allow final DB write
                        setTimeout(function(){ window.location.reload(); }, 500);
                    } else {
                        // Continue processing the next product immediately
                        runWorker();
                    }
                }).fail(function(){
                    alert('Server communication error. Sync paused.');
                    $button.attr('disabled', false).text('Start Manual Sync (AJAX)');
                });
            }

            // Start the recursion
            runWorker();
            
        });
    });
});