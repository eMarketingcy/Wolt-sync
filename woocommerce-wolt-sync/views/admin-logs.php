<?php
$page = max(1, intval($_GET['p'] ?? 1));
$data = Wolt_Sync\Logger::get_logs( $page );
$rows = $data['rows'];
$total = $data['total'];
?>
<div class="wrap"><h1>Wolt Sync Logs</h1>
<table class="widefat"><thead><tr><th>ID</th><th>Product</th><th>Level</th><th>Message</th><th>Date</th></tr></thead><tbody>
<?php foreach( $rows as $r ): ?>
<tr><td><?php echo $r->id ?></td><td><?php echo $r->product_id ?></td><td><?php echo $r->level ?></td><td><?php echo esc_html( $r->message ) ?></td><td><?php echo $r->created_at ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php
$pages = ceil( $total / 50 );
for( $i=1;$i<=$pages;$i++ ) {
    echo '<a href="?page=wolt-sync-logs&p='.$i.'">'.$i.'</a> ';
}
?></div>
