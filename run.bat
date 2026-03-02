cd /d C:\laragon\www\2kocms

tar --exclude=vendor --exclude=node_modules --exclude=.git --exclude=storage --exclude=.env --exclude=public\storage -a -c -f 2kocms-full-needed.zip ^
  app ^
  bootstrap ^
  config ^
  database ^
  docs ^
  public ^
  resources ^
  routes ^
  scripts ^
  tests ^
  artisan ^
  composer.json ^
  composer.lock ^
  package.json ^
  package-lock.json ^
  vite.config.js ^
  tailwind.config.js ^
  postcss.config.js ^
  phpunit.xml ^
  .env.example