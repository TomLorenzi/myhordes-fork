# MyHordes Setup

## Development Installation

```bash
# Install Composer
wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet
php composer.phar update

# Update .env file
cp .env .env.local

# Create database
bin/console doctrine:database:create
bin/console doctrine:schema:update --force
bin/console doctrine:fixtures:load --append

# Install yarn
yarn install
yarn encore dev

# Create the crow account (plus 80 debug accounts in case there are none)
bin/console app:debug --add-crow

# Create a new town (available languages de, en, fr)
bin/console app:create-town remote 40 de

# Add debug users (password = username)
bin/console app:debug --add-debug-users

# Fill town with citizens
bin/console app:debug --fill-town <town.id>

# Schedule an attack
bin/console app:schedule --add "tomorrow"

# Schedule the attack now
bin/console app:schedule --add "now"
# Run the scheduled attack
bin/console app:schedule --now
# Add -vvv for verbose output
bin/console app:schedule --now -vvv

# Don't let debug users die
bin/console app:debug --everyone-drink <town.id>

# Every commands
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

## Using Docker
* Run *./docker/setup.sh* wait for completion.
*yarn* can have hard times to install the packages on the container. Run *yarn install* host side to prevent any freeze (or uncomment the line in setup.sh).
* Run *./docker/setup.sh* to setup the db / install PHP packages.
* MyHordes will be accessible by default using the following addresses: *http://127.0.0.1/* - *http://localhost/*

Note 1: If you are running Windows, execute the shell scripts through a proper terminal (Git bash, Windows 10 bash, Cmder, ...)

Note 2: you can change the port by editing *docker/nginx/default.conf* and *docker-compose.yml*

## Configuration

* Edit .env.local to configure database access
* If you don't run MyHordes in your web root, you'll need to create an additional file in the project root named webpack.config.local.js:

```javascript
module.exports = {
    output_path: 'public/build/',   // Path to the build directory
    public_path: '/myhordes/build/' // URL to access the build directory via the webserver
};
```

## If you have found an untranslatable string
Add `T::__('text','domain')` in the PHP file you found it, or `{{ 'text'|trans({},'domain')` , then run the `bin/console app:migrate -t fr` cmdlet to update FR location (available languages : fr, en, de, es and 'all' to update all languages at once)

## How to add a missing RP text
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

## Update

```bash
# Update Composer
php composer.phar update

# Update database
bin/console app:migrate -u -r

# Update yarn
yarn install
yarn encore dev
```
