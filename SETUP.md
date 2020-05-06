# Setup MyHordes dev server 

#### Tested on Debian 10, Ubuntu 19.04 

## Requirements for apache2 and mariadb method

- PHP (at least 7.4)
  - php-mysql
  - php-xml
  - php-imagick
- imagemagick
- mariadb  (at least 10.1.44)
- apache2
- yarn (using npm)



## Cloning and configuring the project

First of all, install any missing requirements. 

Log in to your database with `sudo mysql -u root` (specify `-p` if you have a local password) and create a new local user, for example :

```sql
CREATE USER 'hordes'@'localhost' IDENTIFIED BY 'hordes_pwd';
```

Exit the mariadb command prompt with CTRL+C or `exit;`

Clone the project inside any directory

```bash
git clone https://apps.benbehrendt.de/kallithea/Privat/MyHordes
```

#### Composer

Open the README file, you will see a wget command to fetch the `composer.phar` file.

In this example, we have this :

```bash
wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet
```

Update composer

```bash
php composer.phar update
```

#### Local configuration

Copy the example config file to the one we will be using

```bash
cp .env .env.local
```

Edit `.env.local` with your favorite text editor.

You will see this line in the file :

`DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7`

Replace `db_user`, `db_password` with the credentials you used for the new database user. If your database is remote, make sure that the user is accessible and not local, then change then IP address from 127.0.0.1 to you database's address, as well as the port if needed.

`db_name` can be whatever you need.

`?serverVersion=x.x` can be deleted.

In our example, this would look like this :

`DATABASE_URL=mysql://hordes:hordes_pwd@127.0.0.1:3306/hordes`

If you want a production server instead of a development server, replace `APP_ENV=dev` by `APP_ENV=prod`

Save the file.

If you would like to override the application config (for example to create custom game rules for your local installation), copy the `config/app` folder to `config/packages/dev/app`. 
All config files in there will overwrite default config files without being pushed to the repository.

#### MyHordes database setup

At this point, you can chose which branch you want to follow. If you want to checkout, do it now.

From the `MyHordes/` directory, run the following commands in order :

```bash
bin/console doctrine:database:create
```

```bash
bin/console doctrine:schema:update --force
```

```bash
bin/console doctrine:fixtures:load --append
```

This will setup your database accordingly to the version you have cloned.

#### Build

Now you can build the project using yarn

```bash
yarn install
```

```
yarn encore <dev,prod>
```

Chose `dev` or `prod` depending on your previous choice.

This can take some time, please be patient.

After the command is successful, you should have contents in the `MyHordes/public/` directory.

## Setting up the apache server

This part will allow you and potentially other user to access the game from a browser via the apache2 web server.

#### Optional: Allowing access through the web

If you want people to access your server, you can follow these optional steps:

- Find the IP address of your server. For this example, let's use 6.6.6.6
- If you own a domain, add a `A` DNS rule pointing to the IP (domain or subdomain)

Let's say your domain name is `myhordes.net`

Domain DNS rule example : `IN A    6.6.6.6`

Typing `myhordes.net` will redirect you to the server 6.6.6.6

Subdomain DNS rule example : `hordes1    IN A    6.6.6.6`

Typing `hordes1.myhordes.net` will redirect you to the server with the address 6.6.6.6

These two rules do not interfere with each other; even with the same IP address. apache2 will be able to handle it. But you should only need one, for making your version accessible.



#### Configuring the Apache Virtual Host

First, you need to make sure your `MyHordes/public` folder is accessible to apache.  You can just do a `chmod 777 -R MyHordes` if you like, but if you want a real production server I would encourage to looking into it further (adding you and apache to a usergroup for example, and using `chown`)

With your favorite text editor, create and edit a new file `/etc/apache2/sites-available/<name>.conf` where `<name>` should be the subdomain.domain name of the previous step, if you did it. In our example, we would create :

`/etc/apache2/sites-available/hordes1.myhordes.conf`

If you don't have a domain or haven't done the previous step, just chose the name you like.

Insert these contents in the newly created file :

```
<VirtualHost *:80>
        ServerName hordes1.myhordes.net
        DocumentRoot /MyHordes/public
        <Directory /MyHordes/public>
                AllowOverride All
                Require all granted
        </Directory>
</VirtualHost>
```

Make sure to replace every instance (2) of `hordes1.myhordes.net` with your own domain name if applicable.

