<table class="table">
    <thead>
        <tr>
        <?php foreach($attrs as $attr): ?>
            <th><?php echo $attr; ?></th>
        <?php endforeach; ?>

        </tr>
    </thead>
    <?php foreach($rows as $row): ?>
        <tr>
        <?php foreach($attrs as $attr): ?>
            <td><?php echo $row[$attr]; ?></td>
        <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>