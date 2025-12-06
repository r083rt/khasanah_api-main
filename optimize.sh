#optimize app

git pull origin master

export COMPOSER_ALLOW_SUPERUSER=1;
composer install --ignore-platform-reqs

#production
php artisan migrate --force

#development
# php artisan migrate:fresh --seed


composer dump-autoload

# running swoole
# php artisan swoole:http reload
