MyHordes is an open implementation of the browser game "Hordes" and its other versions by Motion-Twin and developed by the community.
All files into folder `assets/img` are the proprety of Motion-Twin, and are under [CC-BY-NC-SA-4.0 license](https://spdx.org/licenses/CC-BY-NC-SA-4.0.html).
The rest of the project, is under [AGPL3 license](https://spdx.org/licenses/AGPL-3.0-or-later.html).

# Development Installation
#### Tested on Debian 10, Ubuntu 19.04 

## Requierements

* zip
* unzip
* imagemagick
* php (>=7.4)
* php-xml
* php-intl
* php-zip
* php-imagick
* nodejs (>=14.13.1)
* yarn
* apache2

Depending on the database engine you want to use you will need the following:

### Using mariadb
Install the following packages:
* mariadb (>= 10.1.44)
* php-mysql

#### Adding a new user
Log in to your database with `sudo mysql -u root` (specify `-p` if you have a local password) and create a new local user, for example :

```sql
CREATE USER 'hordes'@'localhost' IDENTIFIED BY 'hordes_pwd';
```

Exit the mariadb command prompt with CTRL+C or `exit;`

#### Access the DB
In case you ever need to access the database :

```bash
mysql -u hordes -phordes_pwd hordes
```

The first `hordes` is the db user, `hordes_pwd` is the username and the second `hordes` is the database name (following the previous example)

### Using postgresql
Install the following packages:
* postgresql
* php-pgsql

You can also install pgAdmin to access the database.

## Cloning the project

Clone the project inside any directory

```bash
git clone https://gitlab.com/eternal-twin/myhordes.git
```

## Composer

You need to fetch the `composer.phar` file:

```bash
wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet
```

Update it:

```bash
php composer.phar update
```

## Configuration

Copy the example config file to the one we will be using

```bash
cp .env .env.local
```

Edit `.env.local` with your favorite text editor.

You need to set the `DATABASE_URL` variable depending on the database engine you want to use.

For mariadb:
`DATABASE_URL=mysql://db_user:db_password@db_ip:db_port/db_name`
Default `db_port`: 3306

For postgresql:
`DATABASE_URL=postgresql://db_user:db_password@db_ip:db_port/db_name`
Default `db_port`: 5432

Replace `db_user`, `db_password` with the credentials used for the database user. If your db is hosted on the same computer, you will probably use `localhost`as the db_ip.

`db_name` can be whatever you need. You can change it during development to test on more databases.

## Populate the database

Create the database:
```bash
bin/console app:migrate -i
```

## Build

Now you can build the project using yarn

First, install all the dependencies

```bash
yarn install
```

Then, let's compile the assets

```
yarn encore dev
```

After the command is successful, you should have contents in the `public/` directory.

## Update

To update the server to a new version, you should follow strict indications or you risk breaking the integrity of your database. If you value the data in it, we recommend doing frequent backups in the first place, and a backup before each update.

Make sure the repository is in a clean state (save your work) or run the following command:
```bash
git reset
```

Make sure the repository is cleaned:
```bash
git status
```

Run this command to update your local copy:
```bash
git pull
```

You can now redo the changes you liked in the sources. If you don't want to redo all your usual modifications after each maintenance and you know git, create a local branch (or your own forked remote when we migrate to gitlab) in which you will store the changes, and on which you will merge the latest version each time before pulling. You might need to handle conflicts in this case, we do not recommend doing this if you are not familiar with git.

Now you have the latest changes and the site is in an unstable state, users could encounter errors until the maintenance is over. If possible for you, we recommend closing access to the site since we did not yet prepare a way to block it in this project, yet.

Update composer:
```bash
php composer.phar update
```

Update database:
```bash
bin/console app:migrate -u
```

In case the migrations fail, you should try to append `-r` to the previous command:
```bash
bin/console app:migrate -u -r
```

Re-build:
```bash
yarn install
yarn encore dev
```

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

First, you need to make sure your `public` folder is accessible to apache. You can just do a `chmod 777 -R MyHordes` if you like, but if you want a real production server I would encourage to looking into it further (adding you and apache to a usergroup for example, and using `chown`)

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

Now, tell apache2 that a new site has been configured :

```bash
a2ensite hordes1.myhordes
```

```
service apache2 reload
```

The server is now accessible from the address you configured (direct IP, or domain if applicable)

Optional: If you want to enable https you can use `certbot`

## First run

Various actions can be performed from the command line via the `bin/console` script.

When first setting up the project, you should run these commands:

- Add the Crow (Le Corbeau) with test users :
```bash
bin/console app:debug --add-crow
```

- Create the very first town :
```bash
bin/console app:town:create remote 40 <en,fr,de,es>
```

### Playing

At this point, you can already play. But the registration needs e-mail verification, and we will not cover it for now. You should disable it by editing the file `src/Service/UserFactory.php`

You will see a line near the beginning starting with `public function createUser`

Right after, create a new line and write `$validated = true;`

Save the file, e-mail verification is now disabled.

To create an account, simply access your server URL, example:  `http://6.6.6.6/` (or your configured domain) and follow the same steps as a regular user. Once you create your account, you should see a town, everything is set.

## Using Docker
You can also setup a development environment using Docker:
* Run *./docker/setup.sh* wait for completion.
*yarn* can have hard times to install the packages on the container. Run *yarn install* host side to prevent any freeze (or uncomment the line in setup.sh).
* Run *./docker/setup.sh* to setup the db / install PHP packages.
* MyHordes will be accessible by default using the following addresses: *http://127.0.0.1/* - *http://localhost/*

Note 1: If you are running Windows, execute the shell scripts through a proper terminal (Git bash, Windows 10 bash, Cmder, ...)

Note 2: you can change the port by editing *docker/nginx/default.conf* and *docker-compose.yml*

## Tips and tricks

### Using a production environment

Replace `APP_ENV=dev` by `APP_ENV=prod` in `.env.local`

Then you will need to re-build the project using:
```bash
yarn install
yarn encore dev
```

### Overriding application config

If you would like to override the application config (for example to create custom game rules for your local installation), copy the `config/app` folder to `config/packages/dev/app`. 
All config files in there will overwrite default config files without being pushed to the repository.

### Running MyHordes in another folder

If you do not run MyHordes within the web root (i.e. you have it running in a folder such as `example.com/myhordes`), you need to create an additional config file in the root directory called `webpack.config.local.js`. The content should look like this:
```javascript
module.exports = {
    // Relative path to the build folder from the MyHordes root folder
    output_path: 'public/build/',
    
    // URL to the build folder from webroot
    public_path: '/myhordes/build/',
   
    // Optional; should be set to false on dev servers and omitted (or set to true) on
    // production servers.
    // When set to false, massively speeds up yarn asset compilation, but will cause
    // caching issues when used in a production setup!
    hash_filenames: false
};
```

Edit these settings to fit your environment.

### If you have found an untranslatable string
Add `T::__('text','domain')` in the PHP file you found it, or `{{ 'text'|trans({},'domain')` , then run the `bin/console app:migrate -t fr` cmdlet to update FR location (available languages : fr, en, de, es and 'all' to update all languages at once)

### How to add a missing RP text
Go to your soul, and go read the first page of the RP text. Then, open your Web Console (CTRL+MAJ+I).
On the first page, copy paste this command : `console.log($("#ghost_pages .document").attr('class'))`. Note down the "bg_" and "design_" classes.
The, paste this command : `console.log(new URLSearchParams(window.location.hash.substring(window.location.hash.lastIndexOf('?'))).get('bkey').split(';')[0])`
Then, on each pages, run this command : `console.log($("#ghost_pages .document .content").html().replace(/'/gi, "\\\'"))`.
Once you have everything, in `src/DataFixture/TextFixtures.php`, add a new element as follow :
```
"<FIRSTCOMMAND>" => [
    "title" => "<TITLEOFYOURTEXT>",
    "author" => "<AUTHOR>",
    "content" => [
        '<CONTENTOFPAGE1>',
        '<CONTENTOFPAGE2>',
        ...
    ],
    "lang" => "<LANGOFTHERP>" // choose between fr, de, en, es,
    "background" => "<CONTENTOFBG_ ATTR>",
    "design" => "<CONTENTOFDESIGN_ ATTR>"
],
```
If you want to import a lot, it saves time to combine the commands into a function paste this once and then run ```x()``` in the console for each text/page:
```
var x = function () {
  var g = $('#ghost_pages'), d = g.find('.document'), z = '', h = window.location.hash || z,
    bkey = new URLSearchParams(h.substring(h.lastIndexOf('?'))).get('bkey').split(';')[0] || z,
    title = g.find('h2').html() || z,
    cls = d.attr('class') || z,
    txt = d.find('.content').html().replace(/'/gi, "\\'"),
    pages = g.find('div.pages').html() || z,
    author = g.find('div.author').html() || z,
    body = [
      'Title: ', title, '\n', 'bkey: ', bkey, '\n', cls, '\n', txt, '\n', author, pages
    ].join(z);
  console.log(body);
};
```
Pay attention with predefined texts from Motion Twin (mostly with anonymous authors), it will overwrite previous translations, best to add language suffix to these text ids.

Then update the fixtures:
```
bin/console doctrine:schema:update --force
bin/console doctrine:fixtures:load --append
```

### Start the local Eternal-Twin server
```bash
yarn run etwin
# Or start it in the background
# yarn run etwin &
```

# Various commands

## Create the crow account and 79 other test accounts
It will also create 80 debug accounts in case there are none.
```
bin/console app:debug --add-crow
```

# Create a new town
Available languages : fr, en, de, es, multi
```
bin/console app:town:create remote 40 <en,fr,de,es,multi>
```

### Fill town with citizens
```
bin/console app:debug --fill-town <town.id>
```

### Night attack

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

#### Schedule an attack
```
bin/console app:schedule --add "tomorrow"
```

#### Schedule the attack now
```
bin/console app:schedule --add "now"
```

#### Run the scheduled attack
```
bin/console app:schedule --now
```

### Administration

The console offers many more possibilities. For example, you can fill a town with test users with :

```bash
bin/console app:debug --fill-town <town.id>
```

The town IDs are incremental, so your first town will have 1 as id. To see all towns, use :

```bash
bin/console app:town:list
```

See what all the commands can do by adding --help, here is the list :

```bash
bin/console app:citizen --help
bin/console app:town:create --help
bin/console app:user:create --help
bin/console app:debug --help
bin/console app:inventory --help
bin/console app:schedule --help
bin/console app:town:inspect --help
bin/console app:town:list --help
bin/console app:user:list --help
```

### Become administrator

```bash
bin/console app:user:list
```

Find your user ID in the displayed results.

```bash
bin/console app:user <id> --set-mod-level 4
```

Replace `<id>` with the ID you found during the previous steps. You can also use the value 1 for a simple moderator.

### Add -vvv for verbose output
```
bin/console app:schedule --now -vvv
```

### Don't let debug users die
```
bin/console app:debug --everyone-drink <town.id>
```

### Every commands
```
bin/console app:citizen --help
bin/console app:town:inspect --help
bin/console app:town:list --help
bin/console app:town:create --help
bin/console app:user:list --help
bin/console app:user:create --help
bin/console app:debug --help
bin/console app:inventory --help
bin/console app:schedule --help
```

### Allowing access through the web

If you want people to access your server, you can follow these optional steps:

- Find the IP address of your server. For this example, let's use 6.6.6.6
- If you own a domain, add a `A` DNS rule pointing to the IP (domain or subdomain)

Let's say your domain name is `myhordes.net`

Domain DNS rule example : `IN A    6.6.6.6`

Typing `myhordes.net` will redirect you to the server 6.6.6.6

Subdomain DNS rule example : `hordes1    IN A    6.6.6.6`

Typing `hordes1.myhordes.net` will redirect you to the server with the address 6.6.6.6

These two rules do not interfere with each other; even with the same IP address. apache2 will be able to handle it. But you should only need one, for making your version accessible.

## Troubleshooting

### Fatal Error: composer.lock was created for PHP version 7.4 or higher but the current PHP version is X.X.XX

This means apache2 is using the wrong module for PHP. You can disable any PHP version it is using by using this command (for example for PHP 7.3):
```bash
a2dismod php7.3
```

Then enable the correct PHP version:
```bash
a2enmod php7.4
```

And restart apache2:
```bash
service apache2 restart
```

### Session data file is not created by your uid

Make sure apache2 is run by the same user as the one owning `var/cache/dev/sessions`

If not either change the owner of the sessions directory or the user running apache2 (by editing `APACHE_RUN_USER` and `APACHE_RUN_GROUP` in `/etc/apache2/envvars` and then restarting apache2)
