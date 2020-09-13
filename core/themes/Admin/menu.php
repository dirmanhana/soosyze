
<ul class="<?php echo $level === 1 ? 'nav nav-pills' : 'dropdown-menu'; ?>">
    <?php foreach ($menu as $link): ?>

    <li class="<?php echo empty($link[ 'submenu' ]) ? '' : '​dropdown-submenu'; ?> <?php echo $link[ 'link_active' ]; ?>">
        <?php if (!empty($link[ 'submenu' ])): ?>

        <a href="<?php echo $link[ 'link' ]; ?>" 
           class="dropdown-toggle"
           data-toggle="dropdown"
           role="button"
           aria-haspopup="true"
           aria-expanded="false"
           title="<?php echo $link[ 'title_link' ]; ?> ">
            <?php echo !empty($link['icon']) ? "<i class='{$link['icon']}' aria-hidden='true'></i> " : ''; ?><?php echo $link[ 'title_link' ]; ?> <span class="caret"></span>
            
        </a>
        <?php echo $link[ 'submenu' ]; ?>
        <?php else: ?>

        <a href="<?php echo $link[ 'link' ]; ?>"
           <?php if ($link[ 'target_link' ] === '_blank'): ?>
           target="<?php echo $link[ 'target_link' ]; ?>"
           rel="noopener noreferrer" <?php endif; ?>
           title="<?php echo $link[ 'title_link' ]; ?> ">
            <?php echo !empty($link['icon']) ? "<i class='{$link['icon']}' aria-hidden='true'></i> " : ''; ?><?php echo $link[ 'title_link' ]; ?>
            
        </a>
        <?php endif; ?>

    </li>
    <?php endforeach; ?>

</ul>