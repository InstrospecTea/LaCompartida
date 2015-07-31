<?php
$invalidated = null;
if( isset($_GET['invalidate-cache-plz']) ){
    $invalidated = opcache_reset();

    if( isset($_GET['json']) ){
        $response = array(
            'msg' => $invalidated ? 'invalidated' : 'not invalidated',
            'code' => intval($invalidated),
        );

        echo json_encode($response);
        exit;
    }
}

$status = opcache_get_status();
unset($status['scripts']);
?>

<html>
<head></head>
<body>
    <h1>OPCache Status</h1>

<?php if( $invalidated === true ): ?>
    <h2>Cache Invalidated :D!</h2>
    <script>
    setTimeout(function(){
        window.location = window.location.pathname;
    }, 5000);
    </script>
<?php elseif($invalidated === false): ?>
    <h2>Couldn't Invalidate Cache D: !!</h2>
<?php endif ?>

    <h2>General</h2>
    <ul>
        <li>
            <strong>Enabled: </strong>
            <span><?php echo ($status['opcache_enabled'] ? 'Yes' : 'No') ?></span>
        </li>
        <li>
            <strong>Cache Full: </strong>
            <span><?php echo ($status['cache_full'] ? 'Yes' : 'No') ?></span>
        </li>

        <li>
            <strong>Memory Usage: </strong>
            <span>Used: <?php echo round($status['memory_usage']['used_memory'] / (1024*1024)) ?>MB / Free: <?php echo round($status['memory_usage']['free_memory'] / (1024*1024)) ?>MB</span>
        </li>
    </ul>

    <h2>Statistics</h2>
    <ul>
    <?php foreach ($status['opcache_statistics'] as $key => $value): ?>
        <li>
            <strong><?php echo ucwords(str_replace('_', ' ', $key)) ?>: </strong>
        <?php if( strpos($key, "time") !== false ): ?>
            <span><?php echo date("d/m/Y H:i:s", $value) ?></span>
        <?php else: ?>
            <span><?php echo $value ?></span>
        <?php endif ?>
        </li>
    <?php endforeach ?>
    </ul>
</body>
</html>