Make sure to replace every instance (2) of `/MyHordes/public` with your own correct path for the project's public directory.

Save the file.

Now, tell apache2 that a new site has been configured :

```bash
a2ensite hordes1.myhordes
```

```
service apache2 reload
```

The server is now accessible from the address you configured (direct IP, or domain if applicable)

If you want to enable https, I recommend using `certbot` but I won't go into depth, it's fairly simple and very optional for a dev server !

## MyHordes first time setup and maintainance

#### Setup

Various actions can be performed from the command line via the `bin/console` script.

When first setting up the project, you should run these commands:

- Add the Crow (Le Corbeau) with test users :

  ```bash
  bin/console app:debug --add-crow
  ```

- Create the very first town :

  ```bash
  bin/console app:create-town remote 40 <en,fr,de,es>
  ```

#### Night attack

To enable the night attack, you should add a cron job.

```bash
crontab -e
```

At the end of the file, add this **in one** line :

`00 00 * * * cd /MyHordes; bin/console app:schedule --add "now"; bin/console app:schedule --now -vvv > attack.log`

Explanation:

- `00 00 * * * *` : Causes the following command to run at midnight every day
- `cd /MyHordes;` : Replace `/MyHordes` with the path to the root of your cloned project. This puts the script's scope in this directory.
- `bin/console app:schedule --add "now";` : This tells the MyHordes server an attack can be run any time from now.
- `bin/console app:schedule --now -vvv` : Actually starts the nightly attack and outputs as much logs as possible.
- ` > attack.log` : Redirects the attack logs to a file. Will overwrite each time, you can use `>>` to append instead.

#### Playing

At this point, you can already play. But the registration needs e-mail verification, and we will not cover it for now. You should disable it by editing the file `MyHordes/src/Service/UserFactory.php`

You will see a line near the beginning starting with `public function createUser`

Right after, create a new line and write `$validated = true;`

Save the file, e-mail verification is now disabled.

To create an account, simply access your server URL, example:  `http://6.6.6.6/` (or your configured domain) and follow the same steps as a regular user. Once you create your account, you should see a town, everything is set.

#### Administration

The console offers many more possibilities. For example, you can fill a town with test users with :

```bash
bin/console app:debug --fill-town <town.id>
```

The town IDs are incremental, so your first town will have 1 as id. To see all towns, use :

```bash
bin/console app:towns
```

See what all the commands can do by adding --help, here is the list :

```bash
bin/console app:citizen --help
bin/console app:create-town --help
bin/console app:create-user --help
bin/console app:debug --help
bin/console app:inventory --help
bin/console app:schedule --help
bin/console app:town --help
bin/console app:towns --help
bin/console app:users --help
```

Now you probably want to become an administrator. Here is how to do it. 

```bash
bin/console app:users
```

Find your user ID in the displayed results.

```bash
bin/console app:user <id> --set-mod-level 2
```

Replace `<id>` with the ID you found during the previous steps. You can also use the value 1 for a simple moderator.

In case you ever need to access the database :

```bash
mysql -u hordes -phordes_pwd hordes
```

The first `hordes` is the db user, `hordes_pwd` is the username and the second `hordes` is the database name (following the previous example)

#### Maintenance

To update the server to a new version, you should follow strict indications or you risk breaking the integrity of your database. If you value the data in it, we recommend doing frequent backups in the first place, and a backup before each update.

Run this command :

```bash
git status
```

**If** you see modifications, (including the previously cited `MyHordes/src/Service/UserFactory.php`), run this command :

```bash
git reset
```

Now you are in a clean state and can run this command :

```bash
git pull
```

You can now redo the changes you liked in the sources. If you don't want to redo all you usual modifications after each maintenance and you know git, create a local branch (or your own forked remote when we migrate to gitlab) in which you will store the changes, and on which you will merge the latest version each time before pulling. You might need to handle conflicts in this case, we do not recommend doing this if you are not familiar with git.

Now you have the latest changes and the site is in an unstable state, users could encounter errors until the maintenance is over. If possible for you, we recommend closing access to the site since we did not yet prepare a way to block it in this project, yet.

Run these commands and the site will be available again :

```bash
php composer.phar update
bin/console app:migrate -u
yarn install
yarn encore dev
```

These commands are mandatory, and others might even be needed in some cases.

Congrats, the site should now be ready for developing! 