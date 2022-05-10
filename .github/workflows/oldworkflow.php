name: Pushing to the staging server live

on:
  push:
    branches:
      - main
jobs:
  setup:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Copy .env
        run: php -r "file_exists('.env') || copy('.env.example', '.env');"
      - name: Install Dependencies
        run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
      - name: Generate key
        run: php artisan key:generate
      - name: Directory Permissions
        run: chmod -R 777 storage bootstrap/cache
      - name: Create Database
        run: |
          mkdir -p database
          touch database/database.sqlite
      - name: Execute tests (Unit and Feature tests) via PHPUnit
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: database/database.sqlite
        run: vendor/bin/phpunit

  deploy:
    name: Deploy
    runs-on: ubuntu-latest
    needs: setup
    steps:
      - uses: actions/checkout@v2.1.0
        with:
          fetch-depth: 2

      - name: Install Composer Dependencies
        run: composer install --no-ansi --no-interaction --no-scripts --no-suggest --no-progress --prefer-dist

      - name: Create zipped vendor directory
        uses: montudor/action-zip@v0.1.0
        with:
          args: zip -qq -r ./vendor.zip ./vendor

      - name: FTP-Deploy-Action
        uses: SamKirkland/FTP-Deploy-Action@3.1.1
        with:
          ftp-server: ${{ secrets.ftp_server }}
          ftp-username: ${{ secrets.ftp_username }}
          ftp-password: ${{ secrets.ftp_password }}

  post-deploy:
      runs-on: ubuntu-latest
      needs: deploy
      steps:
        - name: Pass ssh
          uses: atymic/deployer-php-action@master
          with:
            ssh-known-hosts: ${{ secrets.ssh_host }}
            ssh-private-key: ${{ secrets.ssh_key }}


