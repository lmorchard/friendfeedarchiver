<ul class="entries">

    <?php foreach ($entries as $entry): ?>
        <?php 
            $entry_classes = array(
                'entry', 'service-'.$entry['service']['id']
            );
        ?>
        <li id="<?php echo $entry['id'] ?>" class="<?php echo join(' ', $entry_classes) ?>">
            <?php
                $view_name = 'common/services/' . $entry['service']['id'];
                if (! Kohana::find_file('views', $view_name) )
                    $view_name = 'common/services/default';

                View::factory($view_name)->set(array(
                    'entry' => $entry
                ))->render(TRUE);
            ?>
        </li>
    <?php endforeach ?>

</ul>

<?php if (config::item('config.debug')): ?>
    <pre><?php echo var_export($entries, true) ?></pre>
<?php endif ?>
