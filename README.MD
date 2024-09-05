# Affinidi Login Plugin for Shopware

This contains the shopware plugin which integrates affinidi login into your shopware application

## Setup Shopware

1. Run the below command to create a shopware container in the local machine

```
docker-compose up -d
```

2. Check Shopware Admin
   Open http://localhost/admin in your browser after the installation has finished. You should see the Shopware admin interface.
   The default credentials are:
   User: admin
   Password: shopware

3. Check Shopware Store
   Open http://localhost/ in your browser, You should see the Shopware store with some default products.

4. If you want sync shopware source code from the container

```
docker cp shopware:/var/www/html/. ./src
```

## Install Affinidi Login Plugin

1. Option 1 : Using Shopware Admin Portal

You zip the directory `AffinidiLoginPlugin` and upload it in shopware admin portal `Extensions -> My extensions -> Upload extension`

2. Option 2 : Copy the plugin from local to plugins directory of shopware code running inside a container

```
docker cp ./AffinidiLoginPlugin shopware:/var/www/html/custom/plugins/.
```

3. Post that open the Admin Portal and then install, activate the plugin 

### Handy commands (for my development)

- Remove plugin, need to run this command inside the constainer 

```
sudo rm -rf custom/plugins/AffinidiLoginPlugin
```

- Commands to clear cache etc..

```
php bin/console cache:clear
php bin/console plugin:refresh
php bin/console theme:compile
php bin/console plugin:install --activate AffinidiLoginPlugin
php bin/console debug:router
```
