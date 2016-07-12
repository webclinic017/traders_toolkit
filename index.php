<?php
set_include_path( get_include_path() . PATH_SEPARATOR . __DIR__ . '/assets' . PATH_SEPARATOR . __DIR__ . '/assets/library' . PATH_SEPARATOR . __DIR__ .  '/assets/blocks' . PATH_SEPARATOR . __DIR__ . '/settings' . PATH_SEPARATOR . __DIR__ . '/themes' );
spl_autoload_extensions('php');
spl_autoload_register();
