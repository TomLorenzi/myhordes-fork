MyHordes is an open implementation developed by the community of the browser game "Hordes" and its other versions made by [Motion-Twin](https://motion-twin.com/fr/).
The whole folder is under [CC-BY-NC-SA-4.0 license](https://creativecommons.org/licenses/by-nc-sa/4.0/), some part of the code use [Hordes open-source code](https://github.com/motion-twin/WebGamesArchives/tree/main/Hordes).

# Development Installation
#### Tested on Debian 10, Ubuntu 19.04 

## Requierements

* zip
* unzip
* imagemagick
* php (>=8.2)
* php-xml
* php-intl
* php-zip
* php-imagick
* php-curl
* php-mbstring
* apache2

Depending on the database engine you want to use you will need the following:

### Using mariadb
Install the following packages:
* mariadb-server (>= 10.1.44)
* php-mysql

#### Starting the database server

Before working with the database, you need to start the database server:

```bash
sudo service mysql start
```

#### Adding a new user
Log in to your database with `sudo mysql -u root` (specify `-p` if you have a local password) and create a new local user, for example :

```sql
CREATE USER 'hordes'@'localhost' IDENTIFIED BY 'hordes_pwd';
```

Then give it all privileges for the wanted database name
```sql
GRANT ALL PRIVILEGES ON myhordes.*  TO 'hordes'@'localhost';
```

Then flush the privileges so MySQL/MariaDB knows your latest changes
```sql
FLUSH PRIVILEGES;
```

Exit the mariadb command prompt with CTRL+C or `exit;`

#### Access the DB
In case you ever need to access the database :

```bash
mysql -u hordes -p hordes_pwd hordes
```

The first `hordes` is the db user, `hordes_pwd` is the username and the second `hordes` is the database name (following the previous example)

## Cloning the project

Clone the project inside any directory

```bash
git clone https://gitlab.com/eternaltwin/myhordes/myhordes.git
```

## NodeJS, Yarn and Composer

First, go to the MyHordes directory (`cd myhordes`). 

Install NodeJS and yarn:

```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
npm install --global yarn
```

Afterwards, you may need to *restart your machine*. Then, you need to fetch the `composer.phar` file:

```bash
wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet
```

Update yarn and composer:

```bash
php composer.phar install
yarn install
```

## Configuration

Copy the example config file to the one we will be using

```bash
cp .env .env.local
```

Edit `.env.local` with your favorite text editor (like nano or gedit):

```bash
nano .env.local
```

You need to set the `DATABASE_URL` variable depending on the database engine you want to use.

For mariadb:
`DATABASE_URL=mysql://db_user:db_password@db_ip:db_port/db_name`
Default `db_port`: 3306

For postgresql:
`DATABASE_URL=postgresql://db_user:db_password@db_ip:db_port/db_name`
Default `db_port`: 5432

Replace `db_user`, `db_password` with the credentials used for the database user. If your db is hosted on the same computer, you will probably use `localhost` as the db_ip.

`db_name` can be whatever you need. You can change it during development to test on more databases.

When using MySQL, you need to disable sql mode ONLY_FULL_GROUP_BY by running this query.
`SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));`

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

Now you have the latest changes, and the site is in an unstable state, users could encounter errors until the maintenance is over. If possible for you, we recommend closing access to the site since we did not yet prepare a way to block it in this project, yet.

Update composer:
```bash
php composer.phar install
```

Update database:
```bash
bin/console app:migrate -u
bin/console app:migrate -p
```

In case the migrations fail, you should try to append `-r` to the previous command:
```bash
bin/console app:migrate -u -r
bin/console app:migrate -p
```

Re-build:
```bash
yarn install
yarn encore dev
```

Sometimes, elements or features pulled from the Git may be incorrect or missing. If that happens, you need to clear the cache to fix it:
```bash
bin/console cache:clear
```

## Setting up the apache server

This part will allow you and potentially other users to access the game from a browser via the apache2 web server.

#### Configuring the Apache Virtual Host

First, you need to make sure your `public` folder is accessible to apache. You can just do a `chmod 777 -R MyHordes` if you like, but if you want a real production server I would encourage to looking into it further (adding you and apache to a usergroup for example, and using `chown`)

With your favorite text editor, create and edit a new file `/etc/apache2/sites-available/myhordes.conf`. For example :

`nano /etc/apache2/sites-available/myhordes.conf`

Insert these contents in the newly created file :

```
<VirtualHost *:80>
        ServerName localhost
        DocumentRoot /MyHordes/public
        <Directory /MyHordes/public>
                AllowOverride All
                Require all granted
        </Directory>
</VirtualHost>
```

Make sure to replace every instance (2) of `/MyHordes/public` with your own correct path for the project's public directory. For example : `/home/<username>/myhordes/public`.

Now, tell apache2 that a new site has been configured :

```bash
sudo a2ensite hordes1.myhordes
```

MyHordes requires the "rewrite" apache module, which must be enabled manually be running

```bash
sudo a2enmod rewrite
```

Afterwards, the server must be restarted:

```
sudo service apache2 restart
```

The server is now accessible by typing "localhost" in your browser.

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

Remember, when you re-start your PC or server, you need to run these two commands to start the database and Apache:
```bash
sudo service apache2 start
sudo service mysql start
```

## Using Docker
You can also setup a development environment using Docker. The detailed instructions and scripts could be found in the [MyHordes-Docker repository](https://gitlab.com/eternaltwin/myhordes/myhordes-docker)

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
    // Relative path to the build and service folders from the MyHordes root folder
    output_path: 'public/build/',
    output_service_path: 'public/service/',

    // URL to the build and service folders from webroot
    public_path: '/myhordes/build/',
    public_service_path: '/myhordes/service/',

    // Optional; should be set to false on dev servers and omitted (or set to true) on
    // production servers.
    // When set to false, massively speeds up yarn asset compilation, but will cause
    // caching issues when used in a production setup!
    hash_filenames: false,

    // Override Favicon configuration
    favicon: {
        image: './assets/img/favicon.png',
        background: '#100C0B',
        theme_color: '#7e4d2a',
    }
};
```

Edit these settings to fit your environment.

### If you have found an untranslatable string
Add `T::__('text','domain')` in the PHP file you found it, or `{{ 'text'|trans({},'domain')` , then run the `bin/console app:translation:update fr` cmdlet to update FR location (available languages : fr, en, de, es and 'all' to update all languages at once)

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

### Start the local Eternaltwin server
```bash
yarn run etwin
# Or start it in the background
# yarn run etwin &
```

### Modify the bank lock permanently

The bank lock is set in the `rules.yml` file which is by default located in the `config/app` directory.

First copy the `rules.yml` file in the `config/packages/dev/app` directory.
```
cp config/app/rules.yml config/packages/dev/app
```
The file you created is going to be the one used in the game instead of the original one. So you need to edit this one and not the other, else it will not work. 
```
nano config/packages/dev/app/rules.yml
```
Then search for the `bank abuse` block and edit the values of `limit` and `chaos_limit` to 5000 for example. Don't forget to save the file when you're done.
```
bank_abuse:
        limit: 5000
        chaos_limit: 5000
        base_range_min: 5
        lock_range_min: 15
```
It will allow you to take up to 5000 items in the bank without being blocked. It will apply to all existing towns and new ones.

Note : if you want to reactivate the bank lock or modify the values, you'll just have to modify that same file again.

# Various commands

## Move translation strings between domains

If you want to move translation entries from one domain to another (for example, from `game` to `gazette`), you can use
the following command:
```
app:translation:move-domain <source domain> <target domain> --match <source file>
```
This will move all translations coming from `source file` from `source domain` to `target domain`. You can find out the
source file of a specific translation from the `from` note within the translation files. There can be multiple `--match`
entries with this command.

Please note that translations entries coming from **multiple** sources are only moved if **all of their sources** are
indicated using `--match`.

## Create the crow account and 79 other test accounts
It will also create 80 debug accounts in case there are none.
```
bin/console app:debug --add-crow
```

## Create a new town
Available languages : fr, en, de, es, multi
```
bin/console app:town:create remote 40 <en,fr,de,es,multi>
```

## Fill town with citizens
```
bin/console app:debug --fill-town <town.id>
```

## CronJob

MyHordes requires a special command to be executed regularly to function properly, for example to run the nightly 
attack. To create a cron job on your server, run

```bash
crontab -e
```

At the end of the file, add this **in one** line :

`* * * * * cd /MyHordes; bin/console app:cron`

Explanation:

- `* * * * *` : Causes the following command to run once every minute.
- `cd /MyHordes;` : Replace `/MyHordes` with the path to the root of your cloned project. This puts the script's scope in this directory.
- `bin/console app:cron` : This tells the cron job to call the MyHordes cron handler.

## Schedule an attack
By default, MyHordes runs its attack at midnight. However, the time point of the very first attack must be specified manually.

Schedule an attack at the next midnight:
```
bin/console app:schedule --add "tomorrow"
```
Schedule the attack now:
```
bin/console app:schedule --add now
bin/console app:cron
```

## Administration

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

### Commands not working because of PHP version not up to date

If certain commands don't work, it might be because of your PHP version being too old. As said in the requirements, you need PHP 8.1. 

You can check your current version of PHP with this command :
```
php --version
```

If you need to update your PHP, use the following commands : 
```
sudo apt install software-properties-common && sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt upgrade -y
```
You can then check again if you have the correct version with the same `php --version` command.

### Fatal Error: composer.lock was created for PHP version 8.1 or higher but the current PHP version is X.X.XX / Composer detected issues in your platform: Your Composer dependencies require a PHP version ">=8.1.0".

This means apache2 is using the wrong module for PHP. You can upgrade the PHP version by using this command (for example for PHP 7.4) :
```
sudo a2dismod php7.4 ; sudo a2enmod php8.1 ; sudo service apache2 restart 
```

### Session data file is not created by your uid

Make sure apache2 is run by the same user as the one owning `var/cache/dev/sessions`

If not either change the owner of the sessions directory or the user running apache2 (by editing `APACHE_RUN_USER` and `APACHE_RUN_GROUP` in `/etc/apache2/envvars` and then restarting apache2)
