<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:brunocmoraes/wm-crm.git');
set('writable_mode', 'chmod');
set('bin/php', '/opt/plesk/php/8.2/bin/php');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts 8G65*hz[HeVAm8

host('webmor.com.br')
    ->set('port', 1157)
    ->set('remote_user', 'ferr1015')
    ->set('http_user', 'ferr1015')
    ->set('deploy_path', '~/apps/webmor/crm');

// Hooks
/*
after('artisan:migrate', 'others');
task('others', function () {
    run('cd {{release_path}} && /opt/plesk/php/8.1/bin/php artisan responsecache:clear');
    run('cd {{release_path}} && /opt/plesk/php/8.1/bin/php artisan icons:cache');
}); */

after('deploy:failed', 'deploy:unlock');
