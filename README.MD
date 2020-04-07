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

# Create a new town (available languages de, en, fr)
bin/console app:create-town remote 40 de

# Add debug users (password = password)
bin/console app:debug --add-debug-users

# Schedule an attack
bin/console app:schedule --add "tomorrow"

# Run the attack
bin/console app:schedule --now
# Or, to make the attack now
bin/console app:schedule --add "now"


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
Add `T::__('text','domain')` in the PHP file you found it, or `{{ 'text'|trans({},'domain')` , then run the `bin/console translation:update --force --sort=asc fr --prefix=""` cmdlet to update FR location (available languages : fr, en, de, es)

## Update

```bash
# Update Composer
php composer.phar update

# Update database
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate -n
bin/console doctrine:fixtures:load --append

# Update yarn
yarn install
yarn encore dev
```
